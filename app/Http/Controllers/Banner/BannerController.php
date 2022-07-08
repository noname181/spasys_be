<?php

namespace App\Http\Controllers\Banner;

use App\Http\Requests\Banner\BannerRequest;
use App\Http\Requests\Banner\BannerRegisterRequest;
use App\Http\Requests\Banner\BannerUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\File;
use App\Utils\Messages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
            foreach($banner->items() as $d) {
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
            $banner_no = Banner::insertGetId([
                'banner_title' => $validated['banner_title'],
                'banner_lat' => $validated['banner_lat'],
                'banner_lng' => $validated['banner_lng'],
                'banner_start' => $validated['banner_start'],
                'banner_end' => $validated['banner_end'],
                'banner_use_yn' => $validated['banner_use_yn'],
                'banner_sliding_yn' => $validated['banner_sliding_yn'],
                'mb_no' => $validated['mb_no']
            ]);

            $path = join('/', ['files', 'banner', $banner_no]);

            $files = [];
            foreach($validated['files'] as $key => $file) {
                $url = Storage::disk('public')->put($path, $file);
                $files[] = [
                    'file_table' => 'banner',
                    'file_table_key' => $banner_no,
                    'file_name' => basename($url),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->extension(),
                    'file_position' => $key,
                    'file_url' => $url
                ];
            }

            File::insert($files);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007, 'banner_no' => $banner_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update banner by id
     * @param  Banner $banner
     * @param  BannerUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(Banner $banner, BannerUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $banner->update([
                'banner_title' => $validated['banner_title'],
                'banner_lat' => $validated['banner_lat'],
                'banner_lng' => $validated['banner_lng'],
                'banner_start' => $validated['banner_start'],
                'banner_end' => $validated['banner_end'],
                'banner_use_yn' => $validated['banner_use_yn'],
                'banner_sliding_yn' => $validated['banner_sliding_yn'],
                'mb_no' => $validated['mb_no'],
            ]);
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}
