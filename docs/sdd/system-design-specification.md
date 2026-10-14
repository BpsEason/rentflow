# RentFlow System Design Specification

## Document Purpose
本文檔描述RentFlow租車管理系統的完整系統設計，包含前端、後端、資料庫、API、租戶隔離、預約與定價等核心設計，並明確區分已實作、未驗證、規劃中以及待決策的功能。

## Product Overview
RentFlow是一個多租戶的車輛租賃管理系統，提供：
- 面向客戶的Vue.js前端網站，用於瀏覽車輛、預約租車
- 基於Filament的商家管理後台，用於管理車輛、預約、客戶等
- 完善的租戶隔離機制，每個租戶擁有獨立的資料邊界
- 自動化的定價計算與庫存管理
- 會員與訪客預約流程支援

## System Goals and Non-Goals

### Goals
- 正確性：確保預約衝突避免、定價計算正確、租戶資料隔離
- 清晰性：程式碼結構清晰，易於理解與維護
- 可維護性：模組化設計，支持功能擴展
- 小型修改：保持程式碼變更幅度小，降低引入bug的風險
- 低耦合：組件間依賴最小化

### Non-Goals
- 不追求微服務架構，目前採用Modular Monolith設計
- 不實作複雜的支付閘道整合（僅建立基礎訂單模型）
- 不支援多語系國際化（目前僅中文）
- 不實作複雜的報表與數據分析功能

## System Architecture

### Current Implementation Architecture (v1.0)

```
Customer Browser
    ↓
Vue 3 Customer Web (Single Page Application)
    ↓ HTTP / JSON
Laravel REST API
    ↓
Laravel Application Layer (Use Cases)
    ↓
Laravel Domain Models & Business Rules
    ↓
MySQL (Relational Database)
```

```
Merchant Administrator
    ↓
Filament 5.0 Admin Panel
    ↓
Laravel Authorization & Business Rules
    ↓
MySQL
```

### System Components
- **Customer Web**: 面向租車客戶的Vue 3前端
- **Laravel REST API**: 提供車輛、據點、價格、會員及預約等API
- **Merchant Admin**: Filament商家管理後台
- **Platform Admin**: 共享管理平台，目前與Merchant Admin共用Filament面板
- **MySQL**: 主要關聯式資料庫，存儲所有商業資料
- **Redis**: 未實作，目前僅使用Laravel預設的文件快取與數據庫隊列

### Domain Layer Implementation Status
| Component | Status | Notes |
|-----------|--------|-------|
| `app/Domain/Rental/` | ✅ 已實作 | 包含租賃領域的模型、列舉 |
| `PricingService` | ✅ 已實作 | `app/Services/PricingService.php` |
| `ReservationService` | ⚠️ 部分實作 | 使用Use Cases模式分散在Application層 |
| `AvailabilityService` | ❌ 未實作 | 可用性檢查邏輯內建於Reservation模型 |
| State Machine | ❌ 未實作 | 狀態轉換邏輯以方法形式實作在模型中 |
| Domain Exceptions | ❌ 未實作 | 使用原生PHP Exception |
| Domain Events | ✅ 已實作 | ReservationCreated, ReservationConfirmed等 |
| Repository Pattern | ✅ 已實作 | ReservationRepositoryInterface與實作 |
| DTO | ✅ 已實作 | CreateReservationData等DTO在Application層 |

## Technology Stack

### Backend Technologies
- **PHP**: 8.2+
- **Laravel Framework**: 12.0
- **Filament Admin Panel**: 5.0
- **Filament Shield**: 4.3（權限管理）
- **tymon/jwt-auth**: 2.3（JWT認證）
- **MySQL**: 數據庫
- **L5-swagger**: 11.1（API文件生成）

### Frontend Technologies
- **Vue.js**: 3.5.43
- **Vue Router**: 4.6.4（前端路由）
- **Pinia**: 3.0.4（狀態管理）
- **Axios**: 1.7.4（HTTP客戶端）
- **TypeScript**: 5.9.3
- **Vite**: 6.0.11（建置工具）
- **TailwindCSS**: 4.0.0（UI框架）
- **Laravel Vite Plugin**: 1.2.0

## Architecture Principles

