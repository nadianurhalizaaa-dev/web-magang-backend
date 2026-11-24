<?php

namespace Tests\Feature;

use App\Models\PeriodeMagang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagangPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_a_participant_to_a_period_and_load_it_with_that_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $periode = PeriodeMagang::create([
            'nama_periode' => 'Periode 2024',
            'tanggal_mulai' => '2024-08-01',
            'tanggal_selesai' => '2024-11-30',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/magang', [
                'participant_code' => '211013001',
                'participant_name' => 'Peserta Uji',
                'periode_id' => $periode->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.periode_id', $periode->id);

        $this->assertDatabaseHas('magangs', [
            'participant_code' => '211013001',
            'participant_name' => 'Peserta Uji',
            'periode_id' => $periode->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/periode')
            ->assertOk()
            ->assertJsonPath('data.0.magangs.0.participant_code', '211013001');
    }

    public function test_pembimbing_can_save_a_participant_to_a_period(): void
    {
        $pembimbing = User::factory()->create(['role' => 'pembimbing']);
        $periode = PeriodeMagang::create([
            'nama_periode' => 'Periode 2024',
            'tanggal_mulai' => '2024-08-01',
            'tanggal_selesai' => '2024-11-30',
        ]);

        $this->actingAs($pembimbing, 'sanctum')
            ->postJson('/api/magang', [
                'participant_code' => '211013002',
                'participant_name' => 'Peserta Uji Pembimbing',
                'periode_id' => $periode->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.periode_id', $periode->id);
    }
}
