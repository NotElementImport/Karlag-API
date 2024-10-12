<?php

namespace App\Http\Controllers;

use App\Models\Global\Response;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'login' => 'required',
                'password' => 'required',
            ]);

        if($validate->fails()) {
            return Response::badRequest($validate->errors()->toArray());
        }

        // Getting props:
        [$login, $password] = [$request->input('login'), $request->input('password')];

        // Validate prop data:
        $user = User::where('name', $login)->first();

        if(is_null($user) || !Hash::check($password, $user->password))
            return Response::badRequest('Не правильные данные для входа');

        $response = [ 'token' => $user->createToken($login)->plainTextToken, 'id' => $user->id ];

        return Response::okJSON($response);
    }
}
