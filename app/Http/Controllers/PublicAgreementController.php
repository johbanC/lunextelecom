<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicAgreementController extends Controller
{
    /**
     * Muestra el formulario para que el cliente lo llene y firme.
     */
    public function show(string $uuid)
    {
        $agreement = Agreement::where('uuid', $uuid)->firstOrFail();
        $catalog = Agreement::itemCatalog($agreement->type);

        return view('public.agreements.show', [
            'agreement' => $agreement,
            'catalog' => $catalog,
            'expired' => $agreement->isExpired(),
        ]);
    }

    /**
     * Guarda los datos llenados por el cliente y su firma.
     */
    public function store(Request $request, string $uuid)
    {
        $agreement = Agreement::where('uuid', $uuid)->firstOrFail();

        abort_if($agreement->isSigned(), 403, 'Este formulario ya fue firmado.');
        abort_if($agreement->isExpired(), 403, 'El enlace para firmar este formulario ha vencido.');

        $catalog = collect(Agreement::itemCatalog($agreement->type));

        $validated = $request->validate([
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'business_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'last_4_bank' => 'required|digits:4',
            'training_date' => 'nullable|date',
            'authorization_digits' => 'required|digits:4',
            'items' => 'nullable|array',
            'items.*' => 'nullable|integer|min:0',
            'signature' => 'required|string|starts_with:data:image/png;base64,',
        ]);

        $items = [];
        $total = 0;

        foreach ($catalog as $catalogItem) {
            $qty = (int) ($validated['items'][$catalogItem['key']] ?? 0);
            $lineTotal = $qty * $catalogItem['rate'];
            $total += $lineTotal;

            $items[] = [
                'key' => $catalogItem['key'],
                'name' => $catalogItem['name'],
                'rate' => $catalogItem['rate'],
                'qty' => $qty,
                'total' => $lineTotal,
            ];
        }

        $signatureData = substr($validated['signature'], strpos($validated['signature'], ',') + 1);
        $signaturePath = "signatures/{$agreement->uuid}.png";
        Storage::disk('public')->put($signaturePath, base64_decode($signatureData));

        $agreement->update([
            'owner_name' => $validated['owner_name'],
            'phone' => $validated['phone'],
            'business_name' => $validated['business_name'],
            'address' => $validated['address'],
            'last_4_bank' => $validated['last_4_bank'],
            'training_date' => $validated['training_date'] ?? null,
            'authorization_digits' => $validated['authorization_digits'],
            'items' => $items,
            'total_amount' => $total,
            'signature_path' => $signaturePath,
            'signed_ip' => $request->ip(),
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        return redirect()->route('public.agreements.thanks', $agreement->uuid);
    }

    /**
     * Página de confirmación tras firmar.
     */
    public function thanks(string $uuid)
    {
        $agreement = Agreement::where('uuid', $uuid)->firstOrFail();

        return view('public.agreements.thanks', compact('agreement'));
    }
}
