<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserType;
use App\Http\Controllers\BaseController;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Exception;

class AdminController extends BaseController
{
    public function storeCompany(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3|max:255',
                'email' => 'required|email|unique:companies,email',
                'address' => 'required|min:3|max:255',
                'phone' => 'required|min:10|max:13|regex:/^([0-9\s\-\+\(\)]*)$/|unique:companies,phone',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 400);
            }

            $storeCompany = Company::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'address'   => $request->address,
                'phone'     => $request->phone
            ]);

            $generatedPassword = Str::random(12);

            $storeManager = User::create([
                'name'          => 'manager of '.$request->name,
                'email'         => 'manager@'.Str::slug($request->name).'.com',
                'company_id'    => $storeCompany->id,
                'role'          => UserType::Manager,
                'phone'         => $request->phone,
                'address'       => $request->address,
                'password'      => bcrypt($generatedPassword)
            ]);

            $storeManager->generatedPassword = $generatedPassword;

            return $this->sendResponse(['Company' => $storeCompany, 'Manager' => $storeManager], 'Company Created Successfully.', 201);
        } catch (QueryException $e) {
            return $this->sendError('Database Error', $e->getMessage(), 500);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), 500);
        }
    }
}
