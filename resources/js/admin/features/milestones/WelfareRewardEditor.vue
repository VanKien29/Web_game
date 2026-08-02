<template>
    <div class="reward-editor">
        <div class="reward-editor__toolbar">
            <div class="item-search-wrap" @click.stop>
                <span class="mi search-icon">search</span>
                <input
                    v-model="itemQuery"
                    class="form-input search-input"
                    autocomplete="off"
                    placeholder="Tìm vật phẩm theo tên hoặc ID..."
                    @input="scheduleItemSearch"
                    @focus="showItemResults = true"
                />
                <div
                    v-if="showItemResults && itemResults.length"
                    class="item-search-results"
                >
                    <button
                        v-for="item in itemResults"
                        :key="item.id"
                        type="button"
                        class="item-result"
                        @click="addItem(item)"
                    >
                        <AdminIcon :icon-id="item.icon_id" class="item-result__icon" />
                        <span class="item-result__name">
                            <strong>{{ item.name }}</strong>
                            <small>ID {{ item.id }}</small>
                        </span>
                        <span class="mi item-result__add">add_circle</span>
                    </button>
                </div>
            </div>
            <div class="reward-editor__actions">
                <span class="reward-editor__count">
                    {{ localRewards.length }} vật phẩm
                </span>
                <button
                    type="button"
                    class="btn btn-outline btn-sm"
                    @click="itemPickerOpen = true"
                >
                    <span class="mi">list</span>
                    Chọn item
                </button>
            </div>
        </div>

        <div
            v-if="localRewards.length"
            class="reward-table-wrap items-table-wrap"
        >
            <table class="reward-table">
                <thead>
                    <tr>
                        <th class="column-index">#</th>
                        <th class="column-icon"></th>
                        <th>Vật phẩm</th>
                        <th class="column-amount">Số lượng</th>
                        <th>Options</th>
                        <th class="column-action"></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(reward, index) in localRewards" :key="reward.key">
                        <tr>
                            <td class="cell-index">{{ index + 1 }}</td>
                            <td>
                                <AdminIcon :icon-id="reward.icon_id ?? null" class="reward-icon" />
                            </td>
                            <td>
                                <strong>{{ reward.name || `Item #${reward.item_id}` }}</strong>
                                <small class="item-id">ID {{ reward.item_id }}</small>
                            </td>
                            <td>
                                <input
                                    v-model.number="reward.amount"
                                    class="form-input amount-input"
                                    type="number"
                                    min="1"
                                    required
                                    @input="emitRewards"
                                />
                            </td>
                            <td>
                                <div class="option-list">
                                    <button
                                        v-for="(option, optionIndex) in reward.options"
                                        :key="`${reward.key}-option-${optionIndex}`"
                                        type="button"
                                        class="option-pill"
                                        title="Bấm để sửa option"
                                        @click="openOptionEditor(reward, optionIndex)"
                                    >
                                        {{ optionLabel(option.id, option.param) }}
                                        <span
                                            class="option-pill__remove"
                                            title="Xóa option"
                                            @click.stop="removeOption(reward, optionIndex)"
                                        >
                                            ×
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="option-add"
                                        title="Thêm option"
                                        @click="openOptionEditor(reward)"
                                    >
                                        <span class="mi">add</span>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="remove-item"
                                    title="Xóa vật phẩm"
                                    @click="removeItem(index)"
                                >
                                    <span class="mi">delete</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="optionEditor.itemKey === reward.key" class="option-editor-row">
                            <td colspan="6">
                                <div class="option-editor">
                                    <div class="option-select-wrap">
                                        <input
                                            v-model="optionEditor.query"
                                            class="form-input"
                                            placeholder="Tìm tên hoặc ID option..."
                                            autocomplete="off"
                                            @focus="optionEditor.open = true"
                                            @input="optionEditor.open = true"
                                        />
                                        <div
                                            v-if="optionEditor.open"
                                            class="option-dropdown"
                                        >
                                            <button
                                                v-for="option in filteredOptions"
                                                :key="option.id"
                                                type="button"
                                                class="option-dropdown-item"
                                                @click="selectOption(option)"
                                            >
                                                <span>{{ option.name }}</span>
                                                <small>ID {{ option.id }}</small>
                                            </button>
                                            <div v-if="!filteredOptions.length" class="no-options">
                                                Không tìm thấy option
                                            </div>
                                        </div>
                                    </div>
                                    <div class="option-param">
                                        <label>Chỉ số</label>
                                        <input
                                            v-model.number="optionEditor.param"
                                            class="form-input"
                                            type="number"
                                            min="0"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        :disabled="optionEditor.id === null"
                                        @click="saveOption(reward)"
                                    >
                                        Lưu option
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-sm"
                                        @click="closeOptionEditor"
                                    >
                                        Hủy
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div v-else class="empty-rewards">
            <span class="mi">redeem</span>
            <strong>Chưa có vật phẩm thưởng</strong>
            <small>Tìm vật phẩm phía trên để thêm vào mốc.</small>
        </div>

        <ItemCatalogPicker
            :open="itemPickerOpen"
            @close="itemPickerOpen = false"
            @select="addItem"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import { readJsonResponse } from "../../shared/api";
