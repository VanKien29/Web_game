<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">
                    {{ isEdit ? "Sửa cấu hình phúc lợi" : "Thêm cấu hình phúc lợi" }}
                </h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <router-link :to="{ name: 'admin.welfare-configs' }">Phúc lợi</router-link>
                    <span>/</span>
                    <span class="current">{{ isEdit ? `#${route.params.id}` : "Tạo mới" }}</span>
                </nav>
            </div>
            <router-link :to="{ name: 'admin.welfare-configs' }" class="btn btn-outline">
                <span class="mi">arrow_back</span>
                Quay lại
            </router-link>
        </div>

        <div class="type-tabs">
            <button class="type-tab" @click="openMilestones('moc_nap')">Mốc nạp</button>
            <button class="type-tab" @click="openMilestones('moc_nap_top')">Mốc nạp top</button>
            <button class="type-tab" @click="openMilestones('moc_nhiem_vu_top')">Mốc nhiệm vụ top</button>
            <button class="type-tab" @click="openMilestones('moc_suc_manh_top')">Mốc sức mạnh top</button>
            <button class="type-tab active" @click="router.push({ name: 'admin.welfare-configs' })">
                Phúc lợi
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <form @submit.prevent="save">
            <div class="card">
                <div class="card-header">
                    <h3>Thông tin cấu hình</h3>
                    <span class="type-hint">{{ typeConfig.hint }}</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Loại phúc lợi <span class="required">*</span></label>
                        <select v-model="form.type" class="form-input" @change="onTypeChanged">
                            <option
                                v-for="option in WELFARE_TYPE_OPTIONS"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div v-if="usesReference(form.type)" class="form-group">
                        <label class="form-label">
                            {{ typeConfig.refLabel }} <span class="required">*</span>
                        </label>
                        <input
                            v-model.number="form.ref_id"
                            class="form-input"
                            type="number"
                            min="1"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Thứ tự hiển thị</label>
                        <input
                            v-model.number="form.sort_order"
                            class="form-input"
                            type="number"
                            min="0"
                        />
                    </div>

                    <div class="form-group status-field">
                        <label class="form-label">Trạng thái</label>
                        <label class="switch-row">
                            <input v-model="form.active" type="checkbox" />
                            <span class="switch-track"><span></span></span>
                            <strong>{{ form.active ? "Đang bật" : "Đang tắt" }}</strong>
                        </label>
                    </div>
                </div>

                <template v-if="form.type === 'message'">
                    <div class="form-grid message-grid">
                        <div class="form-group">
                            <label class="form-label">Mã nội dung <span class="required">*</span></label>
                            <input
                                v-model.trim="form.msg_key"
                                class="form-input code-input"
                                required
                                maxlength="64"
                                pattern="[a-z0-9_]+"
                                placeholder="Ví dụ: attendance_success"
                            />
                            <small>Chỉ dùng chữ thường, số và dấu gạch dưới. Mã phải khớp key Java đang gọi.</small>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Nội dung tiếng Việt <span class="required">*</span></label>
                            <textarea
                                v-model.trim="form.msg_value"
                                class="form-input"
                                rows="4"
                                required
                                placeholder="Nội dung gửi cho người chơi..."
                            ></textarea>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Tên hiển thị</label>
                            <input
                                v-model.trim="form.label"
                                class="form-input"
                                maxlength="255"
                                :placeholder="isPackage ? 'Ví dụ: Gói ngày 1' : 'Có thể để trống'"
                            />
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Mô tả</label>
                            <textarea
                                v-model.trim="form.description"
                                class="form-input"
                                rows="3"
                                placeholder="Mô tả hiển thị trong giao diện phúc lợi..."
                            ></textarea>
                        </div>
                    </div>

                    <div v-if="isPackage" class="form-grid price-grid">
                        <div class="form-group">
                            <label class="form-label">Giá gốc</label>
                            <input v-model.number="form.price" class="form-input" type="number" min="0" />
                            <small>Dùng làm giá dự phòng cho client/server cũ.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Giá cash <span class="required">*</span></label>
                            <input v-model.number="form.cash" class="form-input" type="number" min="0" />
                            <small>Game server ưu tiên trừ giá trị cash này.</small>
                        </div>
                    </div>
                </template>
            </div>

            <div v-if="form.type !== 'message'" class="card">
                <div class="card-header reward-header">
                    <div>
                        <h3>Vật phẩm thưởng</h3>
                        <span class="type-hint">
                            {{ form.type === "attendance_daily"
                                ? "Mỗi lần điểm danh sẽ chọn ngẫu nhiên một mục trong danh sách."
                                : "Người chơi nhận toàn bộ vật phẩm trong danh sách khi đủ điều kiện." }}
                        </span>
                    </div>
                    <span class="reward-count">{{ rewards.length }} vật phẩm</span>
                </div>

                <div class="item-search-wrap" @click.stop>
                    <div class="item-search-row">
                        <div class="search-input-wrap">
                            <span class="mi search-icon">search</span>
                            <input
                                v-model="itemQuery"
                                class="form-input search-input"
                                autocomplete="off"
                                placeholder="Tìm vật phẩm theo tên hoặc ID..."
                                @input="scheduleItemSearch"
                                @focus="showItemResults = true"
                            />
                        </div>
                        <span v-if="searching" class="search-state">Đang tìm...</span>
                    </div>
                    <div v-if="showItemResults && itemResults.length" class="item-results">
                        <button
                            v-for="item in itemResults"
                            :key="item.id"
                            type="button"
                            class="item-result"
                            @click="addReward(item)"
                        >
                            <AdminIcon :icon-id="item.icon_id" class="picker-icon" />
                            <span>
                                <strong>{{ item.name }}</strong>
                                <small>ID {{ item.id }}</small>
                            </span>
                            <span class="mi add-icon">add_circle</span>
                        </button>
                    </div>
                </div>

                <div v-if="rewards.length" class="reward-list">
                    <div v-for="(reward, index) in rewards" :key="reward.key" class="reward-row">
                        <span class="drag-index">{{ index + 1 }}</span>
                        <AdminIcon :icon-id="reward.icon_id" class="reward-main-icon" />
                        <div class="reward-name">
                            <strong>{{ reward.name || `Item #${reward.item_id}` }}</strong>
                            <small>ID {{ reward.item_id }}</small>
                        </div>
                        <div class="form-group amount-field">
                            <label class="form-label">Số lượng</label>
                            <input
                                v-model.number="reward.amount"
                                class="form-input"
                                type="number"
                                min="1"
                                required
                            />
                        </div>
                        <div class="reward-actions">
                            <button
                                type="button"
                                class="icon-button"
                                :disabled="index === 0"
                                title="Chuyển lên"
                                @click="moveReward(index, -1)"
                            >
                                <span class="mi">arrow_upward</span>
                            </button>
                            <button
                                type="button"
                                class="icon-button"
                                :disabled="index === rewards.length - 1"
                                title="Chuyển xuống"
                                @click="moveReward(index, 1)"
                            >
                                <span class="mi">arrow_downward</span>
                            </button>
                            <button
                                type="button"
                                class="icon-button danger"
                                title="Xóa vật phẩm"
                                @click="rewards.splice(index, 1)"
                            >
                                <span class="mi">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="empty-rewards">
                    <span class="mi">redeem</span>
                    <strong>Chưa có vật phẩm thưởng</strong>
                    <small>Dùng ô tìm kiếm phía trên để thêm ít nhất một vật phẩm.</small>
                </div>
            </div>

            <div v-if="isEdit && (form.created_at || form.updated_at)" class="card metadata-card">
                <div>
                    <span>Tạo lúc</span>
                    <strong>{{ formatDate(form.created_at) }}</strong>
                </div>
                <div>
                    <span>Cập nhật lúc</span>
                    <strong>{{ formatDate(form.updated_at) }}</strong>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" :disabled="saving">
                    <span class="mi">{{ saving ? "hourglass_top" : "save" }}</span>
                    {{ saving ? "Đang lưu..." : "Lưu cấu hình" }}
                </button>
                <router-link :to="{ name: 'admin.welfare-configs' }" class="btn btn-outline">
                    Hủy
                </router-link>
                <button
                    v-if="isEdit"
                    class="btn btn-danger delete-button"
                    type="button"
                    :disabled="saving"
                    @click="removeConfig"
                >
                    <span class="mi">delete</span>
                    Xóa cấu hình
                </button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { readJsonResponse } from "../../shared/api";
