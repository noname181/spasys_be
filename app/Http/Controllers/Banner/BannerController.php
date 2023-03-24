<?php

namespace App\Http\Controllers\Banner;

use App\Models\ReceivingGoodsDelivery;
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
use Carbon\CarbonPeriod;
use App\Models\WarehousingItem;
use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\Export;
use App\Models\ExportConfirm;
use App\Models\Warehousing;
//use AWS\CRT\HTTP\Request;
use App\Models\StockStatusBad;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\ScheduleShipment;

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
            if (isset($validated['banner_start']) && isset($validated['banner_end'])) {
                $check_banner_times = Banner::where('banner_position', $validated['banner_position'])
                    ->where('banner_position_detail', $validated['banner_position_detail'])
                    ->where('banner_end', '>=', $validated['banner_start'])
                    ->where('banner_start', '<=', $validated['banner_start'])
                    ->first();

                $check_banner_times2 = Banner::where('banner_position', $validated['banner_position'])
                    ->where('banner_position_detail', $validated['banner_position_detail'])
                    ->where('banner_end', '>=', $validated['banner_end'])
                    ->where('banner_start', '<=', $validated['banner_end'])->first();

                $check_banner_times3 = Banner::where(function ($query) use ($validated) {
                    $query->where('banner_end', '<=', $validated['banner_start'])->orWhere('banner_start', '>=', $validated['banner_end']);
                })->first();
            }
            if (isset($check_banner_times) || isset($check_banner_times2) || !isset($check_banner_times3)) {
                if (!isset($check_banner_times3)) {
                    $check_banner_times4 = Banner::where('banner_position', $validated['banner_position'])
                        ->where('banner_position_detail', $validated['banner_position_detail'])->first();
                    if (isset($check_banner_times4)) {
                        return response()->json([
                            'error' => 'same_time',
                            'check_banner_times' => isset($check_banner_times) ? $check_banner_times : '',
                            'check_banner_times2' => isset($check_banner_times2) ? $check_banner_times2 : '',
                            'check_banner_times3' => isset($check_banner_times3) ? $check_banner_times3 : '',
                        ], 201);
                    }
                } else {
                    return response()->json([
                        'error' => 'same_time',
                        'check_banner_times' => isset($check_banner_times) ? $check_banner_times : '',
                        'check_banner_times2' => isset($check_banner_times2) ? $check_banner_times2 : '',
                        'check_banner_times3' => isset($check_banner_times3) ? $check_banner_times3 : '',
                    ], 201);
                }
            }
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
            for ($i = 1; $i <= 3; $i++) {
                $file =  isset($validated['bannerFiles' . $i . '']) ? $validated['bannerFiles' . $i . ''] : '';
                if (!empty($file)) {
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
            return response()->json([
                'message' => Messages::MSG_0007, 'banner_no' => $banner_no, 'check_banner_times' => isset($check_banner_times) ? $check_banner_times : '',
                'check_banner_times2' => isset($check_banner_times2) ? $check_banner_times2 : '',
                'check_banner_times3' => isset($check_banner_times3) ? $check_banner_times3 : '',
            ], 201);
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

            if (isset($validated['banner_start']) && isset($validated['banner_end'])) {
                $check_banner_times = Banner::where('banner_position', $validated['banner_position'])
                    ->where('banner_position_detail', $validated['banner_position_detail'])
                    ->where('banner_end', '>=', $validated['banner_start'])
                    ->where('banner_start', '<=', $validated['banner_start'])->where('banner_no', '!=', $validated['banner_no'])
                    ->first();

                $check_banner_times2 = Banner::where('banner_position', $validated['banner_position'])
                    ->where('banner_position_detail', $validated['banner_position_detail'])
                    ->where('banner_end', '>=', $validated['banner_end'])
                    ->where('banner_start', '<=', $validated['banner_end'])->where('banner_no', '!=', $validated['banner_no'])->first();

                $check_banner_times3 = Banner::where(function ($query) use ($validated) {
                    $query->where('banner_end', '<=', $validated['banner_start'])->orWhere('banner_start', '>=', $validated['banner_end'])->where('banner_no', '!=', $validated['banner_no']);
                })->first();
            }
            if (isset($check_banner_times) || isset($check_banner_times2) || !isset($check_banner_times3)) {
                if (!isset($check_banner_times3)) {
                    $check_banner_times4 = Banner::where('banner_position', $validated['banner_position'])
                        ->where('banner_position_detail', $validated['banner_position_detail'])->where('banner_no', '!=', $validated['banner_no'])->first();
                    if (isset($check_banner_times4)) {
                        return response()->json([
                            'error' => 'same_time',
                            'check_banner_times' => isset($check_banner_times) ? $check_banner_times : '',
                            'check_banner_times2' => isset($check_banner_times2) ? $check_banner_times2 : '',
                            'check_banner_times3' => isset($check_banner_times3) ? $check_banner_times3 : '',
                        ], 201);
                    }
                } else {
                    return response()->json([
                        'error' => 'same_time',
                        'check_banner_times' => isset($check_banner_times) ? $check_banner_times : '',
                        'check_banner_times2' => isset($check_banner_times2) ? $check_banner_times2 : '',
                        'check_banner_times3' => isset($check_banner_times3) ? $check_banner_times3 : '',
                    ], 201);
                }
            }
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
            if (isset($validated['bannerFiles1'])) {
                $banner = Banner::where('banner_no', $validated['banner_no'])->first();
                $file_delete = File::where('file_table', 'banner')
                    ->where('file_table_key', $validated['banner_no'])->where('file_name_old', $banner->banner_link1)->first();
                if (isset($file_delete)) {
                    $url = Storage::disk('public')->delete($path . '/' . $file_delete->file_name);
                    $file_delete->delete();
                }
            }
            if (isset($validated['bannerFiles2'])) {
                $banner2 = Banner::where('banner_no', $validated['banner_no'])->first();
                $file_delete2 = File::where('file_table', 'banner')
                    ->where('file_table_key', $validated['banner_no'])->where('file_name_old', $banner2->banner_link2)->first();
                if (isset($file_delete2)) {
                    $url = Storage::disk('public')->delete($path . '/' . $file_delete2->file_name);
                    $file_delete2->delete();
                }
            }
            if (isset($validated['bannerFiles3'])) {
                $banner3 = Banner::where('banner_no', $validated['banner_no'])->first();
                $file_delete3 = File::where('file_table', 'banner')
                    ->where('file_table_key', $validated['banner_no'])->where('file_name_old', $banner3->banner_link3)->first();
                if (isset($file_delete3)) {
                    $url = Storage::disk('public')->delete($path . '/' . $file_delete3->file_name);
                    $file_delete3->delete();
                }
            }


            $files = [];
            for ($i = 1; $i <= 3; $i++) {
                $file =  isset($validated['bannerFiles' . $i . '']) ? $validated['bannerFiles' . $i . ''] : '';




                if (!empty($file)) {
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


            $banner = Banner::with('mb_no')->with('files')->where('mb_no', '133')->orderBy('banner_no', 'DESC');

            if (isset($validated['from_date'])) {
                $banner->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $banner->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['search_string'])) {
                $banner->where(function ($query) use ($validated) {
                    $query->where('banner_title', 'like', '%' . $validated['search_string'] . '%');
                });
            }
            if (isset($validated['banner_position'])) {
                if ($validated['banner_position'] != '전체')
                    $banner->where('banner_position', $validated['banner_position']);
            }
            if (isset($validated['banner_use_yn'])) {
                if ($validated['banner_use_yn'] != '전체')
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
                ->where('banner_position', '=', '로그인')
                ->where('banner_position_detail', '=', '왼쪽')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->where('mb_no', '=', '133')
                ->first();

            $banner_login_right_top = Banner::with('files')
                ->where('banner_position', '=', '로그인')
                ->where('banner_position_detail', '=', '오른쪽 상단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->where('mb_no', '=', '133')
                ->first();

            $banner_login_right_bottom = Banner::with('files')
                ->where('banner_position', '=', '로그인')
                ->where('banner_position_detail', '=', '오른쪽 하단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->where('mb_no', '=', '133')
                ->first();

            $banner_index_top = Banner::with('files')
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '상단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->where('mb_no', '=', '133')
                ->first();

            $banner_index_bottom = Banner::with('files')
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '하단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->where('mb_no', '=', '133')
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
                //->where('mb_no', $user->mb_no)
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '상단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->where('mb_no', '=', '133')
                ->first();

            $banner_index_bottom = Banner::with('files')
                // ->where('mb_no', $user->mb_no)
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '하단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->where('mb_no', '=', '133')
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

    public function CaculateService3($request)
    {
        $user = Auth::user();
        if ($user->mb_type == 'shop') {
            $warehousinga = ReceivingGoodsDelivery::with(['w_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                    $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                })->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingb = ReceivingGoodsDelivery::with(['w_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingchartb = ReceivingGoodsDelivery::with(['w_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();

            $w_import_no = collect($warehousing2)->map(function ($q) {
                return $q->w_import_no;
            });

            $warehousingc = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereNotIn('w_no', $w_import_no)->whereHas('warehousing', function ($query) use ($user) {
                $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orwhere('rgd_status1', '=', '출고예정')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where('w_category_name', '=', '유통가공')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });

            $warehousingchartd = ReceivingGoodsDelivery::with(['warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where('w_category_name', '=', '유통가공')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });

            $warehousinge = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업대기')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingf = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업중')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general']);

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'spasys');
            });

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) use ($user) {
                $q->where('mb_type', 'spasys');
            });
        } else if ($user->mb_type == 'shipper') {
            $warehousinga = ReceivingGoodsDelivery::with(['w_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                    $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                })->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingb = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingchartb = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();

            $w_import_no = collect($warehousing2)->map(function ($q) {
                return $q->w_import_no;
            });

            $warehousingc = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereNotIn('w_no', $w_import_no)->whereHas('warehousing', function ($query) use ($user) {
                $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orwhere('rgd_status1', '=', '출고예정')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where('w_category_name', '=', '유통가공')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });

            $warehousingchartd = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where('w_category_name', '=', '유통가공')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });

            $warehousinge = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업대기')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingf = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업중')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general']);

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'shop');
            })->orderBy('created_at', 'DESC');

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });
        } else if ($user->mb_type == 'spasys') {
            $warehousinga = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                    $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                })->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingb = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingchartb = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing2 = Warehousing::where('w_type', '=', 'EW')->where('w_category_name', '=', '유통가공')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();

            $w_import_no = collect($warehousing2)->map(function ($q) {
                return $q->w_import_no;
            });

            $warehousingc = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereNotIn('w_no', $w_import_no)->whereHas('warehousing', function ($query) use ($user) {
                $query->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->where('rgd_status2', '=', '작업완료')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orwhere('rgd_status1', '=', '출고예정')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where('w_category_name', '=', '유통가공')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });

            $warehousingchartd = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where('w_category_name', '=', '유통가공')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });

            $warehousinge = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업대기')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingf = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업중')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
        }

        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;

        $warehousing_distribution->where(function ($q) {
<<<<<<< HEAD
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '유통가공')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');

        $warehousing_fulfillment->where(function ($q) {
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '유통가공')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');

        $warehousing_bonded->where(function ($q) {
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '유통가공')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');
=======
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '유통가공')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');

        $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '유통가공')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');

        $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '유통가공')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

        $warehousingh = $warehousing_distribution->union($warehousing_fulfillment)->union($warehousing_bonded);

        if ($request->time3 == 'day') {
            $warehousinga = $warehousinga->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingb = $warehousingb->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
            $warehousingc = $warehousingc->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingd = $warehousingd->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
            $warehousinge = $warehousinge->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingf = $warehousingf->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingg = $warehousingg->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
        } elseif ($request->time3 == 'week') {
            $warehousinga = $warehousinga->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingb = $warehousingb->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $warehousingc = $warehousingc->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingd = $warehousingd->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $warehousinge = $warehousinge->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingf = $warehousingf->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingg = $warehousingg->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
        } else {
            $warehousinga = $warehousinga->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingb = $warehousingb->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();
            $warehousingc = $warehousingc->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingd = $warehousingd->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();
            $warehousinge = $warehousinge->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingf = $warehousingf->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->get();
            $warehousingg = $warehousingg->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereBetween('warehousing.w_completed_day', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();
        }

        $counta = 0;
        $countb = 0;
        $countc = 0;
        $countd = 0;
        $counte = 0;
        $countf = 0;
        $countg = 0;
        $counth = 0;
        $counth_2 = 0;

        $counta = $warehousinga->count();
        $countb = $warehousingb->count();
        $countc = $warehousingc->count();
        $countd = $warehousingd->count();
        $counte = $warehousinge->count();
        $countf = $warehousingf->count();
        $countg = $warehousingg->count();
        //$counth = $warehousingh->count();

        foreach ($warehousinga as $item) {
            $a += WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_shipper')->sum('wi_number');
        }

        foreach ($warehousingb as $item) {
            //$b += WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
            $b += $item->warehousing->w_amount;
        }

        foreach ($warehousingc as $item) {
            $c += WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '출고_shipper')->sum('wi_number');
        }

        foreach ($warehousingd as $item) {
            //$d += WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '출고_spasys')->sum('wi_number');
            $d += $item->w_schedule_amount;
        }

        foreach ($warehousinge as $item) {
            $e += WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
        }

        foreach ($warehousingf as $item) {
            $f += WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
        }

        foreach ($warehousingg as $item) {
            $g += WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_spasys')->sum('wi_number');
        }

        $counth = $warehousingh->get()->count();
        foreach ($warehousingh->get() as $i) {
            $counth_2 += isset($i->rate_data_general->rdg_sum4) ? $i->rate_data_general->rdg_sum4 : 0;
        }

        $countchartb = [];
        $countchartd = [];
        for ($i = 1; $i <= 6; $i++) {
            $countchartb = $warehousingchartb->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereDate('warehousing.w_completed_day', '>', now()->subYear())->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            $countchartd = $warehousingchartd->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereDate('warehousing.w_completed_day', '>', now()->subYear())->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            // $countg = $warehousingg->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereYear('warehousing.w_completed_day', Carbon::now()->year)->get()->groupBy(function ($date) {
            //     //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
            //     return Carbon::parse($date->created_at)->format('m'); // grouping by months
            // });
        }

        $chartcountb = [];
        $chartcountd = [];
        $userArrb = [];
        $userArrd = [];

        $userArrb['label'] = '입고';
        $userArrb['borderColor'] = '#F7C35D';
        $userArrb['backgroundColor'] = '#F7C35D';

        $userArrd['label'] = '출고';
        $userArrd['borderColor'] = '#0493FF';
        $userArrd['backgroundColor'] = '#0493FF';

        foreach ($countchartb as $key => $value) {
            $chartcountb[(int)$key] = count($value);
        }

        foreach ($countchartd as $key => $value) {
            $chartcountd[(int)$key] = count($value);
        }

        // for ($i = 1; $i <= 12; $i++) {
        //     if (!empty($chartcountb[$i])) {
        //         $userArrb['data'][] = $chartcountb[$i];
        //     } else {
        //         $userArrb['data'][] = 0;
        //     }

        //     if (!empty($chartcountd[$i])) {
        //         $userArrd['data'][] = $chartcountd[$i];
        //     } else {
        //         $userArrd['data'][] = 0;
        //     }
        // }

        $period = CarbonPeriod::create(today()->subMonths(5), '1 month', today());

        foreach ($period as $date) {
            $m = (int)$date->format('m');
            if (!empty($chartcountb[$m])) {
                $userArrb['data'][] = $chartcountb[$m];
            } else {
                $userArrb['data'][] = 0;
            }

            if (!empty($chartcountd[$m])) {
                $userArrd['data'][] = $chartcountd[$m];
            } else {
                $userArrd['data'][] = 0;
            }
        }

        return [
            'countcharta' => $userArrb, 'countchartb' => $userArrd, 'warehousingh' => $warehousingh->get(),
            'warehousingb' => $warehousingd, 'a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e, 'f' => $f, 'h' => $h, 'g' => $g,
            'counta' => $counta, 'countb' => $countb, 'countc' => $countc, 'countd' => $countd, 'counte' => $counte, 'countf' => $countf, 'countg' => $countg, 'counth' => $counth, 'counth_2' => $counth_2

        ];
    }

    public function CaculateService2($request)
    {
        $user = Auth::user();
        DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        if ($user->mb_type == 'shop') {
            $warehousing2 = Warehousing::join(
                DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no',
                '=',
                'warehousing.w_no'
            )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();
            $w_import_no = collect($warehousing2)->map(function ($q) {

                return $q->w_import_no;
            });
            $w_no_in = collect($warehousing2)->map(function ($q) {

                return $q->w_no;
            });
            $warehousinga = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                ->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            $warehousingcharta = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                ->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            $warehousingb = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
                //    $q->where('item_service_name', '=', '수입풀필먼트');
                //    $q->whereHas('item_info', function ($e) {
                //             $e->whereNotNull('stock');
                //     });
                $q->whereHas('ContractWms.company.co_parent', function ($k) use ($user) {
                    $k->where('co_no', $user->co_no);
                });
            })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');

            $warehousingc = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고예정')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->orderBy('ss_no', 'DESC');

            $warehousingd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });

            $warehousingchartd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general']);

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'spasys');
            });

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) use ($user) {
                $q->where('mb_type', 'spasys');
            });
        } else if ($user->mb_type == 'shipper') {
            $warehousing2 = Warehousing::join(
                DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no',
                '=',
                'warehousing.w_no'
            )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();
            $w_import_no = collect($warehousing2)->map(function ($q) {

                return $q->w_import_no;
            });
            $w_no_in = collect($warehousing2)->map(function ($q) {

                return $q->w_no;
            });
            $warehousinga = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                ->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            $warehousingcharta = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                ->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            $warehousingb = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
                //    $q->where('item_service_name', '=', '수입풀필먼트');
                //    $q->whereHas('item_info', function ($e) {
                //             $e->whereNotNull('stock');
                //     });
                $q->whereHas('ContractWms.company', function ($k) use ($user) {
                    $k->where('co_no', $user->co_no);
                });
            })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');

            $warehousingc = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고예정')->whereHas('ContractWms.company', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->orderBy('ss_no', 'DESC');

            $warehousingd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });

            $warehousingchartd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general']);

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'shop');
            })->orderBy('created_at', 'DESC');

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });
        } else if ($user->mb_type == 'spasys') {

            $warehousing2 = Warehousing::join(
                DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no',
                '=',
                'warehousing.w_no'
            )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();
            $w_import_no = collect($warehousing2)->map(function ($q) {

                return $q->w_import_no;
            });
            $w_no_in = collect($warehousing2)->map(function ($q) {

                return $q->w_no;
            });

            $warehousinga = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'rate_data_general'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            $warehousingcharta = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'rate_data_general'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            $warehousingb = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
                //    $q->where('item_service_name', '=', '수입풀필먼트');
                //    $q->whereHas('item_info', function ($e) {
                //             $e->whereNotNull('stock');
                //     });
                $q->whereHas('ContractWms.company.co_parent.co_parent', function ($k) use ($user) {
                    $k->where('co_no', $user->co_no);
                });
            })->whereNotNull('stock')->groupby('product_id')->groupby('option_id');

            $warehousingc = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고예정')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->orderBy('ss_no', 'DESC');

            $warehousingd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });

            $warehousingchartd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
        }

        $warehousing_distribution->where(function ($q) {
<<<<<<< HEAD
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '수입풀필먼트')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');

        $warehousing_fulfillment->where(function ($q) {
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '수입풀필먼트')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');

        $warehousing_bonded->where(function ($q) {
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '수입풀필먼트')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');
=======
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '수입풀필먼트')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');

        $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '수입풀필먼트')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');

        $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '수입풀필먼트')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

        $warehousinge = $warehousing_distribution->union($warehousing_fulfillment)->union($warehousing_bonded);

        $warehousinga->whereDoesntHave('rate_data_general');

        $a = 0;
        $b = 0;
        $b_2 = 0;
        $c = 0;
        $d = 0;
        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;

        $counta = 0;
        $countb = 0;
        $countc = 0;
        $countd = 0;
        $counte = 0;
        $counte_2 = 0;


        if ($request->time2 == 'day') {
            $warehousinga = $warehousinga->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
            $warehousingb = $warehousingb->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
        } elseif ($request->time2 == 'week') {
            $warehousinga = $warehousinga->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $warehousingb = $warehousingb->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
        } else {
            $warehousinga = $warehousinga->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();
            $warehousingb = $warehousingb->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();
        }

        $counta = $warehousinga->count();
        $countb = $warehousingb->count();
        $countc = $warehousingc->count();
        $countd = $warehousingd->count();
        $counte = $warehousinge->get()->count();
        foreach ($warehousinge->get() as $i) {
            $counte_2 += isset($i->rate_data_general->rdg_sum6) ? $i->rate_data_general->rdg_sum6 : 0;
        }

        foreach ($warehousinga as $item) {
            $a += isset($item->w_amount) ? $item->w_amount : 0;
        }

        foreach ($warehousingb as $item) {
            $item4 = Item::with(['item_info'])->where('item.item_no', $item->item_no)->first();
            if (isset($item4['item_info']['stock'])) {
                $b += isset($item4['item_info']['stock']) ? $item4['item_info']['stock'] : 0;
            }
        }

        foreach ($warehousingb as $item) {
            $item6 = Item::with(['item_info'])->where('item.item_no', $item->item_no)->first();
            if (isset($item4['item_info']['stock'])) {
                $b_2 += $item6->item_price2 * $item6['item_info']['stock'] ? $item6->item_price2 * $item6['item_info']['stock'] : 0;
            }
        }

        foreach ($warehousingc as $item) {
            $schedule_shipment_item = DB::table('schedule_shipment_info')->where('schedule_shipment_info.ss_no', $item->ss_no)->get();
            foreach ($schedule_shipment_item as $item_s) {
                $c += $item_s->qty;
            }
        }

        foreach ($warehousingd as $item) {
            $schedule_shipment_item = DB::table('schedule_shipment_info')->where('schedule_shipment_info.ss_no', $item->ss_no)->get();
            foreach ($schedule_shipment_item as $item_s) {
                $d += $item_s->qty;
            }
        }

        $countcharta = [];
        $countchartd = [];

        for ($i = 1; $i <= 6; $i++) {
            $countcharta = $warehousingcharta->whereDate('created_at', '>', now()->subYear())->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            $countchartd = $warehousingchartd->whereDate('created_at', '>', now()->subYear())->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });
        }

        $chartcounta = [];
        $chartcountd = [];
        $userArra = [];
        $userArrd = [];


        $userArra['label'] = '입고';
        $userArra['borderColor'] = '#F7C35D';
        $userArra['backgroundColor'] = '#F7C35D';

        $userArrd['label'] = '출고';
        $userArrd['borderColor'] = '#0493FF';
        $userArrd['backgroundColor'] = '#0493FF';

        // $userArrd['label'] = '보관';
        // $userArrd['borderColor'] = '#1EB28C';
        // $userArrd['backgroundColor'] = '#1EB28C';

        foreach ($countcharta as $key => $value) {
            $chartcounta[(int)$key] = count($value);
        }

        foreach ($countchartd as $key => $value) {
            $chartcountd[(int)$key] = count($value);
        }

        // foreach ($countd as $key => $value) {
        //     $chartcountd[(int)$key] = count($value);
        // }

        // for ($i = 1; $i <= 12; $i++) {
        //     if (!empty($chartcounta[$i])) {
        //         $userArra['data'][] = $chartcounta[$i];
        //     } else {
        //         $userArra['data'][] = 0;
        //     }

        //     if (!empty($chartcountd[$i])) {
        //         $userArrd['data'][] = $chartcountd[$i];
        //     } else {
        //         $userArrd['data'][] = 0;
        //     }

        //     // if (!empty($chartcountd[$i])) {
        //     //     $userArrd['data'][] = $chartcountd[$i];
        //     // } else {
        //     //     $userArrd['data'][] = 0;
        //     // }
        // }

        $period = CarbonPeriod::create(today()->subMonths(5), '1 month', today());

        foreach ($period as $date) {
            $m = (int)$date->format('m');
            if (!empty($chartcounta[$m])) {
                $userArra['data'][] = $chartcounta[$m];
            } else {
                $userArra['data'][] = 0;
            }

            if (!empty($chartcountd[$m])) {
                $userArrd['data'][] = $chartcountd[$m];
            } else {
                $userArrd['data'][] = 0;
            }
        }

        return [
            'countcharta' => $userArra, 'countchartb' => $userArrd, 'warehousinga' => $warehousinga, 'counta' => $counta, 'countb' => $countb, 'countc' => $countc, 'countd' => $countd, 'counte' => $counte, 'counte_2' => $counte_2, 'a' => $a, 'b' => $b, 'b_2' => $b_2, 'c' => $c, 'd' => $d, 'e' => $e, 'f' => $f, 'h' => $h, 'g' => $g,
        ];
        DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    public function subquery($sub, $sub_2, $sub_4)
    {
        $warehousing = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
            $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
        })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
            $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
        })->orderBy('te_carry_out_number', 'DESC');

        return $warehousing;
    }

    public function CaculateService1(Request $request)
    {
        $user = Auth::user();
        DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        if ($user->mb_type == 'shop') {

            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 'tie_is_date', 'tie_status_2')
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number', 'ti_i_date')
                // ->leftjoin('receiving_goods_delivery', function ($join) {
                //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                // })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
            //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $warehousinga = $this->subquery($sub, $sub_2, $sub_4);

            $warehousingb = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            })->orderBy('te_carry_out_number', 'DESC');

            $warehousingd = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            })->orderBy('te_carry_out_number', 'DESC');

            $warehousinge = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general']);

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'spasys');
            });

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) use ($user) {
                $q->where('mb_type', 'spasys');
            });

            $warehousingchartb = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });

            $warehousingchartd = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });
        } else if ($user->mb_type == 'shipper') {

            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 'tie_is_date', 'tie_status_2')
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number', 'ti_i_date')
                // ->leftjoin('receiving_goods_delivery', function ($join) {
                //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                // })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
            //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $warehousinga = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            })->orderBy('te_carry_out_number', 'DESC');

            $warehousingb = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            })->orderBy('te_carry_out_number', 'DESC');

            $warehousingd = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            })->orderBy('te_carry_out_number', 'DESC');

            $warehousinge = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });


            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general']);
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general']);

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'shop');
            })->orderBy('created_at', 'DESC');

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });




            $warehousingchartb = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });

            $warehousingchartd = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });
        } else if ($user->mb_type == 'spasys') {
            //FIX NOT WORK 'with'
            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 'tie_is_date', 'tie_status_2')
                ->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })
                ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                ->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number', 'ti_i_date')

                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
            //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')
                ->groupBy(['te_logistic_manage_number','te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);

            $sub_5 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')
                ->groupBy(['te_logistic_manage_number','te_e_confirm_number','te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $warehousinga = $this->subquery($sub, $sub_2, $sub_4);

            $warehousingb = $this->subquery($sub, $sub_2, $sub_4);

            $warehousingd = $this->subquery($sub, $sub_2, $sub_5);

            $warehousinge = $this->subquery($sub, $sub_2, $sub_4);

            $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
            $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');
            $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general'])->whereNull('rgd_no');

            $warehousingchartb = $this->subquery($sub, $sub_2, $sub_4);
            $warehousingchartd = $this->subquery($sub, $sub_2, $sub_5);
        }

        $warehousing_distribution->where(function ($q) {
<<<<<<< HEAD
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '보세화물')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');

        $warehousing_fulfillment->where(function ($q) {
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '보세화물')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');

        $warehousing_bonded->where(function ($q) {
            $q->where('rgd_status4', '=', '예상경비청구서')
                ->orWhere('rgd_status4', '=', '확정청구서');
        })
            ->where('service_korean_name', '=', '보세화물')
            ->whereNull('rgd_status6')
            ->where('rgd_is_show', 'y');
=======
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '보세화물')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');

        $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '보세화물')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');

        $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
        ->where('service_korean_name', '=', '보세화물')
        ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
        ->where('rgd_status7', 'taxed')
        ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

        $warehousingg = $warehousing_distribution->union($warehousing_fulfillment)->union($warehousing_bonded);

        $counta = 0;
        $countb = 0;
        $countc = 0;
        $countd = 0;
        $counte = 0;
        $countf = 0;
        $countg = 0;
        $countg_2 = 0;
        //$tie_logistic_manage_number = $this->SQL();
        $warehousingb2 = $this->subquery($sub, $sub_2, $sub_4)->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->get()->count();
        $warehousingd2 = $this->subquery($sub, $sub_2, $sub_4)->whereNotNull('ddd.te_logistic_manage_number')->get()->count();

        if ($request->time1 == 'day') {
            $countb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereBetween('bbb.ti_i_date', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get()->count();
            $countd = $warehousingd->whereNotNull('ddd.te_logistic_manage_number')->whereBetween('aaa.tie_is_date', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get()->count();
        } elseif ($request->time1 == 'week') {
            $countb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereBetween('bbb.ti_i_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get()->count();
            $countd = $warehousingd->whereNotNull('ddd.te_logistic_manage_number')->whereBetween('aaa.tie_is_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get()->count();
        } elseif ($request->time1 == 'month') {
            $countb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereBetween('bbb.ti_i_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get()->count();
            $countd = $warehousingd->whereNotNull('ddd.te_logistic_manage_number')->whereBetween('aaa.tie_is_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get()->count();
        }

        $counta = $warehousinga->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->get()->count();
        $countc = $warehousingb2;
        $counte = $warehousinge->where('aaa.tie_status_2', '=', '수입신고접수')->orwhere('aaa.tie_status_2', '=', '수입신고정정접수')->get()->count();
        $countg = $warehousingg->get()->count();
        foreach ($warehousingg->get() as $i) {
            $countg_2 += isset($i->rate_data_general->rdg_sum7) ? $i->rate_data_general->rdg_sum7 : 0;
        }

        $countchartb = [];
        $countchartd = [];

        for ($i = 1; $i <= 6; $i++) {
            $countchartb = $warehousingchartb->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->whereDate('ti_i_date', '>', now()->subYear())->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->tie_is_date)->format('m'); // grouping by months
            });

            $countchartd = $warehousingchartd->whereNotNull('ddd.te_logistic_manage_number')->whereDate('tie_is_date', '>', now()->subYear())->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->tie_is_date)->format('m'); // grouping by months
            });
        }

        $chartcountb = [];
        $chartcountd = [];
        $userArrb = [];
        $userArrd = [];

        $userArrb['label'] = '반입';
        $userArrb['borderColor'] = '#F7C35D';
        $userArrb['backgroundColor'] = '#F7C35D';

        $userArrd['label'] = '반출';
        $userArrd['borderColor'] = '#0493FF';
        $userArrd['backgroundColor'] = '#0493FF';

        foreach ($countchartb as $key => $value) {
            $chartcountb[(int)$key] = count($value);
        }

        foreach ($countchartd as $key => $value) {
            $chartcountd[(int)$key] = count($value);
        }

        // for ($i = 1; $i <= 12; $i++) {
        //     if (!empty($chartcountb[$i])) {
        //         $userArrb['data'][] = $chartcountb[$i];
        //     } else {
        //         $userArrb['data'][] = 0;
        //     }

        //     if (!empty($chartcountd[$i])) {
        //         $userArrd['data'][] = $chartcountd[$i];
        //     } else {
        //         $userArrd['data'][] = 0;
        //     }
        // }

        $period = CarbonPeriod::create(today()->subMonths(5), '1 month', today());

        $final = [];

        foreach ($period as $date) {
            $m = (int)$date->format('m');
            if (!empty($chartcountb[$m])) {
                $userArrb['data'][] = $chartcountb[$m];
            } else {
                $userArrb['data'][] = 0;
            }

            if (!empty($chartcountd[$m])) {
                $userArrd['data'][] = $chartcountd[$m];
            } else {
                $userArrd['data'][] = 0;
            }
        }

        return [
            'warehousingb2' => $warehousingb2, 'warehousingd2' => $warehousingd2, 'countcharta' => $userArrb, 'countchartb' => $userArrd, 'counta' => $counta, 'countb' => $countb, 'countc' => $countc, 'countd' => $countd, 'counte' => $counte, 'countg' => $countg, 'countg_2' => $countg_2
        ];

        DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    public function CaculateService1Shop(Request $request)
    {
        //$user = Auth::user();
        DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        $countchartb = [];
        $countchartd = [];
        if ($request->servicechart == "보세화물") {
            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 'tie_is_date', 'tie_status_2')
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                })->where('company.co_no', $request->co_no)->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $warehousingb = $this->subquery($sub, $sub_2, $sub_4);

            $warehousingd = $this->subquery($sub, $sub_2, $sub_4);

            $tie_logistic_manage_number = $this->SQL();

            for ($i = 1; $i <= 6; $i++) {
                $countchartb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->whereDate('tie_is_date', '>', now()->subYear())->get()->groupBy(function ($date) {
                    //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                    return Carbon::parse($date->tie_is_date)->format('m'); // grouping by months
                });

                $countchartd = $warehousingd->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number)->whereDate('tie_is_date', '>', now()->subYear())->get()->groupBy(function ($date) {
                    //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                    return Carbon::parse($date->tie_is_date)->format('m'); // grouping by months
                });
            }
        } elseif ($request->servicechart == "수입풀필먼트") {
            $warehousing2 = Warehousing::join(
                DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no',
                '=',
                'warehousing.w_no'
            )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no', function ($q) use ($request) {
                $q->where('co_no', $request->co_no);
            })->get();
            $w_import_no = collect($warehousing2)->map(function ($q) {

                return $q->w_import_no;
            });
            $w_no_in = collect($warehousing2)->map(function ($q) {

                return $q->w_no;
            });
            $warehousingb = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                ->whereHas('co_no', function ($q) use ($request) {
                    $q->where('co_no', $request->co_no);
                });

            $warehousingd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company', function ($q) use ($request) {
                $q->where('co_no', $request->co_no);
            });

            for ($i = 1; $i <= 6; $i++) {
                $countchartb = $warehousingb->whereDate('created_at', '>', now()->subYear())->get()->groupBy(function ($date) {
                    //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                    return Carbon::parse($date->created_at)->format('m'); // grouping by months
                });

                $countchartd = $warehousingd->whereDate('created_at', '>', now()->subYear())->get()->groupBy(function ($date) {
                    //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                    return Carbon::parse($date->created_at)->format('m'); // grouping by months
                });

                // $countd = $warehousingd->whereYear('created_at', Carbon::now()->year)->get()->groupBy(function ($date) {
                //     //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                //     return Carbon::parse($date->created_at)->format('m'); // grouping by months
                // });
            }
        } elseif ($request->servicechart == "유통가공") {
            $warehousingb = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($request) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($request) {
                    $q->where('co_no', $request->co_no);
                });
            });

            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($request) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')

                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no', function ($q2) use ($request) {
                        $q2->where('co_no', $request->co_no);
                    });
            });

            for ($i = 1; $i <= 6; $i++) {
                $countchartb = $warehousingb->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereDate('warehousing.w_completed_day', '>', now()->subYear())->get()->groupBy(function ($date) {
                    //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                    return Carbon::parse($date->created_at)->format('m'); // grouping by months
                });

                $countchartd = $warehousingd->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereDate('warehousing.w_completed_day', '>', now()->subYear())->get()->groupBy(function ($date) {
                    //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                    return Carbon::parse($date->created_at)->format('m'); // grouping by months
                });
            }
        }

        $chartcountb = [];
        $chartcountd = [];
        $userArrb = [];
        $userArrd = [];

        $userArrb['label'] = '반입';
        $userArrb['borderColor'] = '#F7C35D';
        $userArrb['backgroundColor'] = '#F7C35D';

        $userArrd['label'] = '반출';
        $userArrd['borderColor'] = '#0493FF';
        $userArrd['backgroundColor'] = '#0493FF';

        foreach ($countchartb as $key => $value) {
            $chartcountb[(int)$key] = count($value);
        }

        foreach ($countchartd as $key => $value) {
            $chartcountd[(int)$key] = count($value);
        }

        // for ($i = 1; $i <= 12; $i++) {
        //     if (!empty($chartcountb[$i])) {
        //         $userArrb['data'][] = $chartcountb[$i];
        //     } else {
        //         $userArrb['data'][] = 0;
        //     }

        //     if (!empty($chartcountd[$i])) {
        //         $userArrd['data'][] = $chartcountd[$i];
        //     } else {
        //         $userArrd['data'][] = 0;
        //     }
        // }
        $period = CarbonPeriod::create(today()->subMonths(5), '1 month', today());

        foreach ($period as $date) {
            $m = (int)$date->format('m');

            if (!empty($chartcountb[$m])) {
                $userArrb['data'][] = $chartcountb[$m];
            } else {
                $userArrb['data'][] = 0;
            }

            if (!empty($chartcountd[$m])) {
                $userArrd['data'][] = $chartcountd[$m];
            } else {
                $userArrd['data'][] = 0;
            }
        }


        return [
            'countcharta' => $userArrb, 'countchartb' => $userArrd
        ];

        DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    public function CaculateInvoice($request)
    {
        $user = Auth::user();
        if ($request->co_no != '') {
            $user->mb_type = 'shipper';
            $user->co_no = $request->co_no;
        }
        $warehousing_distribution = ReceivingGoodsDelivery::with(['rate_data_general']);
        $warehousing_fulfillment = ReceivingGoodsDelivery::with(['rate_data_general']);
        $warehousing_bonded = ReceivingGoodsDelivery::with(['rate_data_general']);

        if ($user->mb_type == 'shop') {

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'spasys');
            });

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) use ($user) {
                $q->where('mb_type', 'spasys');
            });
        } else if ($user->mb_type == 'shipper') {

            $warehousing_distribution->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            })->whereHas('mb_no', function ($q) {
                $q->where('mb_type', 'shop');
            })->orderBy('created_at', 'DESC');

            $warehousing_fulfillment->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousing_bonded->whereHas('warehousing', function ($query) use ($user) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });
        } else if ($user->mb_type == 'spasys') {
            $warehousing_distribution->whereNull('rgd_no');
            $warehousing_fulfillment->whereNull('rgd_no');
            $warehousing_bonded->whereNull('rgd_no');
        }

        if ($request->serviceinvoicechart == "보세화물" || $request->service == "보세화물") {

            $warehousing_distribution->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '보세화물');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '보세화물');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '보세화물');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '보세화물');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '보세화물');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '보세화물');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');

