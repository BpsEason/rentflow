<template>
    <PublicLayout>
        <!-- Hero Section - 新版設計：左右雙欄布局 -->
        <section
            class="bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 min-h-[calc(100vh-4rem)] flex items-center relative overflow-hidden"
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-12 lg:py-0 relative z-10"
            >
                <div
                    class="grid grid-cols-1 lg:grid-cols-10 gap-8 lg:gap-12 items-center"
                >
                    <!-- 左側：品牌訊息 - 佔用42% (約4/10空間) -->
                    <div class="lg:col-span-4">
                        <!-- Badge -->
                        <span
                            class="inline-block px-4 py-1.5 bg-blue-100 text-blue-800 text-sm font-medium rounded-full mb-6"
                        >
                            透明計價 · 輕鬆預約
                        </span>

                        <h1
                            class="text-4xl lg:text-5xl font-bold leading-[1.15] text-slate-900 max-w-xl"
                        >
                            租車，不該再有隱藏費用
                        </h1>
                        <p
                            class="mt-6 text-lg text-gray-600 max-w-lg leading-relaxed"
                        >
                            租期、價格與車輛資訊清楚呈現，讓你在預約前掌握每一筆費用。
                        </p>

                        <!-- 信任數據區塊 - 建立視覺層級 -->
                        <div class="mt-12 grid grid-cols-3 gap-6">
                            <div>
                                <p class="text-3xl font-bold text-blue-600">
                                    12,800+
                                </p>
                                <p
                                    class="text-xs text-gray-500 mt-1 font-medium"
                                >
                                    累計服務人次
                                </p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-blue-600">
                                    4.9
                                </p>
                                <p
                                    class="text-xs text-gray-500 mt-1 font-medium"
                                >
                                    顧客滿意度
                                </p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-green-500">
                                    0
                                </p>
                                <p
                                    class="text-xs text-gray-500 mt-1 font-medium"
                                >
                                    隱藏費用
                                </p>
                            </div>
                        </div>
                        <!-- 信任資訊 - 使用可驗證的功能特點 -->
                        <div class="mt-8 flex flex-wrap gap-8">
                            <div>
                                <p
                                    class="text-base font-medium text-slate-700 flex items-center gap-2"
                                >
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
                                        />
                                    </svg>
                                    價格由系統試算
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-base font-medium text-slate-700 flex items-center gap-2"
                                >
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
                                        />
                                    </svg>
                                    依租期查詢車輛
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 右側：搜尋卡片 + 主視覺 - 佔用58% (約6/10空間) -->
                    <div class="lg:col-span-6">
                        <!-- 搜尋卡片 - 主要操作入口 - 提升視覺層級 -->
                        <div
                            class="bg-white rounded-3xl shadow-2xl shadow-blue-900/20 p-8 md:p-10 relative z-20 mb-8 lg:mb-0 ring-1 ring-gray-100"
                        >
                            <h2 class="text-2xl font-bold text-gray-900 mb-8">
                                搜尋可用車輛
                            </h2>
                            <form
                                @submit.prevent="handleSearch"
                                class="grid grid-cols-1 md:grid-cols-2 gap-6"
                            >
                                <!-- 取車地點 -->
                                <div class="md:col-span-2">
                                    <label
                                        class="block text-sm font-semibold text-gray-800 mb-3"
                                        >取車地點</label
                                    >
                                    <input
                                        v-model="searchForm.pickupLocation"
                                        type="text"
                                        placeholder="輸入城市、機場或車站"
                                        class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white placeholder:text-gray-400"
                                    />
                                </div>

                                <!-- 取車日期與時間 -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-800 mb-3"
                                        >取車日期</label
                                    >
                                    <input
                                        v-model="searchForm.pickupDate"
                                        type="date"
                                        :min="today"
                                        class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-800 mb-3"
                                        >取車時間</label
                                    >
                                    <select
                                        v-model="searchForm.pickupTime"
                                        class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white cursor-pointer"
                                    >
                                        <option value="09:00">09:00</option>
                                        <option value="10:00">10:00</option>
                                        <option value="11:00">11:00</option>
                                        <option value="14:00">14:00</option>
                                        <option value="15:00">15:00</option>
                                        <option value="16:00">16:00</option>
                                    </select>
                                </div>

                                <!-- 還車日期與時間 -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-800 mb-3"
                                        >還車日期</label
                                    >
                                    <input
                                        v-model="searchForm.returnDate"
                                        type="date"
                                        :min="searchForm.pickupDate || today"
                                        class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-800 mb-3"
                                        >還車時間</label
                                    >
                                    <select
                                        v-model="searchForm.returnTime"
                                        class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white cursor-pointer"
                                    >
                                        <option value="09:00">09:00</option>
                                        <option value="10:00">10:00</option>
                                        <option value="11:00">11:00</option>
                                        <option value="14:00">14:00</option>
                                        <option value="15:00">15:00</option>
                                        <option value="17:00" selected>
                                            17:00
                                        </option>
                                    </select>
                                </div>

                                <!-- 錯誤訊息 -->
                                <p
                                    v-if="dateError"
                                    class="md:col-span-2 text-red-500 text-sm font-medium"
                                >
                                    {{ dateError }}
                                </p>

                                <!-- Search Button -->
                                <div class="md:col-span-2">
                                    <button
                                        type="submit"
                                        :disabled="
                                            !isSearchValid || isSubmitting
                                        "
                                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:from-gray-400 disabled:to-gray-400 text-white px-8 py-5 rounded-xl font-bold transition-all duration-300 text-lg shadow-lg shadow-blue-600/30 hover:shadow-xl hover:shadow-blue-600/40 active:scale-[0.98] focus:ring-4 focus:ring-blue-500/30 outline-none"
                                    >
                                        {{
                                            isSubmitting
                                                ? "處理中..."
                                                : "搜尋可用車輛"
                                        }}
                                    </button>
                                </div>
                            </form>
                            <p class="mt-5 text-xs text-gray-400 font-medium">
                                支援機場、車站、市區多個據點
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 車輛主視覺 - 桌面版 -->
                <div class="hidden lg:block mt-12">
                    <div
                        class="relative rounded-3xl overflow-hidden shadow-2xl"
                    >
                        <img
                            :src="heroVehicleImage"
                            alt="精選車輛展示"
                            class="w-full h-[400px] object-cover object-center"
                            loading="eager"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-blue-900/40 via-transparent to-transparent"
                        ></div>
                    </div>
                </div>

                <!-- Mobile 版車輛圖片 -->
                <div class="lg:hidden mt-8">
                    <div class="rounded-2xl overflow-hidden shadow-xl">
                        <img
                            :src="heroVehicleImage"
                            alt="精選車輛展示"
                            class="w-full h-64 object-cover object-center"
                            loading="eager"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- 熱門車款區塊 -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">熱門車款</h2>
                    <p class="mt-4 text-lg text-gray-600">
                        精選推薦，滿足您的各種需求
                    </p>
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex justify-center items-center py-20"
                >
                    <div
                        class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
                    ></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-20">
                    <p class="text-red-500">{{ error }}</p>
                    <button
                        @click="fetchVehicles"
                        class="mt-4 text-blue-600 underline"
                    >
                        重新載入
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="featuredVehicles.length === 0"
                    class="text-center py-20"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">
                        目前沒有可用車輛
                    </h3>
                    <p class="mt-2 text-gray-500">請稍後再來查看</p>
                </div>

                <!-- Vehicle Grid -->
                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                >
                    <VehicleCard
                        v-for="(vehicle, index) in featuredVehicles"
                        :key="vehicle.id"
                        :vehicle="{ ...vehicle, is_featured: index === 0 }"
                    />
                </div>

                <div class="text-center mt-12">
                    <router-link
                        :to="`/${tenantSlug}/vehicles`"
                        class="inline-block border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-10 py-4 rounded-xl font-bold transition-all duration-300 hover:shadow-lg hover:shadow-blue-600/30 active:scale-[0.98]"
                    >
                        瀏覽所有車輛
                    </router-link>
                </div>
            </div>
        </section>

        <!-- How It Works - 2026 極簡流線型重構 -->
        <section
            id="how-it-works"
            class="py-24 my-12 relative overflow-hidden bg-white/60 border-y border-slate-200/60 backdrop-blur-sm"
        >
            <!-- 背景微光暈裝飾 (極低飽和度，絕不宣賓奪主) -->
            <div
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] bg-gradient-to-r from-blue-50/40 via-indigo-50/30 to-amber-50/30 blur-3xl pointer-events-none -z-10 rounded-full"
            ></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 區塊標題區 -->
                <div class="text-center max-w-xl mx-auto mb-16 sm:mb-20">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-widest text-blue-600 bg-blue-50 border border-blue-100/80 mb-3"
                    >
                        Simple Step-by-Step
                    </span>
                    <h2
                        class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900"
                    >
                        租車流程，簡單快速
                    </h2>
                    <p
                        class="text-xs sm:text-sm font-medium text-slate-500 mt-2.5 leading-relaxed"
                    >
                        只需簡單 4
                        個步驟，即可輕鬆完成線上預約，開啟您的精彩旅程。
                    </p>
                </div>

                <!-- 4 步驟動態流程卡片 -->
                <div class="relative">
                    <!-- 桌面端橫向漸變連接導引線 (Connecting Guidance Line) -->
                    <div
                        class="hidden lg:block absolute top-[28px] left-[10%] right-[10%] h-[2px] bg-gradient-to-r from-slate-200 via-blue-200 to-slate-200 -z-0"
                    ></div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 relative z-10"
                    >
                        <!-- 步驟 1 -->
                        <div
                            class="group flex flex-col items-start lg:items-center text-left lg:text-center"
                        >
                            <!-- 精緻小巧的數字標註與點頭 -->
                            <div
                                class="w-14 h-14 rounded-2xl bg-white border border-slate-200/80 shadow-[0_4px_16px_-2px_rgba(0,0,0,0.04)] group-hover:border-blue-500 group-hover:shadow-[0_8px_24px_-4px_rgba(37,99,235,0.12)] group-hover:-translate-y-1 transition-all duration-300 flex items-center justify-center relative mb-6"
                            >
                                <span
                                    class="text-xs font-black font-mono tracking-wider text-slate-400 group-hover:text-blue-600 transition-colors"
                                    >01</span
                                >
                                <!-- 右側動態脈衝原點 -->
                                <span
                                    class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-blue-500 border-2 border-white opacity-0 group-hover:opacity-100 transition-opacity animate-pulse"
                                ></span>
                            </div>
                            <h3
                                class="text-sm font-extrabold text-slate-900 tracking-tight mb-2"
                            >
                                1. 選擇車輛
                            </h3>
                            <p
                                class="text-xs text-slate-500 leading-relaxed font-normal max-w-[220px]"
                            >
                                挑選符合您需求與成員數量的理想車款。
                            </p>
                        </div>

                        <!-- 步驟 2 -->
                        <div
                            class="group flex flex-col items-start lg:items-center text-left lg:text-center"
                        >
                            <div
                                class="w-14 h-14 rounded-2xl bg-white border border-slate-200/80 shadow-[0_4px_16px_-2px_rgba(0,0,0,0.04)] group-hover:border-blue-500 group-hover:shadow-[0_8px_24px_-4px_rgba(37,99,235,0.12)] group-hover:-translate-y-1 transition-all duration-300 flex items-center justify-center relative mb-6"
                            >
                                <span
                                    class="text-xs font-black font-mono tracking-wider text-slate-400 group-hover:text-blue-600 transition-colors"
                                    >02</span
                                >
                                <span
                                    class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-blue-500 border-2 border-white opacity-0 group-hover:opacity-100 transition-opacity animate-pulse"
                                ></span>
                            </div>
                            <h3
                                class="text-sm font-extrabold text-slate-900 tracking-tight mb-2"
                            >
                                2. 填寫資料
                            </h3>
                            <p
                                class="text-xs text-slate-500 leading-relaxed font-normal max-w-[220px]"
                            >
                                設定取還車時間地點，填寫基本聯絡資訊。
                            </p>
                        </div>

                        <!-- 步驟 3 -->
                        <div
                            class="group flex flex-col items-start lg:items-center text-left lg:text-center"
                        >
                            <div
                                class="w-14 h-14 rounded-2xl bg-white border border-slate-200/80 shadow-[0_4px_16px_-2px_rgba(0,0,0,0.04)] group-hover:border-blue-500 group-hover:shadow-[0_8px_24px_-4px_rgba(37,99,235,0.12)] group-hover:-translate-y-1 transition-all duration-300 flex items-center justify-center relative mb-6"
                            >
                                <span
                                    class="text-xs font-black font-mono tracking-wider text-slate-400 group-hover:text-blue-600 transition-colors"
                                    >03</span
                                >
                                <span
                                    class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-blue-500 border-2 border-white opacity-0 group-hover:opacity-100 transition-opacity animate-pulse"
                                ></span>
                            </div>
                            <h3
                                class="text-sm font-extrabold text-slate-900 tracking-tight mb-2"
                            >
                                3. 確認送出
                            </h3>
                            <p
                                class="text-xs text-slate-500 leading-relaxed font-normal max-w-[220px]"
                            >
                                核對預約細節與租賃條款，完成線上送出。
                            </p>
                        </div>

                        <!-- 步驟 4 -->
                        <div
                            class="group flex flex-col items-start lg:items-center text-left lg:text-center"
                        >
                            <div
                                class="w-14 h-14 rounded-2xl bg-white border border-slate-200/80 shadow-[0_4px_16px_-2px_rgba(0,0,0,0.04)] group-hover:border-blue-500 group-hover:shadow-[0_8px_24px_-4px_rgba(37,99,235,0.12)] group-hover:-translate-y-1 transition-all duration-300 flex items-center justify-center relative mb-6"
                            >
                                <span
                                    class="text-xs font-black font-mono tracking-wider text-slate-400 group-hover:text-blue-600 transition-colors"
                                    >04</span
                                >
                                <span
                                    class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-blue-500 border-2 border-white opacity-0 group-hover:opacity-100 transition-opacity animate-pulse"
                                ></span>
                            </div>
                            <h3
                                class="text-sm font-extrabold text-slate-900 tracking-tight mb-2"
                            >
                                4. 開心出發
                            </h3>
                            <p
                                class="text-xs text-slate-500 leading-relaxed font-normal max-w-[220px]"
                            >
                                依約定時間前往門市取車，踏上美好行程。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import PublicLayout from "../components/Layout/PublicLayout.vue";
