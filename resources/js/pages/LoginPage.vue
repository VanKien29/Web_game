<template>
    <div class="client-page client-page--auth">
        <div class="client-auth-standalone-head">
            <router-link
                to="/"
                class="pixel-brand"
                aria-label="Ngọc Rồng Horizon - Trang chủ"
            >
                <span class="pixel-orb" aria-hidden="true"></span>
                <span class="pixel-brand__copy">
                    <strong>Ngọc Rồng</strong>
                    <small>Horizon</small>
                </span>
            </router-link>
            <router-link to="/" class="client-auth-back">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                Về trang chủ
            </router-link>
        </div>

        <div class="client-auth-shell">
            <AuthWelcomePanel
                variant="login"
                eyebrow="Cổng chiến binh"
                title="Trở lại Horizon"
                description="Đăng nhập để tiếp tục hành trình, theo dõi nhân vật và kết nối cùng cộng đồng."
            />

            <section class="client-panel client-auth-form">
                <header class="client-auth-form__head">
                    <span class="client-panel__eyebrow"
                        >Tài khoản người chơi</span
                    >
                    <h2>Đăng nhập</h2>
                    <p>Dùng tài khoản game Ngọc Rồng Horizon của bạn.</p>
                </header>

                <div
                    v-if="notice"
                    class="client-auth-alert client-auth-alert--success"
                >
                    <i class="fa-solid fa-circle-dot" aria-hidden="true"></i>
                    <span>{{ notice }}</span>
                </div>
                <div
                    v-if="error"
                    class="client-auth-alert client-auth-alert--error"
                    role="alert"
                >
                    <i
                        class="fa-solid fa-triangle-exclamation"
                        aria-hidden="true"
                    ></i>
                    <span>{{ error }}</span>
                </div>

                <form novalidate @submit.prevent="handleLogin">
                    <label class="client-field">
                        <span>Tên đăng nhập</span>
                        <span class="client-auth-input">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            <input
                                v-model.trim="username"
                                type="text"
                                autocomplete="username"
                                autocapitalize="none"
                                spellcheck="false"
                                maxlength="18"
                                placeholder="Nhập tên đăng nhập"
                                required
                            />
                        </span>
                    </label>

                    <label class="client-field">
                        <span>Mật khẩu</span>
                        <span class="client-auth-input">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            <input
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                maxlength="64"
                                placeholder="Nhập mật khẩu"
                                required
                            />
                            <button
                                type="button"
                                class="client-auth-password-toggle"
                                :aria-label="
                                    showPassword
                                        ? 'Ẩn mật khẩu'
                                        : 'Hiện mật khẩu'
                                "
                                :aria-pressed="showPassword"
                                :title="
                                    showPassword
                                        ? 'Ẩn mật khẩu'
                                        : 'Hiện mật khẩu'
                                "
                                @click="showPassword = !showPassword"
                            >
                                <i
                                    class="fa-solid"
                                    :class="
                                        showPassword ? 'fa-eye-slash' : 'fa-eye'
                                    "
                                    aria-hidden="true"
                                ></i>
                            </button>
                        </span>
                    </label>

                    <button
                        type="submit"
                        class="client-btn client-btn--primary client-auth-submit"
                        :disabled="loading"
                    >
                        <span v-if="loading" class="btn-loading-dot"></span>
                        <i
                            v-else
                            class="fa-solid fa-user-lock"
                            aria-hidden="true"
                        ></i>
                        {{ loading ? "Đang xác thực..." : "Đăng nhập" }}
                    </button>
                </form>

                <p class="client-auth-note">
                    Chưa có tài khoản?
                    <router-link :to="registerRoute"
                        >Tạo tài khoản mới</router-link
                    >
                </p>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios, { AxiosError } from "axios";
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AuthWelcomePanel from "../components/auth/AuthWelcomePanel.vue";

interface AuthUser {
    id: number;
    username: string;
    is_admin: number;
    has_character: boolean;
    player_name?: string | null;
}

interface AuthResponse {
    status: "success" | "error";
    token?: string;
    user?: AuthUser;
    message?: string;
}

interface ApiError {
    message?: string;
    errors?: Record<string, string[]>;
}

const route = useRoute();
const router = useRouter();
const username = ref("");
const password = ref("");
const error = ref("");
const notice = ref("");
const loading = ref(false);
const showPassword = ref(false);

const registerRoute = computed(() => ({
    path: "/register",
    query:
        typeof route.query.redirect === "string"
            ? { redirect: route.query.redirect }
            : {},
}));

function intendedRoute(): string {
    const queryRedirect = Array.isArray(route.query.redirect)
        ? route.query.redirect[0]
        : route.query.redirect;

    return typeof queryRedirect === "string" &&
        queryRedirect.startsWith("/") &&
        !queryRedirect.startsWith("//")
        ? queryRedirect
        : "/";
}

function apiErrorMessage(caughtError: unknown): string {
    const requestError = caughtError as AxiosError<ApiError>;
    const validationMessage = Object.values(
        requestError.response?.data?.errors || {},
    ).flat()[0];

    return (
        validationMessage ||
        requestError.response?.data?.message ||
        "Không thể đăng nhập lúc này."
    );
}

async function handleLogin(): Promise<void> {
    error.value = "";
    notice.value = "";

    if (!/^[A-Za-z0-9]{1,18}$/.test(username.value)) {
        error.value = "Tên đăng nhập chỉ gồm chữ và số, tối đa 18 ký tự.";
        return;
    }

    if (!password.value || password.value.length > 64) {
        error.value = "Vui lòng kiểm tra lại mật khẩu.";
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.post<AuthResponse>("/api/auth/login", {
            username: username.value,
            password: password.value,
        });

        if (data.status !== "success" || !data.token || !data.user) {
            error.value = data.message || "Đăng nhập thất bại.";
            return;
        }

        localStorage.setItem("token", data.token);
        localStorage.setItem("user", JSON.stringify(data.user));
        window.dispatchEvent(new Event("auth-changed"));
        await router.push(intendedRoute());
    } catch (caughtError) {
        error.value = apiErrorMessage(caughtError);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (route.query.registered === "1") {
        notice.value = "Tạo tài khoản thành công. Bạn có thể đăng nhập ngay.";
    }

    const queryUsername = Array.isArray(route.query.username)
        ? route.query.username[0]
        : route.query.username;
    if (
        typeof queryUsername === "string" &&
        /^[A-Za-z0-9]{1,18}$/.test(queryUsername)
    ) {
        username.value = queryUsername;
    }
});
</script>
