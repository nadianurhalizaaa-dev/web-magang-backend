<?php

namespace Tests\Feature;

use App\Models\KomentarAplikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KomentarAplikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_comment_uses_the_account_name_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Peserta Magang',
            'email' => 'peserta@example.com',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/komentar', [
                'rating' => 5,
                'komentar' => 'Pengalaman yang menyenangkan.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user.name', 'Peserta Magang')
            ->assertJsonPath('data.user.email', 'peserta@example.com');

        $this->assertDatabaseHas('komentar_aplikasi', [
            'user_id' => $user->id,
            'nama_pengguna' => 'Peserta Magang',
            'email' => 'peserta@example.com',
        ]);
    }

    public function test_guest_cannot_submit_an_application_comment(): void
    {
        $this->postJson('/api/komentar', [
            'rating' => 5,
            'komentar' => 'Pengalaman yang menyenangkan.',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('komentar_aplikasi', 0);
    }
}
