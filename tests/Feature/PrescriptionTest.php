<?php

namespace Tests\Feature;

use App\Enums\MedicationCourseStatus;
use App\Enums\PrescriptionStatus;
use App\Models\Consultation;
use App\Models\MedicationRepetition;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\PrescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->doctor = $this->doctor();
        $this->patient = $this->patient();
    }

    /** Runs a visit → consultation → completion and returns the finalised prescription. */
    private function prescribe(array $items): Prescription
    {
        $this->actingAs($this->doctor)->post(route('visits.store'), ['patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'visit_type' => 'walk_in']);
        $this->actingAs($this->doctor)->post(route('visits.consult', $this->patient->visits()->latest('id')->first()));
        $c = Consultation::query()->latest('id')->first();
        $this->actingAs($this->doctor)->put(route('consultations.update', $c), ['action' => 'complete', 'diagnosis' => 'Test diagnosis', 'items' => $items, 'general_instructions' => 'Drink water'])
            ->assertRedirect(route('consultations.show', $c));

        return $c->prescriptions()->with('items')->sole();
    }

    public function test_prescription_with_multiple_medicines_and_links(): void
    {
        $rx = $this->prescribe([$this->item(), $this->item(['medicine_name' => 'Test Medicine B', 'duration_days' => 10]), $this->item(['medicine_name' => 'Custom Syrup', 'medicine_id' => null])]);

        $this->assertSame(PrescriptionStatus::Finalized, $rx->status);
        $this->assertCount(3, $rx->items);
        $this->assertSame(['Test Medicine A', 'Test Medicine B', 'Custom Syrup'], $rx->items->pluck('medicine_name')->all());
        $this->assertSame($this->patient->id, $rx->patient_id);
        $this->assertNotNull($rx->consultation_id);
        $this->assertNotNull($rx->visit_id);
        $this->assertMatchesRegularExpression('/^RX-\d{4}-\d{6}$/', $rx->prescription_number);
    }

    public function test_item_validation(): void
    {
        $this->actingAs($this->doctor)->post(route('visits.store'), ['patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'visit_type' => 'walk_in']);
        $this->actingAs($this->doctor)->post(route('visits.consult', $this->patient->visits()->first()));
        $c = Consultation::query()->first();

        $this->actingAs($this->doctor)->put(route('consultations.update', $c), ['action' => 'save', 'items' => [['medicine_name' => 'X', 'dosage' => '', 'frequency' => '', 'duration_days' => 0]]])
            ->assertSessionHasErrors(['items.0.dosage', 'items.0.frequency', 'items.0.duration_days']);
    }

    public function test_duration_calculations_per_medicine(): void
    {
        Carbon::setTestNow('2026-03-10 11:00:00');
        $rx = $this->prescribe([$this->item(['duration_days' => 1]), $this->item(['medicine_name' => 'Med 3', 'duration_days' => 3]), $this->item(['medicine_name' => 'Med 30', 'duration_days' => 30])]);

        $courses = $rx->courses()->orderBy('id')->get();
        $this->assertCount(3, $courses);
        $this->assertSame(['2026-03-10', '2026-03-12', '2026-04-08'], $courses->map(fn ($c) => $c->expected_end_date->toDateString())->all());
        $this->assertTrue($courses->every(fn ($c) => $c->start_date->toDateString() === '2026-03-10' && $c->status === MedicationCourseStatus::Active));
        $this->assertSame('2026-03-12', PrescriptionService::expectedEndDate(Carbon::parse('2026-03-10'), 3)->toDateString());

        Carbon::setTestNow('2026-03-11 09:00:00');
        $this->assertSame(1, $courses[1]->daysRemaining());
        Carbon::setTestNow();
    }

    public function test_course_status_changes_require_reason_to_discontinue(): void
    {
        $course = $this->prescribe([$this->item()])->courses()->sole();

        $this->actingAs($this->doctor)->post(route('medications.status', $course), ['status' => 'discontinued'])->assertSessionHas('error');
        $this->actingAs($this->doctor)->post(route('medications.status', $course), ['status' => 'discontinued', 'reason' => 'Side effects'])->assertSessionHas('success');

        $this->assertSame(MedicationCourseStatus::Discontinued, $course->fresh()->status);
        $this->assertSame('Side effects', $course->fresh()->status_reason);
        $this->actingAs($this->receptionist())->post(route('medications.status', $course), ['status' => 'active'])->assertForbidden();
    }

    public function test_finalised_prescription_is_never_overwritten_and_amendments_are_versioned(): void
    {
        $rx = $this->prescribe([$this->item()]);

        // Direct update of a finalised prescription is forbidden
        $this->actingAs($this->doctor)->put(route('prescriptions.update', $rx), ['items' => [$this->item(['dosage' => '2 tablets'])]])->assertForbidden();
        $this->assertSame('1 tablet', $rx->items()->first()->dosage);

        // Amendment creates a draft v2; original stays finalised until v2 is finalised
        $this->actingAs($this->doctor)->post(route('prescriptions.amend', $rx), ['reason' => 'Dose correction'])->assertRedirect();
        $v2 = Prescription::query()->where('previous_version_id', $rx->id)->sole();
        $this->assertSame(2, $v2->version);
        $this->assertSame(PrescriptionStatus::Finalized, $rx->fresh()->status);

        $this->actingAs($this->doctor)->put(route('prescriptions.update', $v2), ['items' => [$this->item(['dosage' => '2 tablets'])], 'finalize' => 1])->assertRedirect(route('prescriptions.show', $v2));

        $this->assertSame(PrescriptionStatus::Superseded, $rx->fresh()->status);
        $this->assertSame('1 tablet', $rx->items()->first()->dosage, 'original items are preserved');
        $this->assertSame('2 tablets', $v2->items()->first()->dosage);
        $this->assertSame(MedicationCourseStatus::Discontinued, $rx->courses()->first()->status);
        $this->assertSame(MedicationCourseStatus::Active, $v2->courses()->first()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'amendment_started', 'entity_type' => 'prescription']);

        // A superseded version cannot be amended again; finalised ones cannot be deleted
        $this->actingAs($this->doctor)->post(route('prescriptions.amend', $rx), ['reason' => 'Again please'])->assertForbidden();
        $this->actingAs($this->doctor)->delete(route('prescriptions.destroy', $v2))->assertForbidden();
        $this->assertDatabaseCount('prescriptions', 2);
    }

    public function test_repeat_prescription_is_authorised_and_linked(): void
    {
        $rx = $this->prescribe([$this->item(), $this->item(['medicine_name' => 'Not repeated'])]);
        $itemA = $rx->items->firstWhere('medicine_name', 'Test Medicine A');

        $this->actingAs($this->doctor)->get(route('prescriptions.repeat', $this->patient))->assertOk()->assertSee($rx->prescription_number);

        $this->actingAs($this->doctor)->post(route('prescriptions.repeat.store', $this->patient), [
            'source_prescription_id' => $rx->id, 'reason' => 'Stable, continue', 'selected' => [$itemA->id],
            'items' => [$itemA->id => ['duration_days' => 14, 'dosage' => '1/2 tablet']],
        ])->assertRedirect();

        $repeat = Prescription::query()->where('repeat_of_id', $rx->id)->sole();
        $this->assertSame(PrescriptionStatus::Finalized, $repeat->status);
        $this->assertSame($this->doctor->id, $repeat->authorized_by);
        $this->assertNotNull($repeat->authorized_at);
        $this->assertSame('Stable, continue', $repeat->repeat_reason);
        $this->assertSame(['Test Medicine A'], $repeat->items()->pluck('medicine_name')->all());
        $this->assertSame(14, $repeat->items()->first()->duration_days);
        $this->assertSame('1/2 tablet', $repeat->items()->first()->dosage);
        $this->assertSame('Twice daily', $repeat->items()->first()->frequency, 'unchanged fields are reused from the source');
        $this->assertSame(1, MedicationRepetition::query()->where('source_prescription_id', $rx->id)->count());
        $this->assertSame('medication_review', $repeat->visit->visit_type);
        $this->assertSame(1, $rx->fresh()->repeats()->count());
    }

    public function test_repeat_requires_doctor_reason_and_selection(): void
    {
        $rx = $this->prescribe([$this->item()]);
        $payload = ['source_prescription_id' => $rx->id, 'reason' => 'Continue', 'selected' => [$rx->items->first()->id]];

        $this->actingAs($this->receptionist())->post(route('prescriptions.repeat.store', $this->patient), $payload)->assertForbidden();
        $this->actingAs($this->admin())->post(route('prescriptions.repeat.store', $this->patient), $payload)->assertForbidden();
        $this->actingAs($this->doctor)->post(route('prescriptions.repeat.store', $this->patient), ['reason' => '', 'selected' => []] + $payload)->assertSessionHasErrors(['reason', 'selected']);

        // A prescription from another patient cannot be used as the source
        $other = $this->patient();
        $this->actingAs($this->doctor)->post(route('prescriptions.repeat.store', $other), $payload)->assertSessionHasErrors('source_prescription_id');
        $this->assertSame(0, Prescription::query()->whereNotNull('repeat_of_id')->count());
    }

    public function test_prescription_pdf_renders_with_branding(): void
    {
        settings()->set(['clinic_name' => 'Gujranwala Family Clinic']);
        $rx = $this->prescribe([$this->item()]);

        $res = $this->actingAs($this->doctor)->get(route('prescriptions.pdf', [$rx, 'download' => 1]))->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertDatabaseHas('activity_logs', ['action' => 'printed', 'entity_type' => 'prescription']);
    }

    public function test_templates_can_be_saved_and_applied(): void
    {
        $rx = $this->prescribe([$this->item()]);
        $this->actingAs($this->doctor)->post(route('prescriptions.template', $rx), ['name' => 'My set'])->assertSessionHas('success');

        $this->actingAs($this->doctor)->get(route('prescription-templates.index'))->assertSee('My set');
    }

    public function test_other_medications_are_kept_separate_from_clinic_prescriptions(): void
    {
        $this->prescribe([$this->item()]);
        $this->actingAs($this->doctor)->post(route('patients.history.store', [$this->patient, 'medication']), ['medicine_name' => 'Outside Med', 'status' => 'current', 'prescribing_source' => 'Cardiologist'])->assertRedirect();

        $this->assertSame(1, $this->patient->otherMedications()->count());
        $this->assertSame(1, $this->patient->medicationCourses()->count());
        $this->assertFalse($this->patient->medicationCourses()->where('medicine_name', 'Outside Med')->exists());
        $this->actingAs($this->doctor)->get(route('patients.show', [$this->patient, 'tab' => 'medications']))->assertSee('Outside Med')->assertSee('Other source')->assertSee('Cardiologist');
    }
}
