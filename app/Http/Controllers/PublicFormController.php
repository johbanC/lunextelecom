<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Services\FormNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicFormController extends Controller
{
    public function show(string $uuid)
    {
        $submission = FormSubmission::with('template.fields.options', 'values')->where('uuid', $uuid)->firstOrFail();

        return view('public.forms.on-demand', [
            'submission' => $submission,
            'template' => $submission->template,
            'expired' => $submission->isExpired(),
            'knownValues' => $submission->values->pluck('value', 'form_field_id'),
        ]);
    }

    public function store(Request $request, string $uuid)
    {
        $submission = FormSubmission::with('template.fields')->where('uuid', $uuid)->firstOrFail();

        abort_if($submission->isSubmitted(), 403, __('This form has already been submitted.'));
        abort_if($submission->isExpired(), 403, __('The link to fill this form has expired.'));

        $validated = $this->validateSubmission($request, $submission->template);

        $this->persistSubmission($validated, $request, $submission, $submission->template);

        FormNotifier::notifySubmitted($submission->fresh());

        return redirect()->route('public.forms.thanks', $submission->uuid);
    }

    public function thanks(string $uuid)
    {
        $submission = FormSubmission::with('template')->where('uuid', $uuid)->firstOrFail();

        return view('public.forms.thanks', compact('submission'));
    }

    public function showStandalone(string $slug)
    {
        $template = FormTemplate::where('slug', $slug)->where('is_active', true)->where('mode', FormTemplate::MODE_STANDALONE)->firstOrFail();
        $template->load('fields.options');

        return view('public.forms.standalone', compact('template'));
    }

    public function storeStandalone(Request $request, string $slug)
    {
        $template = FormTemplate::where('slug', $slug)->where('is_active', true)->where('mode', FormTemplate::MODE_STANDALONE)->firstOrFail();
        $template->load('fields');

        // Standalone forms are filled out by the recipient from scratch (there's no agent
        // pre-fill step for this mode), so every field is treated as editable regardless of
        // its stored editable_by_recipient flag. Validate BEFORE creating the submission row
        // so a failed submission doesn't leave behind an orphaned "pending" row.
        $validated = $this->validateSubmission($request, $template, treatAllEditable: true);

        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
        ]);

        $this->persistSubmission($validated, $request, $submission, $template, treatAllEditable: true);

        FormNotifier::notifySubmitted($submission->fresh());

        return redirect()->route('public.forms.thanks', $submission->uuid);
    }

    /**
     * Build and run the validation rules for a template's fields.
     *
     * A field that is not editable_by_recipient is agent-prefilled and rendered as a disabled
     * HTML control, which browsers never include in submitted form data — so it must not be
     * required (or validated at all) from the recipient's submission. Standalone forms have no
     * agent pre-fill step, so $treatAllEditable lets that flow validate every field.
     */
    protected function validateSubmission(Request $request, FormTemplate $template, bool $treatAllEditable = false): array
    {
        $rules = [];
        foreach ($template->fields as $field) {
            if (! $treatAllEditable && ! $field->editable_by_recipient) {
                continue;
            }

            $rules["values.{$field->key}"] = ($field->is_required ? 'required' : 'nullable').($field->isMultiValue() ? '|array' : '|string|max:2000');
        }
        if ($template->requires_signature) {
            // max:500000 caps the base64-encoded signature string at ~500,000 characters
            // (~500 KB), which comfortably covers a signature-pad PNG while preventing an
            // unbounded payload from being written to disk on this unauthenticated endpoint.
            $rules['signature'] = 'required|string|starts_with:data:image/png;base64,|max:500000';
        }

        return $request->validate($rules);
    }

    protected function persistSubmission(array $validated, Request $request, FormSubmission $submission, FormTemplate $template, bool $treatAllEditable = false): void
    {
        foreach ($template->fields as $field) {
            if (! $treatAllEditable && ! $field->editable_by_recipient) {
                continue;
            }

            $value = $validated['values'][$field->key] ?? null;
            $stored = is_array($value) ? implode(', ', $value) : $value;

            if ($stored !== null && $stored !== '') {
                $submission->values()->updateOrCreate(['form_field_id' => $field->id], ['value' => $stored]);
            }
        }

        $updates = ['status' => FormSubmission::STATUS_SUBMITTED, 'submitted_at' => now()];

        if ($template->requires_signature) {
            $signatureData = substr($validated['signature'], strpos($validated['signature'], ',') + 1);
            $signaturePath = "signatures/form-{$submission->uuid}.png";
            Storage::disk('public')->put($signaturePath, base64_decode($signatureData));

            $updates['signature_path'] = $signaturePath;
            $updates['signed_ip'] = $request->ip();
        }

        $submission->update($updates);
    }
}
