<template>
    <section
        class="client-panel topup-history-panel"
        aria-labelledby="topup-history-title"
    >
        <header class="topup-history-panel__header">
            <div>
                <span class="topup-history-panel__eyebrow">Giao dịch gần đây</span>
                <h2 id="topup-history-title">{{ title }}</h2>
            </div>
            <span class="topup-history-panel__count">
                {{ entries.length }} giao dịch
            </span>
        </header>

        <div v-if="!entries.length" class="topup-history-panel__empty">
            <strong>Chưa có giao dịch nào</strong>
            <span>Lịch sử nạp tiền của bạn sẽ xuất hiện tại đây.</span>
        </div>

        <div v-else class="topup-history-panel__table-wrap">
            <table class="topup-history-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Kênh nạp</th>
                        <th>Số tiền</th>
                        <th>Mã giao dịch</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in paginatedEntries" :key="entry.id">
                        <td data-label="Thời gian">
                            {{ formatDate(entry.createdAt) }}
                        </td>
                        <td data-label="Kênh nạp">
                            <strong>{{ entry.channel }}</strong>
                        </td>
                        <td data-label="Số tiền" class="topup-history-table__amount">
                            {{ formatMoney(entry.amount) }}
                        </td>
                        <td
                            data-label="Mã giao dịch"
                            class="topup-history-table__reference"
                        >
                            {{ entry.reference || "—" }}
                        </td>
                        <td data-label="Trạng thái">
                            <span
                                class="topup-status"
                                :class="`topup-status--${entry.status}`"
                            >
                                {{ statusLabel(entry.status) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer
            v-if="totalPages > 1"
            class="topup-history-panel__pagination"
        >
            <button
                type="button"
                :disabled="currentPage === 1"
                @click="currentPage--"
            >
                Trang trước
            </button>
            <span>Trang {{ currentPage }}/{{ totalPages }}</span>
            <button
                type="button"
                :disabled="currentPage === totalPages"
                @click="currentPage++"
            >
                Trang sau
            </button>
        </footer>
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";

export type TopupHistoryStatus = "success" | "pending" | "failed";

export interface TopupHistoryEntry {
    id: string | number;
    createdAt: string;
    channel: string;
    amount: number;
    reference?: string;
    status: TopupHistoryStatus;
}

const props = withDefaults(
    defineProps<{
        entries: TopupHistoryEntry[];
        title?: string;
        pageSize?: number;
    }>(),
    {
        title: "Lịch sử nạp tiền",
        pageSize: 5,
    },
);

const currentPage = ref(1);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(props.entries.length / props.pageSize)),
);
const paginatedEntries = computed(() => {
    const start = (currentPage.value - 1) * props.pageSize;
    return props.entries.slice(start, start + props.pageSize);
});

watch(
    () => props.entries.length,
    () => {
        currentPage.value = Math.min(currentPage.value, totalPages.value);
    },
);

const moneyFormatter = new Intl.NumberFormat("vi-VN", {
    maximumFractionDigits: 0,
});

function formatMoney(amount: number): string {
    return `${moneyFormatter.format(Number(amount) || 0)}đ`;
}

