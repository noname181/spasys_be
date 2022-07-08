<?php

namespace App\Http\Controllers\Qna;

use App\Http\Requests\Qna\QnaRequest;
use App\Http\Requests\Qna\QnaRegisterRequest;
use App\Http\Requests\Qna\QnaUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Qna;
use App\Utils\Messages;
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
            $qna_no = Qna::insertGetId([
                'mb_no' => $validated['mb_no'],
                'qna_status' => 'wating',
                'mb_no_target' => $validated['mb_no_target'],
                'qna_title' => $validated['qna_title'],
                'qna_content' => $validated['qna_content']
            ]);
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
