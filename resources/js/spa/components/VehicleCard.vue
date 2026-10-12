<template>
    <div
        class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300"
    >
        <!-- Vehicle Image -->
        <div class="relative h-48 bg-gray-200">
            <img
                :src="
                    vehicle.image_url ||
                    'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600'
                "
                :alt="vehicle.name"
                class="w-full h-full object-cover"
            />
            <span
                v-if="vehicle.status === 'AVAILABLE'"
                class="absolute top-3 left-3 bg-green-500 text-white text-xs px-2 py-1 rounded-full"
            >
                可預約
            </span>
            <span
                v-else
                class="absolute top-3 left-3 bg-gray-500 text-white text-xs px-2 py-1 rounded-full"
            >
                已出租
            </span>
        </div>

        <!-- Vehicle Info -->
        <div class="p-5">
            <h3 class="text-xl font-bold text-gray-900">{{ vehicle.name }}</h3>

            <!-- Specs -->
            <div class="mt-3 flex flex-wrap gap-3 text-sm text-gray-600">
                <span class="flex items-center gap-1">
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                    {{ vehicle.seats }}人座
                </span>
                <span
                    v-if="vehicle.transmission"
                    class="flex items-center gap-1"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 3.35 0a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
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
            </div>

            <!-- Price and Actions -->
            <div
                class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between"
            >
                <div>
                    <span class="text-2xl font-bold text-blue-900">
                        ${{ vehicle.pricing?.daily_rate || 0 }}
                    </span>
                    <span class="text-gray-500 text-sm">/天</span>
                </div>
                <router-link
                    :to="`/${tenantSlug}/vehicles/${vehicle.id}`"
                    class="bg-blue-900 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-sm font-medium"
                >
                    查看詳情
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
    daily_rate: number;
}

interface Vehicle {
    id: number;
    name: string;
    plate_number: string;
    status: string;
    seats: number;
    transmission?: string;
    description?: string;
    image_url?: string;
    pricing?: VehiclePricing;
}

defineProps<{
    vehicle: Vehicle;
}>();
</script>

<style scoped>
/* Component specific styles */
</style>
