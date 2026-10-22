# RentFlow

> Multi-Tenant Rental SaaS Platform built with Laravel 12

RentFlow 是一套以 **B2B 租車業者**為主要目標的 Multi-Tenant SaaS 平台，目前已實作完整的租車核心營運系統，包含：

- 多租戶隔離架構
- 車輛庫存與預約管理
- 彈性定價與假日加成機制
- 跨據點取還車流程
- 會員/訪客預約 API
- Filament 5 商家後台管理系統

---

## 1. Project Overview

RentFlow 是一個以真實租車營運場景為基礎的技術展示專案，重點解決 Multi-Tenant 隔離、預約併發控制、定價一致性、狀態機可靠性等工程難題。

### 核心商業價值

- **租車業者零技術門檻開店**：每個租戶（租車公司）可獨立管理自己的車輛、客戶、據點
- **自動化庫存管理**：車輛位置追蹤、維修時段管理、預約自動佔用庫存
- **彈性定價系統**：平日/假日/特殊節日不同價格，自動計算租期與總金額
- **完整的生命週期追蹤**：預約→確認→取車→還車→完成，每個狀態都有嚴格的轉換限制
- **會員體驗**：前端客戶可註冊成為會員，管理自己的預約歷史

---

## 2. Engineering Highlights

### Multi-Tenant Isolation
- **解析策略**：公開 API 透過 URL 中的 `{tenantSlug}` 解析租戶；後台 Filament 與會員 API 透過認證使用者的租戶關聯解析，由`ResolvePublicTenant`中間件處理租戶上下文存入TenantContext
- **資料隔離**：所有商業實體（車輛、客戶、預約、訂單、據點）的資料表都包含`tenant_id`欄位。應用層透過Repository、Policy層級強制同租戶存取，實現跨租戶資源隔離
- **租戶驗證**：所有外部傳入的Vehicle、Branch、Customer ID都會在業務邏輯中驗證所屬租戶一致性，防止越權存取
- **Customer 去重**：客戶資料綁定到所屬租戶，跨租戶客戶資料完全隔離，即使使用同一個email也會建立獨立記錄

### Reservation Correctness
- **半開區間設計**：採用 `[start_at, end_at)` 時間區間，確保前一個預約的結束時間可做為下一個預約的開始時間，避免時間空隙浪費
- **庫存佔用狀態**：`PENDING`/`CONFIRMED`/`PICKED_UP` 三個狀態會鎖定車輛，`RETURNED`/`CANCELLED` 釋放庫存
- **衝突檢查**：使用資料庫層級的查詢 `existing.start_at < new.end_at AND existing.end_at > new.start_at` 偵測時間重疊
- **交易鎖定**：建立預約時使用資料庫交易，並透過 `lockForUpdate()` 對車輛記錄加排他鎖，在MySQL InnoDB引擎下可防止併發預約造成超賣。鎖定、衝突檢查與預約建立都在同一個交易內完成
- **最短租期限制**：所有預約必須至少滿 4 小時，驗證邏輯位於 Reservation Model 中
- **併發保證**：核心邏輯已實作交易與排他鎖機制，但尚未在生產級MySQL環境下進行高併發壓力測試

### Pricing Consistency
- **定價優先順序**：實作假日價格 > 周末價格 > 平日價格的優先權，正確區分租戶自定義節假日與一般周末
- **租期計算**：每不足24小時仍計為一整天，使用分鐘數向上取整，邏輯由PricingService統一處理
- **價格儲存**：預約建立時即計算並儲存`rental_days`與`amount`欄位，後續車輛定價變更不會影響歷史預約的金額。目前僅儲存總金額與租期，尚未實作完整的`pricing_snapshot`詳細價格明細
- **金額精度**：所有金額使用decimal(12,2)儲存，避免浮點數誤差

### Branch & Vehicle Location
- **三個位置語義**：
  - `current_branch_id`：車輛目前實體所在的據點
  - `pickup_branch_id`：預約的取車據點
  - `return_branch_id`：預約的還車據點
