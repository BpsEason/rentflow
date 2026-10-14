<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2
            class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4"
        >
            車輛資訊
        </h2>
        <div class="flex flex-col md:flex-row gap-6">
            <!-- 左側：車輛圖片 -->
            <div class="w-full md:w-72 flex-shrink-0">
                <img
                    :src="vehicle?.image_url"
                    :alt="vehicle?.name"
                    class="w-full h-48 object-cover rounded-xl bg-gray-100"
                    @error="
                        (e) =>
                            (e.target.src =
                                'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&h=400&fit=crop')
                    "
                />
            </div>
            <!-- 右側：車款名稱與規格 -->
            <div class="flex-1">
                <!-- 車款名稱區塊 -->
                <div>
                    <div
                        class="flex flex-wrap items-start justify-between gap-3 mb-2"
                    >
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ vehicle?.name }}
                        </h3>
                        <!-- 車牌號碼如果存在 -->
                        <span
                            v-if="vehicle?.license_plate"
                            class="inline-flex items-center px-3 py-1 bg-gray-900 text-white rounded-lg font-mono font-bold text-sm"
                        >
                            {{ vehicle?.license_plate }}
                        </span>
                    </div>
                    <p class="text-gray-500">
                        {{ vehicle?.brand }} {{ vehicle?.model }}
                    </p>
                </div>
                <!-- 規格網格區塊 -->
                <div class="grid grid-cols-2 gap-3 mt-6">
                    <div
                        class="bg-gray-50 rounded-lg p-4 min-w-0 border border-gray-100"
                    >
                        <div class="flex items-center gap-2 mb-2">
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
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    ></path>
                                </svg>
                            </div>
                            <p
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                            >
                                乘客數
                            </p>
                        </div>
                        <p class="text-lg font-bold text-gray-900">
                            {{ vehicle?.seats || 5 }}
                            <span class="text-xs font-normal text-gray-500 ml-1"
                                >人</span
                            >
                        </p>
                    </div>
                    <div
                        class="bg-gray-50 rounded-lg p-4 min-w-0 border border-gray-100"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 text-emerald-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756-2.924 3.35 0a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    ></path>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    ></path>
                                </svg>
                            </div>
                            <p
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                            >
                                變速箱
                            </p>
                        </div>
                        <p class="text-lg font-bold text-gray-900">
                            {{ vehicle?.transmission || "自排" }}
                        </p>
                    </div>
                    <div
                        class="bg-gray-50 rounded-lg p-4 min-w-0 border border-gray-100"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 text-amber-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"
                                    ></path>
                                </svg>
                            </div>
                            <p
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                            >
                                燃油類型
                            </p>
                        </div>
                        <p class="text-lg font-bold text-gray-900">
                            {{ vehicle?.fuel_type || "汽油" }}
                        </p>
                    </div>
                    <div
                        class="bg-gray-50 rounded-lg p-4 min-w-0 border border-gray-100"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 text-violet-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    ></path>
                                </svg>
                            </div>
                            <p
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                            >
                                行李箱
                            </p>
                        </div>
                        <p class="text-lg font-bold text-gray-900">
                            {{ vehicle?.luggage || 3 }}
                            <span class="text-xs font-normal text-gray-500 ml-1"
                                >件</span
                            >
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Vehicle {
    image_url?: string;
    name?: string;
    brand?: string;
    model?: string;
    seats?: number;
    transmission?: string;
    fuel_type?: string;
    luggage?: number;
    license_plate?: string;
}

const props = defineProps<{
    vehicle: Vehicle | null;
}>();
</script>
