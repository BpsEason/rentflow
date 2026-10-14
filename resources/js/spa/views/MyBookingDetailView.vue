<template>
    <PublicLayout>
        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
                    <!-- Header - Modernized -->
                    <div
                        class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 mb-6 hover:shadow-xl transition-all duration-500"
                    >
                        <div
                            class="flex flex-col lg:flex-row lg:items-start justify-between gap-6"
                        >
                            <div class="flex-1">
                                <h1
                                    class="text-3xl font-bold text-gray-900 tracking-tight mb-4"
                                >
                                    預約詳情
                                </h1>
                                <div class="flex flex-wrap items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-sm font-medium text-gray-500"
                                            >預約編號</span
                                        >
                                        <code
                                            class="px-3 py-1 bg-gray-100 rounded-lg text-gray-900 font-mono text-sm font-semibold"
                                            >{{
                                                reservation.booking_number
                                            }}</code
                                        >
                                        <button
                                            @click="
                                                navigator.clipboard.writeText(
                                                    reservation.booking_number,
                                                )
                                            "
                                            class="p-1.5 hover:bg-gray-100 rounded-md transition-colors"
                                            title="複製預約編號"
                                        >
                                            <svg
                                                class="w-4 h-4 text-gray-400 hover:text-gray-600"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                ></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div
                                        v-if="reservation.order_number"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="text-sm font-medium text-gray-500"
                                            >訂單編號</span
                                        >
                                        <code
                                            class="px-3 py-1 bg-gray-100 rounded-lg text-gray-900 font-mono text-sm"
                                            >{{
                                                reservation.order_number
                                            }}</code
                                        >
                                    </div>
                                </div>
                                <p
                                    class="text-sm text-gray-400 mt-4 flex items-center gap-2"
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
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    建立時間：{{
                                        formatDateTime(reservation.created_at)
                                    }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold',
                                        getStatusClasses(reservation.status),
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-2 h-2 rounded-full',
                                            reservation.status === 'PENDING'
                                                ? 'bg-yellow-500'
                                                : '',
                                            reservation.status === 'CONFIRMED'
                                                ? 'bg-blue-500'
                                                : '',
                                            reservation.status === 'ACTIVE'
                                                ? 'bg-green-500'
                                                : '',
                                            reservation.status === 'COMPLETED'
                                                ? 'bg-gray-500'
                                                : '',
                                            reservation.status === 'CANCELLED'
                                                ? 'bg-red-500'
                                                : '',
                                        ]"
                                    ></span>
                                    {{ getStatusText(reservation.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-10 gap-8">
                        <!-- Main Content -->
                        <div class="lg:col-span-7 space-y-6">
                            <!-- Vehicle Card -->
                            <ReservationVehicleCard
                                :vehicle="reservation.vehicle"
                            />

                            <!-- Rental Period Card -->
                            <ReservationPeriodCard
                                :startAt="reservation.start_at"
                                :endAt="reservation.end_at"
                                :rentalDays="
                                    reservation.rental_days ||
                                    calculateDays(
                                        reservation.start_at,
                                        reservation.end_at,
                                    )
                                "
                            />

                            <!-- Customer Card -->
                            <ReservationCustomerCard
                                :customerName="reservation.customer_name"
                                :customerPhone="reservation.customer_phone"
                                :customerEmail="reservation.customer_email"
                                :customerIdNumber="
                                    reservation.customer_id_number
                                "
                                :licenseNumber="reservation.license_number"
                                :address="reservation.address"
                            />

                            <!-- Additional Services Card -->
                            <ReservationServicesCard
                                v-if="
                                    reservation.additional_services &&
                                    Object.values(
                                        reservation.additional_services,
                                    ).some((v) => v)
                                "
                                :additionalServices="
                                    reservation.additional_services
                                "
                            />
                        </div>

                        <!-- Sidebar - Price Summary & Actions -->
                        <div class="lg:col-span-3">
                            <ReservationSummaryCard
                                :amount="reservation.amount"
                                :status="reservation.status"
                                :canCancel="canCancel(reservation)"
                                :createdAt="reservation.created_at"
                                @open-cancel-modal="openCancelModal"
                            />
                        </div>
                    </div>
                </template>
            </div>

            <!-- Cancel Reservation Modal -->
            <CancelReservationModal
                :show="showCancelModal"
                :bookingNumber="reservation?.booking_number || ''"
                :cancelling="cancelling"
                @close="showCancelModal = false"
                @confirm="confirmCancel"
            />
        </div>
    </PublicLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import PublicLayout from "../components/Layout/PublicLayout.vue";
import ReservationVehicleCard from "../components/Reservation/ReservationVehicleCard.vue";
import ReservationPeriodCard from "../components/Reservation/ReservationPeriodCard.vue";
import ReservationCustomerCard from "../components/Reservation/ReservationCustomerCard.vue";
import ReservationServicesCard from "../components/Reservation/ReservationServicesCard.vue";
import ReservationSummaryCard from "../components/Reservation/ReservationSummaryCard.vue";
import CancelReservationModal from "../components/Reservation/CancelReservationModal.vue";
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

const formatTime = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");
    return `${hours}:${minutes}`;
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
        await api.post(`/customer/reservations/${reservation.value.id}/cancel`);
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
            `/customer/reservations/${reservationId}`,
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