- **車輛位置預測**：Vehicle模型提供`getExpectedBranchIdAt()`方法，可根據歷史預約的還車據點，預測未來某個時間點車輛會在哪個據點。搭配`canBeAtBranchAt()`方法驗證車輛是否能在指定時間到位於指定據點
- **跨據點支援**：資料表已儲存取車與還車據點欄位，後端已實作完整的車輛位置驗證邏輯，可確保車輛在取車時間確實位於指定據點。但前端尚未提供完整的跨據點選擇流程，目前需手動指定有效據點

### API / Frontend Integration
- **公開預約流程**：訪客可不註冊直接預約；會員可登入後管理自己的所有預約
- **租戶解析中間件**：`ResolvePublicTenant` 中間件自動從 URL 參數解析租戶並存入 TenantContext
- **JWT 認證**：會員 API 使用 tymon/jwt-auth 實作無狀態認證，支援登入/登出/刷新 Token

---

## 3. Feature Status

| 功能 | 狀態 | 驗證依據 |
|---|---|---|
| Multi-Tenant Isolation | ✅ 已實作且有驗證 | TenantContext Middleware、所有Model皆有tenant_id、Policy隔離 |
| 車輛/據點管理 | ✅ 已實作且有驗證 | Vehicle/Branch Model、Filament Resource、API實作 |
| 預約核心邏輯 | ✅ 已實作且有驗證 | UseCases、狀態轉移驗證、時間衝突檢測、交易鎖定 |
| 定價與快照機制 | ✅ 已實作且有驗證 | PricingService、預約建立時計算並儲存金額 |
| 訪客/會員預約流程 | ✅ 已實作且有驗證 | API路由、GuestStore/MemberStore分離、JWT認證 |
| 訂單生命週期 | ✅ 已實作 | 預約確認時在同一交易內自動創建Order、訂單編號使用lockForUpdate()防止重複生成 |
| Filament 後台管理 | ✅ 已實作 | 所有Resource已建立、狀態操作按鈕完成、5個儀表板Widget已實作 |
| OpenAPI 文件 | 🟡 部分完成 | l5-swagger已配置，但部分API尚未補全註解 |
| 自動化測試套件 | 🟡 部分實作 | PHPUnit已配置，核心商業邏輯有基礎測試案例覆蓋，但尚未完整 |
| Payment 支付串接 | ❌ 未實作 | 目前僅有Order訂單模型，尚未建立Payment相關資料表與支付流程串接 |

狀態說明：✅ 完整實作 🟡 部分實作 ❌ 尚未開始

---

## 3.1 AI Agent 功能介紹

RentFlow 內建基於 Google Gemini 的 AI Agent 自動化分析與修復系統，支援兩種任務模式：

### 支援的任務模式

#### 純分析模式
- 使用者提供既有的錯誤描述或測試失敗內容
- **`test_path = NULL`**：不會自動執行 PHPUnit
- Agent 將提供的內容交給 Gemini 分析，保存 AI 分析結果
- 提供可能的根因與修復建議，但**不會自動套用修復**
- 分析完成僅代表 AI 已回應，不代表修復已完成或測試已通過
- 若修復提案無效但分析本身有效，仍會標記為完成並記錄修復未套用
- 若 AI 回應無效或分析本身失敗，會正確標記為失敗狀態

#### 自動修復模式
- 必須指定有效的 `test_path`（測試檔案路徑）
- 系統會自動執行測試 → 分析失敗 → 生成修復方案 → 驗證修復的完整流程
- 預設最大迴圈次數：3 次（可透過 `--max-loops` 參數調整）
- 只有開啟 `--auto-apply-fixes` 參數才會自動套用驗證通過的修復
- 終止條件：測試通過、達到最大迴圈次數、AI 分析永久失敗、測試執行失敗

---

## 4. Technology Stack

