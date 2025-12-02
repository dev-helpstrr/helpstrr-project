<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\AuthHelper;
use Symfony\Component\HttpFoundation\Response;

class ValidateSPToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $phone = $request->input('phone');
        $token = $request->input('token');

        if (!$phone || !$token) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'status_message' => 'Phone and token are required',
                'data' => null
            ], 400);
        }

        $tokenCheck = AuthHelper::validateToken('s_p_users', $phone, $token, 'phone');

        if (!$tokenCheck['valid']) {
            return response()->json([
                'status' => 'failure',
                'status_code' => $tokenCheck['status_code'],
                'status_message' => $tokenCheck['message'],
                'data' => null
            ], $tokenCheck['status_code']);
        }

        // Add the validated user to the request
        $request->merge(['validated_sp' => $tokenCheck['user']]);

        return $next($request);
    }
}