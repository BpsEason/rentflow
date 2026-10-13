<template>
    <div
        class="min-h-screen bg-gray-50 flex items-center justify-center p-4 sm:p-6 lg:p-8"
    >
        <div class="w-full max-w-md">
            <!-- Logo & Brand -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl mb-4"
                >
                    <svg
                        class="w-8 h-8 text-white"
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
                <h1 class="text-2xl font-bold text-gray-900">RentFlow</h1>
                <p class="text-gray-500 mt-1">專業車輛租賃管理平台</p>
            </div>

            <!-- Login Card -->
            <div class="card p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    使用者登入
                </h2>

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-gray-700 mb-2"
                        >
                            電子郵件
                        </label>
                        <input
                            type="email"
                            id="email"
                            v-model="form.email"
                            required
                            placeholder="請輸入您的email"
                            class="w-full"
                        />
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700 mb-2"
                        >
                            密碼
                        </label>
                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                v-model="form.password"
                                required
                                placeholder="請輸入您的密碼"
                                class="w-full pr-10"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
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
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
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

                    <div v-if="error" class="alert-danger">
                        {{ error }}
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="btn-primary w-full py-2.5 text-base"
                    >
                        <span v-if="loading" class="inline-flex items-center">
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
            </div>

            <p class="text-center text-sm text-gray-600 mt-6">
                還沒有帳號？
                <router-link
                    :to="`/${tenantSlug}/register`"
                    class="text-primary-600 hover:text-primary-500 font-medium"
                >
                    立即註冊
                </router-link>
            </p>

            <p class="text-center text-sm text-gray-500 mt-4">
                © 2026 RentFlow. 版權所有
            </p>
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
        const response = await api.post("/customer/auth/login", {
            email: form.value.email,
            password: form.value.password,
        });

        if (response.data.access_token) {
            localStorage.setItem("token", response.data.access_token);
            router.push(`/${tenantSlug}/my-bookings`);
        }
    } catch (e: any) {
        error.value =
            e.response?.data?.message || "登入失敗，請檢查您的帳號密碼";
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
/* 新的設計系統已透過 Tailwind CSS 實作 */
</style>
