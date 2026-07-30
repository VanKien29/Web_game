<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">
                    {{ isEdit ? `Sửa ${currentConfig.label}` : `Thêm ${currentConfig.label}` }}
                </h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <router-link
                        :to="{ name: 'admin.welfare-configs', params: { type: currentType } }"
                    >
                        {{ currentConfig.label }}
                    </router-link>
                    <span>/</span>
                    <span class="current">{{ isEdit ? `#${route.params.id}` : "Tạo mới" }}</span>
                </nav>
            </div>
            <router-link
                :to="{ name: 'admin.welfare-configs', params: { type: currentType } }"
                class="btn btn-outline"
            >
                <span class="mi">arrow_back</span>
                Quay lại
            </router-link>
        </div>

        <div class="type-tabs">
            <button
                v-for="option in WELFARE_TYPE_OPTIONS"
                :key="option.value"
                class="type-tab"
                :class="{ active: option.value === currentType }"
                type="button"
                @click="openType(option.value)"
            >
                {{ option.label }}
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <form class="form-workspace" @submit.prevent="save">
            <main class="form-main">
                <section v-if="!isMessage" class="card editor-card">
                    <div class="card-header">
                        <div>
                            <h3>Vật phẩm thưởng</h3>
                            <small>
                                {{
                                    currentType === "attendance_daily"
                                        ? "Game chọn ngẫu nhiên một vật phẩm trong danh sách."
                                        : "Người chơi nhận toàn bộ danh sách khi đủ điều kiện."
                                }}
                            </small>
                        </div>
                    </div>
                    <WelfareRewardEditor v-model="rewards" />
                </section>

                <section v-else class="card message-card">
                    <div class="card-header">
                        <div>
                            <h3>Nội dung hệ thống</h3>
                            <small>Mã và nội dung tiếng Việt game server gửi tới người chơi.</small>
                        </div>
                    </div>
                    <div class="message-fields">
                        <div class="form-group">
                            <label class="form-label">
                                Mã nội dung <span class="required">*</span>
                            </label>
                            <input
                                v-model.trim="form.msg_key"
                                class="form-input code-input"
                                required
                                maxlength="64"
                                pattern="[a-z0-9_]+"
                                placeholder="Ví dụ: attendance_success"
                            />
                            <small>Chỉ dùng chữ thường, số và dấu gạch dưới.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Nội dung tiếng Việt <span class="required">*</span>
                            </label>
                            <textarea
                                v-model.trim="form.msg_value"
                                class="form-input message-textarea"
                                rows="8"
                                required
                                placeholder="Nội dung gửi cho người chơi..."
                            ></textarea>
                        </div>
                    </div>
                </section>
            </main>

            <aside class="config-sidebar">
                <section class="card config-card">
                    <div class="config-card__header">
                        <div>
                            <span class="config-card__eyebrow">Thông tin cấu hình</span>
                            <strong>{{ currentConfig.label }}</strong>
                        </div>
                        <span class="type-code">{{ currentType }}</span>
                    </div>

                    <div class="config-fields">
                        <div v-if="usesReference(currentType)" class="form-group">
                            <label class="form-label">
                                {{ currentConfig.refLabel }} <span class="required">*</span>
                            </label>
                            <input
                                v-model.number="form.ref_id"
                                class="form-input"
                                type="number"
                                min="1"
                                required
                            />
                        </div>

                        <template v-if="!isMessage">
                            <div class="form-group">
                                <label class="form-label">Tên hiển thị</label>
                                <input
                                    v-model.trim="form.label"
                                    class="form-input"
                                    maxlength="255"
                                    :placeholder="isPackage ? 'Tên gói' : 'Có thể để trống'"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mô tả</label>
                                <textarea
                                    v-model.trim="form.description"
                                    class="form-input"
                                    rows="3"
                                    placeholder="Mô tả ngắn..."
                                ></textarea>
                            </div>
                        </template>

                        <template v-if="isPackage">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Giá gốc</label>
                                    <input
                                        v-model.number="form.price"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                    />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cash</label>
                                    <input
                                        v-model.number="form.cash"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                    />
                                </div>
                            </div>
                        </template>

                        <div class="form-group">
                            <label class="form-label">Thứ tự</label>
                            <input
                                v-model.number="form.sort_order"
                                class="form-input"
                                type="number"
                                min="0"
                            />
                        </div>

                        <label class="switch-row">
                            <input v-model="form.active" type="checkbox" />
                            <span class="switch-track"><span></span></span>
                            <strong>{{ form.active ? "Đang bật" : "Đang tắt" }}</strong>
                        </label>
                    </div>

                    <div v-if="isEdit" class="metadata">
                        <span>Tạo: {{ formatDate(form.created_at) }}</span>
                        <span>Sửa: {{ formatDate(form.updated_at) }}</span>
                    </div>

                    <div class="config-actions">
                        <button class="btn btn-primary" type="submit" :disabled="saving">
                            <span class="mi">save</span>
                            {{ saving ? "Đang lưu..." : "Lưu cấu hình" }}
                        </button>
                        <button
                            v-if="isEdit"
                            class="btn btn-danger"
                            type="button"
                            :disabled="saving"
                            @click="removeConfig"
                        >
                            <span class="mi">delete</span>
                            Xóa
                        </button>
                    </div>
                </section>
            </aside>
        </form>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { readJsonResponse } from "../../shared/api";
import WelfareRewardEditor from "./WelfareRewardEditor.vue";
import {
    WELFARE_TYPES,
    WELFARE_TYPE_OPTIONS,
    isPackageType,
    usesReference,
    type WelfareReward,
    type WelfareType,
} from "./welfareTypes";

