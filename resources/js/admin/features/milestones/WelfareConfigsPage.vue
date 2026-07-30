<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Mốc thưởng · Phúc lợi</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <span class="current">Phúc lợi</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.welfare-configs.create' }"
                class="btn btn-primary admin-fab"
            >
                <span class="mi">add</span>
                Thêm cấu hình
            </router-link>
        </div>

        <div class="type-tabs">
            <button class="type-tab" @click="openMilestones('moc_nap')">Mốc nạp</button>
            <button class="type-tab" @click="openMilestones('moc_nap_top')">Mốc nạp top</button>
            <button class="type-tab" @click="openMilestones('moc_nhiem_vu_top')">Mốc nhiệm vụ top</button>
            <button class="type-tab" @click="openMilestones('moc_suc_manh_top')">Mốc sức mạnh top</button>
            <button class="type-tab active">Phúc lợi</button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="filter-bar welfare-filters">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="filters.search"
                        class="form-input search-input"
                        placeholder="Tìm ID, mốc, tên gói hoặc nội dung..."
                    />
                </div>
                <select v-model="filters.type" class="form-input filter-select">
                    <option value="">Tất cả loại</option>
                    <option
                        v-for="option in WELFARE_TYPE_OPTIONS"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <select v-model="filters.active" class="form-input filter-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Đang bật</option>
                    <option value="0">Đang tắt</option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
                <button class="btn btn-outline btn-sm" type="button" @click="resetFilters">
                    Đặt lại
                </button>
            </form>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-label">Tổng cấu hình</span>
                <strong>{{ total }}</strong>
            </div>
            <div class="summary-card">
                <span class="summary-label">Loại đang xem</span>
                <strong>{{ selectedTypeLabel }}</strong>
            </div>
            <div class="summary-card">
                <span class="summary-label">Cập nhật game</span>
                <strong>Tức thời</strong>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Loại / mốc</th>
                            <th>Thông tin</th>
                            <th>Phần thưởng</th>
                            <th>Giá cash</th>
                            <th>Thứ tự</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>#{{ row.id }}</td>
                            <td>
                                <span class="type-badge">{{ row.type_label }}</span>
                                <small v-if="usesReference(row.type)" class="sub-line">
                                    Mốc: {{ formatNumber(row.ref_id) }}
                                </small>
                                <small v-else-if="row.type === 'message'" class="sub-line code-text">
                                    {{ row.msg_key }}
                                </small>
                            </td>
                            <td>
                                <strong>{{ rowTitle(row) }}</strong>
                                <small class="sub-line">{{ rowDescription(row) }}</small>
                            </td>
                            <td>
                                <div v-if="row.type !== 'message'" class="reward-preview">
                                    <div
                                        v-for="(reward, index) in row.rewards.slice(0, 5)"
                                        :key="`${row.id}-${reward.item_id}-${index}`"
                                        class="reward-icon"
                                        :title="rewardTitle(reward)"
                                    >
                                        <AdminIcon
                                            :icon-id="itemCatalog[reward.item_id]?.icon_id ?? null"
                                            class="item-icon-sm"
                                        />
                                        <span>x{{ formatCompact(reward.amount) }}</span>
                                    </div>
                                    <span v-if="row.rewards.length > 5" class="more-items">
                                        +{{ row.rewards.length - 5 }}
                                    </span>
                                    <span v-if="!row.rewards.length" class="empty-mark">—</span>
                                </div>
                                <span v-else class="message-preview">{{ row.msg_value }}</span>
                            </td>
                            <td>{{ isPackageType(row.type) ? formatNumber(row.cash) : "—" }}</td>
                            <td>{{ row.sort_order }}</td>
                            <td>
                                <button
                                    class="status-toggle"
                                    :class="{ active: row.active }"
                                    :disabled="busyId === row.id"
                                    @click="toggleStatus(row)"
                                >
                                    {{ row.active ? "Đang bật" : "Đang tắt" }}
                                </button>
                            </td>
                            <td class="action-cell">
                                <router-link
                                    :to="{ name: 'admin.welfare-configs.edit', params: { id: row.id } }"
                                    class="btn btn-primary btn-sm"
                                >
                                    <span class="mi">edit</span>
                                    Sửa
                                </router-link>
                                <button
                                    class="btn btn-danger btn-sm"
                                    :disabled="busyId === row.id"
                                    @click="removeRow(row)"
                                >
                                    <span class="mi">delete</span>
                                    Xóa
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!rows.length && !loading">
                            <td colspan="8" class="empty-cell">Không có dữ liệu phúc lợi.</td>
                        </tr>
                        <tr v-if="loading">
                            <td colspan="8" class="empty-cell">Đang tải dữ liệu...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="loadPage(1)">Đầu</button>
                <button :disabled="page <= 1" @click="loadPage(page - 1)">&laquo;</button>
                <template v-for="item in paginationItems" :key="String(item)">
                    <span v-if="typeof item !== 'number'" class="pagination-ellipsis">...</span>
                    <button
                        v-else
                        :class="{ active: item === page }"
                        @click="loadPage(item)"
                    >
                        {{ item }}
                    </button>
                </template>
                <button :disabled="page >= totalPages" @click="loadPage(page + 1)">&raquo;</button>
                <button :disabled="page >= totalPages" @click="loadPage(totalPages)">Cuối</button>
                <span class="pagination-summary">Trang {{ page }} / {{ totalPages }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { readJsonResponse } from "../../shared/api";
import { buildPaginationItems } from "../../shared/format";
import {
    WELFARE_TYPES,
    WELFARE_TYPE_OPTIONS,
    isPackageType,
    usesReference,
    type WelfareType,
} from "./welfareTypes";

interface Reward {
    item_id: number;
    amount: number;
}

interface WelfareRow {
    id: number;
    type: WelfareType;
    type_label: string;
    ref_id: number;
    label: string;
    description: string;
    price: number;
    cash: number;
    rewards: Reward[];
    msg_key: string;
    msg_value: string;
    sort_order: number;
    active: boolean;
}

interface CatalogItem {
    id: number;
    name: string;
    icon_id: number | null;
}

const router = useRouter();
const rows = ref<WelfareRow[]>([]);
const itemCatalog = ref<Record<number, CatalogItem>>({});
const loading = ref(false);
const busyId = ref<number | null>(null);
const error = ref("");
const success = ref("");
const page = ref(1);
const total = ref(0);
const totalPages = ref(1);
const filters = reactive({ search: "", type: "", active: "" });

const paginationItems = computed(() => buildPaginationItems(page.value, totalPages.value));
const selectedTypeLabel = computed(() =>
    filters.type
        ? WELFARE_TYPES[filters.type as WelfareType]?.label || filters.type
        : "Tất cả",
);

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || "";
}

