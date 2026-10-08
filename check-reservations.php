<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Models\Reservation;

echo "=== 預約狀態統計 ===\n";

// 查詢所有狀態的分組
$reservations = Reservation::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->get();

foreach ($reservations as $r) {
    echo $r->status . ': ' . $r->count . "\n";
}

echo "\n總預約數: " . Reservation::count() . "\n";

// 列出所有預約的狀態
echo "\n=== 所有預約狀態列表 ===\n";
$allReservations = Reservation::all(['id', 'status', 'tenant_id']);
foreach ($allReservations as $r) {
    echo "ID: {$r->id}, Status: {$r->status}, Tenant: {$r->tenant_id}\n";
}
