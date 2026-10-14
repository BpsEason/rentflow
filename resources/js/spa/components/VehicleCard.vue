<template>
    <div
        :class="[
            'group bg-white rounded-3xl shadow-xl shadow-gray-200/60 overflow-hidden transition-all duration-500 ease-out hover:-translate-y-3 cursor-pointer ring-1 ring-gray-100/60',
            vehicle.is_featured
                ? 'lg:col-span-2 shadow-2xl shadow-blue-100/80 ring-2 ring-blue-200/60'
                : 'hover:shadow-2xl hover:shadow-gray-300/70',
        ]"
    >
        <!-- Vehicle Image - 優化圖片區質感 -->
        <div class="relative h-56 md:h-64 bg-gray-200 overflow-hidden">
            <div
                class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent z-10"
            ></div>
            <img
                :src="
                    vehicle.image_url ||
                    'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800'
                "
                :alt="vehicle.name"
                class="w-full h-full object-cover transition-all duration-700 ease-out group-hover:scale-105 group-hover:brightness-105"
                loading="lazy"
            />
            <!-- 狀態標籤 - 更精緻的現代設計 -->
            <div class="absolute top-4 left-4 z-20 flex flex-col gap-2">
                <span
                    v-if="vehicle.is_featured"
                    class="inline-flex items-center bg-gradient-to-r from-amber-400 to-orange-500 text-white text-xs px-4 py-1.5 rounded-full font-semibold shadow-lg backdrop-blur-sm"
                >
                    ⭐ 熱門推薦
                </span>
                <span
                    v-if="
                        vehicle.status === 'AVAILABLE' &&
                        !vehicle.maintenance_start_at
                    "
                    class="inline-flex items-center bg-emerald-500/90 backdrop-blur-md text-white text-xs px-4 py-1.5 rounded-full font-semibold shadow-lg"
                >
                    <span
                        class="w-2 h-2 bg-white rounded-full mr-2 animate-pulse"
                    ></span>
                    可預約
                </span>
                <span
                    v-else-if="vehicle.maintenance_start_at"
                    class="inline-flex items-center bg-amber-500/90 backdrop-blur-md text-white text-xs px-4 py-1.5 rounded-full font-medium shadow-lg"
                >
                    維修中
                </span>
                <span
                    v-else
                    class="inline-flex items-center bg-gray-600/90 backdrop-blur-md text-white text-xs px-4 py-1.5 rounded-full font-medium shadow-lg"
                >
                    已出租
                </span>
            </div>
        </div>

        <!-- Vehicle Info - 優化內部層級與留白 -->
        <div class="p-7">
            <h3
                class="text-2xl font-bold text-gray-900 leading-tight group-hover:text-blue-600 transition-colors duration-300"
            >
                {{ vehicle.name }}
            </h3>

            <!-- Specs - 更精緻的規格展示 -->
            <div class="mt-5 flex flex-wrap gap-5 text-sm text-gray-600">
                <span
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-blue-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 20v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                    {{ vehicle.seats }}人座
                </span>
                <span
                    v-if="vehicle.manufacture_year"
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-blue-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m0 0V7m0-3h8m-8 18v-4m0 4h8m-8 0h-3a2 2 0 01-2-2V9a2 2 0 012-2h3m8 16h3a2 2 0 002-2V9a2 2 0 00-2-2h-3m-8 18h8m-8-18h8a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2z"
                        />
                    </svg>
                    {{ vehicle.manufacture_year }}年
                </span>
                <span
                    v-if="vehicle.fuel_type"
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-green-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 0 01-2.343 5.657z"
                        />
                    </svg>
                    {{
                        vehicle.fuel_type === "PETROL"
                            ? "汽油"
                            : vehicle.fuel_type === "DIESEL"
                              ? "柴油"
                              : vehicle.fuel_type === "ELECTRIC"
                                ? "電動"
                                : vehicle.fuel_type === "HYBRID"
                                  ? "油電混合"
                                  : vehicle.fuel_type
                    }}
                </span>
                <span
                    v-if="vehicle.mileage"
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-blue-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                        />
                    </svg>
                    {{ (vehicle.mileage / 10000).toFixed(1) }}萬公里
                </span>
                <span
                    v-if="vehicle.transmission"
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-blue-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.723 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.723 0 001.065 2.572c1.756.426 1.756 2.924 3.35 0a1.723 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.723 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.723 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.723 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.723 0 001.066-2.573c-.94-1.543.826-3.31-2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    {{ vehicle.transmission === "automatic" ? "自排" : "手排" }}
                </span>
                <span
                    v-if="vehicle.luggage && vehicle.type === 'CAR'"
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-blue-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                    {{ vehicle.luggage }}件行李
                </span>
                <!-- 機車專用資訊：排氣量 -->
                <span
                    v-if="vehicle.type === 'MOTORCYCLE' && vehicle.engine_cc"
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-orange-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"
                        />
                    </svg>
                    {{ vehicle.engine_cc }}cc
                </span>
                <!-- 機車專用資訊：安全帽數量 -->
                <span
                    v-if="vehicle.type === 'MOTORCYCLE' && vehicle.helmet_count"
                    class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl"
                >
                    <svg
                        class="w-4 h-4 text-green-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                    {{ vehicle.helmet_count }}頂安全帽
                </span>
            </div>

            <!-- Price and Actions - 強化價格與CTA的視覺權重 -->
            <div
                class="mt-7 pt-6 border-t border-gray-100 flex items-center justify-between"
            >
                <div>
                    <span
                        v-if="vehicle.pricing?.weekday_price"
                        class="text-4xl font-extrabold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent"
                    >
                        ${{ vehicle.pricing.weekday_price }}
                    </span>
                    <span v-else class="text-2xl font-bold text-gray-600">
                        詢問價格
                    </span>
                    <span
                        v-if="vehicle.pricing?.weekday_price"
                        class="text-gray-400 text-base ml-2 font-medium"
                        >/天</span
                    >
                </div>
                <router-link
                    :to="`/${tenantSlug}/vehicles/${vehicle.id}`"
                    :class="[
                        'inline-flex items-center px-7 py-3.5 rounded-2xl transition-all duration-300 text-sm font-bold',
                        'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white shadow-xl shadow-orange-500/30 hover:shadow-2xl hover:shadow-orange-500/40 hover:-translate-y-0.5 active:scale-[0.97]',
                    ]"
                >
                    立即租車
                    <svg
                        class="w-4 h-4 ml-2 transition-transform duration-300 group-hover:translate-x-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";

const route = useRoute();
const tenantSlug = route.params.tenantSlug as string;

interface VehiclePricing {
    weekday_price: number;
    weekend_price: number;
    holiday_price: number;
    daily_rate?: number;
}

interface Vehicle {
    id: number;
    name: string;
    plate_number: string;
    status: string;
    seats: number;
    transmission?: string;
    manufacture_year?: number;
    mileage?: number;
    fuel_type?: string;
    description?: string;
    image_url?: string;
    images?: string[];
    pricing?: VehiclePricing;
    maintenance_start_at?: string | null;
    maintenance_expected_end_at?: string | null;
    is_featured?: boolean;
    brand?: string;
    model?: string;
    luggage?: number;
    features?: string[];
}

defineProps<{
    vehicle: Vehicle;
}>();
</script>

<style scoped>
/* Component specific styles */
</style>
