<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Hộp quà</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span class="sep">/</span>
                    <span class="current">Hộp quà</span>
                </nav>
            </div>
            <button class="btn btn-primary admin-fab" type="button" @click="openCreate">
                <span class="mi" style="font-size: 16px">add</span>
                Tạo item hộp
            </button>
        </div>

        <div class="info-note">
            <span class="mi">info</span>
            <span>Web chỉ tạo <strong>item_template TYPE 27</strong> và icon. Phần thưởng, tỉ lệ, quantity và option được viết trong <code>Source_game/src</code>.</span>
        </div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input v-model="search" class="form-input search-input" placeholder="Tìm ID hoặc tên item..." @input="debouncedLoadPage" />
                </div>
                <button class="btn btn-primary btn-sm" type="submit">
                    <span class="mi" style="font-size: 16px">filter_list</span>
                    Lọc
                </button>
            </form>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
            <div class="table-wrap">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Item template</th>
                            <th>Type</th>
                            <th>Reward</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="box in boxes" :key="box.item_id">
                            <td>#{{ box.item_id }}</td>
                            <td><AdminIcon :icon-id="box.icon_id" class="box-icon" /></td>
                            <td>
                                <div class="item-name">{{ box.name }}</div>
                                <div class="item-meta">{{ box.description || "Chưa có mô tả" }}</div>
                            </td>
                            <td><span class="badge badge-info">27</span></td>
                            <td>
                                <span class="badge badge-source">src game</span>
                            </td>
                            <td class="action-cell">
                                <button class="btn btn-primary btn-sm" type="button" @click="openEdit(box.item_id)">
                                    <span class="mi" style="font-size: 14px">edit</span>
                                    Sửa
                                </button>
                                <button class="btn btn-danger btn-sm" type="button" @click="deleteBox(box)">
                                    <span class="mi" style="font-size: 14px">delete</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="6"><span class="admin-loading-row__content"><span class="admin-loading-spinner"></span></span></td>
                        </tr>
                        <tr v-if="!boxes.length && !loading">
                            <td colspan="6" class="empty-cell">Chưa có item hộp quà TYPE 27.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="loadPage(page - 1)">&laquo;</button>
                <template v-for="item in paginationItems" :key="String(item)">
                    <span v-if="typeof item !== 'number'" class="pagination-ellipsis">...</span>
                    <button v-else :class="{ active: item === page }" @click="loadPage(item)">{{ item }}</button>
                </template>
                <button :disabled="page >= totalPages" @click="loadPage(page + 1)">&raquo;</button>
            </div>
        </div>

        <div v-if="editor.open" class="editor-overlay" @click.self="closeEditor">
            <div class="editor-panel">
                <div class="editor-head">
                    <div>
                        <h3>{{ editor.isEdit ? "Sửa item hộp quà" : "Tạo item hộp quà" }}</h3>
                        <p>Chỉ lưu item template. Reward sẽ được xử lý bằng code trong game source.</p>
                    </div>
                    <button class="icon-action" type="button" @click="closeEditor"><span class="mi">close</span></button>
                </div>

                <div v-if="editor.error" class="alert alert-error">{{ editor.error }}</div>

                <div class="editor-body">
                    <div class="editor-grid">
                        <label>
                            <span class="form-label">Item ID</span>
                            <input v-model.number="editor.form.item_id" class="form-input" type="number" min="0" :disabled="editor.isEdit" placeholder="Bỏ trống để tự tăng" />
                            <small class="field-hint">ID này sẽ dùng để thêm <code>case ID</code> trong <code>UseItem.java</code>.</small>
                        </label>
                        <label>
                            <span class="form-label">Tên item</span>
                            <input v-model.trim="editor.form.name" class="form-input" required placeholder="Ví dụ: Rương sự kiện" />
                        </label>
                        <label>
                            <span class="form-label">Type</span>
                            <input class="form-input" value="27 - Hộp quà" disabled />
                        </label>
                    </div>

                    <div class="icon-field">
                        <span class="form-label">Icon PNG x4{{ editor.isEdit ? " (bỏ trống để giữ icon cũ)" : " *" }}</span>
                        <div class="file-box" @dragover.prevent="iconDrag = true" @dragleave.prevent="iconDrag = false" @drop.prevent="dropIconFile">
                            <input id="gift-box-icon-upload" class="file-input-hidden" type="file" accept="image/png" @change="onIconFile" />
                            <label class="drop-box" :class="{ dragging: iconDrag }" for="gift-box-icon-upload">
                                <span class="mi">upload_file</span>
                                <strong>Chọn icon hộp</strong>
                                <small>{{ editor.iconFile ? editor.iconFile.name : "PNG x4, hệ thống tự sinh icon_id" }}</small>
                            </label>
                        </div>
                        <small class="field-hint">Tên file có thể là icon ID; nếu không hệ thống sẽ tự chọn ID icon tiếp theo.</small>
                    </div>

                    <label>
                        <span class="form-label">Mô tả</span>
                        <textarea v-model="editor.form.description" class="form-input" rows="4" placeholder="Mô tả hiển thị của item"></textarea>
                    </label>

                    <div class="source-checklist">
                        <strong>Sau khi tạo item, thêm vào game source:</strong>
                        <code>UseItem.java → case {{ editor.form.item_id || "ITEM_ID" }}</code>
                        <code>ItemService.java → OpenItem{{ editor.form.item_id || "ITEM_ID" }}(...)</code>
                    </div>
                </div>

                <div class="editor-actions">
                    <button class="btn btn-outline" type="button" :disabled="editor.saving" @click="closeEditor">Hủy</button>
                    <button class="btn btn-primary" type="button" :disabled="editor.saving" @click="saveEditor">
                        <span class="mi" style="font-size: 16px">save</span>
                        {{ editor.saving ? "Đang lưu..." : "Lưu item template" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from "vue";
import { buildPaginationItems, csrfToken } from "../../shared/format";
import { readJsonResponse } from "../../shared/api";

interface GiftBoxRow {
    id: number;
    item_id: number;
    name: string;
    description?: string;
    icon_id: number;
    legacy_config_id?: number | null;
}

interface EditorForm {
    item_id: number | string;
    name: string;
    description: string;
}

interface EditorState {
    open: boolean;
    isEdit: boolean;
    saving: boolean;
    error: string;
    iconFile: File | null;
    id: number | null;
    form: EditorForm;
}

interface AdminWindow extends Window {
    adminConfirm?: (options: Record<string, string>) => Promise<boolean>;
}

const boxes = ref<GiftBoxRow[]>([]);
const search = ref("");
const searchTimer = ref<number | null>(null);
const loading = ref(false);
const error = ref("");
const success = ref("");
const page = ref(1);
const totalPages = ref(1);
const iconDrag = ref(false);

const emptyForm = (): EditorForm => ({ item_id: "", name: "", description: "" });
const editor = reactive<EditorState>({
    open: false,
    isEdit: false,
    saving: false,
    error: "",
    iconFile: null,
    id: null,
    form: emptyForm(),
});

const paginationItems = computed(() => buildPaginationItems(page.value, totalPages.value));

const resetEditor = () => {
    editor.open = false;
    editor.isEdit = false;
    editor.saving = false;
    editor.error = "";
    editor.iconFile = null;
    editor.id = null;
    editor.form = emptyForm();
};

const debouncedLoadPage = () => {
    if (searchTimer.value !== null) window.clearTimeout(searchTimer.value);
    searchTimer.value = window.setTimeout(() => loadPage(1), 300);
};

async function loadPage(nextPage = 1) {
    loading.value = true;
    error.value = "";
    try {
        const params = new URLSearchParams({ page: String(Math.max(1, nextPage)), per_page: "20" });
        if (search.value.trim()) params.set("search", search.value.trim());
        const response = await fetch(`/admin/api/gift-boxes?${params.toString()}`, { headers: { "X-Requested-With": "XMLHttpRequest" } });
        const data = await readJsonResponse(response, "Không thể tải item hộp quà");
        boxes.value = data.data || [];
        page.value = data.page || 1;
        totalPages.value = data.total_pages || 1;
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : "Không thể tải item hộp quà";
        boxes.value = [];
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    resetEditor();
    editor.open = true;
}

async function openEdit(id: number) {
    resetEditor();
    editor.open = true;
    editor.isEdit = true;
    editor.id = id;
    try {
        const response = await fetch(`/admin/api/gift-boxes/${id}`, { headers: { "X-Requested-With": "XMLHttpRequest" } });
        const data = await readJsonResponse(response, "Không thể tải item hộp quà");
        const box = data.data || {};
        editor.form = {
            item_id: Number(box.item_id || id),
            name: box.NAME || box.name || "",
            description: box.description || "",
        };
    } catch (exception) {
        editor.error = exception instanceof Error ? exception.message : "Không thể tải item hộp quà";
    }
}

function closeEditor() {
    if (!editor.saving) editor.open = false;
}

function onIconFile(event: Event) {
    const input = event.target as HTMLInputElement;
    editor.iconFile = input.files?.[0] || null;
}

function dropIconFile(event: DragEvent) {
    iconDrag.value = false;
    const file = Array.from(event.dataTransfer?.files || []).find((item) => item.type === "image/png" || item.name.toLowerCase().endsWith(".png"));
    if (file) editor.iconFile = file;
}

async function saveEditor() {
    if (!editor.form.name.trim()) {
        editor.error = "Tên item không được để trống.";
        return;
    }
    if (!editor.isEdit && !editor.iconFile) {
        editor.error = "Vui lòng chọn icon PNG x4 để tạo item template.";
        return;
    }

    editor.saving = true;
    editor.error = "";
    try {
        const formData = new FormData();
        if (editor.form.item_id !== "" && Number(editor.form.item_id) >= 0) formData.append("item_id", String(editor.form.item_id));
        formData.append("name", editor.form.name);
        formData.append("description", editor.form.description || "");
        if (editor.iconFile) formData.append("icon_x4", editor.iconFile);

        const url = editor.isEdit ? `/admin/api/gift-boxes/${editor.id}` : "/admin/api/gift-boxes";
        const response = await fetch(url, {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrfToken() },
            body: formData,
        });
        const data = await readJsonResponse(response, "Không thể lưu item hộp quà");
        if (!data.ok) throw new Error(data.message || "Không thể lưu item hộp quà");

        success.value = data.message || "Đã lưu item hộp quà";
        closeEditor();
        await loadPage(page.value);
    } catch (exception) {
        editor.error = exception instanceof Error ? exception.message : "Không thể lưu item hộp quà";
    } finally {
        editor.saving = false;
    }
}

async function deleteBox(box: GiftBoxRow) {
    const confirm = (window as AdminWindow).adminConfirm;
    const ok = confirm
        ? await confirm({
              title: "Xóa item hộp quà",
              message: `Xóa item_template #${box.item_id} (${box.name})? Hãy chắc chắn item này chưa được phát cho người chơi.`,
              tone: "danger",
              confirmText: "Xóa",
          })
        : window.confirm(`Xóa item_template #${box.item_id}?`);
    if (!ok) return;

    try {
        const response = await fetch(`/admin/api/gift-boxes/${box.item_id}`, {
            method: "DELETE",
            headers: { "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrfToken() },
        });
        const data = await readJsonResponse(response, "Không thể xóa item hộp quà");
        success.value = data.message || "Đã xóa item hộp quà";
        await loadPage(page.value);
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : "Không thể xóa item hộp quà";
    }
}

onMounted(() => loadPage(1));
onUnmounted(() => {
    if (searchTimer.value !== null) window.clearTimeout(searchTimer.value);
});
</script>

<style scoped>
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}
.page-title {
    margin-bottom: 4px;
    color: var(--ds-text-emphasis);
    font-size: 20px;
    font-weight: 700;
}
.breadcrumb,
.search-form {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.breadcrumb {
    font-size: 13px;
}
.breadcrumb a,
.breadcrumb .sep,
.item-meta,
.field-hint {
    color: var(--ds-text-muted);
}
.info-note {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-bottom: 18px;
    padding: 11px 13px;
    border: 1px solid rgba(var(--ds-info-rgb), 0.28);
    border-radius: 9px;
    background: rgba(var(--ds-info-rgb), 0.08);
    color: var(--ds-text);
    font-size: 13px;
    line-height: 1.45;
}
.info-note > .mi {
    color: var(--ds-info);
    font-size: 19px;
}
.info-note code,
.source-checklist code,
.field-hint code {
    color: var(--ds-primary);
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
}
.filter-bar {
    margin-bottom: 20px;
}
.search-input-wrap {
    position: relative;
}
.search-icon {
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    font-size: 18px;
    pointer-events: none;
}
.search-input {
    width: 280px;
    padding-left: 38px !important;
}
.box-icon {
    width: 36px;
    height: 36px;
}
.item-name {
    color: var(--ds-text-emphasis);
    font-weight: 600;
}
.badge-source {
    border: 1px solid rgba(var(--ds-primary-rgb), 0.25);
    background: rgba(var(--ds-primary-rgb), 0.1);
    color: var(--ds-primary);
}
.action-cell {
    text-align: right;
    white-space: nowrap;
}
.empty-cell {
    padding: 28px;
    color: var(--ds-text-muted);
    text-align: center;
}
.pagination-ellipsis {
    color: var(--ds-text-muted);
}
.editor-overlay {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: var(--ds-overlay-bg);
    backdrop-filter: blur(4px);
}
.editor-panel {
    width: min(760px, calc(100vw - 32px));
    max-height: calc(100vh - 40px);
    overflow: auto;
    padding: 18px;
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-lg, 0 24px 70px rgba(0, 0, 0, 0.45));
}
.editor-head,
.editor-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.editor-head {
    margin-bottom: 14px;
}
.editor-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.editor-head p {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.editor-body {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.editor-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.form-label {
    display: block;
    margin-bottom: 6px;
    color: var(--ds-text-muted);
    font-size: 12px;
    font-weight: 700;
}
.field-hint {
    display: block;
    margin-top: 5px;
    font-size: 11px;
    line-height: 1.4;
}
.icon-field {
    min-width: 0;
}
.file-input-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.drop-box {
    min-height: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 12px;
    border: 1px dashed var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    text-align: center;
    cursor: pointer;
}
.drop-box:hover,
.drop-box.dragging {
    border-color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.08);
}
.drop-box .mi {
    color: var(--ds-primary);
    font-size: 23px;
}
.drop-box strong {
    color: var(--ds-text-emphasis);
    font-size: 13px;
}
.drop-box small {
    max-width: 100%;
    overflow: hidden;
    color: var(--ds-text-muted);
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.source-checklist {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px;
    border: 1px solid var(--ds-border);
    border-radius: 9px;
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
    font-size: 12px;
}
.source-checklist strong {
    color: var(--ds-text-emphasis);
}
.source-checklist code {
    overflow: auto;
    padding: 6px 8px;
    border-radius: 5px;
    background: var(--ds-body-bg);
    white-space: nowrap;
}
.icon-action {
    width: 36px;
    height: 36px;
    min-width: 36px;
    display: inline-grid;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
    cursor: pointer;
}
.icon-action:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.35);
    color: var(--ds-text-emphasis);
}
@media (max-width: 760px) {
    .editor-grid {
        grid-template-columns: 1fr;
    }
    .search-input {
        width: min(280px, 100%);
    }
    .info-note {
        align-items: flex-start;
    }
}
</style>
