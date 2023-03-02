<?php

namespace App\Http\Controllers\Package;

use App\Http\Requests\Service\ServiceRequest;
use App\Http\Controllers\Controller;
use App\Models\Package;

use App\Utils\Messages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Manager;
use App\Models\Company;
use App\Models\Member;
use App\Models\Export;
class PackageController extends Controller
{
    
    public function get_package_data(Request $request)
    {
        try {
            $member = Member::with('company')->where('mb_no', Auth::user()->mb_no)->first();
            $package = Package::where('w_no', $request->w_no)->first();
            $package_get = Package::where('w_no', $request->w_no)->get();
            $manager = Manager::select([
                'm_no',
                'co_no',
                'm_position',
                'm_name',
                'm_duty1',
                'm_duty2',
                'm_hp',
                'm_email',
                'm_etc',
            ])->where('co_no', $request->co_no)->get();
            
            $company = Company::select([
                'company.co_no',
                'company.mb_no',
                'company.co_name',
                'company.co_address',
                'company.co_zipcode',
                'company.co_address_detail',
                'company.co_country',
                'company.co_service',
                'company.co_major',
                'company.co_license',
                'company.co_close_yn',
                'company.co_owner',
                'company.co_homepage',
                'company.co_email',
                'company.co_etc',
                'company.co_tel',
                // 'co_address.ca_address as co_address',
                // 'co_address.ca_address_detail as co_address_detail',
                // ])->join('co_address', 'co_address.co_no', 'company.co_no')
            ])->where('company.co_no', $request->co_no)
                // ->where('co_address.co_no', $co_no)
                ->first();

            //for page 715
            if(isset($request->w_no)){
                $export = Export::with(['import', 'import_expected', 't_export_confirm'])->where('te_carry_out_number', $request->w_no)->first();
            }

        
            
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'manager' => $manager,
                'company' => $company,
                'package' => $package,
                'package_get' => $package_get,
                'member' => $member,
                'export' => isset($export) ? $export : '',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

  
}
