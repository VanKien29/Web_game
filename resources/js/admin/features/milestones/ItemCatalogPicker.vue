<template>
    <div
        v-if="open"
        class="picker-overlay"
        @click.self="emit('close')"
    >
        <div class="picker-panel">
            <div class="picker-head">
                <h3>Chọn vật phẩm</h3>
                <button
                    type="button"
                    class="picker-close"
                    aria-label="Đóng bảng chọn vật phẩm"
                    @click="emit('close')"
                >
                    <span class="mi">close</span>
                </button>
            </div>

            <div class="picker-tools">
                <input
                    v-model="picker.search"
                    class="form-input"
                    placeholder="Tìm theo ID hoặc tên..."
                    @keyup.enter="loadItems(1)"
                />
                <select
                    v-model="picker.type"
                    class="form-input"
                    @change="loadItems(1)"
                >
                    <option value="">Tất cả TYPE</option>
                    <option
                        v-for="type in pickerTypes"
                        :key="type.id"
                        :value="String(type.id)"
                    >
                        {{ type.name }} (TYPE {{ type.id }})
                    </option>
                </select>
                <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    @click="loadItems(1)"
                >
                    Lọc
                </button>
            </div>

            <div class="picker-list">
                <div v-if="picker.loading" class="picker-empty">
                    <span class="admin-loading-spinner"></span>
                </div>
                <div v-else-if="error" class="picker-empty">
                    {{ error }}
                </div>
                <div v-else-if="!picker.rows.length" class="picker-empty">
                    Không có vật phẩm phù hợp.
                </div>
                <template v-else>
                    <button
                        v-for="item in picker.rows"
                        :key="item.id"
                        type="button"
                        class="picker-item"
                        @click="selectItem(item)"
                    >
                        <AdminIcon
                            class="picker-item-icon"
                            :icon-id="item.icon_id"
                        />
                        <span class="picker-item-info">
                            <strong class="picker-item-name">{{ item.name }}</strong>
                            <small class="picker-item-meta">
                                ID: {{ item.id }} | {{ typeLabel(item.type) }}
                            </small>
                        </span>
                        <span class="mi picker-item-add">add</span>
                    </button>
                </template>
            </div>

            <div class="picker-foot">
                <span>{{ picker.total.toLocaleString("vi-VN") }} item</span>
                <div class="picker-pagination">
                    <button
                        type="button"
                        class="btn btn-outline btn-xs"
                        :disabled="picker.page <= 1"
                        @click="goToPage(1)"
                    >
                        Đầu
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline btn-xs"
                        :disabled="picker.page <= 1"
                        @click="goToPage(picker.page - 1)"
                    >
                        Trước
                    </button>
                    <div class="picker-page-list">
                        <template
                            v-for="page in paginationItems"
                            :key="String(page)"
                        >
                            <span
                                v-if="typeof page !== 'number'"
                                class="picker-pagination-ellipsis"
                            >
                                ...
                            </span>
                            <button
                                v-else
                                type="button"
                                class="btn btn-outline btn-xs"
                                :class="{ active: page === picker.page }"
                                @click="goToPage(page)"
                            >
                                {{ page }}
                            </button>
                        </template>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline btn-xs"
                        :disabled="picker.page >= picker.totalPages"
                        @click="goToPage(picker.page + 1)"
                    >
                        Sau
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline btn-xs"
                        :disabled="picker.page >= picker.totalPages"
                        @click="goToPage(picker.totalPages)"
                    >
                        Cuối
                    </button>
                    <span class="picker-pagination-summary">
                        Trang {{ picker.page }} / {{ picker.totalPages }}
                    </span>
                    <input
                        v-model="picker.pageInput"
                        type="number"
                        min="1"
                        :max="picker.totalPages"
                        class="form-input picker-page-input"
                        @keyup.enter="jumpToPage"
                    />
                    <button
                        type="button"
                        class="btn btn-primary btn-xs"
                        @click="jumpToPage"
                    >
                        Đi
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { readJsonResponse } from "../../shared/api";
import { buildPaginationItems } from "../../shared/format";
import {
    applyItemPickerResponse,
    itemPickerTypes,
    itemTypeLabel,
    normalizePickerPage,
    resetItemPickerResults,
} from "../../shared/itemCatalog";

interface CatalogItem {
    id: number;
    name: string;
    icon_id: number | null;
    type?: number | string;
}

interface ItemType {
    id: number;
    name: string;
}

