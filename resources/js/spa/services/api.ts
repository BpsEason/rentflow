import axios from "axios";

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || "/api/v1",
    headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

// 請求攔截器：加入認證 token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("token");
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    },
);

// 回應攔截器：統一處理錯誤
api.interceptors.response.use(
    (response) => response,
    (error) => {
        // 處理 401 未授權錯誤
        if (error.response?.status === 401) {
            localStorage.removeItem("token");
            // 可以在這裡導向登入頁面
            window.location.href = "/login";
        }
        return Promise.reject(error);
    },
);

export default api;
