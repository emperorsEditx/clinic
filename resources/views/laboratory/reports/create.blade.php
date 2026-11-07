<x-layouts.app title="Upload laboratory report">
    <x-page-header title="Upload laboratory report" :subtitle="$req ? 'For request '.$req->request_number.' · '.$req->test_name : 'For reports received without a request in this system (e.g. from an outside laboratory).'" :back="$req ? route('laboratory.requests.show', $req) : route('laboratory.reports.index')" />
    <form method="POST" action="{{ $req ? route('laboratory.requests.upload', $req) : route('laboratory.reports.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        <div class="card"><div class="card-body space-y-5">
            @if ($req)
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-sm font-medium">{{ $req->patient->full_name }}</p><p class="text-xs text-slate-500">{{ $req->patient->patient_code }}</p></div>
            @else
                <x-patient-picker :patient="$patient" />
            @endif
            @include('laboratory._upload-fields', ['fromRequest' => (bool) $req, 'defaultLab' => $req?->laboratory_name])
        </div></div>
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('laboratory.reports.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary"><x-icon name="upload" /> Upload report</button></div>
    </form>
</x-layouts.app>
