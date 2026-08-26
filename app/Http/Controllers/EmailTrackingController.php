<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    /**
     * Píxel de 1x1 embebido en los correos (resources/views/emails/branded.
     * blade.php) — cuando el cliente de correo lo carga, marcamos el
     * EmailLog correspondiente como abierto. Público y sin autenticación:
     * lo pide el cliente de correo del destinatario, no un usuario logueado.
     */
    public function pixel(string $token): Response
    {
        EmailLog::where('tracking_token', $token)->first()?->markOpened();

        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');

        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