>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc
        } else if ($request->serviceinvoicechart == "수입풀필먼트" || $request->service == "수입풀필먼트") {

            $warehousing_distribution->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '수입풀필먼트');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '수입풀필먼트');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '수입풀필먼트');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '수입풀필먼트');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '수입풀필먼트');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '수입풀필먼트');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
         
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc
        } else if ($request->serviceinvoicechart == "유통가공" || $request->service == "유통가공") {

            $warehousing_distribution->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '유통가공');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '유통가공');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '유통가공');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '유통가공');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '유통가공');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '유통가공');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
           
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc
        } else {

            $warehousing_distribution->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '보세화물');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '보세화물');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_fulfillment->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '보세화물');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '보세화물');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc

            $warehousing_bonded->where(function ($q) {
                $q->where('rgd_status4', '=', '예상경비청구서')
                    ->orWhere('rgd_status4', '=', '확정청구서');
            })
<<<<<<< HEAD
                ->whereHas('warehousing', function ($query) {
                    $query->where('w_category_name', '=', '보세화물');
                })
                ->whereNull('rgd_status6')
                ->where('rgd_is_show', 'y');
=======
            ->whereHas('warehousing', function ($query) {
                $query->where('w_category_name', '=', '보세화물');
            })
            ->where(function ($q4){
                                $q4->whereNull('rgd_status5')->orWhere('rgd_status5', '!=', 'cancel');
                            })
                            ->where(function ($q4){
                                $q4->whereNull('rgd_status6')->orWhere('rgd_status6', '!=', 'paid');
                            })
            ->where('rgd_status7', 'taxed')
            ->where('rgd_is_show', 'y');
          
