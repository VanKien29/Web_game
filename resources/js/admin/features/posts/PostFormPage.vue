<template>
    <div>
        <div class="page-top">
            <div>
                <h2 class="page-title">
                    {{ isEdit ? "Sửa bài viết" : "Tạo bài viết" }}
                </h2>
                <nav class="breadcrumb">
                    <router-link :to="{ name: 'admin.dashboard' }">Trang chủ</router-link>
                    <span>/</span>
                    <router-link :to="{ name: 'admin.posts' }">Bài viết</router-link>
                    <span>/</span>
                    <span class="current">{{ isEdit ? form.title || "..." : "Tạo mới" }}</span>
                </nav>
            </div>
            <div class="page-top-actions">
                <a
                    v-if="isEdit && form.slug"
                    :href="`/post/${form.slug}`"
                    target="_blank"
                    class="btn btn-outline"
                >
                    <span class="mi" style="font-size: 16px">open_in_new</span>
                    Xem ngoài web
                </a>
                <router-link :to="{ name: 'admin.posts' }" class="btn btn-outline">
                    <span class="mi" style="font-size: 16px">arrow_back</span>
                    Quay lại
                </router-link>
            </div>
        </div>

        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <form class="post-form" @submit.prevent="save">
            <div class="form-layout">
                <main class="form-main">
                    <div class="card">
                        <div class="card-header"><h3>Nội dung chính</h3></div>
                        <div class="form-group">
                            <label class="form-label">Tiêu đề <span class="required">*</span></label>
                            <input
                                v-model="form.title"
                                class="form-input"
                                required
                                maxlength="255"
                                placeholder="Nhập tiêu đề bài viết..."
                                @blur="syncSlug"
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input
                                v-model="form.slug"
                                class="form-input"
                                maxlength="255"
                                placeholder="Tự tạo từ tiêu đề nếu để trống"
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tóm tắt</label>
                            <textarea
                                v-model="form.excerpt"
                                class="form-input"
                                rows="3"
                                maxlength="1000"
                                placeholder="Tóm tắt ngắn, có thể để trống để tự lấy từ nội dung."
                            ></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nội dung</label>
                            <RichTextEditor
                                v-model="form.content"
                                placeholder="Soạn nội dung bài viết..."
                            />
                        </div>
                    </div>
                </main>

                <aside class="form-sidebar">
                    <div class="card">
                        <div class="card-header"><h3>Xuất bản</h3></div>
                        <div class="form-group">
                            <label class="form-label">Trạng thái</label>
                            <select v-model="form.status" class="form-input">
                                <option value="published">Đã xuất bản</option>
                                <option value="draft">Bản nháp</option>
                                <option value="archived">Lưu trữ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ngày đăng</label>
                            <input v-model="form.published_at" class="form-input" type="datetime-local" />
                        </div>
                        <div class="button-stack">
                            <button class="btn btn-primary btn-block" type="submit" :disabled="saving">
                                <span class="mi" style="font-size: 16px">save</span>
                                {{ saving ? "Đang lưu..." : "Lưu bài viết" }}
                            </button>
                            <router-link
                                v-if="isEdit"
                                :to="{ name: 'admin.posts.comments', params: { id: $route.params.id } }"
                                class="btn btn-outline btn-block"
                            >
                                <span class="mi" style="font-size: 16px">forum</span>
                                Quản lý bình luận
                            </router-link>
                            <button
                                v-if="isEdit"
                                type="button"
                                class="btn btn-danger btn-block"
                                @click="deletePost"
                            >
                                <span class="mi" style="font-size: 16px">delete</span>
                                Xóa bài viết
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h3>Thông tin phụ</h3></div>
                        <div class="form-group">
                            <label class="form-label">Chuyên mục</label>
                            <select v-model="form.category_id" class="form-input">
                                <option value="">Chưa chọn</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ảnh đại diện</label>
                            <input
                                v-model="form.featured_image"
                                class="form-input"
                                placeholder="/assets/..."
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tác giả</label>
                            <input
                                v-model="form.author_username"
                                class="form-input"
                                placeholder="admin"
                            />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Avatar tác giả</label>
                            <input
                                v-model="form.author_avatar"
                                class="form-input"
                                placeholder="/assets/..."
                            />
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</template>

