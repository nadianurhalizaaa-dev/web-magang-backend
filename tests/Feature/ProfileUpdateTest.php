<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_participant_can_update_their_profile(): void
    {
        Storage::fake('public');
        $participant = User::factory()->create([
            'role' => 'peserta',
            'password' => 'old-password',
        ]);

        $response = $this->actingAs($participant, 'sanctum')->post('/api/me/update', [
            'nama' => 'Nama Peserta Baru',
            'name' => 'Nama Peserta Baru',
            'email' => 'peserta-baru@example.test',
            'nim' => '231013018',
            'no_hp' => '08123456789',
            'program_studi' => 'Sistem Informasi',
            'alamat' => 'Jakarta',
            'password' => 'new-password',
            'role' => 'admin',
            'foto' => UploadedFile::fake()->createWithContent(
                'foto.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l7sAAAAASUVORK5CYII=')
            ),
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Nama Peserta Baru')
            ->assertJsonPath('data.email', 'peserta-baru@example.test')
            ->assertJsonPath('data.nim', '231013018')
            ->assertJsonPath('data.program_studi', 'Sistem Informasi')
            ->assertJsonPath('data.role', 'peserta');

        $participant->refresh();
        $this->assertSame('Nama Peserta Baru', $participant->name);
        $this->assertSame('peserta-baru@example.test', $participant->email);
        $this->assertTrue(Hash::check('new-password', $participant->password));
        Storage::disk('public')->assertExists($response->json('data.foto'));
    }

    public function test_profile_update_requires_authentication(): void
    {
        $this->postJson('/api/me/update', [
            'nama' => 'Nama Peserta',
            'name' => 'Nama Peserta',
        ])->assertUnauthorized();
    }
}