import {
    WELFARE_TYPES,
    WELFARE_TYPE_OPTIONS,
    isPackageType,
    usesReference,
    type WelfareType,
} from "./welfareTypes";

interface CatalogItem {
    id: number;
    name: string;
    icon_id: number | null;
}

interface RewardForm {
    key: string;
    item_id: number;
    amount: number;
    name: string;
    icon_id: number | null;
}

interface ConfigForm {
    type: WelfareType;
    ref_id: number;
    label: string;
    description: string;
    price: number;
    cash: number;
    msg_key: string;
    msg_value: string;
    sort_order: number;
    active: boolean;
    created_at: string;
    updated_at: string;
}

const route = useRoute();
const router = useRouter();
const form = reactive<ConfigForm>({
    type: "level",
    ref_id: 1,
    label: "",
    description: "",
    price: 0,
    cash: 0,
    msg_key: "",
    msg_value: "",
    sort_order: 0,
    active: true,
    created_at: "",
    updated_at: "",
});
const rewards = ref<RewardForm[]>([]);
const itemQuery = ref("");
const itemResults = ref<CatalogItem[]>([]);
const showItemResults = ref(false);
const searching = ref(false);
const saving = ref(false);
const error = ref("");
const success = ref("");
let searchTimer: number | undefined;

