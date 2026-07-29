import { createApp } from "vue";
import "@fortawesome/fontawesome-free/css/fontawesome.min.css";
import "@fortawesome/fontawesome-free/css/solid.min.css";
import "../css/app.css";
import App from "./App.vue";
import router from "./router";
import { createAutoDismissMessages } from "./shared/autoDismissMessages";

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
