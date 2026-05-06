<template>
    <div class="client-page client-page--auth">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Đăng ký</span>
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
                <div class="client-panel__eyebrow">Người chơi mới</div>
                <h1 class="client-panel__title">Đăng ký</h1>
                <p class="client-panel__desc">
                    Tạo tài khoản nhanh để bắt đầu chơi và quản lý giao dịch.
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
                <form novalidate @submit.prevent="handleRegister">
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
                    <label class="client-field">
                        <span>Nhập lại mật khẩu</span>
                        <input
                            v-model="confirmPassword"
                            type="password"
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
                        {{ loading ? "Đang xử lý..." : "Đăng ký" }}
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

<script>
import axios from "axios";

export default {
    name: "RegisterPage",
    data() {
        return {
            username: "",
            password: "",
            confirmPassword: "",
            error: "",
            success: "",
            loading: false,
        };
    },
    methods: {
        async handleRegister() {
            this.error = "";
            this.success = "";
            if (!this.username || !this.password || !this.confirmPassword) {
                this.error = "Vui lòng nhập đầy đủ thông tin đăng ký";
                return;
            }

            if (this.password !== this.confirmPassword) {
                this.error = "Mật khẩu không khớp";
                return;
            }
            this.loading = true;
            try {
                const { data } = await axios.post("/api/auth/register", {
                    username: this.username,
                    password: this.password,
                });
                if (data.status === "success") {
                    this.success =
                        "Đăng ký thành công! Đang chuyển đến trang đăng nhập...";
                    setTimeout(() => this.$router.push("/login"), 2000);
                } else {
                    this.error = data.message || "Đăng ký thất bại";
                }
            } catch (err) {
                this.error = err.response?.data?.message || "Đăng ký thất bại";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