const isEdit = computed(() => Boolean(route.params.id));
const typeConfig = computed(() => WELFARE_TYPES[form.type]);
const isPackage = computed(() => isPackageType(form.type));

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || "";
}

function openMilestones(type: string): void {
    router.push({ name: "admin.milestones", params: { type } });
}

function rewardKey(itemId: number): string {
    return `${itemId}-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function onTypeChanged(): void {
    if (!usesReference(form.type)) form.ref_id = 0;
    else if (form.ref_id < 1) form.ref_id = 1;
    if (!isPackage.value) {
        form.price = 0;
        form.cash = 0;
    }
    if (form.type === "message") rewards.value = [];
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
    searching.value = true;
    try {
        const response = await fetch(`/admin/api/items/search?q=${encodeURIComponent(query)}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tìm vật phẩm");
        itemResults.value = Array.isArray(data) ? data : data.data || [];
        showItemResults.value = true;
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể tìm vật phẩm";
    } finally {
        searching.value = false;
    }
}

function addReward(item: CatalogItem): void {
    const existing = rewards.value.find((reward) => reward.item_id === Number(item.id));
    if (existing) {
        existing.amount += 1;
    } else {
        rewards.value.push({
            key: rewardKey(Number(item.id)),
            item_id: Number(item.id),
            amount: 1,
            name: item.name || `Item #${item.id}`,
            icon_id: item.icon_id ?? null,
        });
    }
    itemQuery.value = "";
    itemResults.value = [];
    showItemResults.value = false;
}

function moveReward(index: number, direction: -1 | 1): void {
    const target = index + direction;
    if (target < 0 || target >= rewards.value.length) return;
    const [item] = rewards.value.splice(index, 1);
    rewards.value.splice(target, 0, item);
}

function formatDate(value: string): string {
    if (!value) return "—";
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat("vi-VN", { dateStyle: "medium", timeStyle: "short" }).format(date);
}

