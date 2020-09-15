<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public $successStatus = 200;
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
        $success['name'] =  $user->name;

        return response()->json(['success' => $success], $this->successStatus);
    }
    public function login(Request $request)
    {
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = Auth::user();
            $success = $user->createToken('MyApp')->accessToken;

            return response()->json($success, $this->successStatus);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }
    public function details()
    {
        $user = Auth::user();
        return $user;
    }
}
