<template>
    <div class="client-page client-page--topup">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Nạp ATM</span>
        </div>

        <div class="client-page-head client-page-head--split">
            <div>
                <div class="client-panel__eyebrow">Thanh toán tự động</div>
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

        <template v-else>
            <div
                v-if="loadError"
                class="client-panel atm-error"
                role="alert"
            >
                {{ loadError }}
                <button type="button" @click="loadData">Thử lại</button>
            </div>

            <div v-else class="atm-payment-layout">
                <section class="client-panel atm-payment-panel">
                    <header class="atm-section-heading">
                        <div>
                            <span>Bước 1</span>
                            <h2>Quét mã chuyển khoản</h2>
                        </div>
                        <strong>Tự động cộng tiền</strong>
                    </header>

                    <div class="atm-payment-panel__content">
                        <div
                            class="atm-qr"
                            :class="{ 'atm-qr--disabled': isAmountTooLow }"
                        >
                            <img
                                v-if="qrUrl"
                                :src="qrUrl"
                                alt="Mã QR chuyển khoản ngân hàng"
                                @error="handleQRError"
                            />
                            <div v-else class="atm-qr__empty">
                                Chưa thể tạo mã QR. Vui lòng kiểm tra thông tin
                                ngân hàng.
                            </div>
                            <span v-if="isAmountTooLow" class="atm-qr__overlay">
                                Tối thiểu 10.000đ
                            </span>
                            <small>Quét bằng ứng dụng ngân hàng</small>
                        </div>

                        <div class="atm-transfer">
                            <label class="atm-amount-field">
                                <span>Số tiền muốn nạp</span>
                                <div>
                                    <input
                                        v-model.number="amount"
                                        type="number"
                                        inputmode="numeric"
                                        min="10000"
                                        step="1000"
                                    />
                                    <strong>VNĐ</strong>
                                </div>
                                <small>Tối thiểu 10.000đ</small>
                            </label>

                            <dl class="atm-bank-details">
                                <div>
                                    <dt>Ngân hàng</dt>
                                    <dd>{{ bankName || "—" }}</dd>
                                </div>
                                <div>
                                    <dt>Số tài khoản</dt>
                                    <dd>{{ bankAccount || "—" }}</dd>
                                </div>
                                <div>
                                    <dt>Chủ tài khoản</dt>
                                    <dd>{{ bankOwner || "—" }}</dd>
                                </div>
                            </dl>

                            <div class="atm-transfer-content">
                                <span>Nội dung chuyển khoản</span>
                                <div>
                                    <strong>{{ transferContent }}</strong>
                                    <button
                                        type="button"
                                        @click="copyTransferContent"
                                    >
                                        {{ copied ? "Đã chép" : "Sao chép" }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="client-panel atm-guide-panel">
                    <header class="atm-section-heading">
                        <div>
                            <span>Bước 2</span>
                            <h2>Kiểm tra trước khi chuyển</h2>
                        </div>
                    </header>
                    <ol>
                        <li>
                            Nhập số tiền từ <strong>10.000đ</strong> trở lên.
                        </li>
                        <li>
                            Giữ nguyên nội dung
                            <strong>{{ transferContent }}</strong>.
                        </li>
                        <li>
                            Tiền được cộng tự động trong khoảng
                            <strong>30 giây – 2 phút</strong>.
                        </li>
                    </ol>
                    <p>
                        Nếu sau 5 phút chưa nhận được tiền, hãy chụp biên lai và
                        liên hệ Admin.
                    </p>
                </aside>

                <TopupHistoryPanel
                    :entries="historyEntries"
                    title="Lịch sử nạp ATM"
                    :page-size="5"
                />
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import axios from "axios";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import TopupHistoryPanel, {
    type TopupHistoryEntry,
} from "../components/topup/TopupHistoryPanel.vue";

interface AtmSettings {
    bank_name?: string;
    bank_account?: string;
    bank_owner?: string;
    transfer_prefix?: string;
}

interface AtmTransaction {
    amount: number | string;
    source?: string;
    trans_id?: string;
    created_at?: string;
    status?: number;
}

const router = useRouter();
const requestController = new AbortController();
const amount = ref(10000);
const bankName = ref("");
const bankAccount = ref("");
const bankOwner = ref("");
const transferPrefix = ref("naptien");
const history = ref<AtmTransaction[]>([]);
const loading = ref(true);
const loadError = ref("");
const qrAttemptIndex = ref(0);
const copied = ref(false);
let copyTimer: ReturnType<typeof window.setTimeout> | null = null;

const currentUsername = computed(() => {
    try {
        return String(
            JSON.parse(localStorage.getItem("user") || "{}").username || "",
        )
            .trim()
            .toLowerCase();
    } catch {
        return "";
    }
});

const transferContent = computed(() => {
    const prefix = transferPrefix.value.trim() || "naptien";
    if (!currentUsername.value) return prefix;

    return prefix.includes("{username}")
        ? prefix.replaceAll("{username}", currentUsername.value)
        : `${prefix} ${currentUsername.value}`.trim();
});

const normalizedBankName = computed(() =>
    bankName.value.toUpperCase().replace(/[^A-Z0-9]/g, ""),
);
const bankIdentifiers = computed(() => {
    const aliases: Record<string, string[]> = {
        TPBANK: ["970423", "TPB", "TPBank"],
        TIENPHONGBANK: ["970423", "TPB", "TPBank"],
        MBBANK: ["970422", "MB", "MBBank"],
        MB: ["970422", "MB", "MBBank"],
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

    return [
        ...new Set(
            aliases[normalizedBankName.value] || [normalizedBankName.value],
        ),
    ].filter(Boolean);
});
const isAmountTooLow = computed(
    () => (Number(amount.value) || 0) < 10000,
);
const qrCandidates = computed(() => {
    if (!bankIdentifiers.value.length || !bankAccount.value) return [];

    const qrAmount = Math.max(Number(amount.value) || 0, 10000);
    const query = new URLSearchParams({
        amount: String(qrAmount),
        addInfo: transferContent.value,
        accountName: bankOwner.value,
    });

    return bankIdentifiers.value.flatMap((bankId) => [
        `https://img.vietqr.io/image/${bankId}-${bankAccount.value}-compact2.jpg?${query}`,
        `https://img.vietqr.io/image/${bankId}-${bankAccount.value}-print.png?${query}`,
    ]);
});
const qrUrl = computed(() => qrCandidates.value[qrAttemptIndex.value] || "");
const historyEntries = computed<TopupHistoryEntry[]>(() =>
    history.value.map((transaction, index) => ({
        id: transaction.trans_id || `${transaction.created_at}-${index}`,
        createdAt: transaction.created_at || "",
        channel:
            transaction.source?.toLowerCase() === "sepay"
                ? "Chuyển khoản"
                : transaction.source || "ATM",
        amount: Number(transaction.amount) || 0,
        reference: transaction.trans_id || "—",
        status: transaction.status === 0 ? "pending" : "success",
    })),
);

watch(qrCandidates, () => {
    qrAttemptIndex.value = 0;
});

function authHeaders() {
    return {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
        signal: requestController.signal,
    };
}

async function loadAtmConfig(): Promise<AtmSettings> {
    try {
        const { data } = await axios.get("/api/topup/atm-config", {
            signal: requestController.signal,
        });
        return data.settings || {};
    } catch (error) {
        if (axios.isCancel(error)) throw error;
        const { data } = await axios.get("/api/home", {
            signal: requestController.signal,
        });
        return data.settings || {};
    }
}

async function loadData(): Promise<void> {
    const token = localStorage.getItem("token");
    if (!token) {
        await router.push("/login");
        return;
    }

    loading.value = true;
    loadError.value = "";
    try {
        const [settings, historyResponse] = await Promise.all([
            loadAtmConfig(),
            axios.get("/api/topup/history", authHeaders()),
        ]);

        bankName.value = settings.bank_name || "MB";
        bankAccount.value = settings.bank_account || "";
        bankOwner.value = settings.bank_owner || "";
        transferPrefix.value = settings.transfer_prefix || "naptien";
        history.value = historyResponse.data?.ok
            ? historyResponse.data.data || []
            : [];

    } catch (error) {
        if (axios.isCancel(error)) return;
        if (axios.isAxiosError(error) && error.response?.status === 401) {
            await router.push("/login");
            return;
        }
        loadError.value =
            "Không thể tải thông tin nạp ATM. Vui lòng thử lại sau.";
    } finally {
        loading.value = false;
    }
}

function handleQRError(): void {
    if (qrAttemptIndex.value < qrCandidates.value.length - 1) {
        qrAttemptIndex.value += 1;
        return;
    }
    qrAttemptIndex.value = qrCandidates.value.length;
}

async function copyTransferContent(): Promise<void> {
    try {
        await navigator.clipboard.writeText(transferContent.value);
        copied.value = true;
        if (copyTimer) window.clearTimeout(copyTimer);
        copyTimer = window.setTimeout(() => {
            copied.value = false;
        }, 1800);
    } catch {
        copied.value = false;
    }
}

onMounted(loadData);
onBeforeUnmount(() => {
    requestController.abort();
    if (copyTimer) window.clearTimeout(copyTimer);
});
</script>

<style scoped>
.atm-payment-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(270px, 0.55fr);
    gap: 18px;
}

.atm-payment-panel,
.atm-guide-panel {
    padding: 0 !important;
    overflow: hidden;
}

.atm-section-heading {
    display: flex;
    min-height: 74px;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 18px;
    background: var(--pixel-paper, #fff0c7);
    border-bottom: 2px solid var(--pixel-line, #7a4829);
}

.atm-section-heading span {
    display: block;
    margin-bottom: 3px;
    color: var(--pixel-orange-dark, #a7440d);
    font-family: var(--pixel-font);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.atm-section-heading h2 {
    margin: 0;
    color: var(--pixel-ink, #36251d);
    font-family: var(--font-sans);
    font-size: 1.1rem;
}

.atm-section-heading > strong {
    padding: 5px 8px;
    color: #267438;
    background: #e5f4d4;
    border: 1px solid #4b8b42;
    font-size: 0.68rem;
}

.atm-payment-panel__content {
    display: grid;
    grid-template-columns: minmax(210px, 0.65fr) minmax(280px, 1fr);
    gap: 18px;
    padding: 18px;
}

.atm-qr {
    position: relative;
    display: grid;
    min-height: 260px;
    align-content: center;
    justify-items: center;
    gap: 9px;
    padding: 12px;
    background: #fff;
    border: 2px solid var(--pixel-line, #7a4829);
    box-shadow: 3px 3px 0 rgb(63 41 28 / 15%);
}

.atm-qr img {
    display: block;
    width: min(100%, 260px);
    aspect-ratio: 1;
    object-fit: contain;
}

.atm-qr small {
    color: var(--pixel-muted, #745b47);
    font-size: 0.68rem;
}

.atm-qr--disabled img {
    filter: grayscale(0.8);
    opacity: 0.28;
}

.atm-qr__overlay {
    position: absolute;
    inset: 50% auto auto 50%;
    padding: 8px 10px;
    color: #fff;
    background: #9c352b;
    border: 1px solid #6f251f;
    font-size: 0.72rem;
    font-weight: 800;
    translate: -50% -50%;
    white-space: nowrap;
}

.atm-qr__empty {
    max-width: 220px;
    color: var(--pixel-muted, #745b47);
    font-size: 0.78rem;
    line-height: 1.5;
    text-align: center;
}

.atm-transfer {
    display: grid;
    align-content: start;
    gap: 13px;
}

.atm-amount-field {
    display: grid;
    gap: 5px;
}

.atm-amount-field > span,
.atm-transfer-content > span {
    color: var(--pixel-muted, #745b47);
    font-size: 0.7rem;
    font-weight: 700;
}

.atm-amount-field > div {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    background: #fffdf4;
    border: 2px solid var(--pixel-line-soft, #c89b64);
}

.atm-amount-field input {
    width: 100%;
    min-height: 46px;
    padding: 9px 11px;
    color: var(--pixel-ink, #36251d);
    background: transparent;
    border: 0;
    outline: 0;
    font: inherit;
    font-size: 1rem;
    font-weight: 800;
}

.atm-amount-field div > strong {
    padding: 0 11px;
    color: var(--pixel-orange-dark, #a7440d);
    font-size: 0.72rem;
}

.atm-amount-field small {
    color: var(--pixel-muted, #745b47);
    font-size: 0.65rem;
}

.atm-bank-details {
    display: grid;
    margin: 0;
    border: 1px solid var(--pixel-line-soft, #c89b64);
}

.atm-bank-details > div {
    display: grid;
    grid-template-columns: minmax(100px, 0.8fr) minmax(0, 1.2fr);
    gap: 10px;
    padding: 9px 10px;
    background: var(--pixel-cream, #fff8df);
    border-bottom: 1px dashed var(--pixel-line-soft, #c89b64);
}

.atm-bank-details > div:last-child {
    border-bottom: 0;
}

.atm-bank-details dt {
    color: var(--pixel-muted, #745b47);
    font-size: 0.68rem;
}

.atm-bank-details dd {
    margin: 0;
    color: var(--pixel-ink, #36251d);
    font-size: 0.74rem;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.atm-transfer-content {
    display: grid;
    gap: 5px;
}

.atm-transfer-content > div {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: stretch;
    background: #fff0ba;
    border: 2px solid var(--pixel-orange-dark, #a7440d);
}

.atm-transfer-content strong {
    align-self: center;
    padding: 10px;
    color: var(--pixel-ink, #36251d);
    font-family: var(--font-sans);
    font-size: 0.82rem;
    overflow-wrap: anywhere;
}

.atm-transfer-content button,
.atm-error button {
    min-height: 40px;
    padding: 8px 11px;
    color: #fff;
    background: var(--pixel-orange, #ec7424);
    border: 0;
    border-left: 1px solid var(--pixel-orange-dark, #a7440d);
    font: inherit;
    font-size: 0.7rem;
    font-weight: 800;
    cursor: pointer;
}

.atm-guide-panel ol {
    display: grid;
    gap: 12px;
    margin: 0;
    padding: 18px 18px 14px 46px;
}

.atm-guide-panel li {
    padding-left: 3px;
    color: var(--pixel-ink, #36251d);
    font-size: 0.78rem;
    line-height: 1.55;
}

.atm-guide-panel p {
    margin: 0 16px 16px;
    padding: 11px;
    color: #8a3b22;
    background: #ffedbd;
    border: 1px dashed #c7782a;
    font-size: 0.72rem;
    line-height: 1.5;
}

.atm-error {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    color: #8f2d24;
}

.atm-error button {
    border: 1px solid var(--pixel-orange-dark, #a7440d);
}

@media (max-width: 860px) {
    .atm-payment-layout,
    .atm-payment-panel__content {
        grid-template-columns: 1fr;
    }

    .atm-guide-panel {
        order: 2;
    }
}

@media (max-width: 600px) {
    .atm-payment-layout {
        gap: 13px;
    }

    .atm-section-heading {
        min-height: 0;
        align-items: flex-start;
        padding: 12px;
    }

    .atm-section-heading > strong {
        max-width: 110px;
        text-align: center;
    }

    .atm-payment-panel__content {
        gap: 14px;
        padding: 12px;
    }

    .atm-qr {
        min-height: 230px;
    }

    .atm-qr img {
        width: min(100%, 220px);
    }

    .atm-transfer-content > div {
        grid-template-columns: 1fr;
    }

    .atm-transfer-content button {
        border-top: 1px solid var(--pixel-orange-dark, #a7440d);
        border-left: 0;
    }

    .atm-error {
        align-items: stretch;
        flex-direction: column;
    }
}
</style>
