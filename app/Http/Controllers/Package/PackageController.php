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

class PackageController extends Controller
{
    
    public function get_package_data(Request $request)
    {
        try {
          
            $package = Package::where('w_no', $request->w_no)->first();

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
               
                'package' => $package
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

  
}
