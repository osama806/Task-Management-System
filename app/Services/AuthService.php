<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    use ResponseTrait;

    /**
     * Get list of users
     * @return array
     */
    public function index()
    {
        // just admin can show all users with their tasks
        if (Auth::user()->role !== 'admin') {
            return ['status' => false, 'msg' => "Can't access to this permission", 'code' => 400];
        }

        // Call the static method to get users with 'in-progress' tasks
        $users = User::getUsersWithTasksProgress();

        // get users through resource best of foreach loop
        return ['status' => true, 'users' => UserResource::collection($users)];
    }

    /**
     * Create a new user in storage.
     * @param array $data
     * @return array
     */
    public function register(array $data)
    {
        try {
            $role = null;

            // check if email request contains @admin OR @manager
            if (strpos($data['email'], '@admin') !== false) {
                $role = 'admin';
            } elseif (strpos($data['email'], '@manager') !== false) {
                $role = 'manager';
            }

            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => bcrypt($data['password']),
            ]);

            $user->role = $role;
            $user->save();
            return ['status' => true, 'role'    =>  $role ?? null];
        } catch (Exception $e) {
            Log::error('Error registering user: ' . $e->getMessage());
            return ['status' => false, 'msg' => $e->getMessage(), 'code' => 500];
        }
    }


    /**
     * Check if user authorize or unAuthorize
     * @param array $data
     * @return array
     */
    public function login(array $data)
    {
        $credentials = [
            "email"         =>      $data['email'],
            "password"      =>      $data['password']
        ];
        $token = JWTAuth::attempt($credentials);
        if (!$token) {
            return ['status'    =>  false, 'msg'    =>  "username or password is incorrect", 'code' =>  401];
        }
        return ["status"    =>  true, "token"   =>      $token];
    }

    /**
     * Update user profile in storage
     * @param array $data
     * @param \App\Models\User $user
     * @return array
     */
    public function updateProfile(array $data, User $user)
    {
        if (empty($data['name']) && empty($data['password'])) {
            return ['status' => false, 'msg' => 'Not Found Data in Request!', 'code' => 404];
        }
        try {
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            if (isset($data['password'])) {
                $user->password = bcrypt($data['password']);
            }
            $user->save();
            return ['status'    =>  true];
        } catch (Exception $e) {
            Log::error('Error update profile: ' . $e->getMessage());
            return ['status'    =>  false, 'msg'    =>  'Failed update profile for user. Try again', 'code' =>  500];
        }
    }

    /**
     * Delete user from storage.
     * @return array
     */
    public function deleteUser()
    {
        try {
            $user = User::find(Auth::id());
            if ($user) {
                $user->delete();
                return ['status' => true];
            }

            return [
                'status' => false,
                'msg' => 'User not found',
                'code' => 404,
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'msg' => $e->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Retrive user after deleted
     * @param mixed $userId
     * @return array
     */
    public function restoreUser(array $data)
    {
        try {
            $user = User::withTrashed()->where('email', $data['email'])->first();
            if (!$user) {
                return [
                    'status' => false,
                    'msg' => 'User Not Found',
                    'code' => 404,
                ];
            }
            if ($user->deleted_at === null) {
                return [
                    'status' => false,
                    'msg' => "This user isn't deleted",
                    'code' => 400,
                ];
            }
            $user->restore();
            return ['status' => true,];
        } catch (Exception $e) {
            return [
                'status' => false,
                'msg' => $e->getMessage(),
                'code' => 500,
            ];
        }
    }
}
