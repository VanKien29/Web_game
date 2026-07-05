<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Quản lý diễn đàn</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <span class="current">Diễn đàn</span>
                </nav>
            </div>
            <div class="page-top-actions">
                <button type="button" class="btn btn-primary admin-fab" title="Tạo bài admin" @click="openCreate">
                    <span class="mi" style="font-size: 16px">add</span>
                    Tạo bài admin
                </button>
                <a href="/forum" target="_blank" class="btn btn-outline">
                    <span class="mi" style="font-size: 16px">open_in_new</span>
                    Xem diễn đàn
                </a>
            </div>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="stats-grid forum-stats">
            <div class="stat-card">
                <div class="stat-icon primary"><span class="mi">forum</span></div>
                <div>
                    <div class="stat-title">Tổng bài</div>
                    <div class="stat-value">{{ stats.all || 0 }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><span class="mi">campaign</span></div>
                <div>
                    <div class="stat-title">Thông báo</div>
                    <div class="stat-value">{{ stats.announcements || 0 }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><span class="mi">lightbulb</span></div>
                <div>
                    <div class="stat-title">Góp ý</div>
                    <div class="stat-value">{{ stats.feedback || 0 }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon danger"><span class="mi">visibility_off</span></div>
                <div>
                    <div class="stat-title">Đã ẩn</div>
                    <div class="stat-value">{{ stats.hidden || 0 }}</div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input v-model="search" class="form-input search-input" placeholder="Tìm nội dung, tác giả..." />
                </div>
                <select v-model="typeFilter" class="form-input compact-filter">
                    <option value="">Tất cả loại</option>
                    <option value="announcement">Thông báo</option>
                    <option value="player_post">Bài người chơi</option>
                    <option value="feedback">Góp ý</option>
                </select>
                <select v-model="statusFilter" class="form-input compact-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="published">Hiển thị</option>
                    <option value="pending">Chờ duyệt</option>
                    <option value="hidden">Ẩn</option>
                    <option value="deleted">Đã xóa</option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bài diễn đàn</th>
                            <th>Loại</th>
                            <th>Tương tác</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="post in posts" :key="post.id">
                            <td>{{ post.id }}</td>
                            <td>
                                <div class="forum-title">{{ post.title || shortText(post.content, 72) }}</div>
                                <div class="forum-sub">
                                    <span class="mi">person</span>
                                    {{ post.author_username || "admin" }}
                                </div>
                                <div class="forum-sub forum-sub--content">
                                    {{ shortText(post.content, 120) }}
                                </div>
                                <div class="forum-flags">
                                    <span v-if="post.is_pinned" class="badge badge-warning">Ghim</span>
                                    <span v-if="post.is_locked" class="badge badge-danger">Khóa bình luận</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="typeBadge(post.type)">
                                    {{ post.type_label }}
                                </span>
                            </td>
                            <td>
                                <div class="stat-line">
                                    <span class="mi">thumb_up</span>
                                    {{ post.reaction_count || 0 }} cảm xúc
                                </div>
                                <div class="stat-line">
                                    <span class="mi">comment</span>
                                    {{ post.comment_count || 0 }} bình luận
                                </div>
                                <div class="stat-line">
                                    <span class="mi">share</span>
                                    {{ post.share_count || 0 }} chia sẻ
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="statusBadge(post.status)">
                                    {{ statusLabel(post.status) }}
                                </span>
                            </td>
                            <td>{{ formatDate(post.created_at) }}</td>
                            <td class="action-cell">
                                <div class="row-actions">
                                    <button type="button" class="btn btn-outline btn-sm" title="Xem bình luận" @click="openComments(post)">
                                        <span class="mi" style="font-size: 14px">forum</span>
                                        Bình luận
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" title="Sửa bài diễn đàn" @click="edit(post)">
                                        <span class="mi" style="font-size: 14px">edit</span>
                                        Sửa
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" title="Xóa bài diễn đàn" @click="deletePost(post)">
                                        <span class="mi" style="font-size: 14px">delete</span>
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!posts.length && !loading">
                            <td colspan="7" class="empty-cell">Chưa có bài diễn đàn nào.</td>
                        </tr>
                        <tr v-if="loading">
                            <td colspan="7" class="empty-cell">
                                <span class="admin-loading-inline">
                                    <span class="admin-loading-dot"></span>
                                    Đang tải...
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totalPages > 1" class="pagination">
                <button :disabled="page <= 1" @click="loadPage(page - 1)">&laquo;</button>
                <button
                    v-for="p in totalPages"
                    :key="p"
                    :class="{ active: p === page }"
                    @click="loadPage(p)"
                >
                    {{ p }}
                </button>
                <button :disabled="page >= totalPages" @click="loadPage(page + 1)">&raquo;</button>
            </div>
        </div>

        <teleport to="body">
            <div v-if="showEditor" class="forum-modal" @click.self="closeEditor">
                <form class="forum-modal__panel forum-modal__panel--editor" @submit.prevent="save">
                    <div class="forum-modal__head">
                        <div>
                            <h3>{{ form.id ? "Sửa bài diễn đàn" : "Tạo bài admin" }}</h3>
                            <p>Soạn thông báo hoặc nội dung cần ghim trên diễn đàn.</p>
                        </div>
                        <button type="button" class="modal-close" @click="closeEditor">
                            <span class="mi">close</span>
                        </button>
                    </div>

                    <div class="forum-form-grid">
                        <div class="form-group">
                            <label class="form-label">Loại bài</label>
                            <select v-model="form.type" class="form-input">
                                <option value="announcement">Thông báo admin</option>
                                <option value="player_post">Bài người chơi</option>
                                <option value="feedback">Góp ý</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Trạng thái</label>
                            <select v-model="form.status" class="form-input">
                                <option value="published">Hiển thị</option>
                                <option value="pending">Chờ duyệt</option>
                                <option value="hidden">Ẩn</option>
                                <option value="deleted">Đã xóa</option>
                            </select>
                        </div>
                        <label class="forum-check">
                            <input v-model="form.is_pinned" type="checkbox" />
                            <span>Ghim lên đầu</span>
                        </label>
                        <label class="forum-check">
                            <input v-model="form.is_locked" type="checkbox" />
                            <span>Khóa bình luận</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tiêu đề</label>
                        <input v-model="form.title" class="form-input" maxlength="160" placeholder="Tiêu đề ngắn" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nội dung</label>
                        <RichTextEditor
                            v-model="form.content"
                            placeholder="Soạn nội dung hiển thị trên diễn đàn..."
                        />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ảnh đính kèm bằng URL</label>
                        <textarea
                            v-model="form.image_urls"
                            class="form-input"
                            rows="2"
                            placeholder="Mỗi dòng một URL ảnh hoặc đường dẫn /assets/..."
                        ></textarea>
                    </div>
                    <div class="forum-modal__actions">
                        <button type="button" class="btn btn-outline" @click="closeEditor">Hủy</button>
                        <button class="btn btn-primary" type="submit" :disabled="saving">
                            <span class="mi" style="font-size: 16px">save</span>
                            {{ saving ? "Đang lưu..." : form.id ? "Lưu thay đổi" : "Đăng bài" }}
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="commentsPost" class="forum-modal" @click.self="closeComments">
                <div class="forum-modal__panel forum-modal__panel--comments">
                    <div class="forum-modal__head">
                        <div>
                            <h3>Bình luận bài #{{ commentsPost.id }}</h3>
                            <p>{{ commentsPost.title || shortText(commentsPost.content, 86) }}</p>
                        </div>
                        <button type="button" class="modal-close" @click="closeComments">
                            <span class="mi">close</span>
                        </button>
                    </div>
                    <div v-if="commentsPost.commentsLoading" class="empty-cell">Đang tải bình luận...</div>
                    <div v-else-if="!commentsPost.comments?.length" class="empty-cell">Chưa có bình luận.</div>
                    <div v-else class="admin-comment-list">
                        <div v-for="comment in commentsPost.comments" :key="comment.id" class="admin-comment">
                            <div>
                                <strong>{{ comment.username }}</strong>
                                <span>{{ formatDate(comment.created_at) }}</span>
                                <p>{{ comment.content }}</p>
                            </div>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm"
                                :disabled="comment.status === 'deleted'"
                                @click="deleteComment(commentsPost, comment)"
                            >
                                Xóa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>

<script>
import RichTextEditor from "../../components/RichTextEditor.vue";
import { readJsonResponse } from "../../shared/api";

const emptyForm = () => ({
    id: null,
    type: "announcement",
    title: "",
    content: "",
    status: "published",
    is_pinned: true,
    is_locked: false,
    image_urls: "",
});

export default {
    components: { RichTextEditor },
    data() {
        return {
            posts: [],
            stats: {},
            form: emptyForm(),
            search: "",
            typeFilter: "",
            statusFilter: "",
            page: 1,
            totalPages: 1,
            loading: false,
            saving: false,
            showEditor: false,
            commentsPost: null,
            error: "",
            success: "",
        };
    },
    created() {
        this.loadPage(1);
    },
    methods: {
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        },
        async loadPage(page) {
            this.loading = true;
            this.error = "";
            this.page = Math.max(1, Number(page) || 1);
            try {
                const params = new URLSearchParams({
                    page: String(this.page),
                    search: this.search,
                });
                if (this.typeFilter) params.set("type", this.typeFilter);
                if (this.statusFilter) params.set("status", this.statusFilter);

                const res = await fetch(`/admin/api/forum/posts?${params}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(res, "Không thể tải diễn đàn");
                this.posts = (data.data || []).map((post) => ({
                    ...post,
                    commentsOpen: false,
                    commentsLoading: false,
                    comments: [],
                }));
                this.stats = data.stats || {};
                this.page = data.page || this.page;
                this.totalPages = data.total_pages || 1;
            } catch (err) {
                this.error = err.message || "Không thể tải diễn đàn";
            } finally {
                this.loading = false;
            }
        },
        payload() {
            return {
                type: this.form.type,
                title: this.form.title,
                content: this.form.content,
                status: this.form.status,
                is_pinned: !!this.form.is_pinned,
                is_locked: !!this.form.is_locked,
                image_urls: this.form.image_urls,
            };
        },
        openCreate() {
            this.form = emptyForm();
            this.showEditor = true;
        },
        closeEditor() {
            this.showEditor = false;
            this.resetForm();
        },
        async save() {
            this.saving = true;
            this.error = "";
            this.success = "";
            try {
                const url = this.form.id ? `/admin/api/forum/posts/${this.form.id}` : "/admin/api/forum/posts";
                const res = await fetch(url, {
                    method: this.form.id ? "PUT" : "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                    body: JSON.stringify(this.payload()),
                });
                const data = await readJsonResponse(res, "Không thể lưu bài diễn đàn");
                this.success = data.message || "Đã lưu bài diễn đàn.";
                this.closeEditor();
                await this.loadPage(this.page);
            } catch (err) {
                this.error = err.message || "Không thể lưu bài diễn đàn";
            } finally {
                this.saving = false;
            }
        },
        edit(post) {
            this.form = {
                id: post.id,
                type: post.type,
                title: post.title || "",
                content: post.content || "",
                status: post.status || "published",
                is_pinned: !!post.is_pinned,
                is_locked: !!post.is_locked,
                image_urls: (post.images || []).join("\n"),
            };
            this.showEditor = true;
        },
        resetForm() {
            this.form = emptyForm();
        },
        async deletePost(post) {
            const ok = await window.adminConfirm({
                title: "Xóa bài diễn đàn",
                message: `Xóa bài diễn đàn #${post.id}?`,
                tone: "danger",
                confirmText: "Xóa",
            });
            if (!ok) return;
            this.error = "";
            this.success = "";
            try {
                const res = await fetch(`/admin/api/forum/posts/${post.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                });
                const data = await readJsonResponse(res, "Không thể xóa bài diễn đàn");
                this.success = data.message || "Đã xóa bài diễn đàn.";
                await this.loadPage(this.page);
            } catch (err) {
                this.error = err.message || "Không thể xóa bài diễn đàn";
            }
        },
        async openComments(post) {
            this.commentsPost = post;
            await this.loadComments(post);
        },
        closeComments() {
            this.commentsPost = null;
        },
        async loadComments(post) {
            post.commentsLoading = true;
            try {
                const res = await fetch(`/admin/api/forum/posts/${post.id}/comments`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(res, "Không thể tải bình luận");
                post.comments = data.data || [];
            } catch (err) {
                this.error = err.message || "Không thể tải bình luận";
            } finally {
                post.commentsLoading = false;
            }
        },
        async deleteComment(post, comment) {
            const ok = await window.adminConfirm({
                title: "Xóa bình luận",
                message: "Xóa bình luận này?",
                tone: "danger",
                confirmText: "Xóa",
            });
            if (!ok) return;
            try {
                const res = await fetch(`/admin/api/forum/posts/${post.id}/comments/${comment.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                });
                const data = await readJsonResponse(res, "Không thể xóa bình luận");
                post.comment_count = data.comment_count ?? Math.max(0, (post.comment_count || 1) - 1);
                await this.loadComments(post);
            } catch (err) {
                this.error = err.message || "Không thể xóa bình luận";
            }
        },
        shortText(value, length) {
            const text = String(value || "")
                .replace(/<[^>]+>/g, " ")
                .replace(/\s+/g, " ")
                .trim();
            return text.length > length ? `${text.slice(0, length)}...` : text;
        },
        typeBadge(type) {
            return {
                announcement: "badge-warning",
                player_post: "badge-info",
                feedback: "badge-primary",
            }[type] || "badge-info";
        },
        statusBadge(status) {
            return {
                published: "badge-success",
                pending: "badge-warning",
                hidden: "badge-danger",
                deleted: "badge-danger",
            }[status] || "badge-info";
        },
        statusLabel(status) {
            return {
                published: "Hiển thị",
                pending: "Chờ duyệt",
                hidden: "Ẩn",
                deleted: "Đã xóa",
            }[status] || status || "Không rõ";
        },
        formatDate(value) {
            if (!value) return "-";
            return new Date(value).toLocaleString("vi-VN", {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        },
    },
};
</script>

<style scoped>
.page-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.page-top-actions {
    display: inline-flex;
    gap: 10px;
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
.breadcrumb .current {
    color: var(--ds-text);
}
.forum-stats .stat-icon .mi {
    font-size: 22px;
}
.forum-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(180px, 1fr)) repeat(2, auto);
    gap: 14px;
    align-items: end;
}
.forum-check {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    color: var(--ds-text);
    font-size: 13px;
    font-weight: 600;
}
.filter-bar {
    margin-bottom: 20px;
}
.search-form {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) 180px 180px auto;
    gap: 8px;
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
    pointer-events: none;
}
.search-input {
    padding-left: 38px !important;
}
.forum-title {
    color: var(--ds-text-emphasis);
    font-weight: 700;
    margin-bottom: 5px;
}
.forum-sub,
.stat-line {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--ds-text-muted);
    font-size: 12px;
    line-height: 1.5;
}
.forum-sub--content {
    display: block;
    max-width: 420px;
    margin-top: 4px;
}
.forum-flags,
.row-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.row-actions {
    justify-content: flex-end;
    margin-top: 0;
}
.empty-cell {
    text-align: center !important;
    color: var(--ds-text-muted) !important;
    padding: 32px !important;
}
.admin-comment-list {
    display: grid;
    gap: 10px;
    max-height: min(62vh, 640px);
    overflow: auto;
    padding-right: 4px;
}
.admin-comment {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 12px;
    border-radius: 8px;
    background: var(--ds-body-bg);
}
.admin-comment strong {
    color: var(--ds-text-emphasis);
}
.admin-comment span {
    color: var(--ds-text-muted);
    font-size: 12px;
    margin-left: 8px;
}
.admin-comment p {
    margin: 6px 0 0;
    color: var(--ds-text);
    white-space: pre-wrap;
}
.forum-modal {
    position: fixed;
    inset: 0;
    z-index: 6000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: var(--ds-overlay-bg);
    backdrop-filter: blur(4px);
}
.forum-modal__panel {
    width: min(920px, calc(100vw - 32px));
    max-height: calc(100vh - 48px);
    overflow: auto;
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius-lg);
    box-shadow: var(--ds-shadow-xl);
    padding: 22px;
}
.forum-modal__panel--comments {
    width: min(820px, calc(100vw - 32px));
}
.forum-modal__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
}
.forum-modal__head h3 {
    margin: 0 0 4px;
    color: var(--ds-text-emphasis);
    font-size: 18px;
}
.forum-modal__head p {
    margin: 0;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--ds-border);
    background: transparent;
    color: var(--ds-text);
    cursor: pointer;
}
.forum-modal__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
}
@media (max-width: 1080px) {
    .forum-form-grid,
    .search-form {
        grid-template-columns: 1fr;
    }
}
</style>