| Component | Technology | 版本 | 說明 |
| --- | --- | --- | --- |
| Language | PHP | 8.2+ | 專案最低需求 |
| Framework | Laravel | 12.x | 主框架 |
| Admin Panel | Filament | 5.0 | 後台管理系統 |
| 權限管理 | Filament Shield | 4.3 | 搭配Spatie Permission的Filament權限外掛 |
| 權限核心 | Spatie Permission | 6.25 | Role/Permission基礎 |
| API認證 | JWT Auth | 2.3 | tymon/jwt-auth實作無狀態認證 |
| API文件 | L5 Swagger | 11.1 | OpenAPI/Swagger文件生成 |
| 資料庫 | MySQL | - | 主要資料儲存 |
| 快取/佇列 | Redis | 7-alpine | 快取與佇列處理 |
| 容器化 | Docker + Docker Compose | - | 開發環境容器化 |
| 前端框架 | Vue 3 | 3.5.x | 客戶端前端 |
| 測試框架 | PHPUnit | 11.5.3 | 單元測試與功能測試 |

---

## 5. System Architecture

RentFlow 採用 **Modular Monolith** 架構，程式碼分層明確但不過度抽象：

```text
┌─────────────────────────────────────────────────────┐
│                     HTTP Layer                      │
│  Filament Admin / REST API / Middleware             │
├─────────────────────────────────────────────────────┤
│               Application Layer                     │
│  UseCases / DTOs / Events                           │
├─────────────────────────────────────────────────────┤
│                 Domain Layer                         │
│  Models / Enums / Services / Business Rules         │
├─────────────────────────────────────────────────────┤
│              Infrastructure Layer                  │
│  Repositories / Eloquent Implementations            │
└─────────────────────────────────────────────────────┘
                              │
                              ▼
                           MySQL / Redis
```

### 請求流程範例（建立預約）
1. 前端發送請求到 API
2. `ResolvePublicTenant` 中間件解析租戶並存入 TenantContext
3. Controller 驗證輸入資料，建立 CreateReservationData DTO
4. CreateReservation UseCase 執行商業邏輯
5. 使用 DB::transaction() 包裝車輛鎖定、衝突檢查、預約建立
6. 觸發 ReservationCreated 事件
7. 返回建立好的預約資源

---

## 6. Multi-Tenant Isolation 實作細節

### 租戶解析策略
RentFlow 採用 **Shared Database / Shared Schema** 架構，所有商業資料皆透過 `tenant_id` 隔離。租戶解析有兩種路徑：

1. **公開 API**：透過 URL 路徑中的 `{tenantSlug}` 參數，由 `ResolvePublicTenant` 中間件查詢 `is_public = true` 的租戶
2. **後台 Filament / 會員 API**：透過認證使用者的 `tenants()` 關聯取得可存取的租戶，Super Admin 可存取全部租戶

### 跨租戶防護
- 所有查詢預約、車輛、客戶等資源時，都會自動加上 `tenant_id = current_tenant_id` 條件
- Laravel Policy 層級再次驗證操作權限，防止越權存取其他租戶資源
- Customer 模型完全綁定租戶，即使同一人使用相同email在不同租戶預約，也會建立獨立的客戶記錄

### 種子資料
系統預設建立3個公開租戶供開發測試：
- 台北租車 (taipei-rental)
- 台中租車 (taichung-rental)
- 高雄租車 (kaohsiung-rental)

---

## 7. Rental Domain 實體關係

### 核心實體關係圖
```text
Tenant (1) ──┬── (N) User
             ├── (N) Vehicle
             ├── (N) VehiclePricing
             ├── (N) Customer
             ├── (N) Holiday
             ├── (N) Branch
             ├── (N) Reservation
             └── (N) Order

Branch (1) ──┬── (N) Vehicle (current_branch_id)
             ├── (N) Reservation (pickup_branch_id)
             └── (N) Reservation (return_branch_id)

Vehicle (1) ── (N) Reservation
Customer (1) ── (N) Reservation
Reservation (1) ── (1) Order
```

### 主要實體欄位重點
- **Tenant**: `name`, `slug`, `is_public`
- **Vehicle**: `current_branch_id`, `status`, 維修時段欄位
- **Reservation**: `pickup_branch_id`, `return_branch_id`, `start_at`, `end_at`, `status`, `amount`, `rental_days`
- **Order**: 從預約自動生成，包含唯一訂單編號

---

## 8. Reservation Correctness 實作

