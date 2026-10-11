<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
     */

    use AuthenticatesUsers;

    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;
    protected $moduleUtil;
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }
    // public function login(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'username' => 'required|string|max:255',
    //         'password' => 'required|string|min:6',
    //     ]);

    //     if ($validator->fails()) {
    //         return response(['errors' => $validator->errors()->all()], 422);
    //     }

    //     $user = User::where('username', $request->username)->first();
    //     return $user;

    //     if ($user) {
    //         if (Hash::check($request->password, $user->password)) {
    //             $token = $user->createToken('Laravel Password Grant Client')->accessToken;
    //             $response = ['token' => $token];
    //             return response($response, 200);
    //         } else {
    //             $response = ["message" => "Password mismatch"];
    //             return response($response, 422);
    //         }
    //     } else {
    //         $response = ["message" => 'User does not exist'];
    //         return response($response, 422);
    //     }
    // }

    public function logout()
    {
        $user_id = request()->session()->get('user.id');
        //User::where('id', $user_id)->update(['already_login' => 'false']);
        //$user = User::where('id', $user_id)->first();
        //$user->tokens()->delete();
        request()->session()->flush();
        \Auth::logout();
        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //return $user;
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                // if (!$user->can('sell.pos.add')) {
                //     \Auth::logout();
                //     return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.not_allowed_to_add_pos_sell')]);
                // }
                // $is_superadmin = $user->can('superadmin');
                // if (!$this->moduleUtil->isModuleEnabled('product_module', $user->business_id) && !$is_superadmin) {
                //     \Auth::logout();
                //     return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.not_allowed_to_use_product_module')]);
                // }
                /* $business_id = $user->business_id;
                $user_id = $user->id;
                $selectedBusinessLocation = CashRegister::where('business_id', $business_id)->where('user_id', $user_id)
                ->where('status', 'open')->where('closed_at', '=', null)->first();
                if (!empty($selectedBusinessLocation)) {
                $location_id = $selectedBusinessLocation->location_id ?? null;
                $call_register = CashRegister::where('business_id', $business_id)->where('location_id', $location_id)->where('status', 'open')->first();
                if ($call_register) {
                $register_user_id = $call_register->user_id ?? null;
                $status = $call_register->status ?? null;
                \Auth::logout();
                if (($register_user_id != $user_id) && ($status = 'open')) {
                return redirect('/login')->with('status', ['success' => 0, 'msg' => 'Unauthorized action! You Cannot open same register. Register Already open by another user.']);
                }
                return redirect('/login')->with('status', ['success' => 0, 'msg' => 'Register Already opened']);
                } else {
                return redirect('/login')->with('status', ['success' => 0, 'msg' => 'User login and register Already opened.']);
                }
                } */

                // if ($user && $user->already_login == 'true') {
                //     \Auth::logout();
                //     return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.logout_other_devices')]);
                // }
                //User::where('id', $user->id)->update(['already_login' => 'true']);
            } else {
                \Auth::logout();
                return redirect('/login')->with('status', ['success' => 0, 'msg' => __('user.password_mismatch')]);
            }
        } else {
            \Auth::logout();
            return redirect('/login')->with('status', ['success' => 0, 'msg' => __('user.user_not_exist')]);
        }
        if (!$user->business->is_active) {
            \Auth::logout();
            return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.business_inactive')]);
        } elseif ($user->status != 'active') {
            \Auth::logout();
            return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.user_inactive')]);
        } elseif (!$user->allow_login) {
            \Auth::logout();
            return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.login_not_allowed')]);
        } elseif (($user->user_type == 'user_customer') && !$this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
            \Auth::logout();
            return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.business_dont_have_crm_subscription')]);
        } elseif ($user && $user->already_login == 'true') {
            \Auth::logout();
            return redirect('/login')->with('status', ['success' => 0, 'msg' => __('lang_v1.logout_other_devices')]);
        }

    }

    protected function redirectTo()
    {
        $user = \Auth::user();
        if (!$user->can('dashboard.data') && $user->can('sell.pos.add')) {
            return '/pos/new';
        }
        if ($user->user_type == 'user_customer') {
            return 'contact/contact-dashboard';
        }
        return '/home';
    }
}