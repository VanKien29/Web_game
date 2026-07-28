<template>
    <div class="client-page client-page--auth">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Đăng ký</span>
        </div>

        <div class="client-auth-shell">
            <AuthWelcomePanel
                eyebrow="Người chơi mới"
                title="Tạo chiến binh"
                description="Tạo tài khoản để bắt đầu hành trình tại Horizon."
            />

            <section class="client-panel client-auth-form">
                <div v-if="error" class="alert alert-error">{{ error }}</div>
                <div v-if="success" class="alert alert-success">
                    {{ success }}
                </div>

                <form novalidate @submit.prevent="handleRegister">
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
                            autocomplete="new-password"
                            placeholder="Mật khẩu"
                            required
                        />
                    </label>
                    <label class="client-field">
                        <span>Nhập lại mật khẩu</span>
                        <input
                            v-model="confirmPassword"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Nhập lại mật khẩu"
                            required
                        />
                    </label>
                    <button
                        type="submit"
                        class="client-btn client-btn--primary"
                        :disabled="loading"
                    >
                        <span v-if="loading" class="btn-loading-dot"></span>
                        {{ loading ? "Đang xử lý..." : "Tạo tài khoản" }}
                    </button>
                </form>

                <p class="client-auth-note">
                    Đã có tài khoản?
                    <router-link to="/login">Đăng nhập</router-link>
                </p>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios, { AxiosError } from "axios";
import { onBeforeUnmount, ref } from "vue";
import { useRouter } from "vue-router";
import AuthWelcomePanel from "../components/auth/AuthWelcomePanel.vue";

interface RegisterResponse {
    status: "success" | "error";
    message?: string;
}

interface ApiError {
    message?: string;
}

const router = useRouter();
const username = ref("");
const password = ref("");
const confirmPassword = ref("");
const error = ref("");
const success = ref("");
const loading = ref(false);
let redirectTimer: ReturnType<typeof window.setTimeout> | null = null;

async function handleRegister(): Promise<void> {
    error.value = "";
    success.value = "";

    if (!username.value || !password.value || !confirmPassword.value) {
        error.value = "Vui lòng nhập đầy đủ thông tin đăng ký";
        return;
    }

    if (password.value !== confirmPassword.value) {
        error.value = "Mật khẩu không khớp";
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.post<RegisterResponse>(
            "/api/auth/register",
            {
                username: username.value,
                password: password.value,
            },
        );

        if (data.status === "success") {
            success.value =
                "Đăng ký thành công! Đang chuyển đến trang đăng nhập...";
            redirectTimer = window.setTimeout(() => {
                void router.push("/login");
            }, 1600);
        } else {
            error.value = data.message || "Đăng ký thất bại";
        }
    } catch (caughtError) {
        const requestError = caughtError as AxiosError<ApiError>;
        error.value =
            requestError.response?.data?.message || "Đăng ký thất bại";
    } finally {
        loading.value = false;
    }
}

onBeforeUnmount(() => {
    if (redirectTimer) {
        window.clearTimeout(redirectTimer);
    }
});
</script>
