<?php

namespace App\Http\Controllers\API;

use App\Models\Notice;
use App\Utils\Messages;
use App\Utils\CommonFunc;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notice\NoticeRequest;
use App\Http\Requests\Notice\NoticeCreateRequest;
use App\Http\Requests\Notice\NoticeUpdateRequest;

class NoticeController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\Notice\NoticeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(NoticeRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $notice = Notice::paginate($per_page, ['*'], 'page', $page);

            return response()->json($notice);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(NoticeCreateRequest $request)
    {
        $validated = $request->validated();
        try {
            $notice_no = Notice::insertGetId([
                'mb_no' => $validated['mb_no'],
                'notice_title' => $validated['notice_title'],
                'notice_content' => $validated['notice_content'],
                'notice_target' => $validated['notice_target'],
            ]);
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'notice_no' => $notice_no,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Fetch Notice by id
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function getNoticeById(Request $request)
    {

        $notice = Notice::find($request);
        if (!empty($notice)) {
            return response()->json(
                ['message' => Messages::MSG_0007,
                 'data' => $notice
                ], 200);
        } else {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0016, ['Notice'])], 400);
        }
    }

        /**
     * Update Notice by id
     * @param  Notice $qna
     * @param  QnaUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(Notice $notice, NoticeUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $notice->update([
                "notice_no" => $validated['notice_no'],
                "notice_content" => $validated['notice_content']
            ]);
            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }
}
