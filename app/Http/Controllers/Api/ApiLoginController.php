<?php

namespace App\Http\Controllers\Api;

use App;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Passport;

class ApiLoginController extends Controller
{

    public function login(Request $request)
    {

        try {
            $input = $request->all();
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255',
                'password' => 'required|string',
            ]);
            //return response()->json(['success' => 0, 'input' => $input], 200);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors()->all()], 422);
            }
            $user = User::where('username', $input['username'])->first();
            if ($user) {
                $rememberme = $input['rememberme'] ?? 0;
                if (Hash::check($input['password'], $user->password)) {
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
                    if (($register_user_id != $user_id) && ($status = 'open')) {
                    return response()->json(['success' => 0, 'errors' => 'Unauthorized action! You Cannot open same register. Register Already open by another user..'], 422);
                    }
                    return response()->json(['success' => 1, 'msg' => 'Register Already opened', 'call_register' => $call_register], 422);
                    } else {
                    return response()->json(['success' => 1, 'msg' => 'User login and register Already opened.', 'call_register' => $call_register], 422);
                    }
                    } */

                    // if ($user && $user->already_login == 'true') {
                    //     return response(['success' => 0, 'msg' => __('lang_v1.logout_other_devices')], 422);
                    // }
                    // if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                    //     return response(['success' => 0, 'msg' => __('lang_v1.logout_other_devices')], 422);
                    // }
                    if ($rememberme) {
                        Passport::personalAccessTokensExpireIn(now()->addHours(24));
                        //Passport::personalAccessTokensExpireIn(now()->addDays(2));
                    } else {
                        Passport::personalAccessTokensExpireIn(now()->addHours(12));
                    }
                    $user->tokens()->delete();
                    $token = $user->createToken('Fastich Password Grant Client')->accessToken;
                    foreach ($user->tokens as $token) {
                        $user['tokens'] = $token ?? "";
                        $user['client_id'] = $token->client_id ?? "";
                        $user['tokenID'] = $token->id ?? "";
                    }
                    $user_language = $user->language ?? 'ar';
                    App::setLocale($user_language);

                    //User::where('id', $user->id)->update(['already_login' => 'true']);
                    return response(['success' => 1, 'user' => $user], 200);
                } else {
                    return response(['success' => 0, 'msg' => __('user.password_mismatch')], 422);
                }
            } else {
                return response(['success' => 0, 'msg' => __('user.user_not_exist')], 422);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => $e->getMessage()], 500);
            //return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);

        }

    }
    public function logout(Request $request)
    {
        /* $locale = config('app.locale');
        return $locale; */

        try {
            $user_id = $request->get('user_id') ?? "";
            $user = User::where('id', $user_id)->with(['media'])->first();
            $user_language = $user->language ?? 'ar';
            App::setLocale($user_language);

            //$accessToken = $user->tokens ?? "";
            // return response()->json(['accessToken' => $accessToken], 200);

            if (($user) && ($user->tokens) && ((count($user->tokens) > 0))) {
                // User::where('id', $user_id)->update(['already_login' => 'false']);
                // Revoke all tokens...
                $user->tokens()->delete();
                return response()->json(['success' => 1, 'msg' => __('user.logout')], 200);
            } else {
                return response()->json(['success' => 0, 'msg' => __('user.already_logout')], 422);
            }

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);

        }

    }
    public function register(Request $request)
    {
        /* $validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:6|confirmed',
    ]);
    if ($validator->fails()) {
    return response(['errors' => $validator->errors()->all()], 422);
    }
    $request['password'] = Hash::make($request['password']);
    $request['remember_token'] = Str::random(10);
    $user = User::create($request->toArray());
    $token = $user->createToken('Fastich Password Grant Client')->accessToken;
    $response = ['token' => $token];
    return response($response, 200); */
    }
}