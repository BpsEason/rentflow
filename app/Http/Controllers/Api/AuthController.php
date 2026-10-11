<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use OpenApi\Attributes as OA;

/**
 * 認證相關API控制器
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

    #[OA\Post(
        path: "/api/v1/auth/login",
        summary: "使用者登入",
        description: "使用email和password取得JWT認證token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "登入成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "認證失敗", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 422, description: "驗證錯誤", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse"))
        ]
    )]
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

    #[OA\Get(
        path: "/api/v1/auth/me",
        summary: "取得當前使用者資訊",
        description: "取得目前登入使用者的詳細資訊",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "成功取得使用者資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function me()
    {
        return ApiResponse::success(
            new UserResource(Auth::user()),
            '取得使用者資訊成功'
        );
    }

    #[OA\Post(
        path: "/api/v1/auth/logout",
        summary: "使用者登出",
        description: "使當前JWT token失效",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "登出成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 500, description: "伺服器錯誤", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function logout()
    {
        try {
            auth('api')->logout();
            return ApiResponse::success(null, '登出成功');
        } catch (JWTException $e) {
            return ApiResponse::error('登出失敗', null, [], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/auth/refresh",
        summary: "刷新JWT token",
        description: "取得新的存取token",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "刷新成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
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
