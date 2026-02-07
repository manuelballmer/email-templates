<?php

namespace Manuelballmer\EmailTemplates\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Manuelballmer\EmailTemplates\Database\Factories\EmailTemplateFactory;
use Manuelballmer\EmailTemplates\Facades\TokenHelper;


/**
 * @property int $id
 * @property string $key
 * @property array $from
 * @property string $name
 * @property string $view
 * @property array $cc
 * @property array $bcc
 * @property string $subject
 * @property string $title
 * @property string $preheader
 * @property string $language
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class EmailTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'from',
        'key',
        'name',
        'view',
        'subject',
        'title',
        'preheader',
        'content',
        'language',
        'logo',
        'cc',
        'bcc'

    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'deleted_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'from' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
    ];
    /**
     * @var string[]
     */
    protected $dates = ['deleted_at'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['theme'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTableFromConfig();
        // Include the theme foreign key as a fillable attribute
        $this->fillable[] = config('filament-email-templates.theme_table_name') . '_id';
        // Include the tenant foreign key as a fillable attribute
        $this->fillable[] = config('filament-email-templates.tenant_foreign_column_name');
    }

    /**
     * Remove temporary logo fields before mass assignment.
     */
    public function fill(array $attributes)
    {
        if (isset($attributes['logo_url'])) {
            if (($attributes['logo_type'] ?? null) === 'paste_url' && $attributes['logo_url']) {
                $attributes['logo'] = $attributes['logo_url'];
            }
            unset($attributes['logo_url'], $attributes['logo_type']);
        }

        return parent::fill($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        // When an email template is updated
        static::updated(function ($template) {
            self::clearEmailTemplateCache($template->key, $template->language, $template->team_id ?? null);
        });

        // When an email template is deleted
        static::deleted(function ($template) {
            self::clearEmailTemplateCache($template->key, $template->language, $template->team_id ?? null);
        });
    }

    public function setTableFromConfig()
    {
        $this->table = config('filament-email-templates.table_name');
    }

    public static function findEmailByKey($key, $language = null, $teamId = null)
    {
        $cacheKey = "email_by_key_{$key}_{$language}";

        if ($teamId) {
            $cacheKey .= "_{$teamId}";
        }

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($key, $language, $teamId) {
            $query = self::query()
                ->language($language ?? config('filament-email-templates.default_locale'))
                ->where("key", $key);

            if ($teamId) {
                $query->where('team_id', $teamId);
            }

            return $query->firstOrFail();
        });
    }

    public static function clearEmailTemplateCache($key, $language, $teamId = null)
    {
        $cacheKey = "email_by_key_{$key}_{$language}";
        Cache::forget($cacheKey);

        // Also clear team-specific cache if applicable
        if ($teamId) {
            Cache::forget("{$cacheKey}_{$teamId}");
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function getSendToSelectOptions()
    {
        return collect(config('emailTemplate.recipients'));
    }

    /**
     * @return EmailTemplateFactory
     */
    protected static function newFactory()
    {
        return EmailTemplateFactory::new();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name ?? class_basename($this);
    }

    /**
     * Get the assigned theme or the default
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function theme()
    {
        return $this->belongsTo(EmailTemplateTheme::class, config('filament-email-templates.theme_table_name') . '_id')->withDefault(function ($model) {
            return EmailTemplateTheme::where('is_default', true)->first();
        });
    }

    /**
     * Get the team that owns this email template
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

    /**
     * Gets base64 encoded content - to add to an iframe
     *
     * @return string
     */
    public function getBase64EmailPreviewData()
    {
        /**
         * Iframes normally use src attribute to load content from a url
         * This means an extra http request
         *  Below method includes the content directly as base64 encoded
         */

        $data = $this->getEmailPreviewData();
        $content = view($this->view_path, ['data' => $data])->render();

        return base64_encode($content);
    }

    /**
     * @return array
     */
    public function getEmailPreviewData()
    {
        $models = self::createEmailPreviewData();

        return [
            'user' => $models->user,
            'content' => TokenHelper::replace($this->content ?? '', $models),
            'subject' => TokenHelper::replace($this->subject ?? '', $models),
            'preHeaderText' => TokenHelper::replace($this->preheader ?? '', $models),
            'title' => TokenHelper::replace($this->title ?? '', $models),
            'theme' => $this->theme->colours,
            'logo' => $this->logo,
        ];
    }

    /**
     * @return object
     */
    public static function createEmailPreviewData()
    {
        $models = (object)[];

        $userModel = config('filament-email-templates.recipients')[0];
        //Setup some data for previewing email template
        $models->user = $userModel::first();
        $models->tokenUrl = URL::to('/');
        $models->verificationUrl = URL::to('/');
        $models->expiresAt = now();
        /* Not used in preview but need to add something */
        $models->plainText = Str::random(32);

        return $models;
    }

    /**
     * Efficient method to return requested template locale or default language template in one query
     *
     * @param Builder $query
     * @param $language
     *
     * @return Builder
     */
    public function scopeLanguage(Builder $query, $language)
    {
        $languages = [$language, config('filament-email-templates.default_locale')];

        return $query->whereIn('language', $languages)
            ->orderByRaw(
                "(CASE WHEN language = ? THEN 1 ELSE 2 END)",
                [$language]
            );
    }

    /**
     * @return Attribute
     */
    public function viewPath(): Attribute
    {
        return new Attribute(
            get: fn() => config('filament-email-templates.template_view_path') . '.' . $this->view
        );
    }

    /**
     * @return bool
     */
    public function getMailableExistsAttribute(): bool
    {
        $className = Str::studly($this->key);
        $filePath = app_path(config('filament-email-templates.mailable_directory') . "/{$className}.php");

        return File::exists($filePath);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMailableClass()
    {
        $className = Str::studly($this->key);
        $directory = str_replace('/', '\\', config('filament-email-templates.mailable_directory', 'Mail/Manuelballmer/EmailTemplates'));
        $fullClassName = "App\\" . rtrim($directory, '\\') . "\\{$className}";

        if (!class_exists($fullClassName)) {
            throw new \Exception("Mailable class {$fullClassName} does not exist.");
        }

        return $fullClassName;
    }


    public function getLogoAttribute(): string
    {
        //Get Database logo or config logo
        $logo = $this->attributes['logo'] ?? config('filament-email-templates.logo');

        // Return the logo if it's a full URL, otherwise, return the asset URL.
        return Str::isUrl($logo) ? $logo : asset($logo);
    }

}
