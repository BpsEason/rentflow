import axios from "axios";

// 從目前URL路徑中提取租戶slug
function getCurrentTenantSlug(): string | null {
    const pathParts = window.location.pathname.split("/").filter(Boolean);
    return pathParts.length > 0 ? pathParts[0] : null;
}

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || "/api/v1",
    headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

// 請求攔截器：加入認證 token 和動態從URL提取的租戶識別
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("token");
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        // 處理API請求，將tenantSlug加入URL路徑 - 避免重複拼接
        const tenantSlug = getCurrentTenantSlug();
        if (tenantSlug && config.url) {
            // 檢查URL是否已經包含tenantSlug，避免重複拼接
            const urlHasTenant = config.url.includes(`/${tenantSlug}/`);
            if (!urlHasTenant) {
                if (config.url.includes("/public/")) {
                    // 替換URL中的 /public/ 為 /public/{tenantSlug}/
                    config.url = config.url.replace(
                        "/public/",
                        `/public/${tenantSlug}/`,
                    );
                } else if (config.url.includes("/customer/")) {
                    // 替換URL中的 /customer/ 為 /{tenantSlug}/customer/
                    config.url = config.url.replace(
                        "/customer/",
                        `/${tenantSlug}/customer/`,
                    );
                }
            }
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
            // 從目前URL路徑中提取租戶slug，保持正確的租戶前綴
            const pathParts = window.location.pathname
                .split("/")
                .filter(Boolean);
            const tenantSlug = pathParts.length > 0 ? pathParts[0] : "default";
            // 導向到正確的租戶登入頁面
            window.location.href = `/${tenantSlug}/login`;
        }
        return Promise.reject(error);
    },
);

export default api;
