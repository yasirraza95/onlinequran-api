<?php

namespace App\Http\Controllers\V1;

use App\Models\BloodRequest;
use App\Models\BloodGroup;
use App\Models\Namaz;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Site;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Newsletter;
use App\Models\User;
use App\Models\State;
use App\Models\City;
use App\Models\CityArea;
use App\Models\Volunteer;
use App\Models\Log;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Mail;
use Validator;
 
class GeneralController extends Controller
{
    use ApiResponser;

    private $_page = 1;
    private $_offset = 0;
    private $_name = "";
    private $_fname = "";
    private $_lname = "";
    private $_address = "";
    private $_phone = "";
    private $_blood_gp = "";
    private $_state_name = "";
    private $_city_name = "";
    private $_city = 0;
    private $_area = 0;
    private $_group = 0;
    private $_limit = 10;


    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required'
        ]);

        $user = User::where("username", $request->username)
        ->where("password", addslashes($request->password))->first();

        $data = [
            "error" => "Unauthorized"
        ];

        if (is_null($user)) {
            $result = $this->errorResponse($request, $data, 401);
            return $result;
        }

        if (!$token = Auth::login($user)) {
            $result = $this->errorResponse($request, $data, 401);
            return $result;
        }

        return $this->respondWithToken($request, $user, $token);
    }

    public function adminLogin(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required'
        ]);

        $user = User::where(function ($q) use ($request) {
            $q->where('email', addslashes($request->username))
                ->orWhere('phone', addslashes($request->username));
        })->where("password", addslashes($request->password))->where('user_type', 'admin')->first();

        $data = [
            "error" => "Unauthorized"
        ];

        if (is_null($user)) {
            $result = $this->errorResponse($request, $data, 401);
            return $result;
        }

        if (!$token = Auth::login($user)) {
            $result = $this->errorResponse($request, $data, 401);
            return $result;
        }

        return $this->respondWithToken($request, $user, $token);
    }

    public function profile(Request $request)
    {
        $auth = Auth::user();
        $status = !is_null($auth) === true ? 200 : 404;
        $result = $this->successResponse($request, $auth, $status);
        return $result;
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    public function checkEmailExist(Request $request)
    {
        $email = addslashes($request->email);

        $counter = User::where('email', $email)->count();

        $status = 200;
        $response = 'Email is available';

        if ($counter > 0) {
            $status = 409;
            $response = 'Email already exists';
        }

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function checkPhoneExist(Request $request)
    {
        $phone = addslashes($request->phone);

        $counter = User::where('phone', $phone)->count();

        $status = 200;
        $response = 'Phone number is available';

        if ($counter > 0) {
            $status = 409;
            $response = 'Phone number already exists';
        }

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function checkUsernameExist(Request $request)
    {
        $username = addslashes($request->username);

        $result = User::where('username', 'like', $username)->get();

        $status = 200;
        $response = 'Username is available';

        if ($result->count() > 0) {
            $status = 409;
            $response = 'Username already exists';
        }

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function checkForgotToken(Request $request)
    {
        $token = addslashes($request->token);
        $result = User::where('reset_token', $token)
            ->where('reset_tkn_status', 'active')
            ->firstOrFail();

        $status = 200;
        $response = 'Token valid';
        $expiryTime = $result->reset_tkn_time;

        if (strtotime($expiryTime) < strtotime(date('Y-m-d H:i:s'))) {
            $status = 422;
            $response = 'Token expired';
        }

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function checkEmailVerification(Request $request)
    {
        $token = addslashes($request->token);
        $activeCounter = User::where('email_token', $token)
            ->where('email_status', 'disabled')
            ->count();

        $verifiedCounter = User::where('email_token', $token)
            ->where('email_status', 'active')
            ->count();

        $status = '';
        $response = '';

        if ($activeCounter > 0) {
            $status = 422;
            $response = 'Eligible for verification';
        } else if ($verifiedCounter > 0) {
            $status = 409;
            $response = 'Already verified';
        }

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function checkPhoneVerification(Request $request)
    {
        $otp = addslashes($request->otp);
        $activeCounter = User::where('sms_token', $otp)
            ->where('sms_status', 'disabled')
            ->count();

        $verifiedCounter = User::where('sms_token', $otp)
            ->where('sms_status', 'active')
            ->count();

        $status = '';
        $response = '';

        if ($activeCounter > 0) {
            $status = 422;
            $response = 'Eligible for verification';
        } else if ($verifiedCounter > 0) {
            $status = 409;
            $response = 'Already verified';
        }

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function validateEmailVerification(Request $request)
    {
        $token = addslashes($request->token);

        $instance = User::where('email_token', $token)
            ->firstOrFail();

        $updateData = ['email_status' => 'active'];

        User::where('id', $instance->id)->update($updateData);

        $status = 200;
        $response = 'Your email has been successfully verified.';

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function validatePhoneVerification(Request $request)
    {
        $otp = addslashes($request->otp);

        $instance = User::where('sms_token', $otp)
            ->firstOrFail();

        $updateData = ['sms_status' => 'active'];

        User::where('id', $instance->id)->update($updateData);

        $status = 200;
        $response = 'Your phone number has been successfully verified.';

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    // TODO email template + custom validator
    public function register(Request $request)
    {
        $rules = User::validationRules();

        $this->validate($request, $rules);

        // $notifications = "no";
        // if ($request->notifications == 1) {
        //     $notifications = "yes";
        // }
        $notifications = "yes";

        // $donation_consent = "no";
        // if ($request->donation_consent == 1) {
        //     $donation_consent = "yes";
        // }
        $donation_consent = "yes";

        $currentTime = date('Y-m-d H:i:s');
        $code = strtotime($currentTime);
        $otp = rand(100000, 999999);

        $stateRow = State::select('id')->where('name', $request->state)->firstOrFail();
        $cityRow = City::select('id')->where('name', $request->city)->firstOrFail();
        $cityAreaCount = CityArea::select('id')->where('name', 'like', '%' . $request->city_area . '%')->where('city_id', $cityRow->id)->count();
        $cityAreaId = 0;
        if ($cityAreaCount == 0) {
            $area = ['name' => $request->city_area, 'city_id' => $cityRow->id, 'created_ip' => $request->ip()];
            $cityAreaId = CityArea::create($area)->id;
        } else {
            $query = CityArea::select('id')->where('name', 'like', '%' . $request->city_area . '%')->where('city_id', $cityRow->id)->first();
            $cityAreaId = $query->id;
        }

        $bloodGroup = addslashes($request->group);
        if (!str_contains($request->group, '-')) {
            $this->_group = addslashes($request->group) . '+';
            $this->_group = str_replace(' ', '', $this->_group);
        }

        $groupRow = BloodGroup::select('id')->where('name', $bloodGroup)->firstOrFail();


        $userObject = [
            "email" => addslashes($request->email),
            "phone" => addslashes($request->phone),
            "password" => addslashes($request->password),
            "first_name" => addslashes($request->first_name),
            "last_name" => addslashes($request->last_name),
            "state_id" => $stateRow->id,
            "city_id" => $cityRow->id,
            "area_id" => $cityAreaId,
            "address" => addslashes($request->address),
            "user_type" => "donor",
            "consent" => $donation_consent,
            "notifications" => $notifications,
            "email_token" => $code,
            "sms_token" => $otp,
            "sms_tkn_time" => $currentTime,
            "created_ip" => $request->ip(),
            "blood_group" => $groupRow->id,
            "dob" => $request->dob,
            "last_bleed" => $request->last_bleed,
        ];

        // TODO
        $subject = 'Sehat Booking Account Registration';
        $newsletterData = Newsletter::findOrFail(2);
        $template = $newsletterData->body;

        $user = $request->first_name . " " . $request->last_name;
        $link = "https://sehatbooking.com/verification?token=$code";

        $bodyText = '';
        $bodyText = str_replace('$user', $user, $template);
        $bodyText = str_replace('$link', $link, $bodyText);

        User::create($userObject);
        // $this->sendEmailToUser($bodyText, $subject, addslashes($request->email));

        $data = [
            'response' => "Your account has been created successfully",
        ];

        $result = $this->successResponse($request, $data, 201);
        return $result;
    }

    // TODO email template
    public function recipientRegister(Request $request)
    {
        $rules = User::recipientValidationRules();
        $rules['ip'] = 'required|ip';

        $this->validate($request, $rules);

        $currentTime = date('Y-m-d H:i:s');
        $code = strtotime($currentTime);
        $otp = rand(100000, 999999);

        $userObject = [
            "email" => addslashes($request->email),
            "phone" => addslashes($request->phone),
            "first_name" => addslashes($request->first_name),
            "last_name" => addslashes($request->last_name),
            "password" => addslashes($request->username),
            "state_id" => addslashes($request->state_id),
            "city_id" => addslashes($request->city_id),
            "address" => addslashes($request->address),
            "user_type" => addslashes($request->user_type),
            "created_ip" => addslashes($request->ip),
            "email_token" => $code,
            "sms_token" => $otp,
            "sms_tkn_time" => $currentTime,
        ];

        // $subject = 'Sehat Booking Account Registration';
        // $newsletterData = Newsletter::findOrFail(2);
        // $storeData = StoreCityState::where('store_id', $request->store_id)->firstOrFail();
        // $template = $newsletterData->body;

        // $bodyText = '';
        // $bodyText = str_replace('$state', $storeData->state_name, $template);
        // $bodyText = str_replace('$store', $storeData->store_name, $bodyText);
        // $bodyText = str_replace('$city', $storeData->city_name, $bodyText);
        // $bodyText = str_replace('$username', $request->username, $bodyText);
        // $bodyText = str_replace('$role', strtoupper($request->user_type), $bodyText);
        // $bodyText = str_replace('$date', date('m-d-Y'), $bodyText);

        $newResult = User::create($userObject);
        // $this->sendEmailToUser($bodyText, $subject, addslashes($request->email));

        $data = [
            'response' => $newResult,
        ];

        $result = $this->successResponse($request, $data, 201);
        return $result;
    }

    public function forgotPassword(Request $request)
    {
        // $rules = User::validationRules();
        $rules['email'] = 'required|email';
        $instance = User::where('email', addslashes($request->email))->firstOrFail();
        $user = $instance->first_name . ' ' . $instance->last_name;
        $this->validate($request, $rules);
        $subject = 'Sehat Booking Forgot Password';

        $newsletterData = Newsletter::findOrFail(3);

        $token = time();
        $link = "https://sehatbooking.com/forgot-token?token=$token";

        $bodyText = '';
        $template = $newsletterData->body;
        $bodyText = str_replace('$user', $user, $template);
        $bodyText = str_replace('$link', $link, $bodyText);

        $expiryTime = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " +48 hours"));
        $updateData = ['reset_token' => $token, 'reset_tkn_status' => 'active', 'reset_tkn_time' => $expiryTime];
        User::where('id', $instance->id)->update($updateData);

        $this->sendEmailToUser($bodyText, $subject, addslashes($request->email));

        $data = [
            'response' => 'Check your email to reset password',
        ];

        $result = $this->successResponse($request, $data, 200);
        return $result;
    }

    public function listYears(Request $request)
    {
        $result = range(1900, date('Y'));
        $status = 200;

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listSliders(Request $request)
    {
        $result = Slider::get();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listNamazTimings(Request $request)
    {
        $result = Namaz::get();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function getSiteInfo(Request $request)
    {
        $result = Site::first();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function updateSiteInfo(Request $request)
    {
        $instance = Site::firstOrFail();
        $update = ["email" => $request->email, "phone" => $request->phone ];
        $instance->update($update);

        $status = 200;

        $data = [
            'response' => "Site info updated",
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listService(Request $request)
    {
        $result = Service::get();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listAdminVolunteers(Request $request)
    {
        if (isset($request->page) && !empty($request->page)) {
            $this->_page = $request->page;
        }

        if (isset($request->limit) && !empty($request->limit)) {
            $this->_limit = $request->limit;
        }

        if (isset($request->name) && !empty($request->name)) {
            $this->_name = $request->name;
        }

        $this->_offset = ($this->_page - 1) * $this->_limit;

        $result = Volunteer::where('name', 'like', '%'.$this->_name.'%')->offset($this->_offset)
            ->limit($this->_limit)->get();

        $counter = count($result);
        $counter > 0 ? ($status = 206) : ($status = 404);

        $totalRecords = Volunteer::get();
        $totalRecords = $totalRecords->count();
        $totalPages = ceil($totalRecords / (int) $this->_limit);

        $data = [
            'response' => $result,
            'records' => $totalRecords,
            'total_pages' => $totalPages,
            'per_page' => (int) $this->_limit,
            'current_page' => (int) $this->_page,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    // TODO marquee tag listing
    public function getMarquee(Request $request)
    {
        $result = BloodRequest::where('type', "emergency")->whereDate('created_at', '>=', Carbon::yesterday())
            ->whereDate('created_at', '<=', Carbon::today())->orderBy('id', 'DESC')->limit(2)->get();
        $string = "";
        $response = [];
        foreach ($result as $key => $rows) {
            $blood = BloodGroup::select('name')->where('id', $rows->blood_id)->first();
            $state = State::select('name')->where('id', $rows->state_id)->first();
            $city = City::select('name')->where('id', $rows->city_id)->first();
            $rows['blood'] = !empty($blood->name) ? $blood->name : 'N/A';
            $rows['phone'] = !empty($rows->phone) ? $rows->phone : 'N/A';
            $rows['state'] = !empty($state->name) ? $state->name : 'N/A';
            $rows['city'] = !empty($city->name) ? $city->name : 'N/A';
            $response[$key] = $rows;
        }

        $status = 404;
        $counter = count($result);
        if ($counter > 1) {
            $status = 200;
            $string = "Emergency Blood is required => Blood Group: " . $response[0]->blood . ", Phone No: " .
                $response[0]->phone . ", Address: " . $response[0]->city . ", " . $response[0]->state .
                ", Blood Group: " . $response[1]->blood . ", Phone No: " . $response[1]->phone . ", Address: "
                . $response[1]->city . ", " . $response[1]->state;
        } else if ($counter > 0) {
            $status = 200;
            $string = "Emergency Blood is required => Blood Group: " . $response[0]->blood . ", Phone No: " .
                $response[0]->phone . ", Address: " . $response[0]->city . ", " . $response[0]->state;
        }

        $data = [
            'response' => $string,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listStates(Request $request)
    {
        $result = State::get();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listCities(Request $request)
    {
        $result = City::get();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listBloodDonor(Request $request)
    {
        if (isset($request->page) && !empty($request->page)) {
            $this->_page = $request->page;
        }

        if (isset($request->limit) && !empty($request->limit)) {
            $this->_limit = $request->limit;
        }

        $this->_offset = ($this->_page - 1) * $this->_limit;

        // FIXME city area search
        $result = User::select('users.id', 'users.first_name', 'users.last_name', 'users.address')
            ->selectRaw('cities.name as city_name, blood_groups.name as blood_group')
            ->join('cities', 'users.city_id', '=', 'cities.id')
            ->join('blood_groups', 'users.blood_group', '=', 'blood_groups.id')
            ->whereIn('user_type', ['donor', 'both'])
            ->where('consent', 'yes')
            ->offset($this->_offset)
            ->limit($this->_limit)
            ->orderBy('id', 'DESC');

        $totalRecords = User::join('cities', 'users.city_id', '=', 'cities.id')
            ->join('blood_groups', 'users.blood_group', '=', 'blood_groups.id')
            ->whereIn('user_type', ['donor', 'both'])->where('consent', 'yes');

        if (isset($request->city) && !empty($request->city)) {
            $this->_city = $request->city;
            $result = $result->where('cities.name', 'like', $this->_city);
            $totalRecords = $totalRecords->where('cities.name', 'like', $this->_city);
        }

        // TODO
        if (isset($request->area) && !empty($request->area)) {
            $this->_area = $request->area;
            // $result = $result->where('cities.name', 'like', $this->_area);
            // $totalRecords = $totalRecords->where('cities.name', 'like', $this->_city);
        }

        if (isset($request->group) && !empty($request->group)) {
            $this->_group = addslashes($request->group);
            if (!str_contains($request->group, '-')) {
                $this->_group = addslashes($request->group) . '+';
                $this->_group = str_replace(' ', '', $this->_group);
            }

            $result = $result->where('blood_groups.name', 'like', $this->_group);
            $totalRecords = $totalRecords->where('blood_groups.name', 'like', $this->_group);
        }

        $result = $result->get();
        $totalRecords = $totalRecords->count();
        $totalPages = ceil($totalRecords / (int) $this->_limit);

        $counter = count($result);
        $counter > 0 ? ($status = 206) : ($status = 404);

        $data = [
            'response' => $result,
            'records' => $totalRecords,
            'total_pages' => $totalPages,
            'per_page' => (int) $this->_limit,
            'current_page' => (int) $this->_page,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listAllBloodDonor(Request $request)
    {
        if (isset($request->page) && !empty($request->page)) {
            $this->_page = $request->page;
        }

        if (isset($request->limit) && !empty($request->limit)) {
            $this->_limit = $request->limit;
        }

        $this->_offset = ($this->_page - 1) * $this->_limit;

        $result = User::select('users.id', 'users.first_name', 'users.last_name', 'users.address')
            ->selectRaw('cities.name as city_name, blood_groups.name as blood_group')
            ->join('cities', 'users.city_id', '=', 'cities.id')
            ->join('blood_groups', 'users.blood_group', '=', 'blood_groups.id')
            ->whereIn('user_type', ['donor', 'both'])
            ->where('consent', 'yes')
            ->offset($this->_offset)
            ->limit($this->_limit);

        $totalRecords = User::join('cities', 'users.city_id', '=', 'cities.id')
            ->join('blood_groups', 'users.blood_group', '=', 'blood_groups.id')
            ->whereIn('user_type', ['donor', 'both'])->where('consent', 'yes');

        if (isset($request->fname) && !empty($request->fname)) {
            $this->_fname = $request->fname;
            $result = $result->where('users.first_name', 'like', '%' . $this->_fname . '%');
            $totalRecords = $totalRecords->where('users.first_name', 'like', '%' . $this->_fname . '%');
        }

        if (isset($request->lname) && !empty($request->lname)) {
            $this->_lname = $request->lname;
            $result = $result->where('users.last_name', 'like', '%' . $this->_lname . '%');
            $totalRecords = $totalRecords->where('users.last_name', 'like', '%' . $this->_lname . '%');
        }

        if (isset($request->address) && !empty($request->address)) {
            $this->_address = $request->address;

            $result = $result->where('users.address', 'like', '%' . $this->_address . '%');
            $totalRecords = $totalRecords->where('users.address', 'like', '%' . $this->_address . '%');
        }

        if (isset($request->city) && !empty($request->city)) {
            $this->_city = $request->city;
            $result = $result->where('cities.name', 'like', $this->_city);
            $totalRecords = $totalRecords->where('cities.name', 'like', $this->_city);
        }

        // TODO
        if (isset($request->area) && !empty($request->area)) {
            $this->_area = $request->area;
            // $result = $result->where('cities.name', 'like', $this->_area);
            // $totalRecords = $totalRecords->where('cities.name', 'like', $this->_city);
        }

        if (isset($request->group) && !empty($request->group)) {
            $this->_group = addslashes($request->group);
            if (!str_contains($request->group, '-')) {
                $this->_group = addslashes($request->group) . '+';
                $this->_group = str_replace(' ', '', $this->_group);
            }

            $result = $result->where('blood_groups.name', 'like', $this->_group);
            $totalRecords = $totalRecords->where('blood_groups.name', 'like', $this->_group);
        }

        $result = $result->get();
        $totalRecords = $totalRecords->count();
        $totalPages = ceil($totalRecords / (int) $this->_limit);

        $counter = count($result);
        $counter > 0 ? ($status = 206) : ($status = 404);

        $data = [
            'response' => $result,
            'records' => $totalRecords,
            'total_pages' => $totalPages,
            'per_page' => (int) $this->_limit,
            'current_page' => (int) $this->_page,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listBloodRequest(Request $request)
    {
        $type = addslashes($request->type);
        $result = BloodRequest::where('type', $type)->orderBy('id', 'DESC')->get();
        $response = [];
        foreach ($result as $key => $rows) {
            $blood = BloodGroup::select('name')->where('id', $rows->blood_id)->first();
            $state = State::select('name')->where('id', $rows->state_id)->first();
            $city = City::select('name')->where('id', $rows->city_id)->first();
            $rows['type'] = !empty($rows->type) ? $rows->type : 'N/A';
            $rows['blood'] = !empty($blood->name) ? $blood->name : 'N/A';
            $rows['phone'] = !empty($rows->phone) ? $rows->phone : 'N/A';
            $rows['state'] = !empty($state->name) ? $state->name : 'N/A';
            $rows['city'] = !empty($city->name) ? $city->name : 'N/A';
            $rows['date'] = !empty($rows->created_at) ? $rows->created_at : 'N/A';


            $response[$key] = $rows;
        }

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $response,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function listAdminBloodRequest(Request $request)
    {
        if (isset($request->page) && !empty($request->page)) {
            $this->_page = $request->page;
        }

        if (isset($request->limit) && !empty($request->limit)) {
            $this->_limit = $request->limit;
        }

        $this->_offset = ($this->_page - 1) * $this->_limit;


        $result = BloodRequest::select();

        if (isset($request->phone) && !empty($request->phone)) {
            $this->_phone = $request->phone;

            $result = $result->where('phone', 'like', '%' . $this->_phone . '%');
        }

        if (isset($request->blood_gp) && !empty($request->blood_gp)) {
            $this->_blood_gp = $request->blood_gp;
            if (!str_contains($request->blood_gp, '-')) {
                $this->_blood_gp = addslashes($request->blood_gp) . '+';
                $this->_blood_gp = str_replace(' ', '', $this->_blood_gp);
            }
            $bloodId = BloodGroup::select('id')->where('name', 'like', '%' . $this->_blood_gp . '%')->first();
            $result = $result->where('blood_id', $bloodId->id);
        }

        if (isset($request->state_name) && !empty($request->state_name)) {
            $this->_state_name = $request->state_name;
            $stateId = State::select('id')->where('name', 'like', '%' . $this->_state_name . '%')->first();
            $result = $result->where('state_id', $stateId->id);
        }

        if (isset($request->city_name) && !empty($request->city_name)) {
            $this->_city_name = $request->city_name;
            $cityId = City::select('id')->where('name', 'like', '%' . $this->_city_name . '%')->first();
            $result = $result->where('city_id', $cityId->id);
        }


        
        $result = $result->offset($this->_offset)->limit($this->_limit)->orderBy('id', 'DESC')->get();
        $response = [];
        foreach ($result as $key => $rows) {
            $blood = BloodGroup::select('name')->where('id', $rows->blood_id)->first();
            $state = State::select('name')->where('id', $rows->state_id)->first();
            $city = City::select('name')->where('id', $rows->city_id)->first();
            $rows['type'] = !empty($rows->type) ? $rows->type : 'N/A';
            $rows['blood'] = !empty($blood->name) ? $blood->name : 'N/A';
            $rows['phone'] = !empty($rows->phone) ? $rows->phone : 'N/A';
            $rows['state'] = !empty($state->name) ? $state->name : 'N/A';
            $rows['city'] = !empty($city->name) ? $city->name : 'N/A';
            $rows['date'] = !empty($rows->created_at) ? $rows->created_at : 'N/A';


            $response[$key] = $rows;
        }

        $totalRecords = $result->count();
        $totalPages = ceil($totalRecords / (int) $this->_limit);

        $counter = count($result);
        $counter > 0 ? ($status = 206) : ($status = 404);

        $data = [
            'response' => $response,
            'records' => $totalRecords,
            'total_pages' => $totalPages,
            'per_page' => (int) $this->_limit,
            'current_page' => (int) $this->_page,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function getCitiesByStateName(Request $request)
    {
        $stateName = stripslashes($request->name);
        $stateRow = State::select('id')->where('name', $stateName)->firstOrFail();

        $result = City::where('state_id', $stateRow->id)->get();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function donorCounter(Request $request)
    {
        $donors = User::whereIn('user_type', ['donor', 'both'])->count();

        $status = 200;

        $data = [
            'response' => $donors
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function requestCounter(Request $request)
    {
        $requests = BloodRequest::count();

        $status = 200;

        $data = [
            'response' => $requests,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function getNamazTimeById(Request $request)
    {
        $id = stripslashes($request->id);
        $result = Namaz::findOrFail($id);

        $status = 200;
        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function getServiceById(Request $request)
    {
        $id = stripslashes($request->id);
        $result = Service::findOrFail($id);

        $status = 200;
        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function getBloodDonorById(Request $request)
    {
        $id = stripslashes($request->id);
        $result = User::select('id', 'phone', 'first_name', 'last_name', 'blood_group')->findOrFail($id);
        $bloodRow = BloodGroup::select('name')->findOrFail($result->blood_group);
        $result['blood_group'] = $bloodRow->name;
        $result['full_name'] = $result->first_name . " " . $result->last_name;

        $status = 200;
        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }
    public function getCityAreaByCityName(Request $request)
    {
        $cityName = stripslashes($request->name);
        $cityRow = City::select('id')->where('name', $cityName)->firstOrFail();

        $result = CityArea::where('city_id', $cityRow->id)->get();

        $counter = count($result);
        $counter > 0 ? ($status = 200) : ($status = 404);

        $data = [
            'response' => $result,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function updateUser(Request $request)
    {
        $id = $request->id;
        $instance = User::findOrFail($id);

        // $rules = User::validationRules();
        $rules['updated_by'] = 'required|int|exists:users,id';
        $rules['updated_ip'] = 'required|ip';

        $this->validate($request, $rules);
        $instance->update($request->all());

        $data = [
            'response' => $instance,
        ];

        $result = $this->successResponse($request, $data, 200);
        return $result;
    }

    public function updateAdmin(Request $request)
    {
        $id = $request->id;
        $instance = User::where('user_type', 'admin')->findOrFail($id);

        $rules['current_password'] = 'required|string';
        $rules['new_password'] = 'required|string';
        $this->validate($request, $rules);

        $update = [];

        $data = [
            'response' => "Profile updated",
        ];
        $status = 200;

        if($request->new_password == $request->current_password) {
            $data = [
                'response' => "Current & New Passwords cannot be same",
            ];
            $status = 400;

            $result = $this->successResponse($request, $data, $status);
            return $result;
        }

        if($instance->password != $request->current_password) {
            $data = [
                'response' => "Current password is incorrect",
            ];
            $status = 400;

            $result = $this->successResponse($request, $data, $status);
            return $result;
        }

        $update = ['updated_by' => $id, 'updated_ip' => $request->ip(), 'password' => $request->new_password];
        $instance->update($update);

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function updateAdminImage(Request $request)
    {
        $id = $request->id;
        $instance = User::where('user_type', 'admin')->findOrFail($id);

        $rules['image'] = 'required|image';
        // $rules['name'] = 'required|string';
        $this->validate($request, $rules);

        $update = [];

        $dateTime = date('Ymd_His');
        $image = $request->file('image');
        $imageName = $dateTime . '-' . $image->getClientOriginalName();
        $savePath = public_path('/upload/');
        $image->move($savePath, $imageName);

        $update = ['updated_by' => $id, 'updated_ip' => $request->ip(), 'image' => $imageName ];
        $instance->update($update);

        $data = [
            'response' => "Profile updated",
            'name' => $imageName,
        ];
        $status = 200;

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function addSlider(Request $request)
    {
        $rules['image'] = 'required|image';
        $rules['line1'] = 'required|string';
        $rules['line2'] = 'required|string';
        $this->validate($request, $rules);

        $dateTime = date('Ymd_His');
        $image = $request->file('image');
        $imageName = $dateTime . '-' . $image->getClientOriginalName();
        $savePath = public_path('/upload/');
        $image->move($savePath, $imageName);

        $insert = ['line1' => $request->line1, 'line2' => $request->line2, 'image' => $imageName ];
        Slider::create($insert);

        $data = [
            'response' => "Slider created",
        ];
        $status = 200;

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function addVolunteer(Request $request)
    {
        $instance = User::select('id')->where('user_type', 'admin')->first();

        $rules['name'] = 'required|string';
        $rules['image'] = 'required|image';
        // $rules['name'] = 'required|string';
        $this->validate($request, $rules);

        $insert = [];

        $dateTime = date('Ymd_His');
        $name = $request->name;
        $image = $request->file('image');
        $imageName = $dateTime . '-' . $image->getClientOriginalName();
        $savePath = public_path('/upload/');
        $image->move($savePath, $imageName);

        $insert = ['created_by' => $instance->id, 'created_ip' => $request->ip(), 'image' => $imageName, 'name' => $name ];
        Volunteer::insert($insert);

        $data = [
            'response' => "Data inserted",
        ];
        $status = 200;

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function updatePassword(Request $request)
    {
        $token = $request->token;
        $instance = User::where('reset_token', $token)->where('reset_tkn_status', 'active')->firstOrFail();

        // $rules = User::validationRules();
        // $rules['updated_ip'] = 'required|ip';

        // $this->validate($request, $rules);
        $updateData = ['updated_by' => $instance->id, 'updated_ip' => $request->ip(), 'reset_tkn_status' => 'disabled', 'password' => addslashes($request->password)];
        $instance->update($updateData);

        $data = [
            'response' => "Your password has been updated successfully",
        ];

        $result = $this->successResponse($request, $data, 200);
        return $result;
    }

    public function updateAdminPassword(Request $request)
    {
        $current = $request->current_password;
        $password = $request->new_password;
        $instance = User::where('user_type', 'admin')->where('password', $current)->first();
        $counter = User::where('user_type', 'admin')->where('password', $current)->count();
        if ($counter == 0) {
            $data = [
                'response' => "Current password is incorrect",
            ];
            $result = $this->successResponse($request, $data, 400);
            return $result;
        } else {
            $updateData = ['updated_by' => $instance->id, 'updated_ip' => $request->ip(), 'password' => addslashes($password)];
            $instance->update($updateData);

            $data = [
                'response' => "Your password has been updated successfully",
            ];

            $result = $this->successResponse($request, $data, 200);
            return $result;
        }

    }

    public function contactSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {
            $subject = 'Contact Us in Sehat Booking';

            $newsletterData = Newsletter::findOrFail(2);

            $bodyText = "";
            $template = $newsletterData->body;
            $bodyText = str_replace('$first_name', $request->first_name, $template);
            $bodyText = str_replace('$last_name', $request->last_name, $bodyText);
            $bodyText = str_replace('$email', $request->email, $bodyText);
            $bodyText = str_replace('$subject', $request->subject, $bodyText);
            $bodyText = str_replace('$message', $request->message, $bodyText);


            $this->sendEmailToUser($bodyText, $subject, addslashes($request->email));

            // $data = [
            //     'response' => "Thank you for contacting us! Your message has been successfully submitted 
            //     and our team will get back to you as soon as possible. We appreciate your interest and look 
            //     forward to speaking with you.",
            // ];

            $data = [
                'response' => "Your query has been submitted to admin.",
            ];

            $result = $this->successResponse($request, $data, 200);
            return $result;
        }
    }

    public function userViews(Request $request)
    {

        $subject = 'Sehat Booking User Views';

        $counters = Log::whereDate('created_at', Carbon::today())->distinct()->count('ip_address');
        $bodyText = "Total Users who visited website are: " . $counters;
        $adminEmail = "sehatbooking@gmail.com";

        $this->sendEmailToUser($bodyText, $subject, $adminEmail);

        $data = [
            'response' => "Your query has been submitted to admin.",
        ];

        $result = $this->successResponse($request, $data, 200);
        return $result;
    }

    // TODO send sms to 10 random blood donor whose notifications are turned on
    public function bloodRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'user_id' => 'required|numeric',
            'type' => 'required|in:normal,emergency',
            'blood' => 'required|string',
            'phone' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'city_area' => 'required|string',
            // 'ip' => 'required|ip',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {
            $userId = !empty($request->user_id) ? $request->user_id : 0;
            $blood = BloodGroup::select('id')->where('name', $request->blood)->firstOrFail();
            $state = State::select('id')->where('name', $request->state)->firstOrFail();
            $city = City::select('id')->where('name', $request->city)->firstOrFail();
            $cityArea = CityArea::select('id')->where('name', $request->city_area)
                ->where('city_id', $city->id)->firstOrFail();

            $userObject = [
                "created_by" => $userId,
                "created_ip" => $request->ip(),
                "type" => addslashes($request->type),
                "phone" => $request->phone,
                "blood_id" => $blood->id,
                "state_id" => $state->id,
                "city_id" => $city->id,
                "city_area_id" => $cityArea->id,
            ];

            $newResult = BloodRequest::create($userObject);

            $data = [
                'response' => "Your blood request has been generated successfully",
            ];

            $result = $this->successResponse($request, $data, 200);
            return $result;
        }
    }

    // TODO response back to recipient
    public function addSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {

            $data = [
                'email' => addslashes($request->email),
                'created_ip' => $request->ip(),
            ];

            $counter = Subscription::where('email', addslashes($request->email))
                ->count();

            if ($counter == 0) {
                Subscription::create($data);
            }

            // $subject = 'Contact Us in Sehat Booking';

            // $newsletterData = Newsletter::findOrFail(2);

            // $bodyText = "";
            // $template = $newsletterData->body;
            // $bodyText = str_replace('$first_name', $request->first_name, $template);
            // $bodyText = str_replace('$last_name', $request->last_name, $bodyText);
            // $bodyText = str_replace('$email', $request->email, $bodyText);
            // $bodyText = str_replace('$subject', $request->subject, $bodyText);
            // $bodyText = str_replace('$message', $request->message, $bodyText);


            // $this->sendEmailToUser($bodyText, $subject, addslashes($request->email));

            $data = [
                'response' => "You have been subscribed to our newsletter.",
            ];

            $result = $this->successResponse($request, $data, 200);
            return $result;
        }
    }

    public function areaSuggestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'required|string',
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        } else {
            $city = City::select('id')->where('name', 'like', '%' . $request->city . '%')->first();

            $result = CityArea::select('name')->where('city_id', $city->id)->where('name', 'like', '%' . $request->name . '%')->get();
            $counter = count($result);
            $counter > 0 ? ($status = 200) : ($status = 404);

            $data = [
                'response' => $result,
            ];

            $result = $this->successResponse($request, $data, $status);
            return $result;
        }
    }

    public function updateNamazTimeById(Request $request)
    {
        $instance = Namaz::findOrFail($request->id);
        $update = ["time" => $request->time ];
        $instance->update($update);

        $status = 200;
        $message = "Record updated";
        $data = [
            'response' => $message,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function updateServiceById(Request $request)
    {
        $instance = Service::findOrFail($request->id);
        $update = ["name" => $request->name, "description" => $request->description ];
        $instance->update($update);

        $status = 200;
        $message = "Record updated";
        $data = [
            'response' => $message,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function deleteServiceById(Request $request)
    {
        $result = Service::findOrFail($request->id)->delete();

        $status = 200;
        $message = "Record deleted";
        $data = [
            'response' => $message,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function deleteVolunteerById(Request $request)
    {
        $result = Volunteer::findOrFail($request->id)->delete();

        $status = 200;
        $message = "Record deleted";
        $data = [
            'response' => $message,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function deleteDonorById(Request $request)
    {
        $result = User::whereIn('user_type', ['donor', 'both'])->findOrFail($request->id)->delete();

        $status = 200;
        $message = "Record deleted";
        $data = [
            'response' => $message,
        ];

        $result = $this->successResponse($request, $data, $status);
        return $result;
    }

    public function sendEmailToUser($bodyText, $subject, $recipient)
    {
        try {
            $sender = 'no-reply@sehatbooking.com';
            $site = 'Sehat Booking';
            // $admin = "raza.yasir95@gmail.com";

            Mail::send([], [], function ($message) use ($bodyText, $subject, $recipient, $sender, $site, ) {
                $message->from($sender, $site);
                $message
                    ->to($recipient)
                    ->subject($subject)
                    ->html($bodyText);
            });

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}