<template>
    <PublicLayout>
        <section class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">
                        瀏覽所有車輛
                    </h1>
                    <p class="mt-2 text-lg text-gray-600">
                        找到最適合您的理想車款
                    </p>
                </div>

                <!-- Search and Filter Section -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search Keyword -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >搜尋車款</label
                            >
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="輸入車名或型號"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                @input="applyFilters"
                            />
                        </div>

                        <!-- Pickup Date -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >取車日期</label
                            >
                            <input
                                v-model="filters.pickupDate"
                                type="date"
                                :min="today"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            />
                        </div>

                        <!-- Return Date -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >還車日期</label
                            >
                            <input
                                v-model="filters.returnDate"
                                type="date"
                                :min="filters.pickupDate || today"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            />
                        </div>

                        <!-- Search Button -->
                        <div class="flex items-end">
                            <button
                                @click="applyFilters"
                                class="w-full bg-blue-900 hover:bg-blue-800 text-white px-6 py-2 rounded-lg font-medium transition"
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

                <!-- Vehicle Grid -->
                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                >
                    <VehicleCard
                        v-for="vehicle in filteredVehicles"
                        :key="vehicle.id"
                        :vehicle="vehicle"
                    />
                </div>

                <!-- Pagination -->
                <div
                    v-if="pagination.total > pagination.perPage"
                    class="mt-12 flex justify-center"
                >
                    <nav class="flex items-center space-x-2">
                        <button
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition"
                        >
                            上一頁
                        </button>
                        <span class="px-4 py-2 text-gray-600">
                            第 {{ currentPage }} / {{ totalPages }} 頁
                        </span>
                        <button
                            @click="changePage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition"
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

const loading = ref(false);
const error = ref<string | null>(null);
const allVehicles = ref<any[]>([]);
const currentPage = ref(1);
const pagination = ref({
    total: 0,
    perPage: 15,
    currentPage: 1,
    lastPage: 1,
});

const filters = ref({
    search: queryLocation || "",
    pickupDate: queryPickup || "",
    returnDate: queryReturn || "",
});

const filteredVehicles = computed(() => {
    let result = [...allVehicles.value];

    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase();
        result = result.filter((v) =>
            v.name.toLowerCase().includes(searchLower),
        );
    }

    return result;
});

const totalPages = computed(() => {
    return Math.ceil(filteredVehicles.value.length / pagination.value.perPage);
});

const fetchVehicles = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get("/public/vehicles");
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
};

const changePage = (page: number) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: "smooth" });
};

onMounted(() => {
    fetchVehicles();
});
</script>

<style scoped>
/* Page specific styles */
</style>
