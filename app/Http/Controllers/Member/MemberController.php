<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MemberRegisterController\CreateAccountRequest;
use App\Http\Requests\Member\MemberRegisterController\InvokeRequest;
use App\Http\Requests\Member\MemberSearchRequest;
use App\Http\Requests\Member\MemberSpasysSearchRequest;
use App\Http\Requests\Member\MemberSpasysUpdateRequest;
use App\Http\Requests\Member\MemberUpdate\MemberUpdateByIdRequest;
use App\Http\Requests\Member\MemberUpdate\MemberUpdateRequest;
use App\Models\Company;
use App\Models\Member;
use App\Models\Service;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    /**
     * Register member
     * @param  \App\Http\Requests\Member\MemberRegisterController\InvokeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(InvokeRequest $request)
    {
        try {
            $validated = $request->validated();
            $roleNoOfUserLogin = Auth::user()->role_no;

            // FIXME hard set mb_language = ko and role_no = 1

            $validated['mb_language'] = 'ko';

            if ($roleNoOfUserLogin == Member::ROLE_ADMIN || ($roleNoOfUserLogin == Member::ROLE_SPASYS_ADMIN && empty($validated['co_no']))) {
                $validated['mb_type'] = Member::SPASYS;
                $validated['mb_parent'] = Member::ADMIN;
            } else if ($roleNoOfUserLogin == Member::ROLE_SPASYS_ADMIN) {
                $validated['mb_type'] = Member::SHOP;
                $validated['mb_parent'] = Member::SPASYS;

            } else if ($roleNoOfUserLogin == Member::ROLE_SHOP_MANAGER) {
                $validated['mb_type'] = Member::SHIPPER;
                $validated['mb_parent'] = Member::SHOP;
            }

            $validated['mb_token'] = '';
            $validated['mb_pw'] = Hash::make($validated['mb_pw']);

            if(empty($validated['co_no'])){
                $validated['co_no'] = Auth::user()->co_no;
            }

            $mb_no = Member::insertGetId($validated);

            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_no' => $mb_no,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Find user_id by name and email
     * @param  Illuminate\Http\Request $request;
     * @return \Illuminate\Http\JsonResponse
     */
    public function findUserId(Request $request)
    {
        try {
            $member = Member::where('mb_name', $request['mb_name'])
                ->where('mb_email', $request['mb_email'])->first();
            if (is_null($member)) {
                return response()->json([
                    'message' => Messages::MSG_0020,
                ], 404);
            }
            $length = strlen($member->mb_id);
            $start = floor($length / 4);
            $end = floor($length / 2) + $start;
            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_id' => substr_replace($member->mb_id, str_repeat('*', $end - $start + 1), $start, $end - $start + 1),
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Get Profiles
     */
    public function getProfile()
    {
        try {
            $member = Member::with(['company', 'role'])->where('mb_no', Auth::user()->mb_no)->first();

            return response()->json([
                'message' => Messages::MSG_0007,
                'profile' => $member,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Get Member
     */
    public function getMember($mb_no)
    {
        try {
            $member = Member::with('company')->where('mb_no', $mb_no)->first();

            if ($member->company->co_type == 'spasys') {
                $services = Service::where('service_use_yn', 'y')->where('service_no', '!=', 1)->get();
            } else {
                $co_service_array = explode(" ", $member->company->co_service);
                $services = Service::whereIN("service_name", $co_service_array)->get();
            }

            // $services = Service::where('service_use_yn', 'y')->get();

            return response()->json([
                'message' => Messages::MSG_0007,
                'member' => $member,
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Get Members
     */
    public function getMembers(MemberSearchRequest $request)
    {
        try {
            $validated = $request->validated();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $user = Member::where('mb_no', Auth::user()->mb_no)->with('company')->first();

            if ($user->mb_type == 'spasys') {
                $members = Member::with('company')->whereHas('company', function($q) use($user){
                    $q->where('co_no', $user->company->co_no);
                })
                ->orWhereHas('company', function($q) use($user){
                    $q->whereHas('co_parent', function($q) use($user){
                        $q->where('co_no', $user->company->co_no);
                    });
                })
                ->orWhereHas('company', function($q) use($user){
                    $q->whereHas('co_parent', function($q) use($user){
                        $q->whereHas('co_parent', function($q) use($user){
                            $q->where('co_no', $user->company->co_no);
                        });
                    });
                })
                ->orderBy('mb_no', 'DESC');
            }else if($user->mb_type == 'shop'){
                $members = Member::with('company')->whereHas('company', function($q) use($user){
                    $q->where('co_no', $user->company->co_no);
                })
                ->orWhereHas('company', function($q) use($user){
                    $q->whereHas('co_parent', function($q) use($user){
                        $q->where('co_no', $user->company->co_no);
                    });
                });
            }else if($user->mb_type == 'shipper'){
                $members = Member::with('company')->whereHas('company', function($q) use($user){
                    $q->where('co_no', $user->company->co_no);
                });
            }



            if (isset($validated['from_date'])) {
                $members->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $members->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_name'])) {
                $members->whereHas('company', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }

            if (isset($validated['mb_name'])) {
                $members->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_name)'), 'like', '%' . strtolower($validated['mb_name']) . '%');
                });
            }

            if (isset($validated['mb_id'])) {
                $members->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_id)'), 'like', '%' . strtolower($validated['mb_id']) . '%');
                });
            }

            $members = $members->paginate($per_page, ['*'], 'page', $page);

            return response()->json($members);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Update Profiles
     */
    public function updateProfile(MemberUpdateRequest $request)
    {
        try {
            $validated = $request->validated();

            $member = Member::where('mb_no', Auth::user()->mb_no)->first();
            $member['mb_email'] = $validated['mb_email'];
            $member['mb_tel'] = $validated['mb_tel'];
            $member['mb_hp'] = $validated['mb_hp'];
            $member['mb_push_yn'] = $validated['mb_push_yn'];
            $member['mb_service_no_array'] = $validated['mb_service_no_array'];
            $member->save();

            return response()->json([
                'message' => Messages::MSG_0007,
                'profile' => $member,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Update Profiles By Id
     */
    public function updateProfileById(MemberUpdateByIdRequest $request)
    {
        $validated = $request->validated();
        $member = Member::where('mb_no', $validated['mb_no'])->first();
        $services = Service::where('service_use_yn', 'y')->get();
        $member['mb_email'] = $validated['mb_email'];
        $member['mb_name'] = $validated['mb_name'];
        $member['mb_tel'] = $validated['mb_tel'];
        $member['mb_hp'] = $validated['mb_hp'];
        $member['mb_push_yn'] = $validated['mb_push_yn'];
        $member['mb_use_yn'] = $validated['mb_use_yn'];
        $member['mb_service_no_array'] = $validated['mb_service_no_array'];
        $member['co_no'] = $validated['co_no'];
        $member['role_no'] = $validated['role_no'];

        $member->save();

        return response()->json([
            'message' => Messages::MSG_0007,
            'profile' => $member,
            'services' => $services,
        ]);
    }

    /**
     * Create Account
     */
    public function createAccount(CreateAccountRequest $request)
    {
        try {
            $validated = $request->validated();

            $roleNoOfUserLogin = Auth::user()->role_no;
            if ($roleNoOfUserLogin == Member::ROLE_ADMIN) {
                $validated['mb_type'] = Member::SPASYS;
                $validated['mb_parent'] = Member::ADMIN;
            }
            // FIXME hard set mb_language = ko and role_no = 1
            $validated['role_no'] = 2;
            $validated['mb_language'] = 'ko';

            $validated['mb_token'] = '';
            $validated['mb_pw'] = Hash::make($validated['mb_pw']);
            $com_no = Company::insertGetId([
                'mb_no' => Auth::user()->mb_no,
                'co_type' => Member::SPASYS,
                'co_name' => $validated['mb_name'],
                'co_etc' => $validated['mb_note'],
                'co_operating_time' => $validated['co_operating_time'],
                'co_lunch_break' => $validated['co_lunch_break'],
                'co_email' => $validated['co_email'],
                'co_about_us' => $validated['co_about_us'],
                 'co_policy' => isset($validated['co_policy']) ? $validated['co_policy'] : '',
                'co_help_center' => $validated['co_help_center'],
            ]);

            $validated['co_no'] = $com_no;

            $mb_no = Member::insertGetId([
                'co_no' =>  $validated['co_no'],
                'mb_name' => $validated['mb_name'],
                'mb_pw' => $validated['mb_pw'],
                'mb_tel' => $validated['mb_tel'],
                'mb_id' => $validated['mb_id'],
                'mb_note' => $validated['mb_note'],
                'mb_type' => $validated['mb_type'],
                'mb_parent' => $validated['mb_parent'],
                'role_no' => $validated['role_no'],
                'mb_language' => $validated['mb_language'],
                'mb_token' => $validated['mb_token'],
            ]);


            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_no' => $mb_no,
                'co_no' => $com_no,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0001], 500);
        }
    }

    /**
     * Update Account
     */
    public function updateAccount(MemberSpasysUpdateRequest $request, Member $memeber)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            if (isset($validated['mb_pw'])) {
                // Logout when change pw
                $memeber->mb_token = '';
                $memeber->mb_pw = Hash::make($validated['mb_pw']);
            }
            if ($validated['mb_id'] != $memeber->mb_id) {
                // Logout when change mb_id
                $memeber->mb_token = '';
            }
            $memeber->mb_name = $validated['mb_name'];
            $memeber->mb_id = $validated['mb_id'];
            $memeber->mb_note = $validated['mb_note'];
            $memeber->mb_tel = $validated['mb_tel'];
            $memeber->mb_id = $validated['mb_id'];
            $memeber->save();

            Company::where('co_no', $memeber->co_no)->update([
                'co_operating_time' => $validated['mb_name'],
                'co_lunch_break' => $validated['co_lunch_break'],
                'co_email' => $validated['co_email'],
                'co_about_us' => $validated['co_about_us'],
                'co_help_center' => $validated['co_help_center'],
                'co_policy' => $validated['co_policy'],
            ]);

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'memeber' => $memeber,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0002], 500);
        }
    }

    /**
     * Delete Account
     */
    public function deleteAccount($mb_no)
    {
        try {
            $member = Member::where('mb_no', $mb_no)->first();
            $co_no = $member->co_no;
            $member->delete();
            Company::where('co_no', $co_no)->delete();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0003], 500);
        }
    }

    /**
     * Delete Account
     */
    public function deleteMember($mb_no)
    {
        try {
            $member = Member::where('mb_no', $mb_no)->first();
            $co_no = $member->co_no;
            $member->delete();

            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0003], 500);
        }
    }

    /**
     * Get Spasys Members
     */
    public function getSpasys(MemberSpasysSearchRequest $request)
    {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $spasys = Member::with('company')->where('role_no', 2)->orderBy('mb_no', 'DESC');

            if (isset($validated['mb_name'])) {
                $spasys->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_name)'), 'like', '%' . strtolower($validated['mb_name']) . '%');
                });
            }

            if (isset($validated['mb_id'])) {
                $spasys->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(mb_id)'), 'like', '%' . strtolower($validated['mb_id']) . '%');
                });
            }

            $spasys = $spasys->paginate($per_page, ['*'], 'page', $page);

            return response()->json($spasys);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Get members not paginate
     */

    public function list_members()
    {
        try {
            $user = Auth::user();
            if($user->mb_type == 'shop'){
                $members = Member::with(['company'])
                ->whereHas('company.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })
                // ->orWhereHas('company',function ($q) use ($user){
                //     $q->where('co_no', $user->company->co_parent->co_no);
                // })
                ->get();
            }else if($user->mb_type == 'spasys'){
                $members = Member::with(['company'])
                ->whereHas('company.co_parent.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })
                ->orWhereHas('company.co_parent',function ($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })
                ->get();
            }else if($user->mb_type == 'shipper'){
                $members = Member::where('co_no', $user->company->co_parent->co_no)
                ->orwhere('co_no', $user->company->co_parent->co_parent->co_no)
                ->get();
            }

            return response()->json(["member" => $members]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }

    }

}
