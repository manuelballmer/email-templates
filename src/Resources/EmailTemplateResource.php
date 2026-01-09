<?php

namespace Manuelballmer\EmailTemplates\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Illuminate\View\View;
use Manuelballmer\EmailTemplates\Contracts\CreateMailableInterface;
use Manuelballmer\EmailTemplates\Contracts\FormHelperInterface;
use Manuelballmer\EmailTemplates\EmailTemplatesPlugin;
use Manuelballmer\EmailTemplates\Models\EmailTemplate;
use Manuelballmer\EmailTemplates\Resources\EmailTemplateResource\Pages;
use Visualbuilder\FilamentTinyEditor\TinyEditor;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    public static function shouldRegisterNavigation(): bool
    {
        return EmailTemplatesPlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): ?string
    {
        return config('filament-email-templates.navigation.templates.icon');
    }

    public static function getNavigationGroup(): ?string
    {
        return EmailTemplatesPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-email-templates.navigation.templates.sort');
    }

    public static function getModelLabel(): string
    {
        return __(config('filament-email-templates.navigation.templates.label'));
    }

    public static function getPluralModelLabel(): string
    {
        return __(config('filament-email-templates.navigation.templates.label'));
    }

    public static function getCluster(): string
    {
        return config('filament-email-templates.navigation.templates.cluster');
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return config('filament-email-templates.navigation.templates.position');
    }

    public static function table(Table $table): Table
    {
        return $table
                ->query(EmailTemplate::query())
                ->columns(
                        [
                                Stack::make([
                                        ViewColumn::make('preview')
                                                ->view('vb-email-templates::tables.columns.preview-thumbnail'),

                                        TextColumn::make('name')
                                                ->weight(FontWeight::Bold)
                                                ->size(TextSize::Large)
                                                ->searchable()
                                                ->sortable(),

                                        TextColumn::make('subject')
                                                ->color('gray')
                                                ->size(TextSize::Small)
                                                ->searchable()
                                                ->limit(50),

                                        TextColumn::make('language')
                                                ->badge()
                                                ->color('info'),
                                ])->space(2),
                        ]
                )
                ->contentGrid([
                        'md' => 2,
                        'xl' => 3,
                ])
                ->recordUrl(fn (EmailTemplate $record): string => static::getUrl('edit', ['record' => $record]))
                ->filters(
                        [
                                Tables\Filters\TrashedFilter::make(),
                        ]
                )
                ->actions(
                        [
                                ViewAction::make()
                                        ->label(__('vb-email-templates::email-templates.actions.preview'))
                                        ->iconButton()
                                        ->icon('heroicon-o-eye')
                                        ->modalContent(fn(EmailTemplate $record): View => view(
                                                'vb-email-templates::forms.components.iframe',
                                                ['record' => $record],
                                        ))
                                        ->modalHeading(fn(EmailTemplate $record): string => __('vb-email-templates::email-templates.actions.preview').': '.$record->name)
                                        ->modalSubmitAction(false)
                                        ->modalCancelAction(false)
                                        ->slideOver(),

                                EditAction::make()
                                        ->iconButton(),

                                DeleteAction::make()
                                        ->iconButton(),

                                Action::make('create-mail-class')
                                        ->iconButton()
                                        ->icon('heroicon-o-code-bracket')
                                        ->tooltip('Mailable-Klasse erstellen')
                                        ->visible(fn (EmailTemplate $record) => !$record->mailable_exists)
                                        ->action(function (EmailTemplate $record) {
                                            $notify = app(CreateMailableInterface::class)->createMailable($record);
                                            Notification::make()
                                                    ->title($notify->title)
                                                    ->icon($notify->icon)
                                                    ->iconColor($notify->icon_color)
                                                    ->duration(10000)
                                                    ->body("<span style='overflow-wrap: anywhere;'>".$notify->body."</span>")
                                                    ->send();
                                        }),

                                RestoreAction::make()
                                        ->iconButton(),
                        ]
                )
                ->bulkActions(
                        [
                                DeleteBulkAction::make(),
                                ForceDeleteBulkAction::make(),
                                RestoreBulkAction::make(),
                        ]
                );
    }

    public static function form(Schema $schema): Schema
    {
        $formHelper = app(FormHelperInterface::class);
        $templates = $formHelper->getTemplateViewOptions();

        return $schema->schema(
                [
                        Section::make()
                                ->columnSpanFull()
                                ->schema(
                                        [
                                                Grid::make(['default' => 1])
                                                        ->schema(
                                                                [
                                                                        TextInput::make('name')
                                                                                ->live()
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.template-name'))
                                                                                ->hint(__('vb-email-templates::email-templates.form-fields-labels.template-name-hint'))
                                                                                ->required(),
                                                                ]
                                                        ),

                                                Grid::make(['default' => 1, 'sm' => 1, 'md' => 2])
                                                        ->schema(
                                                                [
                                                                        TextInput::make('key')
                                                                                ->afterStateUpdated(
                                                                                        fn(Set $set, ?string $state) => $set('key', Str::slug($state))
                                                                                )
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.key'))
                                                                                ->hint(__('vb-email-templates::email-templates.form-fields-labels.key-hint'))
                                                                                ->required()
                                                                                ->unique(table: EmailTemplate::class,
                                                                                        column: 'key',
                                                                                        ignoreRecord: true,
                                                                                        modifyRuleUsing: function (Unique $rule, $get) {
                                                                                            $tenantColumn = config('filament-email-templates.tenant_foreign_column_name', 'team_id');
                                                                                            $tenant = \Filament\Facades\Filament::getTenant();
                                                                                            
                                                                                            $rule->where('language', $get('language'));
                                                                                            
                                                                                            if ($tenant) {
                                                                                                $rule->where($tenantColumn, $tenant->getKey());
                                                                                            }
                                                                                            
                                                                                            return $rule;
                                                                                        })
                                                                                ->maxLength(191),
                                                                        Select::make('language')
                                                                                ->options($formHelper->getLanguageOptions())
                                                                                ->default(config('filament-email-templates.default_locale'))
                                                                                ->searchable()
                                                                                ->allowHtml(),
                                                                        TextInput::make('from.email')->default(config('mail.from.address'))
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.email-from'))
                                                                                ->email(),
                                                                        TextInput::make('from.name')->default(config('mail.from.name'))
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.email-from-name'))
                                                                                ->string()
                                                                                ->maxLength(191),

                                                                        Select::make('view')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.template-view'))
                                                                                ->options($templates)
                                                                                ->default(current($templates))
                                                                                ->searchable()
                                                                                ->required(),

                                                                        Select::make(config('filament-email-templates.theme_table_name').'_id')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.theme'))
                                                                                ->relationship(name: 'theme', titleAttribute: 'name')
                                                                                ->native(false)
                                                                ]
                                                        ),

                                                Grid::make(['default' => 1])
                                                        ->schema(
                                                                [
                                                                        TextInput::make('subject')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.subject'))
                                                                                ->maxLength(191),

                                                                        TextInput::make('preheader')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.header'))
                                                                                ->hint(__('vb-email-templates::email-templates.form-fields-labels.header-hint'))
                                                                                ->maxLength(191),

                                                                        TextInput::make('title')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.title'))
                                                                                ->hint(__('vb-email-templates::email-templates.form-fields-labels.title-hint'))
                                                                                ->maxLength(191),

                                                                        TinyEditor::make('content')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.content'))
                                                                                ->profile('default')
                                                                                ->default("<p>Dear ##user.first_name##, </p>"),

                                                                        Radio::make('logo_type')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.logo-type'))
                                                                                ->options([
                                                                                        'browse_another' => __('vb-email-templates::email-templates.form-fields-labels.browse-another'),
                                                                                        'paste_url'      => __('vb-email-templates::email-templates.form-fields-labels.paste-url'),
                                                                                ])
                                                                                ->default('browse_another')
                                                                                ->inline()
                                                                                ->live(),

                                                                        FileUpload::make('logo')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.logo'))
                                                                                ->hint(__('vb-email-templates::email-templates.form-fields-labels.logo-hint'))
                                                                                ->hidden(fn(Get $get) => $get('logo_type') !== 'browse_another')
                                                                                ->directory(config('filament-email-templates.browsed_logo'))
                                                                                ->image(),

                                                                        TextInput::make('logo_url')
                                                                                ->label(__('vb-email-templates::email-templates.form-fields-labels.logo-url'))
                                                                                ->hint(__('vb-email-templates::email-templates.form-fields-labels.logo-url-hint'))
                                                                                ->placeholder('https://www.example.com/media/test.png')
                                                                                ->hidden(fn(Get $get) => $get('logo_type') !== 'paste_url')
                                                                                ->activeUrl()
                                                                                ->maxLength(191),
                                                                ]
                                                        ),

                                        ]
                                ),
                ]
        );
    }

    public function handleLogoDelete($logo)
    {
        if ($logo) {
            $defaultLogoPath = config('filament-email-templates.logo');
            $parsedLogoPath = str_replace(asset('/'), storage_path('app/public/'), $logo);

            if (!str_contains($parsedLogoPath, $defaultLogoPath) && File::exists($parsedLogoPath)) {
                File::delete($parsedLogoPath);
            }
        }
    }

    public static function getPages(): array
    {
        return [
                'index'  => Pages\ListEmailTemplates::route('/'),
                'create' => Pages\CreateEmailTemplate::route('/create'),
                'edit'   => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
                ->withoutGlobalScopes(
                        [
                                SoftDeletingScope::class,
                        ]
                );
    }

    public function handleLogo(array $data): array
    {
        if ($data['logo_type'] == "paste_url" && $data['logo_url']) {
            $data['logo'] = $data['logo_url'];
        }
        unset($data['logo_type'], $data['logo_url']);
        return $data;
    }
}
