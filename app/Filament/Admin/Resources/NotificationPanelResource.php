<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\NotificationPanel;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use App\Filament\Admin\Resources\NotificationPanelResource\Pages;

class NotificationPanelResource extends Resource
{
    protected static ?string $model = NotificationPanel::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notification Panel';
    protected static ?string $navigationGroup = 'Notifications';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make()
                ->columns(2)
                ->schema([
                    // ---------------- Left: User Selection ----------------
                    Grid::make()
                        ->schema([
                            Checkbox::make('select_all_users')
                                ->label('Select All Users')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $set('user_ids', User::pluck('id')->toArray());
                                    } else {
                                        $set('user_ids', []);
                                    }
                                }),

                            CheckboxList::make('user_ids')
                                ->label('Select Users')
                                ->options(User::pluck('name', 'id'))
                                ->columns(1)
                                ->required(),
                        ]),

                    // ---------------- Right: Notification Type + Template ----------------
                    Grid::make()
                        ->schema([
                            Select::make('type')
                                ->label('Notification Type')
                                ->options([
                                    'email' => 'Email',
                                    'whatsapp' => 'WhatsApp',
                                    'sms' => 'SMS',
                                    'in_app' => 'In-App',
                                ])
                                ->reactive()
                                ->required()
                                // CORRECT signature: ($state, $set). Clear template_id & message when type changes.
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('template_id', null);
                                    $set('send_message', '');
                                }),

                            Select::make('template_id')
                                ->label('Select Template')
                                ->visible(fn($get) => filled($get('type')))
                                ->reactive()
                                ->placeholder('Select a template for the chosen type')
                                ->options(function (callable $get) {
                                    $type = $get('type');
                                    if (!$type) {
                                        return [];
                                    }

                                    return NotificationTemplate::where('type', $type)
                                        ->pluck('title', 'id')
                                        ->toArray();
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $template = NotificationTemplate::find($state);
                                        if ($template) {
                                            $set('send_message', $template->send_message);
                                        }
                                    }
                                }),

                            Textarea::make('send_message')
                                ->label('Message Body')
                                ->rows(8)
                                ->disabled(fn($get) => $get('type') !== 'in_app')
                                ->dehydrated(false),
                        ]),
                ]),
        ]);
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationPanels::route('/'),
            'create' => Pages\CreateNotificationPanel::route('/create'),
            'edit' => Pages\EditNotificationPanel::route('/{record}/edit'),
        ];
    }
}