1. **租戶隔離優先**：所有資料操作都必須在租戶上下文內進行
2. **後端驗證為唯一信任來源**：前端驗證僅為使用者體驗優化
3. **資料庫交易保證一致性**：關鍵操作（如建立預約）必須在交易中執行
4. **樂觀鎖定避免併發問題**：使用`lockForUpdate()`進行行級鎖定
5. **明確的分層架構**：Presentation -> Application -> Domain -> Infrastructure

## Frontend Architecture

### 7.1 Frontend Responsibilities
✅ **已實作的前端職責**：
- 頁面呈現與使用者互動
- 表單輸入及前端基本驗證
- 車輛、據點、價格與預約資訊的呈現
- 呼叫Laravel API
- 處理API成功、驗證失敗、權限錯誤與伺服器錯誤
- 呈現載入中狀態

⚠️ **部分實作的能力**：
- 空資料狀態處理
- 操作結果訊息呈現

### 7.2 Frontend Module and Page Inventory

| Page / Component | Responsibility | API Dependency | Status | Evidence |
|-----------------|----------------|----------------|--------|----------|
| HomeView.vue | 租戶首頁，展示租戶資訊、精選車輛 | GET /public/{tenant}/vehicles | ✅ 完成 | resources/js/spa/views/HomeView.vue |
| VehiclesPublicView.vue | 車輛列表頁面，顯示所有可租車輛 | GET /public/{tenant}/vehicles | ✅ 完成 | resources/js/spa/views/VehiclesPublicView.vue |
| VehicleDetailView.vue | 車輛詳情頁面，顯示車輛資訊 | GET /public/{tenant}/vehicles/{id} | ✅ 完成 | resources/js/spa/views/VehicleDetailView.vue |
| BookingView.vue | 預約表單頁面，選擇日期、據點、提交預約 | POST /public/{tenant}/vehicles/{id}/calculate-price<br>GET /public/{tenant}/branches<br>POST /{tenant}/customer/reservations | ✅ 完成 | resources/js/spa/views/BookingView.vue |
| BookingConfirmationView.vue | 預約成功確認頁面 | GET /{tenant}/customer/reservations/{id} | ✅ 完成 | resources/js/spa/views/BookingConfirmationView.vue |
| LoginView.vue | 會員登入頁面 | POST /{tenant}/customer/auth/login | ✅ 完成 | resources/js/spa/views/LoginView.vue |
| RegisterView.vue | 會員註冊頁面 | POST /{tenant}/customer/auth/register | ✅ 完成 | resources/js/spa/views/RegisterView.vue |
| MyBookingsView.vue | 會員預約列表頁面 | GET /{tenant}/customer/reservations | ✅ 完成 | resources/js/spa/views/MyBookingsView.vue |
| MyBookingDetailView.vue | 會員預約詳情頁面，支援取消預約 | GET /{tenant}/customer/reservations/{id}<br>POST /{tenant}/customer/reservations/{id}/cancel | ✅ 完成 | resources/js/spa/views/MyBookingDetailView.vue |

### 7.3 Frontend API Integration

✅ **已實作的機制**：
- **API Base URL**: 從環境變數`VITE_API_URL`讀取，預設為`/api/v1`
- **請求封裝**: 使用Axios建立實體，統一處理請求
- **Tenant Slug**: 從URL路徑提取，自動注入API請求路徑
- **JWT傳遞**: 從localStorage讀取token，自動加入Authorization標頭
- **401錯誤處理**: 自動清除token並跳轉到登入頁面

⚠️ **待改善項目**：
- Token自動刷新機制未實作
- 全域的422/403/404/500錯誤處理不完整，各頁面分散處理
- 重複提交防護機制未實作
- 頁面重新整理時的狀態恢復機制簡易

✅ **已驗證的串接項目**：
- 前端送出`pickup_branch_id`、`return_branch_id`等必要欄位
- 前端顯示的價格與後端試算結果一致
- 取車與還車據點已完成串接

## Multi-Tenant Architecture

### 8.1 Tenant Resolution
- 前端：從URL路徑的第一段提取tenant slug（如`/default/vehicles`中的`default`）
- 後端：通過`ResolvePublicTenant`中間件解析租戶，設定`TenantContext`
- 所有API路由都包含tenant slug參數，確保租戶上下文正確建立

### 8.2 Data Isolation
- 所有核心資料表都包含`tenant_id`外鍵
- 查詢時自動限定當前租戶ID
- 通過資料庫外鍵約束防止跨租戶資料關聯

