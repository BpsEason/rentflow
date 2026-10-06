<?php

namespace App\Filament\Resources\Roles\Schemas;

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
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('名稱')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('guard_name')
                                    ->label('守衛名稱')
                                    ->required()
                                    ->default('web')
                                    ->maxLength(255),
                                static::getSelectAllFormComponent(),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }
}
