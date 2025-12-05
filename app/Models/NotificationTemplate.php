<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $table = 'notification_templates';

    protected $fillable = [
        'title',
        'type',
        'email_subject',
        'is_template',
        'template_status',
        'message_type',
        'send_message',
        'url',
        'userid',
        'password',
        'msg_type',
        'footer',
        'v',
        'format',
    ];

    /**
     * Handle validation + saving a new template record.
     */
    public static function saveTemplate(array $data): self
    {
        // Common validation rules
        $rules = [
            'title' => 'required|string|max:255',
            'type'  => 'required|in:email,sms,whatsapp,in_app',
        ];

        // Type-based validation
        switch ($data['type'] ?? null) {
            case 'email':
                $rules = array_merge($rules, [
                    'email_subject' => 'required|string|max:255',
                    'send_message' => 'required|string',
                ]);
                break;

            case 'whatsapp':
            case 'sms':
                $rules = array_merge($rules, [
                    'url' => 'required|string',
                    'userid' => 'required|string',
                    'password' => 'required|string',
                    'msg_type' => 'required|string',
                    'footer' => 'nullable|string',
                    'send_message' => 'required|string',
                ]);
                break;

            case 'in_app':
                $rules = array_merge($rules, [
                    'send_message' => 'required|string',
                ]);
                break;
        }

        // Validate data
        $validated = Validator::make($data, $rules)->validate();

        // Create template using only fillable fields
        return self::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'email_subject' => $validated['email_subject'] ?? null,
            'is_template' => $data['is_template'] ?? false,
            'template_status' => $data['template_status'] ?? 'active',
            'message_type' => $data['message_type'] ?? 'TEXT',
            'send_message' => $validated['send_message'],
            'url' => $data['url'] ?? null,
            'userid' => $data['userid'] ?? null,
            'password' => $data['password'] ?? null,
            'msg_type' => $data['msg_type'] ?? null,
            'footer' => $data['footer'] ?? null,
            'v' => $data['v'] ?? '1.1',
            'format' => $data['format'] ?? 'json',
        ]);
    }
}
