<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Nhóm option ẩn</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span class="sep">/</span>
                    <span class="current">Nhóm option ẩn</span>
                </nav>
            </div>
            <button class="btn btn-primary admin-fab" type="button" @click="openCreate">
                <span class="mi" style="font-size: 16px">add</span>
                Tạo group
            </button>
        </div>

        <div class="info-note hidden-option-info">
            <span class="mi">info</span>
            <span>
                Tạo pool option ở đây, sau đó dùng <strong>option 210</strong> trên item và nhập
                <strong>group ID</strong> vào param. Khi mặc item, game sẽ random
                <strong>roll_count</strong> dòng trong pool; sau khi lưu cần restart game để nạp lại cấu hình.
            </span>
        </div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="filters.search"
                        class="form-input search-input"
                        placeholder="Tìm ID hoặc tên group..."
                        @input="debouncedLoadPage"
                    />
                </div>
                <select v-model="filters.active" class="form-input status-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Đang bật</option>
                    <option value="0">Đang tắt</option>
                </select>
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
                <table class="hidden-groups-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên group</th>
                            <th>Roll</th>
                            <th>Pool option</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="group in groups" :key="group.id">
                            <td class="id-cell">#{{ group.id }}</td>
                            <td>
                                <strong>{{ group.name }}</strong>
                                <small class="sub-line">Marker trên item: option 210 + param {{ group.id }}</small>
                            </td>
                            <td>
                                <span class="roll-badge">{{ group.roll_count }} / {{ group.option_count }}</span>
                                <small class="sub-line">dòng random / pool</small>
                            </td>
                            <td>
                                <div class="option-preview">
                                    <span
                                        v-for="option in group.options.slice(0, 6)"
                                        :key="option.id"
                                        class="option-pill"
                                    >
                                        {{ optionLabel(option.option_id, option.param_min, option.param_max, option.option_name) }}
                                    </span>
                                    <span v-if="group.options.length > 6" class="more-options">
                                        +{{ group.options.length - 6 }} dòng
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="group.is_active ? 'badge-success' : 'badge-muted'">
                                    {{ group.is_active ? "Đang bật" : "Đang tắt" }}
                                </span>
                            </td>
                            <td class="action-cell">
                                <button class="btn btn-primary btn-sm" type="button" @click="openEdit(group.id)">
                                    <span class="mi" style="font-size: 14px">edit</span>
                                    Sửa
                                </button>
                                <button
                                    class="btn btn-outline btn-sm"
                                    type="button"
                                    :disabled="copyingId === group.id"
                                    @click="copyGroup(group)"
                                >
                                    <span class="mi" style="font-size: 14px">content_copy</span>
                                    {{ copyingId === group.id ? "Đang sao chép..." : "Sao chép" }}
                                </button>
                                <button class="btn btn-danger btn-sm" type="button" @click="deleteGroup(group)">
                                    <span class="mi" style="font-size: 14px">delete</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="6">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-else-if="!groups.length">
                            <td colspan="6" class="empty-cell">Chưa có group option ẩn.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="loadPage(page - 1)">&laquo;</button>
                <template v-for="item in paginationItems" :key="String(item)">
                    <span v-if="typeof item !== 'number'" class="pagination-ellipsis">...</span>
                    <button v-else :class="{ active: item === page }" @click="loadPage(item)">
                        {{ item }}
                    </button>
                </template>
                <button :disabled="page >= totalPages" @click="loadPage(page + 1)">&raquo;</button>
            </div>
        </div>

        <div v-if="editor.open" class="editor-overlay" @click.self="closeEditor">
            <div class="editor-panel hidden-option-editor">
                <div class="editor-head">
                    <div>
                        <h3>{{ editor.isEdit ? "Sửa group option ẩn" : "Tạo group option ẩn" }}</h3>
                        <p>Chọn các option có thể xuất hiện và nhập param riêng cho từng dòng.</p>
                    </div>
                    <button class="icon-action" type="button" @click="closeEditor">
                        <span class="mi">close</span>
                    </button>
                </div>

                <div v-if="editor.error" class="alert alert-error">{{ editor.error }}</div>

                <div class="editor-body">
                    <div class="editor-grid hidden-option-fields">
                        <label>
                            <span class="form-label">Tên group</span>
                            <input
                                v-model.trim="editor.form.name"
                                class="form-input"
                                maxlength="255"
                                placeholder="Ví dụ: Chỉ số ẩn cải trang"
                            />
                        </label>
                        <label>
                            <span class="form-label">Roll count</span>
                            <input
                                v-model.number="editor.form.roll_count"
                                class="form-input"
                                type="number"
                                min="1"
                                max="255"
                            />
                            <small class="field-hint">Số dòng lấy ngẫu nhiên từ pool.</small>
                        </label>
                        <div class="active-field">
                            <span class="form-label">Trạng thái</span>
                            <label class="switch-row">
                                <input v-model="editor.form.is_active" type="checkbox" />
                                <span>{{ editor.form.is_active ? "Đang bật" : "Đang tắt" }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="group-usage-note">
                        <span class="mi">auto_awesome</span>
                        <span>
                            Sau khi lưu, thêm <code>option 210</code> vào item và đặt param bằng
                            <code>{{ editor.id || "GROUP_ID" }}</code>.
                        </span>
                    </div>

                    <div class="pool-header">
                        <div>
                            <h4>Pool option</h4>
                            <small>Random {{ editor.form.roll_count || 0 }} dòng trong {{ editor.form.options.length }} dòng đang cấu hình.</small>
                        </div>
                        <span
                            class="pool-count"
                            :class="{ invalid: editor.form.options.length < Number(editor.form.roll_count || 0) }"
                        >
                            {{ editor.form.options.length }} option
                        </span>
                    </div>

                    <div v-if="editor.form.options.length < Number(editor.form.roll_count || 0)" class="pool-warning">
                        <span class="mi">warning</span>
                        Pool phải có ít nhất {{ editor.form.roll_count || 0 }} option mới random được.
                    </div>

                    <div class="option-rows">
                        <div v-for="(option, index) in editor.form.options" :key="option.key" class="option-row">
                            <span class="option-row-index">{{ index + 1 }}</span>
                            <div class="option-select-wrap">
                                <label class="form-label">Option</label>
                                <input
                                    v-model="option.query"
                                    class="form-input"
                                    placeholder="Tìm tên hoặc ID option..."
                                    @focus="openOptionPicker(option, $event)"
                                    @click.stop="openOptionPicker(option, $event)"
                                />
                                <div
                                    v-if="option.open"
                                    class="option-dropdown"
                                    :class="{ 'option-dropdown--up': option.direction === 'up' }"
                                    @click.stop
                                >
                                    <button
                                        v-for="candidate in optionCandidates(option)"
                                        :key="candidate.id"
                                        type="button"
                                        class="option-dropdown-item"
                                        @click="selectOption(option, candidate)"
                                    >
                                        <span>{{ candidate.name }}</span>
                                        <small>#{{ candidate.id }}</small>
                                    </button>
                                    <div v-if="!optionCandidates(option).length" class="no-options">
                                        Không tìm thấy option phù hợp
                                    </div>
                                </div>
                            </div>
                            <div class="option-param-fields">
                                <label class="option-param-field">
                                    <span class="form-label">Param min</span>
                                    <input
                                        v-model.number="option.param_min"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                        max="2147483647"
                                        step="1"
                                    />
                                </label>
                                <label class="option-param-field">
                                    <span class="form-label">Param max <small>(tuỳ chọn)</small></span>
                                    <input
                                        v-model.number="option.param_max"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                        max="2147483647"
                                        step="1"
                                        placeholder="Bỏ trống = cố định"
                                    />
                                </label>
                            </div>
                            <div class="option-line-preview">
                                <span class="form-label">Hiển thị</span>
                                <strong>{{ selectedOptionLabel(option) }}</strong>
                            </div>
                            <button
                                class="icon-action option-remove"
                                type="button"
                                title="Xóa dòng"
                                @click="removeOption(index)"
                            >
                                <span class="mi">delete</span>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-outline add-option-button" type="button" @click="addOption">
                        <span class="mi">add</span>
                        Thêm dòng option
                    </button>
                </div>

                <div class="editor-actions">
                    <button class="btn btn-outline" type="button" :disabled="editor.saving" @click="closeEditor">
                        Hủy
                    </button>
                    <button class="btn btn-primary" type="button" :disabled="editor.saving" @click="saveEditor">
                        <span class="mi" style="font-size: 16px">save</span>
                        {{ editor.saving ? "Đang lưu..." : "Lưu group" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from "vue";
import { readJsonResponse } from "../../shared/api";
import { buildPaginationItems, csrfToken } from "../../shared/format";

interface OptionTemplate {
    id: number;
    name: string;
}

interface GroupOption {
    id: number;
    option_id: number;
    param: number;
    param_min: number;
    param_max: number | null;
    sort_order: number;
    option_name: string;
}

interface HiddenOptionGroup {
    id: number;
    name: string;
    roll_count: number;
    is_active: boolean;
    option_count: number;
    options: GroupOption[];
}

interface EditableOption {
    key: string;
    id: number | null;
    param_min: number | string;
    param_max: number | string | null;
    query: string;
    open: boolean;
    direction: "up" | "down";
}

interface EditorForm {
    name: string;
    roll_count: number;
    is_active: boolean;
    options: EditableOption[];
}

interface EditorState {
    open: boolean;
    isEdit: boolean;
    id: number | null;
    saving: boolean;
    error: string;
    form: EditorForm;
}

interface AdminWindow extends Window {
    adminConfirm?: (options: Record<string, string>) => Promise<boolean>;
}

const groups = ref<HiddenOptionGroup[]>([]);
const allOptions = ref<OptionTemplate[]>([]);
const filters = reactive({ search: "", active: "" });
const loading = ref(false);
const error = ref("");
const success = ref("");
const page = ref(1);
const totalPages = ref(1);
const searchTimer = ref<number | null>(null);
const copyingId = ref<number | null>(null);
let optionKey = 0;
const MAX_PARAM = 2147483647;

const emptyForm = (): EditorForm => ({
    name: "",
    roll_count: 1,
    is_active: true,
    options: [newEditableOption()],
});

const editor = reactive<EditorState>({
    open: false,
    isEdit: false,
    id: null,
    saving: false,
    error: "",
    form: emptyForm(),
});

const paginationItems = computed(() => buildPaginationItems(page.value, totalPages.value));

function newEditableOption(option?: Partial<EditableOption>): EditableOption {
    optionKey += 1;
    return {
        key: `hidden-option-${Date.now()}-${optionKey}`,
        id: option?.id ?? null,
        param_min: option?.param_min ?? 0,
        param_max: option?.param_max ?? null,
        query: option?.query ?? "",
        open: false,
        direction: "down",
    };
}

function resetEditor(): void {
    editor.open = false;
    editor.isEdit = false;
    editor.id = null;
    editor.saving = false;
    editor.error = "";
    editor.form = emptyForm();
}

function toBound(value: unknown): number | null {
    return parseBound(value).value;
}

function parseBound(value: unknown): { value: number | null; invalid: boolean } {
    if (value === null || value === undefined || value === "") {
        return { value: null, invalid: false };
    }
    const number = Number(value);
    return Number.isInteger(number)
        ? { value: number, invalid: false }
        : { value: null, invalid: true };
}

function optionLabel(
    id: number,
    paramMin: number | string | null,
    paramMax: number | string | null,
    name = "",
): string {
    const option = allOptions.value.find((item) => Number(item.id) === Number(id));
    const templateName = name || option?.name || `Option ${id}`;
    const min = toBound(paramMin);
    const max = toBound(paramMax);
    const param = min === null
        ? "?"
        : max !== null && max !== min
          ? `${min}-${max}`
          : String(min);
    return templateName.includes("#")
        ? templateName.replace("#", String(param))
        : `${templateName}: ${param}`;
}

function selectedOptionLabel(option: EditableOption): string {
    if (option.id === null) return "Chưa chọn option";
    return optionLabel(option.id, option.param_min, option.param_max);
}

function optionCandidates(current: EditableOption): OptionTemplate[] {
    const query = current.query.trim().toLowerCase();
    const usedIds = new Set(
        editor.form.options
            .filter((option) => option.key !== current.key && option.id !== null)
            .map((option) => Number(option.id)),
    );

    return allOptions.value
        .filter((option) => Number(option.id) !== 210 && (!usedIds.has(Number(option.id)) || current.id === option.id))
        .filter((option) => !query || option.name.toLowerCase().includes(query) || String(option.id).includes(query))
        .sort((left, right) => Number(left.id) - Number(right.id));
}

function openOptionPicker(option: EditableOption, event?: FocusEvent | MouseEvent): void {
    editor.form.options.forEach((item) => {
        if (item.key !== option.key) item.open = false;
    });

    const target = event?.currentTarget as HTMLElement | null;
    if (target) {
        const rect = target.getBoundingClientRect();
        const dropdownHeight = 290;
        option.direction = window.innerHeight - rect.bottom < dropdownHeight && rect.top > dropdownHeight
            ? "up"
            : "down";
    }
    option.open = true;
}

function selectOption(option: EditableOption, selected: OptionTemplate): void {
    option.id = Number(selected.id);
    option.query = `${selected.name} (ID: ${selected.id})`;
    option.open = false;
}

function addOption(): void {
    editor.form.options.push(newEditableOption());
}

function removeOption(index: number): void {
    editor.form.options.splice(index, 1);
}

function closeEditor(): void {
    if (!editor.saving) editor.open = false;
}

const debouncedLoadPage = (): void => {
    if (searchTimer.value !== null) window.clearTimeout(searchTimer.value);
    searchTimer.value = window.setTimeout(() => loadPage(1), 300);
};

async function loadOptions(): Promise<void> {
    try {
        const response = await fetch("/admin/api/options", {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tải danh sách option");
        const options = Array.isArray(data) ? data : data.data || data.options || [];
        allOptions.value = options.map((option: OptionTemplate) => ({
            id: Number(option.id),
            name: String(option.name || `Option ${option.id}`),
        }));
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : "Không thể tải danh sách option";
    }
}

async function loadPage(nextPage = 1): Promise<void> {
    loading.value = true;
    error.value = "";
    try {
        const params = new URLSearchParams({ page: String(Math.max(1, nextPage)), per_page: "20" });
        if (filters.search.trim()) params.set("search", filters.search.trim());
        if (filters.active !== "") params.set("active", filters.active);
        const response = await fetch(`/admin/api/hidden-option-groups?${params.toString()}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tải group option ẩn");
        groups.value = data.data || [];
        page.value = Number(data.page || 1);
        totalPages.value = Number(data.total_pages || 1);
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : "Không thể tải group option ẩn";
        groups.value = [];
    } finally {
        loading.value = false;
    }
}

function openCreate(): void {
    resetEditor();
    editor.open = true;
}

async function openEdit(id: number): Promise<void> {
    resetEditor();
    editor.open = true;
    editor.isEdit = true;
    editor.id = id;
    try {
        const response = await fetch(`/admin/api/hidden-option-groups/${id}`, {
            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        const data = await readJsonResponse(response, "Không thể tải group option ẩn");
        const group = data.data as HiddenOptionGroup;
        editor.form = {
            name: group.name || "",
            roll_count: Number(group.roll_count || 1),
            is_active: Boolean(group.is_active),
            options: (group.options || []).map((option) => {
                const template = allOptions.value.find((item) => item.id === Number(option.option_id));
                return newEditableOption({
                    id: Number(option.option_id),
                    param_min: Number(option.param_min ?? option.param ?? 0),
                    param_max: option.param_max === null || option.param_max === undefined
                        ? null
                        : Number(option.param_max),
                    query: template ? `${template.name} (ID: ${template.id})` : `Option ${option.option_id}`,
                });
            }),
        };
    } catch (exception) {
        editor.error = exception instanceof Error ? exception.message : "Không thể tải group option ẩn";
    }
}

async function saveEditor(): Promise<void> {
    const rollCount = Number(editor.form.roll_count);
    const selectedIds = editor.form.options.map((option) => option.id);
    const normalizedOptions = editor.form.options.map((option) => ({
        ...option,
        parsedMin: parseBound(option.param_min),
        parsedMax: parseBound(option.param_max),
    }));
    if (!editor.form.name.trim()) {
        editor.error = "Tên group không được để trống.";
        return;
    }
    if (!Number.isInteger(rollCount) || rollCount < 1 || rollCount > 255) {
        editor.error = "Roll count phải nằm trong khoảng 1 đến 255.";
        return;
    }
    if (editor.form.options.length < rollCount) {
        editor.error = `Pool phải có ít nhất ${rollCount} option.`;
        return;
    }
    if (selectedIds.some((id) => id === null)) {
        editor.error = "Vui lòng chọn option cho tất cả các dòng.";
        return;
    }
    if (new Set(selectedIds).size !== selectedIds.length) {
        editor.error = "Không được chọn trùng option trong cùng một pool.";
        return;
    }
    for (const [index, option] of normalizedOptions.entries()) {
        if (option.parsedMin.invalid || option.parsedMin.value === null) {
            editor.error = `Dòng option ${index + 1}: Param min là bắt buộc.`;
            return;
        }
        if (option.parsedMin.value < 0 || option.parsedMin.value > MAX_PARAM) {
            editor.error = `Dòng option ${index + 1}: Param min phải từ 0 đến ${MAX_PARAM}.`;
            return;
        }
        if (option.parsedMax.invalid) {
            editor.error = `Dòng option ${index + 1}: Param max phải là số nguyên hoặc để trống.`;
            return;
        }
        if (option.parsedMax.value !== null && (option.parsedMax.value < 0 || option.parsedMax.value > MAX_PARAM)) {
            editor.error = `Dòng option ${index + 1}: Param max phải từ 0 đến ${MAX_PARAM}.`;
            return;
        }
        if (option.parsedMax.value !== null && option.parsedMax.value < option.parsedMin.value) {
            editor.error = `Dòng option ${index + 1}: Param max phải lớn hơn hoặc bằng Param min.`;
            return;
        }
    }

    editor.saving = true;
    editor.error = "";
    try {
        const payload = {
            name: editor.form.name.trim(),
            roll_count: rollCount,
            is_active: editor.form.is_active,
            options: normalizedOptions.map((option, index) => ({
                id: Number(option.id),
                param_min: option.parsedMin.value as number,
                param_max: option.parsedMax.value,
                sort_order: index,
            })),
        };
        const url = editor.isEdit
            ? `/admin/api/hidden-option-groups/${editor.id}`
            : "/admin/api/hidden-option-groups";
        const response = await fetch(url, {
            method: editor.isEdit ? "PUT" : "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken() || "",
                Accept: "application/json",
                "Content-Type": "application/json",
            },
            body: JSON.stringify(payload),
        });
        const data = await readJsonResponse(response, "Không thể lưu group option ẩn");
        success.value = data.message || "Đã lưu group option ẩn";
        closeEditor();
        await loadPage(page.value);
    } catch (exception) {
        editor.error = exception instanceof Error ? exception.message : "Không thể lưu group option ẩn";
    } finally {
        editor.saving = false;
    }
}

async function deleteGroup(group: HiddenOptionGroup): Promise<void> {
    const confirm = (window as AdminWindow).adminConfirm;
    const ok = confirm
        ? await confirm({
              title: "Xóa group option ẩn",
              message: `Xóa group #${group.id} (${group.name})? Các dòng option trong pool cũng sẽ bị xóa.`,
              tone: "danger",
              confirmText: "Xóa",
          })
        : window.confirm(`Xóa group #${group.id}?`);
    if (!ok) return;

    try {
        const response = await fetch(`/admin/api/hidden-option-groups/${group.id}`, {
            method: "DELETE",
            headers: { "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrfToken() || "" },
        });
        const data = await readJsonResponse(response, "Không thể xóa group option ẩn");
        success.value = data.message || "Đã xóa group option ẩn";
        await loadPage(page.value);
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : "Không thể xóa group option ẩn";
    }
}

async function copyGroup(group: HiddenOptionGroup): Promise<void> {
    const confirm = (window as AdminWindow).adminConfirm;
    const ok = confirm
        ? await confirm({
              title: "Sao chép group option ẩn",
              message: `Tạo một group mới từ toàn bộ cấu hình của group #${group.id} (${group.name})?`,
              confirmText: "Sao chép",
          })
        : window.confirm(`Sao chép group #${group.id}?`);
    if (!ok) return;

    copyingId.value = group.id;
    error.value = "";
    try {
        const response = await fetch(`/admin/api/hidden-option-groups/${group.id}/copy`, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken() || "",
                Accept: "application/json",
            },
        });
        const data = await readJsonResponse(response, "Không thể sao chép group option ẩn");
        success.value = data.message || "Đã sao chép group option ẩn";
        await loadPage(1);
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : "Không thể sao chép group option ẩn";
    } finally {
        copyingId.value = null;
    }
}

function closeDropdowns(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    if (target.closest(".option-select-wrap")) return;
    editor.form.options.forEach((option) => {
        option.open = false;
    });
}

onMounted(() => {
    loadOptions();
    loadPage(1);
    document.addEventListener("click", closeDropdowns);
});

onUnmounted(() => {
    document.removeEventListener("click", closeDropdowns);
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
    margin: 0 0 4px;
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
    color: var(--ds-text-muted);
    font-size: 13px;
}

.breadcrumb a,
.breadcrumb .sep {
    color: var(--ds-primary);
}

.filter-bar {
    margin-bottom: 20px;
}

.search-input-wrap {
    position: relative;
    min-width: 240px;
    flex: 1 1 280px;
}

.search-icon {
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    font-size: 18px;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding-left: 38px !important;
}

.status-filter {
    width: 190px;
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
.group-usage-note code {
    color: var(--ds-primary);
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
}

.hidden-option-info {
    align-items: flex-start;
}

.hidden-option-info .mi {
    margin-top: 1px;
}

.hidden-groups-table {
    min-width: 980px;
}

.id-cell {
    color: var(--muted-foreground);
    white-space: nowrap;
}

.sub-line {
    display: block;
    margin-top: 3px;
    color: var(--muted-foreground);
    font-size: 11px;
}

.roll-badge,
.pool-count {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.25);
    border-radius: 6px;
    background: rgba(var(--ds-primary-rgb), 0.08);
    color: var(--ds-primary);
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.option-preview {
    display: flex;
    max-width: 440px;
    flex-wrap: wrap;
    gap: 5px;
}

.option-pill {
    display: inline-flex;
    max-width: 210px;
    overflow: hidden;
    padding: 4px 7px;
    border: 1px solid var(--ds-border);
    border-radius: 6px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.more-options {
    align-self: center;
    color: var(--muted-foreground);
    font-size: 11px;
}

.badge-muted {
    background: var(--ds-surface-2);
    color: var(--muted-foreground);
}

.hidden-option-editor {
    width: min(100%, 1040px);
    container-type: inline-size;
}

.editor-overlay {
    position: fixed;
    inset: 0;
    z-index: 3000;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow-x: hidden;
    overflow-y: auto;
    padding: 24px;
    background: var(--ds-overlay-bg);
    backdrop-filter: blur(4px);
}

.editor-panel {
    width: min(1040px, calc(100vw - 48px));
    max-height: calc(100vh - 48px);
    overflow: auto;
    padding: 18px;
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-xl);
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

.editor-actions {
    justify-content: flex-end;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid var(--ds-border);
}

.hidden-option-editor .icon-action {
    display: inline-grid;
    width: 34px;
    height: 34px;
    place-items: center;
    flex: 0 0 auto;
    border: 1px solid var(--ds-border);
    border-radius: 7px;
    background: transparent;
    color: var(--ds-text-muted);
    cursor: pointer;
}

.hidden-option-editor .icon-action:hover {
    background: var(--ds-surface-2);
    color: var(--ds-text);
}

.hidden-option-fields {
    grid-template-columns: minmax(260px, 1.5fr) 160px 180px;
}

.active-field {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.switch-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    color: var(--ds-text);
    font-size: 13px;
}

.group-usage-note,
.pool-warning {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 18px;
    padding: 10px 12px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.2);
    border-radius: 7px;
    background: rgba(var(--ds-primary-rgb), 0.06);
    color: var(--ds-text-muted);
    font-size: 12px;
    line-height: 1.5;
}

.group-usage-note .mi {
    color: var(--ds-primary);
}

.pool-warning {
    margin-top: 0;
    border-color: rgba(var(--ds-warning-rgb), 0.3);
    background: rgba(var(--ds-warning-rgb), 0.08);
    color: var(--ds-warning);
}

.pool-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 24px;
    margin-bottom: 10px;
}

.pool-header h4 {
    margin: 0 0 3px;
    color: var(--ds-text-emphasis);
    font-size: 15px;
}

.pool-header small {
    color: var(--muted-foreground);
}

.pool-count.invalid {
    border-color: rgba(var(--ds-warning-rgb), 0.35);
    background: rgba(var(--ds-warning-rgb), 0.1);
    color: var(--ds-warning);
}

.option-rows {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.option-row {
    display: grid;
    grid-template-columns: 30px minmax(0, 1.25fr) minmax(190px, 1fr) minmax(140px, 0.8fr) 34px;
    align-items: end;
    gap: 10px;
    min-width: 0;
    padding: 10px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
}

.option-row > *,
.option-select-wrap,
.option-param-fields,
.option-line-preview {
    min-width: 0;
}

.option-row-index {
    display: grid;
    width: 28px;
    height: 28px;
    place-items: center;
    margin-bottom: 5px;
    border-radius: 50%;
    background: rgba(var(--ds-primary-rgb), 0.1);
    color: var(--ds-primary);
    font-size: 12px;
    font-weight: 700;
}

.option-select-wrap {
    position: relative;
}

.option-select-wrap .form-input {
    width: 100%;
}

.option-param-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.option-param-field,
.option-line-preview {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
}

.option-line-preview strong {
    min-height: 38px;
    overflow: hidden;
    padding: 10px 10px;
    color: var(--ds-text);
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.option-param-field small {
    color: var(--ds-text-muted);
    font-size: 10px;
    font-weight: 400;
}

.option-remove {
    margin-bottom: 5px;
    color: var(--destructive);
}

.option-dropdown {
    position: absolute;
    z-index: 60;
    top: calc(100% + 5px);
    right: 0;
    left: 0;
    max-height: 260px;
    overflow-y: auto;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-xl);
}

.option-dropdown--up {
    top: auto;
    bottom: calc(100% + 5px);
}

.option-dropdown-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 10px;
    padding: 9px 10px;
    border: 0;
    border-bottom: 1px solid var(--ds-border);
    background: transparent;
    color: var(--ds-text);
    text-align: left;
    cursor: pointer;
}

.option-dropdown-item:hover {
    background: rgba(var(--ds-primary-rgb), 0.08);
}

.option-dropdown-item small {
    color: var(--muted-foreground);
    white-space: nowrap;
}

.no-options {
    padding: 12px;
    color: var(--muted-foreground);
    font-size: 12px;
}

.add-option-button {
    margin-top: 12px;
}

@media (max-width: 900px) {
    .editor-overlay {
        padding: 18px;
    }

    .editor-panel {
        width: min(100%, calc(100vw - 36px));
        max-height: calc(100vh - 36px);
    }

    .hidden-option-fields {
        grid-template-columns: 1fr 1fr;
    }

    .active-field {
        grid-column: 1 / -1;
    }

    .option-row {
        grid-template-columns: 30px minmax(0, 1fr) 34px;
        grid-template-areas:
            "index option remove"
            ". params ."
            ". preview .";
    }

    .option-row-index {
        grid-area: index;
    }

    .option-select-wrap {
        grid-area: option;
    }

    .option-param-fields {
        grid-area: params;
    }

    .option-line-preview {
        grid-area: preview;
    }

    .option-remove {
        grid-area: remove;
    }
}

@media (max-width: 620px) {
    .editor-overlay {
        padding: 10px;
    }

    .editor-panel {
        width: 100%;
        max-height: calc(100vh - 20px);
        padding: 14px;
    }

    .hidden-option-fields,
    .option-row {
        grid-template-columns: 1fr;
    }

    .option-row {
        grid-template-areas:
            "index"
            "option"
            "params"
            "preview"
            "remove";
    }

    .option-row-index {
        margin-bottom: 0;
    }

    .option-line-preview {
        grid-column: auto;
    }

    .option-param-fields {
        grid-template-columns: 1fr 1fr;
    }

    .option-remove {
        grid-area: remove;
        justify-self: start;
        margin: 0;
    }
}

@container (max-width: 900px) {
    .option-row {
        grid-template-columns: 30px minmax(0, 1fr) 34px;
        grid-template-areas:
            "index option remove"
            ". params ."
            ". preview .";
    }

    .option-row-index {
        grid-area: index;
    }

    .option-select-wrap {
        grid-area: option;
    }

    .option-param-fields {
        grid-area: params;
    }

    .option-line-preview {
        grid-area: preview;
    }

    .option-remove {
        grid-area: remove;
    }
}

@container (max-width: 620px) {
    .option-row {
        grid-template-columns: 1fr;
        grid-template-areas:
            "index"
            "option"
            "params"
            "preview"
            "remove";
    }
}
</style>
