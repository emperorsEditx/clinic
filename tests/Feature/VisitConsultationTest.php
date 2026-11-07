<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Enums\VisitStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\FollowUp;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitConsultationTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->doctor = $this->doctor();
    }

    private function book(array $o = []): Appointment
    {
        $p = $this->patient();
        $this->actingAs($this->receptionist())->post(route('appointments.store'), array_merge([
            'patient_id' => $p->id, 'doctor_id' => $this->doctor->id, 'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00', 'appointment_type' => 'Follow-up', 'reason' => 'Review',
        ], $o))->assertRedirect();

        return Appointment::query()->latest('id')->first();
    }

    private function startConsultation(?Visit $visit = null): Consultation
    {
        $visit ??= Visit::query()->latest('id')->first();
        $this->actingAs($this->doctor)->post(route('visits.consult', $visit))->assertRedirect();

        return Consultation::query()->latest('id')->first();
    }

    public function test_booking_and_double_booking_protection(): void
    {
        $a = $this->book();
        $this->assertMatchesRegularExpression('/^APT-\d{4}-\d+$/', $a->appointment_number);

        $p2 = $this->patient();
        $this->actingAs($this->receptionist())->from(route('appointments.create'))->post(route('appointments.store'), [
            'patient_id' => $p2->id, 'doctor_id' => $this->doctor->id, 'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00', 'appointment_type' => 'Follow-up',
        ])->assertSessionHas('error');
        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_check_in_creates_a_linked_visit(): void
    {
        $a = $this->book();

        $this->actingAs($this->receptionist())->post(route('appointments.check-in', $a))->assertRedirect();

        $visit = Visit::query()->sole();
        $this->assertSame($a->id, $visit->appointment_id);
        $this->assertSame('checked_in', $a->fresh()->status->value);
        $this->assertSame(VisitStatus::Waiting, $visit->status);
    }

    public function test_walk_in_visit_creation_and_every_visit_is_separate(): void
    {
        $p = $this->patient();
        $user = $this->receptionist();
        foreach (['Fever', 'Cough'] as $complaint) {
            $this->actingAs($user)->post(route('visits.store'), ['patient_id' => $p->id, 'doctor_id' => $this->doctor->id, 'visit_type' => 'walk_in', 'chief_complaint' => $complaint])->assertRedirect();
        }

        $this->assertSame(['Fever', 'Cough'], $p->visits()->orderBy('id')->pluck('chief_complaint')->all());
        $this->assertNotSame(...$p->visits()->pluck('visit_number')->all());
    }

    public function test_full_consultation_workflow(): void
    {
        $this->book();
        $this->actingAs($this->receptionist())->post(route('appointments.check-in', Appointment::query()->first()));
        $c = $this->startConsultation();
        $this->assertSame(ConsultationStatus::Draft, $c->status);
        $this->assertSame(VisitStatus::InConsultation, $c->visit->status);

        // Save a draft with vitals, a prescription and a lab request
        $this->actingAs($this->doctor)->put(route('consultations.update', $c), [
            'action' => 'save', 'symptoms' => 'Headache', 'vitals' => ['systolic_bp' => 130, 'diastolic_bp' => 85, 'temperature' => 37.2, 'weight' => 70, 'height' => 175],
            'items' => [$this->item(), ['medicine_name' => '', 'dosage' => '']], // blank row is ignored
            'lab_tests' => [['test_name' => 'Lipid profile', 'priority' => 'routine']],
        ])->assertRedirect(route('consultations.edit', $c));

        $c->refresh();
        $this->assertSame(130, $c->vitals->systolic_bp);
        $this->assertSame(22.9, $c->vitals->bmi());
        $this->assertSame(1, $c->prescriptions()->sole()->items()->count());
        $this->assertSame(1, $c->laboratoryRequests()->count());

        // Completing requires a diagnosis
        $this->actingAs($this->doctor)->put(route('consultations.update', $c), ['action' => 'complete', 'items' => [$this->item()]])->assertSessionHasErrors('diagnosis');

        $this->actingAs($this->doctor)->put(route('consultations.update', $c), [
            'action' => 'complete', 'symptoms' => 'Headache', 'diagnosis' => 'Tension headache',
            'follow_up_date' => today()->addWeek()->toDateString(), 'follow_up_instructions' => 'Review',
            'items' => [$this->item()],
        ])->assertRedirect(route('consultations.show', $c));

        $c->refresh();
        $this->assertSame(ConsultationStatus::Completed, $c->status);
        $this->assertSame('finalized', $c->prescriptions()->sole()->status->value);
        $this->assertSame(VisitStatus::Completed, $c->visit->status);
        $this->assertSame('completed', Appointment::query()->first()->status->value);
        $this->assertSame(1, FollowUp::query()->where('consultation_id', $c->id)->where('status', 'pending')->count());
        $this->assertSame(1, $c->patient->medicationCourses()->count());
    }

    public function test_completed_consultation_cannot_be_edited_and_amendments_preserve_history(): void
    {
        $visit = $this->actingAs($this->doctor)->post(route('visits.store'), ['patient_id' => $this->patient()->id, 'doctor_id' => $this->doctor->id, 'visit_type' => 'walk_in']);
        $c = $this->startConsultation();
        $this->actingAs($this->doctor)->put(route('consultations.update', $c), ['action' => 'complete', 'diagnosis' => 'Original diagnosis']);

        // Direct edits are blocked
        $this->actingAs($this->doctor)->put(route('consultations.update', $c), ['action' => 'save', 'diagnosis' => 'Overwritten'])->assertForbidden();
        $this->assertSame('Original diagnosis', $c->fresh()->diagnosis);

        // Amendment needs a reason, keeps old + new
        $this->actingAs($this->doctor)->post(route('consultations.amend.store', $c), ['diagnosis' => 'Corrected'])->assertSessionHasErrors('reason');
        $this->actingAs($this->doctor)->post(route('consultations.amend.store', $c), ['diagnosis' => 'Corrected diagnosis', 'reason' => 'Transcription error'])->assertRedirect();

        $c->refresh();
        $this->assertSame('Corrected diagnosis', $c->diagnosis);
        $this->assertSame(1, $c->amendment_count);
        $am = $c->amendments()->sole();
        $this->assertSame(['old' => 'Original diagnosis', 'new' => 'Corrected diagnosis'], $am->changes['diagnosis']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'amended', 'entity_type' => 'consultation', 'entity_id' => $c->id]);

        // Another doctor cannot amend
        $this->actingAs($this->doctor())->get(route('consultations.amend', $c))->assertForbidden();
    }

    public function test_only_the_consulting_doctor_edits_the_draft(): void
    {
        $this->actingAs($this->doctor)->post(route('visits.store'), ['patient_id' => $this->patient()->id, 'doctor_id' => $this->doctor->id, 'visit_type' => 'walk_in']);
        $c = $this->startConsultation();

        $this->actingAs($this->doctor())->put(route('consultations.update', $c), ['action' => 'save', 'symptoms' => 'x'])->assertForbidden();
        $this->actingAs($this->receptionist())->post(route('visits.consult', $c->visit))->assertForbidden();
    }

    public function test_patient_timeline_includes_the_encounter(): void
    {
        $p = $this->patient();
        $this->actingAs($this->doctor)->post(route('visits.store'), ['patient_id' => $p->id, 'doctor_id' => $this->doctor->id, 'visit_type' => 'walk_in', 'chief_complaint' => 'Rash']);
        $c = $this->startConsultation();
        $this->actingAs($this->doctor)->put(route('consultations.update', $c), ['action' => 'complete', 'diagnosis' => 'Contact dermatitis', 'items' => [$this->item()]]);

        $this->actingAs($this->doctor)->get(route('patients.show', [$p, 'tab' => 'timeline']))->assertOk()
            ->assertSee('Registered as')->assertSee('Rash')->assertSee('Contact dermatitis')->assertSee($c->prescriptions()->first()->prescription_number);
    }
}
