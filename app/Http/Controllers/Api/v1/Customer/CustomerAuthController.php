<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CustomerAuthController extends Controller
{

 public function signup(Request $request)
    {
        $response = Customer::handleSignup($request->all());
        return response()->json($response, $response['status_code']);
    }
}
