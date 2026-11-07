<?php

namespace Tests\Unit;

use App\Models\ConsultationVitals;
use App\Models\Patient;
use App\Services\PrescriptionService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClinicRulesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** End date is inclusive: day 1 is the start date. */
    #[DataProvider('durations')]
    public function test_expected_end_date(string $start, int $days, string $end): void
    {
        $this->assertSame($end, PrescriptionService::expectedEndDate(Carbon::parse($start), $days)->toDateString());
    }

    public static function durations(): array
    {
        return [
            'single day' => ['2026-03-10', 1, '2026-03-10'],
            'three days' => ['2026-03-10', 3, '2026-03-12'],
            'month boundary' => ['2026-01-30', 5, '2026-02-03'],
            'leap year' => ['2028-02-27', 3, '2028-02-29'],
            'zero treated as one' => ['2026-03-10', 0, '2026-03-10'],
        ];
    }

    #[DataProvider('phones')]
    public function test_phone_normalisation(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Patient::normalizePhone($input));
    }

    public static function phones(): array
    {
        return [
            'local format' => ['0300-1234567', '3001234567'],
            'international' => ['+92 300 1234567', '3001234567'],
            'spaces and brackets' => ['(0300) 123 4567', '3001234567'],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    public function test_current_age_prefers_date_of_birth_then_advances_recorded_age(): void
    {
        Carbon::setTestNow('2026-11-07');

        $withDob = new Patient(['date_of_birth' => '1990-11-08', 'age' => 99]);
        $this->assertSame(35, $withDob->currentAge());

        $withAge = new Patient(['age' => 40, 'age_recorded_on' => '2024-06-01']);
        $this->assertSame(42, $withAge->currentAge());

        $this->assertNull((new Patient)->currentAge());
    }

    public function test_bmi_and_blood_pressure_formatting(): void
    {
        $v = new ConsultationVitals(['weight' => 70, 'height' => 175, 'systolic_bp' => 120, 'diastolic_bp' => 80]);
        $this->assertSame(22.9, $v->bmi());
        $this->assertSame('120/80 mmHg', $v->bloodPressure());

        $this->assertNull((new ConsultationVitals(['weight' => 70]))->bmi());
        $this->assertTrue((new ConsultationVitals)->isEmpty());
    }
}
