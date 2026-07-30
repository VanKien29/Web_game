<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">{{ currentConfig.label }}</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <span>Mốc thưởng</span>
                    <span>/</span>
                    <span class="current">{{ currentConfig.label }}</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.welfare-configs.create', params: { type: currentType } }"
                class="btn btn-primary admin-fab"
            >
                <span class="mi">add</span>
                Thêm mốc
            </router-link>
        </div>

        <div class="type-tabs">
            <button
                v-for="option in WELFARE_TYPE_OPTIONS"
                :key="option.value"
                class="type-tab"
                :class="{ active: option.value === currentType }"
                @click="switchType(option.value)"
            >
                {{ option.label }}
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="filters.search"
                        class="form-input search-input"
                        :placeholder="searchPlaceholder"
                    />
                </div>
                <select v-model="filters.active" class="form-input status-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Đang bật</option>
                    <option value="0">Đang tắt</option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">Tìm kiếm</button>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ primaryColumnLabel }}</th>
                            <th>{{ isMessage ? "Nội dung" : "Phần thưởng" }}</th>
                            <th v-if="isPackage">Giá cash</th>
                            <th>Thứ tự</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>#{{ row.id }}</td>
                            <td>
                                <strong>{{ primaryValue(row) }}</strong>
                                <small v-if="secondaryValue(row)" class="sub-line">
                                    {{ secondaryValue(row) }}
                                </small>
                            </td>
                            <td>
                                <span v-if="isMessage" class="message-value">
                                    {{ row.msg_value }}
                                </span>
                                <div v-else class="reward-preview">
                                    <div
                                        v-for="(reward, index) in row.rewards.slice(0, 7)"
                                        :key="`${row.id}-${reward.item_id}-${index}`"
                                        class="reward-item"
                                        :title="rewardTitle(reward)"
                                    >
                                        <AdminIcon
                                            :icon-id="itemCatalog[reward.item_id]?.icon_id ?? null"
                                            class="reward-icon"
                                        />
                                        <span>x{{ compactNumber(reward.amount) }}</span>
                                        <small v-if="reward.options.length">
                                            {{ reward.options.length }} opt
                                        </small>
                                    </div>
                                    <span v-if="row.rewards.length > 7" class="more-items">
                                        +{{ row.rewards.length - 7 }}
                                    </span>
                                </div>
                            </td>
                            <td v-if="isPackage">{{ formatNumber(row.cash) }}</td>
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
                                    :to="{
                                        name: 'admin.welfare-configs.edit',
                                        params: { type: currentType, id: row.id },
                                    }"
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
                                </button>
                            </td>
                        </tr>
                        <tr v-if="loading">
                            <td :colspan="columnCount" class="empty-cell">Đang tải dữ liệu...</td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td :colspan="columnCount" class="empty-cell">
                                Chưa có dữ liệu {{ currentConfig.label.toLowerCase() }}.
                            </td>
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
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { readJsonResponse } from "../../shared/api";
import { buildPaginationItems } from "../../shared/format";
import {
    WELFARE_TYPES,
    WELFARE_TYPE_OPTIONS,
    isPackageType,
    type WelfareReward,
    type WelfareType,
} from "./welfareTypes";

interface WelfareRow {
    id: number;
    type: WelfareType;
    ref_id: number;
    label: string;
    description: string;
    cash: number;
    rewards: WelfareReward[];
    msg_key: string;
    msg_value: string;
    sort_order: number;
    active: boolean;
}

interface CatalogItem {
    name: string;
    icon_id: number | null;
}

const route = useRoute();
const router = useRouter();
const rows = ref<WelfareRow[]>([]);
const itemCatalog = ref<Record<number, CatalogItem>>({});
const loading = ref(false);
const busyId = ref<number | null>(null);
const error = ref("");
const success = ref("");
const page = ref(1);
const totalPages = ref(1);
const filters = reactive({ search: "", active: "" });

const currentType = computed(() => route.params.type as WelfareType);
const currentConfig = computed(() => WELFARE_TYPES[currentType.value] || WELFARE_TYPES.attendance_daily);
const isMessage = computed(() => currentType.value === "message");
const isPackage = computed(() => isPackageType(currentType.value));
const columnCount = computed(() => (isPackage.value ? 7 : 6));
const paginationItems = computed(() => buildPaginationItems(page.value, totalPages.value));
const primaryColumnLabel = computed(() => {
    if (isMessage.value) return "Mã nội dung";
    if (currentType.value === "attendance_daily") return "Nhóm quà";
    if (isPackage.value) return "Tên gói / ID";
    return currentConfig.value.refLabel || "Mốc";
});
const searchPlaceholder = computed(() =>
    isMessage.value
        ? "Tìm mã hoặc nội dung..."
        : isPackage.value
          ? "Tìm ID hoặc tên gói..."
          : "Tìm ID hoặc giá trị mốc...",
);

