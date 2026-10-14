<template>
    <div
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 sticky top-8 hover:shadow-lg transition-shadow duration-500"
    >
        <div class="flex items-center gap-3 mb-8">
            <div
                class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center"
            >
                <svg
                    class="w-5 h-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">費用明細</h2>
        </div>
        <div class="space-y-4 mb-8">
            <div
                class="flex justify-between items-center py-3 px-4 bg-gray-50 rounded-xl"
            >
                <span class="text-sm text-gray-600">基本租金</span>
                <span class="font-semibold text-gray-900"
                    >NT$ {{ baseRent.toLocaleString() }}</span
                >
            </div>
            <div
                class="flex justify-between items-center py-3 px-4 bg-gray-50 rounded-xl"
            >
                <span class="text-sm text-gray-600">服務費</span>
                <span class="font-semibold text-gray-900"
                    >NT$ {{ serviceFee.toLocaleString() }}</span
                >
            </div>
            <div
                class="border-t border-gray-200 pt-6 mt-6 bg-gradient-to-r from-blue-50 via-indigo-50 to-sky-50 -mx-4 -mb-4 p-6 rounded-2xl"
            >
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-bold text-lg">總計</span>
                    <span
                        class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent tracking-tight"
                        >NT$ {{ totalAmount.toLocaleString() }}</span
                    >
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-4">
            <button
                v-if="showContactSupport"
                class="w-full px-5 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-2"
            >
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
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                    ></path>
                </svg>
                聯繫客服
            </button>
            <button
                v-if="canCancel"
                @click="$emit('open-cancel-modal')"
                class="w-full px-5 py-3.5 bg-transparent border border-red-500 text-red-500 hover:bg-red-50 rounded-xl font-medium transition-all duration-300 flex items-center justify-center gap-2"
            >
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
                        d="M6 18L18 6M6 6l12 12"
                    ></path>
                </svg>
                取消預約
            </button>
        </div>

        <!-- Created At -->
        <div class="mt-10 pt-8 border-t border-gray-200">
            <div class="flex items-center gap-2 mb-3">
                <svg
                    class="w-4 h-4 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
                <p
                    class="text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                    預約建立時間
                </p>
            </div>
            <p class="text-sm text-gray-700 font-medium">
                {{ formattedCreatedAt }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    amount: number;
    status: string;
    canCancel: boolean;
    createdAt: string;
}>();

const emit = defineEmits<{
    "open-cancel-modal": [];
}>();

const baseRent = Math.round(props.amount * 0.95);
const serviceFee = Math.round(props.amount * 0.05);
const totalAmount = props.amount;
const showContactSupport = props.status === "CONFIRMED";

const formatDateTime = (dateString: string) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日 ${date.getHours().toString().padStart(2, "0")}:${date.getMinutes().toString().padStart(2, "0")}`;
};

const formattedCreatedAt = formatDateTime(props.createdAt);
</script>