interface ConfigForm {
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
const rewards = ref<WelfareReward[]>([]);
const saving = ref(false);
const error = ref("");
const success = ref("");

const currentType = computed(() => route.params.type as WelfareType);
const currentConfig = computed(() => WELFARE_TYPES[currentType.value] || WELFARE_TYPES.attendance_daily);
const isEdit = computed(() => Boolean(route.params.id));
const isMessage = computed(() => currentType.value === "message");
const isPackage = computed(() => isPackageType(currentType.value));

watch(
    () => route.params.id,
    () => {
        if (isEdit.value) loadRecord();
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

function openType(type: WelfareType): void {
    if (type === currentType.value) return;
    router.push({ name: "admin.welfare-configs", params: { type } });
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || "";
}

function formatDate(value: string): string {
    if (!value) return "—";
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat("vi-VN", {
              dateStyle: "short",
              timeStyle: "short",
          }).format(date);
}

async function loadRecord(): Promise<void> {
    error.value = "";
    try {
        const response = await fetch(`/admin/api/welfare-configs/${route.params.id}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const payload = await readJsonResponse(response, "Không thể tải cấu hình");
        const data = payload.data;
        if (data.type !== currentType.value) {
            await router.replace({
                name: "admin.welfare-configs.edit",
                params: { type: data.type, id: data.id },
            });
            return;
        }
        Object.assign(form, {
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
        rewards.value = (data.rewards || []).map((reward: WelfareReward) => ({
            item_id: Number(reward.item_id),
            amount: Number(reward.amount || 1),
            options: (reward.options || []).map((option) => ({
                id: Number(option.id),
                param: Number(option.param || 0),
            })),
            name: catalog[reward.item_id]?.name || `Item #${reward.item_id}`,
            icon_id: catalog[reward.item_id]?.icon_id ?? null,
        }));
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể tải cấu hình";
    }
}

function validateForm(): string {
    if (usesReference(currentType.value) && form.ref_id < 1) {
        return "Giá trị mốc/ID phải lớn hơn 0.";
    }
    if (isMessage.value) {
        return form.msg_key && form.msg_value ? "" : "Cần nhập mã và nội dung hệ thống.";
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
            type: currentType.value,
            ref_id: usesReference(currentType.value) ? Number(form.ref_id) : 0,
            label: isMessage.value ? "" : form.label,
            description: isMessage.value ? "" : form.description,
            price: isPackage.value ? Number(form.price || 0) : 0,
            cash: isPackage.value ? Number(form.cash || 0) : 0,
            rewards: isMessage.value
                ? []
                : rewards.value.map((reward) => ({
                      item_id: Number(reward.item_id),
                      amount: Number(reward.amount),
                      options: reward.options.map((option) => ({
                          id: Number(option.id),
                          param: Number(option.param),
                      })),
                  })),
            msg_key: isMessage.value ? form.msg_key : "",
            msg_value: isMessage.value ? form.msg_value : "",
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
            await router.replace({
                name: "admin.welfare-configs.edit",
                params: { type: currentType.value, id: data.id },
            });
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
    if (!window.confirm("Xóa cấu hình này?")) return;
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
        await router.push({
            name: "admin.welfare-configs",
            params: { type: currentType.value },
        });
    } catch (caught) {
        error.value = caught instanceof Error ? caught.message : "Không thể xóa cấu hình";
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    if (!ensureType()) return;
    if (!usesReference(currentType.value)) form.ref_id = 0;
    if (isEdit.value) loadRecord();
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

.form-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    align-items: start;
    gap: 14px;
}

.card-header {
    padding: 12px 14px;
    border-bottom: 1px solid var(--ds-border);
}

.card-header h3 {
    margin: 0 0 2px;
}

.card-header small,
.message-fields small {
    color: var(--muted-foreground);
}

.editor-card {
    min-height: 360px;
}

.message-fields {
    display: grid;
    gap: 14px;
    padding: 14px;
}

.message-textarea {
    min-height: 180px;
}

.code-input,
.type-code {
    font-family: var(--font-mono);
}

.config-sidebar {
    position: sticky;
    top: 76px;
}

.config-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    padding: 12px;
    border-bottom: 1px solid var(--ds-border);
}

.config-card__header > div {
    display: flex;
    flex-direction: column;
}

.config-card__eyebrow {
    color: var(--muted-foreground);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.type-code {
    max-width: 125px;
    overflow: hidden;
    color: var(--muted-foreground);
    font-size: 9px;
    text-overflow: ellipsis;
}

.config-fields {
    display: grid;
    gap: 10px;
    padding: 12px;
}

.form-group {
    margin: 0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.required {
    color: var(--destructive);
}

.switch-row {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.switch-row input {
    position: absolute;
    opacity: 0;
}

.switch-track {
    width: 38px;
    height: 22px;
    padding: 3px;
    border-radius: 999px;
    background: var(--muted-foreground);
}

.switch-track span {
    display: block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--primary-foreground);
    transition: 0.15s;
}

.switch-row input:checked + .switch-track {
    background: var(--ds-primary);
}

.switch-row input:checked + .switch-track span {
    transform: translateX(16px);
}

.metadata {
    display: grid;
    gap: 2px;
    padding: 8px 12px;
    border-top: 1px solid var(--ds-border);
    color: var(--muted-foreground);
    font-size: 10px;
}

.config-actions {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 7px;
    padding: 12px;
    border-top: 1px solid var(--ds-border);
}

@media (max-width: 980px) {
    .form-workspace {
        grid-template-columns: 1fr;
    }

    .config-sidebar {
        position: static;
    }
}
</style>
