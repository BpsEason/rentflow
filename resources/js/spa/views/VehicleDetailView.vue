<template>
    <PublicLayout>
        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <router-link
                        :to="`/${tenantSlug}/vehicles`"
                        class="inline-flex items-center text-blue-900 hover:text-blue-700 transition"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            ></path>
                        </svg>
                        返回車輛列表
                    </router-link>
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex justify-center items-center py-20"
                >
                    <div
                        class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"
                    ></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-20">
                    <p class="text-red-500">{{ error }}</p>
                    <button
                        @click="fetchVehicle"
                        class="mt-4 text-blue-900 underline"
                    >
                        重新載入
                    </button>
                </div>

                <!-- Vehicle Detail Content -->
                <template v-else>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Main Content - Left Column -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Vehicle Image Gallery -->
                            <div
                                class="bg-white rounded-xl shadow-md overflow-hidden"
                            >
                                <div class="relative">
                                    <img
                                        :src="
                                            selectedImage || vehicle.image_url
                                        "
                                        :alt="vehicle.name"
                                        class="w-full h-96 object-cover"
                                    />
                                    <span
                                        class="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium"
                                    >
                                        可租用
                                    </span>
                                </div>
                                <!-- 只有當有多張圖片時才顯示縮圖圖庫 -->
                                <div
                                    v-if="
                                        vehicle.images &&
                                        vehicle.images.length > 1
                                    "
                                    class="p-4 flex gap-4 overflow-x-auto"
                                >
                                    <img
                                        v-for="(img, idx) in vehicle.images"
                                        :key="idx"
                                        :src="img"
                                        :alt="`${vehicle.name} ${idx + 1}`"
                                        class="w-24 h-20 object-cover rounded-lg cursor-pointer hover:opacity-80 transition"
                                        @click="selectedImage = img"
                                    />
                                </div>
                            </div>

                            <!-- Vehicle Info -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h1
                                    class="text-3xl font-bold text-gray-900 mb-2"
                                >
                                    {{ vehicle.name }}
                                </h1>
                                <p class="text-gray-600 mb-6">
                                    {{ vehicle.brand }} {{ vehicle.model }}
                                </p>

                                <!-- Vehicle Specifications Grid -->
                                <h2
                                    class="text-xl font-semibold text-gray-900 mb-4"
                                >
                                    車輛規格
                                </h2>
                                <div
                                    class="grid grid-cols-2 md:grid-cols-4 gap-6"
                                >
                                    <div class="flex items-center">
                                        <svg
                                            class="w-8 h-8 text-blue-900 mr-3"
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
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                乘客數
                                            </p>
                                            <p
                                                class="font-semibold text-gray-900"
                                            >
                                                {{ vehicle.seats || 5 }} 人
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <svg
                                            class="w-8 h-8 text-blue-900 mr-3"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                            ></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                變速箱
                                            </p>
                                            <p
                                                class="font-semibold text-gray-900"
                                            >
                                                {{
                                                    vehicle.transmission ||
                                                    "自排"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <svg
                                            class="w-8 h-8 text-blue-900 mr-3"
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
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                燃油類型
                                            </p>
                                            <p
                                                class="font-semibold text-gray-900"
                                            >
                                                {{
                                                    vehicle.fuel_type || "汽油"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <svg
                                            class="w-8 h-8 text-blue-900 mr-3"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                            ></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                行李箱
                                            </p>
                                            <p
                                                class="font-semibold text-gray-900"
                                            >
                                                {{ vehicle.luggage || 3 }} 件
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vehicle Description -->
                                <div class="mt-8">
                                    <h2
                                        class="text-xl font-semibold text-gray-900 mb-4"
                                    >
                                        車輛介紹
                                    </h2>
                                    <p class="text-gray-600 leading-relaxed">
                                        {{
                                            vehicle.description ||
                                            "這是一款高性能的優質車輛，提供舒適的駕駛體驗。配備先進的安全系統和舒適的內裝，是您旅遊或商務出行的理想選擇。"
                                        }}
                                    </p>
                                </div>

                                <!-- Features -->
                                <div class="mt-8">
                                    <h2
                                        class="text-xl font-semibold text-gray-900 mb-4"
                                    >
                                        車輛配備
                                    </h2>
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-3 gap-4"
                                    >
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'aircon',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            冷氣空調
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'bluetooth',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            藍牙連接
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes('gps')
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            GPS導航
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'airbags',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            安全氣囊
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes('abs')
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            ABS防鎖死
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'backup_camera',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            倒車影像
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Sidebar - Right Column -->
                        <div class="lg:col-span-1">
                            <div
                                class="bg-white rounded-xl shadow-md p-6 sticky top-8"
                            >
                                <h2
                                    class="text-xl font-bold text-gray-900 mb-6"
                                >
                                    預約租車
                                </h2>

                                <!-- Date Selection -->
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >取車日期</label
                                        >
                                        <input
                                            v-model="bookingDates.pickupDate"
                                            type="date"
                                            :min="today"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                            @change="calculateTotal"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >取車時間</label
                                        >
                                        <select
                                            v-model="bookingDates.pickupTime"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                            @change="calculateTotal"
                                        >
                                            <option value="09:00">09:00</option>
                                            <option value="10:00">10:00</option>
                                            <option value="11:00">11:00</option>
                                            <option value="12:00">12:00</option>
                                            <option value="13:00">13:00</option>
                                            <option value="14:00">14:00</option>
                                            <option value="15:00">15:00</option>
                                            <option value="16:00">16:00</option>
                                            <option value="17:00">17:00</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >還車日期</label
                                        >
                                        <input
                                            v-model="bookingDates.returnDate"
                                            type="date"
                                            :min="
                                                bookingDates.pickupDate || today
                                            "
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                            @change="calculateTotal"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >還車時間</label
                                        >
                                        <select
                                            v-model="bookingDates.returnTime"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                            @change="calculateTotal"
                                        >
                                            <option value="09:00">09:00</option>
                                            <option value="10:00">10:00</option>
                                            <option value="11:00">11:00</option>
                                            <option value="12:00">12:00</option>
                                            <option value="13:00">13:00</option>
                                            <option value="14:00">14:00</option>
                                            <option value="15:00">15:00</option>
                                            <option value="16:00">16:00</option>
                                            <option value="17:00">17:00</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Date Validation Error -->
                                <div
                                    v-if="dateError"
                                    class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg"
                                >
                                    <p class="text-red-600 text-sm">
                                        {{ dateError }}
                                    </p>
                                </div>

                                <!-- Price Summary -->
                                <div class="border-t border-gray-200 pt-6 mb-6">
                                    <h3
                                        class="font-semibold text-gray-900 mb-4"
                                    >
                                        價格明細
                                    </h3>
                                    <div class="space-y-3">
                                        <div
                                            class="flex justify-between text-gray-600"
                                        >
                                            <span>每日租金</span>
                                            <span
                                                >NT$
                                                {{
                                                    dailyRate.toLocaleString()
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="flex justify-between text-gray-600"
                                        >
                                            <span>租用天數</span>
                                            <span>{{ days }} 天</span>
                                        </div>
                                        <div
                                            class="flex justify-between text-gray-600"
                                        >
                                            <span>基本租金</span>
                                            <span
                                                >NT$
                                                {{
                                                    basePrice.toLocaleString()
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="flex justify-between text-gray-600"
                                        >
                                            <span>服務費</span>
                                            <span
                                                >NT$
                                                {{
                                                    serviceFee.toLocaleString()
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <div
                                        class="border-t border-gray-200 mt-4 pt-4"
                                    >
                                        <div
                                            class="flex justify-between items-center"
                                        >
                                            <span
                                                class="text-lg font-semibold text-gray-900"
                                                >總計</span
                                            >
                                            <span
                                                class="text-2xl font-bold text-blue-900"
                                                >NT$
                                                {{
                                                    totalPrice.toLocaleString()
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Book Now Button -->
                                <button
                                    @click="proceedToBooking"
                                    :disabled="!canBook"
                                    class="w-full bg-blue-900 hover:bg-blue-800 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-medium transition"
                                >
                                    立即預約
                                </button>

                                <!-- Help Text -->
                                <p
                                    class="mt-4 text-xs text-gray-500 text-center"
                                >
                                    點擊立即預約後將進入填寫資料頁面
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import PublicLayout from "../components/Layout/PublicLayout.vue";
import api from "../services/api";

const route = useRoute();
const router = useRouter();
const tenantSlug = route.params.tenantSlug as string;
const vehicleId = route.params.id as string;

const loading = ref(true);
const error = ref<string | null>(null);
const vehicle = ref<any>({});
const today = ref(new Date().toISOString().split("T")[0]);
const dateError = ref<string | null>(null);
const selectedImage = ref<string | null>(null);

// Booking dates
const bookingDates = ref({
    pickupDate: "",
    pickupTime: "09:00",
    returnDate: "",
    returnTime: "17:00",
});

// Calculated prices
const dailyRate = computed(() => {
    return vehicle.value.pricing?.daily_rate || 2000;
});

const days = computed(() => {
    if (!bookingDates.value.pickupDate || !bookingDates.value.returnDate)
        return 0;
    const start = new Date(bookingDates.value.pickupDate);
    const end = new Date(bookingDates.value.returnDate);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays || 1;
});

const basePrice = computed(() => {
    return days.value * dailyRate.value;
});

const serviceFee = computed(() => {
    return Math.round(basePrice.value * 0.05); // 5% service fee
});

const totalPrice = computed(() => {
    return basePrice.value + serviceFee.value;
});

const canBook = computed(() => {
    return (
        days.value > 0 &&
        !dateError.value &&
        vehicle.value.status === "AVAILABLE"
    );
});

const fetchVehicle = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get(`/public/vehicles/${vehicleId}`);
        vehicle.value = response.data.data;

        // Initialize selected image, handling both images array and single image_url
        if (vehicle.value.image_url) {
            selectedImage.value = vehicle.value.image_url;
        } else if (vehicle.value.images && vehicle.value.images.length > 0) {
            selectedImage.value = vehicle.value.images[0];
        }

        // Set default dates (tomorrow to day after tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 3);
        bookingDates.value.pickupDate = tomorrow.toISOString().split("T")[0];
        bookingDates.value.returnDate = dayAfter.toISOString().split("T")[0];
    } catch (e) {
        error.value = "載入車輛詳情失敗，請稍後再試";
        console.error("Failed to fetch vehicle:", e);
    } finally {
        loading.value = false;
    }
};

const calculateTotal = () => {
    dateError.value = null;

    if (bookingDates.value.pickupDate && bookingDates.value.returnDate) {
        const start = new Date(bookingDates.value.pickupDate);
        const end = new Date(bookingDates.value.returnDate);

        if (end < start) {
            dateError.value = "還車日期不能早於取車日期";
        } else if (start.toDateString() === end.toDateString()) {
            // Same day, check times
            const pickupHour = parseInt(
                bookingDates.value.pickupTime.split(":")[0],
            );
            const returnHour = parseInt(
                bookingDates.value.returnTime.split(":")[0],
            );
            if (returnHour <= pickupHour) {
                dateError.value = "當日歸還時，還車時間必須晚於取車時間";
            }
        }
    }
};

const proceedToBooking = () => {
    if (!canBook.value) return;

    // Pass booking data to booking page
    router.push({
        path: `/${tenantSlug}/booking/${vehicleId}`,
        query: {
            pickupDate: bookingDates.value.pickupDate,
            pickupTime: bookingDates.value.pickupTime,
            returnDate: bookingDates.value.returnDate,
            returnTime: bookingDates.value.returnTime,
            totalPrice: totalPrice.value.toString(),
        },
    });
};

onMounted(() => {
    fetchVehicle();
});
</script>
