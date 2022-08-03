<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\MemberRegisterController\InvokeRequest;
use App\Http\Requests\Member\MemberUpdate\MemberUpdateRequest;
use App\Http\Requests\Member\MemberSearchRequest;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Utils\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            if($roleNoOfUserLogin == Member::ROLE_ADMIN) {
                $validated['mb_type'] = Member::SPASYS;
                $validated['mb_parent'] = Member::ADMIN;
            }
            // FIXME hard set mb_language = ko and role_no = 1
            $validated['role_no'] = 1;
            $validated['mb_language'] = 'ko';

            $validated['mb_token'] = '';
            $validated['mb_pw'] = Hash::make($validated['mb_pw']);

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
                    'message' => Messages::MSG_0020
                ], 404);
            }
            return response()->json([
                'message' => Messages::MSG_0007,
                'mb_id' => $member->mb_id,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

    /**
     * Get Profiles
     */
    public function getProfile() {
        try {
            $member = Member::where('mb_no', Auth::user()->mb_no)->first();
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
    public function getMember($mb_no) {
        try {
            $member = Member::where('mb_no', $mb_no)->first();
            return response()->json([
                'message' => Messages::MSG_0007,
                'member' => $member,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0020], 500);
        }
    }

     /**
     * Get Members
     */
    public function getMembers(MemberSearchRequest $request) {
        try {
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $members = Member::with('company')->orderBy('mb_no', 'DESC');

            if (isset($validated['from_date'])) {
                $members->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $members->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            // if (isset($validated['co_name'])) {
            //     $members->where(function($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            //     });
            // }

            // if (isset($validated['co_service'])) {
            //     $members->where(function($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_service)'), 'like', '%' . strtolower($validated['co_service']) . '%');
            //     });
            // }
            
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
    public function updateProfile(MemberUpdateRequest $request) {
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

}
