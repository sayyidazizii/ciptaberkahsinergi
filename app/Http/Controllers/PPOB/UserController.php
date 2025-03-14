<?php

namespace App\Http\Controllers\PPOB;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public static function profile() {
            $user = auth()->user()->only([
                "user_id",
                "member_id",
                "branch_id",
                "member_no",
                "member_name",
                "member_phone",
            ]);
            return response()->json($user, 200);

    }

    public function userManagement() {
        abort_unless(auth()->user()->isDeveloper(), 403);
        return view('userManagement');
    }
}
