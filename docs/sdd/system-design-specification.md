# RentFlow System Design Specification

> 系統設計規格（System Design Specification）

---

## 1. 文件目的

本文件定義 RentFlow 第一階段的系統設計、核心 Domain、資料模型、租戶隔離、授權、預約、定價、併發控制與未來擴充方向。

本文件的目的不是描述每一個 Laravel class 的實作細節，而是建立：

- 系統邊界
- Domain 邊界
- 資料一致性邊界
- Tenant 隔離規則
- Authorization 規則
- Reservation 商業規則
- Pricing 規則
- 系統元件責任
- 實作順序
- 驗收條件

所有後續實作應以本文件與相關 ADR 為設計依據。

---

# 2. Product Overview

RentFlow 是一套面向租車業者的 Multi-Tenant SaaS Platform。

第一階段主要服務：

- 小型租車業者
- 中小型租車門市
- 多門市租車業者
- 需要線上預約能力的租車業者

系統核心目標：

```text
Vehicle Management
        ↓
Availability
        ↓
Reservation
        ↓
Pricing
        ↓
Order
        ↓
Payment
```

平台同時提供：

```text
Customer
    ↓
Customer Web / API

Merchant
    ↓
Merchant Admin

Platform
    ↓
Platform Admin
```

---

# 3. System Goals

## 3.1 Primary Goals

第一階段必須建立：

1. Multi-Tenant SaaS 基礎架構
2. Tenant Data Isolation
3. Vehicle Inventory
4. Reservation
5. Availability Checking
6. Deterministic Pricing
7. Reservation State Machine
8. Order 基礎架構
9. Payment 基礎架構
10. Policy-based Authorization
11. REST API
12. Admin Management

---

## 3.2 Non-Goals

第一階段不實作：

- Microservices
- Event Sourcing
- CQRS
- Domain Event Bus
- 分散式交易
- 即時價格最佳化
- AI Pricing
- 自動收益管理
- Marketplace
- 多租車業者撮合
- 跨租戶車輛共享
- 複雜支付分帳
- FastAPI Integration Layer

如果未來出現實際需求，再透過 ADR 評估是否引入。

---

# 4. System Architecture

第一階段採用：

> Laravel Modular Monolith

系統架構：

```text
┌───────────────────────────────────────────────┐
│                    RentFlow                   │
│                                               │
│  Customer Web                                 │
│  Merchant Admin                               │
│  Platform Admin                               │
│  REST API                                     │
│                                               │
├───────────────────────────────────────────────┤
│              Application Layer                │
├───────────────────────────────────────────────┤
│                Rental Domain                  │
│                                               │
│ Availability │ Pricing │ Reservation          │
│ Order        │ Payment                        │
│                                               │
├───────────────────────────────────────────────┤
│                   Laravel                     │
├───────────────────────┬───────────────────────┤
│        MySQL          │        Redis          │
│  Source of Truth      │ Cache / Queue         │
│  Transactions         │ Coordination           │
└───────────────────────┴───────────────────────┘
```

---

# 5. Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Language | PHP 8.2+ |
| Admin | Filament 5 |
| Database | MySQL |
| Cache | Redis |
| Queue | Redis |
| API Authentication | JWT |
| Authorization | Laravel Policy |
| Role / Permission | Spatie Permission |
| Testing | PHPUnit |
| Runtime | PHP-FPM / Docker |

---

# 6. Architecture Principles

## 6.1 Laravel Owns the Domain

所有 Rental 商業邏輯由 Laravel 負責。

```text
Laravel
    ↓
Rental Domain
    ↓
Business Rules
```

Controller、Filament Resource、API 不直接實作核心商業規則。

---

## 6.2 MySQL Is the Consistency Boundary

MySQL 是商業資料正確性的最終來源。

包含：

- Reservation
- Vehicle Availability
- Order
- Payment
- Pricing Snapshot

Redis 不可成為商業資料的最終 Truth。

---

## 6.3 Redis Is Infrastructure

