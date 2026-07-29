<template>
    <div class="client-page client-page--auth">
        <div class="client-auth-shell client-auth-shell--register">
            <AuthWelcomePanel
                variant="register"
                eyebrow="Khởi đầu hành trình"
                title="Tạo chiến binh"
                description="Tạo tài khoản an toàn, vào game dựng nhân vật và bắt đầu khám phá Horizon."
            />

            <section class="client-panel client-auth-form">
                <header class="client-auth-form__head">
                    <span class="client-panel__eyebrow">Tài khoản mới</span>
                    <h2>Đăng ký</h2>
                    <p>Thông tin này dùng trực tiếp để đăng nhập vào game.</p>
                </header>

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

                <form novalidate @submit.prevent="handleRegister">
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
                                minlength="6"
                                maxlength="18"
                                placeholder="6–18 chữ hoặc số"
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
                                autocomplete="new-password"
                                minlength="6"
                                maxlength="18"
                                placeholder="6–18 ký tự"
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

                    <label class="client-field">
                        <span>Nhập lại mật khẩu</span>
                        <span class="client-auth-input">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            <input
                                v-model="confirmPassword"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                maxlength="18"
                                placeholder="Nhập lại mật khẩu"
                                required
                            />
                        </span>
                        <small
                            v-if="confirmPassword"
                            class="client-auth-field-hint"
                            :class="{ valid: passwordsMatch }"
                        >
                            {{
                                passwordsMatch
                                    ? "Mật khẩu đã khớp."
                                    : "Mật khẩu chưa khớp."
                            }}
                        </small>
                    </label>

                    <button
                        type="submit"
                        class="client-btn client-btn--primary client-auth-submit"
                        :disabled="loading"
                    >
                        <span v-if="loading" class="btn-loading-dot"></span>
                        <i
                            v-else
                            class="fa-solid fa-user-plus"
                            aria-hidden="true"
                        ></i>
                        {{
                            loading ? "Đang tạo tài khoản..." : "Tạo tài khoản"
                        }}
                    </button>
                </form>

                <p class="client-auth-note">
                    Đã có tài khoản?
                    <router-link :to="loginRoute">Đăng nhập ngay</router-link>
                </p>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios, { AxiosError } from "axios";
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AuthWelcomePanel from "../components/auth/AuthWelcomePanel.vue";

interface RegisterResponse {
    status: "success" | "error";
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
const confirmPassword = ref("");
const error = ref("");
const loading = ref(false);
const showPassword = ref(false);

const usernameValid = computed(() =>
    /^[A-Za-z0-9]{6,18}$/.test(username.value),
);
const passwordChecks = computed(() => ({
    length: password.value.length >= 6 && password.value.length <= 18,
    letter: /[A-Za-z]/.test(password.value),
    number: /\d/.test(password.value),
    allowed: /^[\x21-\x7E]+$/.test(password.value),
}));
const passwordValid = computed(() =>
    Object.values(passwordChecks.value).every(Boolean),
);
const passwordsMatch = computed(
    () => !!confirmPassword.value && password.value === confirmPassword.value,
);
const loginRoute = computed(() => ({
    path: "/login",
    query:
        typeof route.query.redirect === "string"
            ? { redirect: route.query.redirect }
            : {},
}));

function apiErrorMessage(caughtError: unknown): string {
    const requestError = caughtError as AxiosError<ApiError>;
    const validationMessage = Object.values(
        requestError.response?.data?.errors || {},
    ).flat()[0];

    return (
        validationMessage ||
        requestError.response?.data?.message ||
        "Không thể tạo tài khoản lúc này."
    );
}

async function handleRegister(): Promise<void> {
    error.value = "";

    if (!usernameValid.value) {
        error.value = "Tên đăng nhập phải có 6–18 ký tự, chỉ gồm chữ và số.";
        return;
    }

    if (!passwordValid.value) {
        error.value =
            "Mật khẩu cần 6–18 ký tự, có ít nhất một chữ và một số; ký tự đặc biệt không bắt buộc.";
        return;
    }

    if (!passwordsMatch.value) {
        error.value = "Mật khẩu nhập lại chưa khớp.";
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.post<RegisterResponse>(
            "/api/auth/register",
            {
                username: username.value,
                password: password.value,
                password_confirmation: confirmPassword.value,
            },
        );

        if (data.status !== "success") {
            error.value = data.message || "Đăng ký thất bại.";
            return;
        }

        const query: Record<string, string> = {
            registered: "1",
            username: username.value.toLowerCase(),
        };
        if (typeof route.query.redirect === "string") {
            query.redirect = route.query.redirect;
        }

        await router.push({ path: "/login", query });
    } catch (caughtError) {
        error.value = apiErrorMessage(caughtError);
    } finally {
        loading.value = false;
    }
}
</script>