watch(
    () => route.params.type,
    () => {
        if (!ensureType()) return;
        filters.search = "";
        filters.active = "";
        loadPage(1);
    },
);

function ensureType(): boolean {
    if (WELFARE_TYPES[currentType.value]) return true;
    router.replace({
        name: "admin.welfare-configs",
        params: { type: "attendance_daily" },
    });
    return false;
}

function switchType(type: WelfareType): void {
    if (type === currentType.value) return;
    router.push({ name: "admin.welfare-configs", params: { type } });
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || "";
}

function primaryValue(row: WelfareRow): string {
    if (isMessage.value) return row.msg_key;
    if (currentType.value === "attendance_daily") return row.label || "Quà ngẫu nhiên mỗi ngày";
    if (isPackage.value) return row.label || `Gói #${row.ref_id}`;
    return formatNumber(row.ref_id);
}

function secondaryValue(row: WelfareRow): string {
    if (isMessage.value) return "";
    if (isPackage.value) return `ID gói: ${row.ref_id}${row.description ? ` · ${row.description}` : ""}`;
    return row.label || row.description || "";
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat("vi-VN").format(value || 0);
}

function compactNumber(value: number): string {
    return value >= 1000
        ? new Intl.NumberFormat("vi-VN", { notation: "compact", maximumFractionDigits: 1 }).format(value)
        : String(value);
}

function rewardTitle(reward: WelfareReward): string {
    const item = itemCatalog.value[reward.item_id];
    const optionText = reward.options.length ? ` · ${reward.options.length} option` : "";
    return `${item?.name || `Item #${reward.item_id}`} x${formatNumber(reward.amount)}${optionText}`;
}

async function loadPage(target = 1): Promise<void> {
    loading.value = true;
    error.value = "";
    page.value = Math.max(1, Number(target) || 1);
    try {
        const params = new URLSearchParams({
            page: String(page.value),
            type: currentType.value,
            search: filters.search.trim(),
            active: filters.active,
        });
        const response = await fetch(`/admin/api/welfare-configs?${params}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tải mốc phúc lợi");
        rows.value = data.data || [];
        itemCatalog.value = data.item_catalog || {};
        totalPages.value = Math.max(1, Number(data.total_pages || 1));
        page.value = Math.min(Math.max(1, Number(data.page || 1)), totalPages.value);
    } catch (caught) {
        rows.value = [];
        error.value = caught instanceof Error ? caught.message : "Không thể tải mốc phúc lợi";
    } finally {
        loading.value = false;
    }
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
    if (!window.confirm(`Xóa cấu hình “${primaryValue(row)}”?`)) return;
    busyId.value = row.id;
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

onMounted(() => {
    if (!ensureType()) return;
    loadPage(1);
});
</script>

<style scoped>
.type-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-bottom: 14px;
}

.type-tab {
    padding: 6px 10px;
    border: 1px solid var(--ds-border);
    border-radius: 7px;
    background: var(--ds-surface);
    color: var(--ds-text);
    font-size: 12px;
    cursor: pointer;
}

.type-tab.active {
    border-color: rgba(var(--ds-primary-rgb), 0.55);
    background: rgba(var(--ds-primary-rgb), 0.15);
    color: var(--ds-primary);
}

.filter-bar {
    margin-bottom: 14px;
}

.search-form {
    display: flex;
    gap: 8px;
}

.search-input-wrap {
    position: relative;
    max-width: 520px;
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

.status-filter {
    width: 170px;
}

.sub-line {
    display: block;
    max-width: 330px;
    overflow: hidden;
    color: var(--muted-foreground);
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reward-preview {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 260px;
}

.reward-item {
    width: 38px;
    text-align: center;
}

.reward-icon {
    width: 32px;
    height: 32px;
}

.reward-item span,
.reward-item small {
    display: block;
    color: var(--muted-foreground);
    font-size: 9px;
    line-height: 1.1;
}

.message-value {
    display: block;
    max-width: 520px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status-toggle {
    padding: 5px 9px;
    border: 0;
    border-radius: 999px;
    background: var(--muted);
    color: var(--destructive);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.status-toggle.active {
    background: var(--accent);
    color: var(--accent-foreground);
}

.action-cell {
    display: flex;
    gap: 5px;
    white-space: nowrap;
}

@media (max-width: 760px) {
    .search-form {
        flex-wrap: wrap;
    }

    .search-input-wrap,
    .status-filter {
        width: 100%;
        max-width: none;
    }
}
</style>
