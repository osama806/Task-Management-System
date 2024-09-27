<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DeleteUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\RetriveUserRequest;
use App\Http\Requests\Auth\UpdateProfileUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ResponseTrait;
    protected $authService;

    /**
     * Create a new class instance.
     * @param \App\Services\AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Get all users from storage
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function index()
    {
        $response = $this->authService->index();
        return $response['status']
            ? $this->getResponse('users', $response['users'], 200)
            : $this->getResponse('error', $response['msg'], $response['code']);
    }

    /**
     * Create a new user in storage.
     * @param \App\Http\Requests\Auth\RegisterUserRequest $registerFormRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function register(RegisterUserRequest $registerFormRequest)
    {
        $validatedData = $registerFormRequest->validated();
        $response = $this->authService->register($validatedData);
        if ($response['status']) {
            if ($response['role']) {
                // If registration is successful and a role is assigned
                return $this->getResponse("msg", "Registration successful as " . $response['role'], 201);
            } else {
                // If registration is successful but no role is assigned
                return $this->getResponse("msg", "User registered successfully", 201);
            }
        } else {
            // If registration failed
            return $this->getResponse("msg", $response['msg'], $response['code']);
        }
    }

    /**
     * Check if user authorize or unAuthorize
     * @param \App\Http\Requests\Auth\LoginUserRequest $loginFormRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function login(LoginUserRequest $loginFormRequest)
    {
        $validatedData = $loginFormRequest->validated();
        $response = $this->authService->login($validatedData);
        return $response['status']
            ? $this->getResponse("token", $response['token'], 201)
            : $this->getResponse("msg", $response['msg'], $response['code']);
    }

    /**
     * To make logout for user if be authorize
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken());
            return $this->getResponse("msg", "User logged out successfully", 200);
        } catch (JWTException $e) {
            // throw new JWTException("Failed to logout, please try again", 500);
            return $this->getResponse("msg", "Failed to logout, please try again", 500);
        }
    }


    /**
     * Get user profile data
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function show()
    {
        $user = Auth::user();
        return $this->getResponse("profile", new UserResource($user), 200);
    }

    /**
     * Update user profile in storage
     * @param \App\Http\Requests\Auth\UpdateProfileUserRequest $updateProfileFormRequest
     * @param \App\Models\User $user
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function updateProfile(UpdateProfileUserRequest $updateProfileFormRequest, User $user)
    {
        $validatedData = $updateProfileFormRequest->validated();
        $response = $this->authService->updateProfile($validatedData, $user);
        return $response['status']
            ? $this->getResponse("msg", "User updated profile successfully", 200)
            : $this->getResponse("msg", $response['msg'], $response['code']);
    }

    /**
     * Delete auth user from storage.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function deleteUser()
    {
        $response = $this->authService->deleteUser();
        return $response['status']
            ? $this->getResponse("msg", "User deleted successfully", 200)
            : $this->getResponse("msg", $response['msg'], $response['code']);
    }

    /**
     * Get list of users that soft deleted
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function showDeletedUsers()
    {
        if (Auth::user()->role === null) {
            return $this->getResponse('error', "You can't access to this permission", 400);
        }
        $users = User::onlyTrashed()->get();
        return $this->getResponse('deleted-users', UserResource::collection($users), 200);
    }

    /**
     * Retrive user after deleted
     * @param mixed $email
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function restoreUser(RetriveUserRequest $retriveFormRequest)
    {
        $validated = $retriveFormRequest->validated();
        $response = $this->authService->restoreUser($validated);
        return $response['status']
            ? $this->getResponse('msg', 'User restored successfully', 200)
            : $this->getResponse('msg', $response['msg'], $response['code']);
    }

    /**
     * Force delete user from storage.
     * @param \App\Http\Requests\Auth\DeleteUserRequest $deleteUserRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function forceDeleteUser(DeleteUserRequest $deleteUserRequest)
    {
        $validatedData = $deleteUserRequest->validated();
        $user = User::where('email', $validatedData['email'])->first();
        if (!$user) {
            $user = User::onlyTrashed()->where('email', $validatedData['email'])->first();
            if (!$user) {
                return $this->getResponse('error', 'User Not Found', 404);
            }
        }
        $user->forceDelete();
        return $this->getResponse('msg', 'Deleted user permanently', 200);
    }
}
