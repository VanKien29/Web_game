<template>
    <div class="npcs-page">
        <div class="page-top">
            <div>
                <h2 class="page-title">NPC</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">
                        Trang chủ
                    </router-link>
                    <span>/</span>
                    <span class="current">NPC</span>
                </nav>
            </div>
            <span v-if="total" class="total-count">
                {{ total.toLocaleString("vi-VN") }} NPC
            </span>
        </div>

        <div class="filter-bar">
            <div class="search-input-wrap">
                <span class="mi search-icon">search</span>
                <input
                    v-model="search"
                    class="form-input search-input"
                    placeholder="Tìm ID, tên, avatar, head/body/leg..."
                    @input="debouncedLoad"
                />
            </div>
            <button class="btn btn-outline" :disabled="loading" @click="load(1)">
                <span class="mi" style="font-size: 16px">refresh</span>
                Tải lại
            </button>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Avatar</th>
                            <th>Tên NPC</th>
                            <th>Head</th>
                            <th>Body</th>
                            <th>Leg</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="npc in rows" :key="npc.id">
                            <td>#{{ npc.id }}</td>
                            <td>
                                <img
                                    v-if="npc.avatar_url"
                                    :src="npc.avatar_url"
                                    loading="lazy"
                                    decoding="async"
                                    class="npc-avatar"
                                    :alt="npc.name"
                                />
                                <AdminIcon
                                    v-else
                                    :icon-id="npc.avatar"
                                    class="npc-avatar"
                                />
                            </td>
                            <td>
                                <div class="item-name">{{ npc.name }}</div>
                                <div class="item-meta">Avatar {{ npc.avatar }}</div>
                            </td>
                            <td>
                                <span class="part-chip">#{{ npc.head }}</span>
                            </td>
                            <td>
                                <span class="part-chip">#{{ npc.body }}</span>
                            </td>
                            <td>
                                <span class="part-chip">#{{ npc.leg }}</span>
                            </td>
                            <td class="action-cell">
                                <button
                                    class="btn btn-primary btn-sm"
                                    type="button"
                                    title="Sửa NPC"
                                    @click="openEditor(npc)"
                                >
                                    <span class="mi">edit</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="loading" class="admin-loading-row">
                            <td colspan="7">
                                <span class="admin-loading-row__content">
                                    <span class="admin-loading-spinner"></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!rows.length && !loading">
                            <td colspan="7" class="empty-cell">
                                Chưa có NPC phù hợp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="load(page - 1)">
                    &laquo;
                </button>
                <button
                    v-for="p in totalPages"
                    :key="p"
                    :class="{ active: p === page }"
                    @click="load(p)"
                >
                    {{ p }}
                </button>
                <button :disabled="page >= totalPages" @click="load(page + 1)">
                    &raquo;
                </button>
            </div>
        </div>

        <div v-if="editor.open" class="modal-overlay" @click.self="closeEditor">
            <div class="modal-panel">
                <div class="modal-head">
                    <div>
                        <h3>Sửa NPC #{{ editor.form.id }}</h3>
                        <p>Thay avatar hoặc sprite sẽ tự sinh icon mới và dọn ảnh cũ nếu không còn dùng.</p>
                    </div>
                    <button class="icon-action danger" type="button" @click="closeEditor">
                        <span class="mi">close</span>
                    </button>
                </div>

                <div v-if="editor.error" class="alert alert-error">
                    {{ editor.error }}
                </div>

                <div class="editor-layout">
                    <aside class="editor-side">
                        <div class="preview-card">
                            <div class="preview-icon-wrap">
                                <img
                                    v-if="avatarPreview"
                                    :src="avatarPreview"
                                    decoding="async"
                                    class="preview-image"
                                    alt="NPC avatar"
                                />
                                <AdminIcon
                                    v-else
                                    :icon-id="Number(editor.form.avatar || 0)"
                                    class="preview-image"
                                />
                            </div>
                            <strong>{{ editor.form.name || "NPC" }}</strong>
                            <small>Avatar {{ editor.form.avatar || 0 }}</small>
                        </div>
                    </aside>

                    <div class="editor-main">
                        <section class="editor-card">
                            <div class="form-grid">
                                <label>
                                    <span class="form-label">Tên NPC</span>
                                    <input
                                        v-model.trim="editor.form.name"
                                        class="form-input"
                                        placeholder="Tên NPC"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Avatar ID</span>
                                    <input
                                        v-model.number="editor.form.avatar"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                        max="32767"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Head part</span>
                                    <input
                                        v-model.number="editor.form.head"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                        max="32767"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Body part</span>
                                    <input
                                        v-model.number="editor.form.body"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                        max="32767"
                                    />
                                </label>
                                <label>
                                    <span class="form-label">Leg part</span>
                                    <input
                                        v-model.number="editor.form.leg"
                                        class="form-input"
                                        type="number"
                                        min="0"
                                        max="32767"
                                    />
                                </label>
                            </div>
                        </section>

                        <section class="editor-card">
                            <div class="upload-grid">
                                <div
                                    class="file-box"
                                    @dragover.prevent="dragField = 'avatar_x4'"
                                    @dragleave.prevent="dragField = ''"
                                    @drop.prevent="dropFiles('avatar_x4', $event)"
                                >
                                    <input
                                        id="npc-avatar"
                                        class="file-input-hidden"
                                        type="file"
                                        accept="image/png"
                                        @change="setFiles('avatar_x4', $event.target.files)"
                                    />
                                    <label
                                        class="drop-box"
                                        :class="{ dragging: dragField === 'avatar_x4' }"
                                        for="npc-avatar"
                                    >
                                        <span class="mi">account_circle</span>
                                        <strong>Avatar NPC</strong>
                                        <small>
                                            {{ fileSummary("avatar_x4") || "PNG x4, tự sinh avatar ID" }}
                                        </small>
                                    </label>
                                </div>

                                <div
                                    class="file-box"
                                    @dragover.prevent="dragField = 'icon_x4'"
                                    @dragleave.prevent="dragField = ''"
                                    @drop.prevent="dropFiles('icon_x4', $event)"
                                >
                                    <input
                                        id="npc-part-icons"
                                        class="file-input-hidden"
                                        type="file"
                                        accept="image/png"
                                        multiple
                                        webkitdirectory
                                        directory
                                        @change="setFiles('icon_x4', $event.target.files)"
                                    />
                                    <label
                                        class="drop-box"
                                        :class="{ dragging: dragField === 'icon_x4' }"
                                        for="npc-part-icons"
                                    >
                                        <span class="mi">image</span>
                                        <strong>Sprite part</strong>
                                        <small>
                                            {{ fileSummary("icon_x4") || "Nhiều PNG/folder, map theo ID trong DATA" }}
                                        </small>
                                    </label>
                                </div>
                            </div>
                        </section>

                        <section class="editor-card">
                            <div class="part-grid">
                                <label>
                                    <span class="form-label">Head DATA</span>
                                    <textarea
                                        v-model="editor.form.head_data"
                                        class="form-input part-input"
                                        rows="6"
                                    ></textarea>
                                </label>
                                <label>
                                    <span class="form-label">Body DATA</span>
                                    <textarea
                                        v-model="editor.form.body_data"
                                        class="form-input part-input"
                                        rows="6"
                                    ></textarea>
                                </label>
                                <label>
                                    <span class="form-label">Leg DATA</span>
                                    <textarea
                                        v-model="editor.form.leg_data"
                                        class="form-input part-input"
                                        rows="6"
                                    ></textarea>
                                </label>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="modal-actions">
                    <button
                        class="btn btn-outline"
                        type="button"
                        :disabled="editor.saving"
                        @click="closeEditor"
                    >
                        Hủy
                    </button>
                    <button
                        class="btn btn-primary"
                        type="button"
                        :disabled="editor.saving"
                        @click="saveNpc"
                    >
                        <span class="mi" style="font-size: 16px">save</span>
                        {{ editor.saving ? "Đang lưu..." : "Lưu NPC" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { csrfToken } from "../../shared/format";
import { readJsonResponse } from "../../shared/api";
import { filesToPayload } from "../../shared/files";

export default {
    data() {
        return {
            rows: [],
            search: "",
            page: 1,
            perPage: 30,
            total: 0,
            totalPages: 1,
            loading: false,
            error: "",
            success: "",
            searchTimer: null,
            dragField: "",
            avatarPreview: "",
            editor: this.blankEditor(),
        };
    },
    created() {
        this.load(1);
    },
    beforeUnmount() {
        window.clearTimeout(this.searchTimer);
        this.revokePreview();
    },
    methods: {
        blankEditor() {
            return {
                open: false,
                saving: false,
                error: "",
                files: {
                    avatar_x4: [],
                    icon_x4: [],
                },
                form: {
                    id: null,
                    name: "",
                    avatar: 0,
                    head: 0,
                    body: 0,
                    leg: 0,
                    head_data: "",
                    body_data: "",
                    leg_data: "",
                },
            };
        },
        async load(page = this.page) {
            this.loading = true;
            this.error = "";
            try {
                const params = new URLSearchParams({
                    page,
                    per_page: this.perPage,
                    search: this.search,
                });
                const res = await fetch(`/admin/api/npcs?${params}`, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                const data = await readJsonResponse(res, "Không tải được NPC");
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được NPC");
                }
                this.rows = data.data || [];
                this.page = data.page || page;
                this.total = data.total || 0;
                this.totalPages = data.total_pages || 1;
            } catch (error) {
                this.error = error?.message || "Không tải được NPC";
            } finally {
                this.loading = false;
            }
        },
        debouncedLoad() {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = window.setTimeout(() => this.load(1), 260);
        },
        async openEditor(npc) {
            this.revokePreview();
            this.avatarPreview = npc?.avatar_url || "";
            this.editor = this.blankEditor();
            this.editor.open = true;
            this.editor.saving = true;
            try {
                const res = await fetch(`/admin/api/npcs/${npc.id}`, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                const data = await readJsonResponse(res, "Không tải được NPC");
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không tải được NPC");
                }
                const detail = data.data || {};
                this.editor.form = {
                    ...this.editor.form,
                    id: detail.id,
                    name: detail.name || "",
                    avatar: Number(detail.avatar || 0),
                    head: Number(detail.head || 0),
                    body: Number(detail.body || 0),
                    leg: Number(detail.leg || 0),
                    head_data: detail.head_data || "",
                    body_data: detail.body_data || "",
                    leg_data: detail.leg_data || "",
                };
                this.avatarPreview = detail.avatar_url || npc.avatar_url || "";
            } catch (error) {
                this.editor.error = error?.message || "Không tải được NPC";
            } finally {
                this.editor.saving = false;
            }
        },
        closeEditor() {
            if (this.editor.saving) return;
            this.revokePreview();
            this.editor.open = false;
        },
        setFiles(field, fileList) {
            const files = Array.from(fileList || []);
            this.editor.files[field] =
                field === "avatar_x4" ? files.slice(0, 1) : files;
            this.dragField = "";
            if (field === "avatar_x4") {
                this.revokePreview();
                this.avatarPreview = files[0] ? URL.createObjectURL(files[0]) : "";
            }
        },
        dropFiles(field, event) {
            this.setFiles(field, event.dataTransfer?.files || []);
        },
        fileSummary(field) {
            const files = this.editor.files[field] || [];
            if (!files.length) return "";
            if (files.length === 1) return files[0].name;
            return `${files.length} file`;
        },
        revokePreview() {
            if (this.avatarPreview?.startsWith("blob:")) {
                URL.revokeObjectURL(this.avatarPreview);
            }
        },
        numericIdFromFilename(name) {
            const base = String(name || "").replace(/\\/g, "/").split("/").pop() || "";
            const stem = base.replace(/\.[^.]+$/, "");
            const match = stem.match(/(\d+)(?!.*\d)/);
            if (!match) return null;
            const id = Number(match[1]);
            return id > 0 && id <= 32767 ? id : null;
        },
        referencedPartIconIds() {
            const raw = [
                this.editor.form.head_data,
                this.editor.form.body_data,
                this.editor.form.leg_data,
            ].join("\n");
            const ids = new Set();
            const pattern = /\[\s*(-?\d+)\s*,\s*-?\d+\s*,\s*-?\d+\s*\]/g;
            let match;
            while ((match = pattern.exec(raw))) {
                const id = Number(match[1]);
                if (id > 0 && id <= 32767) ids.add(id);
            }
            return ids;
        },
        filteredSpriteFiles(files) {
            const referenced = this.referencedPartIconIds();
            const byNumericName = (left, right) =>
                (this.numericIdFromFilename(left.name) ?? Number.MAX_SAFE_INTEGER) -
                    (this.numericIdFromFilename(right.name) ?? Number.MAX_SAFE_INTEGER) ||
                String(left.name).localeCompare(String(right.name));
            if (!referenced.size) return [...files].sort(byNumericName);
            const filtered = files.filter((file) => {
                const id = this.numericIdFromFilename(file.name);
                return id !== null && referenced.has(id);
            });
            return (filtered.length ? filtered : files).slice().sort(byNumericName);
        },
        async saveNpc() {
            if (!this.editor.form.name) {
                this.editor.error = "Tên NPC không được để trống.";
                return;
            }
            if (
                !this.editor.form.head_data ||
                !this.editor.form.body_data ||
                !this.editor.form.leg_data
            ) {
                this.editor.error = "Cần nhập đủ Head/Body/Leg DATA.";
                return;
            }

            this.editor.saving = true;
            this.editor.error = "";
            const formData = new FormData();
            Object.entries(this.editor.form).forEach(([key, value]) => {
                formData.set(key, value ?? "");
            });

            try {
                const spriteFiles = this.editor.files.icon_x4 || [];
                const filteredSprites = this.filteredSpriteFiles(spriteFiles);
                if (filteredSprites.length) {
                    formData.set(
                        "icon_x4_payload",
                        JSON.stringify(await filesToPayload(filteredSprites)),
                    );
                }

                (this.editor.files.avatar_x4 || []).forEach((file) =>
                    formData.append("avatar_x4[]", file),
                );

                const res = await fetch(`/admin/api/npcs/${this.editor.form.id}`, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": csrfToken(),
                    },
                    body: formData,
                });
                const data = await readJsonResponse(res, "Không lưu được NPC");
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || "Không lưu được NPC");
                }
                this.success = data.message || "Đã lưu NPC";
                this.closeEditor();
                await this.load(this.page);
            } catch (error) {
                this.editor.error = error?.message || "Không lưu được NPC";
            } finally {
                this.editor.saving = false;
            }
        },
    },
};
</script>

