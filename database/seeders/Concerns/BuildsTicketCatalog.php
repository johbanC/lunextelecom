<?php

namespace Database\Seeders\Concerns;

use App\Models\Category;
use App\Models\FieldDefinition;
use App\Models\Group;
use App\Models\Issue;
use App\Models\TicketType;
use Illuminate\Support\Str;

/**
 * Helpers compartidos por los seeders de catálogo (Retailer y Customer),
 * fuente de verdad: docs/SPEC_DESARROLLO.md secciones 4 y 5.
 */
trait BuildsTicketCatalog
{
    /**
     * @param  array<int, array{0: string, 1: array<int, array<string, mixed>>}>  $issues  Lista de [nombre_issue, campos]
     */
    protected function seedCategory(TicketType $type, string $name, array $issues, ?string $defaultGroupName = null): Category
    {
        $category = Category::create([
            'ticket_type_id' => $type->id,
            'name' => $name,
            'default_related_to_group_id' => $defaultGroupName
                ? Group::where('name', $defaultGroupName)->value('id')
                : null,
            'sort_order' => Category::where('ticket_type_id', $type->id)->count(),
        ]);

        foreach (array_values($issues) as $sort => [$issueName, $fields]) {
            $this->seedIssue($category, $issueName, $fields, $sort);
        }

        return $category;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function seedIssue(Category $category, string $name, array $fields, int $sort = 0): Issue
    {
        $issue = Issue::create([
            'category_id' => $category->id,
            'name' => $name,
            'sort_order' => $sort,
        ]);

        $usedKeys = [];

        foreach (array_values($fields) as $fsort => $field) {
            $key = Str::slug($field['label'], '_');
            $unique = $key;
            for ($i = 2; in_array($unique, $usedKeys, true); $i++) {
                $unique = $key.'_'.$i;
            }
            $usedKeys[] = $unique;

            $definition = FieldDefinition::create([
                'issue_id' => $issue->id,
                'label' => $field['label'],
                'key' => $unique,
                'field_type' => $field['type'] ?? FieldDefinition::TYPE_TEXT,
                'is_required' => $field['required'] ?? false,
                'help_text' => $field['help'] ?? null,
                'pick_count' => $field['pick'] ?? null,
                'sort_order' => $fsort,
            ]);

            foreach ($field['options'] ?? [] as $osort => $option) {
                $definition->options()->create(['value' => $option, 'sort_order' => $osort]);
            }
        }

        return $issue;
    }

    // --- Constructores de campo ---

    protected function text(string $label, bool $required = false): array
    {
        return ['label' => $label, 'type' => FieldDefinition::TYPE_TEXT, 'required' => $required];
    }

    protected function textarea(string $label, bool $required = false): array
    {
        return ['label' => $label, 'type' => FieldDefinition::TYPE_TEXTAREA, 'required' => $required];
    }

    protected function date(string $label, bool $required = false): array
    {
        return ['label' => $label, 'type' => FieldDefinition::TYPE_DATE, 'required' => $required];
    }

    protected function select(string $label, array $options, bool $required = false): array
    {
        return ['label' => $label, 'type' => FieldDefinition::TYPE_SELECT, 'options' => $options, 'required' => $required];
    }

    protected function radio(string $label, array $options, bool $required = false): array
    {
        return ['label' => $label, 'type' => FieldDefinition::TYPE_RADIO, 'options' => $options, 'required' => $required];
    }

    protected function checkbox(string $label, array $options, bool $required = false): array
    {
        return ['label' => $label, 'type' => FieldDefinition::TYPE_CHECKBOX, 'options' => $options, 'required' => $required];
    }

    protected function pickN(string $label, int $n, array $options, bool $required = false): array
    {
        return ['label' => $label, 'type' => FieldDefinition::TYPE_PICK_N, 'pick' => $n, 'options' => $options, 'required' => $required];
    }

    // --- Bloques reusables descritos en la spec (sección 2, "patrón recurrente") ---

    protected function callerName(): array
    {
        return $this->text('Caller Name');
    }

    protected function callerNumber(): array
    {
        return $this->text('Caller #');
    }

    protected function methodOfVerificationPick2(): array
    {
        return $this->pickN('Method of Verification', 2, [
            'User ID', 'Caller Name', 'Phone Number', 'Address', 'Last 4 digit of Bank Account',
        ]);
    }

    protected function retailerLogin(): array
    {
        return $this->text('Retailer Login');
    }

    protected function infoProvidedToCaller(): array
    {
        return $this->textarea('Info provided to the caller');
    }

    protected function notes(): array
    {
        return $this->textarea('Notes');
    }

    /**
     * Bloque "Follow up": checkbox de seguimiento. La spec pide NO duplicar
     * el campo Notes cuando ya existe uno base en el issue (ver sección 2).
     */
    protected function followUp(): array
    {
        return $this->checkbox('Follow up', [
            'CSR Please call customer when received a resolution',
            '1st try - Called customer no answer',
            '2nd try - Called customer no answer',
            '3rd try - Called customer no answer',
            'Customer was informed',
            'Other',
        ]);
    }

    /**
     * Campo "Retailer" de texto libre que casi todos los issues de Customer
     * agregan al inicio del bloque dinámico (sección 5).
     */
    protected function retailerFreeText(): array
    {
        return $this->text('Retailer');
    }

    protected function transactionBaseFields(): array
    {
        return [
            $this->text('Transaction ID'),
            $this->text('Transaction Status'),
            $this->text('Transaction Date/Time'),
        ];
    }
}
