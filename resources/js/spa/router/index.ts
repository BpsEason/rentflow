import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";
import LoginView from "../views/LoginView.vue";

// Lazy load front-end pages for better performance
const VehicleDetailView = () => import("../views/VehicleDetailView.vue");
const BookingView = () => import("../views/BookingView.vue");
const BookingConfirmationView = () =>
    import("../views/BookingConfirmationView.vue");
const MyBookingsView = () => import("../views/MyBookingsView.vue");
const MyBookingDetailView = () => import("../views/MyBookingDetailView.vue");

const routes = [
    // Frontend public routes with tenant slug as path parameter
    {
        path: "/:tenantSlug",
        name: "home",
        component: HomeView,
        props: true,
    },
    {
        path: "/:tenantSlug/vehicles",
        name: "vehicles",
        component: () => import("../views/VehiclesPublicView.vue"),
        props: true,
    },
    {
        path: "/:tenantSlug/vehicles/:id",
        name: "vehicle-detail",
        component: VehicleDetailView,
        props: true,
    },
    {
        path: "/:tenantSlug/booking/:vehicleId",
        name: "booking",
        component: BookingView,
        meta: { requiresAuth: true },
        props: true,
    },
    {
        path: "/:tenantSlug/booking/confirmation/:id",
        name: "booking-confirmation",
        component: BookingConfirmationView,
        props: true,
    },
    {
        path: "/:tenantSlug/login",
        name: "login",
        component: LoginView,
        props: true,
    },
    {
        path: "/:tenantSlug/my-bookings",
        name: "my-bookings",
        component: MyBookingsView,
        meta: { requiresAuth: true },
        props: true,
    },
    {
        path: "/:tenantSlug/my-bookings/:id",
        name: "my-booking-detail",
        component: MyBookingDetailView,
        meta: { requiresAuth: true },
        props: true,
    },
    // 重定向根路徑到默認租戶（如果需要）
    {
        path: "/",
        redirect: "/default",
    },
];

const router = createRouter({
    history: createWebHistory("/"),
    routes,
    strict: false, // 允許匹配帶尾斜杠的路徑
});

// Navigation guard for protected routes
router.beforeEach((to, from, next) => {
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
    const isLoggedIn = !!localStorage.getItem("token");

    if (requiresAuth && !isLoggedIn) {
        // 從目前路由或目標路由獲取tenantSlug，確保登入頁面保留租戶前綴
        const tenantSlug = to.params.tenantSlug || from.params.tenantSlug;
        if (tenantSlug) {
            next(`/${tenantSlug}/login`);
        } else {
            next(false);
        }
    } else {
        next();
    }
});

export default router;
