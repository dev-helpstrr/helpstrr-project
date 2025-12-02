<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TemplateResource\Pages;
use App\Models\NotificationTemplate;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\HtmlContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\File;

class TemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?string $navigationLabel = 'Templates';

    public static function form(Form $form): Form
    {
        // Load variables from JSON
        $jsonPath = public_path('data_helpstrr/template_variable_list.json');
        $variables = [];
        if (File::exists($jsonPath)) {
            $json = json_decode(File::get($jsonPath), true);
            foreach ($json as $groupName => $group) {
                foreach ($group as $subgroupName => $subgroup) {
                    foreach ($subgroup as $var) {
                        $variables[$groupName][$subgroupName][] = $var;
                    }
                }
            }
        }

        return $form->schema([
            Grid::make()->columns(2)->schema([
                TextInput::make('title')
                    ->label('Template Title')
                    ->required(),

                Radio::make('type')
                    ->label('Template Type')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                        'in_app' => 'In-App',
                    ])
                    ->inline()
                    ->reactive()
                    ->required(),
            ]),

            self::emailFields(),
            self::whatsappFields(),
            self::smsFields(),
            self::inAppFields(),

            // Template Body + Variable Picker
            Grid::make()->columns(2)->schema([
                RichEditor::make('send_message')
                    ->label('Template Body')
                    ->required()
                    ->extraAttributes(['id' => 'templateBody'])
                    ->columnSpan(1),

                ViewField::make('template_variables')
                    ->label('Template Variables')
                    ->columnSpan(1)
                    ->view('filament.forms.components.variable-picker', [
                        'variables' => $variables,
                    ]),
            ]),
        ]);
    }
    /* ---------------- EMAIL SECTION ---------------- */
    protected static function emailFields(): Forms\Components\Component
    {
        return Group::make([
            TextInput::make('email_subject')
                ->label('Email Subject')
                ->required(),

            RichEditor::make('send_message')
                ->label('Template Body')
                ->required()
                ->extraAttributes(['id' => 'templateBody']),
        ])->visible(fn(callable $get) => $get('type') === 'email');
    }


    /* ---------------- WHATSAPP SECTION ---------------- */
    protected static function whatsappFields(): Forms\Components\Component
    {
        return Group::make([
            Grid::make(12)->schema([
                Forms\Components\Checkbox::make('is_template')->label('Is Template?')->columnSpan(4),
                TextInput::make('message_type')->label('Message Type')->default('TEXT')->columnSpan(4),
                TextInput::make('footer')->default('This code expires in 10 minutes.')->label('Footer')->columnSpan(4),
            ]),
            Grid::make(12)->schema([
                TextInput::make('url')->label('API URL')->columnSpan(4),
                TextInput::make('userid')->label('User ID')->columnSpan(4),
                TextInput::make('password')->label('Password')->password()->columnSpan(4),
            ]),
            Grid::make(12)->schema([
                TextInput::make('v')->label('Version')->columnSpan(4),
                TextInput::make('format')->label('Format')->columnSpan(4),
                TextInput::make('msg_type')->label('Message Type')->columnSpan(4),
            ]),
        ])->visible(fn(callable $get) => $get('type') === 'whatsapp');
    }


    /* ---------------- SMS SECTION ---------------- */
    protected static function smsFields(): Forms\Components\Component
    {
        return Group::make([
            Grid::make(12)->schema([
                TextInput::make('url')->label('API URL')->columnSpan(4),
                TextInput::make('userid')->label('User ID')->columnSpan(4),
                TextInput::make('password')->label('Password')->password()->columnSpan(4),
            ]),
            Grid::make(12)->schema([
                TextInput::make('v')->label('Version')->columnSpan(4),
                TextInput::make('format')->label('Format')->columnSpan(4),
                TextInput::make('msg_type')->label('Message Type')->columnSpan(4),
            ]),
            Grid::make(12)->schema([
                Forms\Components\Checkbox::make('is_template')->label('Is Template?')->columnSpan(6),
                TextInput::make('footer')->label('Footer')->columnSpan(6),
            ]),
        ])->visible(fn(callable $get) => $get('type') === 'sms');
    }


    /* ---------------- IN-APP SECTION ---------------- */
    protected static function inAppFields(): Forms\Components\Component
    {
        return Group::make([
            Placeholder::make('in_app_info')->content('In-App templates only require the common Template Body.'),
        ])->visible(fn(callable $get) => $get('type') === 'in_app');
    }

    /* ---------------- TABLE ---------------- */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Template Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'email' => 'success',
                        'sms' => 'warning',
                        'whatsapp' => 'info',
                        'in_app' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('email_subject')
                    ->label('Subject')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y'),

                // ✅ Editable status toggle
                Tables\Columns\ToggleColumn::make('template_status')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->beforeStateUpdated(function ($record, $state) {
                        // Convert boolean to enum value before saving
                        $record->template_status = $state ? 'active' : 'inactive';
                        $record->save();
                        return false; // Prevent Filament from auto-saving the boolean
                    })
                    ->getStateUsing(fn($record) => $record->template_status === 'active')

                    ->afterStateUpdated(
                        fn($record) =>
                        \Filament\Notifications\Notification::make()
                            ->title('Template Status Updated')
                            ->body("Template '{$record->title}' is now " . strtoupper($record->template_status))
                            ->success()
                            ->send()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
