<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Bình luận bài viết</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <router-link :to="{ name: 'admin.posts' }">Bài viết</router-link>
                    <span>/</span>
                    <span class="current">{{ post?.title || "Bình luận" }}</span>
                </nav>
            </div>
            <div class="page-top-actions">
                <a v-if="post" :href="forumPostHref" target="_blank" class="btn btn-outline">
                    <span class="mi" style="font-size: 16px">open_in_new</span>
                    Xem bài
                </a>
                <router-link
                    :to="{ name: 'admin.posts.edit', params: { id: $route.params.id } }"
                    class="btn btn-outline"
                >
                    <span class="mi" style="font-size: 16px">edit</span>
                    Sửa bài
                </router-link>
                <router-link :to="{ name: 'admin.posts' }" class="btn btn-outline">
                    <span class="mi" style="font-size: 16px">arrow_back</span>
                    Quay lại
                </router-link>
            </div>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
            <div class="card-header comments-head">
                <div>
                    <h3>{{ comments.length }} bình luận</h3>
                    <p>Quản lý nội dung bình luận và phản hồi của bài viết.</p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" @click="load">
                    <span class="mi" style="font-size: 15px">refresh</span>
                    Tải lại
                </button>
            </div>

            <div v-if="loading" class="empty-state">
                <span class="admin-loading-dot"></span>
                Đang tải bình luận...
            </div>

            <div v-else-if="!rows.length" class="empty-state">
                Bài viết này chưa có bình luận.
            </div>

            <div v-else class="comment-list">
                <article
                    v-for="row in rows"
                    :key="row.comment.id"
                    class="comment-row"
                    :class="{ 'comment-row--reply': row.depth > 0 }"
                    :style="{ marginLeft: row.depth ? '42px' : '0' }"
                >
                    <div class="comment-avatar">
                        <img v-if="row.comment.avatar_url" :src="row.comment.avatar_url" alt="" />
                        <span v-else>{{ initial(row.comment.username) }}</span>
                    </div>
                    <div class="comment-main">
                        <div class="comment-top">
                            <div>
                                <strong>{{ row.comment.username }}</strong>
                                <span class="comment-meta">
                                    ID {{ row.comment.id }}
                                    <template v-if="row.comment.parent_comment_id">
                                        · trả lời #{{ row.comment.parent_comment_id }}
                                    </template>
                                    · {{ formatDate(row.comment.created_at) }}
                                </span>
                            </div>
                            <span class="badge badge-info">
                                {{ row.comment.likes || 0 }} thích
                            </span>
                        </div>

                        <textarea
                            v-if="editingId === row.comment.id"
                            v-model="editingContent"
                            class="form-input comment-editor"
                            rows="4"
                        ></textarea>
                        <p v-else class="comment-content">{{ row.comment.content }}</p>

                        <div class="comment-actions">
                            <template v-if="editingId === row.comment.id">
                                <button type="button" class="btn btn-primary btn-sm" title="Lưu bình luận" @click="saveComment(row.comment)">
                                    <span class="mi" style="font-size: 14px">save</span>
                                    Lưu
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" title="Hủy sửa" @click="cancelEdit">
                                    <span class="mi" style="font-size: 14px">close</span>
                                    Hủy
                                </button>
                            </template>
                            <template v-else>
                                <button type="button" class="btn btn-outline btn-sm" title="Sửa bình luận" @click="startEdit(row.comment)">
                                    <span class="mi" style="font-size: 14px">edit</span>
                                    Sửa
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" title="Xóa bình luận" @click="deleteComment(row.comment)">
                                    <span class="mi" style="font-size: 14px">delete</span>
                                    Xóa
                                </button>
                            </template>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</template>

<script>
import { readJsonResponse } from "../../shared/api";