function openMilestones(type: string): void {
    router.push({ name: "admin.milestones", params: { type } });
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat("vi-VN").format(value || 0);
}

function formatCompact(value: number): string {
    return value >= 1000
        ? new Intl.NumberFormat("vi-VN", { notation: "compact", maximumFractionDigits: 1 }).format(value)
        : String(value);
}

function rowTitle(row: WelfareRow): string {
    if (row.type === "message") return row.msg_key;
    return row.label || `${row.type_label} ${row.ref_id || ""}`.trim();
}

function rowDescription(row: WelfareRow): string {
    if (row.type === "message") return row.msg_value;
    return row.description || `${row.rewards.length} vật phẩm`;
}

function rewardTitle(reward: Reward): string {
    const item = itemCatalog.value[reward.item_id];
    return `${item?.name || `Item #${reward.item_id}`} x${formatNumber(reward.amount)}`;
}

async function loadPage(target = 1): Promise<void> {
    loading.value = true;
    error.value = "";
    page.value = Math.min(Math.max(1, Number(target) || 1), totalPages.value || 1);
    try {
        const params = new URLSearchParams({
            page: String(page.value),
            search: filters.search.trim(),
            type: filters.type,
            active: filters.active,
        });
        const response = await fetch(`/admin/api/welfare-configs?${params}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tải cấu hình phúc lợi");
        rows.value = data.data || [];
        itemCatalog.value = data.item_catalog || {};
        total.value = Number(data.total || 0);
        totalPages.value = Math.max(1, Number(data.total_pages || 1));
        page.value = Math.min(Math.max(1, Number(data.page || 1)), totalPages.value);
    } catch (caught) {
        rows.value = [];
        error.value = caught instanceof Error ? caught.message : "Không thể tải cấu hình phúc lợi";
    } finally {
        loading.value = false;
    }
}

function resetFilters(): void {
    filters.search = "";
    filters.type = "";
    filters.active = "";
    loadPage(1);
}

async function toggleStatus(row: WelfareRow): Promise<void> {
    busyId.value = row.id;
    error.value = "";
    try {
        const response = await fetch(`/admin/api/welfare-configs/${row.id}/toggle`, {
            method: "PATCH",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken(),
                Accept: "application/json",
            },
        });
        const data = await readJsonResponse(response, "Không thể đổi trạng thái");
        row.active = Boolean(data.active);
        success.value = data.message || "Đã cập nhật trạng thái";
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể đổi trạng thái";
    } finally {
        busyId.value = null;
    }
}

async function removeRow(row: WelfareRow): Promise<void> {
    if (!window.confirm(`Xóa cấu hình “${rowTitle(row)}”? Thao tác này ảnh hưởng trực tiếp tới game.`)) {
        return;
    }
    busyId.value = row.id;
    error.value = "";
    try {
        const response = await fetch(`/admin/api/welfare-configs/${row.id}`, {
            method: "DELETE",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken(),
                Accept: "application/json",
            },
        });
        const data = await readJsonResponse(response, "Không thể xóa cấu hình");
        success.value = data.message || "Đã xóa cấu hình";
        await loadPage(rows.value.length === 1 && page.value > 1 ? page.value - 1 : page.value);
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể xóa cấu hình";
    } finally {
        busyId.value = null;
    }
}

onMounted(() => loadPage(1));
</script>

<style scoped>
.type-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.type-tab {
    padding: 7px 12px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    color: var(--ds-text);
    font-size: 13px;
    cursor: pointer;
}

.type-tab:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.45);
}

.type-tab.active {
    border-color: rgba(var(--ds-primary-rgb), 0.55);
    background: rgba(var(--ds-primary-rgb), 0.15);
    color: var(--ds-primary);
}

.filter-bar {
    margin-bottom: 16px;
}

.welfare-filters .search-form {
    display: flex;
    width: 100%;
    flex-wrap: wrap;
    gap: 8px;
}

.search-input-wrap {
    position: relative;
    min-width: 260px;
    flex: 1;
}

.search-input {
    padding-left: 38px;
}

.search-icon {
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: var(--muted-foreground);
}

.filter-select {
    width: 190px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.summary-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card);
}

.summary-label,
.sub-line {
    display: block;
    color: var(--muted-foreground);
    font-size: 12px;
    margin-top: 4px;
}

.type-badge {
    display: inline-flex;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(99, 102, 241, 0.12);
    color: var(--primary);
    font-size: 12px;
    font-weight: 700;
}

.code-text {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

.reward-preview {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 190px;
}

.reward-icon {
    position: relative;
    width: 38px;
    text-align: center;
}

.item-icon-sm {
    width: 34px;
    height: 34px;
}

.reward-icon span {
    display: block;
    font-size: 10px;
    color: var(--muted-foreground);
}

.message-preview {
    display: block;
    max-width: 280px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status-toggle {
    border: 0;
    border-radius: 999px;
    padding: 6px 10px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.status-toggle.active {
    background: #dcfce7;
    color: #15803d;
}

.action-cell {
    display: flex;
    gap: 6px;
    white-space: nowrap;
}

@media (max-width: 900px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }

    .filter-select {
        width: 100%;
    }
}
</style>
