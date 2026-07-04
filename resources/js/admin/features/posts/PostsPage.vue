<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">Quản lý bài viết</h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <span class="current">Bài viết</span>
                </nav>
            </div>
            <router-link :to="{ name: 'admin.posts.create' }" class="btn btn-primary admin-fab" title="Tạo bài viết">
                <span class="mi" style="font-size: 16px">add</span>
                Tạo bài viết
            </router-link>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="filter-bar">
            <form class="search-form" @submit.prevent="loadPage(1)">
                <div class="search-input-wrap">
                    <span class="mi search-icon">search</span>
                    <input
                        v-model="search"
                        class="form-input search-input"
                        placeholder="Tìm tiêu đề, slug, tác giả..."
                    />
                </div>
                <select v-model="statusFilter" class="form-input compact-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="published">Đã xuất bản</option>
                    <option value="draft">Bản nháp</option>
                    <option value="archived">Đã lưu trữ</option>
                </select>
                <select v-model="categoryFilter" class="form-input compact-filter">
                    <option value="">Tất cả chuyên mục</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
                <button class="btn btn-primary btn-sm" type="submit">
                    Tìm kiếm
                </button>
            </form>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bài viết</th>
                            <th>Chuyên mục</th>
                            <th>Thống kê</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="post in posts" :key="post.id">
                            <td>{{ post.id }}</td>
                            <td>
                                <div class="post-title">{{ post.title }}</div>
                                <div class="post-sub">
                                    <span class="mi">link</span>
                                    {{ post.slug }}
                                </div>
                                <div class="post-sub">
                                    <span class="mi">person</span>
                                    {{ post.author_username || "admin" }}
                                </div>
                            </td>
                            <td>{{ post.category?.name || "Chưa chọn" }}</td>
                            <td>
                                <div class="stat-line">
                                    <span class="mi">visibility</span>
                                    {{ post.views || 0 }} xem
                                </div>
                                <div class="stat-line">
                                    <span class="mi">thumb_up</span>
                                    {{ post.likes || 0 }} thích
                                </div>
                                <div class="stat-line">
                                    <span class="mi">comment</span>
                                    {{ post.comments_count || 0 }} bình luận
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="statusBadge(post.status)">
                                    {{ statusLabel(post.status) }}
                                </span>
                            </td>
                            <td>
                                <div>{{ formatDate(post.published_at || post.created_at) }}</div>
                                <div class="post-sub">Tạo: {{ formatDate(post.created_at) }}</div>
                            </td>
                            <td class="action-cell">
                                <div class="row-actions">
                                    <a
                                        :href="`/post/${post.slug}`"
                                        target="_blank"
                                        class="btn btn-outline btn-sm"
                                        title="Xem ngoài web"
                                    >
                                        <span class="mi" style="font-size: 14px">open_in_new</span>
                                        Xem
                                    </a>
                                    <router-link
                                        :to="{ name: 'admin.posts.comments', params: { id: post.id } }"
                                        class="btn btn-outline btn-sm"
                                        title="Xem bình luận"
                                    >
                                        <span class="mi" style="font-size: 14px">forum</span>
                                        Bình luận
                                    </router-link>
                                    <router-link
                                        :to="{ name: 'admin.posts.edit', params: { id: post.id } }"
                                        class="btn btn-primary btn-sm"
                                        title="Sửa bài viết"
                                    >
                                        <span class="mi" style="font-size: 14px">edit</span>
                                        Sửa
                                    </router-link>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        title="Xóa bài viết"
                                        @click="deletePost(post)"
                                    >
                                        <span class="mi" style="font-size: 14px">delete</span>
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!posts.length && !loading">
                            <td colspan="7" class="empty-cell">
                                Chưa có bài viết nào.
                            </td>
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
                <button :disabled="page <= 1" @click="goToPage(page - 1)">
                    &laquo;
                </button>
                <button
                    v-for="p in totalPages"
                    :key="p"
                    :class="{ active: p === page }"
                    @click="goToPage(p)"
                >
                    {{ p }}
                </button>
                <button :disabled="page >= totalPages" @click="goToPage(page + 1)">
                    &raquo;
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { readJsonResponse } from "../../shared/api";

export default {
    data() {
        return {
            posts: [],
            categories: [],
            search: "",
            statusFilter: "",
            categoryFilter: "",
            page: 1,
            totalPages: 1,
            loading: false,
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
        normalizePage(page) {
            const value = Number(page);
            if (!Number.isFinite(value)) return 1;
            return Math.min(Math.max(1, Math.trunc(value)), this.totalPages || 1);
        },
        goToPage(page) {
            this.loadPage(this.normalizePage(page));
        },
        async loadPage(page) {
            this.loading = true;
            this.error = "";
            this.page = this.normalizePage(page);
            try {
                const params = new URLSearchParams({
                    page: String(this.page),
                    search: this.search,
                });
                if (this.statusFilter) params.set("status", this.statusFilter);
                if (this.categoryFilter) params.set("category_id", this.categoryFilter);

                const res = await fetch(`/admin/api/posts?${params.toString()}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(res, "Không thể tải bài viết");
                this.posts = data.data || [];
                this.categories = data.categories || this.categories;
                this.totalPages = data.total_pages || 1;
                this.page = this.normalizePage(data.page || this.page);
            } catch (err) {
                this.error = err.message || "Không thể tải bài viết";
            } finally {
                this.loading = false;
            }
        },
        async deletePost(post) {
            const ok = await window.adminConfirm({
                title: "Xóa bài viết",
                message: `Xóa bài viết "${post.title}" và toàn bộ bình luận của nó?`,
                tone: "danger",
                confirmText: "Xóa",
            });
            if (!ok) return;
            this.error = "";
            this.success = "";
            try {
                const res = await fetch(`/admin/api/posts/${post.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                });
                const data = await readJsonResponse(res, "Không thể xóa bài viết");
                this.success = data.message || "Đã xóa bài viết.";
                await this.loadPage(this.page);
            } catch (err) {
                this.error = err.message || "Không thể xóa bài viết";
            }
        },
        statusLabel(status) {
            return {
                published: "Đã xuất bản",
                draft: "Bản nháp",
                archived: "Lưu trữ",
            }[status] || status || "Không rõ";
        },
        statusBadge(status) {
            return {
                published: "badge-success",
                draft: "badge-warning",
                archived: "badge-danger",
            }[status] || "badge-info";
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
.filter-bar {
    margin-bottom: 20px;
}
.search-form {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) 180px 200px auto;
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
.post-title {
    color: var(--ds-text-emphasis);
    font-weight: 700;
    margin-bottom: 5px;
}
.post-sub,
.stat-line {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--ds-text-muted);
    font-size: 12px;
    line-height: 1.5;
}
.post-sub .mi,
.stat-line .mi {
    font-size: 14px;
}
.row-actions {
    display: inline-flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.empty-cell {
    text-align: center !important;
    color: var(--ds-text-muted) !important;
    padding: 32px !important;
}
@media (max-width: 1080px) {
    .search-form {
        grid-template-columns: 1fr;
    }
}
</style>