### 合法狀態轉移
```text
PENDING
   │
   ├─► CONFIRMED (僅能從PENDING轉入)
   │      │
   │      ├─► PICKED_UP
   │      │      │
   │      │      └─► RETURNED
   │      │
   │      └─► CANCELLED (僅PENDING/CONFIRMED可取消)
   │
   └─► CANCELLED
```

### 狀態說明
- `PENDING`: 預約已建立但尚未確認，仍會佔用庫存
- `CONFIRMED`: 商家確認預約，自動創建訂單
- `PICKED_UP`: 客戶已取車
- `RETURNED`: 客戶已還車，釋放庫存
- `CANCELLED`: 預約已取消，釋放庫存

### 庫存佔用狀態
`BLOCKING_STATUSES = [PENDING, CONFIRMED, PICKED_UP]`，只有這三個狀態會佔用車輛庫存。

### 時間衝突檢測
```php
// 時間區間 [start_at, end_at) 的半開區間設計
existing.start_at < new.end_at AND existing.end_at > new.start_at
```
此邏輯確保前一個預約的結束時間可做為下一個預約的開始時間，不會產生空隙或重疊。

### 交易鎖定策略
所有預約建立操作都包裹在資料庫交易內，對車輛記錄加排他鎖：
```php
return DB::transaction(function () use ($data) {
    $vehicle = Vehicle::where('id', $data['vehicle_id'])
        ->where('tenant_id', $data['tenant_id'])
        ->lockForUpdate()
        ->firstOrFail();
    // ... 其餘驗證與建立邏輯
});
```
在MySQL的InnoDB引擎下，`lockForUpdate()`會對該記錄加上排他鎖，配合交易內的時間衝突檢查，防止併發預約導致超賣。

---

## 9. Pricing Rules 實作

### 定價優先順序
1. **假日價格 (holiday_price)**: 比對租戶的holidays資料表
2. **周末價格 (weekend_price)**: 星期六、星期日
3. **平日價格 (weekday_price)**: 其他工作日

### 租期計算
```php
$diffInMinutes = $startAt->diffInMinutes($endAt);
return (int)ceil($diffInMinutes / (24 * 60));
```
每不足24小時仍計為一整天，確保租金計算正確。

### 價格快照機制
預約建立時立即計算總金額並儲存於Reservation的`amount`和`rental_days`欄位，後續車輛定價變更不會影響歷史預約，實現價格快照。

---

## 10. Branch & Vehicle Location 語義

### 三個位置欄位的區別
| 欄位 | 語義 | 適用時機 |
|------|------|----------|
| `current_branch_id` | 車輛目前實體所在的據點 | 車輛閒置時的所在地 |
| `pickup_branch_id` | 預約的取車據點 | 客戶預約時指定的取車地點 |
| `return_branch_id` | 預約的還車據點 | 客戶預約時指定的還車地點 |

### 車輛位置預測
Vehicle模型提供`getExpectedBranchIdAt()`方法，可根據歷史預約的還車據點，預測未來某個時間點車輛會在哪個據點，做為跨據點調度的基礎。

### 目前限制
後端已完整實作跨據點取還車的位置驗證邏輯，`canBeAtBranchAt()`會檢查車輛是否能在指定時間到位於取車據點。但前端尚未提供動態的跨據點選擇UI，需手動指定符合條件的據點。

---

## 11. Authentication & Authorization

### 雙軌認證架構
- **Filament 後台**: 使用Session認證（web guard），僅限平台員工登入
- **會員API**: 使用JWT無狀態認證（api guard），供前端客戶使用

### 角色權限架構
```text
User
  │
  ├─ Super Admin (平台超管)：可存取所有租戶、所有資源
  ├─ Tenant Admin (租戶管理員)：可管理所屬租戶的所有資源
  └─ Tenant Staff (租戶員工)：可存取被授權的所屬租戶資源
```

### 授權流程
Spatie Permission 儲存角色權限資料 → Laravel Policy 判斷實際操作權限 → Filament Resource/API Controller 執行操作

---

## 12. API Reference

### API 版本與前綴
所有API皆為v1版本，前綴：`/api/v1/`

