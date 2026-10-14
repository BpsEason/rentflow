<template>
    <div
        class="min-h-screen bg-[#FAFBFC] text-slate-900 flex flex-col selection:bg-blue-500 selection:text-white"
    >
        <!-- Public Header - 2026 頂級精緻設計升級版 -->
        <header
            :class="[
                'sticky top-0 z-50 transition-all duration-500 ease-out backdrop-blur-2xl border-b',
                isScrolled
                    ? 'bg-white/90 shadow-[0_1px_0_rgba(0,0,0,0.03),0_8px_30px_-10px_rgba(0,0,0,0.08)] border-slate-200/70 py-3'
                    : 'bg-white/50 border-transparent py-4',
            ]"
            @scroll.window="handleScroll"
        >
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <!-- Logo 精緻化升級 -->
                    <router-link
                        :to="`/${tenantSlug}`"
                        class="flex items-center gap-2.5 group"
                    >
                        <div
                            class="relative flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 via-blue-500 to-indigo-500 shadow-sm shadow-blue-500/30 group-hover:scale-105 group-hover:shadow-blue-500/40 transition-all duration-300 overflow-hidden"
                        >
                            <span
                                class="text-white font-bold text-base tracking-wider relative z-10"
                                >R</span
                            >
                            <!-- 內部光暈效果 -->
                            <div
                                class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-white/40 blur-sm group-hover:scale-150 transition-transform duration-500"
                            ></div>
                            <div
                                class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/10 to-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                            ></div>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-base font-bold tracking-tight text-slate-900 group-hover:text-slate-800 transition-colors"
                            >
                                RentFlow
                            </span>
                        </div>
                    </router-link>

                    <!-- Desktop Navigation - 精緻化層級導航 -->
                    <div
                        class="hidden md:flex items-center gap-0.5 bg-slate-50/80 p-1 rounded-full border border-slate-200/50 backdrop-blur-sm"
                    >
                        <router-link
                            :to="`/${tenantSlug}`"
                            :class="[
                                'px-4.5 py-1.5 rounded-full text-sm font-medium tracking-wide transition-all duration-300',
                                isActiveRoute('')
                                    ? 'bg-white text-slate-900 shadow-sm shadow-slate-200/60'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-white/40',
                            ]"
                            >首頁</router-link
                        >
                        <router-link
                            :to="`/${tenantSlug}/vehicles`"
                            :class="[
                                'px-4.5 py-1.5 rounded-full text-sm font-medium tracking-wide transition-all duration-300',
                                isActiveRoute('vehicles')
                                    ? 'bg-white text-slate-900 shadow-sm shadow-slate-200/60'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-white/40',
                            ]"
                            >瀏覽車輛</router-link
                        >
                        <a
                            href="#how-it-works"
                            class="px-4.5 py-1.5 rounded-full text-sm font-medium tracking-wide text-slate-500 hover:text-slate-700 hover:bg-white/40 transition-all duration-300"
                            >租車說明</a
                        >
                    </div>

                    <!-- Auth Links - 層級最佳化 -->
                    <div class="hidden md:flex items-center gap-2">
                        <template v-if="isLoggedIn">
                            <router-link
                                :to="`/${tenantSlug}/my-bookings`"
                                :class="[
                                    'px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-300 border',
                                    isActiveRoute('my-bookings')
                                        ? 'bg-blue-50/80 text-blue-700 border-blue-100'
                                        : 'text-slate-600 border-transparent hover:text-slate-800 hover:bg-slate-50',
                                ]"
                                >我的預約</router-link
                            >
                            <button
                                @click="logout"
                                class="px-3 py-2 rounded-lg text-sm font-medium text-slate-500 hover:text-red-600 hover:bg-red-50/80 transition-all duration-300 flex items-center gap-1.5 border border-transparent hover:border-red-100/50"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    />
                                </svg>
                                登出
                            </button>
                        </template>
                        <template v-else>
                            <router-link
                                :to="`/${tenantSlug}/login`"
                                class="px-3.5 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-50 transition-all duration-300"
                            >
                                登入
                            </router-link>
                            <router-link
                                :to="`/${tenantSlug}/vehicles`"
                                class="relative group overflow-hidden bg-slate-900 hover:bg-slate-800 text-white px-4.5 py-2.5 rounded-lg text-sm font-semibold shadow-sm shadow-slate-900/15 hover:shadow-slate-900/25 transition-all duration-300 active:scale-[0.98]"
                            >
                                <span class="relative z-10">立即租車</span>
                                <div
                                    class="absolute inset-0 bg-gradient-to-tr from-blue-600/0 to-blue-500/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                                ></div>
                            </router-link>
                        </template>
                    </div>

                    <!-- Mobile menu button - 精緻化 -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 rounded-xl bg-slate-100/80 text-slate-700 hover:bg-slate-200 transition-all duration-300"
                    >
                        <svg
                            class="w-5 h-5"
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

                <!-- Mobile Menu - 質感升級 -->
                <transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-3 scale-[0.98]"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 -translate-y-3 scale-[0.98]"
                >
                    <div v-if="mobileMenuOpen" class="md:hidden pt-4 pb-2">
                        <div
                            class="bg-white/95 backdrop-blur-2xl rounded-2xl shadow-xl shadow-slate-200/60 p-3 border border-slate-200/70 flex flex-col gap-1"
                        >
                            <router-link
                                @click="mobileMenuOpen = false"
                                :to="`/${tenantSlug}`"
                                class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                >首頁</router-link
                            >
                            <router-link
                                @click="mobileMenuOpen = false"
                                :to="`/${tenantSlug}/vehicles`"
                                class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                >瀏覽車輛</router-link
                            >
                            <a
                                @click="mobileMenuOpen = false"
                                href="#how-it-works"
                                class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                >租車說明</a
                            >
                            <div class="h-px bg-slate-100 my-2"></div>
                            <template v-if="isLoggedIn">
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    :to="`/${tenantSlug}/my-bookings`"
                                    class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                    >我的預約</router-link
                                >
                                <button
                                    @click="logout"
                                    class="text-left px-4 py-3 rounded-xl text-base font-medium text-red-600 hover:bg-red-50 transition-colors"
                                >
                                    登出
                                </button>
                            </template>
                            <template v-else>
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    :to="`/${tenantSlug}/login`"
                                    class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                    >登入</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    :to="`/${tenantSlug}/vehicles`"
                                    class="mt-2 bg-slate-900 text-white px-4 py-3.5 rounded-xl text-center text-base font-semibold shadow-md"
                                    >立即租車</router-link
                                >
                            </template>
                        </div>
                    </div>
                </transition>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="flex-grow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <slot />
            </div>
        </main>

        <!-- Public Footer - 2026 安靜現代極簡風格 -->
        <footer
            class="bg-[#0B0F17] text-slate-400 border-t border-slate-800/80 relative z-10"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div
                    class="flex flex-col md:flex-row items-start md:items-center justify-between gap-8 pb-10 border-b border-slate-800/60"
                >
                    <!-- 左側品牌區：極簡 Logo + 標語 -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6"
                    >
                        <router-link
                            :to="`/${tenantSlug}`"
                            class="flex items-center gap-2.5 group"
                        >
                            <div
                                class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-inner group-hover:scale-105 transition-transform"
                            >
                                R
                            </div>
                            <span
                                class="text-base font-black text-white tracking-tight"
                                >RentFlow</span
                            >
                        </router-link>
                        <span class="hidden sm:inline text-slate-700">|</span>
                        <p class="text-xs text-slate-500 font-medium">
                            新世代智慧租車平台，提供無縫流暢的車輛租賃體驗。
                        </p>
                    </div>

                    <!-- 右側快速導覽與聯絡：單行水平整合 -->
                    <div
                        class="flex flex-wrap items-center gap-x-8 gap-y-3 text-xs font-medium"
                    >
                        <router-link
                            :to="`/${tenantSlug}`"
                            class="hover:text-white transition-colors"
                            >首頁</router-link
                        >
                        <router-link
                            :to="`/${tenantSlug}/vehicles`"
                            class="hover:text-white transition-colors"
                            >瀏覽車輛</router-link
                        >
                        <a
                            href="#how-it-works"
                            class="hover:text-white transition-colors"
                            >租車說明</a
                        >
                        <span class="text-slate-800">/</span>
                        <span class="text-slate-300 font-mono"
                            >0800-XXX-XXX</span
                        >
                        <a
                            href="mailto:service@rentflow.com"
                            class="text-slate-400 hover:text-white transition-colors"
                            >service@rentflow.com</a
                        >
                    </div>
                </div>

                <!-- 底部版權與法規聲明 -->
                <div
                    class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-[11px] text-slate-500"
                >
                    <p>&copy; 2026 RentFlow. All rights reserved.</p>
                    <div class="flex items-center gap-6">
                        <a
                            href="#"
                            class="hover:text-slate-400 transition-colors"
                            >隱私權政策</a
                        >
                        <a
                            href="#"
                            class="hover:text-slate-400 transition-colors"
                            >服務條款</a
                        >
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";

const router = useRouter();
const route = useRoute();
const mobileMenuOpen = ref(false);
const isScrolled = ref(false);

const isLoggedIn = computed(() => {
    return !!localStorage.getItem("token");
});

const tenantSlug = route.params.tenantSlug as string;

const isActiveRoute = (path: string) => {
    const fullPath = path ? `/${tenantSlug}/${path}` : `/${tenantSlug}`;
    return route.path === fullPath;
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    handleScroll();
    window.addEventListener("scroll", handleScroll);
});

const logout = () => {
    localStorage.removeItem("token");
    mobileMenuOpen.value = false;
    router.push(`/${tenantSlug}`);
};
</script>
