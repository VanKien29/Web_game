<template>
    <div class="client-page client-page--profile">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Thông tin cá nhân</span>
        </div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>

        <div v-else-if="profile" class="profile-dashboard">
            <div
                v-if="message"
                class="alert"
                :class="
                    messageType === 'success' ? 'alert-success' : 'alert-error'
                "
            >
                {{ message }}
            </div>

            <section class="client-panel profile-hero-card">
                <div class="profile-hero-card__main">
                    <img :src="avatarUrl" class="profile-avatar" />
                    <div>
                        <div class="client-panel__eyebrow">Tài khoản</div>
                        <h1 class="client-panel__title">
                            {{ profile.user.username }}
                        </h1>
                        <span
                            class="status-badge"
                            :class="profile.user.active ? 'status-active' : 'status-pending'"
                        >
                            {{ profile.user.active ? "Đã kích hoạt" : "Chưa kích hoạt" }}
                        </span>
                    </div>
                </div>
                <div class="profile-balance-grid">
                    <div class="profile-balance-card">
                        <span>Số tiền còn lại</span>
                        <strong>{{ formatNumber(profile.user.cash) }} VNĐ</strong>
                    </div>
                    <div class="profile-balance-card">
                        <span>Số tiền đã nạp</span>
                        <strong>{{ formatNumber(profile.user.danap) }} VNĐ</strong>
                    </div>
                </div>
            </section>

            <section
                v-if="player.has_character"
                class="client-panel profile-card profile-card--character"
            >
                <div class="profile-card__head">
                    <div class="client-panel__eyebrow">Nhân vật</div>
                    <h2 class="profile-section-title">Nhân vật</h2>
                </div>
                <dl class="profile-meta profile-meta--compact">
                    <div>
                        <dt>Tên</dt>
                        <dd>{{ player.name }}</dd>
                    </div>
                    <div>
                        <dt>Hành tinh</dt>
                        <dd>{{ player.gender_text }}</dd>
                    </div>
                    <div>
                        <dt>Nhiệm vụ</dt>
                        <dd>{{ player.task_name }}</dd>
                    </div>
                </dl>
                <div class="profile-stat-grid">
                    <div>
                        <span>Sức mạnh</span>
                        <strong>{{ formatNumber(player.power) }}</strong>
                    </div>
                    <div>
                        <span>Tiềm năng</span>
                        <strong>{{ formatNumber(stats.potential) }}</strong>
                    </div>
                    <div>
                        <span>HP</span>
                        <strong>{{ formatNumber(stats.hp) }}</strong>
                    </div>
                    <div>
                        <span>KI</span>
                        <strong>{{ formatNumber(stats.ki) }}</strong>
                    </div>
                    <div>
                        <span>Sức đánh</span>
                        <strong>{{ formatNumber(stats.damage) }}</strong>
                    </div>
                    <div>
                        <span>Giáp / Chí mạng</span>
                        <strong>{{ formatNumber(stats.defense) }} / {{ formatNumber(stats.critical) }}%</strong>
                    </div>
                </div>
            </section>

            <section v-else class="client-panel client-empty profile-empty">
                Chưa tạo nhân vật trong game.
            </section>

            <section class="client-panel profile-card profile-card--wallet">
                <div class="profile-card__head">
                    <div class="client-panel__eyebrow">Tài sản game</div>
                    <h2 class="profile-section-title">Hành trang</h2>
                </div>
                <div class="profile-wallet-grid">
                    <div>
                        <span>Vàng</span>
                        <strong>{{ formatNumber(inventory.gold) }}</strong>
                    </div>
                    <div>
                        <span>Ngọc xanh</span>
                        <strong>{{ formatNumber(inventory.gem) }}</strong>
                    </div>
                    <div>
                        <span>Hồng ngọc</span>
                        <strong>{{ formatNumber(inventory.ruby) }}</strong>
                    </div>
                    <div>
                        <span>Thỏi vàng</span>
                        <strong>{{ formatNumber(inventory.thoi_vang) }}</strong>
                    </div>
                </div>
            </section>

            <section class="client-panel profile-actions">
                <div
                    v-if="!profile.user.active"
                    class="button-grid-2x2"
                >
                    <button
                        class="action-btn"
                        @click="showPasswordModal = true"
                    >
                        Đổi mật khẩu
                    </button>
                    <button
                        class="action-btn"
                        @click="showActivateModal = true"
                    >
                        Kích hoạt
                    </button>
                    <a
                        href="https://zalo.me/g/tkdeeb069"
                        class="action-btn"
                        >Nhóm Zalo</a
                    >
                    <button class="action-btn" @click="logout">
                        Đăng xuất
                    </button>
                </div>
                <div
                    v-else
                    class="button-grid-1x3"
                >
                    <button
                        class="action-btn"
                        @click="showPasswordModal = true"
                    >
                        Đổi mật khẩu
                    </button>
                    <a
                        href="https://zalo.me/g/tkdeeb069"
                        class="action-btn"
                        >Nhóm Zalo</a
                    >
                    <button class="action-btn" @click="logout">
                        Đăng xuất
                    </button>
                </div>
            </section>

            <div class="modal-overlay" :class="{ active: showPasswordModal }">
                <div class="modal">
                    <h3>Đổi mật khẩu</h3>
                    <form @submit.prevent="changePassword">
                        <input
                            v-model="newPassword"
                            type="password"
                            placeholder="Mật khẩu mới"
                            required
                        />
                        <input
                            v-model="confirmNewPassword"
                            type="password"
                            placeholder="Xác nhận mật khẩu"
                            required
                        />
                        <div class="btn-row">
                            <button type="submit" class="btn-success">
                                Cập nhật
                            </button>
                            <button
                                type="button"
                                class="btn-danger"
                                @click="showPasswordModal = false"
                            >
                                Hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-overlay" :class="{ active: showActivateModal }">
                <div class="modal">
                    <h3>Kích hoạt tài khoản</h3>
                    <p class="modal-copy">
                        Kích hoạt sẽ tốn 10.000 VNĐ từ tài khoản game. Bạn có
                        chắc chắn không?
                    </p>
                    <div class="modal-actions">
                        <button
                            class="btn-success"
                            @click="activateAccount"
                        >
                            Xác nhận
                        </button>
                        <button
                            class="btn-danger"
                            @click="showActivateModal = false"
                        >
                            Hủy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ProfilePage",
    data() {
        return {
            profile: null,
            loading: true,
            message: "",
            messageType: "",
            showPasswordModal: false,
            showActivateModal: false,
            newPassword: "",
            confirmNewPassword: "",
        };
    },
    computed: {
        player() {
            return this.profile?.player || { has_character: false };
        },
        stats() {
            return this.player.stats || {};
        },
        inventory() {
            return this.player.inventory || {};
        },
        avatarUrl() {
            return (
                this.player.avatar_url ||
                "/assets/frontend/home/v1/images/bannergame.png"
            );
        },
    },
    methods: {
        formatNumber(value) {
            const number = Number(value || 0);
            return Number.isFinite(number)
                ? number.toLocaleString("vi-VN")
                : "0";
        },
        getAuthHeaders() {
            return {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
            };
        },
        async changePassword() {
            if (this.newPassword !== this.confirmNewPassword) {
                this.message = "Mật khẩu không khớp";
                this.messageType = "error";
                return;
            }
            try {
                const { data } = await axios.post(
                    "/api/change-password",
                    { new_password: this.newPassword },
                    this.getAuthHeaders(),
                );
                this.message = data.message || "Đổi mật khẩu thành công";
                this.messageType = data.ok ? "success" : "error";
                if (data.ok) this.showPasswordModal = false;
            } catch (err) {
                this.message = err.response?.data?.message || "Lỗi";
                this.messageType = "error";
            }
        },
        async activateAccount() {
            try {
                const { data } = await axios.post(
                    "/api/activate",
                    {},
                    this.getAuthHeaders(),
                );
                this.message = data.message || "Kích hoạt thành công";
                this.messageType = data.ok ? "success" : "error";
                if (data.ok) {
                    this.showActivateModal = false;
                    this.profile.user.active = 1;
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Lỗi";
                this.messageType = "error";
            }
        },
        logout() {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            this.$router.push("/");
            window.location.reload();
        },
    },
    async mounted() {
        const token = localStorage.getItem("token");
        if (!token) {
            this.$router.push("/login");
            return;
        }
        try {
            const { data } = await axios.get(
                "/api/profile",
                this.getAuthHeaders(),
            );
            if (data.ok) this.profile = data.data;
        } catch (err) {
            if (err.response?.status === 401) {
                localStorage.removeItem("token");
                localStorage.removeItem("user");
                this.$router.push("/login");
            }
        } finally {
            this.loading = false;
        }
    },
};
</script>
