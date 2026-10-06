# RentFlow

> Multi-Tenant Rental SaaS Platform built with Laravel 12

RentFlow 是一套以 **B2B 租車業者**為主要目標的 Multi-Tenant SaaS 平台。

第一階段以租車業者的核心營運需求為中心，建立租戶、使用者、車輛、預約、訂單與付款等基礎架構，並以 Laravel 12 作為單體應用核心。

專案的重點不是堆疊框架或設計模式，而是建立一個具備：

- Multi-Tenant Isolation
- Reservation Concurrency Control
- Deterministic Pricing
- Reservation State Machine
- Role & Policy Authorization
- Clear Domain Boundaries
- Production-oriented Architecture

的實際 SaaS 系統。

---

## 1. Project Positioning

RentFlow 的目標不是單純的租車 CRUD 系統，而是逐步建立一個可以支撐租車業者實際營運的 SaaS 平台。

### Phase 1

以租車業者管理平台為核心：

```text
Rental Operator
       │
       ▼
┌─────────────────────┐
│      RentFlow       │
├─────────────────────┤
│ Customer Web        │
│ Merchant Admin      │
│ Platform Admin      │
│ REST API            │
│ Rental Domain       │
└─────────────────────┘
       │
       ├── MySQL
       ├── Redis
       └── Queue
```

Laravel 負責：

- Web Application
- Admin Panel
- REST API
- Authentication
- Authorization
- Tenant Context
- Rental Domain
- Reservation
- Pricing
- Order
- Payment

Redis 負責：

- Cache
- Queue
- Coordination

MySQL 是最終資料一致性的邊界。

Redis 不作為商業資料正確性的最終依據。

---

# 2. Current Status

目前專案已完成基礎平台架構。

### 已完成

- Laravel 12 專案初始化
- MySQL 基礎環境
- Redis 基礎環境
- Filament 5 Admin Panel
- JWT API Authentication 基礎設置
- Spatie Permission
- Multi-Tenant 基礎架構
- Tenant Model
- User / Tenant 關聯
- Role 基礎架構
- Laravel Policy Authorization 架構
- Platform Admin
- Tenant Admin
- Tenant Staff
- Tenant 使用者資料範圍限制
- User / Tenant / Role 基礎管理介面
- Traditional Chinese Admin UI

目前尚未進入完整 Rental Domain 實作。

---

# 3. Technology Stack

| Component | Technology |
| --- | --- |
| Language | PHP 8.2+ |
| Framework | Laravel 12 |
| Admin Panel | Filament 5 |
| Database | MySQL |
| Cache / Queue | Redis |
| API Authentication | JWT |
| Authorization | Laravel Policy + Spatie Permission |
| Frontend Admin | Filament |
| Testing | PHPUnit |
| Runtime | PHP-FPM / Docker |

---

# 4. Architecture

RentFlow 第一階段採用 **Modular Monolith** 思路，但不過度抽象。

```text
┌─────────────────────────────────────────────┐
│                  RentFlow                   │
│                                             │
│  Customer Web                               │
│  Merchant Admin                             │
│  Platform Admin                             │
│  REST API                                   │
│                                             │
│              Application Layer              │
│                     │                       │
│                     ▼                       │
│              Rental Domain                  │
│                                             │
│  Availability                              │
│  Pricing                                   │
│  Reservation                               │
│  Order                                     │
│  Payment                                   │
│                                             │
└─────────────────────────────────────────────┘
              │                    │
              ▼                    ▼
           MySQL                 Redis
```

目前不拆分 Microservices。

也不引入 FastAPI 作為第二套 Backend。

原因很簡單：

> 在 Domain 還沒有真正遇到服務拆分需求之前，不先增加分散式系統的複雜度。

---

# 5. Multi-Tenant Architecture

RentFlow 採用 **Shared Database / Shared Schema** 的 Multi-Tenant 架構。

Business Data 透過 `tenant_id` 進行隔離。

