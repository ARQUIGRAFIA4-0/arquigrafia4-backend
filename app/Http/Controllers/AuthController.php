<?php

namespace App\Http\Controllers;

use App\Models\AccountVerificationToken;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Notifications\AccountVerificationRequested;
use App\Notifications\PasswordResetRequested;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\TokenRepository;

class AuthController extends Controller
{
    public function sendVerificationEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
    
        $token = AccountVerificationToken::where('email', $request->input('email'))->first();

        if (!$token) {
            $token = new AccountVerificationToken([
                'email' => $request->input('email'),
                'token' => 0,
            ]);
        }

        $token->token = rand(100000, 999999);
        $token->save();

        $user = User::where('email', $request->input('email'))->first();

        $user->notify(new AccountVerificationRequested($token));
    
        return response()->json([
            'message' => "OK",
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ]);

        $token = AccountVerificationToken::where('email', $request->input('email'))->first();
        if (!$token) {
            return response()->json([
                'message' => 'Sem código de verificação',
            ], 400);
        }

        if ($token->token != $request->input('code')) {
            return response()->json([
                'message' => 'Código de verificação inválido',
            ], 400);
        }

        $user = User::where('email', $request->input('email'))->first();
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'message' => "OK",
        ]);
    }

    public function passwordResetEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
    
        $token = PasswordResetToken::where('email', $request->input('email'))->first();

        if (!$token) {
            $token = new PasswordResetToken([
                'email' => $request->input('email'),
                'token' => 0,
                'created_at' => now(),
            ]);
        }

        $token->token = rand(100000, 999999);
        $token->created_at = now();
        $token->save();

        $user = User::where('email', $request->input('email'))->first();

        $user->notify(new PasswordResetRequested($token));
    
        return response()->json([
            'message' => "OK",
        ]);
    }

    public function verifyPasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => ['nullable', 'max:250', Password::min(8)],
            'code' => 'required|string|size:6',
        ]);

        $token = PasswordResetToken::where('email', $request->input('email'))->first();
        if (!$token) {
            return response()->json([
                'message' => 'Sem código de recuperação',
            ], 400);
        }
        
        if ($token->created_at->diffInMinutes(now()) > 15) {
            return response()->json([
                'message' => 'Código de recuperação expirado',
            ], 400);
        }

        if ($token->token != $request->input('code')) {
            return response()->json([
                'message' => 'Código de recuperação inválido',
            ], 400);
        }

        $user = User::where('email', $request->input('email'))->first();
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return response()->json([
            'message' => "OK",
        ]);
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
        // Revoke an access token
        $tokenRepository->revokeAccessToken($currentToken->id);
        // Revoke all of the token's refresh tokens
        $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($currentToken->id);

        return response()->json([
            'message' => "OK",
        ]);
    }
}
