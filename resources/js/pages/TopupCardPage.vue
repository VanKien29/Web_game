<template>
    <div class="client-page client-page--topup">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Nạp Thẻ Cào</span>
        </div>

        <div class="client-page-head client-page-head--split">
            <div>
                <div class="client-panel__eyebrow">Thanh toán</div>
                <h1 class="client-panel__title">Nạp thẻ cào</h1>
            </div>
            <div class="client-segment">
                <router-link to="/nap-atm">Nạp ATM</router-link>
                <router-link to="/nap-card" class="active">Nạp thẻ cào</router-link>
            </div>
        </div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>

        <div v-else class="topup-grid topup-grid--card">
            <section class="client-panel topup-box">
                <form @submit.prevent="submitCard">
                    <label class="client-field form-group">
                        <span>Tên nhân vật</span>
                        <input type="text" :value="username" readonly />
                    </label>
                    <label class="client-field form-group">
                        <span>Loại thẻ</span>
                        <select v-model="cardType">
                            <option value="">-- Chọn loại thẻ --</option>
                            <option value="Viettel">Viettel</option>
                            <option value="Mobifone">Mobifone</option>
                            <option value="Vinaphone">Vinaphone</option>
                            <option value="Vietnamobile">Vietnamobile</option>
                        </select>
                    </label>
                    <label class="client-field form-group">
                        <span>Mệnh giá</span>
                        <select v-model="cardAmount">
                            <option value="">-- Chọn mệnh giá --</option>
                            <option value="10000">10.000 đ</option>
                            <option value="20000">20.000 đ</option>
                            <option value="50000">50.000 đ</option>
                            <option value="100000">100.000 đ</option>
                            <option value="200000">200.000 đ</option>
                            <option value="500000">500.000 đ</option>
                        </select>
                    </label>
                    <label class="client-field form-group">
                        <span>Số serial</span>
                        <input
                            v-model="serial"
                            type="text"
                            placeholder="Nhập số serial thẻ"
                            required
                        />
                    </label>
                    <label class="client-field form-group">
                        <span>Mã thẻ</span>
                        <input
                            v-model="pin"
                            type="text"
                            placeholder="Nhập mã thẻ"
                            required
                        />
                    </label>
                    <div
                        v-if="formMessage"
                        :class="
                            formMessageType === 'success'
                                ? 'msg-success'
                                : 'msg-error'
                        "
                    >
                        {{ formMessage }}
                    </div>
                    <button
                        type="submit"
                        class="client-btn client-btn--primary"
                        :disabled="submitting"
                    >
                        {{ submitting ? "Đang gửi..." : "Nạp thẻ" }}
                    </button>
                </form>
            </section>

            <section class="client-panel side-box">
                <div class="note">
                    <strong>Lưu ý quan trọng:</strong>
                    <p>Kiểm tra kỹ thông tin trước khi nạp</p>
                    <p>Nạp sai loại/mệnh giá sẽ không được hoàn tiền</p>
                    <p>Sau 5 phút chưa được cộng, liên hệ Admin</p>
                </div>

                <div class="history">
                    <h3>Lịch sử nạp</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Loại Thẻ</th>
                                <th>Seri</th>
                                <th>Mã Thẻ</th>
                                <th>Mệnh Giá</th>
                                <th>Thời Gian</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!cardHistory.length">
                                <td colspan="7">
                                    Chưa có giao dịch nào
                                </td>
                            </tr>
                            <tr v-for="(tx, i) in cardHistory" :key="tx.id">
                                <td>{{ i + 1 }}</td>
                                <td>{{ tx.card_type }}</td>
                                <td>{{ tx.serial }}</td>
                                <td>{{ tx.pin }}</td>
                                <td>
                                    {{ Number(tx.amount).toLocaleString() }}đ
                                </td>
                                <td>{{ formatDate(tx.created_at) }}</td>
                                <td>
                                    {{
                                        tx.status === 1
                                            ? "Thành công"
                                            : tx.status === 2
                                              ? "Thất bại"
                                              : "Chờ"
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "TopupCardPage",
    data() {
        return {
            cardType: "",
            cardAmount: "",
            serial: "",
            pin: "",
            cardHistory: [],
            formMessage: "",
            formMessageType: "",
            submitting: false,
            loading: true,
        };
    },
    computed: {
        username() {
            try {
                return (
                    JSON.parse(localStorage.getItem("user") || "{}").username ||
                    ""
                );
            } catch {
                return "";
            }
        },
    },
    methods: {
        formatDate(d) {
            return d ? new Date(d).toLocaleString("vi-VN") : "";
        },
        getAuthHeaders() {
            return {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
            };
        },
        async submitCard() {
            this.formMessage = "";
            if (
                !this.cardType ||
                !this.cardAmount ||
                !this.serial ||
                !this.pin
            ) {
                this.formMessage = "Vui lòng điền đầy đủ thông tin";
                this.formMessageType = "error";
                return;
            }
            this.submitting = true;
            try {
                const { data } = await axios.post(
                    "/api/topup/card",
                    {
                        card_type: this.cardType,
                        amount: this.cardAmount,
                        serial: this.serial,
                        pin: this.pin,
                    },
                    this.getAuthHeaders(),
                );
                this.formMessage = data.message || "Gửi thẻ thành công";
                this.formMessageType = data.ok ? "success" : "error";
                if (data.ok) {
                    this.serial = "";
                    this.pin = "";
                    this.loadHistory();
                }
            } catch (err) {
                this.formMessage = err.response?.data?.message || "Lỗi gửi thẻ";
                this.formMessageType = "error";
            } finally {
                this.submitting = false;
            }
        },
        async loadHistory() {
            try {
                const { data } = await axios.get(
                    "/api/topup/card/history",
                    this.getAuthHeaders(),
                );
                if (data.ok) this.cardHistory = data.data || [];
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        const token = localStorage.getItem("token");
        if (!token) {
            this.$router.push("/login");
            return;
        }
        this.loadHistory();
    },
};
</script>
