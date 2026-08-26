<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgreementController extends Controller
{
    /**
     * Listado de formularios generados, filtrable por estado.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $agreements = Agreement::query()
            ->with(['creator', 'manager'])
            ->when($status === 'pending', fn ($q) => $q->pending())
            ->when($status === 'expired', fn ($q) => $q->expired())
            ->when($status === 'signed', fn ($q) => $q->signed())
            ->when($status === 'to_manage', fn ($q) => $q->toManage())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => Agreement::count(),
            'pending' => Agreement::pending()->count(),
            'expired' => Agreement::expired()->count(),
            'signed' => Agreement::signed()->count(),
            'to_manage' => Agreement::toManage()->count(),
        ];

        return view('admin.agreements.index', compact('agreements', 'status', 'counts'));
    }

    /**
     * Formulario para generar un nuevo enlace.
     */
    public function create()
    {
        $expirationOptions = Agreement::expirationOptions();

        return view('admin.agreements.create', compact('expirationOptions'));
    }

    /**
     * Genera el enlace: crea el registro con account_id y fecha fijos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:coam_equipment',
            'account_id' => 'required|string|max:50',
            'form_date' => 'required|date',
            'expires_in' => 'required|in:'.implode(',', array_keys(Agreement::expirationOptions())),
        ]);

        $agreement = Agreement::create([
            'type' => $validated['type'],
            'account_id' => $validated['account_id'],
            'form_date' => $validated['form_date'],
            'expires_at' => Agreement::expiresAtFromOption($validated['expires_in']),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.agreements.show', $agreement)
            ->with('status', __('Link generated successfully.'));
    }

    /**
     * Detalle de un formulario: enlace para compartir y datos si ya fue firmado.
     */
    public function show(Agreement $agreement)
    {
        $agreement->load(['creator', 'manager']);
        $expirationOptions = Agreement::expirationOptions();

        return view('admin.agreements.show', compact('agreement', 'expirationOptions'));
    }

    /**
     * Enlaza un documento firmado a un número de ticket y lo marca como
     * gestionado (ya enviado al área encargada) — así el siguiente turno
     * sabe qué firmados todavía están pendientes por gestionar.
     */
    public function manage(Request $request, Agreement $agreement)
    {
        abort_unless($agreement->isSigned(), 403, __('Only signed forms can be linked to a ticket.'));

        $validated = $request->validate([
            'ticket_number' => 'required|string|max:50',
        ]);

        $agreement->update([
            'linked_ticket_number' => $validated['ticket_number'],
            'managed_by' => $request->user()->id,
            'managed_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('status', __('Marked as managed — linked to ticket :number.', ['number' => $validated['ticket_number']]));
    }

    /**
     * Extiende (o quita) la vigencia de un enlace pendiente que venció o está por vencer.
     */
    public function extend(Request $request, Agreement $agreement)
    {
        abort_if($agreement->isSigned(), 403, __('This form has already been signed.'));

        $validated = $request->validate([
            'expires_in' => 'required|in:'.implode(',', array_keys(Agreement::expirationOptions())),
        ]);

        $agreement->update([
            'expires_at' => Agreement::expiresAtFromOption($validated['expires_in']),
        ]);

        return redirect()
            ->route('admin.agreements.show', $agreement)
            ->with('status', __('Signing window updated.'));
    }

    /**
     * Descarga en PDF del formulario firmado.
     */
    public function pdf(Agreement $agreement)
    {
        abort_unless($agreement->isSigned(), 404);

        $agreement->load('creator');

        $signatureDataUri = null;
        if ($agreement->signature_path && Storage::disk('public')->exists($agreement->signature_path)) {
            $contents = Storage::disk('public')->get($agreement->signature_path);
            $signatureDataUri = 'data:image/png;base64,'.base64_encode($contents);
        }

        $pdf = Pdf::loadView('agreements.pdf', ['agreement' => $agreement, 'signatureDataUri' => $signatureDataUri])
            ->setPaper('letter');

        return $pdf->download("coam-{$agreement->account_id}-{$agreement->id}.pdf");
    }
}
