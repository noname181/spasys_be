<?php

namespace App\Http\Controllers\Qna;

use App\Http\Requests\Qna\QnaRequest;
use App\Http\Requests\Qna\QnaRegisterRequest;
use App\Http\Requests\Qna\QnaUpdateRequest;
use App\Http\Requests\Qna\QnaSearchRequest;
use App\Http\Requests\Qna\QnaReplyRequest;
use App\Http\Controllers\Controller;
use App\Models\Qna;
use App\Utils\Messages;
use App\Models\File;
use App\Models\Member;
use DateTime;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

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
            $qna = Qna::with('mb_no_target')->with('mb_no')->with('member')->with('files')->paginate($per_page, ['*'], 'page', $page);

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
        $qna['files'] = $qna->files()->get();
        $qna['mb_no_target'] = $qna->mb_no_target()->first();
        $qna['mb_no'] = $qna->mb_no()->first();
        return response()->json($qna);
    }

    /**
     * Register qna
     * @param  QnaRegisterRequest $request
     * @return \Illuminate\Http\Response
     */
    public function register(QnaRegisterRequest $request)
    {

        $validated = $request->validated();
        try {
            //DB::beginTransaction();
            // FIXME hard set mb_no = 1
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $qna_no = Qna::insertGetId([
                'mb_no' => $member->mb_no,
                'qna_status' => 'receipt',
                'mb_no_target' => $validated['mb_no_target'],
                'qna_title' => $validated['qna_title'],
                'qna_content' => $validated['qna_content'],
                'answer_for' => 0,
                'depth_path' => '',
                'depth_level' => 0,
            ]);

            $path = join('/', ['files', 'qna', $qna_no]);

            $files = [];
            foreach ($validated['files'] as $key => $file) {
                $url = Storage::disk('public')->put($path, $file);
                $files[] = [
                    'file_table' => 'qna',
                    'file_table_key' => $qna_no,
                    'file_name' => basename($url),
                    'file_name_old' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->extension(),
                    'file_position' => $key,
                    'file_url' => $url
                ];
            }

            File::insert($files);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007, 'qna_no' => $qna_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }


    public function reply_qna(QnaReplyRequest $request)
    {

        $validated = $request->validated();

        try {
            DB::beginTransaction();
            // FIXME hard set mb_no = 1
            $depth_level = Qna::where('qna_no' ,'=' ,$validated['qna_no'])->first()['depth_level'];
            $answer_for = Qna::where('qna_no' ,'=' ,$validated['qna_no'])->first()['answer_for'];
            $member = Member::where('mb_id', Auth::user()->mb_id)->first();
            $depth_level = $depth_level + 1;
            $qna_no = Qna::insertGetId([
                'mb_no' => $member->mb_no,
                'qna_status' => 'receipt',
                'mb_no_target' => 1,
                'qna_title' => $validated['qna_title'],
                'qna_content' => $validated['qna_content'],
                'answer_for' => $answer_for == 0 ? $validated['qna_no'] : $answer_for,
                'depth_path' => '',
                'depth_level' => $depth_level,
            ]);

            $path = join('/', ['files', 'qna', $qna_no]);

            $files = [];
            foreach ($validated['files'] as $key => $file) {
                $url = Storage::disk('public')->put($path, $file);
                $files[] = [
                    'file_table' => 'qna',
                    'file_table_key' => $qna_no,
                    'file_name' => basename($url),
                    'file_name_old' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_extension' => $file->extension(),
                    'file_position' => $key,
                    'file_url' => $url
                ];
            }

            File::insert($files);

            DB::commit();
            return response()->json(['message' => Messages::MSG_0007, 'qna_no' => $qna_no], 201);
        } catch (\Exception $e) {
            DB::rollback();
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
            $qna = Qna::where('qna_no', $validated['qna_no'])
                ->where('mb_no', Auth::user()->mb_no)
                ->update([
                    'qna_title' => $validated['qna_title'],
                    'qna_content' => $validated['qna_content'],
                    'qna_status' => $validated['qna_status']
            ]);

            //FILE PART

            $path = join('/', ['files', 'qna', $validated['qna_no']]);

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

                $max_position_file = File::where('file_table', 'qna')->where('file_table_key', $validated['qna_no'])->orderBy('file_position', 'DESC')->get()->first();
                if($max_position_file)
                    $i = $max_position_file->file_position + 1;
                else
                    $i = 0;

                foreach($validated['files'] as $key => $file) {
                    $url = Storage::disk('public')->put($path, $file);
                    $files[] = [
                        'file_table' => 'qna',
                        'file_table_key' => $validated['qna_no'],
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
            //return response()->json(['message' => Messages::MSG_0005], 500);

        }
    }

    /**
     * Get QnA
     * @param  QnaSearchRequest $request
     */
    public function getQnA(QnaSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $qna = Qna::where(function ($query) {
                $query->where('mb_no_target', '=', Auth::user()->mb_no)
                      ->orWhere('mb_no', '=', Auth::user()->mb_no);
            })->with(['mb_no_target'=>function($query){
                $query->select(['mb_name','mb_no']);
            }])->with(['mb_no'=>function($query){
                $query->select(['mb_name','mb_no']);
            }])->with('files')->with(['childQna'=>function($query){

                $query->with('files')->with(['mb_no_target'=>function($query){
                    $query->select(['mb_name','mb_no']);
                }])->with(['mb_no'=>function($query){
                    $query->select(['mb_name','mb_no']);
                }]);

            }])->with(['member' => function($query){

            }])
            ->orderBy('qna_no', 'DESC')->where('depth_level', '=', '0');

            if (isset($validated['from_date'])) {
                $qna->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $qna->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['qna_title'])) {
                $qna->where('qna_title', 'like', '%' . $validated['qna_title'] . '%');
            }

            if (isset($validated['qna_content'])) {
                $qna->where('qna_content', 'like', '%' . $validated['qna_content'] . '%');
            }

            if (isset($validated['qna_status1']) || isset($validated['qna_status2']) || isset($validated['qna_status3'])) {
                $qna->where(function($query) use ($validated) {
                    $query->orwhere('qna_status', '=', $validated['qna_status1']);
                    $query->orWhere('qna_status', '=', $validated['qna_status2']);
                    $query->orWhere('qna_status', '=', $validated['qna_status3']);
                });
            }



            if (isset($validated['search_string'])) {
                $qna->where(function($query) use ($validated) {
                    $query->where('qna_title', 'like', '%' . $validated['search_string'] . '%');
                    $query->orWhere('qna_content', 'like', '%' . $validated['search_string'] . '%');
                });
            }

            $members = Member::where('mb_no', '!=', 0)->get();

            $qna = $qna->paginate($per_page, ['*'], 'page', $page);

            return response()->json($qna);
        } catch (\Exception $e) {
            Log::error($e);

            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function delete_qna(QnaRequest $request){
        $check = Qna::where('qna_no',$request->qna_no)->update(['qna_status'=>'삭제']);
        return response()->json(['status'=> $check]);
    }

    private function formatDate($dateStr) {
        return DateTime::createFromFormat('j/n/Y', $dateStr)->format('Y-m-d');
    }
}
