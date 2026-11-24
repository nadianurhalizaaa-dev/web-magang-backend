<?php

namespace Tests\Feature;

use App\Models\PdfFinalSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfFinalSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_configure_combined_signature_image_once_for_automatic_reuse(): void
    {
        Storage::fake('local');
        $supervisor = User::factory()->create(['role' => 'pembimbing']);
        $signature = $this->fakePng('signature.png');

        $this->actingAs($supervisor, 'sanctum')
            ->getJson('/api/v1/laporan-magang/pdf-assets')
            ->assertOk()
            ->assertJsonPath('data.configured', false);

        $this->actingAs($supervisor, 'sanctum')
            ->post('/api/v1/laporan-magang/pdf-assets', [
                'signature' => $signature,
            ])
            ->assertCreated()
            ->assertJsonPath('data.configured', true);

        $setting = PdfFinalSetting::findOrFail(1);
        $this->assertSame(1, $setting->id);
        Storage::disk('local')->assertExists($setting->signature_path);

        $this->actingAs($supervisor, 'sanctum')
            ->get('/api/v1/laporan-magang/pdf-assets/signature')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($supervisor, 'sanctum')
            ->post('/api/v1/laporan-magang/pdf-assets', [
                'signature' => $this->fakePng('new-signature.png'),
            ])
            ->assertStatus(409);
    }

    public function test_participant_cannot_access_signature_settings(): void
    {
        $participant = User::factory()->create(['role' => 'peserta']);

        $this->actingAs($participant, 'sanctum')
            ->getJson('/api/v1/laporan-magang/pdf-assets')
            ->assertForbidden();
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l3sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
