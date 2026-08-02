import { createApp } from "vue";
import axios from "axios";
import "@fortawesome/fontawesome-free/css/fontawesome.min.css";
import "@fortawesome/fontawesome-free/css/solid.min.css";
import "../css/app.css";
import App from "./App.vue";
import router from "./router";
import { createAutoDismissMessages } from "./shared/autoDismissMessages";

const publicAuthEndpoints = ["/api/auth/login", "/api/auth/register"];

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status;
        const responseError = error?.response?.data?.error;
        const requestUrl = String(error?.config?.url || "");
        const isAuthEndpoint = publicAuthEndpoints.some((endpoint) =>
            requestUrl.includes(endpoint),
        );
        const shouldClearSession =
            status === 401 || (status === 403 && responseError === "account_locked");

        if (
            typeof window !== "undefined" &&
            shouldClearSession &&
            !isAuthEndpoint &&
            localStorage.getItem("token")
        ) {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            localStorage.removeItem("game_last_activity_at");
            window.dispatchEvent(
                new CustomEvent("auth-changed", {
                    detail: { reason: "expired" },
                }),
            );
        }

        return Promise.reject(error);
    },
);

const app = createApp(App);
app.mixin(createAutoDismissMessages({ delay: 4600 }));
app.use(router);
app.config.errorHandler = (error, _instance, info) => {
    console.error(`[Vue] ${info}`, error);
};

try {
    app.mount("#app");
} catch (error) {
    console.error("[Vue] Failed to mount client application", error);
    const root = document.getElementById("app");
    if (root) {
        root.innerHTML = `
            <main class="client-boot-error" role="alert">
                <strong>Không thể khởi động giao diện.</strong>
                <span>Vui lòng tải lại trang hoặc thử lại sau.</span>
            </main>
        `;
    }
}
