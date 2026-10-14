<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Filament\Resources\Shared\Concerns\HasDefaultFormLayout;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use App\Models\Tenant;

class BranchForm
{
    use HasDefaultFormLayout;

    public static function configure(Schema $schema): Schema
    {
        return static::applyDefaultLayout($schema)
            ->components([
                Section::make('據點基本資料')
                    ->description('管理租戶的營業據點資訊')
                    ->icon('heroicon-o-map-pin')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 12,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 4,
                    ])
                    ->schema([
                        // 只有超級管理員可以選擇租戶
                        Select::make('tenant_id')
                            ->label('所屬租戶')
                            ->options(fn() => Tenant::pluck('name', 'id'))
                            ->required()
                            ->visible(fn() => auth()->user()?->hasRole('Super Admin'))
                            ->placeholder('選擇所屬租戶'),

                        TextInput::make('name')
                            ->label('據點名稱')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                $tenant = filament()->getTenant();
                                if ($tenant) {
                                    return $rule->where('tenant_id', $tenant->id);
                                }
                                return $rule;
                            })
                            ->placeholder('輸入據點名稱'),

                        TextInput::make('phone')
                            ->label('聯絡電話')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('輸入聯絡電話'),

                        Textarea::make('address')
                            ->label('地址')
                            ->required()
                            ->rows(3)
                            ->placeholder('輸入據點地址')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        Toggle::make('is_active')
                            ->label('是否啟用')
                            ->default(true)
                            ->helperText('停用後此據點無法用於新的預約'),
                    ]),
            ]);
    }
}
