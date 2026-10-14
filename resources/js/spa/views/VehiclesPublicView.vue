<template>
    <PublicLayout>
        <section
            class="py-16 bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header - 增加更開闊的留白與層次 -->
                <div class="mb-12 text-center md:text-left">
                    <h1
                        class="text-4xl md:text-5xl font-bold text-gray-900 tracking-tight"
                    >
                        瀏覽所有車輛
                    </h1>
                    <p class="mt-4 text-xl text-gray-600 max-w-2xl">
                        找到最適合您的理想車款，開啟每一段精彩旅程
                    </p>
                </div>

                <!-- Search and Filter Section - 進一步升級的現代工具列風格 -->
                <div
                    class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl shadow-gray-200/70 p-8 md:p-10 mb-16 ring-1 ring-white/60"
                >
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8"
                    >
                        <!-- Pickup Branch -->
                        <div>
                            <label
                                class="block text-sm font-semibold text-gray-800 mb-3"
                                >取車據點</label
                            >
                            <select
                                v-model="filters.pickup_branch_id"
                                class="w-full px-5 py-4 border-2 border-gray-100 rounded-2xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50/80 focus:bg-white cursor-pointer"
                                :disabled="loadingBranches"
                            >
                                <option value="">請選擇取車據點</option>
                                <option
                                    v-for="branch in branches"
                                    :key="branch.id"
                                    :value="branch.id"
                                >
                                    {{ branch.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Search Keyword -->
                        <div>
                            <label
                                class="block text-sm font-semibold text-gray-800 mb-3"
                                >搜尋車款</label
                            >
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="輸入車名或型號"
                                class="w-full px-5 py-4 border-2 border-gray-100 rounded-2xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50/80 focus:bg-white placeholder:text-gray-400"
                                @input="applyFilters"
                            />
                        </div>

                        <!-- Pickup Date -->
                        <div>
                            <label
                                class="block text-sm font-semibold text-gray-800 mb-3"
                                >取車日期</label
                            >
                            <input
                                v-model="filters.pickupDate"
                                type="date"
                                :min="today"
                                class="w-full px-5 py-4 border-2 border-gray-100 rounded-2xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50/80 focus:bg-white cursor-pointer"
                            />
                        </div>

                        <!-- Return Date -->
                        <div>
                            <label
                                class="block text-sm font-semibold text-gray-800 mb-3"
                                >還車日期</label
                            >
                            <input
                                v-model="filters.returnDate"
                                type="date"
                                :min="filters.pickupDate || today"
                                class="w-full px-5 py-4 border-2 border-gray-100 rounded-2xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50/80 focus:bg-white cursor-pointer"
                            />
                        </div>

                        <!-- Search Button -->
                        <div class="flex items-end">
                            <button
                                @click="applyFilters"
                                class="w-full bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white px-8 py-4 rounded-2xl font-bold transition-all duration-300 shadow-xl shadow-orange-500/30 hover:shadow-2xl hover:shadow-orange-500/40 hover:-translate-y-1 active:scale-[0.98] focus:ring-4 focus:ring-orange-500/30 outline-none"
                            >
                                套用篩選
                            </button>
                        </div>
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
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-20">
                    <p class="text-red-500">{{ error }}</p>
                    <button
                        @click="fetchVehicles"
                        class="mt-4 text-blue-900 underline"
                    >
                        重新載入
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="filteredVehicles.length === 0"
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
                        沒有找到符合條件的車輛
                    </h3>
                    <p class="mt-2 text-gray-500">請嘗試調整您的搜尋條件</p>
                </div>

                <!-- Vehicle Grid - 優化間距與節奏 -->
                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10"
                >
                    <VehicleCard
                        v-for="(vehicle, index) in filteredVehicles"
                        :key="vehicle.id"
                        :vehicle="{ ...vehicle, is_featured: index === 0 }"
                    />
                </div>

                <!-- Pagination - 升級分頁設計 -->
                <div
                    v-if="pagination.total > pagination.perPage"
                    class="mt-20 flex justify-center"
                >
                    <nav
                        class="bg-white rounded-2xl shadow-xl shadow-gray-200/60 p-3 ring-1 ring-gray-100 inline-flex items-center space-x-3"
                    >
                        <button
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-6 py-3 border-0 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed bg-gray-50 hover:bg-blue-50 hover:text-blue-600 text-gray-700 font-medium transition-all duration-300"
                        >
                            上一頁
                        </button>
                        <span class="px-6 py-3 text-gray-600 font-medium">
                            第 {{ currentPage }} / {{ totalPages }} 頁
                        </span>
                        <button
                            @click="changePage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="px-6 py-3 border-0 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed bg-gray-50 hover:bg-blue-50 hover:text-blue-600 text-gray-700 font-medium transition-all duration-300"
                        >
                            下一頁
                        </button>
                    </nav>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import PublicLayout from "../components/Layout/PublicLayout.vue";
import VehicleCard from "../components/VehicleCard.vue";
import api from "../services/api";

const route = useRoute();
const today = ref(new Date().toISOString().split("T")[0]);

// Get query params from URL (passed from home page search)
const queryLocation = route.query.location as string;
const queryPickup = route.query.pickup as string;
const queryReturn = route.query.return as string;
const queryStartTime = route.query.start_at as string;
const queryEndTime = route.query.end_at as string;

const loading = ref(false);
const loadingBranches = ref(false);
const error = ref<string | null>(null);
const allVehicles = ref<any[]>([]);
const branches = ref<any[]>([]);
const currentPage = ref(1);
const pagination = ref({
    total: 0,
    perPage: 15,
    currentPage: 1,
    lastPage: 1,
});

const filters = ref({
    pickup_branch_id: "",
    search: queryLocation || "",
    pickupDate: queryPickup || "",
    returnDate: queryReturn || "",
    startAt: queryStartTime || "",
    endAt: queryEndTime || "",
});

const filteredVehicles = computed(() => {
    let result = [...allVehicles.value];

    // 過濾掉維修中的車輛
    result = result.filter((v) => !v.maintenance_start_at);

    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase();
        result = result.filter(
            (v) =>
                v.name.toLowerCase().includes(searchLower) ||
                (v.brand && v.brand.toLowerCase().includes(searchLower)) ||
                (v.model && v.model.toLowerCase().includes(searchLower)),
        );
    }

    return result;
});

const totalPages = computed(() => {
    return Math.ceil(filteredVehicles.value.length / pagination.value.perPage);
});

const fetchBranches = async () => {
    loadingBranches.value = true;
    try {
        const response = await api.get("/public/branches");
        branches.value = response.data.data;
        // 預設選擇第一個據點
        if (branches.value.length > 0 && !filters.value.pickup_branch_id) {
            filters.value.pickup_branch_id = branches.value[0].id;
        }
    } catch (e) {
        console.error("Failed to fetch branches:", e);
    } finally {
        loadingBranches.value = false;
    }
};

const fetchVehicles = async () => {
    loading.value = true;
    error.value = null;
    try {
        let response;
        // 檢查是否有輸入有效的租期和取車據點
        const hasValidPeriod =
            (filters.value.startAt && filters.value.endAt) ||
            (filters.value.pickupDate && filters.value.returnDate);
        const hasValidBranch = filters.value.pickup_branch_id;

        if (hasValidPeriod && hasValidBranch) {
            let startAt, endAt;

            if (filters.value.startAt && filters.value.endAt) {
                // 使用從首頁傳遞的完整時間
                startAt = filters.value.startAt;
                endAt = filters.value.endAt;
            } else {
                // 自行組裝預設時間
                const start = new Date(filters.value.pickupDate);
                const end = new Date(filters.value.returnDate);
                if (end < start) {
                    // 日期無效，使用一般車輛列表
                    response = await api.get("/public/vehicles");
                    allVehicles.value = response.data.data || [];
                    pagination.value.total =
                        response.data.total || allVehicles.value.length;
                    pagination.value.lastPage = response.data.last_page || 1;
                    return;
                }
                startAt = `${filters.value.pickupDate} 09:00:00`;
                endAt = `${filters.value.returnDate} 17:00:00`;
            }

            // 有有效租期且選擇了取車據點，呼叫可用車輛 API
            if (filters.value.pickup_branch_id) {
                response = await api.get("/public/vehicles/available", {
                    params: {
                        pickup_branch_id: filters.value.pickup_branch_id,
                        start_at: startAt,
                        end_at: endAt,
                    },
                });
            } else {
                // 未選擇取車據點，使用一般車輛列表
                response = await api.get("/public/vehicles");
            }
        } else {
            // 沒有選擇租期或取車據點，使用一般車輛列表
            response = await api.get("/public/vehicles");
        }

        allVehicles.value = response.data.data || [];
        pagination.value.total =
            response.data.total || allVehicles.value.length;
        pagination.value.lastPage = response.data.last_page || 1;
    } catch (e) {
        error.value = "載入車輛資料失敗，請稍後再試";
        console.error("Failed to fetch vehicles:", e);
    } finally {
        loading.value = false;
    }
};

const applyFilters = () => {
    currentPage.value = 1;
    // 當套用篩選時重新獲取車輛資料
    fetchVehicles();
};

const changePage = (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: "smooth" });
};

onMounted(async () => {
    await fetchBranches();
    fetchVehicles();
});
</script>

<style scoped>
/* Page specific styles */
</style>