## Authentication and Authorization

### 9.1 Frontend Customer Auth
- JWT認證機制，token存儲於localStorage
- 支援註冊、登入、登出、token刷新
- 需要認證的路由由Vue Router導航守衛保護

### 9.2 Admin Panel Auth
- Laravel內建的session認證
- Filament Shield管理權限，支援RBAC
- 管理員只能存取所屬租戶的資料

## Core Domain and Entities

### 10.1 Implemented Entities
| Entity | Status | Table |
|--------|--------|-------|
| Tenant | ✅ | tenants |
| User (Admin) | ✅ | users |
| Vehicle | ✅ | vehicles |
| VehiclePricing | ✅ | vehicle_pricing |
| Branch | ✅ | branches |
| Customer | ✅ | customers |
| Holiday | ✅ | holidays |
| Reservation | ✅ | reservations |
| Order | ✅ | orders |
| Payment | ❌ 未實作 | - |

## Database Design

### 11.1 Core Tables Schema Verification

#### reservations Table
| Column | Type | Exists | Notes |
|--------|------|--------|-------|
| pickup_branch_id | foreignId | ✅ | 2026_10_14_021834_add_pickup_return_branch_ids_to_reservations_table.php |
| return_branch_id | foreignId | ✅ | 同上 |
| amount | decimal(12,2) | ✅ | 存儲預約總金額 |
| rental_days | integer | ✅ | 存儲租用天數 |
| pricing_snapshot | JSON | ❌ | 未實作，僅存儲總金額與天數 |

#### orders Table
- ✅ 已建立，與reservations關聯
- payments表未建立，支付功能僅為規劃

### 11.2 Indexes and Constraints
- 所有外鍵都建立索引
- `reservations`表有複合索引`[tenant_id, vehicle_id, start_at, end_at]`用於衝突檢測
- 所有外鍵使用`onDelete('restrict')`防止誤刪除
- 金額欄位全部使用decimal類型，符合財務資料要求

### 11.3 Status Enums Implementation

#### Reservation Status (app/Models/Reservation.php)
```php
const STATUS_PENDING = 'PENDING';      // 待確認
const STATUS_CONFIRMED = 'CONFIRMED';  // 已確認
const STATUS_PICKED_UP = 'PICKED_UP';  // 已取車
const STATUS_RETURNED = 'RETURNED';    // 已歸還
const STATUS_CANCELLED = 'CANCELLED';  // 已取消

// 佔用庫存的狀態
const BLOCKING_STATUSES = [
    self::STATUS_PENDING,
    self::STATUS_CONFIRMED,
    self::STATUS_PICKED_UP,
];
```

#### VehicleStatus Enum (app/Domain/Rental/Enums/VehicleStatus.php)
- 已實作車輛狀態列舉

### 11.4 Holiday Data Scope
- Holiday按租戶隔離，每個租戶擁有自己的假日設定
- ❌ 不是全平台共用資料

## Reservation and Availability

### 12.1 Implemented Business Rules

✅ **時間區間規則**：
- 使用`[start_at, end_at)`邏輯檢測衝突（`start_at < 新結束時間 && end_at > 新開始時間`）
- `end_at > start_at`強制驗證
- 最短租期4小時，驗證於`validateMinimumRentalTime()`

✅ **庫存管理**：
- `PENDING`、`CONFIRMED`、`PICKED_UP`狀態佔用庫存（BLOCKING_STATUSES）
- `RETURNED`、`CANCELLED`狀態釋放庫存
- 車輛位置與取車據點驗證：`canBeAtBranchAt()`方法檢查車輛能否在指定時間位於取車據點

✅ **狀態轉換**：
- `confirm()`: PENDING -> CONFIRMED
- `markAsPickedUp()`: CONFIRMED -> PICKED_UP
- `markAsReturned()`: PICKED_UP -> RETURNED
- 取消邏輯實作於CancelReservation Use Case

✅ **併發安全機制**：
- 建立預約時使用資料庫交易
- 車輛查詢使用`lockForUpdate()`行級鎖定
- 衝突檢查在交易內執行
- 但尚未經過大量併發測試驗證

## Pricing

### 13.1 Implemented Pricing Logic

