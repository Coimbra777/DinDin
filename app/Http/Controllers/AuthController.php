<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthenticationService $authenticationService,
    ) {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Credenciais inválidas. Verifique e-mail e senha.',
            ], 401);
        }

        $user = $this->authenticationService->validaUsuario($credentials['email']);

        return $this->respondWithToken($token, $user);
    }

    /**
     * Register User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $new = $this->authenticationService->novoUsuario($data);

        if ($new['status']) {
            return response()->json($new['msg'], 200);
        }else{
            return response()->json($new['msg'], 500);
        }

    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(request()->user('api')->cliente());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken(string $token, $profile): JsonResponse
    {
        return response()->json([
            'token' => $token,
            'profile' => $profile,
        ]);
    }
}
