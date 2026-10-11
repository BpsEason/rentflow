<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "RentFlow API",
    version: "1.0.0",
    description: "RentFlow 專業租賃管理平台 API 文件"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Schema(
    schema: "ApiSuccessResponse",
    type: "object",
    title: "API 成功回應",
    properties: [
        new OA\Property(property: "status", type: "string", example: "success"),
        new OA\Property(property: "message", type: "string", example: "操作成功"),
        new OA\Property(property: "data", type: "object"),
        new OA\Property(property: "meta", type: "object")
    ]
)]
#[OA\Schema(
    schema: "ApiErrorResponse",
    type: "object",
    title: "API 錯誤回應",
    properties: [
        new OA\Property(property: "status", type: "string", example: "error"),
        new OA\Property(property: "message", type: "string", example: "操作失敗"),
        new OA\Property(property: "data", type: "object", nullable: true),
        new OA\Property(property: "meta", type: "object")
    ]
)]
#[OA\Schema(
    schema: "ValidationErrorResponse",
    type: "object",
    title: "驗證錯誤回應",
    properties: [
        new OA\Property(property: "status", type: "string", example: "error"),
        new OA\Property(property: "message", type: "string", example: "驗證失敗"),
        new OA\Property(property: "data", type: "object", properties: [
            new OA\Property(property: "errors", type: "object")
        ]),
        new OA\Property(property: "meta", type: "object")
    ]
)]
#[OA\Schema(
    schema: "User",
    type: "object",
    title: "使用者資料",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "管理員"),
        new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
        new OA\Property(property: "phone", type: "string", example: "0912345678"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-01-01T00:00:00Z")
    ]
)]
#[OA\Schema(
    schema: "AuthTokenData",
    type: "object",
    title: "認證Token資料",
    properties: [
        new OA\Property(property: "access_token", type: "string"),
        new OA\Property(property: "token_type", type: "string", example: "Bearer"),
        new OA\Property(property: "expires_in", type: "integer", example: 3600),
        new OA\Property(property: "user", ref: "#/components/schemas/User")
    ]
)]
class OpenApiSpec
{
    // This class is only for holding OpenAPI global annotations
}
