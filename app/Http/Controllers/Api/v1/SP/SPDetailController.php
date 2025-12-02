<?php

namespace App\Http\Controllers\Api\v1\SP;

use Illuminate\Http\Request;
use App\Models\SortkarJobRole;
use App\Http\Controllers\Controller;
use App\Models\SP\Auth\SortkarSPUser;
use Illuminate\Support\Facades\Validator;

class SPDetailController extends Controller
{
    public function getSPDetails(Request $request)
    {
        $response = SortkarSPUser::verifyAndGetDetails(
            $request->phone,
            $request->token
        );

        return response()->json($response, $response['status_code']);
    }

    public function saveSPDetails(Request $request)
    {
        $res = SortkarSPUser::verifyAndSaveSPDetails($request);

        return response()->json($res, $res['status_code']);
    }

    public function getJobRoles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:all,specific',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'status_code' => 422,
                'status_message' => $validator->errors()->first('type'),
            ], 422);
        }

        $type = $request->get('type');

        if ($type === 'all') {
            // Case 1: return all job roles
            $job_roles = SortkarJobRole::all();
        } else {
            // Case 2: return only specific job roles
            $job_roles = SortkarJobRole::whereIn('id', [1, 2, 27])->get();
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'status_message' => 'Sortkar Job Roles.',
            'job_roles' => $job_roles,
        ], 200);
    }
}