<style scoped>
.npcs-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.page-top,
.filter-bar,
.modal-head,
.modal-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.page-title {
    margin: 0 0 4px;
    color: var(--ds-text-emphasis);
    font-size: 22px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.total-count {
    color: var(--ds-text-muted);
    font-weight: 700;
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
}
.search-input {
    width: min(460px, calc(100vw - 48px));
    padding-left: 40px !important;
}
.npc-avatar,
.preview-image {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: var(--ds-gray-100);
    object-fit: contain;
}
.item-name {
    color: var(--ds-text-emphasis);
    font-weight: 700;
}
.item-meta,
.drop-box small,
.preview-card small {
    color: var(--ds-text-muted);
    font-size: 12px;
}
.part-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 54px;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.18);
    border-radius: 999px;
    background: rgba(var(--ds-primary-rgb), 0.08);
    color: var(--ds-primary);
    padding: 4px 9px;
    font-weight: 700;
    font-size: 12px;
}
.empty-cell {
    text-align: center;
    color: var(--ds-text-muted);
    padding: 32px;
}
.action-cell {
    text-align: right;
    white-space: nowrap;
}
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 3000;
    background: var(--ds-overlay-bg);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow: auto;
    padding: 18px;
}
.modal-panel {
    width: min(1080px, calc(100vw - 36px));
    border: 1px solid var(--ds-border);
    border-radius: 12px;
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-xl);
    overflow: hidden;
}
.modal-head {
    align-items: flex-start;
    padding: 18px 20px;
    border-bottom: 1px solid var(--ds-border);
    background: linear-gradient(
        180deg,
        rgba(var(--ds-primary-rgb), 0.08),
        transparent
    );
}
.modal-head h3 {
    margin: 0;
    color: var(--ds-text-emphasis);
}
.modal-head p {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.modal-panel > .alert {
    margin: 16px 20px 0;
}
.editor-layout {
    display: grid;
    grid-template-columns: 250px minmax(0, 1fr);
}
.editor-side {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px;
    border-right: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
}
.editor-main {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
    padding: 18px 20px;
}
.editor-card,
.preview-card {
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface);
    padding: 14px;
}
.preview-card {
    text-align: center;
}
.preview-icon-wrap {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    margin: 0 auto 10px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
}
.preview-card .preview-image {
    width: 64px;
    height: 64px;
}
.form-grid,
.upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.part-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.file-input-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.drop-box {
    min-height: 118px;
    border: 1px dashed var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    text-align: center;
    cursor: pointer;
    transition:
        border-color 0.16s ease,
        background 0.16s ease,
        transform 0.16s ease;
}
.drop-box:hover,
.drop-box.dragging {
    border-color: var(--ds-primary);
    background: rgba(var(--ds-primary-rgb), 0.08);
    transform: translateY(-1px);
}
.drop-box .mi {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: rgba(var(--ds-primary-rgb), 0.12);
    color: var(--ds-primary);
}
.drop-box strong {
    color: var(--ds-text-emphasis);
}
.part-input {
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
    font-size: 12px;
    line-height: 1.45;
}
.modal-actions {
    padding: 14px 20px;
    border-top: 1px solid var(--ds-border);
    justify-content: flex-end;
    background: var(--ds-surface-2);
}
.icon-action {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border: 1px solid var(--ds-border);
    border-radius: 8px;
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
    display: inline-grid;
    place-items: center;
    cursor: pointer;
    transition:
        background-color 0.16s ease,
        border-color 0.16s ease,
        color 0.16s ease,
        transform 0.16s ease;
}
.icon-action:hover {
    background: var(--ds-gray-100);
    color: var(--ds-text-emphasis);
    transform: translateY(-1px);
}
.icon-action.danger:hover {
    border-color: rgba(var(--ds-danger-rgb), 0.55);
    color: var(--ds-danger);
}
@media (max-width: 980px) {
    .editor-layout,
    .form-grid,
    .upload-grid,
    .part-grid {
        grid-template-columns: 1fr;
    }
    .editor-side {
        border-right: 0;
        border-bottom: 1px solid var(--ds-border);
    }
}
</style>