### 公開API（不需認證）
```
GET  /api/v1/public/{tenantSlug}/vehicles              # 取得租戶的公開車輛列表
GET  /api/v1/public/{tenantSlug}/vehicles/available    # 查詢車輛可用性
POST /api/v1/public/{tenantSlug}/vehicles/{id}/calculate-price  # 試算租金
GET  /api/v1/public/{tenantSlug}/vehicles/{id}        # 取得單一車輛詳情
GET  /api/v1/public/{tenantSlug}/branches              # 取得租戶的據點列表
POST /api/v1/public/{tenantSlug}/reservations         # 訪客建立預約
```

### 會員認證API
```
POST /api/v1/{tenantSlug}/customer/auth/register       # 會員註冊
POST /api/v1/{tenantSlug}/customer/auth/login         # 會員登入
POST /api/v1/{tenantSlug}/customer/auth/refresh       # 刷新JWT（需JWT認證）
POST /api/v1/{tenantSlug}/customer/auth/logout        # 會員登出（需JWT認證）
GET  /api/v1/{tenantSlug}/customer/auth/me            # 取得當前會員資料（需JWT認證）
POST /api/v1/{tenantSlug}/customer/auth/forgot-password # 忘記密碼
POST /api/v1/{tenantSlug}/customer/auth/reset-password # 重置密碼
POST /api/v1/{tenantSlug}/customer/profile            # 更新會員資料（需JWT認證）
```

### 會員專用API（需JWT認證）
```
GET    /api/v1/{tenantSlug}/customer/reservations      # 取得會員的預約列表
GET    /api/v1/{tenantSlug}/customer/reservations/{id} # 取得單一預約詳情
POST   /api/v1/{tenantSlug}/customer/reservations      # 會員建立預約
POST   /api/v1/{tenantSlug}/customer/reservations/{id}/cancel  # 取消預約
```

### OpenAPI 文件入口
`/swagger/api/documentation`（開發環境可存取）

---

## 13. Frontend & Admin Panel

### Filament 後台已實作功能
- **平台管理**: 租戶管理、使用者管理、角色管理（僅Super Admin可見）
- **租戶管理**: 車輛管理、定價管理、客戶管理、據點管理、假日管理
- **營運管理**: 預約管理、訂單管理，支援確認、取車、還車、取消等操作
- **儀表板**: 儀表板Widget顯示關鍵指標、近期預約、待處理操作等

### Vue前端已完成流程
- 會員註冊/登入
- 車輛瀏覽與查詢
- 可用性檢查與租金試算
- 訪客/會員預約流程
- 會員預約歷史管理

---

## 14. Database Model 主要資料表

| 資料表 | 說明 |
|-------|------|
| tenants | 租戶資料 |
| users | 平台使用者（後台管理員） |
| tenant_user | 使用者與租戶的關聯表 |
| vehicles | 車輛主檔 |
| vehicle_pricing | 車輛定價 |
| branches | 營業據點 |
| customers | 租車客戶 |
| holidays | 租戶自定義假日 |
| reservations | 預約主檔 |
| orders | 訂單主檔（從預約自動生成） |
| （無payments資料表） | 支付相關功能尚未實作 |

---

## 15. Testing Strategy

### 測試重點領域
- 租戶隔離測試：驗證無法跨租戶存取資源
- 預約衝突測試：驗證同一車輛同一時間無法建立多個預約
- 狀態轉移測試：驗證非法狀態跳轉會被拒絕
- 定價計算測試：驗證假日/周末/平日價格正確套用
- 時間邊界測試：驗證半開區間設計正確運作

### 執行測試
```bash
./vendor/bin/phpunit
```

---

## 16. Setup & Development

### 環境需求
- Docker + Docker Compose
- PHP 8.2+（本機開發時需要，若使用Docker則由容器負責）
- Node.js 20+

### 安裝步驟
```bash
# 複製環境變數範本
cp .env.example .env

# 安裝PHP依賴
composer install

# 安裝Node.js依賴
npm install

# 生成應用程式金鑰
php artisan key:generate

# 生成JWT金鑰
php artisan jwt:secret

# 啟動Docker容器
docker-compose up -d

# 執行資料庫遷移
php artisan migrate

# 執行種子資料（建立預設租戶與管理員）
php artisan db:seed

# 編譯前端資源
npm run dev
```

