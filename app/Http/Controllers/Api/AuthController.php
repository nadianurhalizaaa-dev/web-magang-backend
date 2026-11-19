<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $existingUser = User::where('email', $request->input('email'))->first();
        $validated = $request->validate([
            'nama' => 'required_without:name|string|max:255',
            'name' => 'required_without:nama|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existingUser?->id)],
            'nim' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nim')->ignore($existingUser?->id)],
            'password' => 'required|string|min:8',
            'role' => ['sometimes', Rule::in(['Pemagang', 'pemagang', 'peserta'])],
            'no_hp' => 'nullable|string|max:255',
        ]);

        if ($existingUser) {
            if ($existingUser->nim !== null
                || ! in_array(strtolower((string) $existingUser->role), ['peserta', 'pemagang', 'calon pemagang'], true)
                || ! Hash::check($validated['password'], $existingUser->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email sudah terdaftar. Silakan masuk menggunakan akun tersebut.',
                ], 422);
            }

            $existingUser->nim = $validated['nim'] ?? null;
            $existingUser->no_hp = $validated['no_hp'] ?? $existingUser->no_hp;
            $existingUser->save();

            return response()->json([
                'status' => 'success',
                'message' => 'NIM berhasil ditambahkan ke akun Anda. Silakan masuk.',
                'data' => [
                    'user' => $this->presentUser($existingUser),
                ],
            ]);
        }

        $user = User::create([
            'name' => $validated['nama'] ?? $validated['name'],
            'email' => $validated['email'],
            'nim' => $validated['nim'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'peserta',
            'no_hp' => $validated['no_hp'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi akun berhasil',
            'data' => [
                'user' => $this->presentUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function createPembimbing(Request $request): JsonResponse
    {
        if (strtolower((string) $request->user()?->role) !== 'admin') {
            return response()->json([
                'message' => 'Hanya Admin yang dapat menambahkan Pembimbing.',
            ], 403);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'no_hp' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'pembimbing',
            'no_hp' => $validated['no_hp'] ?? null,
        ]);

        return response()->json([
            'data' => $this->presentUser($user),
            'message' => 'Akun Pembimbing berhasil dibuat.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $identifier = trim($validated['email']);
        
        // Cari pengguna berdasarkan Email ATAU NPM/NIM (users.nim) ATAU Kode Peserta (magangs.participant_code)
        $user = User::with('magang')
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('nim', $identifier)
                    ->orWhereHas('magang', function ($sub) use ($identifier) {
                        $sub->where('participant_code', $identifier);
                    });
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email/NPM atau password salah',
            ], 401);
        }

        if (in_array($user->role, ['peserta', 'user'], true)
            && $user->email === 'peserta@absensi.com') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun peserta bersama tidak dapat digunakan. Silakan masuk menggunakan email dan password akun pribadi.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $this->presentUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->presentUser($request->user()->load('magang')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nama' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nim' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nim')->ignore($user->id)],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'program_studi' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:6'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'nim.unique' => 'NIM/NPM tersebut sudah terdaftar pada akun peserta lain. Mohon periksa kembali NIM Anda.',
            'email.unique' => 'Alamat email tersebut sudah digunakan oleh akun lain.',
            'email.email' => 'Format email yang dimasukkan tidak valid.',
            'password.min' => 'Kata sandi baru minimal harus 6 karakter.',
            'foto.max' => 'Ukuran file foto maksimal adalah 5 MB.',
            'foto.image' => 'File harus berupa gambar dengan format JPG, JPEG, PNG, atau WEBP.',
        ]);

        $name = $validated['nama'] ?? $validated['name'] ?? null;
        if (!empty($name)) {
            $user->name = $name;
            $user->nama = $name;
        }

        if (!empty($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (array_key_exists('nim', $validated)) {
            $user->nim = $validated['nim'];
        }

        if (array_key_exists('no_hp', $validated)) {
            $user->no_hp = $validated['no_hp'];
        }

        if (array_key_exists('program_studi', $validated)) {
            $user->program_studi = $validated['program_studi'];
        }

        if (array_key_exists('alamat', $validated)) {
            $user->alamat = $validated['alamat'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('avatars', 'public');
            $user->foto = 'storage/' . $path;
        }

        try {
            $user->save();
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            if ($errorCode == 1062 || str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == 23000) {
                if (str_contains($e->getMessage(), 'users_nim_unique') || str_contains($e->getMessage(), 'nim')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'NIM/NPM tersebut sudah terdaftar pada akun peserta lain. Silakan periksa kembali atau gunakan NIM Anda yang benar.',
                        'errors' => ['nim' => ['NIM/NPM sudah terdaftar pada akun lain.']],
                    ], 422);
                }
                if (str_contains($e->getMessage(), 'users_email_unique') || str_contains($e->getMessage(), 'email')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Alamat email tersebut sudah terdaftar pada akun lain. Silakan gunakan alamat email yang berbeda.',
                        'errors' => ['email' => ['Email sudah terdaftar pada akun lain.']],
                    ], 422);
                }
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data yang dimasukkan (NIM atau Email) sudah terdaftar pada akun lain.',
                ], 422);
            }
            throw $e;
        }

        if ($user->magang) {
            try {
                $user->magang->update([
                    'participant_name' => $user->name,
                ]);
            } catch (\Throwable $th) {
                // Ignore secondary sync failures
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui!',
            'data' => $this->presentUser($user->fresh()->load('magang')),
        ]);
    }

    public function getPembimbing(Request $request): JsonResponse
    {
        if (strtolower((string) $request->user()?->role) !== 'admin') {
            return response()->json([
                'message' => 'Hanya Admin yang dapat melihat daftar Pembimbing.',
            ], 403);
        }

        $pembimbings = User::where('role', 'pembimbing')->get();

        return response()->json([
            'data' => $pembimbings->map(fn (User $user) => $this->presentUser($user)),
        ]);
    }

    private function presentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name ?? $user->nama,
            'nama' => $user->name ?? $user->nama,
            'email' => $user->email,
            'nim' => $user->nim,
            'program_studi' => $user->program_studi,
            'alamat' => $user->alamat,
            'role' => $user->role,
            'no_hp' => $user->no_hp,
            'foto' => $user->foto,
            'magang' => $user->relationLoaded('magang') ? $user->magang : null,
        ];
    }
}
