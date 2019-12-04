<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Role;
use App\Relationship;
use Carbon\Carbon;
use App\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        return view('index');
    }

    public function login()
    {
        // validator
        $validator = Validator::make($this->request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->responseRequestError($errors);
        }

        $user = User::where('username', $this->request->input('username'))->first();

        if ($user) {

            $role = Role::where('id', $user->role_id)->first();
            $relationship = Relationship::where('id', $user->relationship_id)->first();
            if (Hash::check($this->request->input('password'), $user->password)) {
                $token = $this->jwt($user);
                // dd($token);
                $user->token = $token;
                $user->last_login_date = Carbon::now();
                $user->save();

                $user->role_name = $role['name'];
                $user->relationship_name = $relationship['name'];
                // dd($user->role_id);
                if ($user->role_id == '2') {

                    return $this->responseRequestSuccess($user)
                        ->withCookie(cookie('role_number', $user->role_id, 60))
                        ->withCookie(cookie('car_num', $user->car_id, 60));
                } else {
                    return $this->responseRequestSuccess($user)
                        ->withCookie(cookie('role_number', $user->role_id, 60));
                }
            } else {
                return $this->responseRequestError('incorrect_password');
            }
        } else {
            return $this->responseRequestError('no_user');
        }
    }
    /*
    |--------------------------------------------------------------------------
    | response เมื่อข้อมูลส่งถูกต้อง
    |--------------------------------------------------------------------------
     */
    protected function responseRequestSuccess($ret)
    {
        return response()->json(['status' => 'success', 'data' => $ret], 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }
    /*
    |--------------------------------------------------------------------------
    | response เมื่อข้อมูลมีการผิดพลาด
    |--------------------------------------------------------------------------
     */
    protected function responseRequestError($status = '', $ret = '', $message = 'Bad request', $statusCode = 200)
    {
        return response()->json(['status' => $status, 'data' => $ret, 'error' => $message], $statusCode)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }
    /*
    |--------------------------------------------------------------------------
    | ตัวเข้ารหัส JWT
    |--------------------------------------------------------------------------
     */
    protected function jwt($user)
    {
        $payload = [
            'iss' => "lumen-jwt", // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + env('JWT_EXPIRE_HOUR') * 60 * 60, // Expiration time
        ];
        return JWT::encode($payload, env('JWT_SECRET'));
    }
}