```text
Tenant A
 ├── Users
 ├── Vehicles
 ├── Customers
 ├── Reservations
 ├── Orders
 └── Payments

Tenant B
 ├── Users
 ├── Vehicles
 ├── Customers
 ├── Reservations
 ├── Orders
 └── Payments
```

核心原則：

> 所有租戶商業資料必須具備明確的 Tenant Context。

跨 Tenant 存取視為錯誤。

---

## 5.1 Platform Admin

Platform Admin 屬於平台層級，不強制綁定單一 Tenant。

因此：

```text
Super Admin
tenant_id = null
```

Super Admin 可以管理：

- Tenants
- Users
- Roles
- Platform-level configuration

---

## 5.2 Tenant User

一般租戶使用者屬於特定 Tenant。

例如：

```text
Tenant
└── 台北租車
    ├── Tenant Admin
    ├── Tenant Staff
    └── Tenant Staff
```

Tenant User 在 Admin Panel 中只能看到自己 Tenant 的資料。

---

# 6. Authorization

授權架構採用：

```text
User
 │
 ▼
Spatie Permission
 │
 ▼
Role / Permission
 │
 ▼
Laravel Policy
 │
 ▼
Filament Resource
```

這裡刻意將：

**Role / Permission**

與：

**Authorization Decision**

分開。

Spatie Permission 負責角色與權限資料。

Laravel Policy 負責實際的：

```text
Can View?
Can Create?
Can Update?
Can Delete?
```

因此不在每個 Resource 裡自行撰寫大量角色判斷。

---

## 6.1 Current Roles

目前基礎角色：

```text
Super Admin
Tenant Admin
Tenant Staff
```

### Super Admin

平台管理者。

可以跨 Tenant 管理平台資料。

### Tenant Admin

租戶管理者。

只能操作自己 Tenant 的資料。

### Tenant Staff

租戶一般工作人員。

只能存取被授權的自己 Tenant 資料。

---

# 7. Admin Panel

Admin Panel 使用 Filament 5。

目前管理功能：

```text
平台管理
├── 租戶管理
├── 使用者管理
└── 角色管理
```

Admin UI 採用繁體中文。

Tenant-level Resource 使用 Filament Tenant Context 控制資料範圍。

Platform-level Resource 則不套用 Tenant Ownership。

例如：

```text
TenantResource
    └── Platform Level

RoleResource
    └── Platform Level

UserResource
    └── Tenant Scoped
```

其中 User 管理特別區分：

```text
Super Admin
    → 全部 Tenant Users

Tenant Admin
    → 自己 Tenant Users

Tenant Staff
    → 自己 Tenant Users
```

---

# 8. Rental Domain

Rental Domain 是 RentFlow 的核心商業邏輯。

預計結構：

```text
app/Domain/Rental/
├── Models/
├── Enums/
├── Exceptions/
├── Services/
│   ├── AvailabilityService
│   ├── PricingService
│   └── ReservationService
└── StateMachines/
```

Domain Layer 負責商業規則。

Controller、Filament Resource 或 API Layer 不直接承擔核心商業邏輯。

---

# 9. Reservation

Reservation 是 RentFlow 最重要的 Domain。

狀態：

```text
PENDING
   │
   ▼
CONFIRMED
   │
   ▼
PICKED_UP
   │
   ▼
RETURNED
```

取消：

```text
PENDING ──────► CANCELLED

CONFIRMED ────► CANCELLED
```

`RETURNED` 與 `CANCELLED` 為 Terminal State。

不允許任意跳轉狀態。

---

# 10. Vehicle Availability

同一台 Vehicle 在相同時間區間只能存在一筆 Inventory-occupying Reservation。

Occupying Status：

```text
PENDING
CONFIRMED
PICKED_UP
```

Non-occupying Status：

```text
CANCELLED
RETURNED
```

時間區間採用：

```text
[start_at, end_at)
```

衝突判斷：

```text
existing.start_at < new.end_at
AND
existing.end_at > new.start_at
```

因此：

```text
Reservation A
10:00 ───────── 14:00

Reservation B
14:00 ───────── 18:00
```

兩者不衝突。

---

