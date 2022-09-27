<?php

namespace App\Http\Controllers\ImportSchedule;

use DateTime;
use App\Models\File;
use App\Models\Member;
use App\Models\ImportSchedule;
use App\Models\Import;
use App\Models\ImportExpected;

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
use App\Http\Requests\ImportSchedule\ImportScheduleRequest;
use App\Http\Requests\ImportSchedule\ImportScheduleSearchRequest;

class ImportScheduleController extends Controller
{
    /**
     * Fetch data
     * @param  \App\Http\Requests\ImportSchedule\ImportScheduleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ImportScheduleRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $import_schedule = ImportSchedule::with('co_no')->with('files')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    /**
     * Get ImportSchedule
     * @param  ImportScheduleSearchRequest $request
     */
    public function getImportSchedule(ImportScheduleSearchRequest $request)
    {
        try {
           
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $import_schedule = ImportSchedule::with('co_no')->with('files')->orderBy('i_no', 'DESC');
            

            if (isset($validated['from_date'])) {
                $import_schedule->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name'])) {
                $import_schedule->whereHas('co_no', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }


            $members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function getImportAPI(ImportScheduleSearchRequest $request)
    {
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $import_schedule = ImportExpected::with(['import','company'])->orderBy('tie_no', 'DESC');
           
            if (isset($validated['from_date'])) {
                $import_schedule->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $import_schedule->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $companies->whereHas('company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(company.co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $import_schedule->whereHas('company', function($q) use($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['m_bl'])) {
                $import_schedule->where(DB::raw('tie_m_bl'), 'like', '%' . strtolower($validated['m_bl']) . '%');
            }

            if (isset($validated['h_bl'])) {
                $import_schedule->where(DB::raw('tie_h_bl'), 'like', '%' . strtolower($validated['h_bl']) . '%');
            }

            if (isset($validated['logistic_manage_number'])) {
                $import_schedule->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            }

            // if (isset($validated['import_schedule_status1']) || isset($validated['import_schedule_status2'])) {
            //     $import_schedule->where(function($query) use ($validated) {
            //         $query->orwhere('import_schedule_status', '=', $validated['import_schedule_status1']);
            //         $query->orWhere('import_schedule_status', '=', $validated['import_schedule_status2']);
            //     });
            // }

            //$members = Member::where('mb_no', '!=', 0)->get();

            $import_schedule = $import_schedule->paginate($per_page, ['*'], 'page', $page);

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function show(ImportSchedule $importSchedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function edit(ImportSchedule $importSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ImportSchedule $importSchedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ImportSchedule  $importSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(ImportSchedule $importSchedule)
    {
        //
    }
}
