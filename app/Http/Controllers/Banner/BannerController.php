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


            $banner = Banner::with('mb_no')->with('files')->where('mb_no', $user->mb_no)->orderBy('banner_no', 'DESC');

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
                ->first();

            $banner_login_right_top = Banner::with('files')
                ->where('banner_position', '=', '로그인')
                ->where('banner_position_detail', '=', '오른쪽 상단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->first();

            $banner_login_right_bottom = Banner::with('files')
                ->where('banner_position', '=', '로그인')
                ->where('banner_position_detail', '=', '오른쪽 하단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->first();

            $banner_index_top = Banner::with('files')
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '상단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->first();

            $banner_index_bottom = Banner::with('files')
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '하단')
                ->where('banner_use_yn', '=', '1')
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
                ->where('mb_no', $user->mb_no)
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '상단')
                ->where('banner_use_yn', '=', '1')
                ->where('banner_start', '<=', $today)
                ->where('banner_end', '>=', $today)->latest('created_at')
                ->first();

            $banner_index_bottom = Banner::with('files')
                ->where('mb_no', $user->mb_no)
                ->where('banner_position', '=', '메인')
                ->where('banner_position_detail', '=', '하단')
                ->where('banner_use_yn', '=', '1')
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

            $warehousingb = ReceivingGoodsDelivery::with(['w_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
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
                })->orwhere('rgd_status1', '=', '출고예정 취소')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingd = ReceivingGoodsDelivery::with(['warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
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

            $warehousinge = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업대기')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingf = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업중')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });
        } else if ($user->mb_type == 'shipper') {
            $warehousinga = ReceivingGoodsDelivery::with(['w_no'])->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('w_category_name', '=', '유통가공')->where(function ($q) {
                    $q->where('rgd_status1', '!=', '입고')->orWhereNull('rgd_status1');
                })->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingb = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
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
                })->orwhere('rgd_status1', '=', '출고예정 취소')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no', function ($q) use ($user) {
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

            $warehousinge = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업대기')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingf = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업중')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                });
            });

            $warehousingg = ReceivingGoodsDelivery::with(['warehousing'])->whereNull('rgd_parent_no')->whereHas('warehousing', function ($query) use ($user) {
                $query->where('w_type', '=', 'IW')->where('rgd_status1', '=', '입고')->where('rgd_status2', '=', '작업완료')->where('w_category_name', '=', '유통가공')->whereHas('co_no', function ($q) use ($user) {
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
                })->orwhere('rgd_status1', '=', '출고예정 취소')->where('w_category_name', '=', '유통가공')->whereNotNull('w_import_no')->whereNull('w_children_yn')->whereHas('co_no.co_parent.co_parent', function ($q) use ($user) {
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
        }

        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;
        $f = 0;
        $g = 0;
        $h = 0;
        if ($request->time3 == 'day') {
            $warehousinga = $warehousinga->get();
            $warehousingb = $warehousingb->where('warehousing.w_completed_day', '>=', Carbon::now()->subDay()->toDateTimeString())->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->where('warehousing.w_completed_day', '>=', Carbon::now()->subDay()->toDateTimeString())->get();
            $warehousinge = $warehousinge->get();
            $warehousingf = $warehousingf->get();
            $warehousingg = $warehousingg->where('warehousing.w_completed_day', '>=', Carbon::now()->subDay()->toDateTimeString())->get();
        } elseif ($request->time3 == 'week') {
            $warehousinga = $warehousinga->get();
            $warehousingb = $warehousingb->where('warehousing.w_completed_day', '>=', Carbon::now()->subWeek()->toDateTimeString())->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->where('warehousing.w_completed_day', '>=', Carbon::now()->subWeek()->toDateTimeString())->get();
            $warehousinge = $warehousinge->get();
            $warehousingf = $warehousingf->get();
            $warehousingg = $warehousingg->where('warehousing.w_completed_day', '>=', Carbon::now()->subWeek()->toDateTimeString())->get();
        } else {
            $warehousinga = $warehousinga->get();
            $warehousingb = $warehousingb->where('warehousing.w_completed_day', '>=', Carbon::now()->subMonth()->toDateTimeString())->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->where('warehousing.w_completed_day', '>=', Carbon::now()->subMonth()->toDateTimeString())->get();
            $warehousinge = $warehousinge->get();
            $warehousingf = $warehousingf->get();
            $warehousingg = $warehousingg->where('warehousing.w_completed_day', '>=', Carbon::now()->subMonth()->toDateTimeString())->get();
        }

        $counta = 0;
        $countb = 0;
        $countc = 0;
        $countd = 0;
        $counte = 0;
        $countf = 0;
        $countg = 0;

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

        return [
            'warehousingb' => $warehousingd, 'a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e, 'f' => $f, 'h' => $h, 'g' => $g,
            'counta' => $counta, 'countb' => $countb, 'countc' => $countc, 'countd' => $countd, 'counte' => $counte, 'countf' => $countf, 'countg' => $countg

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
        }
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
        $countf = 0;
        $countg = 0;

        if ($request->time2 == 'day') {
            $warehousinga = $warehousinga->where('created_at', '>=', Carbon::now()->subDay()->toDateTimeString())->get();
            $warehousingb = $warehousingb->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->where('created_at', '>=', Carbon::now()->subDay()->toDateTimeString())->get();
        } elseif ($request->time2 == 'week') {
            $warehousinga = $warehousinga->where('created_at', '>=', Carbon::now()->subWeek()->toDateTimeString())->get();
            $warehousingb = $warehousingb->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->where('created_at', '>=', Carbon::now()->subWeek()->toDateTimeString())->get();
        } else {
            $warehousinga = $warehousinga->where('created_at', '>=', Carbon::now()->subMonth()->toDateTimeString())->get();
            $warehousingb = $warehousingb->get();
            $warehousingc = $warehousingc->get();
            $warehousingd = $warehousingd->where('created_at', '>=', Carbon::now()->subMonth()->toDateTimeString())->get();
        }

        $counta = $warehousinga->count();
        $countb = $warehousingb->count();
        $countc = $warehousingc->count();
        $countd = $warehousingd->count();

        foreach ($warehousinga as $item) {
            $a += $item->w_amount;
        }

        foreach ($warehousingb as $item) {
            $item4 = Item::with(['item_info'])->where('item.item_no', $item->item_no)->first();
            if (isset($item4['item_info']['stock'])) {
                $b += $item4['item_info']['stock'];
            }
        }

        foreach ($warehousingb as $item) {
            $item6 = Item::with(['item_info'])->where('item.item_no', $item->item_no)->first();
            if (isset($item4['item_info']['stock'])) {
                $b_2 += $item6->item_price2 * $item6['item_info']['stock'];
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

        return [
            'warehousinga' => $warehousinga, 'counta' => $counta, 'countb' => $countb, 'countc' => $countc, 'countd' => $countd, 'a' => $a, 'b' => $b, 'b_2' => $b_2, 'c' => $c, 'd' => $d, 'e' => $e, 'f' => $f, 'h' => $h, 'g' => $g,
        ];
        DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    public function CaculateService1(Request $request)
    {
        $user = Auth::user();
        DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        if ($user->mb_type == 'shop') {

            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number','aaa.tie_is_date')
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
        } else if ($user->mb_type == 'shipper') {

            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number','tie_is_date')
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
        } else if ($user->mb_type == 'spasys') {
            //FIX NOT WORK 'with'
            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number','tie_is_date')
                ->leftjoin('company as parent_spasys', function ($join) {
                    $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                })
                ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                ->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number')

                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
            //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')
                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $warehousinga = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });

            $warehousingb = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });

            $warehousingd = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {
                $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            });
        }

        $counta = 0;
        $countb = 0;
        $countc = 0;
        $countd = 0;
        $counte = 0;
        $countf = 0;
        $countg = 0;

        if ($request->time1 == 'day') {
            $counta = $warehousinga->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->get()->count();
            $countb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->where('aaa.tie_is_date', '>=', Carbon::now()->subDay()->toDateTimeString())->get()->count();
            $tie_logistic_manage_number = $this->SQL();
            $countd = $warehousingd->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number)->where('aaa.tie_is_date', '>=', Carbon::now()->subDay()->toDateTimeString())->get()->count();

            $countc = $countb -  $countd;
        } elseif ($request->time1 == 'week') {
            $counta = $warehousinga->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->get()->count();
            $countb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->where('aaa.tie_is_date', '>=', Carbon::now()->subWeek()->toDateTimeString())->get()->count();
            $tie_logistic_manage_number = $this->SQL();
            $countd = $warehousingd->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number)->where('aaa.tie_is_date', '>=', Carbon::now()->subWeek()->toDateTimeString())->get()->count();

            $countc = $countb -  $countd;
        } else {
            $counta = $warehousinga->whereNotNull('aaa.tie_logistic_manage_number')->whereNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->get()->count();
            $countb = $warehousingb->whereNotNull('bbb.ti_logistic_manage_number')->whereNull('ddd.te_logistic_manage_number')->where('aaa.tie_is_date', '>=', Carbon::now()->subMonth()->toDateTimeString())->get()->count();
            $tie_logistic_manage_number = $this->SQL();
            $countd = $warehousingd->whereNotIn('tie_logistic_manage_number', $tie_logistic_manage_number)->where('aaa.tie_is_date', '>=', Carbon::now()->subMonth()->toDateTimeString())->get()->count();

            $countc = $countb -  $countd;
        }


        return [
            'warehousingb' => $warehousingb, 'counta' => $counta, 'countb' => $countb, 'countc' => $countc, 'countd' => $countd
        ];

        DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    public function banner_count(Request $request)
    {
        //return "dsada";
        try {

            $user = Auth::user();
            //DB::enableQueryLog();
            $total1 = [];
            $total2 = [];
            $total3 = [];

            $check = "";
            if ($request->service == "유통가공" || $request->type == "time3") {
                $total3 =  $this->CaculateService3($request);
            } elseif ($request->service == "수입풀필먼트" || $request->type == "time2") {
                $total2 =  $this->CaculateService2($request);
            } elseif ($request->service == "보세화물" || $request->type == "time1") {
                $total1 =  $this->CaculateService1($request);     
            } else {
                $total1 =  $this->CaculateService1($request);
                $total2 =  $this->CaculateService2($request);
                $total3 =  $this->CaculateService3($request);
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

        // If per_page is null set default data = 15
        $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
        // If page is null set default data = 1
        $page = isset($validated['page']) ? $validated['page'] : 1;

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



            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {


                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('te_carry_out_number', 'DESC');
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
                })->orderBy('ti_logistic_manage_number', 'DESC')->orderBy('te_logistic_manage_number', 'DESC');
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
