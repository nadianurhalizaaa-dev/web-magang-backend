<?php

namespace Tests\Feature;

use App\Models\Magang;
use App\Models\Absensi;
use App\Models\Pembimbing;
use App\Models\Pendaftaran;
use App\Models\PeriodeMagang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagangIdentityUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pembimbing_can_update_a_legacy_participant_in_their_period(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $legacyPembimbing = Pembimbing::create([
            'nama' => $pembimbing->name,
            'email' => $pembimbing->email,
        ]);
        $magang = Magang::create([
            'participant_code' => '211013001',
            'participant_name' => 'Nama Lama',
            'institution' => 'Kampus Uji',
            'pembimbing_id' => $legacyPembimbing->id,
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->putJson("/api/magang/{$magang->id}/identitas", [
                'participant_code' => '211013099',
                'participant_name' => 'Nama Baru',
            ])
            ->assertOk()
            ->assertJsonPath('data.participant_code', '211013099')
            ->assertJsonPath('data.participant_name', 'Nama Baru');

        $this->assertDatabaseHas('magangs', [
            'id' => $magang->id,
            'participant_code' => '211013099',
            'participant_name' => 'Nama Baru',
        ]);
    }

    public function test_pembimbing_cannot_update_an_unassigned_legacy_participant(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $otherPembimbing = Pembimbing::create([
            'nama' => 'Pembimbing Lain',
            'email' => 'lain@example.test',
        ]);
        $magang = Magang::create([
            'participant_code' => '211013002',
            'participant_name' => 'Nama Peserta',
            'institution' => 'Kampus Uji',
            'pembimbing_id' => $otherPembimbing->id,
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->putJson("/api/magang/{$magang->id}/identitas", [
                'participant_code' => '211013099',
                'participant_name' => 'Nama Tidak Sah',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('magangs', [
            'id' => $magang->id,
            'participant_code' => '211013099',
            'participant_name' => 'Nama Tidak Sah',
        ]);
    }

    public function test_pembimbing_can_update_legacy_record_linked_to_their_registration_by_nim(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $participant = User::factory()->create([
            'role' => 'peserta',
            'nim' => '211013003',
        ]);
        $periode = PeriodeMagang::create([
            'nama_periode' => 'Periode Uji',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-03-01',
        ]);
        Pendaftaran::create([
            'user_id' => $participant->id,
            'periode_id' => $periode->id,
            'pembimbing_id' => $pembimbing->id,
            'asal_instansi' => 'Kampus Uji',
            'file_surat_pengantar' => 'surat-pengantar.pdf',
            'status' => 'Pemagang Aktif',
            'tanggal_daftar' => '2025-01-01',
        ]);
        $magang = Magang::create([
            'participant_code' => '211013003',
            'participant_name' => 'Nama Lama',
            'institution' => 'Kampus Uji',
            'periode_id' => $periode->id,
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->putJson("/api/magang/{$magang->id}/identitas", [
                'participant_code' => '211013099',
                'participant_name' => 'Nama Baru',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $participant->id,
            'nim' => '211013099',
            'name' => 'Nama Baru',
        ]);
        $this->assertDatabaseHas('magangs', [
            'id' => $magang->id,
            'participant_code' => '211013099',
            'participant_name' => 'Nama Baru',
        ]);
    }

    public function test_pembimbing_can_update_unlinked_legacy_record_when_the_period_has_only_their_assignments(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $assignedParticipant = User::factory()->create([
            'role' => 'peserta',
            'nim' => '211013004',
        ]);
        $periode = PeriodeMagang::create([
            'nama_periode' => 'Periode Uji',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-03-01',
        ]);
        Pendaftaran::create([
            'user_id' => $assignedParticipant->id,
            'periode_id' => $periode->id,
            'pembimbing_id' => $pembimbing->id,
            'asal_instansi' => 'Kampus Uji',
            'file_surat_pengantar' => 'surat-pengantar.pdf',
            'status' => 'Pemagang Aktif',
            'tanggal_daftar' => '2025-01-01',
        ]);
        $magang = Magang::create([
            'participant_code' => '211013005',
            'participant_name' => 'Peserta Lama',
            'institution' => 'Kampus Uji',
            'periode_id' => $periode->id,
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->putJson("/api/magang/{$magang->id}/identitas", [
                'participant_code' => '211013099',
                'participant_name' => 'Nama Baru',
            ])
            ->assertOk();

        $this->assertDatabaseHas('magangs', [
            'id' => $magang->id,
            'participant_code' => '211013099',
            'participant_name' => 'Nama Baru',
        ]);
    }

    public function test_pembimbing_can_remove_linked_participant_from_period_without_deleting_account_or_attendance(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $participant = User::factory()->create([
            'role' => 'peserta',
            'nim' => '211013006',
        ]);
        $periode = PeriodeMagang::create([
            'nama_periode' => 'Periode Uji',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-03-01',
        ]);
        $pendaftaran = Pendaftaran::create([
            'user_id' => $participant->id,
            'periode_id' => $periode->id,
            'pembimbing_id' => $pembimbing->id,
            'asal_instansi' => 'Kampus Uji',
            'file_surat_pengantar' => 'surat-pengantar.pdf',
            'status' => 'Pemagang Aktif',
            'tanggal_daftar' => '2025-01-01',
        ]);
        $magang = Magang::create([
            'participant_code' => '211013006',
            'participant_name' => $participant->name,
            'institution' => 'Kampus Uji',
            'periode_id' => $periode->id,
        ]);
        $absensi = Absensi::create([
            'user_id' => $participant->id,
            'magang_id' => $magang->id,
            'participant_code' => '211013006',
            'participant_name' => $participant->name,
            'tanggal' => '2025-02-01',
            'status' => 'Hadir',
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->deleteJson("/api/magang/{$magang->id}/periode", [
                'registration_id' => $pendaftaran->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('magangs', [
            'id' => $magang->id,
            'periode_id' => null,
        ]);
        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'periode_id' => null,
        ]);
        $this->assertDatabaseHas('users', ['id' => $participant->id]);
        $this->assertDatabaseHas('absensis', [
            'id' => $absensi->id,
            'magang_id' => $magang->id,
            'participant_code' => '211013006',
        ]);
    }

    public function test_pembimbing_cannot_remove_an_unassigned_legacy_participant_from_period(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $otherPembimbing = Pembimbing::create([
            'nama' => 'Pembimbing Lain',
            'email' => 'lain@example.test',
        ]);
        $periode = PeriodeMagang::create([
            'nama_periode' => 'Periode Uji',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-03-01',
        ]);
        $magang = Magang::create([
            'participant_code' => '211013007',
            'participant_name' => 'Peserta Lain',
            'institution' => 'Kampus Uji',
            'periode_id' => $periode->id,
            'pembimbing_id' => $otherPembimbing->id,
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->deleteJson("/api/magang/{$magang->id}/periode")
            ->assertForbidden();

        $this->assertDatabaseHas('magangs', [
            'id' => $magang->id,
            'periode_id' => $periode->id,
        ]);
    }
}
