<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPUnit\Exception;

class ManagerController extends BaseController
{
    public function index(): JsonResponse
    {
        $user = User::with('company')->find(auth()->user()->id);
        return $this->sendResponse($user, 'Success get profile', 200);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|min:3|max:255',
                'email' => 'sometimes|unique:users,email,'. auth()->user()->id,
                'phone' => 'sometimes|unique:users,phone,'. auth()->user()->id,
                'address' => 'sometimes|min:3|max:255',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }

            $user = User::findOrFail(auth()->user()->id);

            $user->update($request->all());

            return $this->sendResponse($user, 'Profile Updated Successfully.', 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required',
                'new_password' => [
                    'required',
                    'min:8',
                    'confirmed',
                    function ($attribute, $value, $fail) use ($request) {
                        if (Hash::check($value, auth()->user()->password)) {
                            $fail('The new password must be different from the old password.');
                        }
                    },
                ]
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }

            $user = auth()->user();

            if (!Hash::check($request->old_password, $user->password)) {
                return $this->sendError('Old password is incorrect.', ['error' => 'Unauthorized'], 401);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return $this->sendResponse([], 'Password changed successfully.', 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), 500);
        }
    }

    public function showAll(): JsonResponse
    {
        $users = User::where('company_id', auth()->user()->company_id)->paginate(10);

        return $this->sendResponse($users, 'Success get all employees', 200);
    }

    public function show($id): JsonResponse
    {
        $user = User::where('company_id', auth()->user()->company_id)->findOrFail($id);

        return $this->sendResponse($user, 'Success get detail employee', 200);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3|max:255',
                'email' => 'required|email|unique:companies,email',
                'address' => 'required|min:3|max:255',
                'phone' => 'required|min:10|max:13|regex:/^([0-9\s\-\+\(\)]*)$/|unique:companies,phone',
                'role' => 'required|in:manager,employee'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }

            $generatedPassword = Str::random(12);

            $storeUserEmployee = User::create([
                'name'          => $request->name,
                'email'         => $request->email,
                'company_id'    => auth()->user()->company_id,
                'role'          => $request->role,
                'phone'         => $request->phone,
                'address'       => $request->address,
                'password'      => bcrypt($generatedPassword)
            ]);

            $storeUserEmployee->generatedPassword = $generatedPassword;

            return $this->sendResponse($storeUserEmployee, 'Company Created Successfully.', 201);
        } catch (QueryException $e) {
            return $this->sendError('Database Error', $e->getMessage(), 500);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|min:3|max:255',
                'email' => 'sometimes|unique:users,email,'.$id,
                'phone' => 'sometimes|unique:users,phone,'.$id,
                'address' => 'sometimes|min:3|max:255',
                'password' => 'sometimes|min:8',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }

            $user = User::findOrFail($id);

            $data = $validator->validated();

            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            return $this->sendResponse($user, 'Profile Employee Updated Successfully.', 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $user->delete();

            return $this->sendResponse([], 'Employee deleted successfully.', 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), 500);
        }
    }
}
