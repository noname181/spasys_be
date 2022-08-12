<?php

namespace App\Http\Controllers\ImportSchedule;

use DateTime;
use App\Models\File;
use App\Models\ImportSchedule;
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
            $import_schedule = ImportSchedule::with('files')->paginate($per_page, ['*'], 'page', $page);

            return response()->json($import_schedule);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
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
