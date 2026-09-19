<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FormController extends Controller
{
    /**
     * Listado combinado de todos los formularios: COAM Equipment (Agreement,
     * único formulario con diseño propio y firma con canvas) y los envíos de
     * plantillas configurables. Son sistemas separados por debajo (el modelo
     * Agreement no se toca), pero de cara al usuario son "el mismo tipo de
     * cosa" — un formulario que alguien llena y firma/aprueba — así que
     * comparten una sola pantalla de listado.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', FormSubmission::class);

        $status = $request->query('status', 'all');

        $rows = new Collection;

        if ($request->user()->can('agreements.view')) {
            $rows = $rows->concat(
                Agreement::query()->with(['creator', 'manager'])->latest()->limit(500)->get()
                    ->map(fn (Agreement $agreement) => $this->rowFromAgreement($agreement))
            );
        }

        if ($request->user()->can('forms.view')) {
            $rows = $rows->concat(
                FormSubmission::query()->with(['template', 'creator', 'manager', 'values.field'])->latest()->limit(500)->get()
                    ->map(fn (FormSubmission $submission) => $this->rowFromSubmission($submission))
            );
        }

        $counts = [
            'all' => $rows->count(),
            'pending' => $rows->where('status', 'pending')->count(),
            'expired' => $rows->where('status', 'expired')->count(),
            'completed' => $rows->where('status', 'completed')->count(),
            'to_manage' => $rows->where('to_manage', true)->count(),
        ];

        $filtered = match ($status) {
            'pending', 'expired', 'completed' => $rows->where('status', $status),
            'to_manage' => $rows->where('to_manage', true),
            default => $rows,
        };

        $filtered = $filtered->sortByDesc('created_at')->values();

        $page = (int) $request->query('page', 1);
        $perPage = 15;

        $rowsForm = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $onDemandTemplates = FormTemplate::where('is_active', true)
            ->where('mode', FormTemplate::MODE_ON_DEMAND)
            ->orderBy('name')
            ->get();

        return view('admin.forms.index', [
            'rows' => $rowsForm,
            'status' => $status,
            'counts' => $counts,
            'onDemandTemplates' => $onDemandTemplates,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowFromAgreement(Agreement $agreement): array
    {
        $status = $agreement->isSigned() ? 'completed' : ($agreement->isExpired() ? 'expired' : 'pending');

        return [
            'source' => 'agreement',
            'type' => Agreement::typeLabel($agreement->type),
            'identifier' => $agreement->account_id,
            'status' => $status,
            'to_manage' => $agreement->isSigned() && ! $agreement->isManaged(),
            'managed' => $agreement->isManaged(),
            'managed_label' => $agreement->linked_ticket_number,
            'manager_name' => $agreement->manager?->name,
            'managed_at' => $agreement->managed_at,
            'created_at' => $agreement->created_at,
            'creator_name' => $agreement->creator?->name,
            'completed_at' => $agreement->signed_at,
            'completed_label' => __('Signed'),
            'show_url' => route('admin.agreements.show', $agreement),
            'pdf_url' => $agreement->isSigned() ? route('admin.agreements.pdf', $agreement) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowFromSubmission(FormSubmission $submission): array
    {
        $status = $submission->isSubmitted() ? 'completed' : ($submission->isExpired() ? 'expired' : 'pending');

        $identifier = $submission->values
            ->sortBy(fn ($value) => $value->field->sort_order ?? 0)
            ->pluck('value')
            ->first(fn ($value) => filled($value)) ?? '—';

        return [
            'source' => 'submission',
            'type' => $submission->template->name,
            'identifier' => $identifier,
            'status' => $status,
            'to_manage' => $submission->isSubmitted() && ! $submission->isManaged(),
            'managed' => $submission->isManaged(),
            'managed_label' => $submission->reference_note,
            'manager_name' => $submission->manager?->name,
            'managed_at' => $submission->managed_at,
            'created_at' => $submission->created_at,
            'creator_name' => $submission->creator?->name,
            'completed_at' => $submission->submitted_at,
            'completed_label' => __('Submitted'),
            'show_url' => route('admin.forms.show', $submission),
            'pdf_url' => null,
        ];
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
