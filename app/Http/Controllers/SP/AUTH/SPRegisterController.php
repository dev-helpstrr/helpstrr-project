<?php

namespace App\Http\Controllers\SP\AUTH;

use Exception;
use Aws\S3\S3Client;
use App\Helpers\ZohoHelper;
use Illuminate\Http\Request;
use App\Models\SortkarJobRole;
use App\Services\ReferralService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\SP\Auth\SortkarSPUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SPRegisterController extends Controller
{
    public function viewRegisterForm()
    {
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


        return view('SP/Auth/SPRegister', compact('states', 'cities', 'countries', 'job_roles'));
    }




    public function ViewSPWelcomePage()
    {
        $job_roles = SortkarJobRole::whereIn('id', [1, 2, 27])->get();
        return view('SP/layout/home', compact('job_roles'));
    }


    public function homePage()
    {

        return view('SP/layout/index');
    }

    // ---------------- OTP ---------------- //

    public function sendOtpAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'          => 'required|digits:10',
            'otp_length'     => 'nullable|integer|min:4|max:8',
            'expiry_seconds' => 'nullable|integer|min:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $phone     = $request->phone;
        $otpLength = $request->otp_length ?? 6;
        $expiry    = $request->expiry_seconds ?? 300;

        $otp = $this->generateOtp($otpLength);

        // Save OTP in session (no module_id)
        session([
            'otpverify.phone'   => $phone,
            'otpverify.code'    => $otp,
            'otpverify.expires' => time() + $expiry,
        ]);


        $msgRes = $this->sendWhatsappMessage($phone, $otp);


        if ((int) $msgRes['httpCode'] === 200) {
            return response()->json(['success' => true, 'message' => 'OTP sent successfully.']);
        }

        return response()->json([
            'success'  => false,
            'message'  => 'Failed to send OTP.',
            'provider' => $msgRes,
        ]);
    }

    public function verifyOtpAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits_between:4,8',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $otp = $request->otp;
        $data = $this->getSession();

        if (empty($data['code'])) {
            return response()->json(['success' => false, 'message' => 'No OTP session found. Please request again.']);
        }

        if (time() > (int) $data['expires']) {
            return response()->json(['success' => false, 'message' => 'OTP expired. Please request again.']);
        }

        if ($otp !== (string) $data['code']) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP.']);
        }

        $this->clearSession();

        // ✅ Get phone number (assuming it was stored in session when sending OTP)
        $phone = $data['phone'] ?? null;

        if ($phone) {
            // Insert into sortkar_sp_users if not exists
            DB::table('s_p_users')->updateOrInsert(
                ['mobile1_number' => $phone],  // condition
                ['created_at' => now(), 'updated_at' => now()] // values to insert/update
            );
        }


        return response()->json(['success' => true, 'message' => 'OTP verified successfully.']);
    }

    // ---------------- SP Details ---------------- //

    public function formDetailsAjax(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->input('phone', ''));

        if (strlen($phone) !== 10) {
            return response()->json(['success' => false, 'message' => 'Invalid mobile number.']);
        }

        try {
            $data   = SortkarSPUser::where('mobile1_number', $request->phone)->first();

            return response()->json([
                'success' => true,
                'sp'      => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }


    public function saveSpDetailsAjax(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->input('phone', ''));

        if (strlen($phone) !== 10) {
            return response()->json(['success' => false, 'message' => 'Invalid mobile number.']);
        }

        $zh   = new ZohoHelper();
        // Update if record exists, else insert



        $postData = $request->only([
            'first_name',
            'last_name',
            'email',
            'mobile1_number',
            'whatsapp',
            'dob',
            'gender',
            'profile_status',
            'prior_experience',
            'intrested_role',
            'pincode',
            'state',
            'country',
            'city',
        ]);



        if (isset($postData['prior_experience']) && is_array($postData['prior_experience'])) {
            $postData['prior_experience'] = json_encode($postData['prior_experience']);
        }
        if (isset($postData['intrested_role']) && is_array($postData['intrested_role'])) {
            $postData['intrested_role'] = json_encode($postData['intrested_role']);
        }

        // First, try to find the user by phone
        $user = DB::table('s_p_users')->where('mobile1_number', $request->input('mobile1_number'))->first();

        if ($user && empty($user->email)) {
            // ✅ If user exists and email is NULL → update by phone
            DB::table('s_p_users')->updateOrInsert(
                [
                    'mobile1_number' => $request->input('mobile1_number'),
                ],
                $postData
            );
        } else {
            // ✅ Else update/insert by both phone + email
            DB::table('s_p_users')->updateOrInsert(
                [
                    'mobile1_number' => $request->input('mobile1_number'),
                    'email'          => $request->input('email'),
                ],
                $postData
            );
        }

        // If a referral was used (decodedRefId is set)
        $referralResponse = ReferralService::createReferralForUser($user);
        $refId = $request->input('ref_id');

        if ($refId) {
            ReferralService::recordReferralVisit($refId, $user->id);
        }
        $data = $zh->getSPDetails($phone);

        if (isset($data['message']) && stripos(trim($data['message']), "No records found") !== false) {
            // Insert new SP
            $res = $zh->insertSp($postData);

            return response()->json([
                'success' => true,
                'action'  => 'insert',
                'data'    => $res,
            ]);
        }

        $recordId = $data['data'][0]['ID'] ?? null;

        if (!$recordId) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to find record ID for update.',
                'data'    => $data,
            ]);
        }

        // Update existing SP
        $res = $zh->updateSp($recordId, $postData);

        return response()->json([
            'success' => true,
            'action'  => 'update',
            'data'    => $res,
        ]);
    }

    public function getRolesAjax()
    {
        $zh   = new ZohoHelper();
        $data = $zh->getRoleDetails();

        return response()->json([
            'success' => true,
            'data' => $data, // your array of objects
        ]);
    }

    // ---------------- Helpers ---------------- //

    private function generateOtp($length = 6)
    {
        $length = max(4, (int) $length);
        $min    = (int) pow(10, $length - 1);
        $max    = (int) (pow(10, $length) - 1);

        return (string) random_int($min, $max);
    }

    private function clearSession()
    {
        session()->forget([
            'otpverify.phone',
            'otpverify.code',
            'otpverify.expires',
        ]);
    }

    private function getSession()
    {
        return [
            'phone'   => session('otpverify.phone'),
            'code'    => session('otpverify.code'),
            'expires' => (int) session('otpverify.expires', 0),
        ];
    }

    private function sendWhatsappMessage($phone, $otp)
    {

        $url = env('url');

        $message = "{$otp} is your verification code. For your security, do not share this code.";

        $payload = [
            'userid'     => env('userid'),
            'password'   => env('password'),
            'send_to'    => $phone,
            'v'          => env('v'),
            'format'     => env('format'),
            'msg_type'   => env('msg_type'),
            'method'     => env('method'),
            'msg'        => $message,
            'isTemplate' => env('isTemplate'),
            'footer'     => env('footer'),
        ];

        return $this->postJson($url, $payload);
    }

    private function postJson($url, $payload)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);

        curl_close($ch);

        return ['httpCode' => $httpCode, 'error' => $err, 'body' => $response];
    }


    public function showListedFiles()
    {
        $bucket = env('AWS_BUCKET');
        $region = env('AWS_DEFAULT_REGION');

        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $region,
            // No keys needed – AWS SDK will auto-discover credentials
        ]);

        try {
            $result = $s3Client->listObjectsV2([
                'Bucket' => $bucket,
            ]);

            echo "Objects in bucket {$bucket}:\n";

            if (!empty($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    echo $object['Key'] . PHP_EOL;
                }
            } else {
                echo "No files found in this bucket." . PHP_EOL;
            }
        } catch (AwsException $e) {
            echo "Error accessing S3: " . $e->getMessage();
        }
    }

    public function getAWSFile($value)
    {
        // dd($value);

        $url = Storage::disk('s3')->get($value);
        $ct = Storage::disk('s3')->mimeType($value);

        header("Content-Type: $ct");

        echo $url;
        exit;
    }
}
