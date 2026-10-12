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
            [
                'name' => '陳志明',
                'email' => 'chen.zhiming',
                'phone' => '0912-345-678',
                'is_active' => true,
                'id_number' => 'A123456789',
                'license_number' => 'AA12345678',
                'license_expiry' => '2030-12-31',
                'address' => '台北市中山區南京東路一段1號',
                'emergency_contact_name' => '陳小明',
                'emergency_contact_phone' => '0911-111-111',
            ],
            [
                'name' => '林美玲',
                'email' => 'lin.meiling',
                'phone' => '0923-456-789',
                'is_active' => true,
                'id_number' => 'B234567890',
                'license_number' => 'BB23456789',
                'license_expiry' => '2029-06-30',
                'address' => '新北市板橋區文化路二段2號',
                'emergency_contact_name' => '林小芳',
                'emergency_contact_phone' => '0922-222-222',
            ],
            [
                'name' => '黃建宏',
                'email' => 'huang.jianhong',
                'phone' => '0934-567-890',
                'is_active' => true,
                'id_number' => 'C345678901',
                'license_number' => 'CC34567890',
                'license_expiry' => '2031-03-15',
                'address' => '台中市西區台灣大道三段3號',
                'emergency_contact_name' => '黃小華',
                'emergency_contact_phone' => '0933-333-333',
            ],
            [
                'name' => '張雅婷',
                'email' => 'chang.yating',
                'phone' => '0945-678-901',
                'is_active' => true,
                'id_number' => 'D456789012',
                'license_number' => 'DD45678901',
                'license_expiry' => '2028-09-20',
                'address' => '高雄市苓雅區中正一路4號',
                'emergency_contact_name' => '張小玲',
                'emergency_contact_phone' => '0944-444-444',
            ],
            [
                'name' => '劉偉仁',
                'email' => 'liu.weiren',
                'phone' => '0956-789-012',
                'is_active' => true,
                'id_number' => 'E567890123',
                'license_number' => 'EE56789012',
                'license_expiry' => '2032-01-10',
                'address' => '桃園市桃園區中正路5號',
                'emergency_contact_name' => '劉小偉',
                'emergency_contact_phone' => '0955-555-555',
            ],
            [
                'name' => '楊淑華',
                'email' => 'yang.shuhua',
                'phone' => '0967-890-123',
                'is_active' => true,
                'id_number' => 'F678901234',
                'license_number' => 'FF67890123',
                'license_expiry' => '2030-05-25',
                'address' => '台南市北區成功路6號',
                'emergency_contact_name' => '楊小美',
                'emergency_contact_phone' => '0966-666-666',
            ],
            [
                'name' => '蔡佳蓉',
                'email' => 'tsai.jiarong',
                'phone' => '0978-901-234',
                'is_active' => true,
                'id_number' => 'G789012345',
                'license_number' => 'GG78901234',
                'license_expiry' => '2029-11-05',
                'address' => '基隆市中正區中正路7號',
                'emergency_contact_name' => '蔡小蓉',
                'emergency_contact_phone' => '0977-777-777',
            ],
            [
                'name' => '王柏翰',
                'email' => 'wang.bohan',
                'phone' => '0911-223-344',
                'is_active' => true,
                'id_number' => 'H890123456',
                'license_number' => 'HH89012345',
                'license_expiry' => '2031-08-18',
                'address' => '新竹市東區光復路一段8號',
                'emergency_contact_name' => '王小柏',
                'emergency_contact_phone' => '0988-888-888',
            ],
            [
                'name' => '李佳穎',
                'email' => 'li.jiaying',
                'phone' => '0922-334-455',
                'is_active' => true,
                'id_number' => 'I901234567',
                'license_number' => 'II90123456',
                'license_expiry' => '2028-02-28',
                'address' => '嘉義市西區民生北路9號',
                'emergency_contact_name' => '李小穎',
                'emergency_contact_phone' => '0999-999-999',
            ],
            [
                'name' => '許志豪',
                'email' => 'hsu.zhihao',
                'phone' => '0933-445-566',
                'is_active' => true,
                'id_number' => 'J012345678',
                'license_number' => null, // 無駕照測試案例
                'license_expiry' => null,
                'address' => '屏東市屏東市民族路10號',
                'emergency_contact_name' => '許小志',
                'emergency_contact_phone' => '0910-000-000',
            ],
            [
                'name' => '吳佩蓉',
                'email' => 'wu.peirong',
                'phone' => '0944-556-677',
                'is_active' => true,
                'id_number' => null, // 身分證未提供測試案例
                'license_number' => 'JJ01234567',
                'license_expiry' => '2030-07-12',
                'address' => '宜蘭縣宜蘭市縣政大道11號',
                'emergency_contact_name' => null, // 無緊急聯絡人測試案例
                'emergency_contact_phone' => null,
            ],
            [
                'name' => '鄭宇軒',
                'email' => 'cheng.yuxuan',
                'phone' => '0955-667-788',
                'is_active' => true,
                'id_number' => 'K123456789',
                'license_number' => 'KK12345678',
                'license_expiry' => '2033-04-30',
                'address' => '花蓮市花蓮市國聯一路12號',
                'emergency_contact_name' => '鄭小宇',
                'emergency_contact_phone' => '0920-111-222',
            ],
            [
                'name' => '謝雅雯',
                'email' => 'hsieh.yawen',
                'phone' => '0966-778-899',
                'is_active' => true,
                'id_number' => 'L234567890',
                'license_number' => 'LL23456789',
                'license_expiry' => '2029-10-15',
                'address' => '台東市台東市中華路一段13號',
                'emergency_contact_name' => '謝小雅',
                'emergency_contact_phone' => '0930-222-333',
            ],
            [
                'name' => '羅俊傑',
                'email' => 'lo.junjie',
                'phone' => '0977-889-900',
                'is_active' => true,
                'id_number' => 'M345678901',
                'license_number' => 'MM34567890',
                'license_expiry' => '2031-06-20',
                'address' => '澎湖縣馬公市中正路14號',
                'emergency_contact_name' => '羅小俊',
                'emergency_contact_phone' => '0940-333-444',
            ],
            [
                'name' => '徐欣怡',
                'email' => 'hsu.xinyi',
                'phone' => '0988-990-011',
                'is_active' => true,
                'id_number' => 'N456789012',
                'license_number' => 'NN45678901',
                'license_expiry' => '2028-12-01',
                'address' => '金門縣金城鎮民生路15號',
                'emergency_contact_name' => '徐小欣',
                'emergency_contact_phone' => '0950-444-555',
            ],
            [
                'name' => '葉宗翰',
                'email' => 'yeh.zonghan',
                'phone' => '0910-112-233',
                'is_active' => true,
                'id_number' => 'O567890123',
                'license_number' => 'OO56789012',
                'license_expiry' => '2032-09-10',
                'address' => '連江縣南竿鄉復興村16號',
                'emergency_contact_name' => '葉小宗',
                'emergency_contact_phone' => '0960-555-666',
            ],
            [
                'name' => '蘇怡君',
                'email' => 'su.yijun',
                'phone' => '0921-223-344',
                'is_active' => true,
                'id_number' => null,
                'license_number' => null,
                'license_expiry' => null,
                'address' => '彰化市彰化市中山路一段17號',
                'emergency_contact_name' => '蘇小怡',
                'emergency_contact_phone' => '0970-666-777',
            ],
            [
                'name' => '呂建志',
                'email' => 'lu.jianzhi',
                'phone' => '0932-334-455',
                'is_active' => true,
                'id_number' => 'P678901234',
                'license_number' => 'PP67890123',
                'license_expiry' => '2030-03-05',
                'address' => '南投縣南投市中興路18號',
                'emergency_contact_name' => '呂小建',
                'emergency_contact_phone' => '0980-777-888',
            ],
            [
                'name' => '江佩玲',
                'email' => 'chiang.peiling',
                'phone' => '0943-445-566',
                'is_active' => true,
                'id_number' => 'Q789012345',
                'license_number' => 'QQ78901234',
                'license_expiry' => '2029-08-22',
                'address' => '雲林縣斗六市文化路19號',
                'emergency_contact_name' => '江小佩',
                'emergency_contact_phone' => '0912-888-999',
            ],
            [
                'name' => '何承恩',
                'email' => 'ho.chengen',
                'phone' => '0954-556-677',
                'is_active' => true,
                'id_number' => 'R890123456',
                'license_number' => 'RR89012345',
                'license_expiry' => '2031-11-28',
                'address' => '嘉義縣太保市太子大道20號',
                'emergency_contact_name' => '何小恩',
                'emergency_contact_phone' => '0923-999-000',
            ],

            // ========== 停用客戶 ==========
            [
                'name' => '吳俊傑',
                'email' => 'wu.junjie',
                'phone' => '0989-012-345',
                'is_active' => false,
                'id_number' => 'S901234567',
                'license_number' => 'SS90123456',
                'license_expiry' => '2027-05-15',
                'address' => '台北市大同區民生西路21號',
                'emergency_contact_name' => '吳小杰',
                'emergency_contact_phone' => '0934-123-456',
            ],
            [
                'name' => '周冠宇',
                'email' => 'chou.guanyu',
                'phone' => '0918-765-432',
                'is_active' => false,
                'id_number' => 'T012345678',
                'license_number' => null,
                'license_expiry' => null,
                'address' => '新北市新店區北新路二段22號',
                'emergency_contact_name' => '周小冠',
                'emergency_contact_phone' => '0945-234-567',
            ],
            [
                'name' => '沈佳慧',
                'email' => 'shen.jiahui',
                'phone' => '0927-654-321',
                'is_active' => false,
                'id_number' => null,
                'license_number' => null,
                'license_expiry' => null,
                'address' => null,
                'emergency_contact_name' => null,
                'emergency_contact_phone' => null,
            ],
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

                // 為身分證字號、駕照號碼加入租戶唯一識別，避免跨租戶重複
                $uniqueIdNumber = isset($customerData['id_number']) ? $customerData['id_number'] . $tenant->id : null;
                $uniqueLicenseNumber = isset($customerData['license_number']) ? $customerData['license_number'] . $tenant->id : null;
                $uniqueEmergencyPhone = isset($customerData['emergency_contact_phone'])
                    ? substr(str_replace('-', '', $customerData['emergency_contact_phone']), 0, 4) . str_pad((string)($tenant->id * 100 + $index), 6, '0', STR_PAD_LEFT)
                    : null;

                Customer::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'phone'     => $uniquePhone,
                    ],
                    [
                        'name'      => $customerData['name'],
                        'email'     => $uniqueEmail,
                        'is_active' => $customerData['is_active'],
                        'id_number' => $uniqueIdNumber,
                        'license_number' => $uniqueLicenseNumber,
                        'license_expiry' => $customerData['license_expiry'],
                        'address' => $customerData['address'],
                        'emergency_contact_name' => $customerData['emergency_contact_name'],
                        'emergency_contact_phone' => $uniqueEmergencyPhone,
                    ]
                );
            }
        }
    }
}
