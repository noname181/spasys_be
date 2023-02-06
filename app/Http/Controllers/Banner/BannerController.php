<?php

namespace App\Http\Controllers\Banner;

use DateTime;
use App\Http\Requests\Banner\BannerRequest;
use App\Http\Requests\Banner\BannerRegisterRequest;
use App\Http\Requests\Banner\BannerUpdateRequest;
use App\Http\Requests\Banner\BannerSearchRequest;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\File;
use App\Utils\Messages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Member;
use Carbon\Carbon;

use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\Export;
use App\Models\ExportConfirm;

class BannerController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\Banner\BannerRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(BannerRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $banner = Banner::paginate($per_page, ['*'], 'page', $page);
            foreach ($banner->items() as $d) {
                $d['files'] = $d->files()->get();
            }
            // $banner->
            return response()->json($banner);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Fetch banner by id
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function getById(Banner $banner)
    {
        $banner['files'] = $banner->files()->get();
        return response()->json($banner);
    }

    /**
     * Register banner
     * @param  BannerRegisterRequest $request
     * @return \Illuminate\Http\Response
     */
    public function register(BannerRegisterRequest $request)
    {

        $validated = $request->validated();

        try {
            DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $banner_no = Banner::insertGetId([
                'banner_title' => $validated['banner_title'],
                'banner_lat' => '',
                'banner_lng' => '',
                'banner_position' => $validated['banner_position'],
                'banner_position_detail' => isset($validated['banner_position_detail']) ?  $validated['banner_position_detail'] : '',
                'banner_start' => date('Y-m-d H:i:s', strtotime($validated['banner_start'])),
                'banner_end' => date('Y-m-d H:i:s', strtotime($validated['banner_end'])),
                'banner_use_yn' => $validated['banner_use_yn'],
                'banner_sliding_yn' => $validated['banner_sliding_yn'],
                'banner_link1' => isset($validated['banner_link1']) ? $validated['banner_link1'] : '',
                'banner_link2' => $validated['banner_link2'] ? $validated['banner_link2'] : '',
                'banner_link3' => $validated['banner_link3'] ? $validated['banner_link3'] : '',
                'mb_no' => $member->mb_no
            ]);

            $path = join('/', ['files', 'banner', $banner_no]);

            $files = [];
            for($i = 1;$i <= 3;$i++){
                $file =  isset($validated['bannerFiles'.$i.''])?$validated['bannerFiles'.$i.'']:'';
                if(!empty($file)){
                    $url = Storage::disk('public')->put($path, $file);
                    $files[] = [
                        'file_table' => 'banner',
                        'file_table_key' => $banner_no,
                        'file_name' => basename($url),
                        'file_name_old' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'file_extension' => $file->extension(),
                        'file_position' => $i,
                        'file_url' => $url
                    ];
                }
            }
            File::insert($files);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007, 'banner_no' => $banner_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update banner by id
     * @param  Banner $banner
     * @param  BannerUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(BannerUpdateRequest $request)
    {
        try {

            //DB::enableQueryLog();

            $validated = $request->validated();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();

           
             //FILE PART

             $path = join('/', ['files', 'banner', $validated['banner_no']]);

             // remove old image

            //  if($request->remove_files){
            //      foreach($request->remove_files as $key => $file_no) {
            //          $file = File::where('file_no', $file_no)->get()->first();
            //          $url = Storage::disk('public')->delete($path. '/' . $file->file_name);
            //          $file->delete();
            //      }
            //  }
            if(isset($validated['bannerFiles1'])){
                $banner = Banner::where('banner_no',$validated['banner_no'])->first();
                $file_delete = File::where('file_table', 'banner')
                ->where('file_table_key', $validated['banner_no'])->where('file_name_old',$banner->banner_link1)->first();
                if(isset($file_delete)){
                    $url = Storage::disk('public')->delete($path. '/' . $file_delete->file_name);
                    $file_delete->delete();
                }
            }
            if(isset($validated['bannerFiles2'])){
                $banner2 = Banner::where('banner_no',$validated['banner_no'])->first();
                $file_delete2 = File::where('file_table', 'banner')
                ->where('file_table_key', $validated['banner_no'])->where('file_name_old',$banner2->banner_link2)->first();
                if(isset($file_delete2)){
                    $url = Storage::disk('public')->delete($path. '/' . $file_delete2->file_name);
                    $file_delete2->delete();
                }
            }
            if(isset($validated['bannerFiles3'])){
                $banner3 = Banner::where('banner_no',$validated['banner_no'])->first();
                $file_delete3 = File::where('file_table', 'banner')
                ->where('file_table_key', $validated['banner_no'])->where('file_name_old',$banner3->banner_link3)->first();
                if(isset($file_delete3)){
                    $url = Storage::disk('public')->delete($path. '/' . $file_delete3->file_name);
                    $file_delete3->delete();
                }
            }
            

             $files = [];
             for($i = 1;$i <= 3;$i++){
                 $file =  isset($validated['bannerFiles'.$i.'']) ? $validated['bannerFiles'.$i.'']:'';
                 
                
                   
                
                 if(!empty($file)){
                     $url = Storage::disk('public')->put($path, $file);
                     $files[] = [
                         'file_table' => 'banner',
                         'file_table_key' => $validated['banner_no'],
                         'file_name' => basename($url),
                         'file_name_old' => $file->getClientOriginalName(),
                         'file_size' => $file->getSize(),
                         'file_extension' => $file->extension(),
                         'file_position' => $i,
                         'file_url' => $url
                     ];
                 }
             }
             File::insert($files);
             $banner = Banner::where('banner_no', $validated['banner_no'])->update([
                'banner_title' => $validated['banner_title'],
                'banner_lat' => '',
                'banner_lng' => '',
                'banner_start' => date('Y-m-d H:i:s', strtotime($validated['banner_start'])),
                'banner_end' => date('Y-m-d H:i:s', strtotime($validated['banner_end'])),
                'banner_position' => $validated['banner_position'],
                'banner_position_detail' => $validated['banner_position_detail'] ? $validated['banner_position_detail'] : '',
                'banner_use_yn' => $validated['banner_use_yn'],
                'banner_sliding_yn' => $validated['banner_sliding_yn'],
                'banner_link1' => $validated['banner_link1'],
                'banner_link2' => $validated['banner_link2'] ? $validated['banner_link2'] : '',
                'banner_link3' => $validated['banner_link3'] ? $validated['banner_link3'] : '',
                'mb_no' => $member->mb_no,
            ]);
             DB::commit();
            //  if($request->hasFile('files')){
            //      $files = [];

            //      $max_position_file = File::where('file_table', 'banner')->where('file_table_key', $validated['banner_no'])->orderBy('file_position', 'DESC')->get()->first();
            //      if($max_position_file)
            //          $i = $max_position_file->file_position + 1;
            //      else
            //          $i = 0;

            //      foreach($validated['files'] as $key => $file) {
            //          $url = Storage::disk('public')->put($path, $file);
            //          $files[] = [
            //              'file_table' => 'banner',
            //              'file_table_key' => $validated['banner_no'],
            //              'file_name_old' => $file->getClientOriginalName(),
            //              'file_name' => basename($url),
            //              'file_size' => $file->getSize(),
            //              'file_extension' => $file->extension(),
            //              'file_position' => $i,
            //              'file_url' => $url
            //          ];
            //          $i++;
            //      }

            //     File::insert($files);

            //  }

            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    public function getBanner(BannerSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();

            
            $banner = Banner::with('mb_no')->with('files')->where('mb_no',$user->mb_no)->orderBy('banner_no', 'DESC');

            if (isset($validated['from_date'])) {
                $banner->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $banner->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['search_string'])) {
                $banner->where(function($query) use ($validated) {
                    $query->where('banner_title', 'like', '%' . $validated['search_string'] . '%');
                });
            }
            if (isset($validated['banner_position'])) {
                if($validated['banner_position'] != '전체')
                    $banner->where('banner_position', $validated['banner_position']);

            }
            if (isset($validated['banner_use_yn'])) {
                if($validated['banner_use_yn'] != '전체')
                    $banner->where('banner_use_yn', $validated['banner_use_yn'] == 'y' ? '1' : '0');
            }

            $members = Member::where('mb_no', '!=', 0)->get();

            $banner = $banner->paginate($per_page, ['*'], 'page', $page);

            $custom = collect(['my_data' => 'My custom data here']);

            $data = $custom->merge($banner);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function banner_load()
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $banner_login_left = Banner::with('files')
            ->where('banner_position','=','로그인')
            ->where('banner_position_detail','=','왼쪽')
            ->where('banner_use_yn','=','1')
            ->where('banner_start', '<=', $today)
            ->where('banner_end', '>=', $today)->latest('created_at')
            ->first();

            $banner_login_right_top = Banner::with('files')
            ->where('banner_position','=','로그인')
            ->where('banner_position_detail','=','오른쪽 상단')
            ->where('banner_use_yn','=','1')
            ->where('banner_start', '<=', $today)
            ->where('banner_end', '>=', $today)->latest('created_at')
            ->first();

            $banner_login_right_bottom = Banner::with('files')
            ->where('banner_position','=','로그인')
            ->where('banner_position_detail','=','오른쪽 하단')
            ->where('banner_use_yn','=','1')
            ->where('banner_start', '<=', $today)
            ->where('banner_end', '>=', $today)->latest('created_at')
            ->first();

            $banner_index_top = Banner::with('files')
            ->where('banner_position','=','메인')
            ->where('banner_position_detail','=','상단')
            ->where('banner_use_yn','=','1')
            ->where('banner_start', '<=', $today)
            ->where('banner_end', '>=', $today)->latest('created_at')
            ->first();

            $banner_index_bottom = Banner::with('files')
            ->where('banner_position','=','메인')
            ->where('banner_position_detail','=','하단')
            ->where('banner_use_yn','=','1')
            ->where('banner_start', '<=', $today)
            ->where('banner_end', '>=', $today)->latest('created_at')
            ->first();

            return response()->json([
                'message' => Messages::MSG_0007,
                'banner_login_left' => $banner_login_left,
                'banner_login_right_top' => $banner_login_right_top,
                'banner_login_right_bottom' => $banner_login_right_bottom,
                'banner_index_top' => $banner_index_top,
                'banner_index_bottom' => $banner_index_bottom,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function banner_load2() //index page
    {
        try {
            $user = Auth::user();
            DB::enableQueryLog();
            $today = Carbon::now()->format('Y-m-d');

            $banner_index_top = Banner::with('files')
            ->where('mb_no',$user->mb_no)
            ->where('banner_position','=','메인')
            ->where('banner_position_detail','=','상단')
            ->where('banner_use_yn','=','1')
            ->where('banner_start', '<=', $today)
            ->where('banner_end', '>=', $today)->latest('created_at')
            ->first();

            $banner_index_bottom = Banner::with('files')
            ->where('mb_no',$user->mb_no)
            ->where('banner_position','=','메인')
            ->where('banner_position_detail','=','하단')
            ->where('banner_use_yn','=','1')
            ->where('banner_start', '<=', $today)
            ->where('banner_end', '>=', $today)->latest('created_at')
            ->first();

            return response()->json([
                'message' => Messages::MSG_0007,
                'banner_index_top' => $banner_index_top,
                'banner_index_bottom' => $banner_index_bottom,
                'mb_no' => $user->mb_no
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function banner_count()
    {
        //return "dsada";
        try {
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();
            DB::enableQueryLog();
            if ($user->mb_type == 'shop') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1','ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                    ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                  
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule_a = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');

                $import_schedule_b = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');

                $import_schedule_d = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'shipper') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                    ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                  
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule_a = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');

                $import_schedule_b = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');

                $import_schedule_d = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('parent_spasys.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number') 
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule_a = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');

                $import_schedule_b = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');

                $import_schedule_d = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');        
            }

            $import_schedule_a = $import_schedule_a->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->get()->count();
            $import_schedule_b = $import_schedule_b->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->get()->count();
            $import_schedule_d = $import_schedule_d->whereNotNull('ddd.te_logistic_manage_number')->get()->count();

            return response()->json([
                'message' => Messages::MSG_0007,
                'total_a' => $import_schedule_a,
                'total_b' => $import_schedule_b,
                'total_c' => ($import_schedule_b - $import_schedule_a),
                'total_d' => $import_schedule_d,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}