✅ **優先順序**：Holiday > Weekend > Weekday（實作於PricingService）
✅ **不足24小時進位一天**：使用分鐘數計算，無條件進位
✅ **欄位存在狀況**：
- `rental_days`（對應total_days）: ✅ 存在
- `amount`（對應total_amount）: ✅ 存在
- unit_price等明細欄位：❌ 不存在，僅存儲總計

✅ **價格快照一致性**：
- VehiclePricing更新不會影響已建立的Reservation金額
- 預約建立時計算的金額永久存儲在reservations.amount欄位

## Order and Payment

### 14.1 Current Status
- Order模型已建立，與Reservation關聯
- Payment模型未實作，支付閘道整合未開始
- 訂單建立邏輯僅部分實作，完整的訂單到支付流程待開發

## API Architecture

### 15.1 API Routes Implementation Status

| Category | Routes Registered | Implemented | Tested | Documented | Integrated |
|----------|-------------------|-------------|--------|------------|------------|
| Public API | ✅ | ✅ | ⚠️ | ✅ | ✅ |
| Member API | ✅ | ✅ | ⚠️ | ✅ | ✅ |
| Merchant API | ❌ | ❌ | ❌ | ❌ | ❌ |

### 15.2 Implemented API Versioning
- 目前僅有v1版本，前綴`/api/v1/`
- 所有公開API位於`/api/v1/public/{tenantSlug}/`
- 所有會員API位於`/api/v1/{tenantSlug}/customer/`

### 15.3 OpenAPI Documentation
- 使用L5-swagger實作，API文件已生成
- 存取路徑：/api/documentation

## Admin Architecture

### 16.1 Filament Resources Implemented
所有資源都已實作Filament管理界面：
- Branches (BranchResource)
- Customers (CustomerResource)
- Holidays (HolidayResource)
- Orders (OrderResource)
- Reservations (ReservationResource)
- Tenants (TenantResource)
- Users (UserResource)
- Vehicles (VehicleResource)
- VehiclePricings (VehiclePricingResource)

### 16.2 Tenant Scope in Admin
- Filament資源自動套用當前租戶的查詢範圍
- 管理員只能查看和編輯所屬租戶的資料

## End-to-End Booking Flow

### 17.1 Implemented Flow (as per code)

1. ✅ 使用者進入租戶頁面（如`/default`）
2. ✅ 前端取得租戶的公開車輛列表
3. ✅ 使用者瀏覽車輛，進入車輛詳情頁
4. ✅ 使用者選擇租車期間與取還車據點
5. ✅ 前端呼叫`/vehicles/{id}/calculate-price`取得價格試算
6. ✅ 使用者登入會員帳號（或訪客流程待實作）
7. ✅ 前端提交預約請求到`/customer/reservations`
8. ✅ Laravel驗證租戶、車輛、據點、租期、價格及庫存
9. ✅ Laravel在交易內使用lockForUpdate()建立預約
10. ✅ 前端跳轉到預約確認頁面，顯示預約結果
11. ✅ 使用者可以在`/my-bookings`查看預約，或取消預約

### 17.2 Verified Integration Points
- 前端確實傳送`pickup_branch_id`、`return_branch_id`
- 後端驗證取車據點的有效性，包含車輛位置檢查
- 支援跨據點租車（後端驗證僅確保據點屬於同一租戶）
- 預約失敗時，前端能捕獲API錯誤並顯示
- 價格試算結果與建立預約時的後端計算一致

### 17.3 Status Classification
- **前端畫面可操作**: 所有已實作頁面都可操作
- **API串接完成**: 會員預約流程完整串接
- **完整端到端流程已驗證**: 基本預約流程已通過手動驗證

## Security

### 18.1 Implemented Security Measures
- 租戶隔離中間件確保跨租戶存取被阻擋
- JWT驗證保護會員API
- Filament的權限系統保護管理後台
- 所有使用者輸入都通過Laravel Request驗證
- SQL注入防護由Laravel Query Builder提供

### 18.2 Security Gaps
- Token無自動刷新機制
- XSS防護依賴Vue.js原生能力
- CSRF保護在SPA場景下未完整配置

## Testing Strategy

### 19.1 Current Testing Status
- 單元測試：少量基礎測試存在
- Feature測試：API測試不完整
- 前端測試：無
- 手動測試：核心預約流程已驗證

### 19.2 Unverified Areas
- 高併發場景下的預約衝突避免
- 邊緣案例的定價計算正確性
- 權限系統的完整覆蓋測試

