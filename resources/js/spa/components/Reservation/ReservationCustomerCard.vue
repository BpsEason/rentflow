<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div
                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center"
            >
                <svg
                    class="w-4 h-4 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    ></path>
                </svg>
            </div>
            <h2
                class="text-sm font-semibold text-gray-400 uppercase tracking-wider"
            >
                聯絡人資訊
            </h2>
        </div>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
            <div class="flex flex-col">
                <dt
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2"
                >
                    姓名
                </dt>
                <dd class="text-xl font-bold text-gray-900">
                    {{ customerName }}
                </dd>
            </div>
            <div class="flex flex-col">
                <dt
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2"
                >
                    手機號碼
                </dt>
                <dd
                    class="flex items-center gap-2 text-base font-semibold text-gray-900"
                >
                    <svg
                        class="w-4 h-4 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 012-2h-1C9.716 21 3 14.284 3 6V5z"
                        ></path>
                    </svg>
                    {{ customerPhone }}
                </dd>
            </div>
            <div class="flex flex-col">
                <dt
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2"
                >
                    電子郵件
                </dt>
                <dd
                    class="flex items-center gap-2 text-base font-medium text-gray-900 break-all"
                >
                    <svg
                        class="w-4 h-4 text-gray-400 flex-shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                        ></path>
                    </svg>
                    {{ customerEmail }}
                </dd>
            </div>
            <div class="flex flex-col" v-if="customerIdNumber">
                <dt
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2"
                >
                    身份證字號
                </dt>
                <dd class="text-base font-medium text-gray-900 font-mono">
                    *******{{ customerIdNumber.slice(-4) }}
                </dd>
            </div>
            <div class="flex flex-col" v-if="licenseNumber">
                <dt
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2"
                >
                    駕照號碼
                </dt>
                <dd class="text-base font-medium text-gray-900 font-mono">
                    {{ licenseNumber }}
                </dd>
            </div>
            <div class="flex flex-col" v-if="address">
                <dt
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2"
                >
                    通訊地址
                </dt>
                <dd class="text-base font-medium text-gray-900">
                    {{ address }}
                </dd>
            </div>
        </dl>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    customerName: string;
    customerPhone: string;
    customerEmail: string;
    customerIdNumber?: string;
    licenseNumber?: string;
    address?: string;
}>();
</script>
