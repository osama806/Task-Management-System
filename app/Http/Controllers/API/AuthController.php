<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginFormRequest;
use App\Http\Requests\Auth\RegisterFormRequest;
use App\Http\Requests\Auth\RetriveFormRequest;
use App\Http\Requests\Auth\UpdateProfileFormRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ResponseTrait;
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
     * @param \App\Http\Requests\Auth\RegisterFormRequest $registerFormRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function register(RegisterFormRequest $registerFormRequest)
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
     * @param \App\Http\Requests\Auth\LoginFormRequest $loginFormRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function login(LoginFormRequest $loginFormRequest)
    {
        $validatedData = $loginFormRequest->validated();
        $response = $this->authService->login($validatedData);
        return $response['status']
            ? $this->getResponse("token", $response['token'], 201)
            : $this->getResponse("msg", $response['msg'], $response['code']);
    }


    /**
     * Refresh token method
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function refresh()
    {
        $response = $this->authService->refreshToken();

        return $response['status']
            ? $this->getResponse('token', $response['token'], 200)
            : $this->getResponse('error', $response['msg'], $response['code']);
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
        $response = $this->authService->show();
        return $response['status']
            ? $this->getResponse("profile", $response['profile'], 200)
            : $this->getResponse("msg", "There is error in server", 500);
    }

    /**
     * Update user profile in storage
     * @param \App\Http\Requests\Auth\UpdateProfileFormRequest $updateProfileFormRequest
     * @param mixed $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function updateProfile(UpdateProfileFormRequest $updateProfileFormRequest, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->getResponse('error', 'Not Found This User', 404);
        }
        $validatedData = $updateProfileFormRequest->validated();
        $response = $this->authService->updateProfile($validatedData, $user);
        return $response['status']
            ? $this->getResponse("msg", "User updated profile successfully", 200)
            : $this->getResponse("msg", $response['msg'], $response['code']);
    }

    /**
     * Delete user from storage.
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
     * Retrive user after deleted
     * @param mixed $email
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function restoreUser(RetriveFormRequest $retriveFormRequest)
    {
        $validated = $retriveFormRequest->validated();
        $response = $this->authService->restoreUser($validated);
        return $response['status']
            ? $this->getResponse('msg', 'User restored successfully', 200)
            : $this->getResponse('msg', $response['msg'], $response['code']);
    }
}
