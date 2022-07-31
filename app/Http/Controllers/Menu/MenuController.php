<?php

namespace App\Http\Controllers\Menu;

use App\Http\Requests\Menu\MenuCreateRequest;
use App\Http\Requests\Menu\MenuSearchRequest;

use App\Models\Member;
use App\Models\Menu;
use App\Utils\Messages;
use App\Utils\CommonFunc;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

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

            if (isset($validated['service_no'])) {
                $menu->where('service_no', $validated['service_no']);
            }

            if (isset($validated['menu_depth'])) {
                $menu->where('menu_depth', $validated['menu_depth']);
            }

            if (isset($validated['menu_device'])) {
                $menu->where('menu_device', $validated['menu_device']);
            }

            if (isset($validated['menu_name'])) {
                $menu->where(function($query) use ($validated) {
                    $query->where('menu_name', 'like', '%' . $validated['menu_name'] . '%');
                });
            }
            $sql = $menu->toSql();

            $menu = $menu->paginate($per_page, ['*'], 'page', $page);


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

        $service_no_array = explode(" ", $validated['menu_service_no_array']);

        try {
            //DB::beginTransaction();

            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

            foreach ($service_no_array as $service_no){
                $menu_no = Menu::insertGetId([
                    'mb_no' => $member->mb_no,
                    'menu_name' => $validated['menu_name'],
                    'menu_depth' => $validated['menu_depth'],
                    'menu_url' => $validated['menu_url'],
                    'menu_device' => $validated['menu_device'],
                    'menu_use_yn' => $validated['menu_use_yn'],
                    'service_no' => $service_no,
                ]);
            }


            // DB::commit();
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
        $menu = Menu::where('menu_no', $menu_no)->first();
        return $menu;

    }
}
