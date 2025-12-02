<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerDetailController extends Controller
{
    public function getCustomerDetails(Request $request)
    {
        $response = Customer::verifyAndGetDetails(
            $request->phone,
            $request->token
        );

        return response()->json($response, $response['status_code']);
    }

    public function saveCustomerDetails(Request $request)
    {
        $res = Customer::verifyAndSaveCustomerDetails($request);

        return response()->json($res, $res['status_code']);
    }
}
