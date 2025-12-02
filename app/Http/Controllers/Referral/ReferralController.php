<?php

namespace App\Http\Controllers\Referral;

use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ReferralController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        // If referral link present, decode and pass it to view
        $refId = $request->query('ref_id');
        $decodedRefId = $refId ? base64_decode($refId) : null;
        $states = DB::table('loc_states')
            ->where('id', 4853)
            ->get();

        $cities = DB::table('loc_cities')
            ->where('state_id', 4853)
            ->get();

        $countries = DB::table('loc_countries')
            ->where('id', 101)
            ->get();

        $job_roles = DB::table('sortkar_job_roles')->get();

        return view('SP/Auth/SPRegister', compact('states', 'cities', 'countries', 'job_roles', 'decodedRefId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'referral_to_name' => 'required|string|max:100',
            'referral_to_phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'referral_to_job_role' => 'required|integer|exists:sortkar_job_roles,id',
        ]);

        Referral::create($validated);

        return response()->json(['message' => 'Referral submitted successfully!']);
    }
}
