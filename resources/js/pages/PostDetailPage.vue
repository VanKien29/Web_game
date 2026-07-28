<template>
    <div class="client-page client-page--post post-detail-page">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span v-if="post">{{ post.title }}</span>
            <span v-else>Đang tải...</span>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="pp-state">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
            <span>Đang tải...</span>
        </div>

        <!-- Not found -->
        <div v-else-if="!post" class="pp-state">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Bài viết không tồn tại.</span>
        </div>
        <p v-else-if="!post" class="client-empty">Bài viết không tồn tại.</p>

        <template v-else>
            <div class="post-layout">
                <aside class="post-rail">
                    <div class="post-panel post-author-card">
                        <div class="post-avatar">
                            <img
                                v-if="post.author_avatar"
                                :src="post.author_avatar"
                                alt=""
                            />
                            <span v-else>{{
                                commentInitial(
                                    post.author_username || post.author || "A",
                                )
                            }}</span>
                        </div>
                        <div>
                            <strong>{{
                                post.author_username || post.author || "Admin"
                            }}</strong>
                            <span>{{ post.category?.name || "Bài viết" }}</span>
                        </div>
                    </div>

                    <div class="post-panel post-rail-stats">
                        <div>
                            <strong>{{
                                compactNumber(post.views || 0)
                            }}</strong>
                            <span>Lượt xem</span>
                        </div>
                        <div>
                            <strong>{{
                                compactNumber(engagement.likes || 0)
                            }}</strong>
                            <span>Lượt thích</span>
                        </div>
                        <div>
                            <strong>{{
                                compactNumber(engagement.comments || 0)
                            }}</strong>
                            <span>Bình luận</span>
                        </div>
                    </div>

                    <nav class="post-panel post-rail-nav">
                        <router-link to="/">
                            <i class="fa-solid fa-house"></i>
                            Trang chủ
                        </router-link>
                        <router-link to="/forum">
                            <i class="fa-regular fa-comments"></i>
                            Diễn đàn
                        </router-link>
                        <router-link to="/giftcode">
                            <i class="fa-solid fa-gift"></i>
                            Giftcode
                        </router-link>
                        <router-link to="/bxh">
                            <i class="fa-solid fa-ranking-star"></i>
                            Đua top
                        </router-link>
                    </nav>
                </aside>

                <main class="post-feed">
                    <article class="post-panel post-detail">
                        <header class="post-hero">
                            <div class="post-hero__label">
                                <span>{{
                                    post.category?.name || "Bài viết"
                                }}</span>
                            </div>
                            <h1>{{ post.title }}</h1>

                            <div class="post-meta">
                                <span>
                                    <i class="fa-solid fa-user-pen"></i>
                                    {{
                                        post.author_username ||
                                        post.author ||
                                        "Admin"
                                    }}
                                </span>
                                <span>
                                    <i class="fa-regular fa-clock"></i>
                                    {{
                                        formatDate(
                                            post.published_at ||
                                                post.created_at,
                                        )
                                    }}
                                </span>
                                <span>
                                    <i class="fa-regular fa-eye"></i>
                                    {{ compactNumber(post.views || 0) }} lượt
                                    xem
                                </span>
                            </div>
                        </header>

                        <div v-if="post.excerpt" class="post-excerpt">
                            {{ post.excerpt }}
                        </div>

                        <figure
                            v-if="post.featured_image"
                            class="post-hero__image post-content-image"
                        >
                            <img :src="post.featured_image" :alt="post.title" />
                        </figure>

                        <div v-if="!showFullContent" class="content-preview">
                            <div
                                class="content-preview-text"
                                v-html="post.content"
                            ></div>
                            <div class="content-read-more">
                                <button
                                    class="content-read-more-btn"
                                    @click="showFullContent = true"
                                >
                                    <i class="fa-solid fa-chevron-down"></i>
                                    Xem thêm
                                </button>
                            </div>
                        </div>
                        <div
                            v-else
                            class="post-content"
                            v-html="post.content"
                        ></div>
                    </article>

                    <section class="post-panel post-discussion">
                        <div class="post-summary">
                            <span>
                                <span class="post-like-dot">
                                    <i class="fa-solid fa-thumbs-up"></i>
                                </span>
                                <span class="pp-stats-count">{{ engagement.likes }}</span>
                            </span>
                            <button class="pp-stats-btn" @click="focusCommentBox">
                                {{ engagement.comments }} bình luận
                            </button>
                        </div>

                        <div class="post-actions">
                            <button
                                type="button"
                                class="post-action"
                                :class="{ active: engagement.liked }"
                                @click="togglePostLike"
                            >
                                <i :class="engagement.liked ? 'fa-solid fa-thumbs-up' : 'fa-regular fa-thumbs-up'"></i>
                                Thích
                            </button>
                            <button
                                type="button"
                                class="post-action"
                                @click="focusCommentBox"
                            >
                                <i class="fa-regular fa-comment"></i>
                                Bình luận
                            </button>
                            <button
                                type="button"
                                class="post-action"
                                @click="sharePost"
                            >
                                <i class="fa-solid fa-share"></i>
                                Chia sẻ
                            </button>
                        </div>

                        <div v-if="interactionMessage" class="post-message">
                            {{ interactionMessage }}
                        </div>

                        <form
                            class="post-comment-form"
                            @submit.prevent="submitComment()"
                        >
                            <div class="post-avatar post-avatar--comment">
                                <img
                                    v-if="currentAvatarUrl"
                                    :src="currentAvatarUrl"
                                    alt=""
                                />
                                <span v-else>{{ currentInitial }}</span>
                            </div>
                            <div class="post-comment-form__body">
                                <textarea
                                    ref="commentInput"
                                    v-model="commentText"
                                    rows="2"
                                    placeholder="Viết bình luận..."
                                    @keydown.enter.exact.prevent="
                                        submitComment()
                                    "
                                ></textarea>
                                <div class="post-comment-form__foot">
                                    <span
                                        >Enter để gửi, Shift + Enter để xuống
                                        dòng</span
                                    >
                                    <button
                                        type="submit"
                                        :disabled="commentSubmitting"
                                    >
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="post-comments-shell">
                            <div class="post-comments-head">
                                <div>
                                    <strong>Bình luận</strong>
                                    <span
                                        >{{ comments.length }} cuộc trò
                                        chuyện</span
                                    >
                                </div>
                                <i class="fa-regular fa-comments"></i>
                            </div>

                            <div class="post-comments">
                                <p v-if="!comments.length" class="post-empty">
                                    <i class="fa-regular fa-comments"></i>
                                    Chưa có bình luận nào.
                                </p>

                                <div
                                    v-for="comment in comments"
                                    :key="comment.id"
                                    class="post-comment-thread"
                                >
                                    <div class="post-comment">
                                        <div
                                            class="post-avatar post-avatar--comment"
                                        >
                                            <img
                                                v-if="comment.avatar_url"
                                                :src="comment.avatar_url"
                                                alt=""
                                            />
                                            <span v-else>{{
                                                commentInitial(comment.username)
                                            }}</span>
                                        </div>
                                        <div class="post-comment__main">
                                            <div class="post-bubble">
                                                <strong>{{
                                                    comment.username
                                                }}</strong>
                                                <p
                                                    v-html="
                                                        formatMentionText(
                                                            commentContent(
                                                                comment,
                                                            ),
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                            <div class="post-comment__actions">
                                                <button
                                                    type="button"
                                                    :class="{
                                                        active: comment.liked,
                                                    }"
                                                    @click="
                                                        toggleCommentLike(
                                                            comment,
                                                        )
                                                    "
                                                >
                                                    <i
                                                        class="fa-solid fa-thumbs-up"
                                                    ></i>
                                                    Thích
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="startReply(comment)"
                                                >
                                                    <i
                                                        class="fa-solid fa-reply"
                                                    ></i>
                                                    Phản hồi
                                                </button>
                                                <span>
                                                    <i
                                                        class="fa-regular fa-clock"
                                                    ></i>
                                                    {{
                                                        formatRelative(
                                                            comment.created_at,
                                                        )
                                                    }}
                                                </span>
                                                <span v-if="comment.likes">
                                                    <i
                                                        class="fa-solid fa-heart"
                                                    ></i>
                                                    {{ comment.likes }} thích
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="comment.replies?.length"
                                        class="post-replies"
                                    >
                                        <div
                                            v-for="reply in comment.replies"
                                            :key="reply.id"
                                            class="post-comment post-comment--reply"
                                        >
                                            <div
                                                class="post-avatar post-avatar--comment"
                                            >
                                                <img
                                                    v-if="reply.avatar_url"
                                                    :src="reply.avatar_url"
                                                    alt=""
                                                />
                                                <span v-else>{{
                                                    commentInitial(
                                                        reply.username,
                                                    )
                                                }}</span>
                                            </div>
                                            <div class="post-comment__main">
                                                <div class="post-bubble">
                                                    <strong>{{
                                                        reply.username
                                                    }}</strong>
                                                    <p
                                                        v-html="
                                                            formatMentionText(
                                                                commentContent(
                                                                    reply,
                                                                ),
                                                            )
                                                        "
                                                    ></p>
                                                </div>
                                                <div
                                                    class="post-comment__actions"
                                                >
                                                    <button
                                                        type="button"
                                                        :class="{
                                                            active: reply.liked,
                                                        }"
                                                        @click="
                                                            toggleCommentLike(
                                                                reply,
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            class="fa-solid fa-thumbs-up"
                                                        ></i>
                                                        Thích
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="
                                                            startReply(reply)
                                                        "
                                                    >
                                                        <i
                                                            class="fa-solid fa-reply"
                                                        ></i>
                                                        Phản hồi
                                                    </button>
                                                    <span>
                                                        <i
                                                            class="fa-regular fa-clock"
                                                        ></i>
                                                        {{
                                                            formatRelative(
                                                                reply.created_at,
                                                            )
                                                        }}
                                                    </span>
                                                    <span v-if="reply.likes">
                                                        <i
                                                            class="fa-solid fa-heart"
                                                        ></i>
                                                        {{ reply.likes }} thích
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form
                                        v-if="replyingTo === comment.id"
                                        class="post-comment-form post-comment-form--reply"
                                        @submit.prevent="
                                            submitComment(comment.id)
                                        "
                                    >
                                        <div
                                            class="post-avatar post-avatar--comment"
                                        >
                                            <img
                                                v-if="currentAvatarUrl"
                                                :src="currentAvatarUrl"
                                                alt=""
                                            />
                                            <span v-else>{{
                                                currentInitial
                                            }}</span>
                                        </div>
                                        <div class="post-comment-form__body">
                                            <textarea
                                                v-model="replyText"
                                                rows="1"
                                                :placeholder="
                                                    replyPlaceholder(comment)
                                                "
                                                @keydown.enter.exact.prevent="
                                                    submitComment(comment.id)
                                                "
                                            ></textarea>
                                            <div
                                                class="post-comment-form__foot"
                                            >
                                                <button
                                                    type="button"
                                                    class="plain"
                                                    @click="cancelReply"
                                                >
                                                    Hủy
                                                </button>
                                                <button
                                                    type="submit"
                                                    :disabled="
                                                        commentSubmitting
                                                    "
                                                >
                                                    Trả lời
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </template>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "PostDetailPage",
    data() {
        return {
            post: null,
            loading: true,
            showFullContent: false,
            engagement: { likes: 0, comments: 0, liked: false },
            comments: [],
            commentText: "",
            replyText: "",
            replyingTo: null,
            replyTargetUsername: "",
            commentSubmitting: false,
            interactionMessage: "",
            currentAvatarUrl: "",
        };
    },
    computed: {
        isLoggedIn() { return !!localStorage.getItem("token"); },
        currentUsername() {
            try {
                return (
                    JSON.parse(localStorage.getItem("user") || "{}").username ||
                    ""
                );
            } catch {
                return "";
            }
        },
        currentInitial() {
            return (this.currentUsername || "U").slice(0, 1).toUpperCase();
        },
    },
    methods: {
        formatDate(d) {
            return d ? new Date(d).toLocaleDateString("vi-VN") : "";
        },
        formatRelative(date) {
            const value = date ? new Date(date).getTime() : 0;
            const diff = Math.max(1, Math.floor((Date.now() - value) / 1000));
            if (diff < 60) return "Vừa xong";
            if (diff < 3600) return `${Math.floor(diff / 60)} phút`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} giờ`;
            return new Date(date).toLocaleDateString("vi-VN");
        },
        commentInitial(name) {
            return String(name || "?")
                .trim()
                .slice(0, 1)
                .toUpperCase();
        },
        commentContent(comment) {
            return String(
                comment?.content || comment?.body || comment?.message || "",
            );
        },
        replyPlaceholder(comment) {
            return `Trả lời ${this.replyTargetUsername || comment.username}...`;
        },
        withMentionPrefix(value, username) {
            const mention = this.mentionToken(username);
            const text = String(value || "").trimStart();
            if (!mention || text.startsWith(mention)) return text;
            return text ? `${mention} ${text}` : `${mention} `;
        },
        mentionToken(username) {
            const clean = String(username || "")
                .replace(/\s+/g, "")
                .replace(/[^\p{L}\p{N}_.-]/gu, "")
                .slice(0, 32);
            return clean ? `@${clean}` : "";
        },
        formatMentionText(value) {
            const escaped = this.escapeHtml(value);
            return escaped.replace(
                /(^|[\s>])(@[\p{L}\p{N}_.-]{1,32})/gu,
                '$1<span class="post-mention">$2</span>',
            );
        },
        escapeHtml(value) {
            return String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },
        compactNumber(value) {
            const number = Number(value || 0);
            if (number >= 1000000) return `${(number / 1000000).toFixed(1)}M`;
            if (number >= 1000) return `${(number / 1000).toFixed(1)}K`;
            return String(number);
        },
        authHeaders() {
            const token = localStorage.getItem("token");
            return token
                ? { headers: { Authorization: `Bearer ${token}` } }
                : {};
        },
        showMsg(msg) {
            this.interactionMessage = msg;
            clearTimeout(this._msgTimer);
            this._msgTimer = setTimeout(() => (this.interactionMessage = ""), 3500);
        },
        requireLogin() {
            if (this.isLoggedIn) return true;
            this.showMsg("Bạn cần đăng nhập để tương tác.");
            return false;
        },
        async loadComments() {
            try {
                const slug = this.$route.params.slug;
                const { data } = await axios.get(`/api/posts/${slug}/comments`, this.authHeaders());
                if (data.ok) {
                    this.comments = data.data || [];
                    if (data.engagement) this.engagement = data.engagement;
                }
            } catch (e) { console.error("loadComments", e); }
        },
        async loadCurrentProfileAvatar() {
            if (!this.isLoggedIn) return;
            try {
                const { data } = await axios.get(
                    "/api/profile",
                    this.authHeaders(),
                );
                this.currentAvatarUrl = data?.data?.player?.avatar_url || "";
            } catch {
                this.currentAvatarUrl = "";
            }
        },
        async togglePostLike() {
            if (!this.requireLogin()) return;
            const slug = this.$route.params.slug;
            const { data } = await axios.post(`/api/posts/${slug}/like`, {}, this.authHeaders());
            if (data.ok) { this.engagement.liked = data.liked; this.engagement.likes = data.likes; }
        },
        async toggleCommentLike(comment) {
            if (!this.requireLogin()) return;
            const { data } = await axios.post(`/api/comments/${comment.id}/like`, {}, this.authHeaders());
            if (data.ok) { comment.liked = data.liked; comment.likes = data.likes; }
        },
        async submitComment(parentId = null) {
            if (!this.requireLogin()) return;
            const content = parentId
                ? this.replyText.trim()
                : this.commentText.trim();
            if (!content) return;
            this.commentSubmitting = true;
            try {
                const slug = this.$route.params.slug;
                const { data } = await axios.post(
                    `/api/posts/${slug}/comments`,
                    { content, parent_comment_id: parentId },
                    this.authHeaders()
                );
                if (data.ok) {
                    if (parentId) {
                        this.replyText = "";
                        this.replyingTo = null;
                        this.replyTargetUsername = "";
                    } else {
                        this.commentText = "";
                    }
                    await this.loadComments();
                }
            } catch (err) {
                this.interactionMessage =
                    err.response?.data?.message ||
                    "Không thể gửi bình luận lúc này.";
            } finally {
                this.commentSubmitting = false;
            }
        },
        startReply(comment) {
            if (!this.requireLogin()) return;
            this.replyingTo = comment.parent_comment_id || comment.id;
            this.replyTargetUsername = comment.username || "";
            this.replyText = this.withMentionPrefix(
                this.replyText,
                comment.username,
            );
        },
        cancelReply() {
            this.replyingTo = null;
            this.replyText = "";
            this.replyTargetUsername = "";
        },
        focusCommentBox() {
            this.$nextTick(() => this.$refs.commentInput?.focus());
        },
        async sharePost() {
            const url = window.location.href;
            try {
                if (navigator.share) await navigator.share({ title: this.post.title, url });
                else { await navigator.clipboard.writeText(url); this.showMsg("Đã sao chép liên kết!"); }
            } catch { this.showMsg("Không thể chia sẻ lúc này."); }
        },
    },
    async mounted() {
        const slug = this.$route.params.slug;
        try {
            this.loadCurrentProfileAvatar();
            const [{ data }] = await Promise.all([
                axios.get(`/api/posts/${slug}`),
            ]);
            if (data.ok) {
                this.post = data.data;
                await this.loadComments();
            }
        } catch (err) { console.error(err); }
        finally { this.loading = false; }
    },
};
</script>

<style scoped>
.post-detail-page {
    width: min(1180px, calc(100% - 36px));
}

.post-detail-page .client-breadcrumb {
    margin-bottom: 10px;
}

.post-detail-page .post-wrapper {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 16px;
    align-items: start;
}

.post-detail-page .post-detail,
.post-detail-page .sidebar-widget {
    background: rgba(255, 248, 232, 0.96) !important;
    border: 1px solid rgba(123, 76, 32, 0.22) !important;
    border-radius: 8px !important;
    color: #3d291a !important;
    box-shadow: 0 18px 42px rgba(8, 16, 34, 0.22) !important;
}

.post-detail-page .post-detail {
    padding: 0 !important;
    overflow: hidden;
}

.post-hero {
    padding: 24px 26px 18px;
    background:
        linear-gradient(
            180deg,
            rgba(255, 240, 202, 0.95),
            rgba(255, 248, 232, 0.98)
        ),
        url("/assets/pixel/nro-page-map.webp") center bottom / cover;
    border-bottom: 1px solid rgba(123, 76, 32, 0.16);
}

.post-hero__label {
    display: flex;
    margin-bottom: 10px;
}

.post-hero__label span {
    min-height: 26px;
    display: inline-flex;
    align-items: center;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(198, 123, 33, 0.16);
    color: #914914;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
}

.post-detail-page .post-detail h1 {
    margin: 0;
    color: #2f1e12 !important;
    font-size: clamp(28px, 4vw, 44px) !important;
    line-height: 1.12 !important;
    text-shadow: none !important;
}

.post-detail-page .post-meta {
    margin-top: 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.post-detail-page .post-meta span {
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(106, 67, 28, 0.08);
    color: #6f5238 !important;
    font-size: 13px;
    font-weight: 800;
}

.post-hero__image {
    margin: 18px 0 0;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid rgba(123, 76, 32, 0.18);
    background: rgba(0, 0, 0, 0.08);
    aspect-ratio: 16 / 7;
}

.post-hero__image img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.post-excerpt {
    margin: 18px 26px 0;
    padding: 14px 16px;
    border-left: 4px solid rgba(198, 123, 33, 0.62);
    border-radius: 0 8px 8px 0;
    background: rgba(198, 123, 33, 0.09);
    color: #5e432c;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.55;
}

.post-detail-page .content-preview,
.post-detail-page .post-content,
.post-detail-page .fb-engagement {
    margin-left: 26px !important;
    margin-right: 26px !important;
}

.post-detail-page .content-preview,
.post-detail-page .post-content {
    margin-top: 20px !important;
    color: #332215 !important;
    font-size: 16px;
    line-height: 1.78;
}

.post-detail-page .content-preview-text,
.post-detail-page .post-content {
    color: #332215 !important;
}

.post-detail-page .post-content :deep(img),
.post-detail-page .content-preview :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.post-detail-page .content-read-more-btn,
.post-detail-page .fb-composer__foot button {
    border-radius: 8px !important;
    background: linear-gradient(180deg, #e8bc74, #c77b21) !important;
    color: #fff8e8 !important;
    font-weight: 900;
}

.post-detail-page .fb-engagement {
    margin-top: 22px !important;
    margin-bottom: 26px !important;
    padding-top: 16px;
    border-top: 1px solid rgba(123, 76, 32, 0.16);
}

.post-detail-page .post-discussion {
    display: grid;
    gap: 12px;
}

.post-detail-page .fb-engagement__summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    color: #7a624a !important;
    font-size: 13px;
}

.post-detail-page .fb-engagement__summary button {
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.post-detail-page .fb-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
    border-radius: 8px;
    background: rgba(106, 67, 28, 0.06);
    padding: 6px;
}

.post-detail-page .fb-action {
    min-height: 40px;
    border-radius: 7px !important;
}

.post-detail-page .fb-action:hover,
.post-detail-page .fb-action.active {
    background: rgba(198, 123, 33, 0.14) !important;
    color: #a65316 !important;
}

.post-detail-page .fb-composer {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr);
    gap: 10px;
    background: rgba(255, 253, 247, 0.72);
    border: 1px solid rgba(123, 76, 32, 0.14);
    border-radius: 8px;
    padding: 10px;
}

.post-detail-page .post-comment-form__body {
    min-width: 0;
}

.post-detail-page .fb-composer textarea {
    width: 100%;
    min-height: 44px;
    resize: vertical;
    border-radius: 8px !important;
    background: #fffdf7 !important;
    border-color: rgba(123, 76, 32, 0.18) !important;
    color: #332215 !important;
    line-height: 1.45;
    padding: 10px 12px !important;
}

.post-detail-page .fb-composer textarea::placeholder {
    color: rgba(93, 66, 43, 0.46) !important;
}

.post-detail-page .fb-composer__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
}

.post-detail-page .fb-composer__foot span {
    color: #8a7056 !important;
    font-size: 12px;
}

.post-detail-page .fb-composer__foot .plain {
    background: rgba(106, 67, 28, 0.08) !important;
    color: #6b523b !important;
}

.post-detail-page .fb-comments-shell {
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid rgba(123, 76, 32, 0.14);
    background: rgba(255, 253, 247, 0.62);
}

.post-detail-page .fb-comments-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid rgba(123, 76, 32, 0.12);
    background: rgba(255, 248, 232, 0.75);
}

.post-detail-page .fb-comments-head strong {
    display: block;
    color: #2f1e12 !important;
}

.post-detail-page .fb-comments-head span {
    color: #7a624a !important;
    font-size: 12px;
}

.post-detail-page .fb-comments-head > i {
    color: #a65316;
}

.post-detail-page .fb-comments {
    max-height: none !important;
    overflow: visible !important;
    display: grid;
    gap: 10px;
    padding: 12px;
}

.post-detail-page .fb-empty {
    min-height: 110px;
    display: grid;
    place-items: center;
    gap: 8px;
    color: #8a7056 !important;
    text-align: center;
}

.post-detail-page .fb-comment-thread {
    display: grid;
    gap: 8px;
}

.post-detail-page .fb-comment {
    display: flex;
    align-items: flex-start;
    gap: 9px;
}

.post-detail-page .fb-comment__main {
    min-width: 0;
    flex: 1;
}

.post-detail-page .fb-bubble {
    width: fit-content;
    max-width: min(100%, 660px);
    padding: 9px 11px !important;
    background: rgba(106, 67, 28, 0.08) !important;
    color: #332215 !important;
    border-radius: 8px !important;
}

.post-detail-page .fb-bubble strong {
    display: block;
    margin-bottom: 3px;
    color: #2f1e12 !important;
    font-size: 13px;
}

.post-detail-page .fb-bubble p {
    margin: 0;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    color: #3e2b1d !important;
}

.post-detail-page .fb-bubble :deep(.post-mention) {
    color: #a65316;
    font-weight: 900;
}

.post-detail-page .fb-comment__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    align-items: center;
    padding: 4px 0 0 10px;
    color: #7a624a !important;
    font-size: 12px;
}

.post-detail-page .fb-comment__actions button {
    border: 0;
    background: transparent;
    color: inherit;
    font-weight: 800;
    cursor: pointer;
    padding: 0;
}

.post-detail-page .fb-comment__actions button:hover,
.post-detail-page .fb-comment__actions button.active {
    color: #a65316 !important;
}

.post-detail-page .fb-replies {
    display: grid;
    gap: 8px;
    margin-left: 46px;
    padding-left: 12px;
    border-left: 2px solid rgba(123, 76, 32, 0.13);
}

.post-detail-page .fb-composer--reply {
    margin-left: 46px;
    grid-template-columns: 34px minmax(0, 1fr);
    background: rgba(106, 67, 28, 0.045);
}

.post-detail-page .fb-avatar {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #f1d292 !important;
    color: #4c2e13 !important;
    font-weight: 900;
    overflow: hidden;
}

.post-detail-page .fb-avatar--small {
    width: 34px;
    height: 34px;
    font-size: 13px;
}

.post-detail-page .fb-avatar img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 3px;
    box-sizing: border-box;
}

.post-detail-page .post-sidebar {
    position: sticky;
    top: 92px;
    display: grid;
    gap: 12px;
}

.post-detail-page .sidebar-widget {
    padding: 16px !important;
}

.post-detail-page .sidebar-widget h4 {
    color: #2f1e12 !important;
    margin: 0 0 12px !important;
}

.post-detail-page .widget-link,
.post-detail-page .sidebar-widget p {
    color: #624831 !important;
}

.post-detail-page .widget-link {
    min-height: 38px;
    border-radius: 7px;
    padding: 0 10px;
}

.post-detail-page .widget-link:hover {
    background: rgba(198, 123, 33, 0.12) !important;
    color: #a65316 !important;
}

@media (max-width: 980px) {
    .post-detail-page .post-wrapper {
        grid-template-columns: 1fr;
    }

    .post-detail-page .post-sidebar {
        position: static;
    }
}

@media (max-width: 640px) {
    .post-hero {
        padding: 18px;
    }

    .post-detail-page .content-preview,
    .post-detail-page .post-content,
    .post-detail-page .fb-engagement,
    .post-excerpt {
        margin-left: 18px !important;
        margin-right: 18px !important;
    }

    .post-hero__image {
        aspect-ratio: 16 / 9;
    }

    .post-detail-page .fb-actions {
        grid-template-columns: 1fr;
    }

    .post-detail-page .fb-composer,
    .post-detail-page .fb-composer--reply {
        margin-left: 0;
        grid-template-columns: 34px minmax(0, 1fr);
    }

    .post-detail-page .fb-replies {
        margin-left: 24px;
    }

    .post-detail-page .fb-composer__foot {
        align-items: flex-end;
    }

    .post-detail-page .fb-composer__foot span {
        display: none;
    }
}

/* Forum-like post detail layout */
.post-detail-page {
    width: min(1220px, calc(100% - 36px));
}

.post-layout {
    width: 100%;
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.post-feed {
    min-width: 0;
    display: grid;
    gap: 12px;
    margin-top: -67px;
}

.post-rail {
    position: sticky;
    top: 92px;
    display: grid;
    gap: 14px;
}

.post-panel,
.post-detail-page .post-detail {
    background: rgba(255, 248, 232, 0.96) !important;
    border: 1px solid rgba(123, 76, 32, 0.24) !important;
    border-radius: 8px !important;
    box-shadow: 0 18px 40px rgba(58, 28, 10, 0.16) !important;
    color: #3e2b1d !important;
}

.post-author-card {
    padding: 14px;
    display: flex;
    gap: 12px;
    align-items: center;
}

.post-author-card strong {
    display: block;
    color: #3f2615;
    line-height: 1.2;
}

.post-author-card span {
    display: block;
    margin-top: 4px;
    color: #7a624a;
    font-size: 13px;
}

.post-avatar {
    width: 42px;
    height: 42px;
    box-sizing: border-box;
    padding: 3px;
    border-radius: 50%;
    overflow: hidden;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    background: #f1d292;
    color: #4c2e13;
    font-weight: 900;
}

.post-avatar img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.post-avatar--comment {
    width: 34px;
    height: 34px;
    font-size: 13px;
}

.post-rail-stats {
    padding: 8px;
    display: grid;
    gap: 6px;
}

.post-rail-stats div {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
    align-items: center;
    padding: 9px 10px;
    border-radius: 7px;
    background: rgba(106, 67, 28, 0.06);
}

.post-rail-stats strong {
    order: 2;
    color: #9a7552;
    font-size: 13px;
}

.post-rail-stats span {
    color: #4d3623;
    font-weight: 800;
    font-size: 13px;
}

.post-rail-nav {
    padding: 8px;
    display: grid;
    gap: 4px;
}

.post-rail-nav a {
    min-height: 40px;
    display: grid;
    grid-template-columns: 22px 1fr;
    gap: 10px;
    align-items: center;
    padding: 0 10px;
    border-radius: 7px;
    color: #4d3623 !important;
    font-weight: 800;
    text-decoration: none;
}

.post-rail-nav a:hover {
    background: rgba(198, 123, 33, 0.16);
    color: #9a4e13 !important;
}

.post-detail-page .post-detail {
    padding: 0 !important;
    overflow: hidden;
}

.post-hero {
    padding: 24px 26px 18px;
    background:
        linear-gradient(
            180deg,
            rgba(255, 240, 202, 0.95),
            rgba(255, 248, 232, 0.98)
        ),
        url("/assets/pixel/nro-page-map.webp") center bottom / cover;
    border-bottom: 1px solid rgba(123, 76, 32, 0.16);
}

.post-hero__label {
    margin-bottom: 10px;
}

.post-hero__label span {
    min-height: 26px;
    display: inline-flex;
    align-items: center;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(198, 123, 33, 0.16);
    color: #914914;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
}

.post-detail-page .post-detail h1 {
    margin: 0;
    color: #2f1e12 !important;
    font-size: clamp(28px, 4vw, 42px) !important;
    line-height: 1.12 !important;
    text-shadow: none !important;
}

.post-meta {
    margin-top: 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.post-meta span {
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(106, 67, 28, 0.08);
    color: #6f5238 !important;
    font-size: 13px;
    font-weight: 800;
}

.post-hero__image {
    margin: 18px 0 0;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid rgba(123, 76, 32, 0.18);
    background: rgba(0, 0, 0, 0.08);
    aspect-ratio: 16 / 7;
}

.post-hero__image img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.post-excerpt,
.post-detail-page .content-preview,
.post-detail-page .post-content {
    margin-left: 26px !important;
    margin-right: 26px !important;
}

.post-excerpt {
    margin-top: 18px;
    padding: 14px 16px;
    border-left: 4px solid rgba(198, 123, 33, 0.62);
    border-radius: 0 8px 8px 0;
    background: rgba(198, 123, 33, 0.09);
    color: #5e432c;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.55;
}

.post-detail-page .content-preview,
.post-detail-page .post-content {
    margin-top: 20px !important;
    margin-bottom: 26px !important;
    color: #332215 !important;
    font-size: 16px;
    line-height: 1.78;
}

.post-detail-page .content-preview-text,
.post-detail-page .post-content {
    color: #332215 !important;
}

.post-detail-page .post-content :deep(img),
.post-detail-page .content-preview :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.post-summary {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 14px 8px;
    color: #7a624a;
    font-size: 13px;
}

.post-summary button {
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.post-like-dot {
    width: 22px;
    height: 22px;
    display: inline-grid;
    place-items: center;
    margin-right: 4px;
    border-radius: 50%;
    background: #c77b21;
    color: #fff8e8;
}

.post-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
    margin: 0 14px;
    border-top: 1px solid rgba(123, 76, 32, 0.15);
    border-bottom: 1px solid rgba(123, 76, 32, 0.15);
    padding: 6px 0;
}

.post-action {
    min-height: 40px;
    border: 0;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    background: transparent;
    color: #644a32;
    font-weight: 800;
    cursor: pointer;
}

.post-action:hover,
.post-action.active {
    background: rgba(198, 123, 33, 0.13);
    color: #b55e18;
}

.post-message {
    margin: 0 14px;
    padding: 10px 12px;
    border-radius: 8px;
    background: rgba(198, 123, 33, 0.1);
    color: #7a3f0e;
    font-weight: 800;
}

.post-comment-form {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 8px;
    align-items: start;
    margin: 12px 14px 0;
}

.post-comment-form textarea {
    width: 100%;
    min-height: 42px;
    resize: vertical;
    border: 1px solid rgba(123, 76, 32, 0.2);
    background: rgba(255, 253, 247, 0.92);
    border-radius: 8px;
    color: #3e2b1d;
    padding: 10px 12px;
    font: inherit;
    outline: 0;
}

.post-comment-form textarea::placeholder {
    color: rgba(93, 66, 43, 0.46);
}

.post-comment-form__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
}

.post-comment-form__foot span {
    color: #8a7056;
    font-size: 12px;
}

.post-comment-form__foot button {
    min-height: 34px;
    border: 0;
    border-radius: 7px;
    padding: 0 14px;
    background: linear-gradient(180deg, #e8bc74, #c77b21);
    color: #fff8e8;
    font-weight: 900;
    cursor: pointer;
}

.post-comment-form__foot button:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.post-comment-form__foot .plain {
    background: rgba(106, 67, 28, 0.08);
    color: #6b523b;
}

.post-comments-shell {
    margin-top: 12px;
    border-top: 1px solid rgba(123, 76, 32, 0.14);
}

.post-comments-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid rgba(123, 76, 32, 0.12);
    background: rgba(255, 248, 232, 0.75);
}

.post-comments-head strong {
    display: block;
    color: #2f1e12;
}

.post-comments-head span {
    color: #7a624a;
    font-size: 12px;
}

.post-comments-head > i {
    color: #a65316;
}

.post-comments {
    display: grid;
    gap: 10px;
    padding: 12px 14px 14px;
}

.post-empty {
    min-height: 110px;
    display: grid;
    place-items: center;
    gap: 8px;
    color: #8a7056;
    text-align: center;
}

.post-comment-thread {
    display: grid;
    gap: 8px;
}

.post-comment {
    display: flex;
    align-items: flex-start;
    gap: 9px;
}

.post-comment__main {
    min-width: 0;
    flex: 1;
}

.post-bubble {
    width: fit-content;
    max-width: min(100%, 660px);
    padding: 9px 11px;
    background: rgba(106, 67, 28, 0.08);
    color: #332215;
    border-radius: 8px;
}

.post-bubble strong {
    display: block;
    margin-bottom: 3px;
    color: #2f1e12;
    font-size: 13px;
}

.post-bubble p {
    margin: 0;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    color: #3e2b1d;
}

.post-bubble :deep(.post-mention) {
    color: #a65316;
    font-weight: 900;
}

.post-comment__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    align-items: center;
    padding: 4px 0 0 10px;
    color: #7a624a;
    font-size: 12px;
}

.post-comment__actions button {
    border: 0;
    background: transparent;
    color: inherit;
    font-weight: 800;
    cursor: pointer;
    padding: 0;
}

.post-comment__actions button:hover,
.post-comment__actions button.active {
    color: #a65316;
}

.post-replies {
    display: grid;
    gap: 8px;
    margin-left: 46px;
    padding-left: 12px;
    border-left: 2px solid rgba(123, 76, 32, 0.13);
}

.post-comment-form--reply {
    margin-left: 60px;
    margin-top: 0;
}

@media (max-width: 980px) {
    .post-layout {
        grid-template-columns: 1fr;
    }

    .post-rail {
        position: static;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .post-rail-nav {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .post-detail-page {
        width: min(100% - 18px, 680px);
    }

    .post-rail {
        grid-template-columns: 1fr;
    }

    .post-hero {
        padding: 18px;
    }

    .post-excerpt,
    .post-detail-page .content-preview,
    .post-detail-page .post-content {
        margin-left: 18px !important;
        margin-right: 18px !important;
    }

    .post-hero__image {
        aspect-ratio: 16 / 9;
    }

    .post-actions {
        grid-template-columns: 1fr;
    }

    .post-comment-form,
    .post-comment-form--reply {
        margin-left: 14px;
        grid-template-columns: 34px minmax(0, 1fr);
    }

    .post-replies {
        margin-left: 24px;
    }

    .post-comment-form__foot {
        align-items: flex-end;
    }

    .post-comment-form__foot span {
        display: none;
    }
}
</style>
