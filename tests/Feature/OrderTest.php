<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_from_confirmed_reservation()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant, ['role' => 'admin']);

        $reservation = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'amount' => '5000.00',
        ]);

        $this->actingAs($user);

        $order = Order::createFromReservation($reservation);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('5000.00', $order->total_amount);
        $this->assertEquals($reservation->id, $order->reservation_id);

        // 驗證資料庫持久化
        $this->assertDatabaseHas('orders', [
            'tenant_id' => $tenant->id,
            'reservation_id' => $reservation->id,
            'total_amount' => '5000.00',
            'order_number' => $order->order_number,
        ]);
    }

    public function test_order_amount_snapshot_is_preserved()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant, ['role' => 'admin']);

        $reservation = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'amount' => '5000.00',
        ]);

        $this->actingAs($user);
        $order = Order::createFromReservation($reservation);

        // 修改預約金額
        $reservation->update(['amount' => '6000.00']);
        $order->refresh();

        // 訂單金額應該保持不變（Order 負責從 Reservation 取得當下金額並快照）
        $this->assertSame('5000.00', $order->total_amount);
    }

    public function test_cannot_create_order_from_another_tenants_reservation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot create order from a reservation belonging to another tenant.');

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create();
        $userA->tenants()->attach($tenantA, ['role' => 'admin']);

        $reservationB = Reservation::factory()->create([
            'tenant_id' => $tenantB->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $this->actingAs($userA);
        Order::createFromReservation($reservationB);
    }

    public function test_cannot_create_duplicate_order_for_same_reservation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Order already exists for this reservation.');

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant, ['role' => 'admin']);

        $reservation = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $this->actingAs($user);
        Order::createFromReservation($reservation);

        // 確認資料庫只有一筆訂單
        $this->assertDatabaseCount('orders', 1);

        // 第二次建立會失敗
        Order::createFromReservation($reservation);
    }

    public function test_cannot_create_order_from_unconfirmed_reservation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot create order from an unconfirmed reservation.');

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant, ['role' => 'admin']);

        $reservation = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($user);
        Order::createFromReservation($reservation);
    }

    public function test_order_policy_prevents_cross_tenant_access()
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create();
        $userA->tenants()->attach($tenantA, ['role' => 'admin']);

        $userB = User::factory()->create();
        $userB->tenants()->attach($tenantB, ['role' => 'admin']);

        // Tenant A 建立訂單
        $reservationA = Reservation::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $this->actingAs($userA);
        $orderA = Order::createFromReservation($reservationA);

        // Tenant B 無法存取 Tenant A 的訂單（OrderPolicy 驗證）
        $orderPolicy = new \App\Policies\OrderPolicy();
        $this->assertFalse($orderPolicy->view($userB, $orderA));
    }

    public function test_order_number_is_unique()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant, ['role' => 'admin']);

        $this->actingAs($user);

        $reservation1 = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);
        $order1 = Order::createFromReservation($reservation1);

        $reservation2 = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);
        $order2 = Order::createFromReservation($reservation2);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    public function test_database_enforces_order_number_uniqueness()
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant, ['role' => 'admin']);

        $reservation1 = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);
        $reservation2 = Reservation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $this->actingAs($user);
        $order1 = Order::createFromReservation($reservation1);

        // 手動建立第二個訂單使用相同的 order_number 來觸發 DB 約束
        Order::create([
            'tenant_id' => $tenant->id,
            'reservation_id' => $reservation2->id,
            'order_number' => $order1->order_number,
            'status' => Order::STATUS_PENDING,
            'total_amount' => '5000.00',
        ]);
    }
}
