<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">確認取消預約？</h3>
            <p class="text-gray-600 mb-6">
                您即將取消預約編號
                <span class="font-mono font-semibold">{{ bookingNumber }}</span>
                ，此操作無法復原。
            </p>

            <div
                class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6"
            >
                <p class="text-yellow-800 text-sm">
                    <strong>注意：</strong
                    >如果在取車前24小時內取消，將收取首日租金作為手續費。
                </p>
            </div>

            <div class="flex gap-4">
                <button
                    @click="$emit('close')"
                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg font-medium transition"
                >
                    再想想
                </button>
                <button
                    @click="$emit('confirm')"
                    :disabled="cancelling"
                    class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center justify-center"
                >
                    <span
                        v-if="cancelling"
                        class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"
                    ></span>
                    確認取消
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    show: boolean;
    bookingNumber: string;
    cancelling: boolean;
}>();

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();
</script>
