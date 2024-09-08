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
        [$login, $password] = [$request->get('login'), $request->get('password')];

        // Validate prop data:
        /** @var User|null */
        $user = User::where('name', $login)->first();

        if(is_null($user) || !Hash::check($password, $user->password)) {
            return Response::badRequest('The provided credentials are incorrect');
        }
        $response = [ 'token' => $user->createToken($login)->plainTextToken, 'id' => $user->id ];
        return Response::json($response);
    }
}
