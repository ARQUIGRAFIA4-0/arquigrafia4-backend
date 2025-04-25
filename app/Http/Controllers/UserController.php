<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\TokenRepository;

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
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $loggedUser = Auth::user();

        return response()->json([
            'loggedUser' => $loggedUser,
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    public function me()
    {
        $loggedUser = Auth::user();

        return response()->json([
            'user' => $loggedUser,
        ]);
    }

    public function logout()
    {
        $loggedUser = Auth::user();
        $tokenRepository = app(TokenRepository::class);
        $refreshTokenRepository = app(RefreshTokenRepository::class);
        
        $currentToken = $tokenRepository->forUser($loggedUser->id)->first();
        // Revoke an access token...
        $tokenRepository->revokeAccessToken($currentToken->id);
        // Revoke all of the token's refresh tokens...
        $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($currentToken->id);
    }
}
