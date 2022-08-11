<?php

namespace App\Http\Controllers\Notice;

use DateTime;
use App\Models\File;
use App\Models\Member;
use App\Models\Notice;
use App\Utils\Messages;
use App\Utils\CommonFunc;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use App\Http\Requests\Notice\NoticeRequest;
use App\Http\Requests\Notice\NoticeCreateRequest;
use App\Http\Requests\Notice\NoticeSearchRequest;
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
            $notice = Notice::with('files')->paginate($per_page, ['*'], 'page', $page);

            // foreach ($notice->items() as $d) {
            //     $d['files'] = $d->files()->get();
            // }

            return response()->json($notice);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function searchNotice(NoticeRequest $request)
    {

        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $notice = Notice::with('files')->where('notice_title', 'like', '%'. $request->keyword .'%')->paginate($per_page, ['*'], 'page', $page);

            // foreach ($notice->items() as $d) {
            //     $d['files'] = $d->files()->get();
            // }

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
            //DB::beginTransaction();
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $notice_no = Notice::insertGetId([
                'mb_no' => $member->mb_no,
                'notice_title' => $validated['notice_title'],
                'notice_content' => $validated['notice_content'],
                'notice_target' => $validated['notice_target'],
            ]);

            $path = join('/', ['files', 'notice', $notice_no]);

            $files = [];

            foreach($validated['files'] as $key => $file) {
                $url = Storage::disk('public')->put($path, $file);
                $files[] = [
                    'file_table' => 'notice',
                    'file_table_key' => $notice_no,
                    'file_name_old' => $file->getClientOriginalName(),
                    'file_name' => basename($url),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->extension(),
                    'file_position' => $key,
                    'file_url' => $url
                ];
            }

            File::insert($files);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'file' => $files,
                'notice_no' => $notice_no
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
     * @param  Notice $notice
     * @param  QnaUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(NoticeUpdateRequest $request)
    {
        // return $request->hasFile('files');
        try {
            $validated = $request->validated();
            $notice = Notice::where('notice_no', $validated['notice_no'])
                ->where('mb_no', Auth::user()->mb_no)
                ->update([
                    'notice_title' => $validated['notice_title'],
                    'notice_content' => $validated['notice_content'],
            ]);

            //FILE PART

            $path = join('/', ['files', 'notice', $validated['notice_no']]);

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

                $max_position_file = File::where('file_table', 'notice')->where('file_table_key', $validated['notice_no'])->orderBy('file_position', 'DESC')->get()->first();
                if($max_position_file)
                    $i = $max_position_file->file_position + 1;
                else
                    $i = 0;

                foreach($validated['files'] as $key => $file) {
                    $url = Storage::disk('public')->put($path, $file);
                    $files[] = [
                        'file_table' => 'notice',
                        'file_table_key' => $validated['notice_no'],
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


            DB::commit();

            return response()->json(['message' => Messages::MSG_0007], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0005], 500);
        }
    }

     /**
     * Get Notice
     * @param  NoticeSearchRequest $request
     */
    public function getNotice(NoticeSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 5;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $notices = Notice::with('files')->orderBy('notice_no', 'DESC');

            // if (isset($validated['from_date'])) {
            //     $notice->where('created_at', '>=', Date::parse($this->formatDate($validated['from_date']))->startOfDay()->format('Y-m-d H:i:s'));
            // }

            // if (isset($validated['to_date'])) {
            //     $notice->where('created_at', '<=', Date::parse($this->formatDate($validated['to_date']))->endOfDay()->format('Y-m-d H:i:s'));
            // }

            if (isset($validated['from_date'])) {
                $notices->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $notices->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['search_string'])) {
                $notices->where(function($query) use ($validated) {
                    $query->where('notice_title', 'like', '%' . $validated['search_string'] . '%');
                    $query->orWhere('notice_content', 'like', '%' . $validated['search_string'] . '%');
                });
            }

            $members = Member::where('mb_no', '!=', 0)->get();
            //$sql = $notice->toSql();


            $notices = $notices->paginate($per_page, ['*'], 'page', $page);

            // 'sql' => $sql,
            // 'from_date' => date('Y-m-d H:i:s', strtotime($validated['from_date'])),
            // 'to_date' => date('Y-m-d 23:59:00', strtotime($validated['to_date']))

            return response()->json($notices);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
            //return $e;
        }
    }

    public function delete(Request $request)
    {
        if (isset($request->notice_no)) {
            $notice = Notice::where('notice_no', $request->notice_no)->get()->first();

            $path = join('/', ['files', 'notice', $request->notice_no]);

            // remove old image


            Storage::disk('public')->deleteDirectory($path);
            $notice->delete();
            File::where('file_table', 'notice')->where('file_table_key', $request->notice_no)->delete();
            return response()->json(['message' => Messages::MSG_0007], 200);

        }
    }

    private function formatDate($dateStr) {
        return DateTime::createFromFormat('j/n/Y', $dateStr)->format('Y-m-d');
    }
}
