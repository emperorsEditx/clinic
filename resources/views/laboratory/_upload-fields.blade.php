{{-- Shared report upload fields. $fromRequest (bool) hides test name. --}}
@php $cfg = config('clinic.lab_uploads'); @endphp
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    @unless ($fromRequest ?? false)
        <x-field name="test_name" label="Test name" required />
        <x-field name="test_category" label="Category" />
    @endunless
    <x-field name="laboratory_name" label="Laboratory" :value="$defaultLab ?? null" />
    <x-field name="test_date" label="Test / sample date" type="date" :max="today()->toDateString()" />
    <x-field name="report_date" label="Report date" type="date" required :value="today()" :max="today()->toDateString()" />
    <div class="md:col-span-2">
        <label class="label" for="f_file">Report file <span class="text-red-500">*</span></label>
        <input id="f_file" type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="block w-full text-[13px] text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-[13px] file:font-medium file:text-brand-700 hover:file:bg-brand-100 @error('file') text-red-600 @enderror">
        <p class="help">PDF, JPG or PNG up to {{ round($cfg['max_kb'] / 1024) }} MB. Stored privately — only signed-in staff with laboratory access can open it.</p>
        @error('file')<p class="error">{{ $message }}</p>@enderror
    </div>
    <x-field class="md:col-span-2" name="result_summary" label="Result summary" type="textarea" rows="3" help="Optional key findings for quick reference." />
</div>
