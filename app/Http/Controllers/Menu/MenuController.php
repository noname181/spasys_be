<?php

namespace App\Http\Controllers\Menu;

use App\Http\Requests\Menu\MenuCreateRequest;
use App\Http\Requests\Menu\MenuSearchRequest;
use App\Http\Requests\Menu\MenuUpdateRequest;

use App\Models\Member;
use App\Models\Menu;
use App\Models\Manual;
use App\Models\Service;
use App\Models\Company;
use App\Models\Alarm;

use App\Utils\Messages;
use App\Utils\CommonFunc;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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
            $menu = Menu::with(['service','manual','menu_parent'])->where('menu_url','!=','popup')->where('menu_depth','!=','상위')->orderBy('main_menu_level', 'ASC')->orderBy('sub_menu_level', 'ASC');



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


            if (isset($validated['service_no']) && $validated['service_no'] != '1') {
                if($validated['service_no'] == '99999999'){
                    $menu->where('service_no_array','=','2 3 4')->orwhere('service_no_array','=','1 2 3 4');
                } else {
                $collection = $menu->get();

                $filtered = $collection->filter(function($item) use($validated){
                  
                    $service_no_array = $item->service_no_array;
                    $service_no_array = explode(" ", $service_no_array);
                   
                    return in_array($validated['service_no'], $service_no_array);
                       
                });
                $data = $this->paginate($filtered, $validated['per_page'], $validated['page']);
                return response()->json($data);
                }
             
            
            }

            

            $collection = $menu->get();

            $data = $this->paginate($collection, $validated['per_page'], $validated['page']);

            


            // 'from_date' => date('Y-m-d H:i:s', strtotime($validated['from_date'])),
            // 'to_date' => date('Y-m-d 23:59:00', strtotime($validated['to_date']))

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);

        }
    }
    public function create(MenuCreateRequest $request)
    {


        $validated = $request->validated();

        try {
            DB::enableQueryLog();
            DB::beginTransaction();
            // if (isset($validated['menu_url'])) {
            // $menu_check = Menu::whereRaw('LOWER(`menu_url`) = ? ',[strtolower($validated['menu_url'])])->first();
            // if(isset($menu_check)){
            //     return response()->json([
            //         'error'=>'same_url'
            //     ], 201);
            // }
            // }
            // if (isset($validated['menu_name']) && !isset($validated['menu_url'])) {
            //     $service_no_array = explode(" ", $validated['menu_service_no_array']);
            //     foreach($service_no_array as $row){
            //         $menu_check_name = Menu::whereRaw('LOWER(`menu_name`) = ? ',[strtolower($validated['menu_name'])])->first();
            //         if(isset($menu_check_name)){
            //             return response()->json([
            //                 'error'=>'same_name'
            //             ], 201);
            //         }
            //     }
            // }
            // if (isset($validated['menu_name']) && isset($validated['menu_url'])) {
            //     $service_no_array = explode(" ", $validated['menu_service_no_array']);
            //     foreach($service_no_array as $row){
            //         $menu_check_name = Menu::whereRaw('LOWER(`menu_name`) = ? ',[strtolower($validated['menu_name'])])->where('service_no_array', 'like', '%' . $row . '%')->first();
            //         if(isset($menu_check_name)){
            //             return response()->json([
            //                 'error'=>'same_name'
            //             ], 201);
            //         }
            //     }
            // }
            $main_menu = Menu::first();
            $sub_menu_level = 0;
            if(!$main_menu){
                $main_menu_id = 101;
                $main_menu_level = 1;
            }else if($validated['menu_depth'] == '하위'){
                $main_menu_id = Menu::where('menu_no', $validated['menu_parent_no'])->first()->main_menu_id;
                $main_menu_level = Menu::where('menu_no', $validated['menu_parent_no'])->first()->main_menu_level;
            }else {
                $main_menu_id = Menu::where('menu_depth', '상위')->orderBy('main_menu_id', 'DESC')->first()->main_menu_id + 1;
                $main_menu_level = Menu::where('menu_depth', '상위')->orderBy('main_menu_level', 'DESC')->first()->main_menu_level + 1;
            }


            if($request->menu_parent_no){
                $sub_menu = Menu::where('menu_parent_no', $validated['menu_parent_no'])->orderBy('sub_menu_id', 'DESC')->first();

                if(!$sub_menu){
                    $sub_menu_id = 101;
                    $sub_menu_level = 1;
                }else {
                    $sub_menu_id = $sub_menu->sub_menu_id + 1;
                    $sub_menu_level = $sub_menu->sub_menu_level + 1;
                }

            }else {
                $sub_menu_id = 100;
                $sub_menu_id = 0;
            }



            $menu_id = (string)$main_menu_id . (string)$sub_menu_id;
            if(empty($sub_menu_level)){
                $menu_level = (string)$main_menu_level;
            }else {
                $menu_level = (string)$main_menu_level .'_'. (string)$sub_menu_level;
            }


            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

            $menu_no = Menu::insertGetId([
                'mb_no' => $member->mb_no,
                'menu_name' => $validated['menu_name'],
                'main_menu_id' => $main_menu_id,
                'sub_menu_id' => $sub_menu_id,
                'menu_id' => intval($menu_id),
                'main_menu_level' => $main_menu_level,
                'sub_menu_level' => $sub_menu_level,
                'menu_level' => $menu_level,
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
                'menu_no' => $menu_no,
                'sql' => DB::getQueryLog()
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);

        }
    }
    public function get_menu_by_path($menu_path)
    {
        try {
            $user = Member::where('mb_no', Auth::user()->mb_no)->first();
            if(Auth::user()->mb_push_yn == 'n'){
                $alarm = Alarm::with('alarm_data')->where(function($q) use($user) {
                    $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query){
                    $query->where('ad_must_yn','=','y');
                }); })->where('receiver_no',Auth::user()->mb_no)->where(function($q) use($user) {
                    $q->where('alarm_read_yn','!=','y')->orwhereNull('alarm_read_yn');
                })->count('alarm_no');
            } else {
                $alarm = Alarm::with('alarm_data')->where(function($q) use($user) {
                    $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query){
                    $query->where('ad_must_yn','=','y')->orwhere('ad_must_yn','=','n')->orWhereNull('ad_must_yn');
                }); })->where('receiver_no',Auth::user()->mb_no)->where(function($q) use($user) {
                    $q->where('alarm_read_yn','!=','y')->orwhereNull('alarm_read_yn');
                })->count('alarm_no');
            }
          
            if($menu_path != 'dashboard'){
            $menu = Menu::with('manual')->where('menu_url', $menu_path)->first();
            } else {
                $menu = Menu::with('manual')->where('menu_url', '대시보드')->first();
            }

            return response()->json(['menu' => $menu,'user'=>$user,'alarm'=>$alarm]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
    public function get_menu_by_path2($menu_path,$alarm_push_yn)
    {
        try {
            $user = Member::where('mb_no', Auth::user()->mb_no)->first();
            if($alarm_push_yn == 'n'){
                $alarm = Alarm::with('alarm_data')->where(function($q) use($user) {
                    $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query){
                    $query->where('ad_must_yn','=','y');
                }); })->where('receiver_no',Auth::user()->mb_no)->where(function($q) use($user) {
                    $q->where('alarm_read_yn','!=','y')->orwhereNull('alarm_read_yn');
                })->count('alarm_no');
            } else {
                $alarm = Alarm::with('alarm_data')->where(function($q) use($user) {
                    $q->whereNull('ad_no')->orWhereHas('alarm_data',function ($query){
                    $query->where('ad_must_yn','=','y')->orwhere('ad_must_yn','=','n')->orWhereNull('ad_must_yn');
                }); })->where('receiver_no',Auth::user()->mb_no)->where(function($q) use($user) {
                    $q->where('alarm_read_yn','!=','y')->orwhereNull('alarm_read_yn');
                })->count('alarm_no');
            }
          
            if($menu_path != 'dashboard'){
            $menu = Menu::with('manual')->where('menu_url', $menu_path)->first();
            } else {
                $menu = Menu::with('manual')->where('menu_url', '대시보드')->first();
            }

            return response()->json(['menu' => $menu,'user'=>$user,'alarm'=>$alarm]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
    public function get_menu($menu_no)
    {
        try {
            $menu = Menu::with(['menu_childs'])->where('menu_no', $menu_no)->first();
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
            $user = Auth::user();
            $member = Member::with('company')->where('mb_no', Auth::user()->mb_no)->first();
            $menu = Menu::whereHas('permission', function($q) use($user) {
                $q->where('role_no', $user->role_no);
            })->get();
            if($member->mb_service_no_array == '공통'){
                $menu_main = Menu::with(['menu_childs' => function($q) use($user) {
                    $q->whereHas('permission', function($q) use($user) {
                        $q->where('role_no', $user->role_no);
                    })->where(function($q) {
                        $q->where('service_no_array','=', '1 2 3 4')->orWhere('service_no_array','=', '2 3 4');
                    });
                }])->whereHas('menu_childs', function($q) use($user) {
                    $q->whereHas('permission', function($q) use($user) {
                        $q->where('role_no', $user->role_no);
                    })->where(function ($q){
                        $q->where('service_no_array','=', '1 2 3 4')->orWhere('service_no_array','=', '2 3 4');
                    });
                })->select(['menu_no', 'menu_name', 'service_no_array'])->where('menu_depth', '상위')->get();
            }else{
                $menu_main = Menu::with(['menu_childs' => function($q) use($user) {
                    $q->whereHas('permission', function($q) use($user) {
                        $q->where('role_no', $user->role_no);
                    });
                }])->whereHas('menu_childs', function($q) use($user) {
                    $q->whereHas('permission', function($q) use($user) {
                        $q->where('role_no', $user->role_no);
                    });
                })->select(['menu_no', 'menu_name', 'service_no_array'])->where('menu_depth', '상위')->get();
            }
            


            // getCustomerCenterInformation
            $co_no = Auth::user()->co_no;
            $company = Company::where('co_no', $co_no)->first();
            if($user->mb_type != 'admin'){
                $company->co_parent;
            }
            $co_type = Auth::user()->mb_type ;
            if($co_type == 'shipper'){
               $information = $company->co_parent->co_parent;
            }
            if($co_type == 'shop'){
                $information = $company->co_parent;
             }
             if($co_type == 'spasys'){
                $information = $company;
             }
             if($user->mb_type == 'admin'){
                $information = '';
             }

            return response()->json([
                'menu_main' => $menu_main,
                'information' => $information,
                '$user->role_no' => $user->role_no,
                'member' => $member,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    public function update_menu(MenuUpdateRequest $request)
    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();
            if (isset($validated['menu_url'])) {
                $menu_check = Menu::whereRaw('LOWER(`menu_url`) = ? ',[strtolower($validated['menu_url'])])->where('menu_no','!=',$validated['menu_no'])->first();
                if(isset($menu_check)){
                    return response()->json([
                        'error'=>'same_url',
                        'sql' => DB::getQueryLog()
                    ], 201);
                }
                }
                if (isset($validated['menu_name']) && !isset($validated['menu_url'])) {
                    $service_no_array = explode(" ", $validated['service_no_array']);
                    foreach($service_no_array as $row){
                        $menu_check_name = Menu::whereRaw('LOWER(`menu_name`) = ? ',[strtolower($validated['menu_name'])])->where('menu_no','!=',$validated['menu_no'])->first();
                        if(isset($menu_check_name)){
                            return response()->json([
                                'error'=>'same_name'
                            ], 201);
                        }
                    }
                }
                if (isset($validated['menu_name']) && isset($validated['menu_url'])) {
                    $service_no_array = explode(" ", $validated['service_no_array']);
                    foreach($service_no_array as $row){
                        $menu_check_name = Menu::whereRaw('LOWER(`menu_name`) = ? ',[strtolower($validated['menu_name'])])->where('menu_no','!=',$validated['menu_no'])->where('service_no_array', 'like', '%' . $row . '%')->first();
                        if(isset($menu_check_name)){
                            return response()->json([
                                'error'=>'same_name'
                            ], 201);
                        }
                    }
                }

            $menu = Menu::where('menu_no', $validated['menu_no'])
                ->update($validated);
            if($validated['menu_depth'] == '상위'){
                $update_sub_menu = Menu::where('menu_parent_no',$validated['menu_no']) ->update([
                    'menu_device' => $validated['menu_device'],
                    'service_no_array' => $validated['service_no_array'],
                ]);
                // if($validated['menu_use_yn'] == 'n'){
                //     $update_sub_menu2 = Menu::where('menu_parent_no',$validated['menu_no']) ->update([
                //         'menu_use_yn' => 'n',
                //     ]);
                // }
                
            }
            
            
            return response()->json(['message' => Messages::MSG_0007,
            'sql' => DB::getQueryLog()
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
        }
    }

    public function delete_menu($menu_no)
    {
        try {
            Menu::where('menu_no', $menu_no)->delete();
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0003], 500);
        }
    }

    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
