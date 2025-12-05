<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    // Fetch templates based on type (for dropdown)
    public function getTemplatesByType($type)
    {
        // Normalize both frontend and DB value to lowercase
        $normalizedType = strtolower(trim($type));

        // Optional: Replace spaces or underscores with hyphens (for safety)
        $normalizedType = str_replace([' ', '_'], '-', $normalizedType);



        // Query case-insensitively
        $templates = NotificationTemplate::
            select('id', 'title')
            ->get();


        if ($templates->isEmpty()) {
            return response()->json(['message' => 'No templates found for this type', 'type' => $normalizedType], 404);
        }

        return response()->json($templates);
    }


    // Fetch template message content
    public function getTemplateById($id)
    {
        $template = NotificationTemplate::find($id);

        if (!$template) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        return response()->json([
            'message_body' => $template->send_message ?? '',
        ]);
    }
}
