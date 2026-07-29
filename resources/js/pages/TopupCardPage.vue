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
                        <span>Tài khoản nhận</span>
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

            <section class="client-panel topup-card-guide-panel">
                <header class="topup-card-section-heading">
                    <div>
                        <span>Lưu ý</span>
                        <h2>Lưu ý quan trọng</h2>
                    </div>
                </header>
                <ol>
                    <li>
                        Vui lòng kiểm tra kỹ thông tin trước khi gửi thẻ.
                    </li>
                    <li>
                        Yêu cầu nhập đúng số serial và mã thẻ.
                    </li>
                    <li>
                        Thẻ cào đã sử dụng hoặc hết hạn sẽ không được chấp
                        nhận.
                    </li>
                </ol>
                <p>
                    Nạp sai loại thẻ hoặc mệnh giá sẽ không được hoàn tiền.
                    Nếu sau 5 phút chưa được cộng, hãy liên hệ Admin.
                </p>
            </section>

            <TopupHistoryPanel
                :entries="historyEntries"
                title="Lịch sử nạp thẻ"
                :page-size="5"
            />
        </div>
    </div>
</template>

<script>
import axios from "axios";
import TopupHistoryPanel from "../components/topup/TopupHistoryPanel.vue";

export default {
    name: "TopupCardPage",
    components: {
        TopupHistoryPanel,
    },
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
        historyEntries() {
            return this.cardHistory.map((transaction) => ({
                id: transaction.id,
                createdAt: transaction.created_at || "",
                channel: transaction.card_type || "Thẻ cào",
                amount: Number(transaction.amount) || 0,
                reference: this.maskReference(transaction.serial),
                status:
                    transaction.status === 1
                        ? "success"
                        : transaction.status === 2
                          ? "failed"
                          : "pending",
            }));
        },
    },
    methods: {
        maskReference(value) {
            const reference = String(value || "");
            if (!reference) return "—";
            if (reference.length <= 4) return reference;
            return `•••• ${reference.slice(-4)}`;
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
                if (data.trans_id) {
                    this.loadHistory();
                }
                if (data.ok) {
                    this.serial = "";
                    this.pin = "";
                }
            } catch (err) {
                this.formMessage = err.response?.data?.message || "Lỗi gửi thẻ";
                this.formMessageType = "error";
                if (err.response?.data?.trans_id) {
                    this.loadHistory();
                }
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

<style scoped>
.topup-card-guide-panel {
    padding: 0 !important;
    overflow: hidden;
}

.topup-card-section-heading {
    display: flex;
    min-height: 74px;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 18px;
    background: var(--pixel-paper, #fff0c7);
    border-bottom: 2px solid var(--pixel-line, #7a4829);
}

.topup-card-section-heading span {
    display: block;
    margin-bottom: 3px;
    color: var(--pixel-orange-dark, #a7440d);
    font-family: var(--pixel-font);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.topup-card-section-heading h2 {
    margin: 0;
    color: var(--pixel-ink, #36251d);
    font-family: var(--font-sans);
    font-size: 1.1rem;
}

.topup-card-guide-panel ol {
    display: grid;
    gap: 12px;
    margin: 0;
    padding: 18px 18px 14px 46px;
}

.topup-card-guide-panel li {
    padding-left: 3px;
    color: var(--pixel-ink, #36251d);
    font-size: 0.78rem;
    line-height: 1.55;
}

.topup-card-guide-panel p {
    margin: 0 16px 16px;
    padding: 11px;
    color: #8a3b22;
    background: #ffedbd;
    border: 1px dashed #c7782a;
    font-size: 0.72rem;
    line-height: 1.5;
}

@media (max-width: 600px) {
    .topup-card-section-heading {
        min-height: 0;
        align-items: flex-start;
        padding: 12px;
    }
}
</style>
