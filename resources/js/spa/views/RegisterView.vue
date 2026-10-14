<template>
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- 左側：品牌區 - 約48%寬度 -->
        <div
            class="w-full lg:w-[48%] relative bg-gradient-to-br from-slate-900 via-slate-800 to-primary-900 overflow-hidden"
        >
            <!-- 克制的背景光暈 - 低透明度、小範圍 -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div
                    class="absolute -top-32 -right-32 w-[400px] h-[400px] bg-primary-600/5 rounded-full blur-3xl"
                ></div>
                <div
                    class="absolute -bottom-24 -left-24 w-[300px] h-[300px] bg-orange-500/5 rounded-full blur-3xl"
                ></div>
            </div>

            <!-- Logo -->
            <div class="absolute top-8 left-8 lg:top-12 lg:left-12 z-10">
                <div
                    class="inline-flex items-center justify-center w-12 h-12 bg-white/10 backdrop-blur-sm rounded-xl border border-white/10"
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
                            d="M8 7h8m-8 5h8m-4 5v.01M3 12a9 9 0 1118 0 9 9 0 01-18 0z"
                        />
                    </svg>
                </div>
                <span
                    class="ml-3 text-white text-xl font-semibold tracking-tight"
                    >RentFlow</span
                >
            </div>

            <!-- 品牌文案 -->
            <div
                class="relative z-10 flex flex-col justify-center h-full px-8 lg:px-16 xl:px-24 py-20"
            >
                <h1
                    class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white leading-[1.05] tracking-tight mb-8"
                >
                    加入我們
                    <br />
                    <span class="text-primary-400">開啟租賃之旅</span>
                </h1>
                <p class="text-lg text-white/60 leading-relaxed max-w-md mb-16">
                    專業的車輛租賃平台，為您提供最便捷的租車體驗。無需繁瑣手續，輕鬆完成註冊，立即開始使用。
                </p>

                <!-- 特色列表 -->
                <div class="space-y-5">
                    <div class="flex items-center text-white/70">
                        <div
                            class="w-5 h-5 rounded-full bg-orange-500/20 flex items-center justify-center mr-4"
                        >
                            <svg
                                class="w-3 h-3 text-orange-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <span class="text-base">免費註冊，無需綁定信用卡</span>
                    </div>
                    <div class="flex items-center text-white/70">
                        <div
                            class="w-5 h-5 rounded-full bg-orange-500/20 flex items-center justify-center mr-4"
                        >
                            <svg
                                class="w-3 h-3 text-orange-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <span class="text-base">全台超過500個據點</span>
                    </div>
                    <div class="flex items-center text-white/70">
                        <div
                            class="w-5 h-5 rounded-full bg-orange-500/20 flex items-center justify-center mr-4"
                        >
                            <svg
                                class="w-3 h-3 text-orange-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <span class="text-base">24/7 客戶服務支援</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 右側：註冊表單區 - 約52%寬度 -->
        <div
            class="w-full lg:w-[52%] bg-white flex items-center justify-center p-8 lg:p-16 xl:p-24"
        >
            <div class="w-full max-w-md">
                <!-- 頁頭 -->
                <div class="mb-10">
                    <h2
                        class="text-3xl font-semibold text-slate-900 tracking-tight mb-2"
                    >
                        建立帳號
                    </h2>
                    <p class="text-slate-500 text-lg">
                        填寫以下資訊，完成會員註冊
                    </p>
                </div>

                <!-- 註冊表單 -->
                <form @submit.prevent="handleRegister" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label
                                for="name"
                                class="block text-sm font-medium text-slate-700 mb-3"
                            >
                                姓名
                            </label>
                            <input
                                type="text"
                                id="name"
                                v-model="form.name"
                                required
                                placeholder="您的姓名"
                                class="w-full px-5 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary-500/40 focus:bg-white outline-none transition-all duration-300 placeholder:text-slate-400 text-slate-900 text-base"
                            />
                        </div>
                        <div>
                            <label
                                for="phone"
                                class="block text-sm font-medium text-slate-700 mb-3"
                            >
                                手機號碼
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                v-model="form.phone"
                                required
                                placeholder="0912345678"
                                class="w-full px-5 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary-500/40 focus:bg-white outline-none transition-all duration-300 placeholder:text-slate-400 text-slate-900 text-base"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-slate-700 mb-3"
                        >
                            電子郵件
                        </label>
                        <input
                            type="email"
                            id="email"
                            v-model="form.email"
                            required
                            placeholder="your@email.com"
                            class="w-full px-5 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary-500/40 focus:bg-white outline-none transition-all duration-300 placeholder:text-slate-400 text-slate-900 text-base"
                        />
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-slate-700 mb-3"
                        >
                            密碼
                        </label>
                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                v-model="form.password"
                                required
                                minlength="8"
                                placeholder="至少8位字符"
                                class="w-full pr-14 px-5 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary-500/40 focus:bg-white outline-none transition-all duration-300 placeholder:text-slate-400 text-slate-900 text-base"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors duration-200 p-1.5 rounded-xl hover:bg-slate-100"
                            >
                                <svg
                                    v-if="!showPassword"
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542-7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.935 9.935 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-medium text-slate-700 mb-3"
                        >
                            確認密碼
                        </label>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            required
                            minlength="8"
                            placeholder="再次輸入密碼"
                            class="w-full px-5 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary-500/40 focus:bg-white outline-none transition-all duration-300 placeholder:text-slate-400 text-slate-900 text-base"
                        />
                    </div>

                    <!-- 錯誤/成功訊息 -->
                    <div
                        v-if="error"
                        class="bg-red-50 text-red-600 px-5 py-4 rounded-2xl text-sm"
                    >
                        {{ error }}
                    </div>
                    <div
                        v-if="success"
                        class="bg-green-50 text-green-600 px-5 py-4 rounded-2xl text-sm"
                    >
                        {{ success }}
                    </div>

                    <!-- 註冊按鈕 -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-4.5 text-base font-semibold bg-slate-900 text-white rounded-2xl shadow-lg shadow-slate-900/10 hover:shadow-xl hover:shadow-slate-900/15 transform hover:-translate-y-0.5 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/50 focus:ring-offset-2 transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none disabled:hover:shadow-lg"
                    >
                        <span
                            v-if="loading"
                            class="inline-flex items-center justify-center"
                        >
                            <svg
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            註冊中...
                        </span>
                        <span v-else>建立帳號</span>
                    </button>
                </form>

                <!-- 登入連結 -->
                <p class="text-center text-slate-500 mt-10 text-base">
                    已有帳號？
                    <router-link
                        :to="`/${tenantSlug}/login`"
                        class="text-slate-900 font-semibold ml-1 hover:underline underline-offset-4 transition-all duration-200"
                    >
                        立即登入
                    </router-link>
                </p>

                <!-- 版權 -->
                <p
                    class="text-center text-xs text-slate-400 mt-10 tracking-wide"
                >
                    © 2026 RentFlow. 版權所有
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import api from "../services/api";

