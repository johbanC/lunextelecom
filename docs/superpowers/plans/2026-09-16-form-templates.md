# Form Templates (Forms module) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an Admin build reusable, configurable form templates (fields + optional signature) and send them to customers two ways — an agent-generated `on_demand` link tied to something already known, or a standing public `standalone` URL — without touching code, while the existing COAM Equipment (`Agreement`) form keeps working exactly as it does today.

**Architecture:** A brand-new, fully additive set of tables/models (`form_templates` → `form_fields`/`form_field_options`, and `form_submissions` → `form_submission_values`) mirrors the existing Tickets catalog pattern (`Issue`/`FieldDefinition`/`FieldOption`) but lives entirely inside the Forms module, with zero dependency on `Ticket`/`Issue`. Admin CRUD is a Livewire component modeled on `CatalogBrowser`. Public submission controllers and views are modeled on `PublicAgreementController`. Completion notifies a group via a new database-only notification, reusing the existing `notifications` table and `NotificationBell` component.

**Tech Stack:** Laravel, Livewire, Blade + Tailwind, `signature_pad` JS (already a dependency), `spatie/laravel-permission`, PHPUnit (class-based, see `tests/Feature/ExampleTest.php`).

**Spec:** `docs/superpowers/specs/2026-09-16-form-templates-design.md`

## Global Constraints

- Do **not** modify `App\Models\Agreement`, `AgreementController`, `PublicAgreementController`, `AgreementPolicy`, `AgreementNotifier`, `AgreementSignedNotification`, or any `agreements` migration/view. COAM Equipment must keep working unchanged.
- Do **not** reference `App\Models\Ticket`, `Issue`, `FieldDefinition`, `Category`, or the `feature:tickets` middleware anywhere in this feature. Tickets is inactive.
- New form templates/submissions **never send email** — notifications are `database` channel only (`via()` returns `['database']`, never `'mail'`).
- Public standalone route uses prefix `/p/{slug}` (not `/f/`) — `/f/{uuid}` is already taken by COAM Equipment and both are single-segment wildcards, so they would collide.
- Follow existing code conventions: Blade class names, `@extends('layouts.admin')`, Tailwind utility classes matching `resources/views/admin/agreements/*.blade.php`, policy-gated routes via `can:` middleware, PHPUnit class-based tests with `RefreshDatabase`.

---

## File Structure

**New migrations** (`database/migrations/`):
- `2026_09_16_100000_create_form_templates_table.php`
- `2026_09_16_100100_create_form_fields_table.php`
- `2026_09_16_100200_create_form_field_options_table.php`
- `2026_09_16_100300_create_form_submissions_table.php`
- `2026_09_16_100400_create_form_submission_values_table.php`

**New models** (`app/Models/`): `FormTemplate.php`, `FormField.php`, `FormFieldOption.php`, `FormSubmission.php`, `FormSubmissionValue.php`

**New policies** (`app/Policies/`): `FormTemplatePolicy.php`, `FormSubmissionPolicy.php`

**New Livewire component**: `app/Livewire/Admin/FormTemplateManager.php` + `resources/views/livewire/admin/form-template-manager.blade.php`

**New controllers**: `app/Http/Controllers/Admin/FormController.php`, `app/Http/Controllers/PublicFormController.php`

**New notification/service**: `app/Notifications/FormSubmittedNotification.php`, `app/Services/FormNotifier.php`

**New views**:
- `resources/views/admin/form-templates/index.blade.php` (thin wrapper around the Livewire component)
- `resources/views/admin/forms/index.blade.php`, `create.blade.php`, `show.blade.php`
- `resources/views/public/forms/on-demand.blade.php`, `thanks.blade.php`, `standalone.blade.php`
- `resources/views/components/form-field-input.blade.php` (shared field renderer, used by admin `create` and both public views)

**New JS**: `resources/js/form-submission.js` (signature pad, generalized — no item-table math)

**Modified files**:
- `routes/web.php` — add admin + public route groups
- `database/seeders/RolesAndPermissionsSeeder.php` — add new permissions
- `resources/views/layouts/admin.blade.php` — nav links + bell visibility condition
- `vite.config.js` — register `resources/js/form-submission.js` as a build entry

---

### Task 1: Catalog schema — `form_templates`, `form_fields`, `form_field_options`

**Files:**
- Create: `database/migrations/2026_09_16_100000_create_form_templates_table.php`
- Create: `database/migrations/2026_09_16_100100_create_form_fields_table.php`
- Create: `database/migrations/2026_09_16_100200_create_form_field_options_table.php`
- Create: `app/Models/FormTemplate.php`
- Create: `app/Models/FormField.php`
- Create: `app/Models/FormFieldOption.php`
- Test: `tests/Feature/FormTemplateCatalogTest.php`

