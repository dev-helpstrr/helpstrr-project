<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\UserToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserTokenController extends Controller
{
    public function store(Request $request)
    {
        $response = UserToken::handleSubmission($request->all());
        return response()->json($response);
    }
}