### 開發常用指令
```bash
# 後台檔案重新生成（若新增Filament資源）
php artisan filament:optimize

# 重新生成路由快取
php artisan route:clear

# 重新生成設定快取
php artisan config:clear

# 重新生成事件快取
php artisan event:clear
```

---

## 17. Project Structure

```text
rentflow/
├── app/
│   ├── Application/           # 應用程式層 - UseCases、DTOs、Events
│   │   └── Reservations/      # 預約相關應用程式邏輯
│   ├── Filament/              # Filament 後台資源
│   │   └── Resources/         # 所有資源檔案（Tenants/Vehicles/Branches等）
│   ├── Http/                  # HTTP 層 - Controller、Middleware、Request
│   │   ├── Controllers/       # API與前端控制器
│   │   └── Middleware/        # 自訂中介層（ResolvePublicTenant等）
│   ├── Models/                # 資料模型 - Tenant、Vehicle、Reservation等
│   ├── Policies/              # Laravel Policy - 授權邏輯
│   ├── Services/              # 商業服務 - PricingService等
│   └── Support/               # 通用支援類別 - TenantContext等
├── database/
│   ├── migrations/            # 資料庫遷移檔
│   ├── factories/             # 工廠
│   └── seeders/               # 種子資料
├── routes/
│   └── api.php                # API路由定義
├── resources/
│   └── js/                    # Vue前端原始碼
├── docker-compose.yml         # Docker Compose 設定
├── Dockerfile                 # PHP容器設定
└── README.md                  # 本文件
```

---

## 18. Roadmap

### 短期待完成項目
- [ ] 完成Payment支付串接（支援Line Pay、信用卡）
- [ ] 補全所有API的OpenAPI註解
- [ ] 寫完整核心商業邏輯的PHPUnit測試案例
- [ ] 實作前端跨據點取還車的車輛位置驗證
- [ ] 加入Email通知機制（預約確認、取車提醒等）

### 中期規劃
- [ ] 實作車輛維修排程管理
- [ ] 加入保險選項與附加服務計費
- [ ] 實作會員積分與等級系統
- [ ] 建立BI報表與數據分析儀表板
- [ ] 支援多語系後台與前端

### 長期方向
- [ ] 考慮將高負載服務分離為微服務
- [ ] 導入事件溯源與CQRS架構
- [ ] 實作多區域部署支援

---

## 19. Architecture Decisions

本專案已記錄的架構決策（ADR）：
- 無獨立ADR文件，所有設計決策皆反映在程式碼與本README中
- 核心設計原則：先解決實際問題，不過度預先抽象
- 堅持單一資料庫事務保證關鍵資料正確性，不依賴Redis做最終一致性邊界

---

## 19.1 AI Agent 手動驗證指南

### 前置準備
確認 `.env` 中已正確設定 Gemini API 相關參數：
```env
GEMINI_API_KEY=your_api_key_here
GEMINI_MODEL=gemini-3.5-flash-lite
GEMINI_DAILY_QUOTA=1000
```

### 手動驗證方式

#### 1. 建立純分析任務
使用 `--content` 參數直接傳入錯誤描述，不指定 `--test-path`（此時 test_path 會自動為 NULL），且不開啟 `--auto-apply-fixes`：
```bash
php artisan ai:agent start --content="測試失敗：假設 UserController 的 store 方法發生資料庫錯誤，請分析可能根因並提供修復建議。這是待分析的錯誤描述，尚未經過實際測試驗證。" --max-loops=3
```

#### 2. 確認任務建立成功
命令執行後會輸出：
```
Created new agent loop with ID: {YOUR_TASK_ID}
Loop configuration: max_loops=3, auto_apply=false, test_path=none
Job dispatched to queue. Processing will begin shortly.
Check status with: SELECT * FROM agent_loops WHERE id = {YOUR_TASK_ID}
```

