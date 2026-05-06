<template>
    <div class="client-page client-page--topup">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Nạp ATM</span>
        </div>

        <div class="client-page-head client-page-head--split">
            <div>
                <div class="client-panel__eyebrow">Thanh toán</div>
                <h1 class="client-panel__title">Nạp ATM</h1>
            </div>
            <div class="client-segment">
                <router-link to="/nap-atm" class="active">Nạp ATM</router-link>
                <router-link to="/nap-card">Nạp thẻ cào</router-link>
            </div>
        </div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>

        <div v-else class="topup-grid">
            <section class="client-panel topup-box topup-box--qr">
                <div class="qr-box" :class="{ 'qr-box--disabled': isAmountTooLow }">
                    <img
                        v-if="qrUrl"
                        :src="qrUrl"
                        alt="QR"
                        :class="{ 'qr-image--disabled': isAmountTooLow }"
                        @error="handleQRError"
                    />
                    <div v-if="qrUrl && isAmountTooLow" class="qr-disabled-overlay">
                        Tối thiểu 10.000đ
                    </div>
                    <div v-else-if="!qrUrl" class="client-empty">
                        Chưa thể tạo mã QR. Vui lòng kiểm tra cấu hình ngân hàng
                        và số tiền nạp.
                    </div>
                    <div class="qr-caption">
                        Quét mã QR để thanh toán
                    </div>
                </div>

                <table class="table-info">
                    <tbody>
                        <tr>
                            <td>Ngân hàng:</td>
                            <td>
                                <b>{{ bankName }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td>Số TK:</td>
                            <td>
                                <b>{{ bankAccount }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td>Chủ TK:</td>
                            <td>{{ bankOwner }}</td>
                        </tr>
                        <tr>
                            <td>Số tiền nạp:</td>
                            <td>
                                <input
                                    v-model.number="amount"
                                    type="number"
                                    min="10000"
                                    placeholder="10000"
                                    @input="updateQR"
                                />
                            </td>
                        </tr>
                        <tr>
                            <td>Nội dung CK:</td>
                            <td>
                                <b>{{ transferContent }}</b>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="client-note-inline">
                    Tối thiểu: 10.000đ. Nội dung chuyển khoản phải giữ đúng
                    như trên để hệ thống tự cộng tiền.
                </p>
            </section>

            <section class="client-panel side-box">
                <div class="note">
                    <b>Lưu ý quan trọng:</b>
                    <ul>
                        <li>Vui lòng nhập đúng nội dung chuyển khoản</li>
                        <li>
                            Sai nội dung hoặc sai số tiền không chịu trách
                            nhiệm.
                        </li>
                        <li>Hệ thống xử lý tự động trong 30s - 2 phút.</li>
                    </ul>
                </div>

                <div class="history">
                    <h3>Lịch sử nạp</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!history.length">
                                <td colspan="3">
                                    Chưa có giao dịch nào
                                </td>
                            </tr>
                            <tr v-for="tx in paginatedHistory" :key="tx.id">
                                <td>{{ formatDate(tx.created_at) }}</td>
                                <td>
                                    {{ Number(tx.amount).toLocaleString() }}đ
                                </td>
                                <td>
                                    {{
                                        tx.status === 1
                                            ? "Thành công"
                                            : "Chờ xử lý"
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="totalPages > 1" class="pagination">
                        <button
                            class="page-btn"
                            :disabled="currentPage <= 1"
                            @click="currentPage--"
                        >
                            «
                        </button>
                        <button
                            v-for="p in totalPages"
                            :key="p"
                            class="page-btn"
                            :class="{ active: p === currentPage }"
                            @click="currentPage = p"
                        >
                            {{ p }}
                        </button>
                        <button
                            class="page-btn"
                            :disabled="currentPage >= totalPages"
                            @click="currentPage++"
                        >
                            »
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "TopupAtmPage",
    data() {
        return {
            amount: 10000,
            bankName: "",
            bankAccount: "",
            bankOwner: "",
            transferContent: "",
            qrUrl: "",
            qrCandidates: [],
            qrAttemptIndex: 0,
            history: [],
            currentPage: 1,
            perPage: 10,
            loading: true,
        };
    },
    computed: {
        totalPages() {
            return Math.ceil(this.history.length / this.perPage);
        },
        paginatedHistory() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.history.slice(start, start + this.perPage);
        },
        isAmountTooLow() {
            return (Number(this.amount) || 0) < 10000;
        },
        currentUsername() {
            try {
                return (
                    JSON.parse(localStorage.getItem("user") || "{}")
                        .username || ""
                )
                    .trim()
                    .toLowerCase();
            } catch {
                return "";
            }
        },
        bankIdentifiers() {
            const value = String(this.bankName || "").trim();
            const normalized = value.toUpperCase().replace(/[^A-Z0-9]/g, "");
            const aliases = {
                TPBANK: ["970423", "TPB", "TPBank"],
                TIENPHONGBANK: ["970423", "TPB", "TPBank"],
                MBBANK: ["970422", "MB", "MBBank"],
                MILITARYBANK: ["970422", "MB", "MBBank"],
                VIETCOMBANK: ["970436", "VCB", "Vietcombank"],
                TECHCOMBANK: ["970407", "TCB", "Techcombank"],
                VIETINBANK: ["970415", "ICB", "Vietinbank"],
                BIDV: ["970418", "BIDV"],
                AGRIBANK: ["970405", "VBA", "Agribank"],
                ACB: ["970416", "ACB"],
                VPBANK: ["970432", "VPB", "VPBank"],
                SACOMBANK: ["970403", "STB", "Sacombank"],
            };

            return [...new Set(aliases[normalized] || [normalized])].filter(Boolean);
        },
    },
    methods: {
        formatDate(d) {
            return d ? new Date(d).toLocaleString("vi-VN") : "";
        },
        buildTransferContent(prefix) {
            const normalizedPrefix = String(prefix || "naptien").trim();
            const username = this.currentUsername;

            if (!username) return normalizedPrefix;
            if (normalizedPrefix.includes("{username}")) {
                return normalizedPrefix.replaceAll("{username}", username);
            }

            return `${normalizedPrefix} ${username}`.trim();
        },
        updateQR() {
            if (!this.bankIdentifiers.length || !this.bankAccount) {
                this.qrUrl = "";
                this.qrCandidates = [];
                return;
            }

            const amount = Math.max(Number(this.amount) || 0, 10000);
            const accountName = encodeURIComponent(this.bankOwner || "");
            const addInfo = encodeURIComponent(this.transferContent || "");
            this.qrCandidates = this.bankIdentifiers.flatMap((bankId) => [
                `https://img.vietqr.io/image/${bankId}-${this.bankAccount}-compact2.jpg?amount=${amount}&addInfo=${addInfo}&accountName=${accountName}`,
                `https://img.vietqr.io/image/${bankId}-${this.bankAccount}-print.png?amount=${amount}&addInfo=${addInfo}&accountName=${accountName}`,
            ]);
            this.qrAttemptIndex = 0;
            this.qrUrl = this.qrCandidates[0] || "";
        },
        handleQRError() {
            const nextIndex = this.qrAttemptIndex + 1;

            if (nextIndex >= this.qrCandidates.length) {
                this.qrUrl = "";
                return;
            }

            this.qrAttemptIndex = nextIndex;
            this.qrUrl = this.qrCandidates[nextIndex];
        },
        async loadAtmConfig() {
            try {
                const { data } = await axios.get("/api/topup/atm-config");
                return data.settings || {};
            } catch (err) {
                const { data } = await axios.get("/api/home");
                return data.settings || {};
            }
        },
        async loadData() {
            const token = localStorage.getItem("token");
            if (!token) {
                this.$router.push("/login");
                return;
            }
            try {
                const { data } = await axios.get("/api/topup/history", {
                    headers: { Authorization: `Bearer ${token}` },
                });
                if (data.ok) this.history = data.data || [];
            } catch (err) {
                if (err.response?.status === 401) {
                    this.$router.push("/login");
                }
            }
            try {
                const s = await this.loadAtmConfig();
                this.bankName = s.bank_name || "MB";
                this.bankAccount = s.bank_account || "";
                this.bankOwner = s.bank_owner || "";
                this.transferContent = this.buildTransferContent(
                    s.transfer_prefix || "naptien",
                );
                this.updateQR();
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        this.loadData();
    },
};
</script>
