<?php

namespace Manuelballmer\EmailTemplates\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Manuelballmer\EmailTemplates\Models\EmailTemplate;

class EmailTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
                'key' => Str::random(20),
                'language' => config('filament-email-templates.default_locale'),
                'view' => config('filament-email-templates.default_view'),
                'cc' => null,
                'bcc' => null,
                'from' => ['email' => $this->faker->email,'name' => $this->faker->name],
                'name' => $this->faker->name,
                'preheader' => $this->faker->sentence,
                'subject' => $this->faker->sentence,
                'title' => $this->faker->sentence,
                'content' => new HtmlString("<p>".$this->faker->text."</p>"),
                'logo' => config('filament-email-templates.logo'),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
        ];
    }
}
