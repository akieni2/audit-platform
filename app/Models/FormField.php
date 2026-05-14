<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormField extends Model
{
    use SoftDeletes;

    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_SELECT = 'select';

    public const TYPE_MULTISELECT = 'multiselect';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_RADIO = 'radio';

    public const TYPE_NUMBER = 'number';

    public const TYPE_DATE = 'date';

    public const TYPE_DATETIME = 'datetime';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_FILE = 'file';

    public const TYPE_RISK_SELECTOR = 'risk_selector';

    public const TYPE_USER_SELECTOR = 'user_selector';

    public const TYPE_DEPARTMENT_SELECTOR = 'department_selector';

    protected $fillable = [
        'form_template_id',
        'field_key',
        'label',
        'help_text',
        'field_type',
        'placeholder',
        'default_value',
        'configuration_json',
        'validation_rules_json',
        'conditional_rules_json',
        'sort_order',
        'is_required',
        'is_repeatable',
        'active',
        'source_field_id',
    ];

    protected function casts(): array
    {
        return [
            'configuration_json' => 'array',
            'validation_rules_json' => 'array',
            'conditional_rules_json' => 'array',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
            'is_repeatable' => 'boolean',
            'active' => 'boolean',
            'source_field_id' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function fieldTypes(): array
    {
        return array_keys(self::fieldTypeLabels());
    }

    /**
     * @return array<string, string>
     */
    public static function fieldTypeLabels(): array
    {
        return [
            self::TYPE_TEXT => 'Texte court',
            self::TYPE_TEXTAREA => 'Texte long',
            self::TYPE_SELECT => 'Liste',
            self::TYPE_MULTISELECT => 'Liste multiple',
            self::TYPE_CHECKBOX => 'Cases à cocher',
            self::TYPE_RADIO => 'Choix unique',
            self::TYPE_NUMBER => 'Nombre',
            self::TYPE_DATE => 'Date',
            self::TYPE_DATETIME => 'Date/heure',
            self::TYPE_BOOLEAN => 'Booléen',
            self::TYPE_FILE => 'Fichier',
            self::TYPE_RISK_SELECTOR => 'Sélecteur de risque',
            self::TYPE_USER_SELECTOR => 'Sélecteur utilisateur',
            self::TYPE_DEPARTMENT_SELECTOR => 'Sélecteur département',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class)->withTrashed();
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function sourceField(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_field_id')->withTrashed();
    }

    public function usesOptions(): bool
    {
        return in_array($this->field_type, [
            self::TYPE_SELECT,
            self::TYPE_MULTISELECT,
            self::TYPE_CHECKBOX,
            self::TYPE_RADIO,
        ], true);
    }

    public function isSelector(): bool
    {
        return in_array($this->field_type, [
            self::TYPE_RISK_SELECTOR,
            self::TYPE_USER_SELECTOR,
            self::TYPE_DEPARTMENT_SELECTOR,
        ], true);
    }

    public function resolvedDefaultValue(): mixed
    {
        $value = $this->getAttribute('default_value');

        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_bool($value) || is_numeric($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