Redis 用於：

- Cache
- Queue
- Coordination
- Performance optimization

Redis failure 不應直接造成商業資料錯誤。

---

## 6.4 Minimal Abstraction

優先：

```text
Low Context
Low Coupling
Small Diff
Small Regression Scope
```

避免為了架構形式而增加：

- Repository
- DTO
- Helper
- Trait
- Factory
- Interface
- Service Layer
- Event Bus

沒有實際需求時不新增抽象。

---

# 7. Multi-Tenant Architecture

RentFlow 使用：

> Shared Database / Shared Schema

所有 Tenant Business Data 使用 `tenant_id` 隔離。

---

## 7.1 Tenant Structure

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

---

## 7.2 Tenant Isolation Rule

任何 Tenant Business Data Query 都必須有 Tenant Context。

概念：

```text
Current Tenant
      ↓
Business Query
      ↓
tenant_id = current_tenant_id
```

禁止：

```text
Tenant A
    ↓
Query
    ↓
Tenant B Data
```

---

## 7.3 Platform-level Data

部分資料屬於平台層級，不應強制綁定 Tenant。

例如：

- Tenant
- Platform Role
- Platform Configuration
- Holiday

Platform Admin 可以跨 Tenant 管理。

---

# 8. Authentication

系統存在兩種主要使用場景。

## 8.1 Admin Authentication

Filament 使用 Laravel Web Session。

```text
Browser
   ↓
Session
   ↓
Filament
```

---

## 8.2 API Authentication

Customer / Merchant API 使用 JWT。

```text
Client
   ↓
JWT
   ↓
Laravel API
```

Web Session 與 API JWT 不混用。

---

# 9. Authorization

Authorization 架構：

```text
User
 ↓
Spatie Permission
 ↓
Role / Permission
 ↓
Laravel Policy
 ↓
Filament / API
```

---

## 9.1 Responsibility

### Spatie Permission

負責：

- Role
- Permission
- User Role Assignment

### Laravel Policy

負責：

- View
- Create
- Update
- Delete
- Business-level authorization

### Filament

負責：

- Admin UI
- Tenant Context
- Resource Scope

---

# 10. Roles

第一階段角色：

```text
Super Admin
Tenant Admin
Tenant Staff
```

---

## 10.1 Super Admin

平台管理者。

可以：

- 管理 Tenant
- 管理 Platform User
- 管理 Role
- 查看跨 Tenant 資料

Super Admin 可以沒有 `tenant_id`。

---

## 10.2 Tenant Admin

租戶管理者。

可以：

- 查看自己 Tenant
- 管理自己 Tenant Users
- 管理 Vehicles
- 管理 Customers
- 管理 Reservations

不可存取其他 Tenant。

---

## 10.3 Tenant Staff

租戶一般使用者。

可以：

- 查看自己 Tenant 資料
- 執行被授權的營運操作

不可：

- 管理其他 Tenant
- 修改 Platform-level 資料

---

# 11. Core Domain

Rental Domain：

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

---

# 12. Domain Entities

核心 Entities：

```text
Tenant
User
Vehicle
VehiclePricing
Customer
Holiday
Reservation
Order
Payment
```

---

# 13. Tenant

Tenant 代表租車業者。

主要欄位：

```text
id
name
slug
status
created_at
updated_at
```

---

## 13.1 Tenant Status

```text
ACTIVE
INACTIVE
```

Inactive Tenant 不允許進行正常營運操作。

---

# 14. User

User 是平台使用者。

主要欄位：

```text
id
tenant_id nullable
name
email
password
timestamps
```

`tenant_id = null` 可以代表 Platform-level User。

---

# 15. Vehicle

Vehicle 是租車業者的可租賃車輛。

主要欄位：

```text
id
tenant_id
name
plate_number
status
seats
description
timestamps
```

---

## 15.1 Vehicle Status

```text
AVAILABLE
MAINTENANCE
INACTIVE
```

---

