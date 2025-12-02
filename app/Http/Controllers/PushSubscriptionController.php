<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();

        // ✅ validate payload
        if (
            empty($data['endpoint']) ||
            empty($data['keys']['p256dh']) ||
            empty($data['keys']['auth'])
        ) {
            return response()->json(['error' => 'Invalid subscription payload'], 400);
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
                'subscribable_id' => null,
                'subscribable_type' => null,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function send()
    {
        $webpush = new WebPush([
            "VAPID" => [
                "subject" => env('VAPID_SUBJECT'),
                "publicKey" => env('VAPID_PUBLIC_KEY'),
                "privateKey" => env('VAPID_PRIVATE_KEY'),
            ]
        ]);

        $subscriptionData = [
            "endpoint" => env('SUBSCRIPTION_ENDPOINT'),
            "keys" => [
                "p256dh" => env('SUBSCRIPTION_P256DH'),
                "auth" => env('SUBSCRIPTION_AUTH'),
            ],
            "contentEncoding" => env('SUBSCRIPTION_ENCODING', 'aesgcm'),
        ];

        $subscription = Subscription::create($subscriptionData);

        $payload = json_encode([
            'title' => 'hi',
            'body' => 'check out',
            'url' => env('NOTIFY_URL'),
        ]);

        $response = $webpush->sendOneNotification($subscription, $payload);

        return $response; // optional
    }
}
