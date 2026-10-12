<template>
    <PublicLayout>
        <!-- Hero Section - 精確版面規格：完整首屏高度，專業布局 -->
        <section
            class="bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 min-h-[calc(100vh-4rem)] flex items-center relative overflow-hidden"
        >
            <!-- 背景裝飾 - 精確定位 -->
            <div
                class="absolute top-0 right-0 w-1/2 h-full bg-blue-900/5 -skew-x-12 transform origin-top-right hidden lg:block"
            ></div>

            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-12 lg:py-0 relative z-10"
            >
                <div
                    class="grid grid-cols-1 lg:grid-cols-10 gap-8 lg:gap-12 items-center"
                >
                    <!-- 左側：文字與搜尋表單 - 佔用45% (約5/10空間) -->
                    <div class="lg:col-span-5">
                        <!-- 小Badge -->
                        <span
                            class="inline-block px-4 py-1.5 bg-amber-100 text-amber-800 text-sm font-medium rounded-full mb-6"
                        >
                            台灣最透明的自駕租車
                        </span>

                        <h1
                            class="text-5xl lg:text-6xl font-bold leading-[1.1] text-slate-900 max-w-2xl"
                        >
                            租車，就該這麼簡單
                        </h1>
                        <p
                            class="mt-6 text-xl text-gray-600 max-w-xl leading-relaxed"
                        >
                            透明價格、即時庫存、全程保險。從市區通勤到長途旅行，3
                            分鐘完成預約。
                        </p>

                        <!-- 信任數據列 -->
                        <div class="mt-8 flex flex-wrap gap-12">
                            <div>
                                <p class="text-3xl font-bold text-slate-900">
                                    12,800+
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    完成行程
                                </p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-slate-900">
                                    4.9
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    用戶評分
                                </p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-slate-900">
                                    0
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    隱藏費用
                                </p>
                            </div>
                        </div>

                        <!-- 搜尋表單 - 核心元件 -->
                        <div
                            class="mt-12 bg-white rounded-2xl shadow-2xl p-6 md:p-8 max-w-2xl relative z-20"
                        >
                            <h2 class="text-xl font-bold text-gray-900 mb-6">
                                預約您的車輛
                            </h2>
                            <form
                                @submit.prevent="handleSearch"
                                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end"
                            >
                                <!-- 取車地點 -->
                                <div class="md:col-span-1">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                        >取車地點</label
                                    >
                                    <input
                                        v-model="searchForm.pickupLocation"
                                        type="text"
                                        placeholder="輸入城市、機場或車站"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    />
                                </div>

                                <!-- 取車日期 -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                        >取車日期</label
                                    >
                                    <input
                                        v-model="searchForm.pickupDate"
                                        type="date"
                                        :min="today"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    />
                                </div>

                                <!-- 還車日期 -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                        >還車日期</label
                                    >
                                    <input
                                        v-model="searchForm.returnDate"
                                        type="date"
                                        :min="searchForm.pickupDate || today"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                    />
                                </div>

                                <!-- Search Button -->
                                <div>
                                    <button
                                        type="submit"
                                        :disabled="!isSearchValid"
                                        class="w-full bg-amber-500 hover:bg-amber-600 disabled:bg-gray-400 text-white px-6 py-3.5 rounded-lg font-semibold transition whitespace-nowrap"
                                    >
                                        搜尋車輛
                                    </button>
                                </div>
                            </form>
                            <p class="mt-4 text-xs text-gray-500">
                                支援機場、車站、市區超過 120 個據點
                            </p>
                        </div>

                        <div class="mt-6">
                            <router-link
                                :to="`/${tenantSlug}/vehicles`"
                                class="inline-flex items-center gap-2 text-blue-900 hover:text-blue-700 font-medium transition text-lg"
                            >
                                瀏覽所有車輛
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
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </router-link>
                        </div>
                    </div>

                    <!-- 右側：主車輛圖片 - 佔用55% (約5/10空間)，精確比例裁切 -->
                    <div class="relative hidden lg:block lg:col-span-5">
                        <div
                            class="relative rounded-3xl overflow-hidden shadow-2xl"
                        >
                            <img
                                :src="heroVehicleImage"
                                alt="精選車輛展示"
                                class="w-full h-[500px] object-cover object-center"
                                loading="eager"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-blue-900/30 via-transparent to-transparent"
                            ></div>
                        </div>
                        <!-- 裝飾性元素 - 精確定位 -->
                        <div
                            class="absolute -bottom-8 -left-8 w-80 h-80 bg-blue-200 rounded-full -z-10 blur-3xl opacity-50"
                        ></div>
                        <div
                            class="absolute -top-8 -right-8 w-64 h-64 bg-amber-200 rounded-full -z-10 blur-3xl opacity-50"
                        ></div>
                    </div>

                    <!-- Mobile 版車輛圖片 -->
                    <div class="lg:hidden">
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
            </div>
        </section>

        <!-- Featured Vehicles -->
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

                <!-- Vehicle Grid -->
                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                >
                    <VehicleCard
                        v-for="vehicle in featuredVehicles"
                        :key="vehicle.id"
                        :vehicle="vehicle"
                    />
                </div>

                <div class="text-center mt-12">
                    <router-link
                        :to="`/${tenantSlug}/vehicles`"
                        class="inline-block border-2 border-blue-900 text-blue-900 hover:bg-blue-900 hover:text-white px-8 py-3 rounded-lg font-semibold transition"
                    >
                        瀏覽所有車輛
                    </router-link>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section id="how-it-works" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900">
                        租車流程簡單又快速
                    </h2>
                    <p class="mt-4 text-lg text-gray-600">四個步驟，輕鬆取車</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div
                            class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4"
                        >
                            <span class="text-2xl font-bold text-blue-900"
                                >1</span
                            >
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            選擇車輛
                        </h3>
                        <p class="text-gray-600">
                            瀏覽我們的車輛列表，選擇最適合您的車款
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4"
                        >
                            <span class="text-2xl font-bold text-blue-900"
                                >2</span
                            >
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            確認租期
                        </h3>
                        <p class="text-gray-600">
                            選擇您的取車和還車日期，查看價格明細
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4"
                        >
                            <span class="text-2xl font-bold text-blue-900"
                                >3</span
                            >
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            提交預約
                        </h3>
                        <p class="text-gray-600">
                            填寫基本資料，送出您的預約申請
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4"
                        >
                            <span class="text-2xl font-bold text-blue-900"
                                >4</span
                            >
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            取車出發
                        </h3>
                        <p class="text-gray-600">
                            預約確認後，依約定時間取車展開旅程
                        </p>
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
    returnDate: "",
});

const loading = ref(false);
const error = ref<string | null>(null);
const vehicles = ref<any[]>([]);

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

const handleSearch = () => {
    if (!isSearchValid.value) return;

    // Navigate to vehicles page with search params
    router.push({
        path: `/${tenantSlug}/vehicles`,
        query: {
            location: searchForm.value.pickupLocation,
            pickup: searchForm.value.pickupDate,
            return: searchForm.value.returnDate,
        },
    });
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