## 15.2 Vehicle Uniqueness

同一 Tenant 內：

```text
tenant_id + plate_number
```

必須唯一。

不同 Tenant 可以存在相同車牌資料。

---

# 16. Vehicle Pricing

每台 Vehicle 有自己的基本價格。

```text
tenant_id
vehicle_id
weekday_price
weekend_price
holiday_price
timestamps
```

唯一性：

```text
tenant_id + vehicle_id
```

---

# 17. Customer

Customer 是租車業者的客戶。

```text
tenant_id
name
email
phone
timestamps
```

Customer 屬於單一 Tenant。

---

# 18. Holiday

Holiday 為平台級資料。

```text
date
name
timestamps
```

Holiday 不使用 `tenant_id`。

原因：

同一日期是否為平台認定的 Holiday，第一階段採全平台一致。

---

# 19. Reservation

Reservation 是 Rental Domain 的核心 Entity。

主要欄位：

```text
id
tenant_id
vehicle_id
customer_id
status
start_at
end_at
unit_price
total_days
total_amount
pricing_snapshot
timestamps
```

---

# 20. Reservation Status

狀態：

```text
PENDING
CONFIRMED
PICKED_UP
RETURNED
CANCELLED
```

---

## 20.1 Valid Transitions

```text
PENDING
   ├── CONFIRMED
   └── CANCELLED

CONFIRMED
   ├── PICKED_UP
   └── CANCELLED

PICKED_UP
   └── RETURNED
```

特殊情境下：

```text
PICKED_UP → CANCELLED
```

必須有明確 Domain Rule 才允許。

---

## 20.2 Terminal States

```text
RETURNED
CANCELLED
```

Terminal State 不允許再次變更。

---

# 21. Reservation Time Rule

Reservation 使用：

```text
[start_at, end_at)
```

即 Half-open Interval。

條件：

```text
end_at > start_at
```

---

## 21.1 Conflict Rule

兩筆 Reservation 衝突條件：

```text
existing.start_at < new.end_at
AND
existing.end_at > new.start_at
```

因此：

```text
10:00 ───── 14:00
              14:00 ───── 18:00
```

不衝突。

---

# 22. Minimum Rental Duration

MVP 最低租借時間：

```text
4 hours
```

這是 Domain Rule。

不依賴 Database Constraint。

---

# 23. Pending Reservation

`PENDING` 目前視為 Inventory-occupying Status。

```text
PENDING
CONFIRMED
PICKED_UP
```

都會佔用 Vehicle Inventory。

第一階段不自行新增：

```text
expires_at
```

也不自行設計 PENDING 自動過期機制。

若未來需要，另建立 ADR 定義：

- expiration
- timeout
- payment deadline
- automatic cancellation

---

# 24. Availability

Availability Service 負責判斷：

> Vehicle 在指定時間區間是否可以預約。

概念：

```text
Vehicle
   ↓
Find Occupying Reservations
   ↓
Check Interval Conflict
   ↓
Available / Unavailable
```

---

# 25. Reservation Concurrency

Reservation 建立必須在 Database Transaction 中完成。

概念流程：

```text
BEGIN
  ↓
Lock Vehicle
  ↓
Check Existing Reservations
  ↓
Conflict?
 ├── Yes → Rollback
 └── No
      ↓
Create Reservation
      ↓
COMMIT
```

---

## 25.1 Database Lock

Vehicle 使用：

```text
lockForUpdate()
```

確保同一 Vehicle 的 Reservation Creation 不會在同時交易中繞過 Conflict Check。

---

## 25.2 Redis

Redis 不負責 Reservation Correctness。

Redis 可以協助：

- Reduce contention
- Cache
- Coordination

但：

```text
Redis Lock
```

不能取代：

```text
Database Transaction
+
Database Lock
+
Conflict Check
```

---

# 26. Pricing

PricingService 負責計算 Reservation Price。

輸入：

```text
Vehicle
Start At
End At
Holiday Calendar
Vehicle Pricing
```

