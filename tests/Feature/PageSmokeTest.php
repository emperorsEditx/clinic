<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryRequest;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientMedication;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use App\Reports\ReportRegistry;
use App\Services\ConsultationService;
use App\Services\LaboratoryService;
use App\Services\MedicalHistoryService;
use App\Services\PrescriptionService;
use App\Services\VisitService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Renders every screen for every role against realistic demo data.
 * Catches broken views, missing eager loads and permission leaks (no page may 500).
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DemoSeeder::class);

        $doctor = User::query()->where('email', 'doctor@clinic.test')->first();
        $lab = User::query()->where('email', 'lab@clinic.test')->first();
        Auth::login($doctor);

        // Fill out the data set: history entries, a lab report, a draft consultation, a repeat and an amendment.
        $patient = Consultation::query()->where('status', 'completed')->first()->patient;
        $history = app(MedicalHistoryService::class);
        $history->add($patient, 'condition', ['category' => 'condition', 'name' => 'Hypertension', 'status' => 'current'], $doctor);
        $history->add($patient, 'allergy', ['allergen' => 'Penicillin', 'allergy_type' => 'drug', 'severity' => 'severe', 'status' => 'active'], $doctor);
        $history->add($patient, 'medication', ['medicine_name' => 'Aspirin', 'dosage' => '75 mg', 'status' => 'current', 'prescribing_source' => 'Cardiologist'], $doctor);
        $history->add($patient, 'note', ['title' => 'Social history', 'notes' => 'Non-smoker'], $doctor);

        $req = LaboratoryRequest::query()->first();
        app(LaboratoryService::class)->upload($req->patient, UploadedFile::fake()->create('cbc.pdf', 40, 'application/pdf'), ['report_date' => today()->toDateString()], $lab, $req);

        $rx = Prescription::query()->where('status', 'finalized')->where('patient_id', $patient->id)->first();
        $visit = app(VisitService::class)->create($patient, ['doctor_id' => $doctor->id, 'visit_type' => 'follow_up'], $doctor);
        $draft = app(ConsultationService::class)->start($visit, $doctor);
        app(ConsultationService::class)->save($draft, ['symptoms' => 'Review', 'items' => [['medicine_name' => 'Cetirizine', 'dosage' => '1 tab', 'frequency' => 'Once daily', 'duration_days' => 5]]], $doctor);
        app(PrescriptionService::class)->repeat($rx, $draft->fresh(), [$rx->items()->first()->id => ['duration_days' => 7]], 'Stable', $doctor, finalize: true);
        app(PrescriptionService::class)->amend($rx->fresh(), $doctor, 'Dose clarification');
        Auth::logout();
    }

    /** @return list<string> */
    private function urls(): array
    {
        $patient = Consultation::query()->where('status', 'completed')->first()->patient;
        $rx = Prescription::query()->where('patient_id', $patient->id)->oldest('id')->first();
        $tabs = ['overview', 'history', 'visits', 'consultations', 'prescriptions', 'medications', 'laboratory', 'timeline'];
        $condition = PatientCondition::query()->first();

        $urls = [
            route('dashboard'), route('dashboard', ['from' => today()->subMonth()->toDateString(), 'to' => today()->toDateString()]),
            route('profile.edit'), route('notifications.index'),
            route('patients.index'), route('patients.index', ['q' => 'a', 'gender' => 'male', 'archived' => 1]), route('patients.create'),
            route('patients.search', ['q' => substr($patient->full_name, 0, 3)]),
            route('patients.edit', $patient), route('patients.print', $patient), route('patients.history-pdf', $patient),
            route('patients.history.changes', [$patient, 'condition', $condition->id]),
            route('patients.history.changes', [$patient, 'allergy', PatientAllergy::query()->first()->id]),
            route('patients.history.changes', [$patient, 'medication', PatientMedication::query()->first()->id]),
            route('appointments.index'), route('appointments.index', ['status' => 'scheduled', 'from' => today()->subYear()->toDateString()]),
            route('appointments.create', ['patient' => $patient->id]),
            route('appointments.show', Appointment::query()->first()), route('appointments.edit', Appointment::query()->first()),
            route('visits.index'), route('visits.create', ['patient' => $patient->id]),
            route('consultations.index'), route('consultations.index', ['mine' => 1]),
            route('prescriptions.index'), route('prescriptions.index', ['type' => 'repeat']), route('prescription-templates.index'),
            route('prescriptions.show', $rx), route('prescriptions.pdf', $rx), route('prescriptions.repeat', $patient),
            route('medications.index'), route('medications.index', ['ending' => 7]),
            route('medicines.index'), route('medicines.search', ['q' => 'para']), route('follow-ups.index'),
            route('laboratory.requests.index'), route('laboratory.requests.index', ['status' => 'pending']), route('laboratory.requests.create'),
            route('laboratory.requests.show', LaboratoryRequest::query()->first()),
            route('laboratory.reports.index'), route('laboratory.reports.create'), route('laboratory.reports.create', ['request' => LaboratoryRequest::query()->first()->id]),
            route('laboratory.reports.show', LaboratoryReport::query()->first()), route('laboratory.reports.download', LaboratoryReport::query()->first()),
            route('laboratory.tests.index'),
            route('reports.index'), route('users.index'), route('users.create'), route('users.edit', User::query()->first()),
            route('settings.edit'), route('audit.index'),
        ];
        foreach ($tabs as $tab) {
            $urls[] = route('patients.show', [$patient, 'tab' => $tab]);
        }
        foreach (Visit::query()->get() as $v) {
            $urls[] = route('visits.show', $v);
        }
        foreach (Consultation::query()->get() as $c) {
            $urls[] = route('consultations.show', $c);
            $urls[] = route('consultations.edit', $c);
            $urls[] = route('consultations.amend', $c);
        }
        foreach (Prescription::query()->get() as $p) {
            $urls[] = route('prescriptions.show', $p);
            $urls[] = route('prescriptions.edit', $p);
        }
        foreach (app(ReportRegistry::class)->all() as $key => $report) {
            $urls[] = route('reports.show', [$key, 'from' => today()->subYear()->toDateString()]);
            $urls[] = route('reports.export', [$key, 'print', 'from' => today()->subYear()->toDateString()]);
            $urls[] = route('reports.export', [$key, 'pdf']);
            $urls[] = route('reports.export', [$key, 'xlsx']);
        }

        return $urls;
    }

    public function test_every_page_renders_for_every_role(): void
    {
        $failures = [];
        foreach (['admin@clinic.test', 'doctor@clinic.test', 'doctor2@clinic.test', 'reception@clinic.test', 'lab@clinic.test'] as $email) {
            $user = User::query()->where('email', $email)->first();
            foreach ($this->urls() as $url) {
                $response = $this->actingAs($user)->get($url);
                if ($response->getStatusCode() >= 500) {
                    $e = $response->exception;
                    $failures[] = "[$email] $url -> ".$response->getStatusCode().': '.($e ? get_class($e).': '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine() : '');
                }
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_role_access_matrix_on_key_pages(): void
    {
        $patient = Patient::query()->first();
        $matrix = [
            // url => [admin, doctor, receptionist, lab]
            route('patients.index') => [200, 200, 200, 403],
            route('patients.show', [$patient, 'tab' => 'history']) => [200, 200, 403, 403],
            route('prescriptions.index') => [200, 200, 403, 403],
            route('laboratory.requests.index') => [200, 200, 403, 200],
            route('reports.index') => [200, 403, 403, 403],
            route('settings.edit') => [200, 403, 403, 403],
            route('users.index') => [200, 403, 403, 403],
            route('audit.index') => [200, 403, 403, 403],
            route('appointments.create') => [200, 403, 200, 403],
        ];
        $users = collect(['admin', 'doctor', 'reception', 'lab'])->map(fn ($u) => User::query()->where('email', "$u@clinic.test")->first());

        foreach ($matrix as $url => $expected) {
            foreach ($users as $i => $user) {
                $this->actingAs($user)->get($url)->assertStatus($expected[$i]);
            }
        }
    }
}
