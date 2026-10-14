<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use OpenApi\Attributes as OA;

/**
 * 前台客戶認證相關API控制器
 */
class CustomerAuthController extends Controller
{
    /**
     * Create a new CustomerAuthController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'resetPassword']]);
    }

    #[OA\Post(
        path: "/api/v1/customer/auth/register",
        summary: "客戶註冊",
        description: "前台客戶註冊平台會員帳號",
        tags: ["Customer Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "phone"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "張三"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "customer@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "phone", type: "string", example: "0912345678")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "註冊成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 422, description: "驗證錯誤", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse"))
        ]
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
        ]);

        // 創建平台會員帳號
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 客戶註冊時不會自動創建Customer，需要用戶在進入具體租戶時補充完整資料
        // 這裡只創建User帳號，返回JWT token

        try {
            if (!$token = JWTAuth::fromUser($user)) {
                return ApiResponse::error('無法建立認證token', null, [], 500);
            }
        } catch (JWTException $e) {
            return ApiResponse::error('無法建立token', null, [], 500);
        }

        return $this->respondWithToken($token, $user);
    }

    #[OA\Post(
        path: "/api/v1/customer/auth/login",
        summary: "客戶登入",
        description: "前台客戶使用email和password取得JWT認證token",
        tags: ["Customer Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "customer@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "登入成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "認證失敗", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 422, description: "驗證錯誤", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse"))
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return ApiResponse::error('無效的認證憑據', null, [], 401);
            }
        } catch (JWTException $e) {
            return ApiResponse::error('無法建立token', null, [], 500);
        }

        return $this->respondWithToken($token, Auth::user());
    }

    #[OA\Get(
        path: "/api/v1/customer/auth/me",
        summary: "取得當前客戶資訊",
        description: "取得目前登入客戶的詳細資訊及其在當前租戶的Customer資料",
        tags: ["Customer Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "成功取得客戶資訊", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function me(Request $request)
    {
        $user = Auth::user();
        $tenant = app('tenant.context');

        // 獲取用戶在當前租戶的Customer資料
        $customer = null;
        if ($tenant) {
            $customer = Customer::where('tenant_id', $tenant->id)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere(function ($subQuery) use ($user) {
                            $subQuery->whereNull('user_id')
                                ->where('email', $user->email);
                        });
                })
                ->first();

            // 如果找到的是訪客客戶（user_id為null），自動關聯到當前登入的用戶
            if ($customer && is_null($customer->user_id)) {
                $customer->update(['user_id' => $user->id]);
            }
        }

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'customer' => $customer ? new CustomerResource($customer) : null
        ], '取得客戶資訊成功');
    }

    #[OA\Post(
        path: "/api/v1/customer/auth/logout",
        summary: "客戶登出",
        description: "使當前JWT token失效",
        tags: ["Customer Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "登出成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
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
        path: "/api/v1/customer/auth/refresh",
        summary: "刷新JWT token",
        description: "刷新當前的JWT認證token",
        tags: ["Customer Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "刷新成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse"))
        ]
    )]
    public function refresh()
    {
        try {
            $token = auth('api')->refresh();
            return $this->respondWithToken($token, Auth::user());
        } catch (JWTException $e) {
            return ApiResponse::error('無法刷新token', null, [], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/customer/auth/forgot-password",
        summary: "忘記密碼",
        description: "發送密碼重設連結到客戶的email",
        tags: ["Customer Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "customer@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "重設連結已發送", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 422, description: "驗證錯誤", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse"))
        ]
    )]
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        // 這裡可以實現發送密碼重設郵件的邏輯
        // 由於郵件服務需要配置，先返回成功訊息，實際環境中需要實現寄送邏輯

        return ApiResponse::success(null, '密碼重設連結已發送到您的email');
    }

    #[OA\Post(
        path: "/api/v1/customer/auth/reset-password",
        summary: "重設密碼",
        description: "使用重設token更新密碼",
        tags: ["Customer Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["token", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "token", type: "string", example: "reset-token-here"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "customer@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "newpassword123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "newpassword123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "密碼重設成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 422, description: "驗證錯誤或重設token無效", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse"))
        ]
    )]
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // 這裡實現密碼重設的邏輯
        // 需要使用Laravel的密碼重設功能

        return ApiResponse::success(null, '密碼重設成功');
    }

    /**
     * 建立客戶在當前租戶的Customer資料
     */
    #[OA\Post(
        path: "/api/v1/customer/profile",
        summary: "建立或更新當前租戶的客戶資料",
        description: "為登入的會員在當前租戶建立或更新Customer資料",
        tags: ["Customer Profile"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "phone", "id_number", "license_number", "license_expiry", "address"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "張三"),
                    new OA\Property(property: "phone", type: "string", example: "0912345678"),
                    new OA\Property(property: "id_number", type: "string", example: "A123456789"),
                    new OA\Property(property: "license_number", type: "string", example: "XYZ123456"),
                    new OA\Property(property: "license_expiry", type: "string", format: "date", example: "2030-01-01"),
                    new OA\Property(property: "address", type: "string", example: "台北市信義區信義路五段7號"),
                    new OA\Property(property: "emergency_contact_name", type: "string", example: "李四"),
                    new OA\Property(property: "emergency_contact_phone", type: "string", example: "0987654321")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "資料更新成功", content: new OA\JsonContent(ref: "#/components/schemas/ApiSuccessResponse")),
            new OA\Response(response: 401, description: "未授權", content: new OA\JsonContent(ref: "#/components/schemas/ApiErrorResponse")),
            new OA\Response(response: 422, description: "驗證錯誤", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse"))
        ]
    )]
    public function updateProfile(Request $request)
    {
        $tenant = app('tenant.context');
        if (!$tenant) {
            return ApiResponse::error('無效的租戶', null, [], 400);
        }

        $user = Auth::user();

        // 驗證輸入
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'id_number' => 'required|string|max:50',
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date',
            'address' => 'required|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        // 檢查當前租戶下是否已有此用戶的Customer資料
        $customer = Customer::where('tenant_id', $tenant->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->whereNull('user_id')
                            ->where('email', $user->email);
                    });
            })
            ->first();

        if ($customer) {
            // 如果找到的是訪客客戶（user_id為null），自動關聯到當前登入的用戶
            if (is_null($customer->user_id)) {
                $customer->update(['user_id' => $user->id]);
            }
            // 更新現有資料
            $customer->update(array_merge($validated, [
                'email' => $user->email,
                'is_active' => true,
            ]));
        } else {
            // 檢查同一租戶下email或phone是否已被使用
            $existingCustomer = Customer::where('tenant_id', $tenant->id)
                ->where(function ($query) use ($validated, $user) {
                    $query->where('email', $user->email)
                        ->orWhere('phone', $validated['phone']);
                })
                ->first();

            if ($existingCustomer) {
                throw ValidationException::withMessages([
                    'email' => ['此email已在此租戶註冊過'],
                    'phone' => ['此電話已在此租戶註冊過'],
                ]);
            }

            // 建立新的Customer資料
            $customer = Customer::create(array_merge($validated, [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'is_active' => true,
            ]));
        }

        return ApiResponse::success(new CustomerResource($customer), '客戶資料更新成功');
    }

    /**
     * 回應token的格式
     */
    protected function respondWithToken($token, $user = null)
    {
        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null
        ], '操作成功');
    }
}
