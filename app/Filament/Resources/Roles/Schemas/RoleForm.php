<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RoleForm
{
    use HasShieldFormComponents;
    use HasDefaultFormLayout;

    /**
     * Override the parent's setPermissionStateForRecordPermissions to directly check permissions relationship
     * This fixes the issue where checkPermissionTo() might not correctly detect role permissions
     */
    public static function setPermissionStateForRecordPermissions(Component $component, string $operation, array $permissions, ?Model $record): void
    {
        if (in_array($operation, ['edit', 'view'], true)) {
            if (blank($record)) {
                return;
            }

            if ($component->isVisible() && $permissions !== []) {
                // Get all permission names directly from the relationship
                $rolePermissions = $record->loadMissing('permissions')->permissions->pluck('name')->toArray();

                $component->state(
                    collect($permissions)
                        ->filter(fn($value, $key) => in_array($key, $rolePermissions))
                        ->keys()
                        ->toArray()
                );
            }
        }
    }

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('角色基本資料')
                    ->description('建立系統角色並設定基本識別資訊，角色用於群組化權限設定')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 12,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('角色名稱')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('輸入角色名稱，例如：編輯、管理員'),
                        TextInput::make('guard_name')
                            ->label('守衛名稱')
                            ->required()
                            ->default('web')
                            ->maxLength(255)
                            ->placeholder('web'),
                        static::getSelectAllFormComponent()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ]),
                static::getShieldFormComponents(),
            ]);
    }
}
