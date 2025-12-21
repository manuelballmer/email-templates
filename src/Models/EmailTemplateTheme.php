<?php

namespace Manuelballmer\EmailTemplates\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        // Include the tenant foreign key as a fillable attribute
        $this->fillable[] = config('filament-email-templates.tenant_foreign_column_name');
    }

    public function setTableFromConfig()
    {
        $this->table = config('filament-email-templates.theme_table_name');
    }

    protected static function newFactory()
    {
        return EmailTemplateThemeFactory::new();
    }

    /**
     * Get the team that owns this email template theme
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        $tenantModel = config('filament-email-templates.tenant_model');
        $tenantColumnName = config('filament-email-templates.tenant_foreign_column_name');
        
        // Try to resolve the model class dynamically
        // First try App\Models\{Model}, then just {Model}
        $modelClass = null;
        if (class_exists("App\\Models\\{$tenantModel}")) {
            $modelClass = "App\\Models\\{$tenantModel}";
        } elseif (class_exists($tenantModel)) {
            $modelClass = $tenantModel;
        }
        
        if ($modelClass) {
            return $this->belongsTo($modelClass, $tenantColumnName);
        }
        
        // Fallback: return a relation that will fail gracefully if model doesn't exist
        return $this->belongsTo(\Illuminate\Database\Eloquent\Model::class, $tenantColumnName);
    }
}
