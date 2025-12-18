<?php

namespace Manuelballmer\EmailTemplates\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Manuelballmer\EmailTemplates\Database\Factories\EmailTemplateThemeFactory;

class EmailTemplateTheme extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'colours',
        'is_default',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'colours' => 'array',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $dates = ['deleted_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTableFromConfig();
    }

    /**
     * Tenant ownership relationship for Filament tenancy (defaults to 'team').
     *
     * The target model class and foreign key can be customized via the
     * filament-email-templates config:
     * - tenant_model (default: 'Team' => App\Models\Team)
     * - tenant_foreign_column_name (default: 'team_id')
     */
    public function team(): BelongsTo
    {
        $tenantModel = config('filament-email-templates.tenant_model', 'Team');
        if (! str_contains($tenantModel, '\\')) {
            $tenantModel = 'App\\Models\\' . ltrim($tenantModel, '\\');
        }

        $foreignKey = config('filament-email-templates.tenant_foreign_column_name', 'team_id');

        return $this->belongsTo($tenantModel, $foreignKey);
    }

    public function setTableFromConfig()
    {
        $this->table = config('filament-email-templates.theme_table_name');
    }

    protected static function newFactory()
    {
        return EmailTemplateThemeFactory::new();
    }
}
