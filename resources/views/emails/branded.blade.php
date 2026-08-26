<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                <tr>
                    <td align="center" style="padding-bottom:24px;">
                        <img src="{{ asset('img/logo.png') }}" alt="Lunex Telecom" height="40" style="display:block;">
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff;border-radius:16px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.08);font-family:Helvetica,Arial,sans-serif;">
                        <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#1f2937;">{{ $intro }}</p>

                        @if (! empty($details))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 20px;">
                                <tr>
                                    <td colspan="2" style="padding-bottom:8px;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#9ca3af;">
                                        Details
                                    </td>
                                </tr>
                                @foreach ($details as $label => $value)
                                    <tr>
                                        <td style="padding:6px 12px 6px 0;font-size:13px;color:#6b7280;white-space:nowrap;vertical-align:top;width:150px;border-top:1px solid #f3f4f6;">{{ $label }}</td>
                                        <td style="padding:6px 0;font-size:13px;color:#111827;font-weight:600;border-top:1px solid #f3f4f6;">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        @if (! empty($note))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="padding:12px 14px;background:#f9fafb;border-radius:8px;font-size:13px;line-height:1.5;color:#374151;">
                                        {{ $note }}
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if (! empty($ctaUrl))
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#1d4ed8;border-radius:8px;">
                                        <a href="{{ $ctaUrl }}" style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">{{ $ctaLabel }}</a>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;padding-top:20px;border-top:1px solid #e5e7eb;">
                            <tr>
                                <td style="font-size:13px;line-height:1.6;color:#374151;">
                                    Regards,<br>
                                    <strong>{{ $signatureName ?? 'System' }}</strong>
                                    @if (! empty($signatureRole))
                                        <br><span style="color:#6b7280;">{{ $signatureRole }}</span>
                                    @endif
                                    <br>Lunex Telecom
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top:20px;font-size:11px;color:#9ca3af;font-family:Helvetica,Arial,sans-serif;">
                        &copy; {{ date('Y') }} Lunex Telecom. All rights reserved.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@if (! empty($trackingUrl))
    <img src="{{ $trackingUrl }}" width="1" height="1" alt="" style="display:block;border:0;outline:none;">
@endif
