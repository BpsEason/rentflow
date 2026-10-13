<template>
    <PublicLayout>
        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <router-link
                        :to="`/${tenantSlug}/my-bookings`"
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
                        返回我的預約
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
                        @click="fetchReservation"
                        class="mt-4 text-blue-900 underline"
                    >
                        重新載入
                    </button>
                </div>

                <!-- Reservation Detail Content -->
                <template v-else-if="reservation">
                    <!-- Header -->
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        >
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">
                                    預約詳情
                                </h1>
                                <p class="text-gray-600">
                                    預約編號：<span class="font-mono">{{
                                        reservation.booking_number
                                    }}</span>
                                </p>
                            </div>
                            <span
                                :class="[
                                    'px-4 py-2 rounded-full text-sm font-medium',
                                    getStatusClasses(reservation.status),
                                ]"
                            >
                                {{ getStatusText(reservation.status) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Vehicle Info -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    車輛資訊
                                </h2>
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <img
                                        :src="reservation.vehicle?.image_url"
                                        :alt="reservation.vehicle?.name"
                                        class="w-full sm:w-48 h-48 object-cover rounded-lg"
                                    />
                                    <div class="flex-1">
                                        <h3
                                            class="text-xl font-semibold text-gray-900"
                                        >
                                            {{ reservation.vehicle?.name }}
                                        </h3>
                                        <p class="text-gray-600 mb-4">
                                            {{ reservation.vehicle?.brand }}
                                            {{ reservation.vehicle?.model }}
                                        </p>
                                        <div
                                            class="grid grid-cols-2 gap-3 text-sm"
                                        >
                                            <div>
                                                <span class="text-gray-500"
                                                    >乘客數：</span
                                                >
                                                <span class="text-gray-900"
                                                    >{{
                                                        reservation.vehicle
                                                            ?.seats || 5
                                                    }}
                                                    人</span
                                                >
                                            </div>
                                            <div>
                                                <span class="text-gray-500"
                                                    >變速箱：</span
                                                >
                                                <span class="text-gray-900">{{
                                                    reservation.vehicle
                                                        ?.transmission || "自排"
                                                }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500"
                                                    >燃油類型：</span
                                                >
                                                <span class="text-gray-900">{{
                                                    reservation.vehicle
                                                        ?.fuel_type || "汽油"
                                                }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500"
                                                    >行李箱：</span
                                                >
                                                <span class="text-gray-900"
                                                    >{{
                                                        reservation.vehicle
                                                            ?.luggage || 3
                                                    }}
                                                    件</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rental Period -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    租賃期間
                                </h2>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-6"
                                >
                                    <div class="p-4 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-blue-600 mb-1">
                                            取車時間
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-blue-900"
                                        >
                                            {{
                                                formatDate(
                                                    reservation.pickup_date,
                                                )
                                            }}
                                        </p>
                                        <p class="text-blue-700">
                                            {{ reservation.pickup_time }}
                                        </p>
                                    </div>
                                    <div class="p-4 bg-green-50 rounded-lg">
                                        <p class="text-sm text-green-600 mb-1">
                                            還車時間
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-green-900"
                                        >
                                            {{
                                                formatDate(
                                                    reservation.return_date,
                                                )
                                            }}
                                        </p>
                                        <p class="text-green-700">
                                            {{ reservation.return_time }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <p class="text-center text-gray-600">
                                        總共租用
                                        <span
                                            class="font-bold text-gray-900 text-xl"
                                            >{{
                                                calculateDays(
                                                    reservation.pickup_date,
                                                    reservation.return_date,
                                                )
                                            }}</span
                                        >
                                        天
                                    </p>
                                </div>
                            </div>

                            <!-- Customer Information -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    聯絡人資訊
                                </h2>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            姓名
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ reservation.customer_name }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            手機號碼
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ reservation.customer_phone }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            電子郵件
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ reservation.customer_email }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            身份證字號
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            *******{{
                                                reservation.customer_id_number?.slice(
                                                    -4,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            駕照號碼
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ reservation.license_number }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            通訊地址
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{
                                                reservation.address || "未提供"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Services -->
                            <div
                                v-if="
                                    reservation.additional_services &&
                                    Object.values(
                                        reservation.additional_services,
                                    ).some((v) => v)
                                "
                                class="bg-white rounded-xl shadow-md p-6"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    加購服務
                                </h2>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-if="
                                            reservation.additional_services.gps
                                        "
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm"
                                    >
                                        GPS 導航
                                    </span>
                                    <span
                                        v-if="
                                            reservation.additional_services
                                                .child_seat
                                        "
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm"
                                    >
                                        兒童安全座椅
                                    </span>
                                    <span
                                        v-if="
                                            reservation.additional_services
                                                .additional_driver
                                        "
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm"
                                    >
                                        額外駕駛人
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar - Price Summary & Actions -->
                        <div class="lg:col-span-1">
                            <div
                                class="bg-white rounded-xl shadow-md p-6 sticky top-8"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    費用明細
                                </h2>
                                <div class="space-y-3 mb-6">
                                    <div
                                        class="flex justify-between text-gray-600"
                                    >
                                        <span>基本租金</span>
                                        <span
                                            >NT$
                                            {{
                                                (
                                                    reservation.total_amount *
                                                    0.95
                                                ).toLocaleString()
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
                                                Math.round(
                                                    reservation.total_amount *
                                                        0.05,
                                                ).toLocaleString()
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="border-t border-gray-200 pt-3 mt-3"
                                    >
                                        <div
                                            class="flex justify-between items-center"
                                        >
                                            <span
                                                class="font-semibold text-gray-900"
                                                >總計</span
                                            >
                                            <span
                                                class="text-2xl font-bold text-blue-900"
                                                >NT$
                                                {{
                                                    reservation.total_amount?.toLocaleString()
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="space-y-3">
                                    <button
                                        v-if="canCancel(reservation)"
                                        @click="openCancelModal()"
                                        class="w-full px-4 py-2 border border-red-500 text-red-500 hover:bg-red-50 rounded-lg font-medium transition"
                                    >
                                        取消預約
                                    </button>
                                    <button
                                        v-if="
                                            reservation.status === 'CONFIRMED'
                                        "
                                        class="w-full px-4 py-2 border border-blue-900 text-blue-900 hover:bg-blue-50 rounded-lg font-medium transition"
                                    >
                                        聯繫客服
                                    </button>
                                </div>

                                <!-- Created At -->
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <p class="text-xs text-gray-500">
                                        預約建立時間
                                    </p>
                                    <p class="text-sm text-gray-700">
                                        {{
                                            formatDateTime(
                                                reservation.created_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Cancel Modal -->
            <div
                v-if="showCancelModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            >
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        確認取消預約？
                    </h3>
                    <p class="text-gray-600 mb-6">
                        您即將取消預約編號
                        <span class="font-mono font-semibold">{{
                            reservation?.booking_number
                        }}</span
                        >，此操作無法復原。
                    </p>

                    <div
                        class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6"
                    >
                        <p class="text-yellow-800 text-sm">
                            <strong>注意：</strong
                            >如果在取車前24小時內取消，將收取首日租金作為手續費。
                        </p>
                    </div>

                    <div class="flex gap-4">
                        <button
                            @click="showCancelModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg font-medium transition"
                        >
                            再想想
                        </button>
                        <button
                            @click="confirmCancel"
                            :disabled="cancelling"
                            class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center justify-center"
                        >
                            <span
                                v-if="cancelling"
                                class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"
                            ></span>
                            確認取消
                        </button>
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
const cancelling = ref(false);
const error = ref<string | null>(null);
const reservation = ref<any>(null);
const showCancelModal = ref(false);

const formatDate = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
};

const formatDateTime = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日 ${date.getHours().toString().padStart(2, "0")}:${date.getMinutes().toString().padStart(2, "0")}`;
};

const calculateDays = (pickup: string, returnDate: string) => {
    if (!pickup || !returnDate) return 1;
    const start = new Date(pickup);
    const end = new Date(returnDate);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays || 1;
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

const getStatusClasses = (status: string) => {
    const classesMap: Record<string, string> = {
        PENDING: "bg-yellow-100 text-yellow-800",
        CONFIRMED: "bg-blue-100 text-blue-800",
        ACTIVE: "bg-green-100 text-green-800",
        COMPLETED: "bg-gray-100 text-gray-800",
        CANCELLED: "bg-red-100 text-red-800",
    };
    return classesMap[status] || "bg-gray-100 text-gray-800";
};

const canCancel = (booking: any) => {
    return ["PENDING", "CONFIRMED"].includes(booking?.status);
};

const openCancelModal = () => {
    showCancelModal.value = true;
};

const confirmCancel = async () => {
    if (!reservation.value) return;

    cancelling.value = true;
    try {
        await api.post(`/reservations/${reservation.value.id}/cancel`);
        showCancelModal.value = false;
        fetchReservation(); // Refresh data
    } catch (e: any) {
        console.error("Failed to cancel reservation:", e);
        if (e.response?.data?.message) {
            alert(`取消失敗：${e.response.data.message}`);
        } else {
            alert("取消失敗，請稍後再試");
        }
    } finally {
        cancelling.value = false;
    }
};

const fetchReservation = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get(
            `/${tenantSlug}/customer/reservations/${reservationId}`,
        );
        reservation.value = response.data.data;
    } catch (e: any) {
        console.error("Failed to fetch reservation:", e);
        if (e.response?.status === 404) {
            error.value = "找不到指定的預約資訊";
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
