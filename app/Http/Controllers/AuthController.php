<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'username' => 'required',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->accessToken;

        return response()->json(['success' => $success], 200);
    }
    public function login(Request $request)
    {
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = Auth::user();
            $success = $user->createToken('MyApp')->accessToken;

            return response()->json($success, 200);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
        // $request->validate([
        //     'username' => 'required',
        //     'password' => 'required|string',
        // ]);
        // $credentials = request(['username', 'password']);
        // if (!Auth::attempt($credentials))
        //     return response()->json([
        //         'message' => 'Unauthorized'
        //     ], 401);
        // $user = $request->user();
        // $tokenResult = $user->createToken('Personal Access Token');
        // $token = $tokenResult->token;
        // if ($request->remember_me)
        //     $token->expires_at = Carbon::now()->addWeeks(1);
        // $token->save();
        // return response()->json([
        //     'access_token' => $tokenResult->accessToken,
        //     'token_type' => 'Bearer',
        //     'expires_at' => Carbon::parse(
        //         $tokenResult->token->expires_at
        //     )->toDateTimeString()
        // ]);
    }
    public function details()
    {
        $user = Auth::user();
        return $user;
    }
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
