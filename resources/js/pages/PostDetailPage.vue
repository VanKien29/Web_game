<template>
    <div class="client-page client-page--post">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span v-if="post">{{ post.title }}</span>
            <span v-else>Đang tải...</span>
        </div>

        <div v-if="loading" class="page-loading">
            <div class="page-loading__spinner"></div>
        </div>
        <p v-else-if="!post" class="client-empty">
            Bài viết không tồn tại.
        </p>

        <template v-else>
            <div class="post-wrapper">
                <article class="client-panel post-detail">
                    <div class="client-panel__eyebrow">Bài viết</div>
                    <h1>{{ post.title }}</h1>

                    <div class="post-meta">
                        <span>{{ post.author_username || post.author || "Admin" }}</span>
                        <span>{{ formatDate(post.created_at) }}</span>
                        <span>{{ post.views || 0 }} lượt xem</span>
                    </div>

                    <div class="content-preview" v-if="!showFullContent">
                        <div
                            class="content-preview-text"
                            v-html="post.content"
                        ></div>
                        <div class="content-read-more">
                            <button
                                class="content-read-more-btn"
                                @click="showFullContent = true"
                            >
                                Xem thêm
                            </button>
                        </div>
                    </div>
                    <div
                        v-else
                        class="post-content"
                        v-html="post.content"
                    ></div>

                    <section class="fb-engagement">
                        <div class="fb-engagement__summary">
                            <span>
                                <span class="fb-like-dot">👍</span>
                                {{ engagement.likes }} lượt thích
                            </span>
                            <button type="button" @click="focusCommentBox">
                                {{ engagement.comments }} bình luận
                            </button>
                        </div>

                        <div class="fb-actions">
                            <button
                                type="button"
                                class="fb-action"
                                :class="{ active: engagement.liked }"
                                @click="togglePostLike"
                            >
                                <span>👍</span>
                                Thích
                            </button>
                            <button type="button" class="fb-action" @click="focusCommentBox">
                                <span>💬</span>
                                Bình luận
                            </button>
                            <button type="button" class="fb-action" @click="sharePost">
                                <span>↗</span>
                                Chia sẻ
                            </button>
                        </div>

                        <div v-if="interactionMessage" class="fb-message">
                            {{ interactionMessage }}
                        </div>

                        <form class="fb-composer" @submit.prevent="submitComment()">
                            <div class="fb-avatar">{{ currentInitial }}</div>
                            <div class="fb-composer__body">
                                <textarea
                                    ref="commentInput"
                                    v-model="commentText"
                                    rows="2"
                                    placeholder="Viết bình luận..."
                                    @keydown.enter.exact.prevent="submitComment()"
                                ></textarea>
                                <div class="fb-composer__foot">
                                    <span>Enter để gửi, Shift + Enter để xuống dòng</span>
                                    <button type="submit" :disabled="commentSubmitting">
                                        Gửi
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="fb-comments">
                            <p v-if="!comments.length" class="fb-empty">
                                Chưa có bình luận nào.
                            </p>

                            <div
                                v-for="comment in comments"
                                :key="comment.id"
                                class="fb-comment-thread"
                            >
                                <CommentItem
                                    :comment="comment"
                                    @reply="startReply"
                                    @like="toggleCommentLike"
                                />

                                <div v-if="comment.replies?.length" class="fb-replies">
                                    <CommentItem
                                        v-for="reply in comment.replies"
                                        :key="reply.id"
                                        :comment="reply"
                                        compact
                                        @reply="startReply"
                                        @like="toggleCommentLike"
                                    />
                                </div>

                                <form
                                    v-if="replyingTo === comment.id"
                                    class="fb-composer fb-composer--reply"
                                    @submit.prevent="submitComment(comment.id)"
                                >
                                    <div class="fb-avatar">{{ currentInitial }}</div>
                                    <div class="fb-composer__body">
                                        <textarea
                                            v-model="replyText"
                                            rows="1"
                                            :placeholder="`Trả lời ${comment.username}...`"
                                            @keydown.enter.exact.prevent="submitComment(comment.id)"
                                        ></textarea>
                                        <div class="fb-composer__foot">
                                            <button type="button" class="plain" @click="cancelReply">
                                                Hủy
                                            </button>
                                            <button type="submit" :disabled="commentSubmitting">
                                                Trả lời
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                </article>

                <aside class="post-sidebar">
                    <div class="client-panel sidebar-widget">
                        <h4>Bài viết khác</h4>
                        <router-link to="/" class="widget-link"
                            >Quay lại danh sách</router-link
                        >
                        <router-link to="/bxh" class="widget-link"
                            >Xếp hạng hôm nay</router-link
                        >
                        <router-link to="/giftcode" class="widget-link"
                            >Mã quà tặng</router-link
                        >
                    </div>
                    <div class="client-panel sidebar-widget">
                        <h4>Thông tin</h4>
                        <p>
                            Bài viết được đăng lúc
                            <strong>{{ formatDate(post.created_at) }}</strong>
                        </p>
                    </div>
                </aside>
            </div>
        </template>
    </div>
</template>

<script>
import axios from "axios";