## Observability, Queue and Cache

### 20.1 Current Implementation
- Redis未使用，快取使用文件驅動
- Queue使用數據庫驅動，僅有CheckReservationsCommand
- Logging使用Laravel預設的單日誌文件
- 無額外的監控或觀測工具

## Development Environment

### 21.1 Local Setup
- 支援Laravel Sail本機開發
- Vite提供熱重載開發服務
- SQLite可用於本機開發數據庫
- Docker配置存在，可容器化部署

## Acceptance Criteria

### 22.1 Frontend Acceptance Criteria

| Criteria | Verification Method | Result |
|----------|---------------------|--------|
| 頁面與路由可正常載入 | 手動測試 | ✅ Passed |
| API Base URL與環境設定正確 | 檢查vite.config與環境變數 | ✅ Passed |
| 車輛、據點與價格資料能正確呈現 | 手動測試前端頁面 | ✅ Passed |
| 表單送出的欄位符合後端要求 | 檢視前端程式碼與後端Request驗證 | ✅ Passed |
| API驗證錯誤能提供可理解的使用者提示 | 手動測試錯誤場景 | ⚠️ Partial - 基本錯誤有處理，邊緣場景待完善 |
| JWT與會員操作符合實際認證流程 | 手動測試登入登出 | ✅ Passed |
| 送出失敗時不會錯誤顯示預約成功 | 程式碼邏輯檢查 | ✅ Passed - 僅在API成功後才跳轉 |
| 不將前端價格或租戶資訊當成可信任的商業依據 | 後端永遠重新計算價格 | ✅ Passed - 後端永遠重新驗證所有資料 |

### 22.2 End-to-End Booking Acceptance Criteria

| Criteria | Verification Method | Result |
|----------|---------------------|--------|
| 可依實際支援的流程完成預約 | 手動完成完整預約流程 | ✅ Passed |
| 不符合租期或據點規則的請求會被後端拒絕 | 手動測試4小時以下預約 | ✅ Passed - 後端正確拒絕 |
| 衝突預約不能成功建立 | 手動建立重疊預約 | ✅ Passed - 後端正確拒絕 |
| 相鄰但不重疊的預約符合時間區間規則 | 手動建立相鄰預約 | ✅ Passed |
| 會員不能存取其他租戶的預約 | 嘗試存取其他租戶的預約ID | ✅ Passed - 租戶隔離生效 |
| 訪客及會員流程依各自實際設計驗證 | 僅會員流程完整實作 | ⚠️ Partial - 訪客流程未完整實作 |
| 前端能正確呈現成功與失敗結果 | 手動測試成功與失敗場景 | ✅ Passed |

## Future Expansion

### 23.1 Planned Features
- Payment模型與支付閘道整合
- 完整的訪客預約流程
- 電子郵件通知系統
- 車輛保養與維護提醒
- 數據報表與分析功能
- 多語系支援
- Redis快取與Queue優化
- 完整的自動化測試覆蓋

### 23.2 Architecture Improvements
- 獨立的AvailabilityService
- 完整的Domain State Machine
- 自訂Domain Exceptions
- 完整的Repository層抽象
- API速率限制
- 完整的Token自動刷新機制

## Architecture Decisions

### 24.1 Decisions Made
1. **Modular Monolith over Microservices**: 保持簡單，滿足目前業務規模
2. **Tenant Slug in URL**: 簡化前後端租戶解析，適合SaaS場景
3. **Filament作為管理後台**: 節省開發時間，快速建立管理界面
4. **Vue 3 SPA作為前端**: 現代化前端技術棧，良好的生態系統
5. **集中式定價邏輯**: PricingService確保全系統定價計算一致

### 24.2 Open Questions (Pending Decisions)
- 是否需要獨立的Platform Admin與Merchant Admin分離？
- 何時導入完整的事件驅動架構？
- 是否需要實作多區域部署支援？
- 支付閘道選擇哪家第三方服務？
- 是否需要實作資料庫讀寫分離？

---
## Document Change Log
| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-10-14 | System | 初始完整系統設計規格，基於實際程式碼實作狀態編寫 |

---
*本文件依據實際程式碼、遷移檔、路由定義等真實實作狀態編寫，準確反映當前系統的實作進度與架構設計。*