import ItemCatalogPicker from "./ItemCatalogPicker.vue";
import type { WelfareReward } from "./welfareTypes";

interface CatalogItem {
    id: number;
    name: string;
    icon_id: number | null;
}

interface OptionTemplate {
    id: number;
    name: string;
}

interface OptionEditor {
    itemKey: string;
    optionIndex: number | null;
    id: number | null;
    param: number;
    query: string;
    open: boolean;
}

const props = defineProps<{ modelValue: WelfareReward[] }>();
const emit = defineEmits<{ "update:modelValue": [value: WelfareReward[]] }>();

const localRewards = ref<WelfareReward[]>([]);
const itemQuery = ref("");
const itemResults = ref<CatalogItem[]>([]);
const showItemResults = ref(false);
const itemPickerOpen = ref(false);
const allOptions = ref<OptionTemplate[]>([]);
const optionEditor = reactive<OptionEditor>({
    itemKey: "",
    optionIndex: null,
    id: null,
    param: 0,
    query: "",
    open: false,
});
let searchTimer: number | undefined;

const filteredOptions = computed(() => {
    const query = optionEditor.query.trim().toLowerCase();
    const options = query
        ? allOptions.value.filter(
              (option) =>
                  option.name.toLowerCase().includes(query) ||
                  String(option.id).includes(query),
          )
        : allOptions.value;
    return options.slice(0, 40);
});

watch(
    () => props.modelValue,
    (value) => {
        localRewards.value = value.map((reward) => ({
            ...reward,
            key: reward.key || rewardKey(reward.item_id),
            options: (reward.options || []).map((option) => ({ ...option })),
        }));
    },
    { immediate: true, deep: true },
);

function rewardKey(itemId: number): string {
    return `${itemId}-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function cloneRewards(): WelfareReward[] {
    return localRewards.value.map((reward) => ({
        ...reward,
        options: reward.options.map((option) => ({ ...option })),
    }));
}

function emitRewards(): void {
    emit("update:modelValue", cloneRewards());
}

function optionLabel(id: number, param: number): string {
    const template = allOptions.value.find((option) => Number(option.id) === Number(id));
    if (!template) return `Option ${id}: ${param}`;
    return template.name.includes("#")
        ? template.name.replace("#", String(param))
        : `${template.name}: ${param}`;
}

function scheduleItemSearch(): void {
    window.clearTimeout(searchTimer);
    const query = itemQuery.value.trim();
    if (!query) {
        itemResults.value = [];
        showItemResults.value = false;
        return;
    }
    searchTimer = window.setTimeout(() => searchItems(query), 250);
}

async function searchItems(query: string): Promise<void> {
    try {
        const response = await fetch(`/admin/api/items/search?q=${encodeURIComponent(query)}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tìm vật phẩm");
        itemResults.value = Array.isArray(data) ? data : data.data || [];
        showItemResults.value = true;
    } catch {
        itemResults.value = [];
    }
}

function addItem(item: CatalogItem): void {
    const existing = localRewards.value.find((reward) => reward.item_id === Number(item.id));
    if (existing) {
        existing.amount += 1;
    } else {
        localRewards.value.push({
            key: rewardKey(Number(item.id)),
            item_id: Number(item.id),
            amount: 1,
            options: [],
            name: item.name || `Item #${item.id}`,
            icon_id: item.icon_id ?? null,
        });
    }
    itemQuery.value = "";
    itemResults.value = [];
    showItemResults.value = false;
    emitRewards();
}

function removeItem(index: number): void {
    localRewards.value.splice(index, 1);
    closeOptionEditor();
    emitRewards();
}

function openOptionEditor(reward: WelfareReward, optionIndex: number | null = null): void {
    const option = optionIndex === null ? null : reward.options[optionIndex];
    const template = option
        ? allOptions.value.find((entry) => Number(entry.id) === Number(option.id))
        : null;
    Object.assign(optionEditor, {
        itemKey: reward.key || "",
        optionIndex,
        id: option?.id ?? null,
        param: option?.param ?? 0,
        query: template ? `${template.name} (ID: ${template.id})` : option ? `ID: ${option.id}` : "",
        open: false,
    });
}

function selectOption(option: OptionTemplate): void {
    optionEditor.id = Number(option.id);
    optionEditor.query = `${option.name} (ID: ${option.id})`;
    optionEditor.open = false;
}