function closeSearch(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    if (!target.closest(".item-search-wrap")) showItemResults.value = false;
}

async function loadRecord(): Promise<void> {
    error.value = "";
    try {
        const response = await fetch(`/admin/api/welfare-configs/${route.params.id}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const payload = await readJsonResponse(response, "Không thể tải cấu hình");
        const data = payload.data;
        Object.assign(form, {
            type: data.type,
            ref_id: Number(data.ref_id || 0),
            label: data.label || "",
            description: data.description || "",
            price: Number(data.price || 0),
            cash: Number(data.cash || 0),
            msg_key: data.msg_key || "",
            msg_value: data.msg_value || "",
            sort_order: Number(data.sort_order || 0),
            active: Boolean(data.active),
            created_at: data.created_at || "",
            updated_at: data.updated_at || "",
        });
        const catalog = payload.item_catalog || {};
        rewards.value = (data.rewards || []).map((reward: { item_id: number; amount: number }) => ({
            key: rewardKey(Number(reward.item_id)),
            item_id: Number(reward.item_id),
            amount: Number(reward.amount || 1),
            name: catalog[reward.item_id]?.name || `Item #${reward.item_id}`,
            icon_id: catalog[reward.item_id]?.icon_id ?? null,
        }));
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể tải cấu hình";
    }
}

function validateForm(): string {
    if (usesReference(form.type) && form.ref_id < 1) return "Mốc/ID tham chiếu phải lớn hơn 0.";
    if (form.type === "message") {
        if (!form.msg_key || !form.msg_value) return "Cần nhập đầy đủ mã và nội dung hệ thống.";
        return "";
    }
    if (!rewards.value.length) return "Cần thêm ít nhất một vật phẩm thưởng.";
    if (rewards.value.some((reward) => Number(reward.amount) < 1)) {
        return "Số lượng vật phẩm phải lớn hơn 0.";
    }
    return "";
}

async function save(): Promise<void> {
    error.value = validateForm();
    success.value = "";
    if (error.value) return;
    saving.value = true;
    try {
        const body = {
            type: form.type,
            ref_id: usesReference(form.type) ? Number(form.ref_id) : 0,
            label: form.label,
            description: form.description,
            price: isPackage.value ? Number(form.price || 0) : 0,
            cash: isPackage.value ? Number(form.cash || 0) : 0,
            rewards: form.type === "message"
                ? []
                : rewards.value.map((reward) => ({
                    item_id: Number(reward.item_id),
                    amount: Number(reward.amount),
                })),
            msg_key: form.type === "message" ? form.msg_key : "",
            msg_value: form.type === "message" ? form.msg_value : "",
            sort_order: Number(form.sort_order || 0),
            active: Boolean(form.active),
        };
        const url = isEdit.value
            ? `/admin/api/welfare-configs/${route.params.id}`
            : "/admin/api/welfare-configs";
        const response = await fetch(url, {
            method: isEdit.value ? "PUT" : "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify(body),
        });
        const data = await readJsonResponse(response, "Không thể lưu cấu hình");
        success.value = data.message || "Đã lưu cấu hình";
        if (!isEdit.value) {
            await router.replace({ name: "admin.welfare-configs.edit", params: { id: data.id } });
        } else {
            await loadRecord();
        }
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể lưu cấu hình";
    } finally {
        saving.value = false;
    }
}

async function removeConfig(): Promise<void> {
    if (!window.confirm("Xóa cấu hình này? Thay đổi sẽ ảnh hưởng trực tiếp tới game.")) return;
    saving.value = true;
    error.value = "";
    try {
        const response = await fetch(`/admin/api/welfare-configs/${route.params.id}`, {
            method: "DELETE",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken(),
                Accept: "application/json",
            },
        });
        await readJsonResponse(response, "Không thể xóa cấu hình");
        await router.push({ name: "admin.welfare-configs" });
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể xóa cấu hình";
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", closeSearch);
    if (isEdit.value) loadRecord();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", closeSearch);
    window.clearTimeout(searchTimer);
});
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

