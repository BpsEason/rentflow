<template>
    <PublicLayout>
        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Success State -->
                <div
                    v-if="!loading && !error && reservation"
                    class="text-center"
                >
                    <!-- Success Icon -->
                    <div class="mb-8">
                        <div
                            class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full"
                        >
                            <svg
                                class="w-12 h-12 text-green-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                ></path>
                            </svg>
                        </div>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 mb-4">
                        預約成功！
                    </h1>
                    <p class="text-lg text-gray-600 mb-8">
                        您的租車預約已成功提交，預約編號：<span
                            class="font-mono font-bold text-blue-900"
                            >{{ reservation.booking_number }}</span
                        >
                    </p>

                    <!-- Confirmation Card -->
                    <div
                        class="bg-white rounded-xl shadow-md p-8 mb-8 text-left"
                    >
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">
                            預約資訊
                        </h2>

                        <!-- Vehicle Info -->
                        <div
                            class="flex items-start gap-4 pb-6 border-b border-gray-200 mb-6"
                        >
                            <img
                                :src="reservation.vehicle?.image_url"
                                :alt="reservation.vehicle?.name"
                                class="w-32 h-24 object-cover rounded-lg"
                            />
                            <div>
                                <h3 class="font-semibold text-gray-900 text-lg">
                                    {{ reservation.vehicle?.name }}
                                </h3>
                                <p class="text-gray-600">
                                    {{ reservation.vehicle?.brand }}
                                    {{ reservation.vehicle?.model }}
                                </p>
                                <p class="text-blue-900 font-semibold mt-2">
                                    總金額：NT$
                                    {{
                                        reservation.total_amount?.toLocaleString()
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Booking Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Pickup Info -->
                            <div>
                                <h4
                                    class="text-sm font-medium text-gray-500 mb-2"
                                >
                                    取車資訊
                                </h4>
                                <p class="font-semibold text-gray-900">
                                    {{ formatDate(reservation.pickup_date) }}
                                </p>
                                <p class="text-gray-600">
                                    {{ reservation.pickup_time }}
                                </p>
                            </div>

                            <!-- Return Info -->
                            <div>
                                <h4
                                    class="text-sm font-medium text-gray-500 mb-2"
                                >
                                    還車資訊
                                </h4>
                                <p class="font-semibold text-gray-900">
                                    {{ formatDate(reservation.return_date) }}
                                </p>
                                <p class="text-gray-600">
                                    {{ reservation.return_time }}
                                </p>
                            </div>

                            <!-- Customer Info -->
                            <div>
                                <h4
                                    class="text-sm font-medium text-gray-500 mb-2"
                                >
                                    聯絡人資訊
                                </h4>
                                <p class="font-semibold text-gray-900">
                                    {{ reservation.customer_name }}
                                </p>
                                <p class="text-gray-600">
                                    {{ reservation.customer_phone }}
                                </p>
                                <p class="text-gray-600">
                                    {{ reservation.customer_email }}
                                </p>
                            </div>

                            <!-- Status Info -->
                            <div>
                                <h4
                                    class="text-sm font-medium text-gray-500 mb-2"
                                >
                                    預約狀態
                                </h4>
                                <span
                                    class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium"
                                >
                                    {{ getStatusText(reservation.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="bg-blue-50 rounded-xl p-6 mb-8 text-left">
                        <h3 class="font-semibold text-gray-900 mb-4">
                            接下來的步驟
                        </h3>
                        <ol class="space-y-3">
                            <li class="flex items-start">
                                <span
                                    class="flex-shrink-0 w-6 h-6 bg-blue-900 text-white rounded-full flex items-center justify-center text-sm mr-3"
                                    >1</span
                                >
                                <span class="text-gray-700"
                                    >我們已將預約確認信發送至您的電子郵件
                                    {{ reservation.customer_email }}</span
                                >
                            </li>
                            <li class="flex items-start">
                                <span
                                    class="flex-shrink-0 w-6 h-6 bg-blue-900 text-white rounded-full flex items-center justify-center text-sm mr-3"
                                    >2</span
                                >
                                <span class="text-gray-700"
                                    >請於取車當天攜帶身份證及駕照正本至指定地點取車</span
                                >
                            </li>
                            <li class="flex items-start">
                                <span
                                    class="flex-shrink-0 w-6 h-6 bg-blue-900 text-white rounded-full flex items-center justify-center text-sm mr-3"
                                    >3</span
                                >
                                <span class="text-gray-700"
                                    >如有任何問題，請聯繫我們的客服專線：0800-XXX-XXX</span
                                >
                            </li>
                        </ol>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <router-link
                            :to="{ name: 'my-bookings', params: { tenantSlug } }"
                            class="px-8 py-3 bg-blue-900 hover:bg-blue-800 text-white rounded-lg font-medium transition text-center"
                        >
                            查看我的預約
                        </router-link>
                        <router-link
                            :to="{ name: 'vehicles', params: { tenantSlug } }"
                            class="px-8 py-3 border border-blue-900 text-blue-900 hover:bg-blue-50 rounded-lg font-medium transition text-center"
                        >
                            繼續瀏覽車輛
                        </router-link>
                    </div>
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex justify-center items-center py-20"
                >
                    <div
                        class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"
                    ></div>
                    <p class="ml-4 text-gray-600">載入預約資訊中...</p>
                </div>

                <!-- Error State -->
                <div v-if="error" class="text-center py-20">
                    <div
                        class="inline-flex items-center justify-center w-24 h-24 bg-red-100 rounded-full mb-6"
                    >
                        <svg
                            class="w-12 h-12 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            ></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        發生錯誤
                    </h2>
                    <p class="text-gray-600 mb-8">{{ error }}</p>
                    <div class="flex justify-center gap-4">
                        <button
                            @click="fetchReservation"
                            class="px-8 py-3 bg-blue-900 hover:bg-blue-800 text-white rounded-lg font-medium transition"
                        >
                            重新載入
                        </button>
                        <router-link
                            :to="{ name: 'home', params: { tenantSlug } }"
                            class="px-8 py-3 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg font-medium transition"
                        >
                            返回首頁
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import PublicLayout from "../components/Layout/PublicLayout.vue";
import api from "../services/api";

const route = useRoute();
const router = useRouter();
const tenantSlug = route.params.tenantSlug as string;
const reservationId = route.params.id as string;

const loading = ref(true);
const error = ref<string | null>(null);
const reservation = ref<any>(null);

const formatDate = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
};

const getStatusText = (status: string) => {
    const statusMap: Record<string, string> = {
        PENDING: "待確認",
        CONFIRMED: "已確認",
        ACTIVE: "租用中",
        COMPLETED: "已完成",
        CANCELLED: "已取消",
    };
    return statusMap[status] || status;
};

const fetchReservation = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get(`/reservations/${reservationId}`);
        reservation.value = response.data.data;
    } catch (e: any) {
        console.error("Failed to fetch reservation:", e);
        if (e.response?.status === 404) {
            error.value = "找不到指定的預約資訊，請確認預約編號是否正確";
        } else if (e.response?.status === 401) {
            error.value = "您需要登入才能查看此預約資訊";
            setTimeout(() => router.push(`/${tenantSlug}/login`), 3000);
        } else {
            error.value = "載入預約資訊失敗，請稍後再試";
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchReservation();
});
</script>