function saveOption(reward: WelfareReward): void {
    if (optionEditor.id === null) return;
    const value = { id: Number(optionEditor.id), param: Math.max(0, Number(optionEditor.param) || 0) };
    if (optionEditor.optionIndex === null) {
        reward.options.push(value);
    } else {
        reward.options.splice(optionEditor.optionIndex, 1, value);
    }
    closeOptionEditor();
    emitRewards();
}

function removeOption(reward: WelfareReward, optionIndex: number): void {
    reward.options.splice(optionIndex, 1);
    closeOptionEditor();
    emitRewards();
}

function closeOptionEditor(): void {
    Object.assign(optionEditor, {
        itemKey: "",
        optionIndex: null,
        id: null,
        param: 0,
        query: "",
        open: false,
    });
}

async function loadOptions(): Promise<void> {
    try {
        const response = await fetch("/admin/api/options", {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tải danh sách option");
        allOptions.value = Array.isArray(data)
            ? data
            : Array.isArray(data.data)
              ? data.data
              : data.options || [];
    } catch {
        allOptions.value = [];
    }
}

function closeDropdowns(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    if (!target.closest(".item-search-wrap")) showItemResults.value = false;
    if (!target.closest(".option-select-wrap")) optionEditor.open = false;
}

onMounted(() => {
    loadOptions();
    document.addEventListener("click", closeDropdowns);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", closeDropdowns);
    window.clearTimeout(searchTimer);
});
</script>

<style scoped>
.reward-editor__toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-bottom: 1px solid var(--ds-border);
}

.item-search-wrap {
    position: relative;
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

.reward-editor__count {
    color: var(--muted-foreground);
    font-size: 12px;
    white-space: nowrap;
}

.reward-editor__actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.item-search-results,
.option-dropdown {
    position: absolute;
    z-index: 40;
    top: calc(100% + 5px);
    right: 0;
    left: 0;
    max-height: 280px;
    overflow-y: auto;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-xl);
}

.item-result,
.option-dropdown-item {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 10px;
    padding: 8px 10px;
    border: 0;
    border-bottom: 1px solid var(--ds-border);
    background: transparent;
    color: var(--ds-text);
    text-align: left;
    cursor: pointer;
}

.item-result:hover,
.option-dropdown-item:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
}

.item-result__icon {
    width: 36px;
    height: 36px;
}

.item-result__name,
.option-dropdown-item span {
    display: flex;
    min-width: 0;
    flex: 1;
    flex-direction: column;
}

.item-result__name small,
.option-dropdown small {
    color: var(--muted-foreground);
}

.item-result__add {
    color: var(--ds-primary);
}

.reward-table-wrap {
    overflow: visible;
}

.reward-table {
    min-width: 760px;
}

.column-index {
    width: 38px;
}

.column-icon {
    width: 50px;
}

.column-amount {
    width: 110px;
}

.column-action {
    width: 50px;
}

.cell-index {
    color: var(--muted-foreground);
    text-align: center;
}

.reward-icon {
    width: 38px;
    height: 38px;
}

.item-id {
    display: block;
    color: var(--muted-foreground);
    font-size: 11px;
}

.amount-input {
    width: 90px;
}

.option-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.option-pill,
.option-add,
.remove-item {
    border: 1px solid var(--ds-border);
    border-radius: 6px;
    background: var(--ds-surface);
    color: var(--ds-text);
    cursor: pointer;
}

.option-pill {
    padding: 4px 6px;
    font-size: 11px;
}

.option-pill__remove {
    margin-left: 4px;
    color: var(--destructive);
    font-size: 15px;
}

.option-add,
.remove-item {
    display: inline-grid;
    width: 30px;
    height: 30px;
    place-items: center;
}

.option-add {
    color: var(--ds-primary);
}

.remove-item {
    color: var(--destructive);
}

.option-editor-row td {
    padding: 8px 12px;
    background: rgba(var(--ds-primary-rgb), 0.05);
}

.option-editor {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) 120px auto auto;
    align-items: end;
    gap: 8px;
}

.option-select-wrap {
    position: relative;
}

.option-param {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.option-param label {
    color: var(--muted-foreground);
    font-size: 11px;
}

.no-options {
    padding: 10px;
    color: var(--muted-foreground);
    font-size: 12px;
}

.empty-rewards {
    display: flex;
    min-height: 180px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    color: var(--muted-foreground);
}

.empty-rewards .mi {
    font-size: 30px;
}

@media (max-width: 760px) {
    .reward-editor__toolbar,
    .reward-editor__actions {
        align-items: stretch;
        flex-direction: column;
    }

    .option-editor {
        grid-template-columns: 1fr;
        align-items: stretch;
    }
}
</style>