.type-tab.active {
    border-color: rgba(var(--ds-primary-rgb), 0.55);
    background: rgba(var(--ds-primary-rgb), 0.15);
    color: var(--ds-primary);
}

.card {
    margin-bottom: 16px;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
}

.card-header h3 {
    margin: 0;
}

.type-hint,
.form-group small {
    color: var(--muted-foreground);
    font-size: 12px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    padding: 18px;
}

.full-width {
    grid-column: 1 / -1;
}

.required {
    color: #dc2626;
}

.code-input {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

.switch-row {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 38px;
    cursor: pointer;
}

.switch-row input {
    position: absolute;
    opacity: 0;
}

.switch-track {
    width: 42px;
    height: 24px;
    padding: 3px;
    border-radius: 999px;
    background: #cbd5e1;
    transition: 0.2s;
}

.switch-track span {
    display: block;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    transition: 0.2s;
}

.switch-row input:checked + .switch-track {
    background: #22c55e;
}

.switch-row input:checked + .switch-track span {
    transform: translateX(18px);
}

.reward-header {
    align-items: flex-start;
}

.reward-count {
    padding: 5px 9px;
    border-radius: 999px;
    background: rgba(99, 102, 241, 0.12);
    color: var(--primary);
    font-size: 12px;
    font-weight: 700;
}

.item-search-wrap {
    position: relative;
    padding: 18px;
    border-bottom: 1px solid var(--border);
}

.item-search-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-search-row .search-input-wrap {
    position: relative;
    flex: 1;
}

.item-search-row .search-input {
    padding-left: 38px;
}

.item-search-row .search-icon {
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: var(--muted-foreground);
}

.search-state {
    color: var(--muted-foreground);
    font-size: 12px;
}

.item-results {
    position: absolute;
    z-index: 30;
    top: 62px;
    left: 18px;
    right: 18px;
    max-height: 320px;
    overflow-y: auto;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card, #fff);
    box-shadow: 0 16px 35px rgba(15, 23, 42, 0.18);
}

.item-result {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 12px;
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--border);
    background: transparent;
    color: inherit;
    text-align: left;
    cursor: pointer;
}

.item-result:hover {
    background: rgba(99, 102, 241, 0.08);
}

.item-result span:nth-child(2) {
    display: flex;
    flex: 1;
    flex-direction: column;
}

.item-result small,
.reward-name small {
    color: var(--muted-foreground);
}

.picker-icon,
.reward-main-icon {
    width: 42px;
    height: 42px;
}

.add-icon {
    color: var(--primary);
}

.reward-list {
    padding: 8px 18px 18px;
}

.reward-row {
    display: grid;
    grid-template-columns: 30px 50px minmax(150px, 1fr) 150px auto;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.drag-index {
    color: var(--muted-foreground);
    font-weight: 700;
    text-align: center;
}

.reward-name {
    display: flex;
    flex-direction: column;
}

.amount-field {
    margin: 0;
}

.reward-actions {
    display: flex;
    gap: 5px;
}

.icon-button {
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.icon-button:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.icon-button.danger {
    color: #dc2626;
}

.empty-rewards {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 38px 18px;
    color: var(--muted-foreground);
}

.empty-rewards .mi {
    font-size: 34px;
}

.metadata-card {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    padding: 16px 18px;
}

.metadata-card div {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.metadata-card span {
    color: var(--muted-foreground);
    font-size: 12px;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.delete-button {
    margin-left: auto;
}

@media (max-width: 760px) {
    .form-grid,
    .metadata-card {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: auto;
    }

    .reward-row {
        grid-template-columns: 26px 42px 1fr;
    }

    .amount-field,
    .reward-actions {
        grid-column: 3;
    }

    .form-actions {
        flex-wrap: wrap;
    }

    .delete-button {
        margin-left: 0;
    }
}
</style>
