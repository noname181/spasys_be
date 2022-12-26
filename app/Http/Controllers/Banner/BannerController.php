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
             //FILE PART

             $path = join('/', ['files', 'banner', $validated['banner_no']]);

             // remove old image

             if($request->remove_files){
                 foreach($request->remove_files as $key => $file_no) {
                     $file = File::where('file_no', $file_no)->get()->first();
                     $url = Storage::disk('public')->delete($path. '/' . $file->file_name);
                     $file->delete();
                 }
             }

             if($request->hasFile('files')){
                 $files = [];

                 $max_position_file = File::where('file_table', 'banner')->where('file_table_key', $validated['banner_no'])->orderBy('file_position', 'DESC')->get()->first();
                 if($max_position_file)
                     $i = $max_position_file->file_position + 1;
                 else
                     $i = 0;

                 foreach($validated['files'] as $key => $file) {
                     $url = Storage::disk('public')->put($path, $file);
                     $files[] = [
                         'file_table' => 'banner',
                         'file_table_key' => $validated['banner_no'],
                         'file_name_old' => $file->getClientOriginalName(),
                         'file_name' => basename($url),
                         'file_size' => $file->getSize(),
                         'file_extension' => $file->extension(),
                         'file_position' => $i,
                         'file_url' => $url
                     ];
                     $i++;
                 }

                File::insert($files);

             }

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
            $banner = Banner::with('mb_no')->with('files')->orderBy('banner_no', 'DESC');

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

            return response()->json([
                'message' => Messages::MSG_0007,
                'banner_login_left' => $banner_login_left,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}
