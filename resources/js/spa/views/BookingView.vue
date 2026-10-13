<template>
    <PublicLayout>
        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-6">
                    <router-link
                        :to="`/${tenantSlug}/vehicles/${vehicleId}`"
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
                        返回車輛詳情
                    </router-link>
                </div>

                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">完成預約</h1>
                    <p class="mt-2 text-lg text-gray-600">
                        請填寫以下資料以完成您的租車預約
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Booking Form - Left Column -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Loading State -->
                        <div
                            v-if="loading"
                            class="flex justify-center items-center py-20 bg-white rounded-xl shadow-md"
                        >
                            <div
                                class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"
                            ></div>
                        </div>

                        <!-- Error State -->
                        <div
                            v-else-if="error"
                            class="text-center py-20 bg-white rounded-xl shadow-md"
                        >
                            <p class="text-red-500">{{ error }}</p>
                            <button
                                @click="fetchVehicle"
                                class="mt-4 text-blue-900 underline"
                            >
                                重新載入
                            </button>
                        </div>

                        <!-- Booking Form -->
                        <template v-else>
                            <!-- Customer Information - 無論是否登入都顯示，但如果已登入且有現有客戶資料可以預填 -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-xl font-semibold text-gray-900 mb-6 flex items-center"
                                >
                                    <span
                                        class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center text-sm mr-3"
                                        >1</span
                                    >
                                    聯絡資訊
                                </h2>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >姓名
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            placeholder="請輸入您的真實姓名"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >手機號碼
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            v-model="form.phone"
                                            type="tel"
                                            placeholder="請輸入手機號碼"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >電子郵件
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            placeholder="請輸入電子郵件"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >身份證字號
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            v-model="form.id_number"
                                            type="text"
                                            placeholder="請輸入身份證字號"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Driver Information - 無論是否登入都顯示 -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-xl font-semibold text-gray-900 mb-6 flex items-center"
                                >
                                    <span
                                        class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center text-sm mr-3"
                                        >2</span
                                    >
                                    駕駛人資訊
                                </h2>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >駕照號碼
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            v-model="form.license_number"
                                            type="text"
                                            placeholder="請輸入駕駛執照號碼"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >駕照有效日期
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            v-model="form.license_expiry"
                                            type="date"
                                            :min="today"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >通訊地址</label
                                        >
                                        <input
                                            v-model="form.address"
                                            type="text"
                                            placeholder="請輸入通訊地址"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Pickup & Return Information -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-xl font-semibold text-gray-900 mb-6 flex items-center"
                                >
                                    <span
                                        class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center text-sm mr-3"
                                        >3</span
                                    >
                                    取還車資訊
                                </h2>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >取車日期</label
                                        >
                                        <p
                                            class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700"
                                        >
                                            {{ pickupDate }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >取車時間</label
                                        >
                                        <p
                                            class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700"
                                        >
                                            {{ pickupTime }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >還車日期</label
                                        >
                                        <p
                                            class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700"
                                        >
                                            {{ returnDate }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                            >還車時間</label
                                        >
                                        <p
                                            class="px-4 py-2 bg-gray-100 rounded-lg text-gray-700"
                                        >
                                            {{ returnTime }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Services -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-xl font-semibold text-gray-900 mb-6 flex items-center"
                                >
                                    <span
                                        class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center text-sm mr-3"
                                        >4</span
                                    >
                                    加購服務
                                </h2>

                                <div class="space-y-4">
                                    <label
                                        class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                    >
                                        <input
                                            v-model="
                                                form.additional_services.gps
                                            "
                                            type="checkbox"
                                            class="mt-1 w-5 h-5 text-blue-900 rounded focus:ring-blue-500"
                                        />
                                        <div class="ml-3">
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                GPS 導航加購
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                每日 NT$ 100，提供即時導航服務
                                            </p>
                                        </div>
                                        <span
                                            class="ml-auto font-medium text-gray-900"
                                            >NT$ 100/日</span
                                        >
                                    </label>

                                    <label
                                        class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                    >
                                        <input
                                            v-model="
                                                form.additional_services
                                                    .child_seat
                                            "
                                            type="checkbox"
                                            class="mt-1 w-5 h-5 text-blue-900 rounded focus:ring-blue-500"
                                        />
                                        <div class="ml-3">
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                兒童安全座椅
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                每日 NT$
                                                150，符合安全標準的兒童座椅
                                            </p>
                                        </div>
                                        <span
                                            class="ml-auto font-medium text-gray-900"
                                            >NT$ 150/日</span
                                        >
                                    </label>

                                    <label
                                        class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                    >
                                        <input
                                            v-model="
                                                form.additional_services
                                                    .additional_driver
                                            "
                                            type="checkbox"
                                            class="mt-1 w-5 h-5 text-blue-900 rounded focus:ring-blue-500"
                                        />
                                        <div class="ml-3">
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                額外駕駛人
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                一次 NT$
                                                500，可增加一位額外駕駛人
                                            </p>
                                        </div>
                                        <span
                                            class="ml-auto font-medium text-gray-900"
                                            >NT$ 500</span
                                        >
                                    </label>
                                </div>
                            </div>

                            <!-- Terms & Conditions -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2
                                    class="text-xl font-semibold text-gray-900 mb-6"
                                >
                                    注意事項
                                </h2>
                                <ul class="space-y-3 text-gray-600">
                                    <li class="flex items-start">
                                        <svg
                                            class="w-5 h-5 text-blue-900 mr-2 mt-0.5 flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        請攜帶有效身份證件及駕駛執照正本取車
                                    </li>
                                    <li class="flex items-start">
                                        <svg
                                            class="w-5 h-5 text-blue-900 mr-2 mt-0.5 flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        取車時需繳納押金，還車後無損傷將全數退還
                                    </li>
                                    <li class="flex items-start">
                                        <svg
                                            class="w-5 h-5 text-blue-900 mr-2 mt-0.5 flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        若需取消預約，請於取車前24小時通知，否則將收取首日租金作為手續費
                                    </li>
                                    <li class="flex items-start">
                                        <svg
                                            class="w-5 h-5 text-blue-900 mr-2 mt-0.5 flex-shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        延遲還車將依每小時 NT$ 200 計算滯納金
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </div>

                    <!-- Price Summary Sidebar -->
                    <div class="lg:col-span-1">
                        <div
                            class="bg-white rounded-xl shadow-md p-6 sticky top-8"
                        >
                            <h2 class="text-xl font-bold text-gray-900 mb-6">
                                訂購明細
                            </h2>

                            <!-- Vehicle Info -->
                            <div class="pb-6 border-b border-gray-200 mb-6">
                                <img
                                    :src="vehicle.image_url"
                                    :alt="vehicle.name"
                                    class="w-full h-32 object-cover rounded-lg mb-4"
                                />
                                <h3 class="font-semibold text-gray-900">
                                    {{ vehicle.name }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ vehicle.brand }} {{ vehicle.model }}
                                </p>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-gray-600">
                                    <span>基本租金 ({{ rentalDays }} 天)</span>
                                    <span
                                        >NT$
                                        {{ basePrice.toLocaleString() }}</span
                                    >
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>服務費</span>
                                    <span
                                        >NT$
                                        {{ serviceFee.toLocaleString() }}</span
                                    >
                                </div>
                                <div
                                    v-if="additionalServicesTotal > 0"
                                    class="flex justify-between text-gray-600"
                                >
                                    <span>加購服務</span>
                                    <span
                                        >NT$
                                        {{
                                            additionalServicesTotal.toLocaleString()
                                        }}</span
                                    >
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="border-t border-gray-200 pt-4 mb-6">
                                <div class="flex justify-between items-center">
                                    <span
                                        class="text-lg font-semibold text-gray-900"
                                        >總計</span
                                    >
                                    <span
                                        class="text-2xl font-bold text-blue-900"
                                        >NT$
                                        {{ finalTotal.toLocaleString() }}</span
                                    >
                                </div>
                            </div>

                            <!-- Agree Terms and Submit -->
                            <div class="space-y-4">
                                <label class="flex items-start cursor-pointer">
                                    <input
                                        v-model="form.agreed_to_terms"
                                        type="checkbox"
                                        class="mt-1 w-5 h-5 text-blue-900 rounded focus:ring-blue-500"
                                    />
                                    <span class="ml-3 text-sm text-gray-600">
                                        我已閱讀並同意
                                        <a
                                            href="#"
                                            class="text-blue-900 underline"
                                            >服務條款</a
                                        >
                                        及
                                        <a
                                            href="#"
                                            class="text-blue-900 underline"
                                            >隱私權政策</a
                                        >
                                    </span>
                                </label>

                                <button
                                    @click="submitBooking"
                                    :disabled="!canSubmit || submitting"
                                    class="w-full bg-blue-900 hover:bg-blue-800 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-medium transition flex items-center justify-center"
                                >
                                    <span
                                        v-if="submitting"
                                        class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"
                                    ></span>
                                    確認預約
                                </button>

                                <!-- Form Errors -->
                                <div
                                    v-if="formErrors.length > 0"
                                    class="p-3 bg-red-50 border border-red-200 rounded-lg"
                                >
                                    <p
                                        v-for="(error, idx) in formErrors"
                                        :key="idx"
                                        class="text-red-600 text-sm"
                                    >
                                        {{ error }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
const vehicleId = route.params.vehicleId as string;

// 檢查用戶是否登入
const token = localStorage.getItem("token");
const isLoggedIn = ref(!!token);

// Get query params from previous page
const pickupDate = route.query.pickupDate as string;
const pickupTime = route.query.pickupTime as string;
const returnDate = route.query.returnDate as string;
const returnTime = route.query.returnTime as string;
const queryTotalPrice = route.query.totalPrice as string;

const loading = ref(true);
const submitting = ref(false);
const error = ref<string | null>(null);
const vehicle = ref<any>({});
const today = ref(new Date().toISOString().split("T")[0]);

// Form data
const form = ref({
    name: "",
    phone: "",
    email: "",
    id_number: "",
    license_number: "",
    license_expiry: "",
    address: "",
    additional_services: {
        gps: false,
        child_seat: false,
        additional_driver: false,
    },
    agreed_to_terms: false,
});

const formErrors = ref<string[]>([]);

// Calculate rental days
const rentalDays = computed(() => {
    if (!pickupDate || !returnDate) return 1;
    const start = new Date(pickupDate);
    const end = new Date(returnDate);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays || 1;
});

// Calculate prices
const dailyRate = computed(() => {
    return vehicle.value.pricing?.daily_rate || 2000;
});

const basePrice = computed(() => {
    return rentalDays.value * dailyRate.value;
});

const serviceFee = computed(() => {
    return Math.round(basePrice.value * 0.05);
});

const additionalServicesTotal = computed(() => {
    let total = 0;
    if (form.value.additional_services.gps) total += 100 * rentalDays.value;
    if (form.value.additional_services.child_seat)
        total += 150 * rentalDays.value;
    if (form.value.additional_services.additional_driver) total += 500;
    return total;
});

const finalTotal = computed(() => {
    return basePrice.value + serviceFee.value + additionalServicesTotal.value;
});

const canSubmit = computed(() => {
    return form.value.agreed_to_terms && !loading.value && !error.value;
});

const fetchVehicle = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get(`/public/vehicles/${vehicleId}`);
        vehicle.value = response.data.data;
    } catch (e) {
        error.value = "載入車輛資料失敗，請稍後再試";
        console.error("Failed to fetch vehicle:", e);
    } finally {
        loading.value = false;
    }
};

const validateForm = () => {
    formErrors.value = [];

    // 所有用戶都需要填寫個人資料欄位，已登入用戶如果在當前租戶沒有客戶資料也需要填寫
    if (!form.value.name.trim()) formErrors.value.push("請填寫姓名");
    if (!form.value.phone.trim()) formErrors.value.push("請填寫手機號碼");
    if (!form.value.email.trim()) formErrors.value.push("請填寫電子郵件");
    if (!form.value.id_number.trim()) formErrors.value.push("請填寫身份證字號");
    if (!form.value.license_number.trim())
        formErrors.value.push("請填寫駕照號碼");
    if (!form.value.license_expiry) formErrors.value.push("請填寫駕照有效日期");

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (form.value.email && !emailRegex.test(form.value.email)) {
        formErrors.value.push("請輸入有效的電子郵件格式");
    }

    // Phone validation (Taiwan mobile)
    const phoneRegex = /^09\d{8}$/;
    if (form.value.phone && !phoneRegex.test(form.value.phone)) {
        formErrors.value.push("請輸入有效的手機號碼格式");
    }

    // 所有用戶都需要同意服務條款
    if (!form.value.agreed_to_terms)
        formErrors.value.push("請閱讀並同意服務條款");

    return formErrors.value.length === 0;
};

const submitBooking = async () => {
    if (!validateForm()) return;

    submitting.value = true;
    try {
        const token = localStorage.getItem("token");
        const isLoggedIn = !!token;

        let response;
        if (isLoggedIn) {
            // 已登入會員，使用會員專用預約端點
            const bookingData = {
                vehicle_id: vehicleId,
                pickup_date: pickupDate,
                pickup_time: pickupTime,
                return_date: returnDate,
                return_time: returnTime,
                notes: form.value.additional_services.child_seat
                    ? "需要兒童座椅"
                    : "",
                // 客戶資料 - 如果用戶在當前租戶還沒有客戶資料，系統會自動建立
                customer_name: form.value.name,
                customer_phone: form.value.phone,
                customer_id_number: form.value.id_number,
                license_number: form.value.license_number,
                license_expiry: form.value.license_expiry,
                address: form.value.address,
            };
            response = await api.post("/customer/reservations", bookingData);
        } else {
            // 訪客，使用公開預約端點
            const bookingData = {
                vehicle_id: vehicleId,
                pickup_date: pickupDate,
                pickup_time: pickupTime,
                return_date: returnDate,
                return_time: returnTime,
                customer_name: form.value.name,
                customer_phone: form.value.phone,
                customer_email: form.value.email,
                customer_id_number: form.value.id_number,
                license_number: form.value.license_number,
                license_expiry: form.value.license_expiry,
                address: form.value.address,
                additional_services: form.value.additional_services,
                total_amount: finalTotal.value,
            };
            response = await api.post("/public/reservations", bookingData);
        }

        // Redirect to confirmation page
        router.push(
            `/${tenantSlug}/booking/confirmation/${response.data.data.id}`,
        );
    } catch (e: any) {
        console.error("Booking submission failed:", e);
        if (e.response?.data?.message) {
            formErrors.value.push(e.response.data.message);
        } else {
            formErrors.value.push("預約提交失敗，請稍後再試");
        }
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchVehicle();
});
</script>
