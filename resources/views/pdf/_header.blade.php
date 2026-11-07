<table class="brand"><tr>
    <td style="width: 62%">
        <table><tr>
            @if (! empty($branding['logo_data_uri']))<td style="padding-right: 10px"><img src="{{ $branding['logo_data_uri'] }}" style="height: 48px"></td>@endif
            <td>
                <p class="clinic-name">{{ $branding['clinic_name'] }}</p>
                @if ($branding['clinic_address'])<p class="small muted pre">{{ $branding['clinic_address'] }}</p>@endif
                <p class="small muted">{{ collect([$branding['clinic_phone'], $branding['clinic_email'], $branding['clinic_website']])->filter()->implode('  ·  ') }}</p>
                @if ($branding['clinic_registration_number'])<p class="small muted">Reg. No. {{ $branding['clinic_registration_number'] }}</p>@endif
            </td>
        </tr></table>
    </td>
    <td class="right" style="width: 38%">
        <p style="font-size: 13px; font-weight: bold">{{ $docTitle }}</p>
        @isset($docSubtitle)<p class="small muted">{{ $docSubtitle }}</p>@endisset
    </td>
</tr></table>
