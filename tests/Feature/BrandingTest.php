<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_logo_is_used_until_a_custom_logo_is_uploaded(): void
    {
        Storage::fake('public');
        $settings = app(Settings::class);

        $this->assertStringEndsWith('/assets/images/silver-line-logo.jpeg', $settings->logoUrl());
        $this->assertFalse($settings->hasCustomLogo());
        $this->assertStringStartsWith('data:image/jpeg;base64,', $settings->logoDataUri());

        Storage::disk('public')->put(
            'logos/custom.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jOZ8AAAAASUVORK5CYII=')
        );
        $settings->set(['clinic_logo' => 'logos/custom.png']);

        $this->assertSame(Storage::disk('public')->url('logos/custom.png'), $settings->logoUrl());
        $this->assertTrue($settings->hasCustomLogo());
        $this->assertStringStartsWith('data:image/png;base64,', $settings->logoDataUri());
    }
}
