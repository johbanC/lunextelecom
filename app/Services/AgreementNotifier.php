<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\NotificationRule;
use App\Notifications\AgreementSignedNotification;

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
                $member->notify(new AgreementSignedNotification($agreement));
            }
        }
    }
}
