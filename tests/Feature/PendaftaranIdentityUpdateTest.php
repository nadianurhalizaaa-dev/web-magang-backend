<?php

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Models\PeriodeMagang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendaftaranIdentityUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pembimbing_can_update_identity_of_an_assigned_participant(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $participant = User::factory()->create([
            'role' => 'peserta',
            'nim' => '231013001',
        ]);
        $pendaftaran = Pendaftaran::create([
            'user_id' => $participant->id,
            'pembimbing_id' => $pembimbing->id,
            'asal_instansi' => 'Kampus Uji',
            'file_surat_pengantar' => 'surat-pengantar.pdf',
            'status' => 'Pemagang Aktif',
            'tanggal_daftar' => now()->toDateString(),
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->putJson("/api/pendaftaran/{$pendaftaran->id}/identitas", [
                'name' => 'Nama Peserta Baru',
                'nim' => '231013099',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Nama Peserta Baru')
            ->assertJsonPath('data.user.nim', '231013099');

        $this->assertDatabaseHas('users', [
            'id' => $participant->id,
            'name' => 'Nama Peserta Baru',
            'nim' => '231013099',
        ]);
    }

    public function test_pembimbing_cannot_update_identity_of_an_unassigned_participant(): void
    {
        $assignedPembimbing = User::factory()->create(['role' => 'pembimbing']);
        $otherPembimbing = User::factory()->create(['role' => 'pembimbing']);
        $participant = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::create([
            'user_id' => $participant->id,
            'pembimbing_id' => $assignedPembimbing->id,
            'asal_instansi' => 'Kampus Uji',
            'file_surat_pengantar' => 'surat-pengantar.pdf',
            'status' => 'Pemagang Aktif',
            'tanggal_daftar' => now()->toDateString(),
        ]);

        $this->actingAs($otherPembimbing, 'sanctum')
            ->putJson("/api/pendaftaran/{$pendaftaran->id}/identitas", [
                'name' => 'Nama Tidak Sah',
                'nim' => '231013999',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'id' => $participant->id,
            'name' => 'Nama Tidak Sah',
            'nim' => '231013999',
        ]);
    }

    public function test_pembimbing_can_remove_registration_from_period_without_deleting_account(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $participant = User::factory()->create([
            'role' => 'peserta',
            'nim' => '231013002',
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

        $this->actingAs($pembimbing, 'sanctum')
            ->deleteJson("/api/pendaftaran/{$pendaftaran->id}/periode")
            ->assertOk();

        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'periode_id' => null,
        ]);
        $this->assertDatabaseHas('users', ['id' => $participant->id]);
    }

    public function test_pembimbing_cannot_remove_another_supervisors_registration_from_period(): void
    {
        $assignedPembimbing = User::factory()->create(['role' => 'pembimbing']);
        $otherPembimbing = User::factory()->create(['role' => 'pembimbing']);
        $participant = User::factory()->create(['role' => 'peserta']);
        $periode = PeriodeMagang::create([
            'nama_periode' => 'Periode Uji',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-03-01',
        ]);
        $pendaftaran = Pendaftaran::create([
            'user_id' => $participant->id,
            'periode_id' => $periode->id,
            'pembimbing_id' => $assignedPembimbing->id,
            'asal_instansi' => 'Kampus Uji',
            'file_surat_pengantar' => 'surat-pengantar.pdf',
            'status' => 'Pemagang Aktif',
            'tanggal_daftar' => '2025-01-01',
        ]);

        $this->actingAs($otherPembimbing, 'sanctum')
            ->deleteJson("/api/pendaftaran/{$pendaftaran->id}/periode")
            ->assertForbidden();

        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'periode_id' => $periode->id,
        ]);
    }

    public function test_pembimbing_can_remove_unassigned_registration_when_the_period_only_has_their_assignments(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $assignedParticipant = User::factory()->create([
            'role' => 'peserta',
            'nim' => '231013003',
        ]);
        $unassignedParticipant = User::factory()->create([
            'role' => 'peserta',
            'nim' => '231013004',
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
        $unassignedRegistration = Pendaftaran::create([
            'user_id' => $unassignedParticipant->id,
            'periode_id' => $periode->id,
            'pembimbing_id' => null,
            'asal_instansi' => 'Kampus Uji',
            'file_surat_pengantar' => 'surat-pengantar.pdf',
            'status' => 'Pemagang Aktif',
            'tanggal_daftar' => '2025-01-01',
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->deleteJson("/api/pendaftaran/{$unassignedRegistration->id}/periode")
            ->assertOk();

        $this->assertDatabaseHas('pendaftarans', [
            'id' => $unassignedRegistration->id,
            'periode_id' => null,
        ]);
        $this->assertDatabaseHas('users', ['id' => $unassignedParticipant->id]);
    }
}
