<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Items</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">
                        Trang chủ
                    </router-link>
                    <span class="sep">/</span>
                    <span class="current">Items</span>
                </nav>
            </div>
            <span v-if="total" class="total-count">
                {{ total.toLocaleString("vi-VN") }} items
            </span>
        </div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm ID hoặc tên..."
                        @input="debouncedLoadPage"
                    />
                </div>
                <div ref="typeFilterControl" class="type-filter-control">
                    <button
                        type="button"
                        class="form-input type-filter-trigger"
                        :aria-expanded="typeFilterOpen"
                        aria-haspopup="listbox"
                        @click="typeFilterOpen = !typeFilterOpen"
                    >
                        <span>{{ typeFilterSummary }}</span>
                        <span class="mi">expand_more</span>
                    </button>
                    <div
                        v-if="typeFilterOpen"
                        class="type-filter-menu"
                        role="listbox"
                        aria-label="Lọc theo type"
                        aria-multiselectable="true"
                        @click.stop
                    >
                        <label class="type-filter-option type-filter-option--all">
                            <input
                                type="checkbox"
                                :checked="typeFilter.length === 0"
                                @change="clearTypeFilter"
                            />
                            <span>Tất cả type</span>
                        </label>
                        <label
                            v-for="t in displayTypeOptions"
                            :key="'type-opt-' + t.id"
                            class="type-filter-option"
                        >
                            <input
                                v-model="typeFilter"
                                type="checkbox"
                                :value="String(t.id)"
                                @change="loadPage(1)"
                            />
                            <span>{{ t.name }} ({{ t.id }})</span>
                        </label>
                    </div>
                </div>
                <select
                    v-model="genderFilter"
                    class="form-input"
                    style="width: 180px"
                    @change="loadPage(1)"
                >
                    <option value="">Tất cả gender</option>
                    <option :value="'0'">Trái Đất (0)</option>
                    <option :value="'1'">Namek (1)</option>
                    <option :value="'2'">Xayda (2)</option>
                    <option :value="'3'">Chung/Tất cả (3)</option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">
                    <span class="mi" style="font-size: 16px">filter_list</span>
                    Lọc
                </button>
            </form>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>

        <div class="card">
            <div class="table-wrap">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Tên</th>
                            <th>Type</th>
                            <th>Mô tả</th>
                            <th>Gộp chung</th>
                            <th>Head</th>
                            <th>Body</th>
                            <th>Leg</th>
                            <th>Part</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="item in items" :key="item.id">
                            <tr>
                                <td>{{ item.id }}</td>
                                <td>
                                    <AdminIcon
                                        :icon-id="item.icon_id"
                                        :cache-bust="iconCacheBust"
                                        class="item-icon"
                                    />
                                </td>
                                <td>
                                    <div class="item-name">{{ item.name }}</div>
                                    <div class="item-meta">
                                        Icon {{ item.icon_id }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ itemTypeLabel(item.type) }}
                                    </span>
                                </td>
                                <td>
                                    <div
                                        class="description-cell"
                                        :title="item.description || ''"
                                    >
                                        {{ item.description || "—" }}
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="
                                            item.is_up_to_up
                                                ? 'badge-success'
                                                : 'badge-muted'
                                        "
                                    >
                                        {{ item.is_up_to_up ? "Có" : "Không" }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.head >= 0"
                                        class="part-chip"
                                        :title="partChipTitle(item.head)"
                                    >
                                        #{{ item.head }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.body >= 0"
                                        class="part-chip"
                                        :title="partChipTitle(item.body)"
                                    >
                                        #{{ item.body }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.leg >= 0"
                                        class="part-chip"
                                        :title="partChipTitle(item.leg)"
                                    >
                                        #{{ item.leg }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td>
                                    <span
                                        v-if="item.part >= 0"
                                        class="part-chip part-chip-main"
                                        :title="partChipTitle(item.part)"
                                    >
                                        #{{ item.part }}
                                    </span>
                                    <span v-else class="muted">—</span>
                                </td>
                                <td class="action-cell">
                                    <button
                                        class="btn btn-primary btn-sm"
                                        title="Sửa item"
                                        @click="openEditor(item)"
                                    >
                                        <span
                                            class="mi"
                                            style="font-size: 14px"
                                            >edit</span
                                        >
                                        Sửa
                                    </button>
                                    <button
                                        class="btn btn-outline btn-sm"
                                        :title="expandedId === item.id ? 'Ẩn chi tiết' : 'Xem chi tiết'"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        <span class="mi" style="font-size: 14px">
                                            {{ expandedId === item.id ? "visibility_off" : "visibility" }}
                                        </span>
                                        {{
                                            expandedId === item.id
                                                ? "Ẩn"
                                                : "Xem"
                                        }}
                                    </button>
                                </td>
                            </tr>
                            <tr
                                v-if="expandedId === item.id"
                                class="detail-row"
                            >
                                <td colspan="11">
                                    <div class="detail-panel">
                                        <div class="detail-header">
                                            <div>
                                                <div class="detail-title">
                                                    {{ item.name }}
                                                </div>
                                                <div class="detail-subtitle">
                                                    `part.DATA` gồm các phần tử
                                                    `[icon_id, dx, dy]`
                                                </div>
                                            </div>
                                            <div class="detail-stats">
                                                <span class="detail-stat">
                                                    Head:
                                                    {{
                                                        item.head >= 0
                                                            ? item.head
                                                            : "—"
                                                    }}
                                                </span>
                                                <span class="detail-stat">
                                                    Body:
                                                    {{
                                                        item.body >= 0
                                                            ? item.body
                                                            : "—"
                                                    }}
                                                </span>
                                                <span class="detail-stat">
                                                    Leg:
                                                    {{
                                                        item.leg >= 0
                                                            ? item.leg
                                                            : "—"
                                                    }}
                                                </span>
                                                <span class="detail-stat">
                                                    Part:
                                                    {{
                                                        item.part >= 0
                                                            ? item.part
                                                            : "—"
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="part-grid">
                                            <div
                                                v-for="block in detailBlocks(
                                                    item,
                                                )"
                                                :key="item.id + '-' + block.key"
                                                class="part-card"
                                            >
                                                <div class="part-card-head">
                                                    <div
                                                        class="part-card-title"
                                                    >
                                                        {{ block.label }}
                                                    </div>
                                                    <div class="part-card-meta">
                                                        ID #{{ block.part.id }}
                                                        |
                                                        {{
                                                            partTypeLabel(
                                                                block.part,
                                                            )
                                                        }}
                                                    </div>
                                                </div>

                                                <div
                                                    v-if="
                                                        block.part.layers.length
                                                    "
                                                    class="layer-grid"
                                                >
                                                    <div
                                                        v-for="(
                                                            layer, layerIndex
                                                        ) in block.part.layers"
                                                        :key="
                                                            block.key +
                                                            '-' +
                                                            layerIndex
                                                        "
                                                        class="layer-card"
                                                    >
                                                        <AdminIcon
                                                            :icon-id="
                                                                layer.icon_id
                                                            "
                                                            class="layer-icon"
                                                        />
                                                        <div class="layer-info">
                                                            <div
                                                                class="layer-name"
                                                            >
                                                                Ảnh
                                                                {{
                                                                    layer.icon_id
                                                                }}
                                                            </div>
                                                            <div
                                                                class="layer-meta"
                                                            >
                                                                dx:
                                                                {{ layer.dx }}
                                                                | dy:
                                                                {{ layer.dy }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="layer-empty">
                                                    Không có dữ liệu layer.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="11">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!items.length && !loading">
                            <td colspan="11" class="empty-cell">
                                Không có item nào.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="goToPage(1)">Đầu</button>
                <button :disabled="page <= 1" @click="goToPage(page - 1)">
                    &laquo;
                </button>
                <template v-for="p in paginationItems" :key="String(p)">
                    <span
                        v-if="typeof p !== 'number'"
                        class="pagination-ellipsis"
                    >
                        ...
                    </span>
                    <button
                        v-else
                        :class="{ active: p === page }"
                        @click="goToPage(p)"
                    >
                        {{ p }}
                    </button>
                </template>
                <button
                    :disabled="page >= totalPages"
                    @click="goToPage(page + 1)"
                >
                    &raquo;
                </button>
                <button
                    :disabled="page >= totalPages"
                    @click="goToPage(totalPages)"
                >
                    Cuối
                </button>
                <div class="pagination-jump">
                    <span class="pagination-summary"
                        >Trang {{ page }} / {{ totalPages }}</span
                    >
                    <input
                        v-model="pageInput"
                        type="number"
                        min="1"
                        :max="totalPages"
                        class="form-input pagination-input"
                        @keyup.enter="jumpToPage"
                    />
                    <button @click="jumpToPage">Đi</button>
                </div>
            </div>
        </div>

        <div v-if="editor.open" class="editor-overlay" @click.self="closeEditor">
            <div class="editor-panel">
                <div class="editor-head">
                    <div>
                        <h3>Sửa item #{{ editor.form.id }}</h3>
                        <p>Chỉ cập nhật dòng item_template đang chọn.</p>
                    </div>
                    <button class="icon-action" type="button" @click="closeEditor">
                        <span class="mi">close</span>
                    </button>
                </div>

                <div v-if="editor.error" class="alert alert-error">
                    {{ editor.error }}
                </div>

                <div class="editor-body">
                    <div class="editor-preview">
                        <div class="editor-icon-wrap">
                            <img
                                v-if="editor.iconPreviewUrl"
                                :src="editor.iconPreviewUrl"
                                class="editor-icon-preview-img"
                                alt=""
                            />
                            <AdminIcon
                                v-else
                                :icon-id="editorNumber('icon_id')"
                                :cache-bust="iconCacheBust"
                                class="editor-icon"
                            />
                        </div>
                        <div class="editor-preview-copy">
                            <strong>{{ editor.form.name || "Chưa đặt tên" }}</strong>
                            <small>
                                Type {{ editor.form.type }} ·
                                {{ itemGenderLabel(editor.form.gender) }}
                            </small>
                        </div>
                    </div>

                    <input
                        ref="iconFileInput"
                        type="file"
                        accept="image/png"
                        class="hidden-file-input"
                        @change="handleIconFileChange"
                    />
                    <div class="icon-upload-grid">
                        <button
                            class="icon-upload-zone"
                            :class="{
                                'is-dragging': editor.iconDraggingMode === 'replace',
                                'is-selected': editor.iconFile && editor.iconUploadMode === 'replace',
                            }"
                            type="button"
                            :disabled="editor.saving"
                            @click="chooseIconFile('replace')"
                            @dragenter.prevent="handleIconDragEnter($event, 'replace')"
                            @dragover.prevent="handleIconDragOver($event, 'replace')"
                            @dragleave.prevent="handleIconDragLeave($event, 'replace')"
                            @drop.prevent="handleIconDrop($event, 'replace')"
                        >
                            <span class="mi icon-upload-zone__icon">image</span>
                            <span class="icon-upload-zone__copy">
                                <strong>Chèn lại ảnh</strong>
                                <small>Giữ icon #{{ editorNumber("icon_id") }}</small>
                                <span>Kéo PNG vào đây hoặc bấm để chọn</span>
                            </span>
                        </button>
                        <button
                            class="icon-upload-zone icon-upload-zone--split"
                            :class="{
                                'is-dragging': editor.iconDraggingMode === 'split',
                                'is-selected': editor.iconFile && editor.iconUploadMode === 'split',
                            }"
                            type="button"
                            :disabled="editor.saving"
                            @click="chooseIconFile('split')"
                            @dragenter.prevent="handleIconDragEnter($event, 'split')"
                            @dragover.prevent="handleIconDragOver($event, 'split')"
                            @dragleave.prevent="handleIconDragLeave($event, 'split')"
                            @drop.prevent="handleIconDrop($event, 'split')"
                        >
                            <span class="mi icon-upload-zone__icon">add_photo_alternate</span>
                            <span class="icon-upload-zone__copy">
                                <strong>Tạo ID mới</strong>
                                <small>Chỉ áp dụng cho item này</small>
                                <span>Kéo PNG vào đây hoặc bấm để chọn</span>
                            </span>
                        </button>
                    </div>
                    <div v-if="editor.iconFile" class="icon-upload-selection">
                        <small class="icon-file-name">
                            {{ editor.iconFile.name }}
                            <template v-if="editor.iconUploadMode === 'split'">
                                sẽ tạo icon ID mới và chỉ áp dụng cho item này.
                            </template>
                            <template v-else>
                                sẽ chèn lại ảnh cho icon #{{ editorNumber("icon_id") }}.
                            </template>
                        </small>
                        <button
                            class="btn btn-ghost btn-sm"
                            type="button"
                            :disabled="editor.saving"
                            @click="clearIconFile"
                        >
                            <span class="mi" style="font-size: 15px">close</span>
                            Bỏ ảnh
                        </button>
                    </div>

                    <div class="editor-grid">
                        <label>
                            <span class="form-label">Tên item</span>
                            <input v-model.trim="editor.form.name" class="form-input" />
                        </label>
                        <label>
                            <span class="form-label">Icon ID</span>
                            <input
                                v-model.number="editor.form.icon_id"
                                class="form-input"
                                type="number"
                                min="0"
                            />
                        </label>
                        <label>
                            <span class="form-label">Type</span>
                            <input
                                v-model.number="editor.form.type"
                                class="form-input"
                                type="number"
                            />
                        </label>
                        <label>
                            <span class="form-label">Hệ/Gender</span>
                            <select v-model.number="editor.form.gender" class="form-input">
                                <option :value="0">Trái Đất (0)</option>
                                <option :value="1">Namek (1)</option>
                                <option :value="2">Xayda (2)</option>
                                <option :value="3">Chung/Tất cả (3)</option>
                            </select>
                        </label>
                        <label>
                            <span class="form-label">Part chính</span>
                            <input v-model.number="editor.form.part" class="form-input" type="number" min="-1" />
                        </label>
                        <label>
                            <span class="form-label">Head</span>
                            <input v-model.number="editor.form.head" class="form-input" type="number" min="-1" />
                        </label>
                        <label>
                            <span class="form-label">Body</span>
                            <input v-model.number="editor.form.body" class="form-input" type="number" min="-1" />
                        </label>
                        <label>
                            <span class="form-label">Leg</span>
                            <input v-model.number="editor.form.leg" class="form-input" type="number" min="-1" />
                        </label>
                    </div>

                    <label class="editor-description">
                        <span class="form-label">Mô tả</span>
                        <textarea
                            v-model="editor.form.description"
                            class="form-input"
                            rows="4"
                        ></textarea>
                    </label>

                    <label class="editor-check">
                        <input v-model="editor.form.is_up_to_up" type="checkbox" />
                        <span>Cho phép gộp/nâng cấp chung</span>
                    </label>

                    <div class="editor-note">
                        Đặt Part/Head/Body/Leg là -1 nếu item không dùng phần
                        hiển thị đó.
                    </div>
                </div>

                <div class="editor-actions">
                    <button class="btn btn-outline" type="button" :disabled="editor.saving" @click="closeEditor">
                        Hủy
                    </button>
                    <button class="btn btn-primary" type="button" :disabled="editor.saving" @click="saveEditor">
                        <span class="mi" style="font-size: 16px">save</span>
                        {{ editor.saving ? "Đang lưu..." : "Lưu item" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { buildPaginationItems, csrfToken } from "../../shared/format";
import { readJsonResponse } from "../../shared/api";
export default {
    data() {
        return {
            items: [],
            partMap: {},
            types: [],
            typeOptions: [],
            search: "",
            typeFilter: [],
            typeFilterOpen: false,
            genderFilter: "",
            loading: false,
            page: 1,
            pageInput: "1",
            totalPages: 1,
            total: 0,
            expandedId: null,
            iconCacheBust: Date.now(),
            searchTimer: null,
            error: "",
            editor: {
                open: false,
                saving: false,
                error: "",
                form: {},
                iconFile: null,
                iconPreviewUrl: "",
                iconDraggingMode: "",
                iconUploadMode: "split",
                pendingIconUploadMode: "split",
            },
        };
    },
    computed: {
        paginationItems() {
            return buildPaginationItems(this.page, this.totalPages);
        },
        displayTypeOptions() {
            if (Array.isArray(this.typeOptions) && this.typeOptions.length) {
                return this.typeOptions;
            }
            return (this.types || []).map((id) => ({
                id: Number(id),
                name: `Type ${id}`,
            }));
        },
        typeFilterSummary() {
            if (!this.typeFilter.length) return "Tất cả type";
            if (this.typeFilter.length === 1) {
                const selected = this.displayTypeOptions.find(
                    (option) => String(option.id) === String(this.typeFilter[0]),
                );
                return selected
                    ? `${selected.name} (${selected.id})`
                    : "1 type đã chọn";
            }
            return `${this.typeFilter.length} type đã chọn`;
        },
    },
    created() {
        this.loadPage(1);
    },
    mounted() {
        document.addEventListener("click", this.handleTypeFilterOutside);
    },
    unmounted() {
        document.removeEventListener("click", this.handleTypeFilterOutside);
        window.clearTimeout(this.searchTimer);
        this.revokeIconPreview();
    },
    methods: {
        debouncedLoadPage() {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = window.setTimeout(() => {
                this.loadPage(1);
            }, 300);
        },
        clearTypeFilter() {
            this.typeFilter = [];
            this.loadPage(1);
        },
        handleTypeFilterOutside(event) {
            if (!this.$refs.typeFilterControl?.contains(event.target)) {
                this.typeFilterOpen = false;
            }
        },
        normalizePage(page) {
            const value = Number(page);
            if (!Number.isFinite(value)) return 1;
            return Math.min(
                Math.max(1, Math.trunc(value)),
                this.totalPages || 1,
            );
        },
        goToPage(page) {
            const target = this.normalizePage(page);
            if (target === this.page && this.items.length) {
                this.pageInput = String(target);
                return;
            }
            this.loadPage(target);
        },
        jumpToPage() {
            this.goToPage(this.pageInput);
        },
        toggleExpanded(itemId) {
            this.expandedId = this.expandedId === itemId ? null : itemId;
        },
        itemTypeLabel(typeValue) {
            const key = String(typeValue ?? "").trim();
            const found = this.displayTypeOptions.find(
                (opt) => String(opt.id) === key,
            );
            if (found) return `${found.name} (${found.id})`;
            return `Type ${typeValue}`;
        },
        itemGenderLabel(genderValue) {
            const gender = Number(genderValue);
            if (gender === 0) return "Trái Đất";
            if (gender === 1) return "Namek";
            if (gender === 2) return "Xayda";
            if (gender === 3) return "Chung/Tất cả";
            return "Không rõ";
        },
        editorNumber(field) {
            const value = Number(this.editor.form?.[field] ?? 0);
            return Number.isFinite(value) ? value : 0;
        },
        chooseIconFile(mode = "split") {
            this.editor.pendingIconUploadMode = "split";
            if (this.$refs.iconFileInput) {
                this.$refs.iconFileInput.value = "";
            }
            this.$refs.iconFileInput?.click();
        },
        handleIconFileChange(event) {
            const file = event?.target?.files?.[0] || null;
            this.acceptIconFile(file, this.editor.pendingIconUploadMode);
            if (event?.target && !this.editor.iconFile) {
                event.target.value = "";
            }
        },
        handleIconDragEnter(event, mode) {
            if (this.editor.saving) return;
            if (this.hasDraggedFile(event)) {
                this.editor.iconDraggingMode = mode;
            }
        },
        handleIconDragOver(event, mode) {
            if (this.editor.saving) return;
            if (this.hasDraggedFile(event)) {
                event.dataTransfer.dropEffect = "copy";
                this.editor.iconDraggingMode = mode;
            }
        },
        handleIconDragLeave(event, mode) {
            if (
                this.editor.iconDraggingMode === mode &&
                !event.currentTarget.contains(event.relatedTarget)
            ) {
                this.editor.iconDraggingMode = "";
            }
        },
        handleIconDrop(event, mode) {
            this.editor.iconDraggingMode = "";
            if (this.editor.saving) return;

            const file = event.dataTransfer?.files?.[0] || null;
            this.acceptIconFile(file, mode);
        },
        hasDraggedFile(event) {
            return Array.from(event.dataTransfer?.types || []).includes("Files");
        },
        acceptIconFile(file, mode = "split") {
            this.revokeIconPreview();

            if (!file) {
                this.editor.iconFile = null;
                this.editor.pendingIconUploadMode = "split";
                return;
            }

            if (file.type !== "image/png") {
                this.editor.error = "Chỉ hỗ trợ ảnh PNG.";
                this.editor.iconFile = null;
                this.editor.pendingIconUploadMode = "split";
                return;
            }

            this.editor.error = "";
            this.editor.iconFile = file;
            this.editor.iconUploadMode = "split";
            this.editor.pendingIconUploadMode = "split";
            this.editor.iconPreviewUrl = URL.createObjectURL(file);
        },
        clearIconFile() {
            this.revokeIconPreview();
            this.editor.iconFile = null;
            this.editor.iconUploadMode = "split";
            this.editor.pendingIconUploadMode = "split";
            this.editor.iconDraggingMode = "";
            if (this.$refs.iconFileInput) {
                this.$refs.iconFileInput.value = "";
            }
        },
        revokeIconPreview() {
            if (this.editor.iconPreviewUrl) {
                URL.revokeObjectURL(this.editor.iconPreviewUrl);
                this.editor.iconPreviewUrl = "";
            }
        },
        openEditor(item) {
            this.revokeIconPreview();
            this.editor = {
                open: true,
                saving: false,
                error: "",
                iconFile: null,
                iconPreviewUrl: "",
                iconDraggingMode: "",
                iconUploadMode: "split",
                pendingIconUploadMode: "split",
                form: {
                    id: item.id,
                    name: item.name || "",
                    type: Number(item.type ?? 0),
                    gender: Number(item.gender ?? 3),
                    icon_id: Number(item.icon_id ?? 0),
                    part: Number(item.part ?? -1),
                    head: Number(item.head ?? -1),
                    body: Number(item.body ?? -1),
                    leg: Number(item.leg ?? -1),
                    description: item.description || "",
                    is_up_to_up: !!item.is_up_to_up,
                },
            };
        },
        closeEditor() {
            if (this.editor.saving) return;
            this.clearIconFile();
            this.editor.open = false;
            this.editor.error = "";
        },
        async saveEditor() {
            if (!this.editor.form.name) {
                this.editor.error = "Tên item không được để trống.";
                return;
            }

            this.editor.saving = true;
            this.editor.error = "";
            try {
                const formData = new FormData();
                Object.entries(this.editor.form).forEach(([key, value]) => {
                    formData.append(
                        key,
                        typeof value === "boolean" ? (value ? "1" : "0") : String(value ?? ""),
                    );
                });
                if (this.editor.iconFile) {
                    formData.append("icon_x4", this.editor.iconFile);
                    formData.append("icon_upload_mode", this.editor.iconUploadMode);
                }

                const res = await fetch(
                    `/admin/api/items/${this.editor.form.id}`,
                    {
                        method: "POST",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": csrfToken(),
                        },
                        body: formData,
                    },
                );
                const data = await readJsonResponse(
                    res,
                    "Không thể lưu item",
                );
                if (!data.ok) {
                    throw new Error(data.message || "Không thể lưu item");
                }
                this.iconCacheBust = Date.now();
                this.editor.saving = false;
                this.closeEditor();
                await this.loadPage(this.page);
            } catch (e) {
                this.editor.error = e?.message || "Không thể lưu item";
            } finally {
                this.editor.saving = false;
            }
        },
        partById(partId) {
            if (partId === null || partId === undefined || Number(partId) < 0) {
                return null;
            }
            return (
                this.partMap[String(partId)] ||
                this.partMap[Number(partId)] ||
                null
            );
        },
        partChipTitle(partId) {
            const part = this.partById(partId);
            if (!part) return `Part #${partId}`;
            return `Part #${part.id} | ${this.partTypeLabel(part)} | ${part.layer_count} ảnh`;
        },
        partTypeLabel(part) {
            if (!part) return "Không rõ";
            if (part.type_name) {
                return `${part.type_name} (${part.type})`;
            }
            if (part.type === 0) return "Đầu (0)";
            if (part.type === 1) return "Thân (1)";
            if (part.type === 2) return "Chân (2)";
            return `TYPE ${part.type}`;
        },
        detailBlocks(item) {
            const blocks = [
                {
                    key: "part",
                    label: "Part chính",
                    part: item.part_preview?.part || this.partById(item.part),
                },
                {
                    key: "head",
                    label: "Head",
                    part: item.part_preview?.head || this.partById(item.head),
                },
                {
                    key: "body",
                    label: "Body",
                    part: item.part_preview?.body || this.partById(item.body),
                },
                {
                    key: "leg",
                    label: "Leg",
                    part: item.part_preview?.leg || this.partById(item.leg),
                },
            ];
            return blocks.filter((block) => block.part);
        },
        async loadPage(p) {
            this.loading = true;
            this.error = "";
            this.page = this.normalizePage(p);
            this.pageInput = String(this.page);
            this.expandedId = null;

            try {
                const params = new URLSearchParams({ page: String(this.page) });
                if (this.search) params.set("search", this.search);
                this.typeFilter.forEach((type) => params.append("type[]", type));
                if (this.genderFilter) params.set("gender", this.genderFilter);

                const res = await fetch(
                    `/admin/api/items?${params.toString()}`,
                    {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    },
                );
                const data = await readJsonResponse(
                    res,
                    "Không thể lọc item",
                );
                this.items = data.data || [];
                this.partMap = data.part_map || {};
                this.types = data.types || [];
                this.typeOptions = data.type_options || [];
                this.totalPages = data.total_pages || 1;
                this.total = data.total || 0;
                this.page = this.normalizePage(data.page || this.page);
                this.pageInput = String(this.page);
            } catch (e) {
                this.error = e?.message || "Không thể lọc item";
                this.items = [];
                this.total = 0;
                this.totalPages = 1;
                this.page = 1;
                this.pageInput = "1";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}
.page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    margin-bottom: 4px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}
.breadcrumb a {
    color: var(--ds-text-muted);
}
.breadcrumb a:hover {
    color: var(--ds-primary);
}
.breadcrumb .sep {
    color: var(--ds-gray-300);
}
.breadcrumb .current {
    color: var(--ds-text);
}
.total-count {
    font-size: 13px;
    color: var(--ds-text-muted);
    background: var(--ds-gray-100);
    padding: 4px 12px;
    border-radius: 20px;
}
.filter-bar {
    margin-bottom: 20px;
}
.search-form {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.type-filter-control {
    position: relative;
    width: 220px;
}
.type-filter-trigger {
    width: 100%;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    overflow: hidden;
    text-align: left;
    cursor: pointer;
}
.type-filter-trigger > span:first-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.type-filter-trigger .mi {
    flex: 0 0 auto;
    font-size: 18px;
}
.type-filter-menu {
    position: absolute;
    z-index: 30;
    top: calc(100% + 6px);
    left: 0;
    width: min(320px, calc(100vw - 32px));
    max-height: 320px;
    overflow-y: auto;
    padding: 6px;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-surface);
    box-shadow: 0 12px 28px rgba(28, 34, 40, 0.18);
}
.type-filter-option {
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 34px;
    padding: 6px 8px;
    border-radius: 7px;
    color: var(--ds-text);
    font-size: 13px;
    cursor: pointer;
}
.type-filter-option:hover {
    background: var(--ds-gray-100);
}
.type-filter-option--all {
    margin-bottom: 4px;
    border-bottom: 1px solid var(--ds-border);
    border-radius: 7px 7px 4px 4px;
    font-weight: 700;
}
.type-filter-option input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: var(--ds-primary, #2f8132);
}
.search-input-wrap {
    position: relative;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-text-muted);
    font-size: 18px;
    pointer-events: none;
}
.search-input {
    padding-left: 38px !important;
    width: 280px;
}
.items-table th {
    white-space: nowrap;
}
.item-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--ds-gray-100);
}
.item-name {
    font-weight: 600;
    color: var(--ds-text-emphasis);
}
.item-meta {
    margin-top: 2px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.description-cell {
    max-width: 260px;
    color: var(--ds-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.badge-muted {
    background: rgba(148, 163, 184, 0.14);
    color: var(--ds-text-muted);
    border: 1px solid rgba(148, 163, 184, 0.22);
}
.part-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 48px;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.1);
    border: 1px solid rgba(var(--ds-primary-rgb), 0.26);
    color: var(--ds-text);
    font-size: 12px;
    font-weight: 600;
}
.part-chip-main {
    background: rgba(var(--ds-success-rgb), 0.12);
    border-color: rgba(var(--ds-success-rgb), 0.24);
}
.muted {
    color: var(--ds-text-muted);
}
.action-cell {
    text-align: right;
    white-space: nowrap;
}
.action-cell .btn + .btn {
    margin-left: 6px;
}
.detail-row td {
    background: rgba(var(--ds-primary-rgb), 0.03);
    padding: 0 !important;
}
.detail-panel {
    padding: 16px;
}
.detail-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.detail-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}
.detail-subtitle {
    font-size: 12px;
    color: var(--ds-text-muted);
    margin-top: 4px;
}
.detail-stats {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.detail-stat {
    padding: 4px 8px;
    border-radius: 999px;
    background: var(--ds-gray-100);
    color: var(--ds-text-muted);
    font-size: 12px;
}
.part-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
}
.part-card {
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-body-bg);
    padding: 12px;
}
.part-card-head {
    margin-bottom: 10px;
}
.part-card-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}
.part-card-meta {
    margin-top: 3px;
    color: var(--ds-text-muted);
    font-size: 11px;
}
.layer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(118px, 1fr));
    gap: 8px;
}
.layer-card {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-1);
    padding: 8px;
}
.layer-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--ds-gray-100);
    flex-shrink: 0;
}
.layer-info {
    min-width: 0;
}
.layer-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--ds-text);
}
.layer-meta {
    font-size: 11px;
    color: var(--ds-text-muted);
    margin-top: 2px;
}
.layer-empty {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
}
.pagination {
    flex-wrap: wrap;
    gap: 8px;
}
.pagination-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.pagination-jump {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px;
    flex-wrap: wrap;
}
.pagination-summary {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.pagination-input {
    width: 72px;
    min-width: 72px;
    padding: 6px 8px !important;
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
    width: min(780px, calc(100vw - 32px));
    max-height: min(780px, calc(100vh - 48px));
    overflow: auto;
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-lg, 0 24px 70px rgba(0, 0, 0, 0.45));
    padding: 18px;
}
.editor-head,
.editor-actions,
.editor-preview,
.editor-check {
    display: flex;
    align-items: center;
}
.editor-head {
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
}
.editor-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.editor-head p,
.editor-preview small,
.editor-note {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 12px;
}
.icon-action {
    width: 36px;
    height: 36px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.editor-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.editor-preview {
    gap: 12px;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-surface-2);
    padding: 12px;
    transition:
        border-color 0.16s ease,
        background-color 0.16s ease,
        box-shadow 0.16s ease;
}
.editor-preview-copy {
    min-width: 0;
}
.editor-preview strong,
.editor-preview small {
    display: block;
}
.editor-icon-wrap {
    width: 46px;
    height: 46px;
    flex-shrink: 0;
}
.editor-icon {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    background: var(--ds-gray-100);
}
.editor-icon-preview-img {
    width: 46px;
    height: 46px;
    display: block;
    object-fit: contain;
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    background: var(--ds-gray-100);
}
.hidden-file-input {
    display: none;
}
.icon-upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
.icon-upload-zone {
    width: 100%;
    min-height: 104px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px dashed var(--ds-border);
    border-radius: 10px;
    background: var(--ds-surface-2);
    color: var(--ds-text);
    padding: 14px;
    text-align: left;
    cursor: pointer;
    transition:
        border-color 0.16s ease,
        background-color 0.16s ease,
        box-shadow 0.16s ease;
}
.icon-upload-zone:hover:not(:disabled),
.icon-upload-zone.is-dragging,
.icon-upload-zone.is-selected {
    border-color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.08);
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.1);
}
.icon-upload-zone:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}
.icon-upload-zone__icon {
    flex-shrink: 0;
    font-size: 28px;
    color: var(--ds-primary);
}
.icon-upload-zone__copy {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.icon-upload-zone__copy strong {
    color: var(--ds-text-emphasis);
    font-size: 13px;
}
.icon-upload-zone__copy small,
.icon-upload-zone__copy > span {
    color: var(--ds-text-muted);
    font-size: 11px;
}
.icon-upload-zone__copy > span {
    margin-top: 3px;
}
.icon-upload-selection {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.icon-file-name {
    color: var(--ds-primary) !important;
    word-break: break-word;
}
.editor-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.editor-grid label,
.editor-description {
    min-width: 0;
}
.form-label {
    display: block;
    color: var(--ds-text-muted);
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
}
.editor-description textarea {
    height: auto;
    min-height: 96px;
    padding-top: 10px !important;
    resize: vertical;
}
.editor-check {
    gap: 8px;
    color: var(--ds-text);
    font-size: 13px;
}
.editor-actions {
    justify-content: flex-end;
    gap: 10px;
    margin-top: 16px;
}
@media (max-width: 720px) {
    .icon-upload-grid,
    .editor-grid {
        grid-template-columns: 1fr;
    }
}
</style>
