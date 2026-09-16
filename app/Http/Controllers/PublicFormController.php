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

        $this->persistSubmission($request, $submission, $submission->template);

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

        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
        ]);

        $this->persistSubmission($request, $submission, $template);

        FormNotifier::notifySubmitted($submission->fresh());

        return redirect()->route('public.forms.thanks', $submission->uuid);
    }

    protected function persistSubmission(Request $request, FormSubmission $submission, FormTemplate $template): void
    {
        $rules = [];
        foreach ($template->fields as $field) {
            $rules["values.{$field->key}"] = ($field->is_required ? 'required' : 'nullable').($field->isMultiValue() ? '|array' : '|string|max:2000');
        }
        if ($template->requires_signature) {
            $rules['signature'] = 'required|string|starts_with:data:image/png;base64,';
        }

        $validated = $request->validate($rules);

        foreach ($template->fields as $field) {
            if (! $field->editable_by_recipient) {
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
