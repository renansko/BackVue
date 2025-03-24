<?php

namespace App\Domain\Services;

use App\Http\Responses\ApiModelErrorResponse;
use App\Http\Responses\ApiModelResponse;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService
{
    public function login(array $credentials): JsonResponse|ApiModelErrorResponse
    {
        if (!Auth::attempt($credentials)) {
            return new ApiModelErrorResponse(
                'E-mail or password invalid', 
                new UnauthorizedHttpException('Bearer', 'Invalid credentials'),
                [],
                403
            );
        }


        $user = User::where('email', $credentials['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'status'    => true,
            'name'      => Auth::user()->name,
            'email'     => Auth::user()->email,
            'token'     =>  $token 
        ], 200)->withCookie('token', $token, 60 * 24 * 30, null, null, false, true);
      
    }


    public function logout(): ApiModelResponse|ApiModelErrorResponse
    {
        try {
            Auth::user()->tokens()->delete();
            return new ApiModelResponse('Logout successful', [], 200);
        } catch (Exception $e) {
            return new ApiModelErrorResponse(
                'Error during logout',
                $e,
                [],
                500
            );
        }
    }
}