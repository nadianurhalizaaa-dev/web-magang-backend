<?php

namespace Tests\Feature;

use App\Models\AktivitasMagang;
use App\Models\AlumniMagang;
use App\Models\CompanyProfile;
use App\Models\KomentarAplikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_and_manage_landing_page_content(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/landing-page/profil', [
            'nama_perusahaan' => 'PAM TECHNO',
            'deskripsi_singkat' => 'Portal program magang.',
            'tentang_kantor' => 'Informasi contoh untuk halaman perusahaan.',
            'bidang_usaha' => 'Teknologi',
            'visi' => 'Mendukung pembelajaran.',
            'misi' => 'Memberi pengalaman praktik.',
            'alamat' => 'Jakarta',
            'email' => 'admin@example.test',
            'telepon' => '021000000',
            'instagram' => '@pamtechno',
        ])->assertOk()
            ->assertJsonPath('data.nama_perusahaan', 'PAM TECHNO')
            ->assertJsonPath('data.instagram', '@pamtechno')
            ->assertJsonMissingPath('data.website');

        $alumni = $this->postJson('/api/admin/landing-page/alumni', [
            'nama' => 'Peserta Contoh',
            'asal_instansi' => 'Kampus Contoh',
            'jurusan' => 'Teknik Informatika',
            'periode_magang' => 'Januari-Maret 2026',
            'kesan_pesan' => 'Pengalaman belajar yang baik.',
        ])->assertCreated()->assertJsonPath('data.nama', 'Peserta Contoh');
        $alumniId = $alumni->json('data.id');

        $this->putJson("/api/admin/landing-page/alumni/{$alumniId}", [
            'nama' => 'Peserta Contoh',
            'asal_instansi' => 'Kampus Contoh',
            'jurusan' => 'Sistem Informasi',
            'periode_magang' => 'Januari-Maret 2026',
            'kesan_pesan' => 'Pengalaman belajar yang baik.',
        ])->assertOk()->assertJsonPath('data.jurusan', 'Sistem Informasi');

        $this->postJson('/api/admin/landing-page/aktivitas', [
            'judul' => 'Aktivitas Contoh',
            'deskripsi' => 'Kegiatan pembelajaran peserta.',
            'tanggal' => '2026-02-10',
            'kategori' => 'Pelatihan',
            'lokasi' => 'PAM TECHNO',
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.judul', 'Aktivitas Contoh');

        $comment = KomentarAplikasi::create([
            'user_id' => $admin->id,
            'nama_pengguna' => 'Pemberi Ulasan',
            'email' => 'review@example.test',
            'rating' => 4,
            'komentar' => 'Ulasan contoh.',
            'status' => 'pending',
        ]);

        $this->patchJson("/api/admin/landing-page/komentar/{$comment->id}/status", [
            'status' => 'published',
        ])->assertOk()->assertJsonPath('data.status', 'published');

        $this->getJson('/api/admin/landing-page')
            ->assertOk()
            ->assertJsonPath('data.profil_kantor.nama_perusahaan', 'PAM TECHNO')
            ->assertJsonCount(1, 'data.alumni_magang')
            ->assertJsonCount(1, 'data.aktivitas_magang')
            ->assertJsonCount(1, 'data.komentar_aplikasi');

        $this->deleteJson("/api/admin/landing-page/alumni/{$alumniId}")->assertOk();
        $this->deleteJson("/api/admin/landing-page/komentar/{$comment->id}")->assertOk();
        $this->assertDatabaseMissing('alumni_magang', ['id' => $alumniId]);
        $this->assertDatabaseMissing('komentar_aplikasi', ['id' => $comment->id]);
    }

    public function test_landing_page_admin_routes_require_admin_authentication(): void
    {
        $this->getJson('/api/admin/landing-page')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['role' => 'peserta']));
        $this->getJson('/api/admin/landing-page')->assertForbidden();
    }
}
