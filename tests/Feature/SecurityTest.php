<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\LaboratoryRequest;
use App\Models\Prescription;
use App\Models\User;
use App\Services\ConsultationService;
use App\Services\LaboratoryService;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Storage::fake('local');
        $this->doctor = $this->doctor();
        $patient = $this->patient();
        Auth::login($this->doctor);
        $visit = app(VisitService::class)->create($patient, ['doctor_id' => $this->doctor->id, 'visit_type' => 'walk_in'], $this->doctor);
        $c = app(ConsultationService::class)->start($visit, $this->doctor);
        $this->consultation = app(ConsultationService::class)->save($c, ['diagnosis' => 'Secret diagnosis', 'items' => [$this->item()], 'lab_tests' => [['test_name' => 'CBC', 'priority' => 'routine']]], $this->doctor, true);
        Auth::logout();
    }

    public function test_receptionist_cannot_read_or_change_clinical_records(): void
    {
        $r = $this->receptionist();
        $rx = Prescription::query()->sole();
        $c = $this->consultation;

        $this->actingAs($r)->get(route('consultations.show', $c))->assertForbidden();
        $this->actingAs($r)->get(route('prescriptions.show', $rx))->assertForbidden();
        $this->actingAs($r)->get(route('prescriptions.pdf', $rx))->assertForbidden();
        $this->actingAs($r)->post(route('prescriptions.amend', $rx), ['reason' => 'Trying to edit'])->assertForbidden();
        $this->actingAs($r)->put(route('prescriptions.update', $rx), ['items' => [$this->item()]])->assertForbidden();
        $this->actingAs($r)->post(route('consultations.amend.store', $c), ['diagnosis' => 'x', 'reason' => 'xxxxx'])->assertForbidden();
        $this->actingAs($r)->get(route('patients.show', [$c->patient, 'tab' => 'consultations']))->assertForbidden();
        $this->actingAs($r)->get(route('patients.show', $c->patient))->assertOk()->assertDontSee('Secret diagnosis');
        $this->actingAs($r)->get(route('visits.show', $c->visit))->assertOk()->assertDontSee('Secret diagnosis');

        $this->assertSame('Secret diagnosis', $c->fresh()->diagnosis);
        $this->assertSame(1, Prescription::query()->count());
    }

    public function test_lab_technician_cannot_touch_prescriptions_or_consultations(): void
    {
        $lab = $this->labTech();
        $rx = Prescription::query()->sole();

        $this->actingAs($lab)->get(route('prescriptions.index'))->assertForbidden();
        $this->actingAs($lab)->post(route('prescriptions.amend', $rx), ['reason' => 'Change it'])->assertForbidden();
        $this->actingAs($lab)->get(route('consultations.show', $this->consultation))->assertForbidden();
        $this->actingAs($lab)->get(route('consultations.edit', $this->consultation))->assertForbidden();
        $this->actingAs($lab)->get(route('patients.index'))->assertForbidden();
        $this->actingAs($lab)->get(route('laboratory.requests.show', LaboratoryRequest::query()->sole()))->assertOk()->assertDontSee('Secret diagnosis');
    }

    public function test_doctor_cannot_access_administration(): void
    {
        foreach (['settings.edit', 'users.index', 'users.create', 'audit.index', 'reports.index'] as $route) {
            $this->actingAs($this->doctor)->get(route($route))->assertForbidden();
        }
        $this->actingAs($this->doctor)->put(route('settings.update'), ['clinic_name' => 'Hacked'])->assertForbidden();
        $this->assertNotSame('Hacked', settings('clinic_name'));
    }

    public function test_admin_has_read_only_clinical_access(): void
    {
        $admin = $this->admin();
        $rx = Prescription::query()->sole();

        $this->actingAs($admin)->get(route('consultations.show', $this->consultation))->assertOk();
        $this->actingAs($admin)->post(route('prescriptions.amend', $rx), ['reason' => 'Admin edit'])->assertForbidden();
        $this->actingAs($admin)->post(route('patients.history.store', [$this->consultation->patient, 'note']), ['notes' => 'x'])->assertForbidden();
    }

    public function test_lab_files_are_never_publicly_reachable(): void
    {
        $req = LaboratoryRequest::query()->sole();
        Auth::login($this->labTech());
        $report = app(LaboratoryService::class)->upload($req->patient, UploadedFile::fake()->create('r.pdf', 10, 'application/pdf'), ['report_date' => today()->toDateString()], auth()->user(), $req);
        Auth::logout();

        $this->assertSame('local', $report->file_disk);
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.local.root'));
        $this->assertStringNotContainsString(public_path(), Storage::disk('local')->path($report->file_path));

        foreach (['/storage/'.$report->file_path, '/'.$report->file_path, '/storage/app/private/'.$report->file_path] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->get(route('laboratory.reports.download', $report))->assertRedirect(route('login'));
    }

    public function test_finalised_records_cannot_be_deleted(): void
    {
        $rx = Prescription::query()->sole();
        $this->actingAs($this->doctor)->delete(route('prescriptions.destroy', $rx))->assertForbidden();
        $this->actingAs($this->doctor)->delete(route('consultations.destroy', $this->consultation))->assertForbidden();
        $this->assertDatabaseCount('prescriptions', 1);
        $this->assertDatabaseCount('consultations', 1);
    }

    public function test_deactivating_a_user_keeps_their_clinical_history(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('users.toggle', $this->doctor))->assertSessionHas('success');

        $this->assertFalse($this->doctor->fresh()->is_active);
        $this->actingAs($admin)->get(route('consultations.show', $this->consultation))->assertOk()->assertSee($this->doctor->name);
        $this->actingAs($admin)->post(route('users.toggle', $admin))->assertForbidden();
    }

    public function test_security_headers(): void
    {
        $this->actingAs($this->doctor)->get('/')->assertHeader('X-Frame-Options')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_output_is_escaped(): void
    {
        $p = $this->patient(['full_name' => '<script>alert(1)</script>']);
        $this->actingAs($this->receptionist())->get(route('patients.show', $p))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_search_input_is_parameterised(): void
    {
        $this->patient(['full_name' => 'Normal Person']);
        $this->actingAs($this->receptionist())->getJson(route('patients.search', ['q' => "' OR 1=1 --"]))->assertOk()->assertJsonCount(0);
    }
}
