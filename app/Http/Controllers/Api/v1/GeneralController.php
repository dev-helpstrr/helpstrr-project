<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneralController extends Controller
{
    /**
     * Get all countries
     */
    public function countryDetails()
    {
        $countries = DB::table('loc_countries')->select('id', 'zoho_country_id', 'name')->get();

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'Countries fetched successfully',
            'data' => $countries
        ], 200);
    }

    /**
     * Get all states
     */
    public function statesDetails()
    {
        $states = DB::table('loc_states')->select('id', 'zoho_state_id', 'name')->get();

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'States fetched successfully',
            'data' => $states
        ], 200);
    }

    /**
     * Get all cities
     */
    public function citiesDetails()
    {
        $cities = DB::table('loc_cities')->select('id', 'zoho_city_id', 'name')->get();

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'Cities fetched successfully',
            'data' => $cities
        ], 200);
    }

    /**
     * Get states by country ID
     */
    public function countryWiseStates($country_id)
    {
        $states = DB::table('loc_states')
            ->where('country_id', $country_id)->select('id', 'zoho_state_id', 'name')
            ->get();

        if ($states->isEmpty()) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'status_message' => 'No states found for this country',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'States fetched successfully',
            'data' => $states
        ], 200);
    }

    /**
     * Get cities by state ID
     */
    public function stateWiseCities($state_id)
    {
        $cities = DB::table('loc_cities')
            ->where('state_id', $state_id)->select('id', 'zoho_city_id', 'name')
            ->get();

        if ($cities->isEmpty()) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'status_message' => 'No cities found for this state',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'Cities fetched successfully',
            'data' => $cities
        ], 200);
    }



    public function typeWiseData($type)
    {
        $filePath = public_path('data_helpstrr/helpstrr_data.json');

      //  dd($filePath);

        if (!file_exists($filePath)) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'status_message' => 'JSON file not found',
                'data' => null
            ], 500);
        }

        $jsonData = json_decode(file_get_contents($filePath), true);

        if (!isset($jsonData['HELPSTRR_SYS_VAR'][$type])) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'status_message' => "No data found for type: $type",
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'status_message' => ucfirst($type) . " list",
            'data' => $jsonData['HELPSTRR_SYS_VAR'][$type]
        ], 200);
    }
}
