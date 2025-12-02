<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationHelper
{
    /**
     * Main entry point — Send notification based on type
     */
    public static function sendNotification($type, array $data = [])
    {
        try {
            switch ($type) {
                case 'in-app':
                    return self::sendInAppNotification($data);

                case 'email':
                    return self::sendEmailNotification(
                        $data['to'] ?? null,
                        $data['subject'] ?? null,
                        $data['bodyData'] ?? null,
                        $data['cc'] ?? [],
                        $data['bcc'] ?? [],
                        $data['attachments'] ?? []
                    );

                case 'whatsapp':
                    return self::sendWhatsappNotification(
                        $data['phone'] ?? null,
                        $data['otp'] ?? null
                    );

                case 'sms':
                    return self::sendSMSNotification($data);

                default:
                    throw new Exception("Invalid notification type: {$type}");
            }
        } catch (Exception $e) {
            Log::error("Notification Error ({$type}): " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    // ======================================================
    // ================ IN-APP NOTIFICATION =================
    // ======================================================

    protected static function sendInAppNotification(array $data)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $url = str_replace('{project}', $projectId, env('FIREBASE_FCM_SEND_URL'));

            $key_data = ['key1' => 'value1', 'key2' => 'value2'];

            $accessToken = self::generateAccessToken();

            //   dd($accessToken);
            if (!$accessToken) {
                throw new Exception('Access token not generated.');
            }

            $payload = [
                'message' => [
                    'token' => $data['to'] ?? '',
                    'notification' => [
                        'title' => 'Helpstrr',
                        'body' =>  'Testing',
                    ],
                    'data' =>  $key_data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'category' => 'NEW_MESSAGE',
                            ],
                        ],
                    ],
                ],
            ];

            //dd($payload);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($url, $payload);



            if ($response->failed()) {
                Log::error('❌ FCM Error Response', $response->json());
                return [
                    'status' => 'error',
                    'message' => 'FCM request failed',
                    'details' => $response->json(),
                    'code' => $response->status(),
                ];
            }

            Log::info('✅ In-App Notification Sent Successfully', $response->json());
            return [
                'status' => 'success',
                'message' => 'Notification sent successfully via FCM',
                'fcm_response' => $response->json(),
            ];
        } catch (Exception $e) {
            dd($e->getMessage());
            Log::error('⚠️ In-App Notification Error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }


    /**
     * Generate Firebase Access Token dynamically from .env
     */
    protected static function generateAccessToken()
    {
        try {
            $serviceAccountPath = base_path(config('services.firebase.service_account_path'));

            if (!file_exists($serviceAccountPath)) {
                throw new Exception("Firebase service account not found: {$serviceAccountPath}");
            }

            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            if (!$serviceAccount || empty($serviceAccount['private_key']) || empty($serviceAccount['client_email'])) {
                throw new Exception("Invalid or missing fields in Firebase service account file.");
            }



            $header = self::base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now = time();
            $claims = self::base64url_encode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => env('FIREBASE_SCOPE_URL'),
                'aud' => $serviceAccount['token_uri'],
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $input = $header . '.' . $claims;
            if (!openssl_sign($input, $signature, $serviceAccount['private_key'], 'SHA256')) {
                Log::error('OpenSSL failed to sign JWT. Check private key format.');
                return null;
            }
            $jwt =  $input . '.' . self::base64url_encode($signature);
            $response = Http::asForm()->post(
                env('FIREBASE_AUTH_URL'),
                [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]
            );



            if (!$response->successful()) {
                dd('fail');
                throw new Exception('Failed to generate Firebase access token: ' . $response->body());
            }

            return $response->json()['access_token'] ?? null;
        } catch (Exception $e) {
            Log::error('Access Token Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected static function formatPrivateKey($key)
    {
        return "-----BEGIN PRIVATE KEY-----\n"
            . chunk_split(str_replace(["\r", "\n"], '', $key), 64, "\n")
            . "-----END PRIVATE KEY-----\n";
    }

    // ======================================================
    // ================ EMAIL NOTIFICATION ==================
    // ======================================================

    public static function sendEmailNotification($to, $subject, $bodyData, $cc = [], $bcc = [], $attachments = [])
    {
        $to = array_filter($to, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        if (empty($to)) {
            return ['status' => 'error', 'message' => 'No valid "to" emails provided.', 'failedEmails' => []];
        }

        $cc = array_filter($cc, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $bcc = array_filter($bcc, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $attachments = is_array($attachments) ? $attachments : [];

        $failedEmails = [];

        foreach ($to as $email) {
            try {
                $personalizedBody = $bodyData[$email] ?? 'Default Email Body';

                Mail::send('Email.MailTemplate', ['bodyData' => $personalizedBody], function ($message) use ($email, $cc, $bcc, $subject, $attachments) {
                    $message->to($email);
                    if (!empty($cc)) $message->cc($cc);
                    if (!empty($bcc)) $message->bcc($bcc);
                    $message->subject($subject);

                    if (!empty($attachments)) {
                        foreach ($attachments as $file) {
                            $message->attach($file->getRealPath(), [
                                'as' => $file->getClientOriginalName(),
                                'mime' => $file->getMimeType(),
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                //dd("Error sending email to $email: " . $e->getMessage());
                $failedEmails[] = $email;
            }
        }

        return [
            'status' => empty($failedEmails) ? 'success' : 'partial',
            'failedEmails' => $failedEmails
        ];
    }


    // ======================================================
    // ================ WHATSAPP NOTIFICATION ===============
    // ======================================================

    public static function sendWhatsappNotification($phone, $otp)
    {
        $url = config('whatsapp.url');

        $message = "{$otp} is your verification code. For your security, do not share this code.";

        $payload = [
            'userid'     => config('whatsapp.userid'),
            'password'   => config('whatsapp.password'),
            'v'          => config('whatsapp.v'),
            'format'     => config('whatsapp.format'),
            'msg_type'   => config('whatsapp.msg_type'),
            'method'     => config('whatsapp.method'),
            'send_to'    => $phone,
            'msg'        => $message,
            'isTemplate' => config('whatsapp.isTemplate'),
            'footer'     => config('whatsapp.footer'),
        ];

        // ✅ Send as x-www-form-urlencoded (not JSON)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload)); // URL encode
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // ✅ Debug and handle
        if ($error) {
            dd("cURL Error: " . $error);
        }

        if ($httpCode !== 200) {
            dd("Failed to send OTP: HTTP " . $httpCode);
        }

        $body = json_decode($responseBody, true);

        if (!isset($body['response'])) {
            dd("Invalid response: " . $responseBody);
        }

        $resp = $body['response'];

        if (isset($resp['status']) && strtolower($resp['status']) === 'error') {
            $details = $resp['details'] ?? 'Unknown error';
            dd("OTP not sent: {$details}");
        }

        if (isset($resp['status']) && strtolower($resp['status']) === 'success') {
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'details' => $resp,
            ];
        }

        dd("Unexpected response: " . json_encode($body));
    }




    public static function postJson($url, $payload)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        curl_close($ch);

        return [
            'httpCode' => $httpCode,
            'error' => $err,
            'body' => $response
        ];
    }

    // ======================================================
    // ================= SMS NOTIFICATION ===================
    // ======================================================

    protected static function sendSMSNotification($data)
    {
        try {
            $url = env('SMS_API_URL');
            $response = Http::post($url, $data);

            Log::info('SMS notification sent', ['response' => $response->json()]);
            return $response->json();
        } catch (Exception $e) {
            Log::error('SMS Notification Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
