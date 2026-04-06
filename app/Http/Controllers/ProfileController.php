<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;

/**
 * @group Perfis
 *
 * Visualização e gerenciamento de perfis de usuário.
 */
class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profiles = Profile::all();

        return ProfileResource::collection($profiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProfileRequest $request)
    {
        $profile = Profile::where('user_id', $request->input('user_id'))->first();
        if (!$profile) {
            $profile = new Profile();
        }

        $profile->user_id = $request->input('user_id');
        $profile->gender = $request->input('gender');
        $profile->birthdate = $request->input('birthdate');
        $profile->scholarity = $request->input('scholarity');
        $profile->socials = $request->input('socials');
        $profile->configurations = $request->input('configurations');
        $profile->bio = $request->input('bio');
        $profile->race = $request->input('race');
        $profile->profession = $request->input('profession');
        $profile->address = $request->input('address');

        $profile->save();

        $profile->subjects()->sync($request->input('subjects'));

        return new ProfileResource($profile);
    }

    /**
     * Display the specified resource.
     */
    public function show(Profile $profile)
    {
        return new ProfileResource($profile);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProfileRequest $request, Profile $profile)
    {
        $profile->gender = $request->input('gender');
        $profile->birthdate = $request->input('birthdate');
        $profile->scholarity = $request->input('scholarity');
        $profile->socials = $request->input('socials');
        $profile->configurations = $request->input('configurations');
        $profile->bio = $request->input('bio');
        $profile->race = $request->input('race');
        $profile->profession = $request->input('profession');
        $profile->address = $request->input('address');

        $profile->save();

        $profile->subjects()->sync($request->input('subjects'));

        return new ProfileResource($profile);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Profile $profile)
    {
        $profile->delete();
        
        return new ProfileResource($profile);
    }

    public function getByUserId(string $userId)
    {
        // validar userId como uuid?
        $profile = Profile::where('user_id', $userId)->first();

        if (!$profile) {
            return response()->json(['message' => 'profile not found'], 404);
        }

        return new ProfileResource($profile);
    }
}