<script>
import RichTextEditor from "../../components/RichTextEditor.vue";
import { readJsonResponse } from "../../shared/api";

const emptyForm = () => ({
    title: "",
    slug: "",
    content: "",
    excerpt: "",
    featured_image: "",
    category_id: "",
    author_username: "",
    author_avatar: "",
    status: "published",
    published_at: "",
});

export default {
    components: { RichTextEditor },
    data() {
        return {
            form: emptyForm(),
            categories: [],
            saving: false,
            error: "",
            success: "",
        };
    },
    computed: {
        isEdit() {
            return !!this.$route.params.id;
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
            this.error = "";
            try {
                if (this.isEdit) {
                    const res = await fetch(`/admin/api/posts/${this.$route.params.id}`, {
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                    });
                    const data = await readJsonResponse(res, "Không thể tải bài viết");
                    this.categories = data.categories || [];
                    this.form = this.toForm(data.data || {});
                    return;
                }

                const res = await fetch("/admin/api/posts/categories", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const data = await readJsonResponse(res, "Không thể tải chuyên mục");
                this.categories = data.data || [];
                this.form.published_at = this.toDatetimeLocal(new Date().toISOString());
            } catch (err) {
                this.error = err.message || "Không thể tải dữ liệu";
            }
        },
        toForm(post) {
            return {
                title: post.title || "",
                slug: post.slug || "",
                content: post.content || "",
                excerpt: post.excerpt || "",
                featured_image: post.featured_image || "",
                category_id: post.category_id || "",
                author_username: post.author_username || "",
                author_avatar: post.author_avatar || "",
                status: post.status || "draft",
                published_at: this.toDatetimeLocal(post.published_at),
            };
        },
        toDatetimeLocal(value) {
            if (!value) return "";
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return "";
            const pad = (num) => String(num).padStart(2, "0");
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        },
        slugify(value) {
            return String(value || "")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/đ/g, "d")
                .replace(/Đ/g, "D")
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-+|-+$/g, "");
        },
        syncSlug() {
            if (!this.form.slug) {
                this.form.slug = this.slugify(this.form.title);
            }
        },
        payload() {
            return {
                ...this.form,
                category_id: this.form.category_id || null,
                published_at: this.form.published_at || null,
            };
        },
        async save() {
            this.error = "";
            this.success = "";
            this.saving = true;
            try {
                const url = this.isEdit ? `/admin/api/posts/${this.$route.params.id}` : "/admin/api/posts";
                const res = await fetch(url, {
                    method: this.isEdit ? "PUT" : "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                    body: JSON.stringify(this.payload()),
                });
                const data = await readJsonResponse(res, "Không thể lưu bài viết");
                this.success = data.message || "Đã lưu bài viết.";
                if (!this.isEdit && data.id) {
                    this.$router.replace({ name: "admin.posts.edit", params: { id: data.id } });
                } else if (data.data) {
                    this.form = this.toForm(data.data);
                }
            } catch (err) {
                this.error = err.message || "Không thể lưu bài viết";
            } finally {
                this.saving = false;
            }
        },
        async deletePost() {
            if (!this.isEdit) return;
            const ok = await window.adminConfirm({
                title: "Xóa bài viết",
                message: `Xóa bài viết "${this.form.title}" và toàn bộ bình luận?`,
                tone: "danger",
                confirmText: "Xóa",
            });
            if (!ok) return;
            this.error = "";
            try {
                const res = await fetch(`/admin/api/posts/${this.$route.params.id}`, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrfToken(),
                    },
                });
                await readJsonResponse(res, "Không thể xóa bài viết");
                this.$router.push({ name: "admin.posts" });
            } catch (err) {
                this.error = err.message || "Không thể xóa bài viết";
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
.form-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 20px;
    align-items: start;
}
.form-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 92px;
}
.content-editor {
    min-height: 420px;
    font-family: "SF Mono", Consolas, monospace;
    line-height: 1.55;
}
.button-stack {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.required {
    color: var(--ds-danger);
}
@media (max-width: 1100px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
    .form-sidebar {
        position: static;
    }
}
</style>