輸出：

```text
unit_price
total_days
total_amount
pricing_snapshot
```

---

# 27. Pricing Priority

價格優先順序：

```text
Holiday
   ↓
Weekend
   ↓
Weekday
```

即：

```text
Holiday > Weekend > Weekday
```

---

# 28. Rental Days

MVP 採用：

> 每不足 24 小時的部分計為一天。

例如：

```text
24h       → 1 day
24h + 1m  → 2 days

48h       → 2 days
48h + 1m  → 3 days
```

---

# 29. Price Freeze

Reservation 建立時必須保存：

```text
unit_price
total_days
total_amount
pricing_snapshot
```

建立後：

```text
Vehicle Pricing changed
        ↓
Existing Reservation
        ↓
Price unchanged
```

這確保歷史 Reservation 不受未來 Pricing 修改影響。

---

# 30. Pricing Snapshot

`pricing_snapshot` 保存建立 Reservation 當下的計價資訊。

目的：

- Audit
- Debug
- Reproducibility
- Historical pricing

Snapshot 不應作為未來重新計價的輸入。

---

# 31. Order

Order 為 Reservation 的商業訂單。

主要欄位：

```text
tenant_id
reservation_id
order_number
status
amount
timestamps
```

一筆 Reservation 第一階段最多對應一筆 Order。

---

# 32. Order Status

```text
PENDING
CONFIRMED
CANCELLED
COMPLETED
```

Order 狀態與 Reservation 狀態分離。

兩者不可互相直接取代。

---

# 33. Payment

Payment 屬於 Order。

主要欄位：

```text
tenant_id
order_id
status
amount
paid_at
timestamps
```

---

## 33.1 Payment Status

```text
PENDING
PAID
FAILED
REFUNDED
```

第一階段不強制：

```text
One Order = One Payment
```

除非實際 Payment Flow 有此需求。

---

# 34. Database Design

核心 Tables：

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

Migration 順序：

```text
tenants
   ↓
users
   ↓
vehicles
   ↓
vehicle_pricing
   ↓
customers
   ↓
holidays
   ↓
reservations
   ↓
orders
   ↓
payments
```

---

# 35. Database Constraints

Database 負責基本資料完整性。

例如：

```text
NOT NULL
UNIQUE
FOREIGN KEY
CHECK
INDEX
```

Domain Logic 不全部搬進 Migration。

例如：

```text
Minimum Rental Duration
Reservation State Transition
Pricing Calculation
```

由 Domain Layer 負責。

---

# 36. Delete Policy

核心商業資料不使用 Cascade Delete。

原因：

Reservation、Order、Payment 都具有商業歷史價值。

避免：

```text
Tenant Deleted
   ↓
Reservations Deleted
   ↓
Orders Deleted
   ↓
Payments Deleted
```

造成不可逆的商業資料損失。

第一階段不強制使用 Soft Delete。

---

# 37. Money

所有金額使用：

```text
DECIMAL(12,2)
```

不使用 Floating Point 儲存商業金額。

---

# 38. API Architecture

API Prefix：

```text
/api/v1
```

API 使用 JWT Authentication。

---

# 39. Customer API

預計：

```text
GET    /api/v1/vehicles
GET    /api/v1/vehicles/{vehicle}
GET    /api/v1/vehicles/{vehicle}/availability

POST   /api/v1/reservations
GET    /api/v1/reservations/{reservation}

POST   /api/v1/reservations/{reservation}/cancel
```

---

# 40. Merchant API

預計：

```text
GET    /api/v1/merchant/vehicles
POST   /api/v1/merchant/vehicles
PUT    /api/v1/merchant/vehicles/{vehicle}

GET    /api/v1/merchant/reservations

POST   /api/v1/merchant/reservations/{reservation}/confirm
POST   /api/v1/merchant/reservations/{reservation}/pickup
POST   /api/v1/merchant/reservations/{reservation}/return
```

---

# 41. API Tenant Isolation

