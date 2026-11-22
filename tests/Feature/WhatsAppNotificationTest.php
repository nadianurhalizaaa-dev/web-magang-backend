<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    public function test_acceptance_and_registration_notifications_create_manual_whatsapp_links(): void
    {
        Http::fake();

        $acceptance = WhatsAppService::createAcceptanceLink('081234567890', 'Peserta Contoh');
        $adminAlert = WhatsAppService::createAdminRegistrationLink(
            '+6283196853453',
            'Peserta Contoh',
            '123456',
            'Kampus Contoh',
            'Periode Januari',
        );

        $this->assertTrue($acceptance['manual']);
        $this->assertFalse($acceptance['sent']);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $acceptance['url']);
        $this->assertStringContainsString('telah%20diterima', $acceptance['url']);
        $this->assertTrue($adminAlert['manual']);
        $this->assertStringStartsWith('https://wa.me/6283196853453?text=', $adminAlert['url']);
        $this->assertStringContainsString('123456', $adminAlert['url']);
        Http::assertNothingSent();
    }

    public function test_manual_whatsapp_link_is_not_created_without_a_destination_number(): void
    {
        $notification = WhatsAppService::createAcceptanceLink('', 'Peserta Contoh');

        $this->assertFalse($notification['manual']);
        $this->assertArrayNotHasKey('url', $notification);
    }
}
