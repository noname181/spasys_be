<?php

namespace App\Http\Controllers\ContractWms;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractWms\ContractWmsRequest;
use App\Models\ContractWms;
use App\Models\Company;
use App\Utils\Messages;
use App\Http\Requests\Item\ExcelRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Utils\CommonFunc;

class ContractwmsController extends Controller
{
    /**
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContractWmsByCono($request)
    {
        try {
            $Contractwms_tab1 = ContractWms::with(['mb_no'])->where('co_no','=',$request)->where('cw_tab','=','판매처')->get();
            $Contractwms_tab2 = ContractWms::with(['mb_no'])->where('co_no','=',$request)->where('co_no','=',$request)->where('cw_tab','=','공급처')->get();

                return response()->json(
                    ['message' => Messages::MSG_0007,
                    'data' => $Contractwms_tab1,
                    'data2' => $Contractwms_tab2,
                    ], 200);
           
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
        
    }
    public function CreateOrUpdateByCoPu(ContractWmsRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            //$ssi_no = $request->get('ssi_no');
                $user = Auth::user();
                if(isset($validated['co_no'])){
                    if(isset($validated['contract_wms_tab1'])){
                       
                        foreach ($validated['contract_wms_tab1'] as $ssi) {
                            $co_no = $request->get('co_no');
                            ContractWms::updateOrCreate(
                                [
                                    'cw_no' => $ssi['cw_no'] ?: null,
                                ],
                                [
                                    'co_no' => $co_no,
                                    'cw_code' => ($ssi['cw_code'] && $ssi['cw_code'] !='null') ? $ssi['cw_code']  : null,
                                    'cw_name' => ($ssi['cw_name'] && $ssi['cw_name'] !='null') ? $ssi['cw_name']  : null,
                                    'cw_tab' => '판매처',
                                    'mb_no' =>  $user->mb_no
                                ]
                            );
                        }
                    }
                    if(isset($validated['contract_wms_tab2'])){
                        foreach ($validated['contract_wms_tab2'] as $ss) {
                            $co_no = $request->get('co_no');
                            ContractWms::updateOrCreate(
                                [
                                    'cw_no' => ($ss['cw_no'] &&  $ss['cw_no'] != 'undefined') ?  $ss['cw_no'] : null,
                                ],
                                [
                                    'co_no' => $co_no,
                                    'cw_code' => ($ss['cw_code'] && $ss['cw_code'] !='null') ? $ss['cw_code']: null,
                                    'cw_name' => ($ss['cw_name'] && $ss['cw_name'] !='null') ? $ss['cw_name']: null,
                                    'cw_tab' => '공급처',
                                    'mb_no' =>  $user->mb_no
                                ]
                            );
                        }
                    }

                }
                    
                    
            
            DB::commit();
            return response()->json([
                //'message' => Messages::MSG_0007,
                //'co_no' => $co_no ? $co_no : ($ssi ? $ssi->co_no : null),
                //'$validated' => isset($validated['co_no']) ? $validated['co_no'] : ''
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }




}
