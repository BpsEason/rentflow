<template>
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- 左側品牌區 - 深色有質感 -->
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
                    歡迎回來
                    <br />
                    <span class="text-primary-400">繼續您的旅程</span>
                </h1>
                <p class="text-lg text-white/60 leading-relaxed max-w-md mb-16">
                    專業的車輛租賃平台，為您提供最便捷的租車體驗。登入您的帳號，繼續管理您的預訂與車輛。
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
                        <span class="text-base">企業級安全防護</span>
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
                        <span class="text-base">24/7 全天候客戶支援</span>
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
                        <span class="text-base">輕鬆上手的操作介面</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 右側登入表單區 -->
        <div
            class="w-full lg:w-[52%] bg-white flex items-center justify-center p-8 lg:p-16 xl:p-24"
        >
            <div class="w-full max-w-lg">
                <!-- 表單標題 -->
                <div class="mb-10">
                    <h2
                        class="text-3xl font-semibold text-slate-900 tracking-tight mb-2"
                    >
                        登入帳號
                    </h2>
                    <p class="text-slate-500 text-lg">
                        填寫以下資訊，登入您的帳號
                    </p>
                </div>

                <!-- 登入表單 -->
                <form @submit.prevent="handleLogin" class="space-y-6">
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
                                placeholder="請輸入密碼"
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

                    <!-- 記住我 & 忘記密碼 -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                class="w-4 h-4 text-primary-600 bg-slate-100 border-slate-200 rounded-lg focus:ring-primary-500/40"
                            />
                            <span class="ml-3 text-sm text-slate-600"
                                >記住我</span
                            >
                        </label>
                        <a
                            href="#"
                            class="text-sm text-primary-600 hover:text-primary-500 font-medium transition-colors"
                        >
                            忘記密碼？
                        </a>
                    </div>

                    <!-- 錯誤訊息 -->
                    <div
                        v-if="error"
                        class="bg-red-50 border-0 text-red-600 px-5 py-4 rounded-2xl text-sm"
                    >
                        {{ error }}
                    </div>

                    <!-- 登入按鈕 - 絕對焦點 -->
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
                            登入中...
                        </span>
                        <span v-else>登入</span>
                    </button>
                </form>

                <!-- 註冊連結 -->
                <p class="text-center text-base text-slate-500 mt-10">
                    還沒有帳號？
                    <router-link
                        :to="`/${tenantSlug}/register`"
                        class="text-primary-600 hover:text-primary-500 font-semibold ml-1 transition-colors duration-200"
                    >
                        立即註冊
                    </router-link>
                </p>

                <!-- 版權 -->
                <p
                    class="text-center text-xs text-slate-400 mt-12 tracking-wide"
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

interface LoginForm {
    email: string;
    password: string;
}

const router = useRouter();
const loading = ref(false);
const error = ref<string | null>(null);
const showPassword = ref(false);
const form = ref<LoginForm>({
    email: "",
    password: "",
});

const handleLogin = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await api.post(`/${tenantSlug}/customer/auth/login`, {
            email: form.value.email,
            password: form.value.password,
        });

        if (response.data.data.access_token) {
            localStorage.setItem("token", response.data.data.access_token);
            router.push(`/${tenantSlug}/my-bookings`);
        }
    } catch (e: any) {
        error.value = e.response?.data?.message || "登入失敗，請檢查帳號密碼";
    } finally {
        loading.value = false;
    }
};
</script>
