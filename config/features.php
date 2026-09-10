<?php

/*
|--------------------------------------------------------------------------
| Interruptores de módulo (feature flags)
|--------------------------------------------------------------------------
|
| Encienden o apagan módulos completos de la plataforma. Cuando un módulo
| está apagado, su enlace en el menú desaparece y sus rutas responden 404
| (no es solo cosmético). Se controla desde el .env; el valor por defecto
| es "apagado" para que un despliegue nuevo solo exponga lo que se active
| explícitamente. Reactivar un módulo es cambiar una línea y correr
| `php artisan config:clear`.
|
*/

return [
    'tickets' => (bool) env('FEATURE_TICKETS', false),
    'groups' => (bool) env('FEATURE_GROUPS', false),
    'reports' => (bool) env('FEATURE_REPORTS', false),
    'emails' => (bool) env('FEATURE_EMAIL_LOG', false),
    'help' => (bool) env('FEATURE_HELP', false),
];