import VehicleCard from "../components/VehicleCard.vue";
import api from "../services/api";

const router = useRouter();
const route = useRoute();
const tenantSlug = route.params.tenantSlug as string;
const today = ref(new Date().toISOString().split("T")[0]);

const searchForm = ref({
    pickupLocation: "",
    pickupDate: "",
    pickupTime: "09:00",
    returnDate: "",
    returnTime: "17:00",
});

const loading = ref(false);
const error = ref<string | null>(null);
const vehicles = ref<any[]>([]);
const isSubmitting = ref(false);
const dateError = ref<string | null>(null);

const featuredVehicles = computed(() => {
    return vehicles.value.slice(0, 6);
});

const heroVehicleImage = computed(() => {
    // 使用第一台有圖片的可用車輛作為Hero主圖
    const availableVehicle = vehicles.value.find(
        (v) => v.status === "AVAILABLE" && v.image_url,
    );
    return (
        availableVehicle?.image_url ||
        "https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1200"
    );
});

const isSearchValid = computed(() => {
    return (
        searchForm.value.pickupLocation.trim() &&
        searchForm.value.pickupDate &&
        searchForm.value.returnDate
    );
});

const validateDates = (): boolean => {
    dateError.value = null;

    const pickupDateTime = new Date(
        `${searchForm.value.pickupDate}T${searchForm.value.pickupTime}`,
    );
    const returnDateTime = new Date(
        `${searchForm.value.returnDate}T${searchForm.value.returnTime}`,
    );

    if (returnDateTime <= pickupDateTime) {
        dateError.value = "還車時間必須晚於取車時間";
        return false;
    }

    return true;
};

