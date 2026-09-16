<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', FormSubmission::class);

        $status = $request->query('status', 'all');

        $submissions = FormSubmission::query()
            ->with(['template', 'creator', 'manager'])
            ->when($status === 'pending', fn ($q) => $q->pending())
            ->when($status === 'submitted', fn ($q) => $q->submitted())
            ->when($status === 'to_manage', fn ($q) => $q->toManage())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => FormSubmission::count(),
            'pending' => FormSubmission::pending()->count(),
            'submitted' => FormSubmission::submitted()->count(),
            'to_manage' => FormSubmission::toManage()->count(),
        ];

        return view('admin.forms.index', compact('submissions', 'status', 'counts'));
    }

    public function create()
    {
        $this->authorize('create', FormSubmission::class);

        $templates = FormTemplate::where('is_active', true)
            ->where('mode', FormTemplate::MODE_ON_DEMAND)
            ->with('fields.options')
            ->orderBy('name')
            ->get();

        return view('admin.forms.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', FormSubmission::class);

        $template = FormTemplate::where('mode', FormTemplate::MODE_ON_DEMAND)->findOrFail($request->input('form_template_id'));

        $knownFields = $template->fields()->where('editable_by_recipient', false)->get();

        $rules = ['form_template_id' => 'required|exists:form_templates,id', 'expires_in' => 'required|in:1,3,7,15,30,none'];
        foreach ($knownFields as $field) {
            $rules["known_values.{$field->id}"] = $field->is_required ? 'required|string|max:2000' : 'nullable|string|max:2000';
        }

        $validated = $request->validate($rules);

        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'expires_at' => $validated['expires_in'] === 'none' ? null : now()->addDays((int) $validated['expires_in']),
            'created_by' => $request->user()->id,
        ]);

        foreach ($knownFields as $field) {
            $value = $validated['known_values'][$field->id] ?? null;
            if ($value !== null && $value !== '') {
                $submission->values()->create(['form_field_id' => $field->id, 'value' => $value]);
            }
        }

        return redirect()->route('admin.forms.show', $submission)->with('status', __('Link generated successfully.'));
    }

    public function show(FormSubmission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load(['template.fields.options', 'values', 'creator', 'manager']);

        return view('admin.forms.show', compact('submission'));
    }

    public function manage(Request $request, FormSubmission $submission)
    {
        $this->authorize('manage', $submission);

        abort_unless($submission->isSubmitted(), 403, __('Only completed submissions can be marked as managed.'));

        $validated = $request->validate(['reference_note' => 'nullable|string|max:255']);

        $submission->update([
            'reference_note' => $validated['reference_note'] ?? null,
            'managed_by' => $request->user()->id,
            'managed_at' => now(),
        ]);

        return redirect()->back()->with('status', __('Marked as managed.'));
    }
}
