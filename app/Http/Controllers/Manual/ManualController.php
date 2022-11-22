<?php

namespace App\Http\Controllers\Manual;

use App\Models\File;
use App\Models\Manual;
use App\Utils\Messages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Manual\ManualCreateRequest;
use App\Http\Requests\Manual\ManualUpdateRequest;
use App\Http\Requests\Manual\ManualSuneditorRequest;
use Illuminate\Http\Request;

class ManualController extends Controller
{
    /**
     * Fetch Manual by id
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function getManualById(Manual $manual)
    {
        $f = $manual->file()->first();
        if (isset($f)) {
            $file = Storage::url($f->file_url);
            $manual['file'] = $file;
        }
        return response()->json($manual);
    }

    /**
     * Handle the incoming request.
     *
     * @param  \App\Http\Requests\Manual\ManualCreateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function create(ManualCreateRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            foreach ($request->data_menu as $val) {
            $manual_no = Manual::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'man_title' => $val['man_title'],
                'man_content' => $val['man_content'],
                'man_note' => $val['man_note'],
            ]);

            
            }
            foreach ($validated['file'] as $val) {
            $path = join('/', ['files', 'manual', $manual_no]);
            $url = Storage::disk('public')->put($path, $val);
            File::insert([
                'file_table' => 'manual',
                'file_table_key' => $manual_no,
                'file_name_old' => $val->getClientOriginalName(),
                'file_name' => basename($url),
                'file_size' => $val->getSize(),
                'file_extension' => $val->extension(),
                'file_position' => 0,
                'file_url' => $url
            ]);
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'notice_no' => $manual_no
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update Manual by id
     * @param  ManualUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(ManualUpdateRequest $request, Manual $manual)
    {
        try {
            $validated = $request->validated();
            Log::error($validated['man_content']);
            $manual->man_title = $validated['man_title'];
            $manual->man_content = $validated['man_content'];
            $manual->man_note = $validated['man_note'];
            $manual->save();

            //FILE PART

            if (isset($validated['file'])) {
                $path = join('/', ['files', 'manual']);

                // remove old image
                $file = File::where('file_table_key', $manual->man_no)->where('file_table', 'manual')->first();
                $url = Storage::disk('public')->delete($file->file_url);
                $file->delete();

                $path = join('/', ['files', 'manual', $manual->man_no]);
                $url = Storage::disk('public')->put($path, $validated['file']);

                File::insert([
                    'file_table' => 'manual',
                    'file_table_key' => $manual->man_no,
                    'file_name_old' => $validated['file']->getClientOriginalName(),
                    'file_name' => basename($url),
                    'file_size' => $validated['file']->getSize(),
                    'file_extension' => $validated['file']->extension(),
                    'file_position' => 0,
                    'file_url' => $url
                ]);
            }

            DB::commit();

            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    /**
     * Upload image to suneditor
     */
    public function suneditor(ManualSuneditorRequest $request)
    {
        $validated = $request->validated();
        $path = join('/', ['files', 'suneditor', '21']);
        $url = Storage::disk('public')->put($path, $validated['file']);

        return response()->json([
            'message' => Messages::MSG_0007,
            'url' => Storage::url($url)
        ], 201);;
    }
}
