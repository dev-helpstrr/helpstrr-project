<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\UtmTracking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UTMDataController extends Controller
{
    public function store(Request $request)
    {

        $data = $request->validate([
            'user_id' => 'nullable|integer',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'utm_referral_id' => 'nullable|string|max:255',
            'referrer_url' => 'nullable|string',
            'landing_page_url' => 'nullable|string',
        ]);

       $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();


        $utm = UtmTracking::create($data);



        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'UTM data stored successfully',
            'data' => $utm
        ], 201);
    }
}
