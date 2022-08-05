<?php

namespace App\Http\Controllers\Menu;

use App\Http\Requests\Menu\MenuCreateRequest;
use App\Http\Requests\Menu\MenuSearchRequest;
use App\Http\Requests\Menu\MenuUpdateRequest;

use App\Models\Member;
use App\Models\Menu;
use App\Models\Service;

use App\Utils\Messages;
use App\Utils\CommonFunc;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

use PhpParser\Node\Stmt\TryCatch;

class MenuController extends Controller
{
    /**
     * Register menu
     * @param  \App\Http\Requests\Menu\MenuRegisterController\MenuCreateRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function menu(MenuSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $menu = Menu::with('service')->orderBy('menu_no', 'DESC');



            if (isset($validated['menu_depth'])) {
                $menu->where('menu_depth', $validated['menu_depth']);
            }

            if (isset($validated['menu_device'])) {
                $menu->where(DB::raw('lower(menu_device)'), strtolower($validated['menu_device']));
            }

            if (isset($validated['menu_name'])) {
                $menu->where(function($query) use ($validated) {
                    $query->where('menu_name', 'like', '%' . $validated['menu_name'] . '%');
                });
            }
            $sql = $menu->toSql();

            $menu = $menu->paginate($per_page, ['*'], 'page', $page);

            if (isset($validated['service_no'])) {
                $service = Service::where('service_no', $validated['service_no'])->first();


                $menu->setCollection(
                    $menu->getCollection()->filter(function ($item) use ($service) {
                        $service_no_array = $item->service_no_array;
                        $service_no_array = explode(" ", $service_no_array);

                        return in_array($service->service_no, $service_no_array);
                    })
                );

            }


            // 'from_date' => date('Y-m-d H:i:s', strtotime($validated['from_date'])),
            // 'to_date' => date('Y-m-d 23:59:00', strtotime($validated['to_date']))

            return response()->json($menu);
        } catch (\Exception $e) {
            Log::error($e);
            //return response()->json(['message' => Messages::MSG_0018], 500);
            return $e;
        }
    }
    public function create(MenuCreateRequest $request)
    {


        $validated = $request->validated();

        try {
            DB::beginTransaction();


            $main_menu = Menu::first();
            if(!$main_menu){
                $main_menu_id = 101;
            }else {
                $main_menu_id = Menu::orderBy('main_menu_id', 'DESC')->first()->main_menu_id;
            }


            if($request->menu_parent_no){
                $sub_menu = Menu::where('menu_parent_no', $validated['menu_parent_no'])->orderBy('sub_menu_id', 'DESC')->first();

                if(!$sub_menu){
                    $sub_menu_id = 101;
                }else {
                    $sub_menu_id = $sub_menu->sub_menu_id + 1;
                }

            }else {
                $sub_menu_id = 100;
            }



            $menu_id = (string)$main_menu_id . (string)$sub_menu_id;

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

            $menu_no = Menu::insertGetId([
                'mb_no' => $member->mb_no,
                'menu_name' => $validated['menu_name'],
                'main_menu_id' => $main_menu_id,
                'sub_menu_id' => $sub_menu_id,
                'menu_id' => intval($menu_id),
                'menu_depth' => $validated['menu_depth'],
                'menu_parent_no' => $request->menu_parent_no ? $validated['menu_parent_no'] : NULL,
                'menu_url' => $validated['menu_url'],
                'menu_device' => $validated['menu_device'],
                'menu_use_yn' => $validated['menu_use_yn'],
                'service_no_array' => $validated['menu_service_no_array'],
                ]);



            DB::commit();
            // return $menu_no->toSql();
            return response()->json([
                'message' => Messages::MSG_0007,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
        }
    }

    public function get_menu($menu_no)
    {
        try {
            $menu = Menu::where('menu_no', $menu_no)->first();
            $menu_main = Menu::select(['menu_no', 'menu_name', 'service_no_array'])->where('menu_depth', '상위')->get();
            $services = Service::select(['service_no', 'service_name'])->where('service_use_yn', 'y')->get();

            return response()->json(['menu' => $menu, 'menu_main' => $menu_main, 'services' => $services]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    public function get_menu_main()
    {
        try {
            $menu_main = Menu::select(['menu_no', 'menu_name', 'service_no_array'])->where('menu_depth', '상위')->get();
            return response()->json($menu_main);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    public function update_menu(MenuUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $menu = Menu::where('menu_no', $validated['menu_no'])
                ->update($validated);

            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return $validated;
        }
    }
}