API Request 必須取得 Tenant Context。

不可由 Client 任意指定：

```text
tenant_id
```

後直接取得其他 Tenant Data。

Tenant Context 必須經過：

```text
Authenticated User
+
Authorization
+
Tenant Membership
```

驗證。

---

# 42. Admin Architecture

Filament 負責：

```text
Admin UI
Tenant Context
Resource Management
Form
Table
Authorization Integration
```

Laravel Policy 負責：

```text
Authorization Decision
```

Rental Domain 負責：

```text
Business Rule
```

三者責任不可混合。

---

# 43. Resource Scope

Platform-level Resource：

```text
TenantResource
RoleResource
```

不應套用 Tenant Ownership。

Tenant-level Resource：

```text
UserResource
VehicleResource
CustomerResource
ReservationResource
OrderResource
PaymentResource
```

依 Tenant Context 進行資料範圍限制。

---

# 44. User Resource

UserResource 特別處理：

```text
Super Admin
    ↓
All Tenant Users

Tenant Admin
    ↓
Current Tenant Users

Tenant Staff
    ↓
Current Tenant Users
```

Tenant Scope 與 Policy 分工：

```text
Tenant Scope
    ↓
決定資料範圍

Policy
    ↓
決定是否允許操作
```

兩者不可互相取代。

---

# 45. Error Handling

Domain Error 應使用明確 Exception。

例如：

```text
ReservationConflictException
InvalidReservationStateException
TenantAccessDeniedException
InvalidRentalPeriodException
```

API Layer 再將 Domain Exception 轉換成適當 HTTP Response。

---

# 46. Testing Strategy

測試以 Business Invariants 為核心。

---

## 46.1 Tenant Isolation

驗證：

```text
Tenant A
    ↓
Cannot Read Tenant B

Tenant A
    ↓
Cannot Update Tenant B

Tenant A
    ↓
Cannot Delete Tenant B
```

---

## 46.2 Reservation Conflict

同一 Vehicle：

```text
Request A
10:00 - 14:00

Request B
12:00 - 16:00
```

第二筆必須被拒絕。

---

## 46.3 Boundary

```text
10:00 - 14:00
14:00 - 18:00
```

必須允許。

---

## 46.4 Concurrent Reservation

同一 Vehicle 同時收到多筆 Reservation Request。

預期：

```text
At most one valid Reservation
```

其餘 Request 必須收到 Conflict。

---

## 46.5 State Transition

測試：

```text
PENDING → CONFIRMED
CONFIRMED → PICKED_UP
PICKED_UP → RETURNED
```

以及非法：

```text
PENDING → RETURNED
RETURNED → CONFIRMED
CANCELLED → PICKED_UP
```

---

## 46.6 Price Freeze

建立 Reservation。

修改 Vehicle Pricing。

確認：

```text
Existing Reservation
total_amount
```

保持不變。

---

# 47. Observability

第一階段至少需要能追蹤：

- Request
- Reservation
- Order
- Payment
- Exception
- Queue Job

重要 Domain 操作應保留足夠資訊供 Debug。

不為了 Logging 而建立複雜 Logging Framework。

---

# 48. Queue

Queue 使用 Redis。

適合：

- Notification
- Email
- Non-critical background processing
- Future integration jobs

不應將核心 Reservation Transaction 拆成：

```text
Create Reservation
    ↓
Queue
    ↓
Eventually Update Inventory
```

Reservation 的核心一致性必須在同步 Transaction 內完成。

---

# 49. Cache

Cache 可以用於：

- Vehicle listing
- Pricing lookup
- Holiday lookup
- Dashboard statistics

但任何 Cache 都必須能被視為：

```text
Disposable
```

Cache 遺失不應造成資料錯誤。

---

# 50. Security Principles

核心原則：

1. Tenant isolation must be enforced server-side.
2. Client-provided tenant ID cannot be trusted.
3. Authorization must be checked before mutation.
4. Passwords must be hashed.
5. API authentication uses JWT.
6. Admin authentication uses session.
7. Sensitive configuration must use environment variables.
8. Production must not use development credentials.