**Interfaces:**
- Produces: `FormTemplate::MODE_ON_DEMAND = 'on_demand'`, `FormTemplate::MODE_STANDALONE = 'standalone'`; `FormTemplate::fields(): HasMany` (ordered by `sort_order`); `FormField::TYPE_TEXT|TYPE_TEXTAREA|TYPE_SELECT|TYPE_CHECKBOX|TYPE_RADIO|TYPE_PICK_N|TYPE_DATE|TYPE_FILE` constants; `FormField::MULTI_VALUE_TYPES`; `FormField::isMultiValue(): bool`; `FormField::template(): BelongsTo`; `FormField::options(): HasMany`; `FormFieldOption::field(): BelongsTo`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormTemplateCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_template_can_have_ordered_fields_with_options(): void
    {
        $user = User::factory()->create();

        $template = FormTemplate::create([
            'name' => 'Potential Retailer Sign Up',
            'mode' => FormTemplate::MODE_STANDALONE,
            'slug' => 'potential-retailer',
            'requires_signature' => false,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $second = $template->fields()->create([
            'label' => 'Market',
            'field_type' => FormField::TYPE_SELECT,
            'sort_order' => 1,
        ]);
        $first = $template->fields()->create([
            'label' => 'Full Name',
            'field_type' => FormField::TYPE_TEXT,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $second->options()->create(['value' => 'Latino Community', 'sort_order' => 0]);

        $this->assertTrue($first->is_required);
        $this->assertEquals('full_name', $first->key);
        $this->assertFalse($second->isMultiValue());
        $this->assertEquals(['Full Name', 'Market'], $template->fields()->pluck('label')->all());
        $this->assertEquals('Latino Community', $second->options->first()->value);
        $this->assertTrue($second->options->first()->field->is($second));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FormTemplateCatalogTest`
Expected: FAIL — `Class "App\Models\FormTemplate" not found` (tables/models don't exist yet).

- [ ] **Step 3: Create the migrations**

`database/migrations/2026_09_16_100000_create_form_templates_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('instructions')->nullable();
            $table->boolean('requires_signature')->default(false);
            $table->string('mode')->default('on_demand'); // on_demand | standalone
            $table->foreignId('notify_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
```

`database/migrations/2026_09_16_100100_create_form_fields_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('label');
            $table->string('key');
            $table->string('field_type');
            $table->boolean('is_required')->default(false);
            $table->boolean('editable_by_recipient')->default(true);
            $table->string('help_text')->nullable();
            $table->unsignedTinyInteger('pick_count')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_template_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
```

`database/migrations/2026_09_16_100200_create_form_field_options_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_field_id')->constrained('form_fields')->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_field_options');
    }
};
```

- [ ] **Step 4: Create the models**

`app/Models/FormTemplate.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    use HasFactory;

    public const MODE_ON_DEMAND = 'on_demand';

    public const MODE_STANDALONE = 'standalone';

    protected $fillable = [
        'name', 'slug', 'instructions', 'requires_signature',
        'mode', 'notify_group_id', 'is_active', 'created_by',
    ];

    protected $casts = [
        'requires_signature' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function isStandalone(): bool
    {
        return $this->mode === self::MODE_STANDALONE;
    }

    /**
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function notifyGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'notify_group_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

`app/Models/FormField.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FormField extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_SELECT = 'select';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_RADIO = 'radio';

    public const TYPE_PICK_N = 'pick_n';

    public const TYPE_DATE = 'date';

    public const TYPE_FILE = 'file';

    public const MULTI_VALUE_TYPES = [self::TYPE_CHECKBOX, self::TYPE_PICK_N];

    protected $fillable = [
        'form_template_id', 'label', 'key', 'field_type',
        'is_required', 'editable_by_recipient', 'help_text', 'pick_count', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'editable_by_recipient' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FormField $field) {
            if (empty($field->key)) {
                $field->key = Str::slug($field->label, '_');
            }
        });
    }

    public function isMultiValue(): bool
    {
        return in_array($this->field_type, self::MULTI_VALUE_TYPES, true);
    }

    /**
     * @return BelongsTo<FormTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    /**
     * @return HasMany<FormFieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<FormSubmissionValue, $this>
     */
    public function submissionValues(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class);
    }
}
```

`app/Models/FormFieldOption.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormFieldOption extends Model
{
    use HasFactory;

    protected $fillable = ['form_field_id', 'value', 'sort_order'];

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
```

- [ ] **Step 5: Run the migration and test**

Run: `php artisan migrate && php artisan test --filter FormTemplateCatalogTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_09_16_100000_create_form_templates_table.php database/migrations/2026_09_16_100100_create_form_fields_table.php database/migrations/2026_09_16_100200_create_form_field_options_table.php app/Models/FormTemplate.php app/Models/FormField.php app/Models/FormFieldOption.php tests/Feature/FormTemplateCatalogTest.php
git commit -m "feat: add form template catalog schema (templates, fields, options)"
```

---

### Task 2: Submission schema — `form_submissions`, `form_submission_values`

**Files:**
- Create: `database/migrations/2026_09_16_100300_create_form_submissions_table.php`
- Create: `database/migrations/2026_09_16_100400_create_form_submission_values_table.php`
- Create: `app/Models/FormSubmission.php`
- Create: `app/Models/FormSubmissionValue.php`
- Test: `tests/Feature/FormSubmissionModelTest.php`

**Interfaces:**
- Consumes: `FormTemplate::MODE_ON_DEMAND`/`MODE_STANDALONE` (Task 1), `FormField` (Task 1).
- Produces: `FormSubmission::STATUS_PENDING = 'pending'`, `STATUS_SUBMITTED = 'submitted'`, `STATUS_EXPIRED = 'expired'`; `FormSubmission::isSubmitted(): bool`; `isExpired(): bool`; `publicUrl(): string`; `FormSubmission::template(): BelongsTo`; `FormSubmission::values(): HasMany`; `FormSubmission::scopePending`, `scopeSubmitted`, `scopeToManage`; `FormSubmissionValue::submission(): BelongsTo`, `field(): BelongsTo`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSubmissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_submission_stores_values_per_field_and_reports_expiry(): void
    {
        $user = User::factory()->create();

        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'requires_signature' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $field = $template->fields()->create([
            'label' => 'New phone number',
            'field_type' => FormField::TYPE_TEXT,
            'is_required' => true,
            'editable_by_recipient' => false,
        ]);

        $submission = FormSubmission::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'expires_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        $submission->values()->create(['form_field_id' => $field->id, 'value' => '+19294134764']);

        $this->assertTrue($submission->isExpired());
        $this->assertFalse($submission->isSubmitted());
        $this->assertEquals('+19294134764', $submission->values()->first()->value);
        $this->assertTrue($submission->values()->first()->field->is($field));
        $this->assertStringContainsString($submission->uuid, $submission->publicUrl());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FormSubmissionModelTest`
Expected: FAIL — `Class "App\Models\FormSubmission" not found`.

- [ ] **Step 3: Create the migrations**

`database/migrations/2026_09_16_100300_create_form_submissions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | submitted | expired
            $table->timestamp('expires_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('reference_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('managed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('managed_at')->nullable();
            $table->timestamps();

            $table->index(['form_template_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
```

`database/migrations/2026_09_16_100400_create_form_submission_values_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('form_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['form_submission_id', 'form_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_values');
    }
};
```

- [ ] **Step 4: Create the models**

`app/Models/FormSubmission.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'uuid', 'form_template_id', 'status', 'expires_at', 'signature_path',
        'signed_ip', 'submitted_at', 'reference_note', 'created_by', 'managed_by', 'managed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'managed_at' => 'datetime',
    ];

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isExpired(): bool
    {
        return ! $this->isSubmitted() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isManaged(): bool
    {
        return $this->managed_at !== null;
    }

    public function publicUrl(): string
    {
        return $this->template->isStandalone()
            ? route('public.forms.standalone.show', $this->template->slug)
            : route('public.forms.show', $this->uuid);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeToManage($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED)->whereNull('managed_at');
    }

    /**
     * @return BelongsTo<FormTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    /**
     * @return HasMany<FormSubmissionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }
}
```

`app/Models/FormSubmissionValue.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionValue extends Model
{
    use HasFactory;

    protected $fillable = ['form_submission_id', 'form_field_id', 'value'];

    /**
     * @return BelongsTo<FormSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
```

- [ ] **Step 5: Run the migration and test**

Run: `php artisan migrate && php artisan test --filter FormSubmissionModelTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_09_16_100300_create_form_submissions_table.php database/migrations/2026_09_16_100400_create_form_submission_values_table.php app/Models/FormSubmission.php app/Models/FormSubmissionValue.php tests/Feature/FormSubmissionModelTest.php
git commit -m "feat: add form submission schema (submissions, submission values)"
```

---

### Task 3: Permissions and policies

**Files:**
- Modify: `database/seeders/RolesAndPermissionsSeeder.php:16-42`
- Create: `app/Policies/FormTemplatePolicy.php`
- Create: `app/Policies/FormSubmissionPolicy.php`
- Test: `tests/Feature/FormPermissionsTest.php`

**Interfaces:**
- Consumes: `FormTemplate`, `FormSubmission` (Tasks 1–2).
- Produces: permissions `form_templates.manage`, `forms.view`, `forms.create`, `forms.manage`; `FormTemplatePolicy::viewAny|create|update|delete`; `FormSubmissionPolicy::viewAny|view|create|manage`.

- [ ] **Step 1: Find where `AgreementPolicy` is registered**

Run: `grep -rn "AgreementPolicy" app/Providers bootstrap`

Use whatever mechanism that shows (explicit `Gate::policy` map, or Laravel auto-discovery via model/policy naming convention — `FormTemplate` → `FormTemplatePolicy`, `FormSubmission` → `FormSubmissionPolicy`). If `Agreement`/`AgreementPolicy` isn't explicitly registered anywhere, auto-discovery already covers the new ones and no provider change is needed.

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agente_can_manage_submissions_but_not_templates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $agente = User::factory()->create();
        $agente->assignRole('Agente');

        $this->assertTrue($agente->can('viewAny', FormSubmission::class));
        $this->assertTrue($agente->can('create', FormSubmission::class));
        $this->assertFalse($agente->can('viewAny', FormTemplate::class));
    }

    public function test_admin_can_manage_everything(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->assertTrue($admin->can('viewAny', FormTemplate::class));
        $this->assertTrue($admin->can('create', FormTemplate::class));
        $this->assertTrue($admin->can('manage', FormSubmission::class));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter FormPermissionsTest`
Expected: FAIL — permission `form_templates.manage` does not exist / policy not found.

- [ ] **Step 4: Update the seeder**

In `database/seeders/RolesAndPermissionsSeeder.php`, change the `GROUPS` constant so `'Formularios'` gains three lines and a new bucket is inserted right after it:

```php
    public const GROUPS = [
        'Formularios' => [
            'agreements.view',
            'agreements.create',
            'agreements.manage',
            'agreements.extend',
            'forms.view',
            'forms.create',
            'forms.manage',
        ],
        'Plantillas de formulario' => [
            'form_templates.manage',
        ],
        'Usuarios y roles' => [
```

Leave every other array entry untouched.

- [ ] **Step 5: Create the policies**

`app/Policies/FormTemplatePolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\FormTemplate;
use App\Models\User;

class FormTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('form_templates.manage');
    }

    public function view(User $user, FormTemplate $formTemplate): bool
    {
        return $user->can('form_templates.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('form_templates.manage');
    }

    public function update(User $user, FormTemplate $formTemplate): bool
    {
        return $user->can('form_templates.manage');
    }

    public function delete(User $user, FormTemplate $formTemplate): bool
    {
        return $user->can('form_templates.manage');
    }
}
```

`app/Policies/FormSubmissionPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\FormSubmission;
use App\Models\User;

class FormSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('forms.view');
    }

    public function view(User $user, FormSubmission $formSubmission): bool
    {
        return $user->can('forms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('forms.create');
    }

    public function manage(User $user, FormSubmission $formSubmission): bool
    {
        return $user->can('forms.manage');
    }
}
```

- [ ] **Step 6: Register policies if needed**

If Step 1's grep showed an explicit policy map (e.g. in a provider's `$policies` array or a `Gate::policy(...)` call), add both new mappings there following the exact same style as the `Agreement::class => AgreementPolicy::class` line. If nothing turned up (auto-discovery), skip — no file changes needed.

- [ ] **Step 7: Run tests and verify pass**

Run: `php artisan test --filter FormPermissionsTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add database/seeders/RolesAndPermissionsSeeder.php app/Policies/FormTemplatePolicy.php app/Policies/FormSubmissionPolicy.php tests/Feature/FormPermissionsTest.php
git commit -m "feat: add form templates/submissions permissions and policies"
```

---

### Task 4: Admin template builder (Livewire)

**Files:**
- Create: `app/Livewire/Admin/FormTemplateManager.php`
- Create: `resources/views/livewire/admin/form-template-manager.blade.php`
- Create: `resources/views/admin/form-templates/index.blade.php`
- Modify: `routes/web.php` (add `admin.form-templates.index` route)
- Test: `tests/Feature/FormTemplateManagerTest.php`

**Interfaces:**
- Consumes: `FormTemplate`, `FormField`, `FormFieldOption` (Task 1), `Group` (existing model), `form_templates.manage` permission (Task 3).
- Produces: route `admin.form-templates.index`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Livewire\Admin\FormTemplateManager;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormTemplateManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_template_and_add_a_field(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        Livewire::test(FormTemplateManager::class)
            ->set('templateForm.name', 'Potential Retailer Sign Up')
            ->set('templateForm.mode', FormTemplate::MODE_STANDALONE)
            ->set('templateForm.slug', 'potential-retailer')
            ->set('templateForm.requires_signature', false)
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $template = FormTemplate::firstOrFail();
        $this->assertEquals('Potential Retailer Sign Up', $template->name);

        Livewire::test(FormTemplateManager::class)
            ->call('selectTemplate', $template->id)
            ->call('newField')
            ->set('fieldForm.label', 'Full Name')
            ->set('fieldForm.field_type', 'text')
            ->call('saveField')
            ->assertHasNoErrors();

        $this->assertEquals(1, $template->fields()->count());
        $this->assertEquals('Full Name', $template->fields()->first()->label);
    }

    public function test_agente_cannot_access_the_template_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agente = User::factory()->create();
        $agente->assignRole('Agente');
        $this->actingAs($agente);

        Livewire::test(FormTemplateManager::class)->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FormTemplateManagerTest`
Expected: FAIL — component class doesn't exist.

- [ ] **Step 3: Write the Livewire component**

`app/Livewire/Admin/FormTemplateManager.php`:

```php
<?php

namespace App\Livewire\Admin;

use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormTemplate;
use App\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class FormTemplateManager extends Component
{
    public ?int $templateId = null;

    public bool $showTemplateForm = false;

    /** @var array<string, mixed> */
    public array $templateForm = [];

    public bool $showFieldForm = false;

    /** @var array<string, mixed> */
    public array $fieldForm = [];

    public string $newOptionValue = '';

    public function mount(): void
    {
        $this->authorize('viewAny', FormTemplate::class);
    }

    public function selectTemplate(int $templateId): void
    {
        $this->templateId = $templateId;
    }

    public function newTemplate(): void
    {
        $this->authorize('create', FormTemplate::class);

        $this->templateForm = [
            'id' => null,
            'name' => '',
            'slug' => '',
            'instructions' => '',
            'requires_signature' => false,
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'notify_group_id' => null,
            'is_active' => true,
        ];
        $this->showTemplateForm = true;
    }

    public function editTemplate(int $templateId): void
    {
        $template = FormTemplate::findOrFail($templateId);

        $this->templateForm = [
            'id' => $template->id,
            'name' => $template->name,
            'slug' => $template->slug,
            'instructions' => $template->instructions,
            'requires_signature' => $template->requires_signature,
            'mode' => $template->mode,
            'notify_group_id' => $template->notify_group_id,
            'is_active' => $template->is_active,
        ];
        $this->showTemplateForm = true;
    }

    public function saveTemplate(): void
    {
        $this->authorize(($this->templateForm['id'] ?? null) ? 'update' : 'create', FormTemplate::class);

        $data = $this->validate([
            'templateForm.name' => ['required', 'string', 'max:255'],
            'templateForm.mode' => ['required', 'in:'.FormTemplate::MODE_ON_DEMAND.','.FormTemplate::MODE_STANDALONE],
            'templateForm.slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                'required_if:templateForm.mode,'.FormTemplate::MODE_STANDALONE,
                'unique:form_templates,slug,'.($this->templateForm['id'] ?? 'NULL'),
            ],
            'templateForm.instructions' => ['nullable', 'string', 'max:2000'],
            'templateForm.requires_signature' => ['boolean'],
            'templateForm.notify_group_id' => ['nullable', 'exists:groups,id'],
            'templateForm.is_active' => ['boolean'],
        ])['templateForm'];

        $template = FormTemplate::updateOrCreate(
            ['id' => $this->templateForm['id']],
            [
                'name' => $data['name'],
                'slug' => $data['mode'] === FormTemplate::MODE_STANDALONE ? $data['slug'] : null,
                'instructions' => $data['instructions'] ?: null,
                'requires_signature' => (bool) $data['requires_signature'],
                'mode' => $data['mode'],
                'notify_group_id' => $data['notify_group_id'],
                'is_active' => (bool) $data['is_active'],
                'created_by' => $this->templateForm['id'] ? FormTemplate::find($this->templateForm['id'])->created_by : Auth::id(),
            ]
        );

        $this->templateId = $template->id;
        $this->showTemplateForm = false;
    }

    public function newField(): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $this->fieldForm = [
            'id' => null,
            'label' => '',
            'field_type' => FormField::TYPE_TEXT,
            'is_required' => false,
            'editable_by_recipient' => true,
            'help_text' => '',
            'pick_count' => null,
            'sort_order' => FormField::where('form_template_id', $this->templateId)->count(),
            'options' => [],
        ];
        $this->showFieldForm = true;
    }

    public function editField(int $fieldId): void
    {
        $field = FormField::with('options')->findOrFail($fieldId);
        $this->fieldForm = [
            'id' => $field->id,
            'label' => $field->label,
            'field_type' => $field->field_type,
            'is_required' => $field->is_required,
            'editable_by_recipient' => $field->editable_by_recipient,
            'help_text' => $field->help_text ?? '',
            'pick_count' => $field->pick_count,
            'sort_order' => $field->sort_order,
            'options' => $field->options->pluck('value', 'id')->all(),
        ];
        $this->showFieldForm = true;
    }

    public function saveField(): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $data = $this->validate([
            'fieldForm.label' => ['required', 'string', 'max:255'],
            'fieldForm.field_type' => ['required', 'in:'.implode(',', [
                FormField::TYPE_TEXT, FormField::TYPE_TEXTAREA, FormField::TYPE_SELECT,
                FormField::TYPE_CHECKBOX, FormField::TYPE_RADIO, FormField::TYPE_PICK_N,
                FormField::TYPE_DATE, FormField::TYPE_FILE,
            ])],
            'fieldForm.is_required' => ['boolean'],
            'fieldForm.editable_by_recipient' => ['boolean'],
            'fieldForm.help_text' => ['nullable', 'string', 'max:500'],
            'fieldForm.pick_count' => ['nullable', 'integer', 'min:1', 'max:10', 'required_if:fieldForm.field_type,'.FormField::TYPE_PICK_N],
            'fieldForm.sort_order' => ['required', 'integer', 'min:0'],
        ])['fieldForm'];

        $field = FormField::updateOrCreate(
            ['id' => $this->fieldForm['id']],
            [
                'form_template_id' => $this->templateId,
                'label' => $data['label'],
                'field_type' => $data['field_type'],
                'is_required' => (bool) $data['is_required'],
                'editable_by_recipient' => (bool) $data['editable_by_recipient'],
                'help_text' => $data['help_text'] ?: null,
                'pick_count' => $data['field_type'] === FormField::TYPE_PICK_N ? $data['pick_count'] : null,
                'sort_order' => $data['sort_order'],
            ]
        );

        $needsOptions = in_array($data['field_type'], [
            FormField::TYPE_SELECT, FormField::TYPE_CHECKBOX, FormField::TYPE_RADIO, FormField::TYPE_PICK_N,
        ], true);

        if ($needsOptions) {
            $this->editField($field->id);
        } else {
            $this->showFieldForm = false;
        }
    }

    public function deleteField(int $fieldId): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $field = FormField::withCount('submissionValues')->findOrFail($fieldId);

        if ($field->submission_values_count > 0) {
            $this->addError('fieldForm.label', __('This field already has answers on existing submissions and cannot be deleted. Deactivate the template instead if it is no longer used.'));

            return;
        }

        $field->delete();
        $this->showFieldForm = false;
    }

    public function moveField(int $fieldId, string $direction): void
    {
        $this->authorize('update', FormTemplate::findOrFail($this->templateId));

        $fields = FormField::where('form_template_id', $this->templateId)->orderBy('sort_order')->get();

        $this->reorder($fields, $fieldId, $direction);
    }

    public function addFieldOption(): void
    {
        $value = trim($this->newOptionValue);

        if ($value === '' || ! $this->fieldForm['id']) {
            return;
        }

        $option = FormFieldOption::create([
            'form_field_id' => $this->fieldForm['id'],
            'value' => $value,
            'sort_order' => count($this->fieldForm['options']),
        ]);

        $this->fieldForm['options'][$option->id] = $option->value;
        $this->newOptionValue = '';
    }

    public function removeFieldOption(int $optionId): void
    {
        FormFieldOption::where('id', $optionId)->delete();
        unset($this->fieldForm['options'][$optionId]);
    }

    /**
     * @param  Collection<int, Model>  $items
     */
    protected function reorder(Collection $items, int $id, string $direction): void
    {
        $items = $items->values();

        $items->each(function (Model $item, int $index) {
            if ($item->sort_order !== $index) {
                $item->update(['sort_order' => $index]);
            }
        });

        $index = $items->search(fn (Model $item) => $item->id === $id);

        if ($index === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapWith < 0 || $swapWith >= $items->count()) {
            return;
        }

        $items[$index]->update(['sort_order' => $swapWith]);
        $items[$swapWith]->update(['sort_order' => $index]);
    }

    public function render(): View
    {
        $templates = FormTemplate::withCount('fields')->orderBy('name')->get();

        $selectedTemplate = $this->templateId
            ? FormTemplate::with('fields.options')->find($this->templateId)
            : null;

        return view('livewire.admin.form-template-manager', [
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'groups' => Group::where('is_active', true)->orderBy('name')->get(),
            'fieldTypes' => [
                FormField::TYPE_TEXT => __('Text'),
                FormField::TYPE_TEXTAREA => __('Textarea'),
                FormField::TYPE_SELECT => __('Select'),
                FormField::TYPE_CHECKBOX => __('Checkbox (multiple)'),
                FormField::TYPE_RADIO => __('Radio (single)'),
                FormField::TYPE_PICK_N => __('Pick N'),
                FormField::TYPE_DATE => __('Date'),
                FormField::TYPE_FILE => __('File'),
            ],
        ]);
    }
}
```

- [ ] **Step 4: Write the Blade view**

`resources/views/livewire/admin/form-template-manager.blade.php` — two-column layout: template list on the left with a "New template" button opening `showTemplateForm`; selected template's field list on the right with a "New field" button opening `showFieldForm`. Read `resources/views/livewire/admin/catalog-browser.blade.php` first and follow its exact structural pattern, collapsing "Category → Issue → Field" into a flat "Template → Field" list, using `$templates`, `$selectedTemplate`, `$groups`, `$fieldTypes` from the render method above, and `wire:click`/`wire:model` bindings matching the component's public properties (`templateForm.*`, `fieldForm.*`, `showTemplateForm`, `showFieldForm`, `newOptionValue`). Include a `mode` select (`on_demand` / `standalone`), a `slug` input shown only when `templateForm.mode === 'standalone'` (Alpine `x-show`), a `requires_signature` checkbox, an `editable_by_recipient` checkbox in the field form, and a `notify_group_id` select populated from `$groups`.

- [ ] **Step 5: Add the route**

In `routes/web.php`, inside the `Route::prefix('admin')->name('admin.')` group, after the `agreements/*` block and before `Route::prefix('tickets')`:

```php
        Route::get('form-templates', function () {
            return view('admin.form-templates.index');
        })->name('form-templates.index')->middleware('can:viewAny,'.\App\Models\FormTemplate::class);
```

`resources/views/admin/form-templates/index.blade.php`:

```php
@extends('layouts.admin')

@section('title', __('Form templates'))

@section('content')
    <livewire:admin.form-template-manager />
@endsection
```

- [ ] **Step 6: Run tests and verify pass**

Run: `php artisan test --filter FormTemplateManagerTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Admin/FormTemplateManager.php resources/views/livewire/admin/form-template-manager.blade.php resources/views/admin/form-templates/index.blade.php routes/web.php tests/Feature/FormTemplateManagerTest.php
git commit -m "feat: add admin form template builder"
```

---

### Task 5: Shared field-rendering component

**Files:**
- Create: `resources/views/components/form-field-input.blade.php`
- Test: `tests/Feature/FormFieldInputComponentTest.php`

**Interfaces:**
- Consumes: `FormField` (Task 1).
- Produces: `<x-form-field-input :field="$field" :value="$value" :readonly="$readonly" />` — renders the correct input for `$field->field_type`, named `values[{{ $field->key }}]` (or `values[{{ $field->key }}][]` for multi-value types), pre-filled with `$value`, disabled/readonly when `$readonly` is true.

- [ ] **Step 1: Check whether anonymous components need a class**

Run: `find app/View -type f 2>/dev/null; grep -n "class=\"" resources/views/components/locale-switcher.blade.php 2>/dev/null | head -1`

If `app/View/Components/` doesn't exist or has no `LocaleSwitcher.php`-style class backing `<x-locale-switcher>`, this project uses anonymous Blade components — no PHP class needed, proceed straight to Step 3.

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormFieldInputComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_text_input_with_the_field_key_as_name(): void
    {
        $user = User::factory()->create();
        $template = FormTemplate::create(['name' => 'T', 'mode' => 'standalone', 'slug' => 't', 'created_by' => $user->id]);
        $field = $template->fields()->create(['label' => 'Full Name', 'field_type' => FormField::TYPE_TEXT, 'is_required' => true]);

        $html = (string) view('components.form-field-input', ['field' => $field, 'value' => 'Katherine', 'readonly' => false])->render();

        $this->assertStringContainsString('name="values[full_name]"', $html);
        $this->assertStringContainsString('value="Katherine"', $html);
        $this->assertStringContainsString('required', $html);
    }

    public function test_readonly_fields_are_disabled_for_the_recipient(): void
    {
        $user = User::factory()->create();
        $template = FormTemplate::create(['name' => 'T', 'mode' => 'on_demand', 'created_by' => $user->id]);
        $field = $template->fields()->create(['label' => 'Current number', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false]);

        $html = (string) view('components.form-field-input', ['field' => $field, 'value' => '6688', 'readonly' => true])->render();

        $this->assertStringContainsString('readonly', $html);
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter FormFieldInputComponentTest`
Expected: FAIL — view `components.form-field-input` not found.

- [ ] **Step 4: Write the component view**

`resources/views/components/form-field-input.blade.php`:

```blade
@props(['field', 'value' => null, 'readonly' => false])

@php
    $inputClasses = 'w-full h-11 border border-gray-300 rounded-lg px-3 font-medium focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition'
        . ($readonly ? ' bg-gray-100 text-gray-500 cursor-not-allowed' : '');
    $name = $field->isMultiValue() ? "values[{$field->key}][]" : "values[{$field->key}]";
    $selected = old("values.{$field->key}", $value);
@endphp

<div>
    <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">
        {{ $field->label }}@if ($field->is_required)<span class="text-brand-red">*</span>@endif
    </label>

    @if ($field->field_type === \App\Models\FormField::TYPE_TEXTAREA)
        <textarea name="{{ $name }}" rows="3" @required($field->is_required) @readonly($readonly)
            class="{{ $inputClasses }}">{{ $selected }}</textarea>
    @elseif ($field->field_type === \App\Models\FormField::TYPE_DATE)
        <input type="date" name="{{ $name }}" value="{{ $selected }}" @required($field->is_required) @readonly($readonly) class="{{ $inputClasses }}">
    @elseif ($field->field_type === \App\Models\FormField::TYPE_FILE)
        <input type="file" name="{{ $name }}" @required($field->is_required) @disabled($readonly) class="{{ $inputClasses }}">
    @elseif ($field->field_type === \App\Models\FormField::TYPE_SELECT)
        <select name="{{ $name }}" @required($field->is_required) @disabled($readonly) class="{{ $inputClasses }} bg-white">
            <option value="">{{ __('Select...') }}</option>
            @foreach ($field->options as $option)
                <option value="{{ $option->value }}" @selected($selected === $option->value)>{{ $option->value }}</option>
            @endforeach
        </select>
    @elseif (in_array($field->field_type, [\App\Models\FormField::TYPE_CHECKBOX, \App\Models\FormField::TYPE_RADIO, \App\Models\FormField::TYPE_PICK_N]))
        <div class="space-y-1.5">
            @php $inputType = $field->field_type === \App\Models\FormField::TYPE_RADIO ? 'radio' : 'checkbox'; @endphp
            @foreach ($field->options as $option)
                <label class="flex items-center gap-2 text-sm">
                    <input type="{{ $inputType }}" name="{{ $name }}" value="{{ $option->value }}"
                        @checked(is_array($selected) && in_array($option->value, $selected)) @disabled($readonly)>
                    {{ $option->value }}
                </label>
            @endforeach
        </div>
    @else
        <input type="text" name="{{ $name }}" value="{{ $selected }}" @required($field->is_required) @readonly($readonly) class="{{ $inputClasses }}">
    @endif

    @if ($field->help_text)
        <p class="text-xs text-gray-500 mt-1">{{ $field->help_text }}</p>
    @endif
    @error("values.{$field->key}") <p class="text-brand-red text-sm mt-1">{{ $message }}</p> @enderror
</div>
```

- [ ] **Step 5: Run tests and verify pass**

Run: `php artisan test --filter FormFieldInputComponentTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/form-field-input.blade.php tests/Feature/FormFieldInputComponentTest.php
git commit -m "feat: add shared form field input component"
```

---

### Task 6: Notifications (database-only)

**Files:**
- Create: `app/Notifications/FormSubmittedNotification.php`
- Create: `app/Services/FormNotifier.php`
- Test: `tests/Feature/FormNotifierTest.php`

**Interfaces:**
- Consumes: `FormSubmission`, `FormTemplate::notifyGroup()` (Tasks 1–2), `Group::members()` (existing).
- Produces: `FormNotifier::notifySubmitted(FormSubmission $submission): void`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Group;
use App\Models\User;
use App\Services\FormNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_group_members_via_database_only(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $group = Group::create(['name' => 'Retention', 'is_active' => true]);
        $member = User::factory()->create();
        $group->members()->attach($member->id);

        $template = FormTemplate::create([
            'name' => 'Potential Retailer Sign Up',
            'mode' => FormTemplate::MODE_STANDALONE,
            'slug' => 'potential-retailer',
            'notify_group_id' => $group->id,
            'created_by' => $creator->id,
        ]);

        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        FormNotifier::notifySubmitted($submission);

        Notification::assertSentTo($member, \App\Notifications\FormSubmittedNotification::class);

        $notification = new \App\Notifications\FormSubmittedNotification($submission);
        $this->assertEquals(['database'], $notification->via($member));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FormNotifierTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the notification**

`app/Notifications/FormSubmittedNotification.php`:

```php
<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que alguien completó un formulario nuevo (form_submissions).
 * A diferencia de AgreementSignedNotification, esta SOLO usa el canal
 * database — los formularios nuevos nunca envían correo.
 */
class FormSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public FormSubmission $submission) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'form_submission_id' => $this->submission->id,
            'subject' => $this->subject(),
            'line' => $this->line(),
            'url' => route('admin.forms.show', $this->submission),
        ];
    }

    public function subject(): string
    {
        return "Form submitted: {$this->submission->template->name}";
    }

    protected function line(): string
    {
        return "A new response was received for \"{$this->submission->template->name}\".";
    }
}
```

- [ ] **Step 4: Write the notifier service**

`app/Services/FormNotifier.php`:

```php
<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Notifications\FormSubmittedNotification;

