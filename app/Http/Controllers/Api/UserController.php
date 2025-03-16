<?php

namespace App\Http\Controllers\Api;

use App\Domain\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index() {

        $response = $this->userService->searchUsers();

        return response()->json($response->toArray(), $response->getStatusCode());
    }

    public function show(User $user) {
        $response = $this->userService->findUser($user);

        return response()->json($response->toArray(), $response->getStatusCode());
    }
    public function store(UserRequest $userRequest) {

        $validatedDate = $userRequest->validated();

        $response = $this->userService->createUser($validatedDate);

        return response()->json($response->toArray(), $response->getStatusCode());
    }

    public function destroy(User $user) {

        $response = $this->userService->destroy($user);

        return response()->json($response->toArray(), $response->getStatusCode());
    }

    public function update(UserRequest $userRequest,User $user) {

        $validatedDate = $userRequest->validated();

        $response = $this->userService->update($user, $validatedDate);

        return response()->json($response->toArray(), $response->getStatusCode());
    }
}