# 11. Reservation Concurrency

Vehicle Availability 的正確性由 MySQL 保證。

核心流程：

```text
BEGIN TRANSACTION
        │
        ▼
Lock Vehicle
        │
        ▼
Check Reservation Conflict
        │
        ├── Conflict → Reject
        │
        └── Available
                │
                ▼
          Create Reservation
                │
                ▼
          COMMIT
```

Redis 不作為 Reservation Concurrency 的最終正確性邊界。

即使 Redis 發生：

- Connection failure
- Timeout
- Restart
- Lock expiration

也不應該造成兩筆 Reservation 同時佔用同一台 Vehicle。

---

# 12. Pricing

Pricing 必須具備 deterministic behavior。

目前規則：

```text
Holiday
   ↓
Weekend
   ↓
Weekday
```

Priority：

```text
Holiday > Weekend > Weekday
```

租車天數採：

> 每不足 24 小時的區間，仍計為一天。

例如：

```text
24 hours      → 1 day
24h 1 minute  → 2 days
48 hours      → 2 days
48h 1 minute  → 3 days
```

Reservation 建立後，價格資料必須 Freeze。

保存：

```text
unit_price
total_days
total_amount
pricing_snapshot
```

Reservation 後續不能因為 Vehicle Pricing 改變而重新計算歷史價格。

---

# 13. Database Design

核心資料表：

```text
tenants
users
vehicles
vehicle_pricing
customers
holidays
reservations
orders
payments
```

主要關係：

```text
Tenant
 ├── Users
 ├── Vehicles
 ├── Customers
 ├── Reservations
 ├── Orders
 └── Payments

Vehicle
 └── Reservations

Customer
 └── Reservations

Reservation
 └── Order
      └── Payments
```

核心商業資料使用：

```text
tenant_id
```

建立租戶隔離。

---

# 14. Data Consistency

RentFlow 對一致性的基本原則：

```text
MySQL
  ↓
Source of Truth
```

Redis：

```text
Cache
Queue
Coordination
```

不使用 Redis 作為：

```text
Business Truth
Financial Truth
Inventory Truth
```

例如 Vehicle Availability：

```text
MySQL Transaction
        +
SELECT ... FOR UPDATE
        +
Conflict Check
```

才是最終正確性來源。

---

# 15. Engineering Principles

RentFlow 不以「用了多少設計模式」作為架構品質指標。

優先原則：

```text
Low Context
Low Coupling
Small Diff
Small Regression Scope
```

以及：

```text
KISS
Minimal Abstraction
Explicit Business Rules
Database as Consistency Boundary
Policy-based Authorization
```

不會因為「Enterprise」這個詞而增加：

- 不必要的 Service
- Repository
- DTO
- Trait
- Helper
- Factory
- Interface
- Event Bus
- CQRS
- Event Sourcing
- Microservice

只有在實際需求出現時才增加抽象。

---

# 16. Project Structure

目前主要結構：

```text
rentflow/
├── app/
│   ├── Domain/
│   │   └── Rental/
│   ├── Filament/
│   │   └── Resources/
│   ├── Models/
│   ├── Policies/
│   └── Providers/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── docs/
│   └── sdd/
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── tests/
│
└── README.md
```

---

# 17. Development Seed Data

目前開發環境提供基本平台資料。

### Tenants

```text
台北租車
台中租車
高雄租車
```

### Roles

```text
Super Admin
Tenant Admin
Tenant Staff
```

每個 Tenant 提供基本管理者與工作人員帳號。

Development password：

```text
password
```

正式環境不得使用上述預設密碼。

---

# 18. Testing Strategy

測試將優先驗證商業規則，而不是只追求 Code Coverage。

核心測試包括：

### Tenant Isolation

```text
Tenant A
    ↓
不能讀取 Tenant B
```

### Reservation Conflict

```text
Same Vehicle
Same Time
    ↓
Reject
```

### Boundary

```text
10:00 - 14:00
14:00 - 18:00
    ↓
Allowed
```

### State Transition

