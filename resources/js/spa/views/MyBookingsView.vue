<template>
    <PublicLayout>
        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">我的預約</h1>
                    <p class="mt-2 text-lg text-gray-600">
                        管理您的所有租車預約
                    </p>
                </div>

                <!-- Tabs -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="flex space-x-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.value"
                            @click="activeTab = tab.value"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition',
                                activeTab === tab.value
                                    ? 'border-blue-900 text-blue-900'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            ]"
                        >
                            {{ tab.label }}
                            <span
                                :class="[
                                    'ml-2 px-2 py-0.5 rounded-full text-xs',
                                    activeTab === tab.value
                                        ? 'bg-blue-100 text-blue-800'
                                        : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                {{ getTabCount(tab.value) }}
                            </span>
                        </button>
                    </nav>
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
                        @click="fetchBookings"
                        class="mt-4 text-blue-900 underline"
                    >
                        重新載入
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="filteredBookings.length === 0"
                    class="text-center py-20"
                >
                    <svg
                        class="mx-auto h-16 w-16 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        ></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">
                        目前沒有預約
                    </h3>
                    <p class="mt-2 text-gray-500">
                        開始瀏覽我們的車輛，找到適合您的理想車款
                    </p>
                    <router-link
                        to="/vehicles"
                        class="mt-6 inline-flex items-center px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg font-medium transition"
                    >
                        瀏覽車輛
                    </router-link>
                </div>

                <!-- Bookings List -->
                <div v-else class="space-y-6">
                    <div
                        v-for="booking in filteredBookings"
                        :key="booking.id"
                        class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition"
                    >
                        <div class="p-6">
                            <div
                                class="flex flex-col lg:flex-row lg:items-center gap-6"
                            >
                                <!-- Vehicle Image -->
                                <img
                                    :src="booking.vehicle?.image_url"
                                    :alt="booking.vehicle?.name"
                                    class="w-full lg:w-48 h-48 lg:h-32 object-cover rounded-lg"
                                />

                                <!-- Booking Info -->
                                <div class="flex-1">
                                    <div
                                        class="flex flex-wrap items-start justify-between gap-4"
                                    >
                                        <div>
                                            <h3
                                                class="text-xl font-semibold text-gray-900"
                                            >
                                                {{ booking.vehicle?.name }}
                                            </h3>
                                            <p class="text-sm text-gray-500">
                                                預約編號：{{
                                                    booking.booking_number
                                                }}
                                            </p>
                                        </div>
                                        <span
                                            :class="[
                                                'px-3 py-1 rounded-full text-sm font-medium',
                                                getStatusClasses(
                                                    booking.status,
                                                ),
                                            ]"
                                        >
                                            {{ getStatusText(booking.status) }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm"
                                    >
                                        <div>
                                            <p class="text-gray-500">
                                                取車日期
                                            </p>
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    formatDate(
                                                        booking.pickup_date,
                                                    )
                                                }}
                                            </p>
                                            <p class="text-gray-600">
                                                {{ booking.pickup_time }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">
                                                還車日期
                                            </p>
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    formatDate(
                                                        booking.return_date,
                                                    )
                                                }}
                                            </p>
                                            <p class="text-gray-600">
                                                {{ booking.return_time }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">
                                                租用天數
                                            </p>
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    calculateDays(
                                                        booking.pickup_date,
                                                        booking.return_date,
                                                    )
                                                }}
                                                天
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">總金額</p>
                                            <p class="font-bold text-blue-900">
                                                NT$
                                                {{
                                                    booking.total_amount?.toLocaleString()
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex lg:flex-col gap-3">
                                    <router-link
                                        :to="`/my-bookings/${booking.id}`"
                                        class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg font-medium transition text-center text-sm"
                                    >
                                        查看詳情
                                    </router-link>
                                    <button
                                        v-if="canCancel(booking)"
                                        @click="openCancelModal(booking)"
                                        class="px-4 py-2 border border-red-500 text-red-500 hover:bg-red-50 rounded-lg font-medium transition text-sm"
                                    >
                                        取消預約
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                            cancelBooking?.booking_number
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
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PublicLayout from "../components/Layout/PublicLayout.vue";
import api from "../services/api";

const router = useRouter();

const tabs = [
    { label: "全部", value: "all" },
    { label: "待確認", value: "PENDING" },
    { label: "已確認", value: "CONFIRMED" },
    { label: "租用中", value: "ACTIVE" },
    { label: "已完成", value: "COMPLETED" },
    { label: "已取消", value: "CANCELLED" },
];

const activeTab = ref("all");
const loading = ref(true);
const cancelling = ref(false);
const error = ref<string | null>(null);
const bookings = ref<any[]>([]);
const showCancelModal = ref(false);
const cancelBooking = ref<any>(null);

const filteredBookings = computed(() => {
    if (activeTab.value === "all") return bookings.value;
    return bookings.value.filter((b) => b.status === activeTab.value);
});

const getTabCount = (tabValue: string) => {
    if (tabValue === "all") return bookings.value.length;
    return bookings.value.filter((b) => b.status === tabValue).length;
};

const formatDate = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
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
    return ["PENDING", "CONFIRMED"].includes(booking.status);
};

const openCancelModal = (booking: any) => {
    cancelBooking.value = booking;
    showCancelModal.value = true;
};

const confirmCancel = async () => {
    if (!cancelBooking.value) return;

    cancelling.value = true;
    try {
        await api.post(`/reservations/${cancelBooking.value.id}/cancel`);
        showCancelModal.value = false;
        cancelBooking.value = null;
        fetchBookings(); // Refresh list
    } catch (e: any) {
        console.error("Failed to cancel booking:", e);
        if (e.response?.data?.message) {
            alert(`取消失敗：${e.response.data.message}`);
        } else {
            alert("取消失敗，請稍後再試");
        }
    } finally {
        cancelling.value = false;
    }
};

const fetchBookings = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get("/reservations");
        bookings.value = response.data.data.data || response.data.data || [];
    } catch (e: any) {
        console.error("Failed to fetch bookings:", e);
        if (e.response?.status === 401) {
            error.value = "您需要登入才能查看預約";
            setTimeout(() => router.push("/login"), 3000);
        } else {
            error.value = "載入預約列表失敗，請稍後再試";
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchBookings();
});
</script>
