<?php

namespace App\Helpers;
use App\Models\SortkarJobRole;

class ZohoHelper
{
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $authUrl;
    private string $baseUrl;

    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;

    public function __construct()
    {

        // Load from environment (.env)
        $this->clientId     = config('services.zoho.client_id') ?? env('ZOHO_CLIENT_ID');
        $this->clientSecret = config('services.zoho.client_secret') ?? env('ZOHO_CLIENT_SECRET');
        $this->refreshToken = config('services.zoho.refresh_token') ?? env('ZOHO_REFRESH_TOKEN');
        $this->authUrl      = config('services.zoho.auth_url');
        $this->baseUrl      = config('services.zoho.base_url');

        // Refresh immediately on construct
        $this->refreshAccessToken();
    }

    /** Ensure we have a valid token */
    public function ensureToken(): void
    {
        if (!$this->accessToken || time() >= $this->accessTokenExpiresAt) {
            $this->refreshAccessToken();
        }
    }

    /** Refresh access token */
    private function refreshAccessToken(): void
    {

        $params = http_build_query([
            'refresh_token' => $this->refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->authUrl . '?' . $params,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('cURL error (token): ' . $err);
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($resp, true);
        if ($code >= 200 && $code < 300 && !empty($json['access_token'])) {
            $this->accessToken = $json['access_token'];
            $this->accessTokenExpiresAt = time() + 3300; // ~55 minutes
            return;
        }

        $msg = $json['error'] ?? 'Failed to refresh Zoho token';
        throw new \RuntimeException('Zoho token error: ' . $msg);
    }

    /** Make API call */
    private function callZoho(string $method, string $url, $payload = null, array $extraHeaders = [], bool $retryOn401 = true): array
    {

        $this->ensureToken();

        $headers = array_merge([
            'Accept: application/json',
            'Authorization: Zoho-oauthtoken ' . $this->accessToken,
        ], $extraHeaders);

        $ch = curl_init();
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        $method = strtoupper($method);
        if ($method === 'GET') {
            $opts[CURLOPT_HTTPGET] = true;
        } elseif ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
        } else {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
        }

        if ($payload !== null) {
            if (is_array($payload)) {
                $payload = json_encode($payload);
                if (!preg_grep('/^Content-Type:/i', $headers)) {
                    $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
                }
            }
            $opts[CURLOPT_POSTFIELDS] = $payload;
        }

        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);

        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('cURL error: ' . $err);
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 401 && $retryOn401) {
            $this->refreshAccessToken();
            return $this->callZoho($method, $url, $payload, $extraHeaders, false);
        }

        return [$code, json_decode($resp, true) ?: []];
    }

    /* ------------------ Public API Methods ------------------ */

    public function getSPDetails(string $mobile10): array
    {
        // Normalize mobile (only digits) and add +91 prefix
        $mobile = '+91' . preg_replace('/\D/', '', $mobile10);

        // Build criteria for Zoho API
        $criteria = sprintf('Mobile="%s"', $mobile);

        // Encode and attach to URL
        $query = http_build_query(['criteria' => $criteria]);
        $url = $this->baseUrl . '/report/All_SP_Register_Report?' . $query;

        // Call Zoho API
        [, $json] = $this->callZoho('GET', $url);

        return $json;
    }


    public function getRoleDetails()
    {
        return SortkarJobRole::whereIn('id', [1, 2, 27])->get();
    }


    public function insertSp(array $postData): array
    {
        $dobFormatted = '';
        if (!empty($postData['dob']) && ($ts = strtotime($postData['dob'])) !== false) {
            $dobFormatted = date("d-M-Y", $ts);
        }

        $interested = $postData['intrested_role'] ?? [];
        $prior      = $postData['prior_experience'] ?? [];

        // // If the values are JSON strings, decode them into arrays
        // if (is_string($interested) && str_starts_with($interested, '[')) {
        //     $interested = json_decode($interested, true) ?? [];
        // }
        // if (is_string($prior) && str_starts_with($prior, '[')) {
        //     $prior = json_decode($prior, true) ?? [];
        // }

        // // Optional: Clean IDs inside array (remove non-digit chars) but keep array structure
        // if (is_array($interested)) {
        //     $interested = array_map(fn($id) => preg_replace('/\D/', '', $id), $interested);
        // }
        // if (is_array($prior)) {
        //     $prior = array_map(fn($id) => preg_replace('/\D/', '', $id), $prior);
        // }

        $payload = [
            "data" => [
                "Name" => [
                    "first_name" => $postData['first_name'] ?? '',
                    "last_name"  => $postData['last_name'] ?? '',
                ],
                "Email" => $postData['email'] ?? '',
                "Mobile" => '+91' . preg_replace('/\D/', '', $postData['mobile1_number'] ?? ''),
                "Whatsapp_No" => '+91' . preg_replace('/\D/', '', $postData['whatsapp'] ?? ''),
                "Date_of_Birth" => $dobFormatted,
                "Gender" => $postData['gender'] ?? '',
                "Profile_Status" => $postData['profile_status'] ?? '',
                "Interested_Local_Job_Role" => $interested, // ✅ proper array
                "Prior_Experience_Job_Role" => $prior,      // ✅ proper array
                "Pincode" => $postData['pincode'] ?? '',
                "State" => $postData['state'] ?? '',
                "Country" => $postData['country'] ?? '',
                "City" => $postData['city'] ?? '',
            ],
            "skip_workflow" => ["all"],
        ];

        // dd($payload);


        $url = $this->baseUrl . '/form/SP_Local';
        [$code, $json] = $this->callZoho('POST', $url, $payload, ['Content-Type: application/json']);

        return $code >= 200 && $code < 300
            ? ['success' => true, 'response' => $json]
            : ['success' => false, 'status' => $code, 'response' => $json];
    }

    public function updateSp(string $recordId, array $postData): array
    {

        $dobFormatted = '';
        if (!empty($postData['dob']) && ($ts = strtotime($postData['dob'])) !== false) {
            $dobFormatted = date("d-M-Y", $ts);
        }

        $interested = $postData['intrested_role'] ?? [];
        $prior      = $postData['prior_experience'] ?? [];


        $payload = [
            "data" => [
                "Name" => [
                    "first_name" => $postData['first_name'] ?? '',
                    "last_name"  => $postData['last_name'] ?? '',
                ],
                "Email"         => $postData['email'] ?? '',
                "Mobile"        => '+91' . preg_replace('/\D/', '', $postData['mobile1_number'] ?? ''),
                "Whatsapp_No"   => '+91' . preg_replace('/\D/', '', $postData['whatsapp'] ?? ''),
                "Date_of_Birth" => $dobFormatted,
                "Gender"        => $postData['gender'] ?? '',
                "Profile_Status" => $postData['profile_status'] ?? '',
                "Interested_Local_Job_Role" => $interested, // ✅ proper array
                "Prior_Experience_Job_Role" => $prior,      // ✅ proper array
                "Pincode"       => $postData['pincode'] ?? '',
                "State"         => $postData['state'] ?? '',
                "Country"       => $postData['country'] ?? '',
                "City"          => $postData['city'] ?? '',
            ],
            "skip_workflow" => ["all"],
        ];




        $url = $this->baseUrl . '/report/All_SP_Register_Report/' . $recordId;
        [$code, $json] = $this->callZoho('PATCH', $url, $payload, ['Content-Type: application/json']);

        return $code >= 200 && $code < 300
            ? ['success' => true, 'response' => $json]
            : ['success' => false, 'status' => $code, 'response' => $json];
    }
}