---

# 51. Development Data

Development environment 可以提供：

```text
Tenants:
- 台北租車
- 台中租車
- 高雄租車

Roles:
- Super Admin
- Tenant Admin
- Tenant Staff
```

Seed data 必須：

- Deterministic
- Repeatable
- Safe for Development

不可依賴 Production Data。

---

# 52. Documentation Structure

文件分工：

```text
README.md
    ↓
Project Overview

docs/
├── architecture.md
│   ↓
│   System Architecture
│
├── adr/
│   ↓
│   Architecture Decisions
│
├── sdd/
│   ↓
│   Detailed System Design
│
└── operations.md
    ↓
    Operations / Troubleshooting
```

README 不負責承載所有實作細節。

---

# 53. Architecture Decision Records

以下議題應建立 ADR：

```text
ADR-001 Modular Monolith
ADR-002 Shared Database Multi-Tenancy
ADR-003 MySQL as Consistency Boundary
ADR-004 Reservation Concurrency Strategy
ADR-005 Pricing Snapshot
ADR-006 Policy-based Authorization
ADR-007 Redis Usage Boundary
```

每個 ADR 至少包含：

```text
Context
Decision
Alternatives
Trade-offs
Consequences
```

---

# 54. Implementation Order

第一階段建議按照：

```text
1. Platform Foundation
        ↓
2. Tenant / User / Role
        ↓
3. Vehicle
        ↓
4. Vehicle Pricing
        ↓
5. Customer
        ↓
6. AvailabilityService
        ↓
7. PricingService
        ↓
8. ReservationService
        ↓
9. Reservation State Machine
        ↓
10. Order
        ↓
11. Payment
        ↓
12. API
        ↓
13. Feature Tests
        ↓
14. Admin UI
```

---

# 55. Acceptance Criteria

## Tenant

- Tenant 可建立
- Tenant Code / Slug 唯一
- Tenant Status 有效
- Tenant Data 不可跨租戶存取

## User

- User 屬於 Tenant
- Super Admin 可管理平台使用者
- Tenant User 只能看到自己 Tenant
- Policy 阻止跨 Tenant 操作

## Vehicle

- Vehicle 屬於 Tenant
- 同一 Tenant 車牌不可重複
- Vehicle Status 有效

## Reservation

- Vehicle 不可發生時間衝突
- Boundary 不視為衝突
- Invalid State Transition 必須拒絕
- Cancellation 可以釋放 Inventory
- Pricing 必須 Freeze
- Concurrent Request 不得產生重複 Reservation

## Pricing

- Holiday 優先
- Weekend 次之
- Weekday 最後
- 不足 24 小時進位一天
- Reservation 建立後價格不變

## Authorization

- Super Admin 可以跨 Tenant
- Tenant Admin 只能操作自己的 Tenant
- Tenant Staff 只能存取授權資料
- Cross-Tenant Mutation 必須被拒絕

---

# 56. Future Expansion

未來可能加入：

```text
Rental SaaS
    ↓
Marketplace
    ↓
Payment
    ↓
Settlement
    ↓
Revenue Management
    ↓
AI Pricing
    ↓
AI Mobility Platform
```

但任何新架構都必須由實際需求驅動。

不因未來可能需要而提前實作。

---

# 57. Final Design Principle

RentFlow 的核心設計原則：

> **先保證商業規則正確，再追求系統擴展性。**

因此：

```text
MySQL
    ↓
Consistency

Domain
    ↓
Business Rules

Policy
    ↓
Authorization

Filament
    ↓
Admin Experience

Redis
    ↓
Infrastructure Support
```

每個元件只負責自己應該負責的事情。

系統優先追求：

```text
Correctness
   ↓
Clarity
   ↓
Maintainability
   ↓
Performance
   ↓
Scalability
```

而不是為了展示技術而增加不必要的複雜度。