<?php

namespace App\Http\Controllers;

use App\Models\LMDUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class LmdUserController extends Controller
{
    public function index()
    {
        return response()->json(LmdUser::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:lmd_users',
            'phone_no' => 'required|string|max:15|unique:lmd_users',
            'password' => 'required|string|min:6',
            'lmd_user_role' => 'required|string|max:50',
            'addresses_ID' => 'required|integer',
        ]);

        $user = LmdUser::create($validated);
        return response()->json($user, 201);
    }

    public function show($id)
    {
        return LmdUser::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|string|email|max:255|unique:lmd_users,email,' . $id,
            'phone_no' => 'sometimes|string|max:15|unique:lmd_users,phone_no,' . $id,
            'password' => 'sometimes|string|min:6',
            'lmd_user_role' => 'sometimes|string|max:50',
            'addresses_ID' => 'sometimes|integer',
        ]);
    
        //update
        $result = DB::table('lmd_users')->where('id', $id)->update($validatedData);
        //return record
        $updatedRecord = DB::table('lmd_users')->where('id', $id)->first();

        if ($result) {
            return response()->json($updatedRecord, 200);
        }
    
        return response()->json(['error' => 'Update failed'], 400);
    }

    public function destroy($id)
{
    $lmdUser = LMDUser::find($id);
    if (!$lmdUser) {
        return response()->json(['message' => 'Record not found'], 404);
    }

    $deleted = $lmdUser->delete();

    if ($deleted) {
        return response()->json(['message' => 'Record deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'Failed to delete record'], 500);
    }
}



}
