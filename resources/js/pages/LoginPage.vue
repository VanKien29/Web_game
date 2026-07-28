<template>
    <div class="client-page client-page--auth">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Đăng nhập</span>
        </div>

        <div class="client-auth-shell">
            <AuthWelcomePanel
                eyebrow="Tài khoản"
                title="Đăng nhập"
                description="Đăng nhập để nạp, nhận giftcode và theo dõi nhân vật."
            />

            <section class="client-panel client-auth-form">
                <div v-if="error" class="alert alert-error">{{ error }}</div>
                <div v-if="success" class="alert alert-success">
                    {{ success }}
                </div>

                <form novalidate @submit.prevent="handleLogin">
                    <label class="client-field">
                        <span>Tên đăng nhập</span>
                        <input
                            v-model.trim="username"
                            type="text"
                            autocomplete="username"
                            placeholder="Tên đăng nhập"
                            required
                        />
                    </label>
                    <label class="client-field">
                        <span>Mật khẩu</span>
                        <input
                            v-model="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Mật khẩu"
                            required
                        />
                    </label>
                    <button
                        type="submit"
                        class="client-btn client-btn--primary"
                        :disabled="loading"
                    >
                        <span v-if="loading" class="btn-loading-dot"></span>
                        {{ loading ? "Đang xử lý..." : "Đăng nhập" }}
                    </button>
                </form>

                <p class="client-auth-note">
                    Bạn chưa có tài khoản?
                    <router-link to="/register">Đăng ký ngay</router-link>
                </p>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios, { AxiosError } from "axios";
import { ref } from "vue";
import { useRouter } from "vue-router";
import AuthWelcomePanel from "../components/auth/AuthWelcomePanel.vue";

interface AuthResponse {
    status: "success" | "error";
    token?: string;
    user?: unknown;
    message?: string;
}

interface ApiError {
    message?: string;
}

const router = useRouter();
const username = ref("");
const password = ref("");
const error = ref("");
const success = ref("");
const loading = ref(false);

async function handleLogin(): Promise<void> {
    error.value = "";
    success.value = "";

    if (!username.value || !password.value) {
        error.value = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu";
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.post<AuthResponse>("/api/auth/login", {
            username: username.value,
            password: password.value,
        });

        if (data.status === "success" && data.token) {
            localStorage.setItem("token", data.token);
            localStorage.setItem("user", JSON.stringify(data.user || {}));
            window.dispatchEvent(new Event("auth-changed"));
            await router.push("/");
        } else {
            error.value = data.message || "Đăng nhập thất bại";
        }
    } catch (caughtError) {
        const requestError = caughtError as AxiosError<ApiError>;
        error.value =
            requestError.response?.data?.message || "Đăng nhập thất bại";
    } finally {
        loading.value = false;
    }
}
</script>
