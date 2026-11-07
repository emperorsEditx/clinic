<?php

namespace Tests\Feature;

use App\Enums\LabRequestStatus;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryTest as LabTest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaboratoryTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private User $lab;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Storage::fake('local');
        $this->doctor = $this->doctor();
        $this->lab = $this->labTech();
        $this->patient = $this->patient();
    }

    private function request(): LaboratoryRequest
    {
        $test = LabTest::query()->first();
        $this->actingAs($this->doctor)->post(route('laboratory.requests.store'), ['patient_id' => $this->patient->id, 'laboratory_test_id' => $test->id, 'priority' => 'urgent'])->assertRedirect();

        return LaboratoryRequest::query()->latest('id')->first();
    }

    private function upload(LaboratoryRequest $req, ?UploadedFile $file = null)
    {
        return $this->actingAs($this->lab)->post(route('laboratory.requests.upload', $req), [
            'report_date' => today()->toDateString(), 'laboratory_name' => 'City Lab', 'result_summary' => 'Within reference range',
            'file' => $file ?? UploadedFile::fake()->create('result.pdf', 120, 'application/pdf'),
        ]);
    }

    public function test_doctor_requests_test_and_lab_is_notified(): void
    {
        $req = $this->request();

        $this->assertSame(LabRequestStatus::Requested, $req->status);
        $this->assertSame(LabTest::query()->first()->name, $req->test_name);
        $this->assertSame(1, $this->lab->notifications()->count());
        $this->actingAs($this->receptionist())->post(route('laboratory.requests.store'), ['patient_id' => $this->patient->id, 'test_name' => 'X', 'priority' => 'routine'])->assertForbidden();
    }

    public function test_lab_technician_updates_status(): void
    {
        $req = $this->request();

        $this->actingAs($this->lab)->post(route('laboratory.requests.status', $req), ['status' => 'sample_collected'])->assertSessionHas('success');
        $this->assertSame(LabRequestStatus::SampleCollected, $req->fresh()->status);
        $this->assertNotNull($req->fresh()->sample_collected_at);

        $this->actingAs($this->lab)->post(route('laboratory.requests.status', $req), ['status' => 'reviewed'])->assertSessionHas('error');
        $this->actingAs($this->doctor)->post(route('laboratory.requests.status', $req), ['status' => 'processing'])->assertForbidden();
    }

    public function test_report_upload_goes_to_private_disk_and_completes_request(): void
    {
        $req = $this->request();
        $this->upload($req)->assertRedirect(route('laboratory.requests.show', $req));

        $report = LaboratoryReport::query()->sole();
        Storage::disk('local')->assertExists($report->file_path);
        $this->assertStringStartsWith('lab-reports/', $report->file_path);
        $this->assertStringNotContainsString('result.pdf', $report->file_path, 'stored under a random name');
        $this->assertSame('result.pdf', $report->original_filename);
        $this->assertSame($this->patient->id, $report->patient_id);
        $this->assertSame(LabRequestStatus::Completed, $req->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['entity_type' => 'lab_report', 'entity_id' => $report->id]);
        $this->assertSame(1, $this->doctor->notifications()->count(), 'requesting doctor is notified');
    }

    public function test_file_validation_rejects_bad_types_spoofed_content_and_oversize(): void
    {
        $req = $this->request();

        $this->upload($req, UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'))->assertSessionHasErrors('file');
        // A real file whose content is not a PDF, renamed to .pdf: rejected by content sniffing.
        $tmp = tempnam(sys_get_temp_dir(), 'spoof');
        file_put_contents($tmp, '<?php echo "not a pdf"; ?>');
        $this->upload($req, new UploadedFile($tmp, 'fake.pdf', 'application/pdf', null, true))->assertSessionHasErrors('file');
        $this->upload($req, UploadedFile::fake()->create('huge.pdf', 20000, 'application/pdf'))->assertSessionHasErrors('file');
        $this->upload($req, UploadedFile::fake()->image('scan.png'))->assertSessionHasNoErrors();

        $this->assertSame(1, LaboratoryReport::query()->count());
    }

    public function test_standalone_upload_for_external_report(): void
    {
        $this->actingAs($this->lab)->getJson(route('patients.search', ['q' => substr($this->patient->full_name, 0, 4)]))->assertOk()->assertJsonPath('0.url', null);

        $this->actingAs($this->lab)->post(route('laboratory.reports.store'), [
            'patient_id' => $this->patient->id, 'test_name' => 'Thyroid panel', 'report_date' => today()->toDateString(),
            'file' => UploadedFile::fake()->image('tsh.jpg'),
        ])->assertRedirect();

        $this->assertSame('Thyroid panel', LaboratoryReport::query()->sole()->test_name);
    }

    public function test_authorised_download_and_preview(): void
    {
        $this->upload($this->request());
        $report = LaboratoryReport::query()->sole();

        $this->actingAs($this->doctor)->get(route('laboratory.reports.download', $report))->assertOk()->assertDownload()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->doctor)->get(route('laboratory.reports.download', [$report, 'inline' => 1]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertDatabaseHas('activity_logs', ['action' => 'downloaded', 'entity_id' => $report->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'previewed', 'entity_id' => $report->id]);

        $this->actingAs($this->receptionist())->get(route('laboratory.reports.download', $report))->assertForbidden();
        auth()->logout();
        $this->get(route('laboratory.reports.download', $report))->assertRedirect(route('login'));
    }

    public function test_doctor_reviews_report(): void
    {
        $req = $this->request();
        $this->upload($req);
        $report = LaboratoryReport::query()->sole();

        $this->actingAs($this->lab)->post(route('laboratory.reports.review', $report), ['review_notes' => 'x'])->assertForbidden();
        $this->actingAs($this->doctor)->post(route('laboratory.reports.review', $report), ['review_notes' => 'Normal. No action.'])->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('reviewed', $report->status->value);
        $this->assertSame($this->doctor->id, $report->reviewed_by);
        $this->assertSame('reviewed', $req->fresh()->status->value);
        $this->actingAs($this->doctor)->post(route('laboratory.reports.review', $report))->assertForbidden();
    }

    public function test_search_and_filter_reports(): void
    {
        $this->upload($this->request());
        $name = LaboratoryReport::query()->sole()->test_name;

        $this->actingAs($this->doctor)->get(route('laboratory.reports.index', ['q' => $this->patient->patient_code]))->assertSee($name);
        $this->actingAs($this->doctor)->get(route('laboratory.reports.index', ['status' => 'reviewed']))->assertSee('No reports found');
        $this->actingAs($this->doctor)->get(route('patients.show', [$this->patient, 'tab' => 'laboratory']))->assertSee($name);
    }
}