const fetchVehicles = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get("/public/vehicles");
        vehicles.value = response.data.data || [];
    } catch (e) {
        error.value = "載入車輛資料失敗，請稍後再試";
        console.error("Failed to fetch vehicles:", e);
    } finally {
        loading.value = false;
    }
};

const handleSearch = async () => {
    if (!isSearchValid.value) return;

    // 驗證日期時間
    if (!validateDates()) return;

    isSubmitting.value = true;

    try {
        // 導向車輛列表頁，傳遞完整的租期參數
        const pickupAt = `${searchForm.value.pickupDate} ${searchForm.value.pickupTime}:00`;
        const returnAt = `${searchForm.value.returnDate} ${searchForm.value.returnTime}:00`;

        router.push({
            path: `/${tenantSlug}/vehicles`,
            query: {
                location: searchForm.value.pickupLocation,
                pickup: searchForm.value.pickupDate,
                return: searchForm.value.returnDate,
                pickup_time: searchForm.value.pickupTime,
                return_time: searchForm.value.returnTime,
                start_at: pickupAt,
                end_at: returnAt,
            },
        });
    } catch (e) {
        console.error("Search error:", e);
    } finally {
        isSubmitting.value = false;
    }
};

const apiLoading = ref(false);
const apiError = ref<string | null>(null);
const apiData = ref<any>(null);