const route = useRoute();
const tenantSlug = route.params.tenantSlug as string;

interface RegisterForm {
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
}

const router = useRouter();
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);
const showPassword = ref(false);
const form = ref<RegisterForm>({
    name: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
});

const handleRegister = async () => {
    loading.value = true;
    error.value = null;
    success.value = null;

    // 前端密碼驗證
    if (form.value.password !== form.value.password_confirmation) {
        error.value = "兩次輸入的密碼不一致";
        loading.value = false;
        return;
    }

    if (form.value.password.length < 8) {
        error.value = "密碼長度至少需要8位";
        loading.value = false;
        return;
    }

    try {
        const response = await api.post(
            `/${tenantSlug}/customer/auth/register`,
            {
                name: form.value.name,
                email: form.value.email,
                password: form.value.password,
                phone: form.value.phone,
            },
        );

        if (response.data.data.access_token) {
            localStorage.setItem("token", response.data.data.access_token);
            success.value = "註冊成功，正在跳轉...";
            setTimeout(() => {
                router.push(`/${tenantSlug}/my-bookings`);
            }, 1500);
        }
    } catch (e: any) {
        error.value = e.response?.data?.message || "註冊失敗，請稍後再試";
        if (e.response?.data?.errors) {
            const errors = e.response.data.errors;
            if (errors.email) {
                error.value = errors.email[0];
            }
        }
    } finally {
        loading.value = false;
    }
};
</script>
