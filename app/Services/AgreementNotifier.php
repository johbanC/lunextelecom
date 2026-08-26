<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\EmailLog;
use App\Models\NotificationRule;
use App\Notifications\AgreementSignedNotification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Notifica a los grupos configurados (NotificationRule, evento
 * agreement_signed) cuando un cliente firma un formulario. Los formularios
 * no tienen categoría, así que aquí se ignoran las reglas por categoría y se
 * usan todas las reglas activas de este evento.
 */
class AgreementNotifier
{
    public static function notifySigned(Agreement $agreement): void
    {
        $rules = NotificationRule::query()
            ->where('event', 'agreement_signed')
            ->where('is_active', true)
            ->with('group.members')
            ->get();

        $notified = [];

        foreach ($rules as $rule) {
            if (! $rule->group) {
                continue;
            }

            foreach ($rule->group->members as $member) {
                if (isset($notified[$member->id])) {
                    continue;
                }

                $notified[$member->id] = true;

                $trackingToken = (string) Str::uuid();
                $notification = new AgreementSignedNotification($agreement, $trackingToken);

                $emailLog = EmailLog::create([
                    'tracking_token' => $trackingToken,
                    'to_email' => $member->email,
                    'to_name' => $member->name,
                    'user_id' => $member->id,
                    'agreement_id' => $agreement->id,
                    'event' => 'agreement_signed',
                    'purpose' => 'Form signed',
                    'subject' => $notification->subject(),
                    'body_html' => (string) $notification->toMail($member)->render(),
                    'status' => 'pending',
                ]);

                try {
                    $member->notify($notification);

                    $emailLog->update(['status' => 'sent', 'sent_at' => now()]);
                } catch (Throwable $e) {
                    $emailLog->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

                    report($e);
                }
            }
        }
    }
}
