<?php

namespace App\Services;

use App\Models\User;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        if (Auth::user()->role !== 'admin') {
            return ['status' => false, 'msg' => "Can't access to this permission", 'code' => 400];
        }

        $users = User::with('task')->get();
        $data = [];

        foreach ($users as $user) {
            $tasks = [];

            if ($user->task->count() > 0) {
                foreach ($user->task as $t) {
                    $tasks[] = [
                        'title'       => $t->title,
                        'description' => $t->description,
                        'priority'    => $t->priority,
                        'status'      => $t->status,
                        'due_date'    => $t->due_date
                    ];
                }
            }

            $data[] = [
                'name'  => $user->name,
                'email' => $user->email,
                'tasks' => $tasks
            ];
        }

        return ['status' => true, 'users' => $data];
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
     * Get user profile data
     * @return array
     */
    public function show()
    {
        $user = Auth::user();
        if ($user->role !== null) {
            $data = [
                "name"          =>      $user->name,
                "email"         =>      $user->email,
                "role"          =>      $user->role
            ];
        } else
            $data = [
                "name"          =>      $user->name,
                "email"         =>      $user->email,
            ];
        return ['status'    =>      true, 'profile'     =>      $data];
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
