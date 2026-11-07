<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Services\SequenceGenerator;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['full_name' => 'Ali Raza', 'age' => 34, 'gender' => 'male', 'phone' => '0300 1234567', 'address' => 'Gujranwala'], $overrides);
    }

    public function test_receptionist_registers_patient_with_generated_id(): void
    {
        $this->actingAs($this->receptionist())->post(route('patients.store'), $this->payload())->assertRedirect();

        $p = Patient::query()->sole();
        $this->assertSame('PAT-'.now()->year.'-00001', $p->patient_code);
        $this->assertSame('3001234567', $p->phone_normalized);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'entity_type' => 'patient', 'entity_id' => $p->id]);
    }

    public function test_patient_ids_are_unique_and_sequential(): void
    {
        $user = $this->receptionist();
        foreach (['A One', 'B Two', 'C Three'] as $i => $name) {
            $this->actingAs($user)->post(route('patients.store'), $this->payload(['full_name' => $name, 'phone' => '030000000'.$i]));
        }

        $codes = Patient::query()->orderBy('id')->pluck('patient_code')->all();
        $this->assertSame(['PAT-'.now()->year.'-00001', 'PAT-'.now()->year.'-00002', 'PAT-'.now()->year.'-00003'], $codes);
    }

    public function test_patient_id_format_is_configurable(): void
    {
        app(Settings::class)->set(['patient_id_prefix' => 'MRN', 'patient_id_format' => '{PREFIX}{SEQ}', 'patient_id_padding' => '4']);

        $this->assertSame('MRN0001', app(SequenceGenerator::class)->patientCode());
        $this->assertSame('MRN0002', app(SequenceGenerator::class)->patientCode());
    }

    public function test_duplicate_is_flagged_and_can_be_confirmed(): void
    {
        $user = $this->receptionist();
        $this->actingAs($user)->post(route('patients.store'), $this->payload());

        $this->actingAs($user)->post(route('patients.store'), $this->payload(['full_name' => 'ali raza']))->assertSessionHas('duplicates');
        $this->assertSame(1, Patient::query()->count());

        $this->actingAs($user)->post(route('patients.store'), $this->payload(['confirm_duplicate' => 1]));
        $this->assertSame(2, Patient::query()->count());
    }

    public function test_validation_requires_name_gender_and_age_or_dob(): void
    {
        $this->actingAs($this->receptionist())->post(route('patients.store'), ['full_name' => '', 'gender' => 'x'])
            ->assertSessionHasErrors(['full_name', 'gender', 'age']);
    }

    public function test_search_by_name_id_and_phone(): void
    {
        $user = $this->receptionist();
        $p = $this->patient(['full_name' => 'Zainab Akhtar', 'phone' => '0321-5550199', 'patient_code' => 'PAT-2026-00077']);
        $this->patient(['full_name' => 'Someone Else', 'phone' => '0300 0000000']);

        foreach (['Zainab', 'PAT-2026-00077', '00077', '5550199'] as $q) {
            $res = $this->actingAs($user)->getJson(route('patients.search', ['q' => $q]))->assertOk()->json();
            $this->assertCount(1, $res, "query $q");
            $this->assertSame($p->id, $res[0]['id']);
        }
        $this->actingAs($user)->get(route('patients.index', ['q' => 'Zainab']))->assertSee('Zainab Akhtar')->assertDontSee('Someone Else');
    }

    public function test_filters_by_gender_and_registration_date(): void
    {
        $user = $this->receptionist();
        $this->patient(['full_name' => 'Old Female', 'gender' => 'female', 'registration_date' => '2025-01-10']);
        $this->patient(['full_name' => 'New Male', 'gender' => 'male', 'registration_date' => now()->toDateString()]);

        $this->actingAs($user)->get(route('patients.index', ['gender' => 'female']))->assertSee('Old Female')->assertDontSee('New Male');
        $this->actingAs($user)->get(route('patients.index', ['from' => now()->toDateString()]))->assertSee('New Male')->assertDontSee('Old Female');
    }

    public function test_receptionist_sees_profile_but_not_clinical_tabs(): void
    {
        $p = $this->patient();
        $user = $this->receptionist();

        $this->actingAs($user)->get(route('patients.show', $p))->assertOk()->assertSee($p->patient_code)->assertDontSee('Medical History');
        $this->actingAs($user)->get(route('patients.show', [$p, 'tab' => 'prescriptions']))->assertForbidden();
        $this->actingAs($user)->get(route('patients.history-pdf', $p))->assertForbidden();
    }

    public function test_patient_update_is_audited_and_code_is_immutable(): void
    {
        $p = $this->patient();
        $code = $p->patient_code;

        $this->actingAs($this->receptionist())->put(route('patients.update', $p), $this->payload(['full_name' => 'Changed Name', 'patient_code' => 'HACK-1']))->assertRedirect();

        $this->assertSame('Changed Name', $p->fresh()->full_name);
        $this->assertSame($code, $p->fresh()->patient_code);
        $this->assertDatabaseHas('activity_logs', ['action' => 'updated', 'entity_type' => 'patient', 'entity_id' => $p->id]);
    }

    public function test_archiving_keeps_the_record(): void
    {
        $p = $this->patient();
        $this->actingAs($this->receptionist())->delete(route('patients.destroy', $p))->assertForbidden();

        $this->actingAs($this->admin())->delete(route('patients.destroy', $p))->assertRedirect();
        $this->assertSoftDeleted($p);
        $this->actingAs($this->admin())->post(route('patients.restore', $p->id))->assertRedirect();
        $this->assertNotSoftDeleted($p);
    }

    public function test_medical_history_entries_keep_their_change_log(): void
    {
        $p = $this->patient();
        $doctor = $this->doctor();

        $this->actingAs($doctor)->post(route('patients.history.store', [$p, 'allergy']), ['allergen' => 'Penicillin', 'allergy_type' => 'drug', 'severity' => 'moderate', 'status' => 'active'])->assertRedirect();
        $allergy = $p->allergies()->sole();
        $this->assertSame($doctor->id, $allergy->recorded_by);

        $this->actingAs($doctor)->put(route('patients.history.update', [$p, 'allergy', $allergy->id]), ['allergen' => 'Penicillin', 'allergy_type' => 'drug', 'severity' => 'severe', 'status' => 'active']);
        $this->actingAs($doctor)->delete(route('patients.history.destroy', [$p, 'allergy', $allergy->id]), ['reason' => 'Entered in error']);

        $this->assertSoftDeleted($allergy);
        $log = $this->actingAs($doctor)->get(route('patients.history.changes', [$p, 'allergy', $allergy->id]))->assertOk();
        $log->assertSee('moderate')->assertSee('severe')->assertSee('Entered in error');

        $this->actingAs($this->receptionist())->post(route('patients.history.store', [$p, 'condition']), ['category' => 'condition', 'name' => 'X', 'status' => 'current'])->assertForbidden();
    }

    public function test_print_profile_renders(): void
    {
        $p = $this->patient();
        $this->actingAs($this->receptionist())->get(route('patients.print', $p))->assertOk()->assertSee($p->full_name);
    }
}
