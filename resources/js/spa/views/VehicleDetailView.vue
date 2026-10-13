<template>
    <PublicLayout>
        <div
            class="py-12 bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-10">
                    <router-link
                        :to="`/${tenantSlug}/vehicles`"
                        class="inline-flex items-center text-gray-600 hover:text-blue-700 transition-all duration-300 group"
                    >
                        <svg
                            class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform duration-300"
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
                        <span class="text-sm font-medium">返回車輛列表</span>
                    </router-link>
                </div>

                <!-- Loading State -->
                <div
                    v-if="loading"
                    class="flex justify-center items-center py-32"
                >
                    <div
                        class="animate-spin rounded-full h-14 w-14 border-b-3 border-blue-600"
                    ></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-32">
                    <p class="text-red-500 text-lg">{{ error }}</p>
                    <button
                        @click="fetchVehicle"
                        class="mt-6 text-blue-600 underline underline-offset-4 hover:text-blue-700 transition-colors"
                    >
                        重新載入
                    </button>
                </div>

                <!-- Vehicle Detail Content -->
                <template v-else>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                        <!-- Main Content - Left Column -->
                        <div class="lg:col-span-2 space-y-10">
                            <!-- Vehicle Image Gallery - 升級為現代風格 -->
                            <div
                                class="bg-white rounded-3xl shadow-xl shadow-gray-200/70 overflow-hidden ring-1 ring-gray-100"
                            >
                                <div class="relative">
                                    <img
                                        :src="
                                            selectedImage || vehicle.image_url
                                        "
                                        :alt="vehicle.name"
                                        class="w-full h-[480px] object-cover"
                                    />
                                    <!-- 漸層遮罩提升質感 -->
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent pointer-events-none"
                                    ></div>
                                    <!-- 可租用標籤升級為精緻低飽和樣式 -->
                                    <span
                                        class="absolute top-6 right-6 bg-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full text-sm font-semibold backdrop-blur-sm shadow-sm"
                                    >
                                        可租用
                                    </span>
                                </div>
                                <!-- 只有當有多張圖片時才顯示縮圖圖庫 -->
                                <div
                                    v-if="
                                        vehicle.images &&
                                        vehicle.images.length > 1
                                    "
                                    class="p-6 flex gap-4 overflow-x-auto"
                                >
                                    <img
                                        v-for="(img, idx) in vehicle.images"
                                        :key="idx"
                                        :src="img"
                                        :alt="`${vehicle.name} ${idx + 1}`"
                                        class="w-28 h-24 object-cover rounded-2xl cursor-pointer hover:opacity-90 transition-all duration-300 hover:scale-105 ring-2 ring-transparent hover:ring-blue-500"
                                        @click="selectedImage = img"
                                    />
                                </div>
                            </div>

                            <!-- Vehicle Info -->
                            <div
                                class="bg-white rounded-3xl shadow-xl shadow-gray-200/70 p-8 ring-1 ring-gray-100"
                            >
                                <h1
                                    class="text-4xl font-bold text-gray-900 mb-3 tracking-tight"
                                >
                                    {{ vehicle.name }}
                                </h1>
                                <p class="text-gray-500 text-lg mb-10">
                                    {{ vehicle.brand }} {{ vehicle.model }}
                                </p>

                                <!-- Vehicle Specifications Grid - 升級為精煉節奏的呈現方式 -->
                                <h2
                                    class="text-xl font-bold text-gray-900 mb-6"
                                >
                                    車輛規格
                                </h2>
                                <div
                                    class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-100 rounded-2xl overflow-hidden"
                                >
                                    <!-- 乘客數 -->
                                    <div class="bg-white p-6">
                                        <div class="flex items-start">
                                            <div
                                                class="p-3 bg-blue-50 rounded-xl mr-4"
                                            >
                                                <svg
                                                    class="w-6 h-6 text-blue-600"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs text-gray-500 uppercase tracking-wide mb-1"
                                                >
                                                    乘客數
                                                </p>
                                                <p
                                                    class="text-2xl font-bold text-gray-900"
                                                >
                                                    {{ vehicle.seats || 5
                                                    }}<span
                                                        class="text-sm font-normal text-gray-500 ml-1"
                                                        >人</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- 變速箱 -->
                                    <div class="bg-white p-6">
                                        <div class="flex items-start">
                                            <div
                                                class="p-3 bg-orange-50 rounded-xl mr-4"
                                            >
                                                <svg
                                                    class="w-6 h-6 text-orange-600"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs text-gray-500 uppercase tracking-wide mb-1"
                                                >
                                                    變速箱
                                                </p>
                                                <p
                                                    class="text-2xl font-bold text-gray-900"
                                                >
                                                    {{
                                                        vehicle.transmission ||
                                                        "自排"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- 燃油類型 -->
                                    <div class="bg-white p-6">
                                        <div class="flex items-start">
                                            <div
                                                class="p-3 bg-green-50 rounded-xl mr-4"
                                            >
                                                <svg
                                                    class="w-6 h-6 text-green-600"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs text-gray-500 uppercase tracking-wide mb-1"
                                                >
                                                    燃油類型
                                                </p>
                                                <p
                                                    class="text-2xl font-bold text-gray-900"
                                                >
                                                    {{
                                                        vehicle.fuel_type ||
                                                        "汽油"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- 行李箱 -->
                                    <div class="bg-white p-6">
                                        <div class="flex items-start">
                                            <div
                                                class="p-3 bg-purple-50 rounded-xl mr-4"
                                            >
                                                <svg
                                                    class="w-6 h-6 text-purple-600"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs text-gray-500 uppercase tracking-wide mb-1"
                                                >
                                                    行李箱
                                                </p>
                                                <p
                                                    class="text-2xl font-bold text-gray-900"
                                                >
                                                    {{ vehicle.luggage || 3
                                                    }}<span
                                                        class="text-sm font-normal text-gray-500 ml-1"
                                                        >件</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vehicle Description -->
                                <div class="mt-10">
                                    <h2
                                        class="text-xl font-bold text-gray-900 mb-6"
                                    >
                                        車輛介紹
                                    </h2>
                                    <p
                                        class="text-gray-600 leading-relaxed text-lg"
                                    >
                                        {{
                                            vehicle.description ||
                                            "這是一款高性能的優質車輛，提供舒適的駕駛體驗。配備先進的安全系統和舒適的內裝，是您旅遊或商務出行的理想選擇。"
                                        }}
                                    </p>
                                </div>

                                <!-- Features -->
                                <div class="mt-10">
                                    <h2
                                        class="text-xl font-bold text-gray-900 mb-6"
                                    >
                                        車輛配備
                                    </h2>
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-3 gap-4 bg-gray-50 rounded-2xl p-6"
                                    >
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'aircon',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            冷氣空調
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'bluetooth',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            藍牙連接
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes('gps')
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            GPS導航
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'airbags',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            安全氣囊
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes('abs')
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            ABS防鎖死
                                        </div>
                                        <div
                                            v-if="
                                                vehicle.features &&
                                                vehicle.features.includes(
                                                    'backup_camera',
                                                )
                                            "
                                            class="flex items-center text-gray-700"
                                        >
                                            <svg
                                                class="w-5 h-5 text-green-500 mr-2"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            倒車影像
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar - Right Column (Booking) - 升級為視覺焦點卡片 -->
                        <div class="lg:col-span-1">
                            <div
                                class="bg-white rounded-3xl shadow-2xl shadow-gray-300/80 p-8 sticky top-8 ring-1 ring-gray-100 transform hover:shadow-3xl transition-all duration-500"
                            >
                                <div
                                    class="flex items-center justify-between mb-8"
                                >
                                    <h2
                                        class="text-2xl font-bold text-gray-900"
                                    >
                                        預約租車
                                    </h2>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"
                                    >
                                        立即預定
                                    </span>
                                </div>

                                <!-- Date Selection - 升級現代化輸入框 -->
                                <div class="space-y-5 mb-8">
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-3"
                                            >📅 取車日期</label
                                        >
                                        <input
                                            v-model="bookingDates.pickupDate"
                                            type="date"
                                            :min="today"
                                            class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-gray-50 hover:bg-white text-base"
                                            @change="calculateTotal"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-3"
                                            >⏰ 取車時間</label
                                        >
                                        <select
                                            v-model="bookingDates.pickupTime"
                                            class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-gray-50 hover:bg-white text-base"
                                            @change="calculateTotal"
                                        >
                                            <option value="09:00">09:00</option>
                                            <option value="10:00">10:00</option>
                                            <option value="11:00">11:00</option>
                                            <option value="12:00">12:00</option>
                                            <option value="13:00">13:00</option>
                                            <option value="14:00">14:00</option>
                                            <option value="15:00">15:00</option>
                                            <option value="16:00">16:00</option>
                                            <option value="17:00">17:00</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-3"
                                            >📅 還車日期</label
                                        >
                                        <input
                                            v-model="bookingDates.returnDate"
                                            type="date"
                                            :min="
                                                bookingDates.pickupDate || today
                                            "
                                            class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-gray-50 hover:bg-white text-base"
                                            @change="calculateTotal"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-3"
                                            >⏰ 還車時間</label
                                        >
                                        <select
                                            v-model="bookingDates.returnTime"
                                            class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-gray-50 hover:bg-white text-base"
                                            @change="calculateTotal"
                                        >
                                            <option value="09:00">09:00</option>
                                            <option value="10:00">10:00</option>
                                            <option value="11:00">11:00</option>
                                            <option value="12:00">12:00</option>
                                            <option value="13:00">13:00</option>
                                            <option value="14:00">14:00</option>
                                            <option value="15:00">15:00</option>
                                            <option value="16:00">16:00</option>
                                            <option value="17:00">17:00</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Date Validation Error -->
                                <div
                                    v-if="dateError"
                                    class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg"
                                >
                                    <p class="text-red-600 text-sm">
                                        {{ dateError }}
                                    </p>
                                </div>

                                <!-- Price Loading State -->
                                <div
                                    v-if="priceLoading"
                                    class="border-t border-gray-200 pt-6 mb-6 text-center py-8"
                                >
                                    <div
                                        class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-900 mx-auto"
                                    ></div>
                                    <p class="mt-2 text-gray-500">
                                        計算租金中...
                                    </p>
                                </div>

                                <!-- Price Error State -->
                                <div
                                    v-else-if="priceError"
                                    class="border-t border-gray-200 pt-6 mb-6"
                                >
                                    <div
                                        class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg"
                                    >
                                        <p class="text-red-600 text-sm">
                                            {{ priceError }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Price Summary - 升級價格層級設計 -->
                                <div
                                    v-else-if="priceCalculation"
                                    class="bg-gradient-to-b from-gray-50 to-gray-100 rounded-2xl p-6 mb-8"
                                >
                                    <h3
                                        class="text-lg font-bold text-gray-900 mb-5"
                                    >
                                        價格明細
                                    </h3>
                                    <div class="space-y-4">
                                        <div
                                            class="flex justify-between text-gray-600"
                                        >
                                            <span class="text-base"
                                                >租用天數</span
                                            >
                                            <span class="font-medium"
                                                >{{ rentalDays }} 天</span
                                            >
                                        </div>
                                        <div
                                            v-for="(
                                                item, index
                                            ) in priceBreakdown"
                                            :key="index"
                                            class="flex justify-between text-gray-600 text-sm"
                                        >
                                            <span
                                                >{{ item.date }} ({{
                                                    item.type === "weekday"
                                                        ? "平日"
                                                        : item.type ===
                                                            "weekend"
                                                          ? "週末"
                                                          : "假日"
                                                }})</span
                                            >
                                            <span
                                                >NT$
                                                {{
                                                    item.price.toLocaleString()
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <div
                                        class="border-t-2 border-white pt-5 mt-4"
                                    >
                                        <div
                                            class="flex justify-between items-center"
                                        >
                                            <span
                                                class="text-xl font-bold text-gray-900"
                                                >總計</span
                                            >
                                            <span
                                                class="text-3xl font-bold text-orange-500"
                                                >NT$
                                                {{
                                                    totalAmount.toLocaleString()
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Book Now Button - 升級CTA按鈕，加入完整互動狀態 -->
                                <button
                                    @click="proceedToBooking"
                                    :disabled="!canBook"
                                    class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-500 hover:to-orange-700 text-white font-bold py-5 px-8 rounded-2xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 disabled:hover:shadow-none flex items-center justify-center transform hover:scale-[1.02] hover:shadow-xl hover:shadow-orange-200 active:scale-[0.98] text-lg group"
                                >
                                    <span class="tracking-wide">立即預約</span>
                                    <svg
                                        class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition-transform"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                                        ></path>
                                    </svg>
                                </button>

                                <!-- Help Text -->
                                <p
                                    class="mt-4 text-xs text-gray-500 text-center"
                                >
                                    點擊立即預約後將進入填寫資料頁面
                                </p>

                                <!-- Security Note -->
                                <div
                                    class="mt-6 flex items-center justify-center text-gray-400 text-xs"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        ></path>
                                    </svg>
                                    您的資訊已加密保護
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
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
const vehicleId = route.params.id as string;

const loading = ref(true);
const error = ref<string | null>(null);
const vehicle = ref<any>({});
const today = ref(new Date().toISOString().split("T")[0]);
const dateError = ref<string | null>(null);
const selectedImage = ref<string | null>(null);

// Booking dates
const bookingDates = ref({
    pickupDate: "",
    pickupTime: "09:00",
    returnDate: "",
    returnTime: "17:00",
});

// Price calculation from backend API
const priceCalculation = ref<any>(null);
const priceLoading = ref(false);
const priceError = ref<string | null>(null);

// Computed values from API response
const rentalDays = computed(() => {
    return priceCalculation.value?.rental_days || 0;
});

const priceBreakdown = computed(() => {
    return priceCalculation.value?.breakdown || [];
});

const totalAmount = computed(() => {
    return priceCalculation.value?.total_amount || 0;
});

const durationHours = computed(() => {
    return priceCalculation.value?.period?.duration_hours || 0;
});

const canBook = computed(() => {
    return (
        rentalDays.value > 0 &&
        !dateError.value &&
        !priceError.value &&
        vehicle.value.status === "AVAILABLE"
    );
});

const fetchVehicle = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get(`/public/vehicles/${vehicleId}`);
        vehicle.value = response.data.data;

        // Initialize selected image, handling both images array and single image_url
        if (vehicle.value.image_url) {
            selectedImage.value = vehicle.value.image_url;
        } else if (vehicle.value.images && vehicle.value.images.length > 0) {
            selectedImage.value = vehicle.value.images[0];
        }

        // Set default dates (tomorrow to day after tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 3);
        bookingDates.value.pickupDate = tomorrow.toISOString().split("T")[0];
        bookingDates.value.returnDate = dayAfter.toISOString().split("T")[0];

        // Calculate price for default dates
        await calculateTotal();
    } catch (e) {
        error.value = "載入車輛詳情失敗，請稍後再試";
        console.error("Failed to fetch vehicle:", e);
    } finally {
        loading.value = false;
    }
};

const calculateTotal = async () => {
    dateError.value = null;
    priceError.value = null;

    if (!bookingDates.value.pickupDate || !bookingDates.value.returnDate) {
        return;
    }

    const start = new Date(bookingDates.value.pickupDate);
    const end = new Date(bookingDates.value.returnDate);

    if (end < start) {
        dateError.value = "還車日期不能早於取車日期";
        return;
    }

    // Combine date and time for backend API
    const startAt = `${bookingDates.value.pickupDate} ${bookingDates.value.pickupTime}:00`;
    const endAt = `${bookingDates.value.returnDate} ${bookingDates.value.returnTime}:00`;

    // Check minimum 4 hours
    const startTime = new Date(startAt).getTime();
    const endTime = new Date(endAt).getTime();
    const hoursDiff = (endTime - startTime) / (1000 * 60 * 60);

    if (hoursDiff < 4) {
        dateError.value = "最少租用時間為4小時";
        return;
    }

    // Call backend API to calculate price
    priceLoading.value = true;
    try {
        const response = await api.post(
            `/public/vehicles/${vehicleId}/calculate-price`,
            {
                start_at: startAt,
                end_at: endAt,
            },
        );
        priceCalculation.value = response.data.data;
        priceCalculation.value = response.data.data;
    } catch (e: any) {
        if (e.response?.data?.message) {
            priceError.value = e.response.data.message;
        } else {
            priceError.value = "計算租金失敗，請稍後再試";
        }
        console.error("Failed to calculate price:", e);
    } finally {
        priceLoading.value = false;
    }
};

const proceedToBooking = () => {
    if (!canBook.value) return;

    // Pass booking data to booking page
    router.push({
        path: `/${tenantSlug}/booking/${vehicleId}`,
        query: {
            pickupDate: bookingDates.value.pickupDate,
            pickupTime: bookingDates.value.pickupTime,
            returnDate: bookingDates.value.returnDate,
            returnTime: bookingDates.value.returnTime,
            totalPrice: totalAmount.value.toString(),
        },
    });
};

onMounted(() => {
    fetchVehicle();
});
</script>
