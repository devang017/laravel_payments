<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    protected $userModel;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->userModel = new User();
    }

    public function updateUser(string $id, array $userData)
    {
        $this->userModel->where('id', $id)->update($userData);
    }
}
