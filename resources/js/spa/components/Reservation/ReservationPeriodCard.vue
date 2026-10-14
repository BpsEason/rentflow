<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2
            class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4"
        >
            租賃期間
        </h2>
        <div class="relative">
            <!-- Desktop Timeline -->
            <div class="hidden md:block">
                <div class="grid grid-cols-5 gap-0 items-center">
                    <!-- Pickup -->
                    <div class="col-span-2">
                        <div
                            class="relative p-6 bg-sky-50 rounded-xl border border-sky-100"
                        >
                            <div
                                class="inline-flex items-center gap-2 bg-sky-500 text-white text-xs font-semibold px-3 py-1 rounded-full mb-3"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    ></path>
                                </svg>
                                取車時間
                            </div>
                            <p class="text-xl font-bold text-gray-900 mb-1">
                                {{ formatDate(startAt) }}
                            </p>
                            <p class="text-sky-600 font-semibold">
                                {{ formatTime(startAt) }}
                            </p>
                        </div>
                    </div>
                    <!-- Arrow & Duration -->
                    <div class="col-span-1 flex flex-col items-center">
                        <div class="relative">
                            <div
                                class="w-full h-0.5 bg-gradient-to-r from-sky-300 via-gray-300 to-emerald-300"
                            ></div>
                            <div
                                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"
                            >
                                <svg
                                    class="w-5 h-5 text-gray-400"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            class="bg-white shadow-lg rounded-xl px-4 py-3 border border-gray-100 mt-3"
                        >
                            <p class="text-center">
                                <span
                                    class="text-2xl font-bold text-gray-900"
                                    >{{ rentalDays }}</span
                                >
                                <span class="text-xs text-gray-500 block mt-0.5"
                                    >天租用</span
                                >
                            </p>
                        </div>
                    </div>
                    <!-- Return -->
                    <div class="col-span-2">
                        <div
                            class="relative p-6 bg-emerald-50 rounded-xl border border-emerald-100"
                        >
                            <div
                                class="inline-flex items-center gap-2 bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-full mb-3"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                還車時間
                            </div>
                            <p class="text-xl font-bold text-gray-900 mb-1">
                                {{ formatDate(endAt) }}
                            </p>
                            <p class="text-emerald-600 font-semibold">
                                {{ formatTime(endAt) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Mobile Version -->
            <div class="md:hidden">
                <div class="space-y-3">
                    <!-- Pickup -->
                    <div
                        class="relative p-5 bg-sky-50 rounded-xl border border-sky-100"
                    >
                        <div
                            class="inline-flex items-center gap-2 bg-sky-500 text-white text-xs font-semibold px-3 py-1 rounded-full mb-2"
                        >
                            取車時間
                        </div>
                        <p class="text-lg font-bold text-gray-900 mt-1">
                            {{ formatDate(startAt) }}
                        </p>
                        <p class="text-sky-600 font-medium mt-0.5">
                            {{ formatTime(startAt) }}
                        </p>
                    </div>
                    <!-- Vertical Arrow -->
                    <div class="flex justify-center py-1">
                        <svg
                            class="w-5 h-5 text-gray-400"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"
                            ></path>
                        </svg>
                    </div>
                    <!-- Return -->
                    <div
                        class="relative p-5 bg-emerald-50 rounded-xl border border-emerald-100"
                    >
                        <div
                            class="inline-flex items-center gap-2 bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-full mb-2"
                        >
                            還車時間
                        </div>
                        <p class="text-lg font-bold text-gray-900 mt-1">
                            {{ formatDate(endAt) }}
                        </p>
                        <p class="text-emerald-600 font-medium mt-0.5">
                            {{ formatTime(endAt) }}
                        </p>
                    </div>
                </div>
                <!-- Mobile Duration Badge -->
                <div class="mt-4 flex justify-center">
                    <div
                        class="bg-gray-50 rounded-xl px-8 py-3 border border-gray-100"
                    >
                        <p class="text-center">
                            總共租用
                            <span
                                class="font-bold text-gray-900 text-xl ml-1"
                                >{{ rentalDays }}</span
                            >
                            <span class="text-gray-600">天</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    startAt: string;
    endAt: string;
    rentalDays: number;
}>();

const formatDate = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
};

const formatTime = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");
    return `${hours}:${minutes}`;
};
</script>
