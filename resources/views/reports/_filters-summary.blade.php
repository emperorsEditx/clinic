@php
    $parts = ['Period: '.fmt_date($filters['from']).' – '.fmt_date($filters['to'])];
    if (! empty($filters['doctor'])) { $parts[] = 'Doctor: '.(App\Models\User::withTrashed()->find($filters['doctor'])?->name ?? '#'.$filters['doctor']); }
    if (! empty($filters['patient'])) { $p = App\Models\Patient::withTrashed()->find($filters['patient']); $parts[] = 'Patient: '.($p ? $p->full_name.' ('.$p->patient_code.')' : '#'.$filters['patient']); }
    if (! empty($filters['status'])) { $parts[] = 'Status: '.($report->statusOptions()[$filters['status']] ?? $filters['status']); }
    if (! empty($filters['gender'])) { $parts[] = 'Gender: '.ucfirst($filters['gender']); }
@endphp
<p style="margin: 0 0 8px; font-size: 10px; color: #475569;">{{ implode(' · ', $parts) }}</p>
