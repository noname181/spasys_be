<?php

namespace App\Http\Controllers\Qna;

use App\Http\Requests\Qna\QnaRequest;
use App\Http\Requests\Qna\QnaRegisterRequest;
use App\Http\Requests\Qna\QnaUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Qna;
use App\Utils\Messages;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QnaController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\Qna\QnaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(QnaRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $qna = Qna::paginate($per_page, ['*'], 'page', $page);

            return response()->json($qna);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Fetch qna by id
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function getById(Qna $qna)
    {
        return response()->json($qna);
    }

    /**
     * Register qna
     * @param  QnaRegisterRequest $request
     * @return \Illuminate\Http\Response
     */
    public function register(QnaRegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            // FIXME hard set mb_no = 1
            $qna_no = Qna::insertGetId([
                'mb_no' => 1,
                'qna_status' => 'wating',
                'mb_no_target' => $validated['mb_no_target'],
                'qna_title' => $validated['qna_title'],
                'qna_content' => $validated['qna_content']
            ]);

            $path = join('/', ['files', 'qna', $qna_no]);

            $files = [];
            foreach($validated['files'] as $key => $file) {
                $url = Storage::disk('public')->put($path, $file);
                $files[] = [
                    'file_table' => 'qna',
                    'file_table_key' => $qna_no,
                    'file_name' => basename($url),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->extension(),
                    'file_position' => $key,
                    'file_url' => $url
                ];
            }

            File::insert($files);

            return response()->json(['message' => Messages::MSG_0007, 'qna_no' => $qna_no], 201);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update qna by id
     * @param  Qna $qna
     * @param  QnaUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(Qna $qna, QnaUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $qna->update([
                "qna_content" => $validated['qna_content'],
                "qna_status" => $validated['qna_status']
            ]);
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}
