<?php

namespace App\Http\Controllers\Permission;

use App\Models\Member;
use App\Models\Menu;
use App\Models\Service;
use App\Models\Role;
use App\Models\Permission;

use App\Utils\Messages;
use App\Utils\CommonFunc;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\PermissionRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

use PhpParser\Node\Stmt\TryCatch;

class PermissionController extends Controller
{
    /**
     * Register menu
     * @param  \App\Http\Requests\Menu\MenuRegisterController\MenuCreateRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getMenu(PermissionRequest $request){

        try {
            $validated = $request->validated();

            $roles = Role::get();
            $services = Service::where('service_no', '!=', 1)->where('service_use_yn', 'y')->get();
            $permission = Permission::where('role_no', isset($validated['role_no']) ? $validated['role_no'] : $roles[0]->role_no);

            if(isset($validated['menu_device'])){
                $permission->where(function($q) use($validated){
                    $q->where('menu_device', $validated['menu_device'])->orWhere('menu_device', '전체');
                });
            }

            $permission = $permission->get();
            // if(isset($validated['service_no']) && $permission->count() > 0 && $validated['service_no'] != 1){
            //     $permission = $permission->filter(function ($item) use($validated){

            //         $menu_no = $item->menu_no;
            //         $menu = Menu::where('menu_no', $menu_no)->first();
            //         if(isset($menu->menu_no)){
            //             $service_no_array = $menu->service_no_array;
            //             $service_no_array = explode(" ", $service_no_array);

            //             $check = in_array($validated['service_no'], $service_no_array);
            //             return $check;
            //         }else {
            //             return false;
            //         }

            //     })->values();
            // }

            $array_menu_no = [];
            foreach($permission as $per){
                $array_menu_no[] = $per->menu_no;
            }

            $menu = Menu::with('menu_parent')->where(function($q) use($validated){
                if($validated['menu_device'] != 'all')
                    $q->where('menu_device', $validated['menu_device'])->orWhere('menu_device', '전체');
            })->where(function($q) use($validated){
                $q->where('menu_depth', '리스트')->orwhere('menu_depth', '상세');
            })->where('menu_use_yn', 'y')->orderBy('menu_id')->get();

            if (isset($validated['service_no']) && $validated['service_no'] != 1 && $validated['service_no'] != 0) {
                $menu = $menu->filter(function ($item) use ($validated) {
                    $service_no_array = $item->service_no_array;
                    $service_no_array = explode(" ", $service_no_array);


                    return in_array($validated['service_no'], $service_no_array);
                })->values();
            }

            if (isset($validated['service_no']) && $validated['service_no'] == 0) {
                $menu = $menu->filter(function ($item) use ($validated,$services) {
                    // $service_no_array = $item->service_no_array;
                    // $service_no_array = explode(" ", $service_no_array);
                    $services_array = '1 ';
                    foreach($services as $row){
        
                        $services_array .= $row['service_no'].' ';
                        
                    }
                    $services_array = rtrim($services_array," ");
                    return $item->service_no_array == $services_array;
                })->values();
            }

            $menu_selected = [];
            foreach($menu as $key=>$per){
                if(in_array($per['menu_no'] , $array_menu_no)){
                    $menu_selected[] = $key;
                }
            }
         
            return response()->json([
                'menu' => $menu,
                'roles' => $roles,
                'services' => $services,
                'menu_selected' => $menu_selected,
                'validate' => $validated,
            ]);
        }catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }

    }

    public function savePermission(PermissionRequest $request){
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $ids = [];
            if(isset($validated['menu'])){
            foreach($validated['menu'] as $menu){
                $is_exist = Permission::where('role_no', $validated['role_no'])
                ->where('menu_no', $menu['menu_no'])
                ->where('menu_device', $menu['menu_device'])->first();
                if(!$is_exist){
                    Permission::insertGetId([
                        'role_no' => $validated['role_no'],
                        'menu_no' => $menu['menu_no'],
                        'menu_device' => $menu['menu_device'],
                        'service_no' => $validated['service_no']
                    ]);
                }
                $ids[] = $menu['menu_no'];
            }
            
            Permission::where('role_no', $validated['role_no'])
            ->where('menu_device', $menu['menu_device'])
            ->whereNotIn('menu_no', $ids)->delete();

            } else {
                $menu_device = $validated['menu_device'] == 'all' ? '전체' : $validated['menu_device'];
                Permission::where('role_no', $validated['role_no'])
                ->where('menu_device', $validated['menu_device'])
                ->whereNotIn('menu_no', $ids)->delete();
            }


            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'device' => $menu['menu_device']
            ], 201);

        }catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }

    }

}