/**
 * Notifica al grupo configurado en la plantilla (form_templates.notify_group_id)
 * cuando se completa un envío. Solo canal database — nunca correo (a diferencia
 * de AgreementNotifier, que sí manda correo para COAM Equipment y no se toca).
 */
class FormNotifier
{
    public static function notifySubmitted(FormSubmission $submission): void
    {
        $group = $submission->template->notifyGroup;

        if (! $group) {
            return;
        }

        $group->loadMissing('members');

        foreach ($group->members as $member) {
            $member->notify(new FormSubmittedNotification($submission));
        }
    }
}
```

- [ ] **Step 5: Run tests and verify pass**

Run: `php artisan test --filter FormNotifierTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Notifications/FormSubmittedNotification.php app/Services/FormNotifier.php tests/Feature/FormNotifierTest.php
git commit -m "feat: add database-only notification for form submissions"
```

---

### Task 7: Admin submissions list + on_demand link generation

**Files:**
- Create: `app/Http/Controllers/Admin/FormController.php`
- Create: `resources/views/admin/forms/index.blade.php`
- Create: `resources/views/admin/forms/create.blade.php`
- Create: `resources/views/admin/forms/show.blade.php`
- Modify: `routes/web.php` (add `admin.forms.*` routes)
- Modify: `resources/views/layouts/admin.blade.php` (nav links + bell visibility)
- Test: `tests/Feature/Admin/FormControllerTest.php`

**Interfaces:**
- Consumes: `FormTemplate`, `FormSubmission`, `FormField` (Tasks 1–2), `x-form-field-input` (Task 5), `forms.view`/`forms.create`/`forms.manage` (Task 3).
- Produces: routes `admin.forms.index`, `admin.forms.create`, `admin.forms.store`, `admin.forms.show`, `admin.forms.manage`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_generate_an_on_demand_link_with_known_values(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agente = User::factory()->create();
        $agente->assignRole('Agente');

        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'requires_signature' => true,
            'is_active' => true,
            'created_by' => $agente->id,
        ]);
        $known = $template->fields()->create([
            'label' => 'Current number', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false,
        ]);

        $response = $this->actingAs($agente)->post(route('admin.forms.store'), [
            'form_template_id' => $template->id,
            'expires_in' => '7',
            'known_values' => [$known->id => '6688'],
        ]);

        $submission = $template->submissions()->firstOrFail();
        $response->assertRedirect(route('admin.forms.show', $submission));
        $this->assertEquals('6688', $submission->values()->where('form_field_id', $known->id)->first()->value);
        $this->assertEquals('pending', $submission->status);
    }

    public function test_forms_index_lists_submissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agente = User::factory()->create();
        $agente->assignRole('Agente');

        $response = $this->actingAs($agente)->get(route('admin.forms.index'));

        $response->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter FormControllerTest`
