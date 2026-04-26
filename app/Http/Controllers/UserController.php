<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Profile;
use App\Models\VRACore\VRACContributorName;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Jcupitt\Vips\Image;

/**
 * @group Usuários
 *
 * Cadastro, visualização e gerenciamento de usuários.
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * @unauthenticated
     */
    public function index()
    {
        return UserResource::collection(User::paginate());
    }

    /**
     * Store a newly created resource in storage.
     * @unauthenticated
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

        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     * @unauthenticated
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->name = $request->input('name');
        if ($user->email != $request->input('email')) $user->email = $request->input('email');
        if ($request->filled('password')) $user->password = Hash::make($request->input('password'));

        if ($request->hasFile('avatar')) {

            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $filename = 'avatars/users/' . $user->id . '.webp';
            $destPath  = Storage::disk('public')->path($filename);
            $uploadedFile = $request->file('avatar');

            $image = Image::newFromBuffer(
                $uploadedFile->getContent(),
                '',
                ['access' => 'sequential']
            );

            $width  = $image->width;
            $height = $image->height;

            $minSide = min($width, $height);

            $image = $image->crop(
                (int) (($width  - $minSide) / 2),
                (int) (($height - $minSide) / 2),
                $minSide,
                $minSide
            );

            $image = $image->thumbnail_image(400, [
                'height' => 400,
                'size' => 'force',
            ]);

            $image->writeToFile($destPath, [
                'Q'             => 82,
                'strip'         => true,
            ]);

            $user->avatar_path = $filename;
        }

        $user->save();

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteUserRequest $request, User $user)
    {
        optional($user->profile())->delete();
        $user->delete();

        return new UserResource($user);
    }
}
