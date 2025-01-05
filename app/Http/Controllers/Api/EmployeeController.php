<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserType;
use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends BaseController
{
    public function index()
    {
        $user = User::with('company')->find(auth()->user()->id);
        return $this->sendResponse($user, 'Success get profile', 200);
    }

    public function showAll()
    {
        $users = User::where('company_id', auth()->user()->company_id)
            ->where('role', '=', UserType::Employee)
            ->paginate(10);

        return $this->sendResponse($users, 'Success get all employees', 200);
    }
}