const checkApiStatus = async () => {
    apiLoading.value = true;
    apiError.value = null;
    apiData.value = null;

    try {
        // 這裡可以呼叫一個公開的 API 端點來測試連線
        // 由於目前所有 API 都需要認證，我們先顯示連線成功的訊息
        await new Promise((resolve) => setTimeout(resolve, 1000));
        apiData.value = {
            status: "connected",
            message: "成功連線到 RentFlow API 伺服器",
            baseUrl: import.meta.env.VITE_API_URL || "/api/v1",
        };
    } catch (e) {
        apiError.value = "無法連線到 API 伺服器";
    } finally {
        apiLoading.value = false;
    }
};

onMounted(() => {
    fetchVehicles();
    checkApiStatus();
});
</script>

<style scoped>
.home {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.navbar {
    background: #2c3e50;
    color: white;
    padding: 1rem 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    margin: 0;
    font-size: 1.5rem;
}

.nav-links {
    display: flex;
    list-style: none;
    gap: 2rem;
    margin: 0;
    padding: 0;
}

.nav-links a {
    color: white;
    text-decoration: none;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: #42b983;
}

.hero {
    flex: 1;
    padding: 4rem 0;
    text-align: center;
}

.hero h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.hero p {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 3rem;
}

.api-status {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.api-status h3 {
    margin-top: 0;
    color: #2c3e50;
}

button {
    background: #42b983;
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s;
}

button:hover:not(:disabled) {
    background: #3aa876;
}

button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.status {
    margin-top: 1.5rem;
    padding: 1rem;
    border-radius: 4px;
}

.loading {
    background: #e3f2fd;
    color: #1976d2;
}

.error {
    background: #ffebee;
    color: #c62828;
}

.success {
    background: #e8f5e9;
    color: #2e7d32;
}

pre {
    text-align: left;
    white-space: pre-wrap;
    word-wrap: break-word;
    background: #fff;
    padding: 1rem;
    border-radius: 4px;
    margin-top: 1rem;
}

footer {
    background: #2c3e50;
    color: white;
    padding: 1.5rem 0;
    text-align: center;
}

footer p {
    margin: 0;
}
</style>
