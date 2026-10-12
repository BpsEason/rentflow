<?php

use Illuminate\Support\Facades\Route;

// SPA 應用程式 - 捕獲所有非 /admin 和 /api 的路徑
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '^(?!admin|api).*$');