const CommentItem = {
    name: "CommentItem",
    props: {
        comment: { type: Object, required: true },
        compact: { type: Boolean, default: false },
    },
    emits: ["reply", "like"],
    methods: {
        formatRelative(date) {
            const value = date ? new Date(date).getTime() : 0;
            const diff = Math.max(1, Math.floor((Date.now() - value) / 1000));
            if (diff < 60) return "Vừa xong";
            if (diff < 3600) return `${Math.floor(diff / 60)} phút`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} giờ`;
            return new Date(date).toLocaleDateString("vi-VN");
        },
        initial(name) {
            return String(name || "?").trim().slice(0, 1).toUpperCase();
        },
    },
    template: `
        <div class="fb-comment" :class="{ 'fb-comment--compact': compact }">
            <div class="fb-avatar fb-avatar--small">
                <img v-if="comment.avatar_url" :src="comment.avatar_url" alt="" />
                <span v-else>{{ initial(comment.username) }}</span>
            </div>
            <div class="fb-comment__main">
                <div class="fb-bubble">
                    <strong>{{ comment.username }}</strong>
                    <p>{{ comment.content }}</p>
                </div>
                <div class="fb-comment__actions">
                    <button
                        type="button"
                        :class="{ active: comment.liked }"
                        @click="$emit('like', comment)"
                    >
                        Thích
                    </button>
                    <button type="button" @click="$emit('reply', comment)">
                        Phản hồi
                    </button>
                    <span>{{ formatRelative(comment.created_at) }}</span>
                    <span v-if="comment.likes">{{ comment.likes }} thích</span>
                </div>
            </div>
        </div>
    `,
};

export default {
    name: "PostDetailPage",
    components: { CommentItem },
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
            commentSubmitting: false,
            interactionMessage: "",
        };
    },
    computed: {
        isLoggedIn() {
            return !!localStorage.getItem("token");
        },
        currentUsername() {
            try {
                return JSON.parse(localStorage.getItem("user") || "{}").username || "";
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
        authHeaders() {
            const token = localStorage.getItem("token");
            return token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        },
        requireLogin() {
            if (this.isLoggedIn) return true;
            this.interactionMessage = "Bạn cần đăng nhập để tương tác.";
            return false;
        },
        async loadComments() {
            const slug = this.$route.params.slug;
            const { data } = await axios.get(
                `/api/posts/${slug}/comments`,
                this.authHeaders(),
            );
            if (data.ok) {
                this.comments = data.data || [];
                this.engagement = data.engagement || this.engagement;
            }
        },
        async togglePostLike() {
            if (!this.requireLogin()) return;
            const slug = this.$route.params.slug;
            const { data } = await axios.post(
                `/api/posts/${slug}/like`,
                {},
                this.authHeaders(),
            );
            if (data.ok) {
                this.engagement.liked = data.liked;
                this.engagement.likes = data.likes;
            }
        },
        async toggleCommentLike(comment) {
            if (!this.requireLogin()) return;
            const { data } = await axios.post(
                `/api/comments/${comment.id}/like`,
                {},
                this.authHeaders(),
            );
            if (data.ok) {
                comment.liked = data.liked;
                comment.likes = data.likes;
            }
        },
        async submitComment(parentId = null) {
            if (!this.requireLogin()) return;
            const content = parentId ? this.replyText.trim() : this.commentText.trim();
            if (!content) return;

            this.commentSubmitting = true;
            try {
                const slug = this.$route.params.slug;
                const { data } = await axios.post(
                    `/api/posts/${slug}/comments`,
                    { content, parent_comment_id: parentId },
                    this.authHeaders(),
                );
                if (data.ok) {
                    if (parentId) {
                        this.replyText = "";
                        this.replyingTo = null;
                    } else {
                        this.commentText = "";
                    }
                    await this.loadComments();
                }
            } catch (err) {
                this.interactionMessage =
                    err.response?.data?.message || "Không thể gửi bình luận lúc này.";
            } finally {
                this.commentSubmitting = false;
            }
        },
        startReply(comment) {
            if (!this.requireLogin()) return;
            this.replyingTo = comment.parent_comment_id || comment.id;
            this.replyText = "";
        },
        cancelReply() {
            this.replyingTo = null;
            this.replyText = "";
        },
        focusCommentBox() {
            this.$refs.commentInput?.focus();
        },
        async sharePost() {
            const url = window.location.href;
            try {
                if (navigator.share) {
                    await navigator.share({ title: this.post.title, url });
                } else {
                    await navigator.clipboard.writeText(url);
                    this.interactionMessage = "Đã sao chép liên kết bài viết.";
                }
            } catch {
                this.interactionMessage = "Không thể chia sẻ lúc này.";
            }
        },
    },
    async mounted() {
        const slug = this.$route.params.slug;
        try {
            const [{ data }] = await Promise.all([
                axios.get(`/api/posts/${slug}`),
            ]);
            if (data.ok) {
                this.post = data.data;
                await this.loadComments();
            }
        } catch (err) {
            console.error(err);
        } finally {
            this.loading = false;
        }
    },
};
</script>
