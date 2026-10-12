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
    // Frontend public routes
    {
        path: "/",
        name: "home",
        component: HomeView,
    },
    {
        path: "/vehicles",
        name: "vehicles",
        component: () => import("../views/VehiclesPublicView.vue"),
    },
    {
        path: "/vehicles/:id",
        name: "vehicle-detail",
        component: VehicleDetailView,
    },
    {
        path: "/booking/:vehicleId",
        name: "booking",
        component: BookingView,
        meta: { requiresAuth: true },
    },
    {
        path: "/booking/confirmation/:id",
        name: "booking-confirmation",
        component: BookingConfirmationView,
    },
    {
        path: "/login",
        name: "login",
        component: LoginView,
    },
    {
        path: "/my-bookings",
        name: "my-bookings",
        component: MyBookingsView,
        meta: { requiresAuth: true },
    },
    {
        path: "/my-bookings/:id",
        name: "my-booking-detail",
        component: MyBookingDetailView,
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory("/"),
    routes,
});

// Navigation guard for protected routes
router.beforeEach((to, _, next) => {
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
    const isLoggedIn = !!localStorage.getItem("token");

    if (requiresAuth && !isLoggedIn) {
        next("/login");
    } else {
        next();
    }
});

export default router;
