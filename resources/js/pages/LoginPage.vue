<template>
    <div class="client-page client-page--auth">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Đăng nhập</span>
        </div>
        <div class="client-auth-shell">
            <section class="client-panel client-auth-card">
                <div class="client-auth-art">
                    <img
                        src="/assets/frontend/home/v1/images/rtsc.png"
                        alt="Ngọc Rồng HDPE"
                        class="client-auth-logo"
                    />
                    <img
                        src="/assets/frontend/home/v1/images/goku.png"
                        alt=""
                        class="client-auth-hero"
                    />
                </div>
                <div class="client-panel__eyebrow">Tài khoản</div>
                <h1 class="client-panel__title">Đăng nhập</h1>
                <p class="client-panel__desc">
                    Vào tài khoản để nạp, nhận giftcode và theo dõi nhân vật.
                </p>
                <div class="client-auth-links">
                    <router-link to="/giftcode">Giftcode</router-link>
                    <router-link to="/bxh">Bảng xếp hạng</router-link>
                    <router-link to="/nap-atm">Nạp tiền</router-link>
                </div>
            </section>
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
                            placeholder="Tên đăng nhập"
                            required
                        />
                    </label>
                    <label class="client-field">
                        <span>Mật khẩu</span>
                        <input
                            v-model="password"
                            type="password"
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

<script>
import axios from "axios";

export default {
    name: "LoginPage",
    data() {
        return {
            username: "",
            password: "",
            error: "",
            success: "",
            loading: false,
        };
    },
    methods: {
        async handleLogin() {
            this.error = "";
            if (!this.username || !this.password) {
                this.error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu";
                return;
            }

            this.loading = true;
            try {
                const { data } = await axios.post("/api/auth/login", {
                    username: this.username,
                    password: this.password,
                });
                if (data.status === "success") {
                    localStorage.setItem("token", data.token);
                    localStorage.setItem("user", JSON.stringify(data.user));
                    window.dispatchEvent(new Event("auth-changed"));
                    this.$router.push("/");
                } else {
                    this.error = data.message || "Đăng nhập thất bại";
                }
            } catch (err) {
                this.error =
                    err.response?.data?.message || "Đăng nhập thất bại";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
