<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Profile;
use App\Models\VRACore\VRACContributorName;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->save();

        // creates Profile
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();

        // creates Contributor
        $contributor = new VRACContributorName();
        $contributor->name = $request->input('name');
        $contributor->type = 'personal';
        $contributor->vocab = 'ARQUIGRAFIA';
        $contributor->ref_id = $user->id;
        $contributor->save();

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->name = $request->input('name');
        if ($user->email != $request->input('email')) $user->email = $request->input('email');
        if ($request->filled('password')) $user->password = Hash::make($request->input('password'));

        if ($request->hasFile('image')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('image')->store('profileImage', 'public');
        }

        $user->save();

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteUserRequest $request, User $user)
    {
        optional($user->profile())->delete();
        $user->delete();

        return response()->json([
            'user' => $user,
        ]);
    }
}