Expected: FAIL — route `admin.forms.store` not defined.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/Admin/FormController.php`:

```php
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
```

- [ ] **Step 4: Add routes**

In `routes/web.php`, add `use App\Http\Controllers\Admin\FormController;` near the other `use App\Http\Controllers\Admin\...` import, then inside `Route::prefix('admin')->name('admin.')`, right after the `form-templates` route added in Task 4:

```php
        Route::get('forms', [FormController::class, 'index'])->name('forms.index')->middleware('can:viewAny,'.\App\Models\FormSubmission::class);
        Route::get('forms/create', [FormController::class, 'create'])->name('forms.create')->middleware('can:create,'.\App\Models\FormSubmission::class);
        Route::post('forms', [FormController::class, 'store'])->name('forms.store')->middleware('can:create,'.\App\Models\FormSubmission::class);
        Route::get('forms/{submission}', [FormController::class, 'show'])->name('forms.show')->middleware('can:view,submission');
        Route::post('forms/{submission}/manage', [FormController::class, 'manage'])->name('forms.manage')->middleware('can:manage,submission');
```

- [ ] **Step 5: Write the views**

`resources/views/admin/forms/index.blade.php` — copy the structure of `resources/views/admin/agreements/index.blade.php` (status pill filters, table), replacing the "Account ID"/"Type" columns with "Template" (`$submission->template->name`) and a "Generate link" button (`route('admin.forms.create')`) gated by `@can('create', \App\Models\FormSubmission::class)`.

`resources/views/admin/forms/create.blade.php` — copy the structure of `resources/views/admin/agreements/create.blade.php`: a `<select name="form_template_id">` populated from `$templates`, then, for each template, an `x-show`-guarded block (Alpine, keyed on the selected template id) looping its fields where `editable_by_recipient === false` and rendering a plain labeled text input named `known_values[{{ $field->id }}]` (id-keyed, not the recipient-facing `x-form-field-input` component, since this is the agent prefilling data before the link exists) plus a `<select name="expires_in">` matching `Agreement::expirationOptions()`'s value set (`1|3|7|15|30|none`).

`resources/views/admin/forms/show.blade.php` — mirror `resources/views/admin/agreements/show.blade.php`: shareable link (`$submission->publicUrl()`), status badge, a table of `$submission->values` joined against `$submission->template->fields` for labels, and the "manage" form (`reference_note` input posting to `admin.forms.manage`).

- [ ] **Step 6: Run tests and verify pass**

Run: `php artisan test --filter FormControllerTest`
Expected: PASS

- [ ] **Step 7: Update nav and bell visibility**

In `resources/views/layouts/admin.blade.php`, after the `@can('agreements.view')` block, add:

```blade
                    @can('viewAny', \App\Models\FormSubmission::class)
                        <a href="{{ route('admin.forms.index') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                                {{ request()->routeIs('admin.forms.*') ? 'bg-brand-blue-50 text-brand-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ __('New Forms') }}
                        </a>
                    @endcan
                    @can('viewAny', \App\Models\FormTemplate::class)
                        <a href="{{ route('admin.form-templates.index') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold transition
                                {{ request()->routeIs('admin.form-templates.*') ? 'bg-brand-blue-50 text-brand-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ __('Form Templates') }}
                        </a>
                    @endcan
```

Change the notification bell's `@if` condition from `config('features.tickets') || config('features.emails')` to also include `|| auth()->user()->can('forms.view')`.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/FormController.php resources/views/admin/forms resources/views/layouts/admin.blade.php routes/web.php tests/Feature/Admin/FormControllerTest.php
git commit -m "feat: add admin submissions list and on-demand link generation"
```

---

### Task 8: Public on_demand submission flow

**Files:**
- Create: `app/Http/Controllers/PublicFormController.php`
- Create: `resources/views/public/forms/on-demand.blade.php`
- Create: `resources/views/public/forms/thanks.blade.php`
- Create: `resources/js/form-submission.js`
- Modify: `routes/web.php` (add `public.forms.*` routes)
- Modify: `vite.config.js` (register `resources/js/form-submission.js`)
- Test: `tests/Feature/PublicFormOnDemandTest.php`

**Interfaces:**
- Consumes: `FormSubmission`, `FormTemplate`, `FormField` (Tasks 1–2), `x-form-field-input` (Task 5), `FormNotifier::notifySubmitted()` (Task 6).
- Produces: routes `public.forms.show`, `public.forms.store`, `public.forms.thanks`, `public.forms.standalone.show`, `public.forms.standalone.store`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicFormOnDemandTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_can_view_and_submit_a_pending_on_demand_form(): void
    {
        Notification::fake();

        $agent = User::factory()->create();
        $template = FormTemplate::create([
            'name' => 'Update OTP Phone Number',
            'mode' => FormTemplate::MODE_ON_DEMAND,
            'requires_signature' => true,
            'created_by' => $agent->id,
        ]);
        $readonly = $template->fields()->create(['label' => 'Current number', 'field_type' => FormField::TYPE_TEXT, 'editable_by_recipient' => false]);
        $editable = $template->fields()->create(['label' => 'New number', 'field_type' => FormField::TYPE_TEXT, 'is_required' => true]);

        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
            'created_by' => $agent->id,
        ]);
        $submission->values()->create(['form_field_id' => $readonly->id, 'value' => '6688']);

        $this->get(route('public.forms.show', $submission->uuid))->assertOk();

        $response = $this->post(route('public.forms.store', $submission->uuid), [
            'values' => [$editable->key => '9294134764'],
            'signature' => 'data:image/png;base64,'.base64_encode('fake'),
        ]);

        $submission->refresh();
        $response->assertRedirect(route('public.forms.thanks', $submission->uuid));
        $this->assertTrue($submission->isSubmitted());
        $this->assertEquals('9294134764', $submission->values()->where('form_field_id', $editable->id)->first()->value);
        $this->assertNotNull($submission->signature_path);
    }

    public function test_expired_submission_rejects_new_submits(): void
    {
        $agent = User::factory()->create();
        $template = FormTemplate::create(['name' => 'X', 'mode' => FormTemplate::MODE_ON_DEMAND, 'created_by' => $agent->id]);
        $submission = FormSubmission::create([
            'uuid' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'status' => FormSubmission::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        $this->post(route('public.forms.store', $submission->uuid), ['values' => []])->assertForbidden();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter PublicFormOnDemandTest`
Expected: FAIL — route `public.forms.show` not defined.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/PublicFormController.php`:

```php
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
```

- [ ] **Step 4: Add routes**

In `routes/web.php`, after the existing `Route::prefix('f')->name('public.')` block (do not touch it), add `use App\Http\Controllers\PublicFormController;` near the other public controller imports and:

```php
Route::prefix('formularios')->name('public.forms.')->group(function () {
    Route::get('{uuid}', [PublicFormController::class, 'show'])->name('show');
    Route::post('{uuid}', [PublicFormController::class, 'store'])->name('store');
    Route::get('{uuid}/gracias', [PublicFormController::class, 'thanks'])->name('thanks');
});

Route::prefix('p')->name('public.forms.standalone.')->group(function () {
    Route::get('{slug}', [PublicFormController::class, 'showStandalone'])->name('show');
    Route::post('{slug}', [PublicFormController::class, 'storeStandalone'])->name('store');
});
```

- [ ] **Step 5: Write the views and JS**

`resources/js/form-submission.js` — copy `resources/js/agreement-form.js`, keep only the `SignaturePad` init/resize/clear/submit-guard logic; drop the `last4BankInput`/`authorizationDigitsInput` sync and the `qty-input`/`item-row`/`grandTotal` block entirely (out of scope). Target `#formSubmission` as the form id and `#signatureCanvas`/`#signatureInput`/`#clearSignature` as before. Guard every signature-pad line behind `if (!canvas) return;` inside the `DOMContentLoaded` handler so the file no-ops cleanly on pages without a signature (standalone forms with `requires_signature = false`).

`resources/views/public/forms/on-demand.blade.php` — copy the structure of `resources/views/public/agreements/show.blade.php`: same page chrome (logo, locale switcher, gradient header using `$template->name`), the three states (`$submission->isSubmitted()`, `$expired`, else), and inside the form loop `$template->fields as $field` rendering `<x-form-field-input :field="$field" :value="$knownValues[$field->id] ?? null" :readonly="!$field->editable_by_recipient" />`. Show the signature canvas block only `@if ($template->requires_signature)`; otherwise render a plain submit button. Load `resources/js/form-submission.js` via `@vite`.

`resources/views/public/forms/thanks.blade.php` — copy `resources/views/public/agreements/thanks.blade.php`, generalizing the copy to reference `$submission->template->name` instead of a hardcoded type.

- [ ] **Step 6: Register the JS entry**

Read `vite.config.js`, find how `resources/js/agreement-form.js` is listed in the `laravel({ input: [...] })` array, and add `resources/js/form-submission.js` the same way.

- [ ] **Step 7: Run tests and verify pass**

Run: `php artisan test --filter PublicFormOnDemandTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/PublicFormController.php resources/views/public/forms resources/js/form-submission.js routes/web.php vite.config.js tests/Feature/PublicFormOnDemandTest.php
git commit -m "feat: add public on-demand form submission flow"
```

---

### Task 9: Public standalone submission flow

**Files:**
- Create: `resources/views/public/forms/standalone.blade.php`
- Test: `tests/Feature/PublicFormStandaloneTest.php`

**Interfaces:**
- Consumes: `PublicFormController::showStandalone/storeStandalone` (Task 8, already implemented), `x-form-field-input` (Task 5).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicFormStandaloneTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_and_submit_a_standalone_form_creating_a_new_submission_each_time(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $group = Group::create(['name' => 'Retail Onboarding', 'is_active' => true]);
        $group->members()->attach($admin->id);

        $template = FormTemplate::create([
            'name' => 'Potential Retailer Sign Up',
            'mode' => FormTemplate::MODE_STANDALONE,
            'slug' => 'potential-retailer',
            'requires_signature' => false,
            'is_active' => true,
            'notify_group_id' => $group->id,
            'created_by' => $admin->id,
        ]);
        $nameField = $template->fields()->create(['label' => 'Full Name', 'field_type' => FormField::TYPE_TEXT, 'is_required' => true]);

        $this->get(route('public.forms.standalone.show', 'potential-retailer'))->assertOk();

        $response = $this->post(route('public.forms.standalone.store', 'potential-retailer'), [
            'values' => [$nameField->key => 'Katherine Castellanos'],
        ]);

        $this->assertEquals(1, FormSubmission::count());
        $submission = FormSubmission::firstOrFail();
        $response->assertRedirect(route('public.forms.thanks', $submission->uuid));
        $this->assertTrue($submission->isSubmitted());
        $this->assertNull($submission->expires_at);

        Notification::assertSentTo($admin, \App\Notifications\FormSubmittedNotification::class);
    }

    public function test_submitting_twice_creates_two_independent_submissions(): void
    {
        $admin = User::factory()->create();
        $template = FormTemplate::create([
            'name' => 'Sign Up', 'mode' => FormTemplate::MODE_STANDALONE, 'slug' => 'sign-up',
            'is_active' => true, 'created_by' => $admin->id,
        ]);
        $field = $template->fields()->create(['label' => 'Name', 'field_type' => FormField::TYPE_TEXT]);

        $this->post(route('public.forms.standalone.store', 'sign-up'), ['values' => [$field->key => 'A']]);
        $this->post(route('public.forms.standalone.store', 'sign-up'), ['values' => [$field->key => 'B']]);

        $this->assertEquals(2, FormSubmission::count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter PublicFormStandaloneTest`
Expected: FAIL — view `public.forms.standalone` not found (controller already exists from Task 8).

- [ ] **Step 3: Write the view**

`resources/views/public/forms/standalone.blade.php` — same page chrome as `on-demand.blade.php` (Task 8), but simpler: no "already submitted"/"expired" states (a standalone URL is always open), loop `$template->fields as $field` rendering `<x-form-field-input :field="$field" :value="null" :readonly="false" />` (every field is recipient-editable by construction — enforced in `FormTemplateManager::saveTemplate`, so no readonly branch is needed here), and the signature block only `@if ($template->requires_signature)`. Form posts to `route('public.forms.standalone.store', $template->slug)`. Load `resources/js/form-submission.js` via `@vite` (same file as Task 8 — no new JS needed).

- [ ] **Step 4: Run tests and verify pass**

Run: `php artisan test --filter PublicFormStandaloneTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/public/forms/standalone.blade.php tests/Feature/PublicFormStandaloneTest.php
git commit -m "feat: add public standalone form submission flow"
```

---

### Task 10: Regression check — COAM Equipment untouched

**Files:**
- Test: `tests/Feature/AgreementRegressionTest.php`

**Interfaces:**
- Consumes: `Agreement`, `AgreementController`, `PublicAgreementController` (all pre-existing, unmodified).

- [ ] **Step 1: Write the regression test**

```php
<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_coam_equipment_link_generation_and_public_signing_still_work(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)->post(route('admin.agreements.store'), [
            'type' => 'coam_equipment',
            'account_id' => 'ACC123',
            'form_date' => now()->toDateString(),
            'expires_in' => '7',
        ])->assertRedirect();

        $agreement = Agreement::firstOrFail();
        $this->assertEquals('coam_equipment', $agreement->type);

        $this->get(route('public.agreements.show', $agreement->uuid))->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it passes immediately (no production code change expected)**

Run: `php artisan test --filter AgreementRegressionTest`
Expected: PASS without touching any `Agreement`-related file. If it fails, something in Tasks 1–9 accidentally broke the `agreements` table/routes/policies — stop and fix the regression in the *new* code before proceeding (never patch the `Agreement` files themselves to work around it).

- [ ] **Step 3: Run the full suite**

Run: `php artisan test`
Expected: PASS — every test from Tasks 1–10 plus all pre-existing tests (Auth, Profile, Example) green.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/AgreementRegressionTest.php
git commit -m "test: add regression coverage confirming COAM Equipment is unaffected"
```

---

## Self-Review Notes

- **Spec coverage:** Sections 2–5 of the spec map to Tasks 1, 2, 3, 4, 6, 7, 8, 9 respectively; section 1's "COAM untouched" constraint is enforced by the Global Constraints block and verified in Task 10; section 6 (out of scope: `item_table`, COAM migration, Tickets integration, auto-resend) has no corresponding task, by design.
- **Route collision fix:** the spec originally said standalone forms live at `/f/{slug}`; this plan corrects that to `/p/{slug}` (and puts on-demand at `/formularios/{uuid}`, not `/f/{uuid}`) because `/f/{uuid}` is already claimed by `PublicAgreementController` and both are single-segment wildcards. The spec doc has been updated to match (see `docs/superpowers/specs/2026-09-16-form-templates-design.md`, section 5).
- **Type consistency check:** `FormField::isMultiValue()`, `FormTemplate::MODE_ON_DEMAND`/`MODE_STANDALONE`, `FormSubmission::STATUS_PENDING`/`STATUS_SUBMITTED`/`STATUS_EXPIRED`, and `FormNotifier::notifySubmitted()` are defined once (Tasks 1, 2, 6) and referenced identically by name in every later task.