export default {
    data() {
        return {
            post: null,
            comments: [],
            loading: false,
            error: "",
            success: "",
            editingId: null,
            editingContent: "",
        };
    },
    computed: {
        forumPostHref() {
            return this.post?.forum_post_id
                ? `/forum/${this.post.forum_post_id}`
                : "/forum";
        },
        rows() {
            const byParent = new Map();
            const roots = [];

            this.comments.forEach((comment) => {
                const parentId = comment.parent_comment_id || 0;
                if (!byParent.has(parentId)) byParent.set(parentId, []);
                byParent.get(parentId).push(comment);
            });

            this.comments.forEach((comment) => {
                if (!comment.parent_comment_id) roots.push(comment);
            });

            const result = [];
            const walk = (comment, depth = 0) => {
                result.push({ comment, depth });
                (byParent.get(comment.id) || []).forEach((reply) => walk(reply, depth + 1));
            };

            roots.forEach((comment) => walk(comment));
            return result;
        },
    },
    created() {
        this.load();
    },
    methods: {
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        },
        async load() {
            this.loading = true;
            this.error = "";
            try {
                const res = await fetch(`/admin/api/posts/${this.$route.params.id}/comments`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(res, "Không thể tải bình luận");
                this.post = data.post || null;
                this.comments = data.data || [];
            } catch (err) {
                this.error = err.message || "Không thể tải bình luận";
            } finally {
                this.loading = false;
            }
        },
        startEdit(comment) {
            this.editingId = comment.id;
            this.editingContent = comment.content || "";
            this.error = "";
            this.success = "";
        },
        cancelEdit() {
            this.editingId = null;
            this.editingContent = "";
        },
        async saveComment(comment) {
            this.error = "";
            this.success = "";
            try {
                const res = await fetch(`/admin/api/posts/${this.$route.params.id}/comments/${comment.id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                    body: JSON.stringify({ content: this.editingContent }),
                });
                const data = await readJsonResponse(res, "Không thể cập nhật bình luận");
                this.success = data.message || "Đã cập nhật bình luận.";
                comment.content = this.editingContent.trim();
                this.cancelEdit();
            } catch (err) {
                this.error = err.message || "Không thể cập nhật bình luận";
            }
        },
        async deleteComment(comment) {
            const label = comment.parent_comment_id
                ? `Xóa phản hồi #${comment.id}?`
                : `Xóa bình luận #${comment.id} và các phản hồi của nó?`;
            const ok = await window.adminConfirm({
                title: "Xóa bình luận",
                message: label,
                tone: "danger",
                confirmText: "Xóa",
            });
            if (!ok) return;

            this.error = "";
            this.success = "";
            try {
                const res = await fetch(`/admin/api/posts/${this.$route.params.id}/comments/${comment.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                });
                const data = await readJsonResponse(res, "Không thể xóa bình luận");
                this.success = data.message || "Đã xóa bình luận.";
                await this.load();
            } catch (err) {
                this.error = err.message || "Không thể xóa bình luận";
            }
        },
        initial(name) {
            return String(name || "?").trim().slice(0, 1).toUpperCase();
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
    margin-bottom: 24px;
    gap: 16px;
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
.comments-head p {
    margin: 4px 0 0;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.comment-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: calc(100vh - 260px);
    overflow: auto;
    padding-right: 6px;
}
.comment-row {
    display: flex;
    gap: 12px;
    padding: 14px;
    border: 1px solid var(--ds-border);
    background: var(--ds-body-bg);
    border-radius: 10px;
}
.comment-row--reply {
    border-left: 3px solid var(--ds-primary);
}
.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    flex-shrink: 0;
    overflow: hidden;
    background: rgba(var(--ds-primary-rgb), 0.18);
    color: var(--ds-primary);
    font-weight: 800;
    display: grid;
    place-items: center;
}
.comment-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.comment-main {
    min-width: 0;
    flex: 1;
}
.comment-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}
.comment-top strong {
    color: var(--ds-text-emphasis);
}
.comment-meta {
    display: block;
    color: var(--ds-text-muted);
    font-size: 12px;
    margin-top: 2px;
}
.comment-content {
    margin: 10px 0 12px;
    white-space: pre-wrap;
    word-break: break-word;
    color: var(--ds-text);
}
.comment-editor {
    margin: 10px 0 12px;
}
.comment-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 180px;
    color: var(--ds-text-muted);
}
@media (max-width: 760px) {
    .comment-row {
        margin-left: 0 !important;
    }
    .comment-top {
        flex-direction: column;
    }
}
</style>