function formatDate(value: string): string {
    if (!value) return "—";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "—";

    return date.toLocaleString("vi-VN", {
        hour: "2-digit",
        minute: "2-digit",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

function statusLabel(status: TopupHistoryStatus): string {
    return {
        success: "Thành công",
        pending: "Đang xử lý",
        failed: "Thất bại",
    }[status];
}
</script>

<style scoped>
.topup-history-panel {
    grid-column: 1 / -1;
    padding: 0 !important;
    overflow: hidden;
}

.topup-history-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    background: var(--pixel-paper, #fff0c7);
    border-bottom: 2px solid var(--pixel-line, #7a4829);
}

.topup-history-panel__eyebrow {
    display: block;
    margin-bottom: 3px;
    color: var(--pixel-orange-dark, #a7440d);
    font-family: var(--pixel-font);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.topup-history-panel h2 {
    margin: 0;
    color: var(--pixel-ink, #36251d);
    font-family: var(--font-sans);
    font-size: clamp(1.15rem, 3vw, 1.45rem);
}

.topup-history-panel__count {
    flex: 0 0 auto;
    padding: 6px 9px;
    color: var(--pixel-muted, #745b47);
    background: var(--pixel-cream, #fff8df);
    border: 1px solid var(--pixel-line-soft, #c89b64);
    font-family: var(--font-sans);
    font-size: 0.72rem;
    font-weight: 700;
}

.topup-history-panel__table-wrap {
    overflow-x: auto;
}

.topup-history-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.topup-history-table th {
    padding: 10px 12px;
    color: #fff;
    background: var(--pixel-blue, #278fbd);
    border-right: 1px solid rgb(255 255 255 / 20%);
    font-family: var(--font-sans);
    font-size: 0.7rem;
    text-align: left;
    text-transform: uppercase;
}

.topup-history-table th:first-child {
    width: 22%;
}

.topup-history-table th:nth-child(2) {
    width: 16%;
}

.topup-history-table th:nth-child(3) {
    width: 16%;
}

.topup-history-table th:nth-child(4) {
    width: 27%;
}

.topup-history-table th:last-child {
    width: 19%;
    border-right: 0;
}

.topup-history-table td {
    padding: 12px;
    color: var(--pixel-ink, #36251d);
    background: var(--pixel-cream, #fff8df);
    border-bottom: 1px dashed var(--pixel-line-soft, #c89b64);
    font-family: var(--font-sans);
    font-size: 0.78rem;
    line-height: 1.35;
    text-align: left;
    overflow-wrap: anywhere;
}

.topup-history-table tr:nth-child(even) td {
    background: var(--pixel-paper, #fff0c7);
}

.topup-history-table__amount {
    color: var(--pixel-orange-dark, #a7440d) !important;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.topup-history-table__reference {
    font-size: 0.7rem !important;
}

.topup-status {
    display: inline-flex;
    min-height: 26px;
    align-items: center;
    justify-content: center;
    padding: 4px 8px;
    border: 1px solid currentcolor;
    font-size: 0.68rem;
    font-weight: 800;
    white-space: nowrap;
}

.topup-status--success {
    color: #267438;
    background: #e5f4d4;
}

.topup-status--pending {
    color: #94610d;
    background: #fff0ba;
}

.topup-status--failed {
    color: #a13227;
    background: #ffe0d5;
}

.topup-history-panel__empty {
    display: grid;
    min-height: 140px;
    place-content: center;
    gap: 5px;
    padding: 24px;
    color: var(--pixel-muted, #745b47);
    text-align: center;
}

.topup-history-panel__empty strong {
    color: var(--pixel-ink, #36251d);
}

.topup-history-panel__pagination {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: var(--pixel-paper, #fff0c7);
    border-top: 1px solid var(--pixel-line-soft, #c89b64);
}

.topup-history-panel__pagination button {
    min-height: 36px;
    padding: 7px 12px;
    color: var(--pixel-ink, #36251d);
    background: var(--pixel-cream, #fff8df);
    border: 1px solid var(--pixel-line, #7a4829);
    font: inherit;
    font-size: 0.72rem;
    font-weight: 800;
    cursor: pointer;
}

.topup-history-panel__pagination button:first-child {
    justify-self: end;
}

.topup-history-panel__pagination button:last-child {
    justify-self: start;
}

.topup-history-panel__pagination button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.topup-history-panel__pagination span {
    color: var(--pixel-muted, #745b47);
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

@media (max-width: 700px) {
    .topup-history-panel__header {
        align-items: flex-start;
        padding: 15px 14px;
    }

    .topup-history-panel__count {
        padding-inline: 7px;
    }

    .topup-history-panel__table-wrap {
        padding: 10px;
        overflow: visible;
    }

    .topup-history-table,
    .topup-history-table tbody {
        display: block;
        width: 100%;
    }

    .topup-history-table thead {
        display: none;
    }

    .topup-history-table tr {
        display: grid;
        grid-template-columns: 1fr 1fr;
        margin-bottom: 10px;
        overflow: hidden;
        background: var(--pixel-cream, #fff8df);
        border: 1px solid var(--pixel-line, #7a4829);
        box-shadow: 2px 2px 0 rgb(63 41 28 / 14%);
    }

    .topup-history-table tr:last-child {
        margin-bottom: 0;
    }

    .topup-history-table tr:nth-child(even) td {
        background: transparent;
    }

    .topup-history-table td {
        display: flex;
        min-width: 0;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 10px;
        background: transparent;
        border-bottom: 1px dashed var(--pixel-line-soft, #c89b64);
        font-size: 0.74rem;
        text-align: right;
    }

    .topup-history-table td::before {
        flex: 0 0 auto;
        color: var(--pixel-muted, #745b47);
        content: attr(data-label);
        font-size: 0.65rem;
        font-weight: 700;
        text-align: left;
    }

    .topup-history-table td:first-child,
    .topup-history-table td:nth-child(4) {
        grid-column: 1 / -1;
    }

    .topup-history-table td:nth-child(4) {
        border-bottom: 0;
    }

    .topup-history-table td:last-child {
        grid-column: 1 / -1;
        border-bottom: 0;
    }

    .topup-history-table__reference {
        overflow-wrap: anywhere;
    }

    .topup-history-panel__pagination {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 11px 10px;
    }

    .topup-history-panel__pagination span {
        grid-column: 1 / -1;
        grid-row: 1;
        text-align: center;
    }

    .topup-history-panel__pagination button,
    .topup-history-panel__pagination button:first-child,
    .topup-history-panel__pagination button:last-child {
        width: 100%;
        justify-self: stretch;
    }
}
</style>
