<?php

namespace App\Filament\Admin\Resources\TemplateResource\Pages;

use App\Filament\Admin\Resources\TemplateResource;
use App\Models\NotificationTemplate;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    /**
     * Perform lightweight inline validation only for dynamic type-based rules.
     */
    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        // Always required
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:email,sms,whatsapp,in_app'],
            'send_message' => ['required', 'string'],
        ];

        // Conditional validation based on type
        match ($data['type']) {
            'email' => $rules['email_subject'] = ['required', 'string', 'max:255'],
            'whatsapp', 'sms' => $rules += [
                'url' => ['required', 'string'],
                'userid' => ['required', 'string'],
                'password' => ['required', 'string'],
                'msg_type' => ['required', 'string'],
            ],
            default => null,
        };

        validator($data, $rules)->validate();
    }

    /**
     * Post-create notification.
     */
    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Template Created')
            ->body('Your template has been successfully saved!')
            ->success()
            ->send();
    }
}