interface PickerState {
    loading: boolean;
    rows: CatalogItem[];
    types: Array<number | string>;
    typeOptions: ItemType[];
    search: string;
    type: string;
    page: number;
    pageInput: string;
    totalPages: number;
    total: number;
}

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{
    close: [];
    select: [item: CatalogItem];
}>();

const picker = reactive<PickerState>({
    loading: false,
    rows: [],
    types: [],
    typeOptions: [],
    search: "",
    type: "",
    page: 1,
    pageInput: "1",
    totalPages: 1,
    total: 0,
});
const error = ref("");

const pickerTypes = computed(
    () => itemPickerTypes(picker) as ItemType[],
);
const paginationItems = computed(() =>
    buildPaginationItems(picker.page, picker.totalPages),
);

watch(
    () => props.open,
    (open) => {
        if (open && !picker.rows.length) {
            loadItems(1);
        }
    },
);

function normalizePage(page: number | string): number {
    return normalizePickerPage(page, picker.totalPages);
}

function goToPage(page: number | string): void {
    const target = normalizePage(page);
    if (target === picker.page && picker.rows.length) {
        picker.pageInput = String(target);
        return;
    }
    loadItems(target);
}

function jumpToPage(): void {
    goToPage(picker.pageInput);
}

function typeLabel(type: number | string | undefined): string {
    return itemTypeLabel(type, pickerTypes.value);
}

function selectItem(item: CatalogItem): void {
    emit("select", item);
    emit("close");
}

async function loadItems(page = 1): Promise<void> {
    picker.loading = true;
    picker.page = normalizePage(page);
    picker.pageInput = String(picker.page);
    error.value = "";

    try {
        const params = new URLSearchParams({
            page: String(picker.page),
            per_page: "30",
        });
        if (picker.search.trim()) {
            params.set("search", picker.search.trim());
        }
        if (picker.type !== "") {
            params.set("type", picker.type);
        }

        const response = await fetch(`/admin/api/items?${params}`, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        });
        const data = await readJsonResponse(
            response,
            "Không thể tải danh sách vật phẩm",
        );
        applyItemPickerResponse(picker, data);
    } catch (caught) {
        resetItemPickerResults(picker);
        error.value =
            caught instanceof Error
                ? caught.message
                : "Không thể tải danh sách vật phẩm";
    } finally {
        picker.loading = false;
    }
}
</script>

<style scoped>
.picker-overlay {
    position: fixed;
    inset: 0;
    z-index: 3000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: var(--ds-overlay-bg);
}

.picker-panel {
    display: flex;
    width: min(980px, 100%);
    max-height: calc(100vh - 48px);
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-surface-2);
}

.picker-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid var(--ds-border);
}

.picker-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
    font-size: 15px;
}

.picker-close {
    display: inline-flex;
    width: 30px;
    height: 30px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: transparent;
    color: var(--ds-text-muted);
    cursor: pointer;
}

.picker-tools {
    display: grid;
    grid-template-columns: 1fr 180px auto;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--ds-border);
}

.picker-list {
    min-height: 320px;
    padding: 8px 10px;
    overflow: auto;
}

.picker-item {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: var(--ds-text);
    text-align: left;
    cursor: pointer;
}

.picker-item:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.3);
    background: rgba(var(--ds-primary-rgb), 0.08);
}

.picker-item-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 6px;
    background: var(--ds-gray-100);
}

.picker-item-info {
    display: flex;
    min-width: 0;
    flex: 1;
    flex-direction: column;
}

.picker-item-name {
    color: var(--ds-text-emphasis);
    font-size: 13px;
}

.picker-item-meta {
    color: var(--ds-text-muted);
    font-size: 11px;
}

.picker-item-add {
    color: var(--ds-primary);
    font-size: 18px;
}

.picker-empty {
    display: flex;
    height: 220px;
    align-items: center;
    justify-content: center;
    color: var(--ds-text-muted);
    font-size: 13px;
}

.picker-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    border-top: 1px solid var(--ds-border);
    color: var(--ds-text-muted);
    font-size: 12px;
}

.picker-pagination,
.picker-page-list {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.picker-pagination-ellipsis,
.picker-pagination-summary {
    color: var(--ds-text-muted);
}

.picker-page-input {
    width: 72px;
    min-width: 72px;
    padding: 6px 8px !important;
}

@media (max-width: 900px) {
    .picker-overlay {
        padding: 12px;
    }

    .picker-tools {
        grid-template-columns: 1fr;
    }

    .picker-foot {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
