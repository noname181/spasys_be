<?php

namespace App\Http\Controllers\Role;

use App\Models\Role;
use App\Models\Member;
use App\Models\Company;
use App\Utils\Messages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Role\RoleRequest;

class RoleController extends Controller
{
    /**
     * Register Role
     * @param  App\Http\Requests\RoleRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(RoleRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $ids = [];
            $i = 0;
            foreach ($validated['roles'] as $val) {
                Log::error($val);
                $role = Role::updateOrCreate(
                    [
                        'role_no' => isset($val['role_no']) ? $val['role_no'] : null,
                    ],
                    [
                        'role_id' =>  isset($val['role_id']) ? $val['role_id'] : '',
                        'role_name' => $val['role_name'],
                        'role_eng' => $val['role_eng'],
                        'role_use_yn' => $val['role_use_yn'],
                        'role_type' => $val['role_type'],
                    ],
                );
                $ids[] = $role->role_no;
                $i++;
            }
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    public function getRoles()
    {
        try {
            $role = Role::get();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'roles' => $role
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }
    public function getRoles_member($mb_no)
    {
        try {
            $role = Role::get();
            $get_role_no = Member::where('mb_no','=',$mb_no)->first()->role_no;
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'roles' => $role,
                'get_role_no'=>$get_role_no
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    public function deleteRole(Role $role)
    {
        try {
            $role->delete();
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
}
