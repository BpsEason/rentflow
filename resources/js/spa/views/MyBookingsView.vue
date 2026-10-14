<template>
    <PublicLayout>
        <div class="py-12 bg-gradient-to-b from-slate-50 to-white min-h-screen">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header - 精緻化標題區域 -->
                <div
                    class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6"
                >
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <div
                                class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/25"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
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
                            </div>
                            <h1
                                class="text-4xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"
                            >
                                我的預約
                            </h1>
                        </div>
                        <p class="mt-1 text-slate-500 pl-18 text-lg">
                            管理您的所有租車預約，隨時掌握行程狀態
                        </p>
                    </div>
                    <router-link
                        :to="`/${tenantSlug}/vehicles`"
                        class="inline-flex items-center justify-center gap-2 px-7 py-4 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-2xl font-semibold transition-all duration-300 shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 hover:scale-[1.02] active:scale-[0.98] whitespace-nowrap"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            ></path>
                        </svg>
                        立即預約
                    </router-link>
                </div>

                <!-- Modern Tabs - 升級精緻度狀態篩選 -->
                <div
                    class="mb-8 bg-white rounded-2xl p-2 shadow-sm border border-slate-100"
                >
                    <nav class="flex flex-wrap gap-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.value"
                            @click="activeTab = tab.value"
                            :class="[
                                'flex-1 min-w-max py-3.5 px-6 rounded-xl font-medium text-sm transition-all duration-300 flex items-center justify-center gap-2',
                                activeTab === tab.value
                                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/25 scale-[1.02]'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            {{ tab.label }}
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-xs font-semibold transition-all duration-300',
                                    activeTab === tab.value
                                        ? 'bg-white/25 text-white'
                                        : 'bg-slate-100 text-slate-600',
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
                    class="flex justify-center items-center py-32"
                >
                    <div class="flex flex-col items-center gap-4">
                        <div
                            class="animate-spin rounded-full h-14 w-14 border-4 border-blue-900 border-t-transparent"
                        ></div>
                        <p class="text-gray-500">正在載入您的預約...</p>
                    </div>
                </div>

                <!-- Error State - 改善的錯誤狀態 -->
                <div
                    v-else-if="error"
                    class="flex justify-center items-center py-32"
                >
                    <div class="text-center max-w-md mx-auto">
                        <div
                            class="w-24 h-24 mx-auto bg-red-50 rounded-full flex items-center justify-center mb-6"
                        >
                            <svg
                                class="w-12 h-12 text-red-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            發生了一些問題
                        </h3>
                        <p class="text-gray-500 mb-6">{{ error }}</p>
                        <button
                            @click="fetchBookings"
                            class="inline-flex items-center px-6 py-3 bg-blue-900 hover:bg-blue-800 text-white rounded-xl font-medium transition-all duration-300 shadow-lg shadow-blue-900/25 hover:shadow-xl"
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
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                            重新載入
                        </button>
                    </div>
                </div>

                <!-- Empty State - 最重要的空狀態升級 -->
                <div
                    v-else-if="filteredBookings.length === 0"
                    class="flex justify-center items-center py-24"
                >
                    <div class="text-center max-w-lg mx-auto">
                        <!-- 精緻的插圖容器 -->
                        <div class="relative mb-8">
                            <div
                                class="absolute inset-0 bg-gradient-to-b from-blue-100/50 to-transparent rounded-full blur-2xl transform scale-150"
                            ></div>
                            <div
                                class="relative w-40 h-40 mx-auto bg-gradient-to-br from-blue-50 to-slate-100 rounded-3xl flex items-center justify-center border border-blue-100/50 shadow-inner"
                            >
                                <svg
                                    class="w-20 h-20 text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    ></path>
                                </svg>
                            </div>
                        </div>

                        <h3 class="text-2xl font-bold text-gray-900 mb-3">
                            您還沒有任何預約
                        </h3>
                        <p
                            class="text-gray-500 text-lg mb-8 max-w-sm mx-auto leading-relaxed"
                        >
                            開始您的旅程！從我們精選的車隊中找到最適合您的理想車款，展開一段精彩的租車體驗
                        </p>

                        <div
                            class="flex flex-col sm:flex-row items-center justify-center gap-4"
                        >
                            <router-link
                                :to="`/${tenantSlug}/vehicles`"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-blue-900 hover:bg-blue-800 text-white rounded-xl font-semibold transition-all duration-300 shadow-xl shadow-blue-900/30 hover:shadow-2xl hover:shadow-blue-900/35 hover:scale-[1.02] active:scale-[0.98]"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    ></path>
                                </svg>
                                立即預約第一台車
                            </router-link>
                        </div>

                        <!-- 額外的信任感文字 -->
                        <div class="mt-12 pt-8 border-t border-gray-100">
                            <div
                                class="flex items-center justify-center gap-8 text-sm text-gray-400"
                            >
                                <div class="flex items-center gap-2">
                                    <svg
                                        class="w-5 h-5 text-green-500"
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
                                    <span>免費取消</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg
                                        class="w-5 h-5 text-green-500"
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
                                    <span>透明價格</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg
                                        class="w-5 h-5 text-green-500"
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
                                    <span>24/7支援</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings List - 現代化預約卡片列表 -->
                <div v-else class="space-y-5">
                    <div
                        v-for="booking in filteredBookings"
                        :key="booking.id"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-2xl hover:shadow-gray-900/8 hover:border-gray-200 transition-all duration-500 ease-out transform hover:-translate-y-1"
                    >
                        <div class="p-6 sm:p-7">
                            <div
                                class="flex flex-col lg:flex-row lg:items-center gap-6"
                            >
                                <!-- Vehicle Image - 精緻化圖片容器，含錯誤處理 -->
                                <div
                                    class="relative w-full lg:w-52 flex-shrink-0"
                                >
                                    <img
                                        :src="booking.vehicle?.image_url"
                                        :alt="
                                            booking.vehicle?.name || '車輛圖片'
                                        "
                                        class="w-full h-48 lg:h-36 object-cover rounded-xl shadow-sm"
                                        @error="
                                            (e) => {
                                                e.target.src =
                                                    'https://placehold.co/400x300/f1f5f9/94a3b8?text=車輛圖片';
                                            }
                                        "
                                    />
                                    <div
                                        class="absolute inset-0 rounded-xl ring-1 ring-inset ring-black/5"
                                    ></div>
                                </div>

                                <!-- Booking Info - 優化資訊層級 -->
                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex flex-wrap items-start justify-between gap-4"
                                    >
                                        <div>
                                            <!-- 預約編號權重降低 -->
                                            <p
                                                class="text-[11px] text-slate-400 mb-1 tracking-wide"
                                            >
                                                預約編號：<span
                                                    class="font-mono"
                                                    >{{
                                                        booking.booking_number
                                                    }}</span
                                                >
                                                <span
                                                    v-if="booking.order_number"
                                                    class="ml-3"
                                                >
                                                    訂單編號：<span
                                                        class="font-mono"
                                                        >{{
                                                            booking.order_number
                                                        }}</span
                                                    >
                                                </span>
                                                <span
                                                    v-else
                                                    class="ml-3 italic text-slate-300"
                                                >
                                                    訂單編號：尚未生成
                                                </span>
                                            </p>
                                            <!-- 車款名稱更加突出 -->
                                            <h3
                                                class="text-2xl lg:text-[clamp(1.5rem,3vw,1.875rem)] font-bold text-slate-900 leading-tight"
                                            >
                                                {{ booking.vehicle?.name }}
                                            </h3>
                                        </div>
                                        <!-- 狀態標籤樣式精緻化 -->
                                        <span
                                            :class="[
                                                'px-3.5 py-1.5 rounded-full text-xs font-semibold',
                                                getStatusClasses(
                                                    booking.status,
                                                ),
                                            ]"
                                        >
                                            {{ getStatusText(booking.status) }}
                                        </span>
                                    </div>

                                    <!-- 整合式行程資訊 - 更有節奏的呈現 -->
                                    <div
                                        class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-6"
                                    >
                                        <!-- 取還車日期 -->
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-blue-600"
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
                                            </div>
                                            <div>
                                                <p
                                                    class="text-[11px] text-slate-400 uppercase tracking-wide mb-0.5"
                                                >
                                                    行程時間
                                                </p>
                                                <div
                                                    class="font-medium text-slate-700 text-sm space-y-1"
                                                >
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="text-blue-600 text-[11px] font-semibold bg-blue-50 px-1.5 py-0.5 rounded"
                                                            >取車</span
                                                        >
                                                        <span
                                                            >{{
                                                                formatDateSafe(
                                                                    booking.start_at,
                                                                ).slice(5)
                                                            }}
                                                            {{
                                                                formatTimeSafe(
                                                                    booking.start_at,
                                                                )
                                                            }}</span
                                                        >
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="text-emerald-600 text-[11px] font-semibold bg-emerald-50 px-1.5 py-0.5 rounded"
                                                            >還車</span
                                                        >
                                                        <span
                                                            >{{
                                                                formatDateSafe(
                                                                    booking.end_at,
                                                                ).slice(5)
                                                            }}
                                                            {{
                                                                formatTimeSafe(
                                                                    booking.end_at,
                                                                )
                                                            }}</span
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- 租用天數 -->
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-emerald-600"
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
                                            </div>
                                            <div>
                                                <p
                                                    class="text-[11px] text-slate-400 uppercase tracking-wide mb-0.5"
                                                >
                                                    租用天數
                                                </p>
                                                <p
                                                    class="font-bold text-slate-800 text-xl"
                                                >
                                                    {{
                                                        booking.rental_days ||
                                                        calculateDays(
                                                            booking.start_at,
                                                            booking.end_at,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-slate-500 font-normal text-base ml-0.5"
                                                        >天</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                        <!-- 總金額成為視覺焦點 -->
                                        <div
                                            class="flex items-center justify-start sm:justify-end gap-3"
                                        >
                                            <div class="sm:text-right">
                                                <p
                                                    class="text-[11px] text-slate-400 uppercase tracking-wide mb-0.5"
                                                >
                                                    總金額
                                                </p>
                                                <p
                                                    class="font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-amber-600 to-orange-600 text-3xl"
                                                >
                                                    NT$
                                                    {{
                                                        booking.amount?.toLocaleString() ||
                                                        "0"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons - 優化視覺權重 -->
                                <div
                                    class="flex lg:flex-col gap-3 flex-shrink-0"
                                >
                                    <router-link
                                        :to="{
                                            name: 'my-booking-detail',
                                            params: {
                                                tenantSlug,
                                                id: booking.id,
                                            },
                                        }"
                                        class="px-7 py-3.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg shadow-blue-600/25 hover:shadow-xl hover:shadow-blue-600/35 hover:scale-[1.02] active:scale-[0.98] text-center whitespace-nowrap"
                                    >
                                        查看詳情
                                    </router-link>
                                    <button
                                        v-if="canCancel(booking)"
                                        @click="openCancelModal(booking)"
                                        class="px-7 py-3.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl font-medium transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] whitespace-nowrap border border-transparent hover:border-rose-200"
                                    >
                                        取消預約
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancel Modal - 現代化確認視窗 -->
            <div
                v-if="showCancelModal"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-in fade-in duration-300"
            >
                <div
                    class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 animate-in zoom-in-95 duration-300"
                >
                    <div
                        class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-6"
                    >
                        <svg
                            class="w-8 h-8 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            ></path>
                        </svg>
                    </div>
                    <h3
                        class="text-2xl font-bold text-gray-900 mb-3 text-center"
                    >
                        確認取消預約？
                    </h3>
                    <p class="text-gray-600 mb-6 text-center">
                        您即將取消預約編號
                        <span
                            class="font-mono font-semibold bg-gray-100 px-2 py-0.5 rounded"
                            >{{ cancelBooking?.booking_number }}</span
                        >
                        ，此操作無法復原。
                    </p>

                    <div
                        class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-8"
                    >
                        <div class="flex gap-3">
                            <svg
                                class="w-6 h-6 text-amber-500 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                ></path>
                            </svg>
                            <p class="text-amber-800 text-sm leading-relaxed">
                                <strong>注意：</strong
                                >如果在取車前24小時內取消，將收取首日租金作為手續費。
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button
                            @click="showCancelModal = false"
                            class="flex-1 px-6 py-3.5 border-2 border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 rounded-xl font-semibold transition-all duration-300"
                        >
                            再想想
                        </button>
                        <button
                            @click="confirmCancel"
                            :disabled="cancelling"
                            class="flex-1 px-6 py-3.5 bg-red-500 hover:bg-red-600 disabled:bg-gray-400 text-white rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg shadow-red-500/25 hover:shadow-xl hover:shadow-red-500/30"
                        >
                            <span
                                v-if="cancelling"
                                class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-2"
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
import { useRouter, useRoute } from "vue-router";
import PublicLayout from "../components/Layout/PublicLayout.vue";
import api from "../services/api";

const router = useRouter();
const route = useRoute();
const tenantSlug = route.params.tenantSlug as string;

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

// 安全的日期格式化 - 永遠不顯示 "-"
const formatDateSafe = (dateString: string) => {
    if (!dateString) return "日期待設定";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "日期待設定";
    return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
};

const formatTimeSafe = (dateString: string) => {
    if (!dateString) return "時間待設定";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "時間待設定";
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");
    return `${hours}:${minutes}`;
};

// 保留原函數以確保向後相容
const formatDate = formatDateSafe;
const formatTime = formatTimeSafe;

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
    // 2026 現代化低飽和色彩系統 - 更精緻的視覺表現
    const classesMap: Record<string, string> = {
        PENDING: "bg-amber-50/80 text-amber-700 border border-amber-200/80", // 待確認 - 溫和橘黃
        CONFIRMED: "bg-sky-50/80 text-sky-700 border border-sky-200/80", // 已確認 - 清爽藍
        ACTIVE: "bg-emerald-50/80 text-emerald-700 border border-emerald-200/80", // 租用中 - 柔和綠
        COMPLETED: "bg-slate-50/80 text-slate-600 border border-slate-200/80", // 已完成 - 中性灰
        CANCELLED: "bg-rose-50/80 text-rose-700 border border-rose-200/80", // 已取消 - 淡紅
    };
    return (
        classesMap[status] ||
        "bg-slate-50/80 text-slate-600 border border-slate-200/80"
    );
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
        await api.post(
            `/customer/reservations/${cancelBooking.value.id}/cancel`,
        );
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
        const response = await api.get("/customer/reservations");
        bookings.value = response.data.data.data || response.data.data || [];

        // 如果API回應說用戶尚未在此租戶建立客戶資料
        if (response.data.message?.includes("尚未在此租戶建立客戶資料")) {
            error.value = "您尚未在此租車公司建立客戶資料，請先完成第一次預約";
        }
    } catch (e: any) {
        console.error("Failed to fetch bookings:", e);
        if (e.response?.status === 401) {
            error.value = "您需要登入才能查看預約";
            setTimeout(() => router.push(`/${tenantSlug}/login`), 3000);
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