```text
PENDING
  ↓
CONFIRMED
  ↓
PICKED_UP
  ↓
RETURNED
```

非法狀態轉換必須被拒絕。

### Price Freeze

建立 Reservation 後：

```text
Vehicle Pricing changed
        ↓
Existing Reservation price unchanged
```

### Cancellation

取消 Reservation 後：

```text
Vehicle Inventory
        ↓
Available again
```

---

# 19. API Direction

Customer / Public API 預計提供：

```text
GET    /api/v1/vehicles
GET    /api/v1/vehicles/{vehicle}
GET    /api/v1/vehicles/{vehicle}/availability
POST   /api/v1/reservations
GET    /api/v1/reservations/{reservation}
POST   /api/v1/reservations/{reservation}/cancel
```

Merchant API：

```text
GET    /api/v1/merchant/vehicles
POST   /api/v1/merchant/vehicles
PUT    /api/v1/merchant/vehicles/{vehicle}

GET    /api/v1/merchant/reservations
POST   /api/v1/merchant/reservations/{reservation}/confirm
POST   /api/v1/merchant/reservations/{reservation}/pickup
POST   /api/v1/merchant/reservations/{reservation}/return
```

API 的實際實作會依 Domain 完成進度逐步建立。

---

# 20. Roadmap

## Phase 1 — Platform Foundation

- [x] Laravel 12
- [x] Filament 5
- [x] MySQL
- [x] Redis
- [x] JWT Authentication
- [x] Spatie Permission
- [x] Multi-Tenant Foundation
- [x] Tenant Management
- [x] User Management
- [x] Role Management
- [x] Policy Authorization

## Phase 2 — Rental Core

- [ ] Vehicle Management
- [ ] Vehicle Pricing
- [ ] Customer Management
- [ ] Holiday Management
- [ ] Availability Service
- [ ] Pricing Service
- [ ] Reservation Service
- [ ] Reservation State Machine
- [ ] Reservation Concurrency Tests

## Phase 3 — Commercial Flow

- [ ] Order
- [ ] Payment
- [ ] Cancellation
- [ ] Refund
- [ ] Merchant Dashboard
- [ ] Customer Reservation Flow

## Phase 4 — Platform Capability

- [ ] Tenant Dashboard
- [ ] Platform Dashboard
- [ ] Reporting
- [ ] Operational Metrics
- [ ] Audit Log
- [ ] Notification

## Future

```text
Rental SaaS
     ↓
Marketplace
     ↓
Payment / Settlement
     ↓
Revenue Management
     ↓
AI Mobility Platform
```

未來是否進入上述階段，將依實際商業需求與產品驗證結果決定。

---

# 21. Architecture Decision Records

重要架構決策會記錄於：

```text
docs/adr/
```

例如：

```text
Shared Database Multi-Tenancy
Redis is not the Consistency Boundary
Reservation Concurrency Strategy
Pricing Snapshot
Policy-based Authorization
Modular Monolith
```

ADR 的目的不是記錄「用了什麼 Pattern」，而是記錄：

```text
Problem
    ↓
Constraints
    ↓
Decision
    ↓
Trade-offs
```

---

# 22. Development Philosophy

RentFlow 的開發原則：

> 先解決真實問題，再增加抽象。

因此每一個架構決策都應回答：

1. 解決什麼問題？
2. 為什麼目前需要？
3. 有沒有更簡單的方式？
4. 會不會增加維護成本？
5. 是否會擴大 Regression Scope？

如果沒有實際需求：

> 不提前設計。

---

# 23. Project Goal

RentFlow 同時是一個：

- SaaS Product Prototype
- Enterprise Architecture Showcase
- Laravel Backend Project
- Multi-Tenant System
- Reservation / Inventory Concurrency Case Study

但最重要的是：

> 用一個實際的租車 SaaS 問題，展示如何處理 Multi-Tenant Isolation、Concurrency、State Machine、Pricing Consistency 與 Authorization。

而不是展示可以使用多少 Framework 或 Design Pattern。

---

## License

This project is currently maintained as a private product / engineering showcase.