#### 3. 依專案 Queue 設定執行任務
根據專案的佇列設定，啟動 Queue Worker 處理任務：
```bash
# 使用database連接的情況（開發環境常用）
php artisan queue:work --queue=default --tries=6

# 若使用sync驅動（本地開發測試），任務會立即執行
```

#### 4. 查看執行結果
透過資料庫查詢 `agent_loops` 資料表，觀察以下欄位的變化：
- `id`：任務ID
- `status`：任務狀態（pending → processing → completed/failed）
- `ai_analysis`：AI 回應的分析內容（JSON格式）
- `failure_reason`：失敗原因（若有）
- `termination_reason`：終止原因（完成時記錄為何結束）
- `current_loop`：當前執行的迴圈次數
- `last_test_exit_code`：PHPUnit 退出碼（純分析模式下此欄位不會更新）
- `last_test_output`：PHPUnit 輸出（純分析模式下此欄位不會更新）

查詢指令範例：
```sql
SELECT id, status, termination_reason, ai_analysis, completed_at 
FROM agent_loops 
WHERE id = YOUR_TASK_ID;
```

### 驗證情境範例

#### 情境 A：純分析成功 ✅
- 操作：使用非空的錯誤描述建立任務，不指定 test_path
- 預期結果：
  - `status` 變更為 `completed`
  - `ai_analysis` 有內容
  - `termination_reason` 為 `Analysis completed with proposed fixes (unverified, no test_path)` 或類似完成訊息
  - `last_test_exit_code` 維持 NULL（從未執行PHPUnit）
- ⚠️ 注意：此情境會消耗 Gemini API 配額

#### 情境 B：沒有可用修復方案
- 操作：傳入AI無法生成修復建議的模糊錯誤描述
- 預期結果：
  - `status` 變更為 `completed`
  - `termination_reason` 為 `Analysis completed with no fixes (text-only input, no test_path)`
  - AI 已完成分析但無法提供具體修復步驟

#### 情境 C：修復提案路徑無效
- 操作：提供的錯誤內容使AI生成了不存在的檔案路徑修復提案
- 預期結果：
  - `status` 仍為 `completed`（純分析模式下即使修復無效也標記完成）
  - `termination_reason` 為 `Analysis completed with invalid fix proposals (unable to apply fixes)`
  - 分析本身成功，但系統無法驗證修復路徑的有效性

#### 情境 D：PHPUnit 驗證（自動修復模式）
- 操作：指定有效的測試檔案路徑，建立自動修復任務
```bash
php artisan ai:agent start --test-path=tests/Feature/ExampleTest.php --max-loops=3
```
- 判斷測試是否真的執行過：
  - `last_test_exit_code` 有數值（非NULL）
  - `last_test_output` 包含 PHPUnit 的執行輸出
  - 可手動重新執行相同測試比對輸出：`./vendor/bin/phpunit tests/Feature/ExampleTest.php`

### 驗證結果判讀表

| 驗證項目 | 如何判斷 |
|---|---|
| Gemini API 呼叫 | 檢查 Laravel 日誌 `storage/logs/laravel.log` 中的 API 請求記錄及錯誤訊息 |
| AI 分析完成 | 檢查 `ai_analysis` 欄位不為NULL，且 `status` 為 `completed` |
| PHPUnit 執行 | 檢查 `last_test_exit_code` 與 `last_test_output` 有實際內容，僅有 `test_path IS NOT NULL` 的任務會更新這些欄位 |
| 修復已套用 | 檢查 `modified_files` 欄位有修改的檔案清單，或直接查看Git狀態 `git status` / `git diff`，不可只依賴AI提案 |
| 修復驗證通過 | 必須有修復後重新執行PHPUnit的測試結果，`status` 為 `completed` 且 `termination_reason` 為 `Tests passed successfully` |

> ⚠️ 重要提醒：`test_path = NULL` 的純分析任務從未執行 PHPUnit，因此 `last_test_exit_code = 0` 不代表測試通過，僅代表欄位未被更新。分析完成≠修復完成≠測試通過，這些是不同的結果。

---

## 20. License

本專案僅供教育與展示用途，程式碼不得用於商業產品。