>>>>>>> 163a1a44efecb0a9d89cdeefa3964697b0305cbc
        }

        // foreach ($warehousingg->get() as $i) {
        //     $countg_2 += $i->rate_data_general->rdg_sum6;
        // }

        $warehousingg = $warehousing_distribution->union($warehousing_fulfillment)->union($warehousing_bonded);


        $countchartg = [];

        for ($i = 1; $i <= 6; $i++) {
            $countchartg = $warehousingg->whereDate('created_at', '>', now()->subYear())->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });
        }

        //return $countchartg;

        $userArrd = [];
        $chartcountd = [];

        $userArrd['label'] = '반입';
        $userArrd['borderColor'] = '#F7C35D';
        $userArrd['backgroundColor'] = '#F7C35D';


        if ($request->serviceinvoicechart == "보세화물" || $request->service == "보세화물") {
            foreach ($countchartg as $key => $value) {
                $number  = 0;
                foreach ($value as $ke => $i) {
                    $number += isset($i->rate_data_general->rdg_sum7) ? $i->rate_data_general->rdg_sum7 : 0;
                }
                $chartcountd[(int)$key] = $number;
            }
        } else if ($request->serviceinvoicechart == "수입풀필먼트" || $request->service == "수입풀필먼트") {
            foreach ($countchartg as $key => $value) {
                $number  = 0;
                foreach ($value as $ke => $i) {
                    $number += isset($i->rate_data_general->rdg_sum6) ? $i->rate_data_general->rdg_sum6 : 0;
                }
                $chartcountd[(int)$key] = $number;
            }
        } else if ($request->serviceinvoicechart == "유통가공" || $request->service == "유통가공") {
            foreach ($countchartg as $key => $value) {
                $number  = 0;
                foreach ($value as $ke => $i) {
                    $number += isset($i->rate_data_general->rdg_sum4) ? $i->rate_data_general->rdg_sum4 : 0;
                }
                $chartcountd[(int)$key] = $number;
            }
        } else {
            foreach ($countchartg as $key => $value) {
                $number = 0;
                foreach ($value as $ke => $i) {
                    $number += isset($i->rate_data_general->rdg_sum7) ? $i->rate_data_general->rdg_sum7 : 0;
                }
                $chartcountd[(int)$key] = $number;
            }
        }

        $period = CarbonPeriod::create(today()->subMonths(5), '1 month', today());

        $final = [];

        foreach ($period as $date) {
            $m = (int)$date->format('m');
            if (!empty($chartcountd[$m])) {
                $userArrd['data'][] = $chartcountd[$m];
            } else {
                $userArrd['data'][] = 0;
            }
        }

        // for ($i = 1; $i <= 6; $i++) {
        //     if (!empty($chartcountd[$i])) {
        //         $userArrd['data'][] = $chartcountd[$i];
        //     } else {
        //         $userArrd['data'][] = 0;
        //     }
        // }

        return [
            'userArrd' => $userArrd, 'request' => $request, 'countchartg' => $countchartg, 'period' => $period, 'chartcountd' => $chartcountd
        ];
    }

    public function banner_count(Request $request)
    {
        //return "dsada";
        try {

            //DB::enableQueryLog();
            $total1 = [];
            $total2 = [];
            $total3 = [];
            $totalinvoice = [];

            $charttotal1 = [];
            $chartinvoice = [];
            // $charttotal2 = [];
            // $charttotal3 = [];

            $check = "";
            if ($request->service == "유통가공" || $request->type == "time3") {
                $total3 =  $this->CaculateService3($request);
                $charttotal1[] = $total3['countcharta'];
                $charttotal1[] = $total3['countchartb'];
                $totalinvoice =  $this->CaculateInvoice($request);
                $chartinvoice[] = $totalinvoice['userArrd'];
            } elseif ($request->service == "수입풀필먼트" || $request->type == "time2") {
                $total2 =  $this->CaculateService2($request);
                $charttotal1[] = $total2['countcharta'];
                $charttotal1[] = $total2['countchartb'];
                $totalinvoice =  $this->CaculateInvoice($request);
                $chartinvoice[] = $totalinvoice['userArrd'];
            } elseif ($request->service == "보세화물" || $request->type == "time1") {
                $total1 =  $this->CaculateService1($request);
                $charttotal1[] = $total1['countcharta'];
                $charttotal1[] = $total1['countchartb'];
                $totalinvoice =  $this->CaculateInvoice($request);
                $chartinvoice[] = $totalinvoice['userArrd'];
            } elseif (isset($request->servicechart) && ($request->co_no != "전체" && $request->co_no != "")) {
                $totala1 =  $this->CaculateService1Shop($request); //chart1
                $charttotal1[] = $totala1['countcharta'];
                $charttotal1[] = $totala1['countchartb'];
            } elseif (isset($request->servicechart) && ($request->co_no == "전체" || $request->co_no == "")) {
                //chart1
                if ($request->servicechart == "보세화물") {
                    $totala1 =  $this->CaculateService1($request);
                    $charttotal1[] = $totala1['countcharta'];
                    $charttotal1[] = $totala1['countchartb'];
                } elseif ($request->servicechart == "수입풀필먼트") {
                    $totala2 =  $this->CaculateService2($request);
                    $charttotal1[] = $totala2['countcharta'];
                    $charttotal1[] = $totala2['countchartb'];
                } elseif ($request->servicechart == "유통가공") {
                    $totala3 =  $this->CaculateService3($request);
                    $charttotal1[] = $totala3['countcharta'];
                    $charttotal1[] = $totala3['countchartb'];
                }
            } elseif (isset($request->serviceinvoicechart) && ($request->co_no != "전체" && $request->co_no != "")) {
                //chart2
                $totalinvoice =  $this->CaculateInvoice($request);
                $chartinvoice[] = $totalinvoice['userArrd'];
            } elseif (isset($request->serviceinvoicechart) && ($request->co_no == "전체" || $request->co_no == "")) {
                //chart2
                $totalinvoice =  $this->CaculateInvoice($request);
                $chartinvoice[] = $totalinvoice['userArrd'];
            } else {
                $totalinvoice = $this->CaculateInvoice($request);
                $chartinvoice[] = $totalinvoice['userArrd'];
                $total1 =  $this->CaculateService1($request);
                $total2 =  $this->CaculateService2($request);
                $total3 =  $this->CaculateService3($request);
                $charttotal1[] = $total1['countcharta'];
                $charttotal1[] = $total1['countchartb'];
            }

            return response()->json([
                'message' => Messages::MSG_0007,
                'check' => $check,
                'total1' => $total1,
                'total2' => $total2,
                'total3' => $total3,
                'charttotal1' => $charttotal1,
                'totalinvoice' => $totalinvoice,
                'chartinvoice' => $chartinvoice
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function banner_count_invoice(Request $request)
    {

        try {

            //DB::enableQueryLog();
            $total1 = [];
            $total2 = [];
            $total3 = [];

            $charttotal1 = [];
            // $charttotal2 = [];
            // $charttotal3 = [];

            $check = "";
            if ($request->service == "유통가공" || $request->type == "time3") {
                $total3 =  $this->CaculateService3($request);
                $charttotal1[] = $total3['countcharta'];
                $charttotal1[] = $total3['countchartb'];
            } elseif ($request->service == "수입풀필먼트" || $request->type == "time2") {
                $total2 =  $this->CaculateService2($request);
                $charttotal1[] = $total2['countcharta'];
                $charttotal1[] = $total2['countchartb'];
            } elseif ($request->service == "보세화물" || $request->type == "time1") {
                $total1 =  $this->CaculateService1($request);
                $charttotal1[] = $total1['countcharta'];
                $charttotal1[] = $total1['countchartb'];
            } else {
                $total1 =  $this->CaculateService1($request);
                $total2 =  $this->CaculateService2($request);
                $total3 =  $this->CaculateService3($request);
                $charttotal1[] = $total1['countcharta'];
                $charttotal1[] = $total1['countchartb'];
            }

            return response()->json([
                'message' => Messages::MSG_0007,
                'check' => $check,
                'total1' => $total1,
                'total2' => $total2,
                'total3' => $total3,
                'charttotal1' => $charttotal1,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function CaculateChartService1(Request $request)
    {
        $user = Auth::user();
        DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        if ($user->mb_type == 'shop') {

            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 'tie_is_date', 'tie_status_2')
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $warehousingb = $this->subquery($sub, $sub_2, $sub_4);

            $warehousingd = $this->subquery($sub, $sub_2, $sub_4);
        } else if ($user->mb_type == 'shipper') {

            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 'tie_is_date', 'tie_status_2')
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                })->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);



            $warehousingb = $this->subquery($sub, $sub_2, $sub_4);

            $warehousingd = $this->subquery($sub, $sub_2, $sub_4);
        } else if ($user->mb_type == 'spasys') {
            //FIX NOT WORK 'with'
            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 'tie_is_date', 'tie_status_2')
                ->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })
                ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                ->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number')

                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);



            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')
                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);

            $warehousingb = $this->subquery($sub, $sub_2, $sub_4);

            $warehousingd = $this->subquery($sub, $sub_2, $sub_4);
        }
        $countb = [];
        $countd = [];
        for ($i = 1; $i <= 12; $i++) {
            $countb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->whereYear('tie_is_date', Carbon::now()->year)->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->tie_is_date)->format('m'); // grouping by months
            });
            $tie_logistic_manage_number = $this->SQL();
            $countd = $warehousingd->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number)->whereYear('tie_is_date', Carbon::now()->year)->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->tie_is_date)->format('m'); // grouping by months
            });;
        }

        $chartcountb = [];
        $chartcountd = [];
        $userArrb = [];
        $userArrd = [];

        $userArrb['label'] = '반입';
        $userArrb['borderColor'] = '#F7C35D';
        $userArrb['backgroundColor'] = '#F7C35D';

        $userArrd['label'] = '반출';
        $userArrd['borderColor'] = '#0493FF';
        $userArrd['backgroundColor'] = '#0493FF';

        foreach ($countb as $key => $value) {
            $chartcountb[(int)$key] = count($value);
        }

        foreach ($countd as $key => $value) {
            $chartcountd[(int)$key] = count($value);
        }

        for ($i = 1; $i <= 12; $i++) {
            if (!empty($chartcountb[$i])) {
                $userArrb['data'][] = $chartcountb[$i];
            } else {
                $userArrb['data'][] = 0;
            }

            if (!empty($chartcountd[$i])) {
                $userArrd['data'][] = $chartcountd[$i];
            } else {
                $userArrd['data'][] = 0;
            }
        }

        return [
            'counta' => $userArrb, 'countb' => $userArrd
        ];

        DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    public function CaculateChartService2(Request $request)
    {
        $user = Auth::user();
        DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        if ($user->mb_type == 'shop') {
            $warehousing2 = Warehousing::join(
                DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no',
                '=',
                'warehousing.w_no'
            )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();
            $w_import_no = collect($warehousing2)->map(function ($q) {

                return $q->w_import_no;
            });
            $w_no_in = collect($warehousing2)->map(function ($q) {

                return $q->w_no;
            });
            $warehousinga = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                ->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            // $warehousingb = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
            //     //    $q->where('item_service_name', '=', '수입풀필먼트');
            //     //    $q->whereHas('item_info', function ($e) {
            //     //             $e->whereNotNull('stock');
            //     //     });
            //     $q->whereHas('ContractWms.company.co_parent', function ($k) use ($user) {
            //         $k->where('co_no', $user->co_no);
            //     });
            // })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');


            $warehousingd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });
        } else if ($user->mb_type == 'shipper') {
            $warehousing2 = Warehousing::join(
                DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no',
                '=',
                'warehousing.w_no'
            )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();
            $w_import_no = collect($warehousing2)->map(function ($q) {

                return $q->w_import_no;
            });
            $w_no_in = collect($warehousing2)->map(function ($q) {

                return $q->w_no;
            });
            $warehousinga = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent'])->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')->where('w_category_name', '=', '수입풀필먼트')
                ->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            // $warehousingb = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
            //     //    $q->where('item_service_name', '=', '수입풀필먼트');
            //     //    $q->whereHas('item_info', function ($e) {
            //     //             $e->whereNotNull('stock');
            //     //     });
            //     $q->whereHas('ContractWms.company', function ($k) use ($user) {
            //         $k->where('co_no', $user->co_no);
            //     });
            // })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');


            $warehousingd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });
        } else if ($user->mb_type == 'spasys') {

            $warehousing2 = Warehousing::join(
                DB::raw('( SELECT max(w_no) as w_no, w_import_no FROM warehousing where w_type = "EW" and w_cancel_yn != "y" GROUP by w_import_no ) m'),
                'm.w_no',
                '=',
                'warehousing.w_no'
            )->where('warehousing.w_type', '=', 'EW')->where('w_category_name', '=', '수입풀필먼트')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            })->get();
            $w_import_no = collect($warehousing2)->map(function ($q) {

                return $q->w_import_no;
            });
            $w_no_in = collect($warehousing2)->map(function ($q) {

                return $q->w_no;
            });

            $warehousinga = Warehousing::with('mb_no')
                ->with(['co_no', 'warehousing_item', 'receving_goods_delivery', 'w_import_parent', 'warehousing_child', 'rate_data_general'])->where('w_category_name', '=', '수입풀필먼트')->whereNotIn('w_no', $w_import_no)->where('w_type', 'IW')
                ->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });

            // $warehousingb = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
            //     //    $q->where('item_service_name', '=', '수입풀필먼트');
            //     //    $q->whereHas('item_info', function ($e) {
            //     //             $e->whereNotNull('stock');
            //     //     });
            //     $q->whereHas('ContractWms.company.co_parent.co_parent', function ($k) use ($user) {
            //         $k->where('co_no', $user->co_no);
            //     });
            // })->whereNotNull('stock')->groupby('product_id')->groupby('option_id');



            $warehousingd = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                $q->where('co_no', $user->co_no);
            });
        }
        $counta = [];
        $countb = [];
        $countd = [];

        for ($i = 1; $i <= 12; $i++) {
            $counta = $warehousinga->whereYear('created_at', Carbon::now()->year)->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            $countb = $warehousingd->whereYear('created_at', Carbon::now()->year)->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            // $countd = $warehousingd->whereYear('created_at', Carbon::now()->year)->get()->groupBy(function ($date) {
            //     //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
            //     return Carbon::parse($date->created_at)->format('m'); // grouping by months
            // });
        }
        $chartcounta = [];
        $chartcountb = [];
        $chartcountd = [];
        $userArra = [];
        $userArrb = [];
        //$userArrd = [];

        $userArra['label'] = '반입';
        $userArra['borderColor'] = '#F7C35D';
        $userArra['backgroundColor'] = '#F7C35D';

        $userArrb['label'] = '반출';
        $userArrb['borderColor'] = '#0493FF';
        $userArrb['backgroundColor'] = '#0493FF';

        // $userArrd['label'] = '보관';
        // $userArrd['borderColor'] = '#1EB28C';
        // $userArrd['backgroundColor'] = '#1EB28C';

        foreach ($counta as $key => $value) {
            $chartcounta[(int)$key] = count($value);
        }

        foreach ($countb as $key => $value) {
            $chartcountb[(int)$key] = count($value);
        }

        // foreach ($countd as $key => $value) {
        //     $chartcountd[(int)$key] = count($value);
        // }

        for ($i = 1; $i <= 12; $i++) {
            if (!empty($chartcounta[$i])) {
                $userArra['data'][] = $chartcounta[$i];
            } else {
                $userArra['data'][] = 0;
            }

            if (!empty($chartcountb[$i])) {
                $userArrb['data'][] = $chartcountb[$i];
            } else {
                $userArrb['data'][] = 0;
            }

            // if (!empty($chartcountd[$i])) {
            //     $userArrd['data'][] = $chartcountd[$i];
            // } else {
            //     $userArrd['data'][] = 0;
            // }
        }

        return [
            'counta' => $userArra, 'countb' => $userArrb, //'countc' => $userArrd
        ];

        DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    public function CaculateChartService3(Request $request)
    {
        $user = Auth::user();
        if ($user->mb_type == 'shop') {

            $warehousingb = ReceivingGoodsDelivery::with(['w_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });


            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')

                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });


            // $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
            //     $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
            //         $q->where('co_no', $user->co_no);
            //     });
            // });
        } else if ($user->mb_type == 'shipper') {
            // $warehousinga = ReceivingGoodsDelivery::with(['w_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
            //     $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
            //         $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
            //     })->whereHas('co_no', function ($q) use ($user) {
            //         $q->where('co_no', $user->co_no);
            //     });
            // });

            $warehousingb = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });



            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')

                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });



            // $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
            //     $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
            //         $q->where('co_no', $user->co_no);
            //     });
            // });
        } else if ($user->mb_type == 'spasys') {


            $warehousingb = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });


            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'EW')
                    ->where('rgd_status1', '=', '출고')
                    ->where('rgd_status2', '=', '작업완료')
                    ->where('w_category_name', '=', '유통가공')
                    ->where(function ($q) {
                        $q->where(function ($query) {
                            $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                        })
                            ->orWhereNull('rgd_status4');
                    })->whereHas('co_no.co_parent.co_parent', function ($q2) use ($user) {
                        $q2->where('co_no', $user->co_no);
                    });
            });


            // $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
            //     $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
            //         $q->where('co_no', $user->co_no);
            //     });
            // });
        }


        $countb = [];
        $countd = [];
        //$countg = [];

        for ($i = 1; $i <= 12; $i++) {
            $countb = $warehousingb->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereYear('warehousing.w_completed_day', Carbon::now()->year)->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            $countd = $warehousingd->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereYear('warehousing.w_completed_day', Carbon::now()->year)->get()->groupBy(function ($date) {
                //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
                return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            // $countg = $warehousingg->where('rgd_status1', '!=', '입고예정 취소')->where('rgd_status1', '!=', '출고예정 취소')->whereYear('warehousing.w_completed_day', Carbon::now()->year)->get()->groupBy(function ($date) {
            //     //return Carbon::parse($date->created_at)->format('Y'); // grouping by years
            //     return Carbon::parse($date->created_at)->format('m'); // grouping by months
            // });
        }

        $chartcountb = [];
        $chartcountd = [];
        $chartcountg = [];

        $userArrb = [];
        $userArrd = [];
        $userArrg = [];

        $userArrb['label'] = '반입';
        $userArrb['borderColor'] = '#F7C35D';
        $userArrb['backgroundColor'] = '#F7C35D';

        $userArrd['label'] = '반출';
        $userArrd['borderColor'] = '#0493FF';
        $userArrd['backgroundColor'] = '#0493FF';

        // $userArrg['label'] = '보관';
        // $userArrg['borderColor'] = '#1EB28C';
        // $userArrg['backgroundColor'] = '#1EB28C';


        foreach ($countb as $key => $value) {
            $chartcountb[(int)$key] = count($value);
        }

        foreach ($countd as $key => $value) {
            $chartcountd[(int)$key] = count($value);
        }

        // foreach ($countg as $key => $value) {
        //     $chartcountg[(int)$key] = count($value);
        // }

        for ($i = 1; $i <= 12; $i++) {

            if (!empty($chartcountb[$i])) {
                $userArrb['data'][] = $chartcountb[$i];
            } else {
                $userArrb['data'][] = 0;
            }

            if (!empty($chartcountd[$i])) {
                $userArrd['data'][] = $chartcountd[$i];
            } else {
                $userArrd['data'][] = 0;
            }

            // if (!empty($chartcountg[$i])) {
            //     $userArrg['data'][] = $chartcountg[$i];
            // } else {
            //     $userArrg['data'][] = 0;
            // }
        }

        return [
            'counta' => $userArrb, 'countb' => $userArrd, //'countc' => $userArrg 
        ];
    }

    public function banner_loadchart(Request $request)
    {
        //return "dsada";
        try {

            $user = Auth::user();
            //DB::enableQueryLog();
            $total1 = [];
            $total2 = [];
            $total3 = [];

            $check = "";
            if ($request->servicechart == "유통가공") {
                $totala3 =  $this->CaculateChartService3($request);
                $total3[] = $totala3['counta'];
                $total3[] = $totala3['countb'];
            } elseif ($request->servicechart == "수입풀필먼트") {
                $totala2 =  $this->CaculateChartService2($request);
                $total2[] = $totala2['counta'];
                $total2[] = $totala2['countb'];
            } elseif ($request->servicechart == "보세화물") {
                $totala1 =  $this->CaculateChartService1($request);
                $total1[] = $totala1['counta'];
                $total1[] = $totala1['countb'];
            } else {
                //$total1 =  $this->CaculateChartService1($request);
                //$total2 =  $this->CaculateChartService2($request);
                $total3 =  $this->CaculateChartService3($request);
            }

            return response()->json([
                'message' => Messages::MSG_0007,
                'check' => $check,
                'total1' => $total1,
                'total2' => $total2,
                'total3' => $total3,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function SQL($validated = null)
    {
        //DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        $user = Auth::user();
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

            $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                });
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



            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {


                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                });
        } else if ($user->mb_type == 'spasys') {

            //FIX NOT WORK 'with'
            $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                ->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })
                ->leftjoin('company', function ($join) {
                    $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                })->leftjoin('company as parent_shop', function ($join) {
                    $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
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

            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                });
        }

        $import_schedule = $import_schedule->whereNull('ddd.te_logistic_manage_number')->get();


        //DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        $id = [];
        foreach ($import_schedule as $te) {
            $id[] = $te->tie_logistic_manage_number;
        }
        return $id;
    }
}
