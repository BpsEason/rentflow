<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Domain\Rental\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customersTemplate = [
            // ========== 活躍客戶 ==========
            ['name' => '陳志明', 'email' => 'chen.zhiming',   'phone' => '0912-345-678', 'is_active' => true],
            ['name' => '林美玲', 'email' => 'lin.meiling',    'phone' => '0923-456-789', 'is_active' => true],
            ['name' => '黃建宏', 'email' => 'huang.jianhong', 'phone' => '0934-567-890', 'is_active' => true],
            ['name' => '張雅婷', 'email' => 'chang.yating',   'phone' => '0945-678-901', 'is_active' => true],
            ['name' => '劉偉仁', 'email' => 'liu.weiren',     'phone' => '0956-789-012', 'is_active' => true],
            ['name' => '楊淑華', 'email' => 'yang.shuhua',    'phone' => '0967-890-123', 'is_active' => true],
            ['name' => '蔡佳蓉', 'email' => 'tsai.jiarong',   'phone' => '0978-901-234', 'is_active' => true],
            ['name' => '王柏翰', 'email' => 'wang.bohan',     'phone' => '0911-223-344', 'is_active' => true],
            ['name' => '李佳穎', 'email' => 'li.jiaying',     'phone' => '0922-334-455', 'is_active' => true],
            ['name' => '許志豪', 'email' => 'hsu.zhihao',     'phone' => '0933-445-566', 'is_active' => true],
            ['name' => '吳佩蓉', 'email' => 'wu.peirong',     'phone' => '0944-556-677', 'is_active' => true],
            ['name' => '鄭宇軒', 'email' => 'cheng.yuxuan',   'phone' => '0955-667-788', 'is_active' => true],
            ['name' => '謝雅雯', 'email' => 'hsieh.yawen',    'phone' => '0966-778-899', 'is_active' => true],
            ['name' => '羅俊傑', 'email' => 'lo.junjie',      'phone' => '0977-889-900', 'is_active' => true],
            ['name' => '徐欣怡', 'email' => 'hsu.xinyi',      'phone' => '0988-990-011', 'is_active' => true],
            ['name' => '葉宗翰', 'email' => 'yeh.zonghan',    'phone' => '0910-112-233', 'is_active' => true],
            ['name' => '蘇怡君', 'email' => 'su.yijun',       'phone' => '0921-223-344', 'is_active' => true],
            ['name' => '呂建志', 'email' => 'lu.jianzhi',     'phone' => '0932-334-455', 'is_active' => true],
            ['name' => '江佩玲', 'email' => 'chiang.peiling', 'phone' => '0943-445-566', 'is_active' => true],
            ['name' => '何承恩', 'email' => 'ho.chengen',     'phone' => '0954-556-677', 'is_active' => true],

            // ========== 停用客戶 ==========
            ['name' => '吳俊傑', 'email' => 'wu.junjie',      'phone' => '0989-012-345', 'is_active' => false],
            ['name' => '周冠宇', 'email' => 'chou.guanyu',    'phone' => '0918-765-432', 'is_active' => false],
            ['name' => '沈佳慧', 'email' => 'shen.jiahui',    'phone' => '0927-654-321', 'is_active' => false],
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($customersTemplate as $index => $customerData) {
                // 產生看起來真實且跨租戶唯一的手機號碼
                $basePhone = str_replace('-', '', $customerData['phone']);
                $uniquePhone = substr($basePhone, 0, 4) . str_pad(
                    (string) ($tenant->id * 100 + $index),
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                $uniqueEmail = $customerData['email'] . '.t' . $tenant->id . '@example.com';

                Customer::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'phone'     => $uniquePhone,
                    ],
                    [
                        'name'      => $customerData['name'],
                        'email'     => $uniqueEmail,
                        'is_active' => $customerData['is_active'],
                    ]
                );
            }
        }
    }
}
