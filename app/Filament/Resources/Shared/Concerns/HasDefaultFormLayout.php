<?php

namespace App\Filament\Resources\Shared\Concerns;

use Filament\Schemas\Schema;

trait HasDefaultFormLayout
{
    /**
     * 套用全域預設的表單佈局設定
     * 手機版：單欄 (default=1)
     * 桌面版：12欄格線系統 (lg=12)
     */
    public static function applyDefaultLayout(Schema $schema): Schema
    {
        return $schema->columns([
            'default' => 1,
            'lg' => 12,
        ]);
    }
}
