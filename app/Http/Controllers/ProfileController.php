<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;

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
        $profile = new Profile();

        $profile->user_id = $request->input('user_id');
        $profile->gender = $request->input('gender');
        $profile->birthdate = $request->input('birthdate');
        $profile->phone = $request->input('phone');
        $profile->scholarity = $request->input('scholarity');
        $profile->website = $request->input('website');
        $profile->socials = $request->input('socials');
        $profile->configurations = $request->input('configurations');
        $profile->country = $request->input('country');
        $profile->state = $request->input('state');
        $profile->city = $request->input('city');

        $profile->save();

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
        $profile->phone = $request->input('phone');
        $profile->scholarity = $request->input('scholarity');
        $profile->website = $request->input('website');
        $profile->socials = $request->input('socials');
        $profile->configurations = $request->input('configurations');
        $profile->country = $request->input('country');
        $profile->state = $request->input('state');
        $profile->city = $request->input('city');

        $profile->save();

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
        // validar userId como uuid
        $profile = Profile::where('user_id', $userId)->first();

        return new ProfileResource($profile);
    }
}
