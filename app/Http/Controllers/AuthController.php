<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate props:
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        // Getting props:
        [$login, $password] = [$request->get('login'), $request->get('password')];

        // Validate prop data:
        /** @var User|null */
        $user = User::where('name', $login)->first();

        if(is_null($user) || !Hash::check($password, $user->password)) {
            return response([ 'message' => 'The provided credentials are incorrect' ], 403);
        }

        return response([ 'token' => $user->createToken($login)->plainTextToken, 'id' => $user->id ]);
    }
}
