<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class EmailLogController extends Controller
{
    public function show(EmailLog $emailLog): View
    {
        $emailLog->load(['ticket', 'agreement', 'user']);

        return view('admin.email-log.show', ['emailLog' => $emailLog]);
    }

    /**
     * Reenvía el mismo correo (mismo asunto y contenido ya renderizado) a la
     * misma dirección o a una distinta — útil cuando el original se escribió
     * mal o simplemente se quiere confirmar que llegó. Cada reenvío queda
     * como una fila nueva en la auditoría, no sobrescribe la original.
     */
    public function resend(Request $request, EmailLog $emailLog): RedirectResponse
    {
        $validated = $request->validate([
            'to_email' => 'nullable|email|max:255',
        ]);

        $to = $validated['to_email'] ?: $emailLog->to_email;

        $resendLog = EmailLog::create([
            'tracking_token' => $trackingToken = (string) Str::uuid(),
            'to_email' => $to,
            'to_name' => $validated['to_email'] ? null : $emailLog->to_name,
            'user_id' => $validated['to_email'] ? null : $emailLog->user_id,
            'ticket_id' => $emailLog->ticket_id,
            'agreement_id' => $emailLog->agreement_id,
            'event' => $emailLog->event,
            'purpose' => $emailLog->purpose,
            'subject' => $emailLog->subject,
            'body_html' => $emailLog->body_html
                ? str_replace($emailLog->tracking_token, $trackingToken, $emailLog->body_html)
                : null,
            'status' => 'pending',
        ]);

        try {
            Mail::html($resendLog->body_html ?? '', function ($message) use ($to, $emailLog) {
                $message->to($to)->subject($emailLog->subject);
            });

            $resendLog->update(['status' => 'sent', 'sent_at' => now()]);

            return redirect()->route('admin.email-log.show', $resendLog)
                ->with('status', __('Email resent to :email.', ['email' => $to]));
        } catch (Throwable $e) {
            $resendLog->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            report($e);

            return redirect()->back()
                ->withErrors(['to_email' => __('Could not resend the email: :error', ['error' => $e->getMessage()])]);
        }
    }
}
