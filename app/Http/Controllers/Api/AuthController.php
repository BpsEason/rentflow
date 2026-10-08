<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\Info(
 *     title="RentFlow API",
 *     version="1.0.0",
 *     description="RentFlow 專業租賃管理平台 API 文件"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Schema(
 *     schema="ApiSuccessResponse",
 *     type="object",
 *     title="API 成功回應",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="操作成功"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="meta", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     type="object",
 *     title="API 錯誤回應",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="操作失敗"),
 *     @OA\Property(property="data", type="object", nullable=true),
 *     @OA\Property(property="meta", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     title="驗證錯誤回應",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="驗證失敗"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="errors", type="object")
 *     ),
 *     @OA\Property(property="meta", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="AuthTokenData",
 *     type="object",
 *     title="認證Token資料",
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=3600),
 *     @OA\Property(property="user", ref="#/components/schemas/User")
 * )
 */
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="使用者登入",
     *     description="使用email和password取得JWT認證token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="登入成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認證失敗",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="驗證錯誤",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return ApiResponse::error('無效的認證憑據', null, [], 401);
            }
        } catch (JWTException $e) {
            return ApiResponse::error('無法建立token', null, [], 500);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="取得當前使用者資訊",
     *     description="取得目前登入使用者的詳細資訊",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得使用者資訊",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function me()
    {
        return ApiResponse::success(
            new UserResource(Auth::user()),
            '取得使用者資訊成功'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="使用者登出",
     *     description="使當前JWT token失效",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="登出成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="伺服器錯誤",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function logout()
    {
        try {
            auth('api')->logout();
            return ApiResponse::success(null, '登出成功');
        } catch (JWTException $e) {
            return ApiResponse::error('登出失敗', null, [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="刷新JWT token",
     *     description="取得新的存取token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="刷新成功",
     *         @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return $this->respondWithToken($token);
        } catch (JWTException $e) {
            return ApiResponse::error('無法刷新token', null, [], 401);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $data = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => new UserResource(Auth::user())
        ];

        return ApiResponse::success($data, '登入成功');
    }
}
