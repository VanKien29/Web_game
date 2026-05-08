<template>
    <div class="page-post">

        <!-- Breadcrumb -->
        <div class="pp-breadcrumb">
            <router-link to="/"><i class="fa-solid fa-house"></i> Trang chủ</router-link>
            <i class="fa-solid fa-chevron-right"></i>
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

        <template v-else>
            <div class="pp-layout">

                <!-- ── MAIN ── -->
                <main class="pp-main">
                    <article class="pp-card">

                        <!-- Gold top bar -->
                        <div class="pp-topbar"></div>

                        <!-- ── POST HEADER ── -->
                        <div class="pp-post-header">
                            <div class="pp-author-avatar">
                                {{ (post.author_username || post.author || "A").slice(0,1).toUpperCase() }}
                            </div>
                            <div class="pp-author-info">
                                <span class="pp-author-name">
                                    {{ post.author_username || post.author || "Admin" }}
                                </span>
                                <span class="pp-author-meta">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ formatDate(post.created_at) }}
                                    &nbsp;·&nbsp;
                                    <i class="fa-solid fa-earth-asia"></i>
                                    Công khai
                                </span>
                            </div>
                            <button class="pp-btn-more">
                                <i class="fa-solid fa-ellipsis"></i>
                            </button>
                        </div>

                        <!-- ── POST TITLE & BODY ── -->
                        <div class="pp-post-content">
                            <h1 class="pp-post-title">{{ post.title }}</h1>

                            <div v-if="!showFullContent" class="pp-body-preview">
                                <div class="pp-body-text" v-html="post.content"></div>
                                <button class="pp-btn-more-content" @click="showFullContent = true">
                                    <i class="fa-solid fa-chevron-down"></i> Xem thêm
                                </button>
                            </div>
                            <div v-else class="pp-body-text" v-html="post.content"></div>
                        </div>

                        <!-- ── STATS ── -->
                        <div class="pp-stats">
                            <div class="pp-stats-left">
                                <span class="pp-react-icons">
                                    <i class="fa-solid fa-thumbs-up pp-icon-like"></i>
                                    <i class="fa-solid fa-heart pp-icon-heart"></i>
                                </span>
                                <span class="pp-stats-count">{{ engagement.likes }}</span>
                            </div>
                            <button class="pp-stats-btn" @click="focusCommentBox">
                                {{ engagement.comments }} bình luận
                            </button>
                        </div>

                        <div class="pp-divider"></div>

                        <!-- ── ACTION BAR ── -->
                        <div class="pp-actions">
                            <button
                                class="pp-action"
                                :class="{ 'pp-action--active': engagement.liked }"
                                @click="togglePostLike"
                            >
                                <i :class="engagement.liked ? 'fa-solid fa-thumbs-up' : 'fa-regular fa-thumbs-up'"></i>
                                Thích
                            </button>
                            <button class="pp-action" @click="focusCommentBox">
                                <i class="fa-regular fa-comment-dots"></i>
                                Bình luận
                            </button>
                            <button class="pp-action" @click="sharePost">
                                <i class="fa-solid fa-share-nodes"></i>
                                Chia sẻ
                            </button>
                        </div>

                        <div class="pp-divider"></div>

                        <!-- Toast -->
                        <transition name="pp-toast">
                            <div v-if="interactionMessage" class="pp-toast">
                                <i class="fa-solid fa-circle-info"></i>
                                {{ interactionMessage }}
                            </div>
                        </transition>

                        <!-- ── COMMENTS ── -->
                        <div class="pp-comments">

                            <!-- Composer -->
                            <div class="pp-composer">
                                <div class="pp-me-avatar">{{ currentInitial }}</div>
                                <div class="pp-composer-inner" :class="{ 'pp-composer-inner--focus': composerFocused }">
                                    <textarea
                                        ref="commentInput"
                                        v-model="commentText"
                                        rows="1"
                                        placeholder="Viết bình luận..."
                                        class="pp-composer-ta"
                                        @focus="composerFocused = true"
                                        @blur="composerFocused = false"
                                        @keydown.enter.exact.prevent="submitComment()"
                                    ></textarea>
                                    <button
                                        class="pp-composer-send"
                                        :disabled="commentSubmitting || !commentText.trim()"
                                        @click.prevent="submitComment()"
                                        title="Gửi"
                                    >
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="pp-composer-hint">
                                <i class="fa-regular fa-keyboard"></i>
                                Enter để gửi · Shift+Enter xuống dòng
                            </p>

                            <!-- List -->
                            <div class="pp-cmt-list">
                                <div v-if="!comments.length" class="pp-cmt-empty">
                                    <i class="fa-regular fa-comment"></i>
                                    <span>Chưa có bình luận nào. Hãy là người đầu tiên!</span>
                                </div>

                                <template v-for="comment in comments" :key="comment.id">
                                    <!-- Root comment -->
                                    <div class="pp-cmt-row">
                                        <div class="pp-cmt-avatar">
                                            <img v-if="comment.avatar_url" :src="comment.avatar_url" alt="" />
                                            <span v-else>{{ comment.username?.slice(0,1).toUpperCase() }}</span>
                                        </div>
                                        <div class="pp-cmt-right">
                                            <div class="pp-cmt-bubble">
                                                <span class="pp-cmt-name">{{ comment.username }}</span>
                                                <p class="pp-cmt-text">{{ comment.content }}</p>
                                            </div>
                                            <div class="pp-cmt-meta">
                                                <button
                                                    class="pp-cmt-btn"
                                                    :class="{ 'pp-cmt-btn--liked': comment.liked }"
                                                    @click="toggleCommentLike(comment)"
                                                >
                                                    <i :class="comment.liked ? 'fa-solid fa-thumbs-up' : 'fa-regular fa-thumbs-up'"></i>
                                                    Thích
                                                </button>
                                                <button class="pp-cmt-btn" @click="startReply(comment)">
                                                    <i class="fa-solid fa-reply"></i>
                                                    Phản hồi
                                                </button>
                                                <span class="pp-cmt-time">{{ formatRelative(comment.created_at) }}</span>
                                                <span v-if="comment.likes" class="pp-cmt-likes">
                                                    <i class="fa-solid fa-thumbs-up"></i> {{ comment.likes }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Replies -->
                                    <div v-if="comment.replies?.length" class="pp-replies">
                                        <div class="pp-replies-line"></div>
                                        <div class="pp-replies-body">
                                            <div
                                                v-for="reply in comment.replies"
                                                :key="reply.id"
                                                class="pp-cmt-row pp-cmt-row--reply"
                                            >
                                                <div class="pp-cmt-avatar pp-cmt-avatar--sm">
                                                    <img v-if="reply.avatar_url" :src="reply.avatar_url" alt="" />
                                                    <span v-else>{{ reply.username?.slice(0,1).toUpperCase() }}</span>
                                                </div>
                                                <div class="pp-cmt-right">
                                                    <div class="pp-cmt-bubble">
                                                        <span class="pp-cmt-name">{{ reply.username }}</span>
                                                        <p class="pp-cmt-text">{{ reply.content }}</p>
                                                    </div>
                                                    <div class="pp-cmt-meta">
                                                        <button
                                                            class="pp-cmt-btn"
                                                            :class="{ 'pp-cmt-btn--liked': reply.liked }"
                                                            @click="toggleCommentLike(reply)"
                                                        >
                                                            <i :class="reply.liked ? 'fa-solid fa-thumbs-up' : 'fa-regular fa-thumbs-up'"></i>
                                                            Thích
                                                        </button>
                                                        <button class="pp-cmt-btn" @click="startReply(reply)">
                                                            <i class="fa-solid fa-reply"></i>
                                                            Phản hồi
                                                        </button>
                                                        <span class="pp-cmt-time">{{ formatRelative(reply.created_at) }}</span>
                                                        <span v-if="reply.likes" class="pp-cmt-likes">
                                                            <i class="fa-solid fa-thumbs-up"></i> {{ reply.likes }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reply composer -->
                                    <div v-if="replyingTo === comment.id" class="pp-reply-composer">
                                        <div class="pp-me-avatar pp-me-avatar--sm">{{ currentInitial }}</div>
                                        <div class="pp-composer-inner" :class="{ 'pp-composer-inner--focus': replyFocused }">
                                            <textarea
                                                v-model="replyText"
                                                rows="1"
                                                :placeholder="`Trả lời ${comment.username}...`"
                                                class="pp-composer-ta"
                                                @focus="replyFocused = true"
                                                @blur="replyFocused = false"
                                                @keydown.enter.exact.prevent="submitComment(comment.id)"
                                            ></textarea>
                                            <button
                                                class="pp-composer-send"
                                                :disabled="commentSubmitting"
                                                @click.prevent="submitComment(comment.id)"
                                            >
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </button>
                                        </div>
                                        <button class="pp-reply-cancel" @click="cancelReply">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </article>
                </main>

                <!-- ── SIDEBAR ── -->
                <aside class="pp-sidebar">
                    <div class="pp-scard">
                        <div class="pp-scard-topbar"></div>
                        <h4 class="pp-scard-title">
                            <i class="fa-solid fa-compass"></i> Điều hướng
                        </h4>
                        <router-link to="/" class="pp-slink">
                            <span class="pp-slink-icon"><i class="fa-solid fa-house"></i></span>
                            Trang chủ
                        </router-link>
                        <router-link to="/bxh" class="pp-slink">
                            <span class="pp-slink-icon"><i class="fa-solid fa-trophy"></i></span>
                            Xếp hạng hôm nay
                        </router-link>
                        <router-link to="/giftcode" class="pp-slink">
                            <span class="pp-slink-icon"><i class="fa-solid fa-gift"></i></span>
                            Mã quà tặng
                        </router-link>
                    </div>

                    <div class="pp-scard">
                        <div class="pp-scard-topbar"></div>
                        <h4 class="pp-scard-title">
                            <i class="fa-solid fa-circle-info"></i> Thông tin
                        </h4>
                        <div class="pp-smeta">
                            <i class="fa-regular fa-calendar-days"></i>
                            <span>Đăng lúc <strong>{{ formatDate(post.created_at) }}</strong></span>
                        </div>
                        <div class="pp-smeta">
                            <i class="fa-regular fa-eye"></i>
                            <span><strong>{{ post.views || 0 }}</strong> lượt xem</span>
                        </div>
                        <div class="pp-smeta">
                            <i class="fa-regular fa-thumbs-up"></i>
                            <span><strong>{{ engagement.likes }}</strong> lượt thích</span>
                        </div>
                    </div>
                </aside>

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
            commentSubmitting: false,
            interactionMessage: "",
            composerFocused: false,
            replyFocused: false,
            _msgTimer: null,
        };
    },
    computed: {
        isLoggedIn() { return !!localStorage.getItem("token"); },
        currentUsername() {
            try { return JSON.parse(localStorage.getItem("user") || "{}").username || ""; }
            catch { return ""; }
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
            const diff = Math.max(1, Math.floor((Date.now() - new Date(date).getTime()) / 1000));
            if (diff < 60) return "Vừa xong";
            if (diff < 3600) return `${Math.floor(diff / 60)} phút`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} giờ`;
            return new Date(date).toLocaleDateString("vi-VN");
        },
        authHeaders() {
            const token = localStorage.getItem("token");
            return token ? { headers: { Authorization: `Bearer ${token}` } } : {};
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
            const content = parentId ? this.replyText.trim() : this.commentText.trim();
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
                    if (parentId) { this.replyText = ""; this.replyingTo = null; }
                    else this.commentText = "";
                    await this.loadComments();
                }
            } catch (err) {
                this.showMsg(err.response?.data?.message || "Không thể gửi bình luận lúc này.");
            } finally {
                this.commentSubmitting = false;
            }
        },
        startReply(comment) {
            if (!this.requireLogin()) return;
            this.replyingTo = comment.parent_comment_id || comment.id;
            this.replyText = "";
        },
        cancelReply() { this.replyingTo = null; this.replyText = ""; },
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
            const { data } = await axios.get(`/api/posts/${slug}`);
            if (data.ok) {
                this.post = data.data;
                await this.loadComments();
            }
        } catch (err) { console.error(err); }
        finally { this.loading = false; }
    },
};
</script>

<style>
/*
  ⚠️  KHÔNG dùng "scoped" — cần global để style các element được render
      bởi v-html và các class dùng trực tiếp trong template.

  Font Awesome 6 cần được load trong index.html:
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
*/

/* ── TOKENS ── */
:root {
    --pp-gold:         #f5a623;
    --pp-gold-lt:      #ffd166;
    --pp-gold-glow:    rgba(245, 166, 35, 0.18);
    --pp-white:        #ffffff;
    --pp-bg-bubble:    #f3f4f8;
    --pp-border:       rgba(0,0,0,.07);
    --pp-text:         #1a1c23;
    --pp-sub:          #6b7280;
    --pp-muted:        #9ca3af;
    --pp-divider:      #eef0f4;
    --pp-radius:       12px;
    --pp-shadow:       0 4px 24px rgba(0,0,0,.12), 0 1px 4px rgba(0,0,0,.06);
}

/* ── PAGE ── */
.page-post {
    min-height: 100vh;
    padding: 18px 16px 56px;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 15px;
    color: var(--pp-text);
}

/* ── BREADCRUMB ── */
.pp-breadcrumb {
    max-width: 1000px;
    margin: 0 auto 14px;
    display: flex; align-items: center; gap: 7px;
    font-size: 13px;
    color: rgba(255,255,255,.5);
    flex-wrap: wrap;
}
.pp-breadcrumb a {
    color: var(--pp-gold); text-decoration: none; font-weight: 600;
    display: flex; align-items: center; gap: 5px;
}
.pp-breadcrumb a:hover { text-decoration: underline; }
.pp-breadcrumb .fa-chevron-right { font-size: 9px; opacity: .45; }
.pp-breadcrumb span { color: rgba(255,255,255,.75); }

/* ── STATE ── */
.pp-state {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 12px; padding: 80px 0;
    color: rgba(255,255,255,.4); font-size: 15px;
}
.pp-state i { font-size: 30px; color: var(--pp-gold); }

/* ── LAYOUT ── */
.pp-layout {
    max-width: 1000px; margin: 0 auto;
    display: flex; gap: 20px; align-items: flex-start;
}
.pp-main { flex: 1; min-width: 0; }
.pp-sidebar {
    width: 268px; flex-shrink: 0;
    display: flex; flex-direction: column; gap: 14px;
    position: sticky; top: 72px;
}

/* ── CARD ── */
.pp-card {
    background: var(--pp-white);
    border-radius: var(--pp-radius);
    box-shadow: var(--pp-shadow);
    border: 1px solid var(--pp-border);
    overflow: hidden;
}
.pp-topbar {
    height: 3px;
    background: linear-gradient(90deg, var(--pp-gold) 0%, var(--pp-gold-lt) 60%, transparent 100%);
}

/* ── POST HEADER ── */
.pp-post-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px 0;
}
.pp-author-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--pp-gold) 0%, #c47e0b 100%);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 18px;
    box-shadow: 0 0 0 2px var(--pp-gold-glow);
}
.pp-author-info {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 2px;
}
.pp-author-name {
    font-weight: 700; font-size: 15px; color: var(--pp-text);
    line-height: 1.2;
}
.pp-author-meta {
    font-size: 12px; color: var(--pp-muted);
    display: flex; align-items: center; gap: 4px; flex-wrap: wrap;
}
.pp-author-meta i { font-size: 11px; }
.pp-btn-more {
    background: none; border: none; cursor: pointer;
    width: 34px; height: 34px; border-radius: 50%;
    color: var(--pp-sub); font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s; flex-shrink: 0;
}
.pp-btn-more:hover { background: var(--pp-bg-bubble); }

/* ── POST CONTENT ── */
.pp-post-content { padding: 10px 16px 14px; }
.pp-post-title {
    font-size: 19px; font-weight: 700; color: var(--pp-text);
    margin: 0 0 10px; line-height: 1.35; letter-spacing: -.01em;
}
.pp-body-text {
    font-size: 15px; line-height: 1.7; color: var(--pp-text);
}
.pp-body-preview .pp-body-text { max-height: 220px; overflow: hidden; }
.pp-btn-more-content {
    background: none; border: none; cursor: pointer;
    color: var(--pp-gold); font-size: 14px; font-weight: 600;
    padding: 6px 0 0; display: inline-flex; align-items: center; gap: 5px;
}
.pp-btn-more-content:hover { opacity: .75; }

/* ── STATS ── */
.pp-stats {
    display: flex; justify-content: space-between; align-items: center;
    padding: 6px 16px; font-size: 13px; color: var(--pp-sub);
}
.pp-stats-left { display: flex; align-items: center; gap: 6px; }
.pp-react-icons { display: flex; align-items: center; }
.pp-icon-like  { color: var(--pp-gold); font-size: 14px; }
.pp-icon-heart { color: #e0245e; font-size: 13px; margin-left: -2px; }
.pp-stats-count { margin-left: 5px; }
.pp-stats-btn {
    background: none; border: none; cursor: pointer;
    color: var(--pp-sub); font-size: 13px; padding: 0;
}
.pp-stats-btn:hover { text-decoration: underline; }

/* ── DIVIDER ── */
.pp-divider { height: 1px; background: var(--pp-divider); margin: 0 16px; }

/* ── ACTIONS ── */
.pp-actions { display: flex; padding: 2px 8px; }
.pp-action {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 7px;
    background: none; border: none; cursor: pointer;
    color: var(--pp-sub); font-size: 14px; font-weight: 600;
    padding: 9px 4px; border-radius: 8px;
    transition: background .15s, color .15s;
}
.pp-action i { font-size: 16px; }
.pp-action:hover { background: var(--pp-bg-bubble); color: var(--pp-text); }
.pp-action--active,
.pp-action--active i { color: var(--pp-gold) !important; }

/* ── TOAST ── */
.pp-toast {
    margin: 8px 16px 0;
    padding: 9px 13px; border-radius: 8px;
    background: #fefce8; border: 1px solid #fde68a;
    font-size: 13px; color: #854d0e;
    display: flex; align-items: center; gap: 8px;
}
.pp-toast i { color: var(--pp-gold); flex-shrink: 0; }
.pp-toast-enter-active, .pp-toast-leave-active { transition: all .2s ease; }
.pp-toast-enter-from, .pp-toast-leave-to { opacity: 0; transform: translateY(-4px); }

/* ── COMMENTS SECTION ── */
.pp-comments { padding: 12px 16px 18px; }

/* Composer */
.pp-composer { display: flex; align-items: flex-start; gap: 9px; }
.pp-me-avatar {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--pp-gold), #c47e0b);
    color: #fff; font-weight: 700; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
}
.pp-me-avatar--sm { width: 30px; height: 30px; font-size: 12px; }
.pp-composer-inner {
    flex: 1; display: flex; align-items: center;
    background: var(--pp-bg-bubble); border-radius: 22px;
    padding: 7px 10px 7px 14px; gap: 6px;
    border: 1.5px solid transparent;
    transition: border-color .2s, background .2s;
}
.pp-composer-inner--focus { border-color: var(--pp-gold); background: #fff; }
.pp-composer-ta {
    flex: 1; background: none; border: none; outline: none;
    font-size: 14px; color: var(--pp-text); font-family: inherit;
    resize: none; line-height: 1.5; min-height: 22px;
}
.pp-composer-ta::placeholder { color: var(--pp-muted); }
.pp-composer-send {
    background: none; border: none; cursor: pointer;
    color: var(--pp-gold); font-size: 15px; padding: 2px 4px;
    opacity: .3; transition: opacity .15s, transform .15s; line-height: 1;
    flex-shrink: 0;
}
.pp-composer-send:not(:disabled) { opacity: 1; }
.pp-composer-send:not(:disabled):hover { transform: scale(1.2) rotate(-8deg); }
.pp-composer-send:disabled { cursor: default; }
.pp-composer-hint {
    font-size: 11px; color: var(--pp-muted);
    margin: 4px 0 12px 45px;
    display: flex; align-items: center; gap: 5px;
}

/* ── COMMENT LIST ── */
.pp-cmt-list { display: flex; flex-direction: column; gap: 10px; }
.pp-cmt-empty {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 20px; color: var(--pp-muted); font-size: 14px;
}
.pp-cmt-empty i { font-size: 20px; }

/* Comment row */
.pp-cmt-row { display: flex; align-items: flex-start; gap: 8px; }
.pp-cmt-row--reply { }
.pp-cmt-avatar {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #5b9cf6, #2563eb);
    color: #fff; font-weight: 700; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.pp-cmt-avatar--sm { width: 30px; height: 30px; font-size: 12px; }
.pp-cmt-avatar img { width: 100%; height: 100%; object-fit: cover; }

.pp-cmt-right { flex: 1; min-width: 0; }
.pp-cmt-bubble {
    background: var(--pp-bg-bubble);
    border-radius: 16px; padding: 8px 13px;
    display: inline-block; max-width: 100%;
}
.pp-cmt-name {
    display: block; font-weight: 700; font-size: 13px; color: var(--pp-text);
    margin-bottom: 2px;
}
.pp-cmt-text {
    font-size: 14px; color: var(--pp-text);
    margin: 0; line-height: 1.55;
    white-space: pre-wrap; word-break: break-word;
}
.pp-cmt-meta {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: 8px; padding: 4px 4px 0;
}
.pp-cmt-btn {
    background: none; border: none; cursor: pointer;
    color: var(--pp-sub); font-size: 12px; font-weight: 600;
    padding: 0; display: inline-flex; align-items: center; gap: 4px;
    transition: color .15s;
}
.pp-cmt-btn:hover { color: var(--pp-text); }
.pp-cmt-btn--liked { color: var(--pp-gold) !important; }
.pp-cmt-time { color: var(--pp-muted); font-size: 12px; }
.pp-cmt-likes {
    font-size: 12px; color: var(--pp-sub);
    display: inline-flex; align-items: center; gap: 3px;
}
.pp-cmt-likes i { color: var(--pp-gold); font-size: 11px; }

/* Replies */
.pp-replies { display: flex; padding-left: 22px; margin-top: 4px; gap: 0; }
.pp-replies-line {
    width: 2px; background: linear-gradient(to bottom, var(--pp-gold-glow), transparent);
    border-radius: 2px; flex-shrink: 0; margin-right: 14px; min-height: 20px;
}
.pp-replies-body { flex: 1; display: flex; flex-direction: column; gap: 8px; }

/* Reply composer */
.pp-reply-composer {
    display: flex; align-items: center; gap: 8px;
    padding-left: 44px; margin-top: 2px;
}
.pp-reply-cancel {
    background: none; border: none; cursor: pointer;
    width: 28px; height: 28px; border-radius: 50%;
    color: var(--pp-muted); font-size: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: background .15s, color .15s;
}
.pp-reply-cancel:hover { background: var(--pp-bg-bubble); color: var(--pp-text); }

/* ── SIDEBAR CARDS ── */
.pp-scard {
    background: var(--pp-white); border-radius: var(--pp-radius);
    box-shadow: var(--pp-shadow); border: 1px solid var(--pp-border);
    padding: 14px 16px; overflow: hidden; position: relative;
}
.pp-scard-topbar {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--pp-gold) 0%, var(--pp-gold-lt) 60%, transparent 100%);
}
.pp-scard-title {
    font-size: 15px; font-weight: 700; color: var(--pp-text);
    margin: 0 0 10px; display: flex; align-items: center; gap: 7px;
}
.pp-scard-title i { color: var(--pp-gold); }
.pp-slink {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 6px; border-radius: 8px;
    color: var(--pp-text); text-decoration: none;
    font-size: 14px; font-weight: 500;
    transition: background .15s;
}
.pp-slink:hover { background: var(--pp-bg-bubble); }
.pp-slink-icon { color: var(--pp-gold); width: 18px; text-align: center; }
.pp-smeta {
    display: flex; align-items: center; gap: 9px;
    font-size: 13px; color: var(--pp-sub);
    padding: 7px 0; border-bottom: 1px solid var(--pp-divider);
}
.pp-smeta:last-child { border-bottom: none; padding-bottom: 0; }
.pp-smeta i { color: var(--pp-gold); width: 14px; text-align: center; flex-shrink: 0; }
.pp-smeta strong { color: var(--pp-text); }

/* ── RESPONSIVE ── */
@media (max-width: 820px) {
    .pp-layout { flex-direction: column; }
    .pp-sidebar { width: 100%; position: static; }
    .page-post { padding: 10px 0 40px; }
    .pp-card { border-radius: 0; border-left: none; border-right: none; }
    .pp-scard { border-radius: 0; }
    .pp-breadcrumb { padding: 0 12px; }
}
</style>