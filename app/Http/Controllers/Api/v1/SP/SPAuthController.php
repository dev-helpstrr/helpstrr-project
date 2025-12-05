<?php

namespace App\Http\Controllers\Api\v1\SP;

use App\Http\Controllers\Controller;
use App\Models\SP\Auth\SortkarSPUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\SPUser; // your model for s_p_users

class SPAuthController extends Controller
{
    public function signup(Request $request)
    {

        $response = SortkarSPUser::handleSignup($request->all());
        return response()->json($response, $response['status_code']);
    }

    public function verifyOtp(Request $request)
    {

        $response = SortkarSPUser::verifyOtpAndCreateToken($request->all());
        return response()->json($response, $response['status_code']);
    }
}
