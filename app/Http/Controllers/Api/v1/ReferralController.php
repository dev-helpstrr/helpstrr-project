<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use App\Services\ReferralService;
use App\Http\Controllers\Controller;

class ReferralController extends Controller
{
    public function inviteDetailsSubmit(Request $request)
    {
        $mobile = $request->input('mobile1_number');
        $token  = $request->input('token');

        // ✅ Call the helper function
        $validation = AuthHelper::validateToken('s_p_users', $mobile, $token, 'mobile1_number');

        if (!$validation['valid']) {
            return response()->json($validation, $validation['status_code']);
        }

        $response = ReferralService::recordSPInvite($request);

        return response()->json($response, $response['status_code']);
    }
}
