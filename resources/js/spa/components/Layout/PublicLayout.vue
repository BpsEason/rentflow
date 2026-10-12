<template>
    <div class="min-h-screen bg-white flex flex-col">
        <!-- Public Header -->
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <router-link to="/" class="flex items-center">
                        <span class="text-2xl font-bold text-blue-900"
                            >RentFlow</span
                        >
                    </router-link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <router-link
                            to="/"
                            class="text-gray-700 hover:text-slate-900 transition"
                            >首頁</router-link
                        >
                        <router-link
                            to="/vehicles"
                            class="text-gray-700 hover:text-slate-900 transition"
                            >瀏覽車輛</router-link
                        >
                        <a
                            href="#how-it-works"
                            class="text-gray-700 hover:text-slate-900 transition"
                            >租車說明</a
                        >

                        <!-- Auth Links -->
                        <div class="flex items-center space-x-4">
                            <template v-if="isLoggedIn">
                                <router-link
                                    to="/my-bookings"
                                    class="text-gray-700 hover:text-slate-900 transition"
                                    >我的預約</router-link
                                >
                                <button
                                    @click="logout"
                                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition"
                                >
                                    登出
                                </button>
                            </template>
                            <template v-else>
                                <router-link
                                    to="/login"
                                    class="text-gray-700 hover:text-slate-900 transition"
                                >
                                    登入
                                </router-link>
                                <router-link
                                    to="/vehicles"
                                    class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg font-medium transition"
                                >
                                    立即租車
                                </router-link>
                            </template>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                v-if="!mobileMenuOpen"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t">
                    <div class="flex flex-col space-y-4">
                        <router-link
                            @click="mobileMenuOpen = false"
                            to="/"
                            class="text-gray-700 hover:text-blue-900"
                            >首頁</router-link
                        >
                        <router-link
                            @click="mobileMenuOpen = false"
                            to="/vehicles"
                            class="text-gray-700 hover:text-blue-900"
                            >瀏覽車輛</router-link
                        >
                        <a
                            @click="mobileMenuOpen = false"
                            href="#how-it-works"
                            class="text-gray-700 hover:text-blue-900"
                            >租車說明</a
                        >
                        <template v-if="isLoggedIn">
                            <router-link
                                @click="mobileMenuOpen = false"
                                to="/my-bookings"
                                class="text-gray-700 hover:text-blue-900"
                                >我的預約</router-link
                            >
                            <button
                                @click="logout"
                                class="text-left text-gray-700 hover:text-blue-900"
                            >
                                登出
                            </button>
                        </template>
                        <template v-else>
                            <router-link
                                @click="mobileMenuOpen = false"
                                to="/login"
                                class="text-slate-900 font-medium"
                                >登入</router-link
                            >
                            <router-link
                                @click="mobileMenuOpen = false"
                                to="/vehicles"
                                class="bg-amber-600 text-white px-4 py-2 rounded-lg text-center font-medium"
                            >
                                立即租車
                            </router-link>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="flex-grow">
            <slot />
        </main>

        <!-- Public Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Brand -->
                    <div class="col-span-1 md:col-span-2">
                        <span class="text-2xl font-bold">RentFlow</span>
                        <p class="mt-4 text-gray-400 max-w-md">
                            專業租車平台，提供您最舒適、便捷的租車體驗。不論是商務出差還是休閒旅遊，我們都有適合您的車輛。
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="font-semibold mb-4">快速連結</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li>
                                <router-link
                                    to="/"
                                    class="hover:text-white transition"
                                    >首頁</router-link
                                >
                            </li>
                            <li>
                                <router-link
                                    to="/vehicles"
                                    class="hover:text-white transition"
                                    >瀏覽車輛</router-link
                                >
                            </li>
                            <li>
                                <a
                                    href="#how-it-works"
                                    class="hover:text-white transition"
                                    >租車說明</a
                                >
                            </li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="font-semibold mb-4">聯絡我們</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li>客服專線：0800-XXX-XXX</li>
                            <li>服務時間：24小時全年無休</li>
                            <li>Email：service@rentflow.com</li>
                        </ul>
                    </div>
                </div>

                <div
                    class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400"
                >
                    <p>&copy; 2026 RentFlow. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const mobileMenuOpen = ref(false);

const isLoggedIn = computed(() => {
    return !!localStorage.getItem("token");
});

const logout = () => {
    localStorage.removeItem("token");
    mobileMenuOpen.value = false;
    router.push("/");
};
</script>

<style scoped>
/* Component specific styles if needed */
</style>
