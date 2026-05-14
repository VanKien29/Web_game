<template>
    <div class="client-page forum-page">
        <div class="breadcrumb client-breadcrumb forum-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Diễn đàn</span>
        </div>

        <div class="forum-layout">
            <aside class="forum-rail forum-rail--left">
                <div class="forum-panel forum-profile">
                    <div class="forum-profile__avatar">
                        <img v-if="currentAvatarUrl" :src="currentAvatarUrl" alt="" />
                        <span v-else>{{ currentInitial }}</span>
                    </div>
                    <div>
                        <strong>{{ currentUsername || "Người chơi" }}</strong>
                        <span>{{ isLoggedIn ? "Đang trực tuyến" : "Chưa đăng nhập" }}</span>
                    </div>
                </div>

                <nav class="forum-panel forum-tabs">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        type="button"
                        :class="{ active: filter === tab.key }"
                        @click="setFilter(tab.key)"
                    >
                        <i :class="tab.icon"></i>
                        <span>{{ tab.label }}</span>
                        <em>{{ stats[tab.countKey] || 0 }}</em>
                    </button>
                </nav>
            </aside>

            <main class="forum-feed">
                <header class="forum-head">
                    <div>
                        <div class="client-panel__eyebrow">Cộng đồng</div>
                        <h1>Diễn đàn người chơi</h1>
                        <p>Bảng tin riêng cho thông báo admin, bài đăng người chơi và góp ý phát triển game.</p>
                    </div>
                    <form class="forum-search" @submit.prevent="loadFeed(true)">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input v-model="search" placeholder="Tìm bài viết, người đăng..." />
                    </form>
                </header>

                <div class="forum-feed-tools">
                    <div class="forum-sort">
                        <button
                            v-for="option in sortOptions"
                            :key="option.key"
                            type="button"
                            :class="{ active: sort === option.key }"
                            @click="setSort(option.key)"
                        >
                            <i :class="option.icon"></i>
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <section class="forum-panel forum-composer">
                    <template v-if="isLoggedIn">
                        <div class="forum-composer__top">
                            <div class="forum-avatar">
                                <img v-if="currentAvatarUrl" :src="currentAvatarUrl" alt="" />
                                <span v-else>{{ currentInitial }}</span>
                            </div>
                            <div class="forum-composer__fields">
                                <div class="forum-segment">
                                    <button
                                        type="button"
                                        :class="{ active: composer.type === 'player_post' }"
                                        @click="composer.type = 'player_post'"
                                    >
                                        Bài viết
                                    </button>
                                    <button
                                        type="button"
                                        :class="{ active: composer.type === 'feedback' }"
                                        @click="composer.type = 'feedback'"
                                    >
                                        Góp ý
                                    </button>
                                </div>
                                <input
                                    v-model="composer.title"
                                    class="forum-title-input"
                                    maxlength="160"
                                    placeholder="Tiêu đề ngắn nếu cần"
                                />
                                <textarea
                                    v-model="composer.content"
                                    rows="4"
                                    placeholder="Bạn đang nghĩ gì?"
                                ></textarea>
                            </div>
                        </div>

                        <div v-if="composer.previews.length" class="forum-image-preview">
                            <figure v-for="preview in composer.previews" :key="preview">
                                <img :src="preview" alt="" />
                            </figure>
                        </div>

                        <div class="forum-composer__actions">
                            <label class="forum-tool">
                                <i class="fa-regular fa-images"></i>
                                <span>Ảnh</span>
                                <input type="file" accept="image/*" multiple @change="selectImages" />
                            </label>
                            <button
                                type="button"
                                class="forum-tool"
                                @click="composer.content += composer.content ? '\n#hoi-dap ' : '#hoi-dap '"
                            >
                                <i class="fa-solid fa-hashtag"></i>
                                <span>Chủ đề</span>
                            </button>
                            <button
                                type="submit"
                                class="forum-submit"
                                :disabled="posting || !composer.content.trim()"
                                @click="submitPost"
                            >
                                <i class="fa-solid fa-paper-plane"></i>
                                {{ posting ? "Đang đăng..." : "Đăng" }}
                            </button>
                        </div>
                    </template>
                    <div v-else class="forum-login-prompt">
                        <i class="fa-solid fa-user-lock"></i>
                        <span>Đăng nhập để đăng bài, bình luận và thả cảm xúc.</span>
                        <router-link to="/login">Đăng nhập</router-link>
                    </div>
                </section>

                <div v-if="message" class="forum-message">{{ message }}</div>

                <div v-if="loading" class="forum-loading">
                    <div class="page-loading__spinner"></div>
                </div>

                <p v-else-if="!posts.length" class="client-empty">
                    Chưa có bài nào trong mục này.
                </p>

                <template v-else>
                <article
                    v-for="post in posts"
                    :key="post.id"
                    :ref="(el) => observePostCard(el, post)"
                    class="forum-panel forum-post"
                    :class="[`forum-post--${post.type}`, { pinned: post.is_pinned, 'forum-post--unread': post.is_unread }]"
                >
                    <div class="forum-post__head">
                        <div class="forum-avatar forum-avatar--post">
                            <img v-if="post.author_avatar" :src="post.author_avatar" alt="" />
                            <span v-else>{{ initial(post.author_username) }}</span>
                        </div>
                        <div class="forum-post__meta">
                            <strong>{{ post.author_username }}</strong>
                            <div>
                                <span class="forum-badge">{{ post.type_label }}</span>
                                <span v-if="post.is_pinned" class="forum-badge forum-badge--pin">
                                    <i class="fa-solid fa-thumbtack"></i>
                                    Ghim
                                </span>
                                <span v-if="post.is_unread" class="forum-badge forum-badge--new">
                                    <i class="fa-solid fa-circle"></i>
                                    Mới
                                </span>
                                <span>{{ formatRelative(post.created_at) }}</span>
                            </div>
                        </div>
                        <div v-if="post.can_edit || post.can_delete" class="forum-post__owner">
                            <button type="button" @click="startEditPost(post)">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button type="button" @click="deletePost(post)">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <form
                        v-if="editingPostId === post.id"
                        class="forum-edit-box"
                        @submit.prevent="savePost(post)"
                    >
                        <div class="forum-segment">
                            <button
                                type="button"
                                :class="{ active: editPost.type === 'player_post' }"
                                @click="editPost.type = 'player_post'"
                            >
                                Bài viết
                            </button>
                            <button
                                type="button"
                                :class="{ active: editPost.type === 'feedback' }"
                                @click="editPost.type = 'feedback'"
                            >
                                Góp ý
                            </button>
                        </div>
                        <input v-model="editPost.title" maxlength="160" placeholder="Tiêu đề" />
                        <textarea v-model="editPost.content" rows="5"></textarea>
                        <div class="forum-edit-box__actions">
                            <button type="button" class="plain" @click="cancelEditPost">Hủy</button>
                            <button type="submit">Lưu</button>
                        </div>
                    </form>

                    <template v-else>
                        <h2 v-if="post.title">{{ post.title }}</h2>
                        <div
                            class="forum-post__content-wrap"
                            :class="{ collapsed: isLongPost(post) && !post.contentExpanded }"
                        >
                            <div
                                v-if="post.type === 'announcement'"
                                class="forum-post__content forum-post__content--rich"
                                v-html="post.content"
                            ></div>
                            <p v-else class="forum-post__content">{{ post.content }}</p>
                        </div>
                        <button
                            v-if="isLongPost(post)"
                            type="button"
                            class="forum-read-more"
                            @click="togglePostContent(post)"
                        >
                            {{ post.contentExpanded ? "Thu gọn" : "Xem thêm" }}
                            <i :class="post.contentExpanded ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"></i>
                        </button>
                    </template>

                    <div v-if="post.images?.length" class="forum-post__images" :class="`count-${Math.min(post.images.length, 4)}`">
                        <img v-for="image in post.images" :key="image" :src="image" alt="" />
                    </div>

                    <div class="forum-post__summary">
                        <button type="button" @click="toggleReactionPicker(post.id)">
                            <span class="forum-reaction-stack">
                                <span v-for="reaction in topReactions(post)" :key="reaction">
                                    {{ reactionEmoji(reaction) }}
                                </span>
                            </span>
                            {{ post.reaction_count || 0 }}
                        </button>
                        <button type="button" @click="toggleComments(post)">
                            {{ post.comment_count || 0 }} bình luận
                        </button>
                        <span>{{ post.share_count || 0 }} chia sẻ</span>
                    </div>

                    <div class="forum-actions">
                        <div class="forum-reaction-wrap">
                            <button
                                type="button"
                                class="forum-action"
                                :class="{ active: post.user_reaction }"
                                @click="post.user_reaction ? reactToPost(post, post.user_reaction) : toggleReactionPicker(post.id)"
                            >
                                <span>{{ post.user_reaction ? reactionEmoji(post.user_reaction) : "👍" }}</span>
                                {{ post.user_reaction ? reactionLabel(post.user_reaction) : "Cảm xúc" }}
                            </button>
                            <div v-if="reactionPickerPostId === post.id" class="forum-reaction-picker">
                                <button
                                    v-for="reaction in reactionOptions"
                                    :key="reaction.key"
                                    type="button"
                                    :title="reaction.label"
                                    @click="reactToPost(post, reaction.key)"
                                >
                                    <span>{{ reaction.emoji }}</span>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="forum-action" @click="toggleComments(post)">
                            <i class="fa-regular fa-comment"></i>
                            Bình luận
                        </button>
                        <button type="button" class="forum-action" @click="sharePost(post)">
                            <i class="fa-solid fa-share"></i>
                            Chia sẻ
                        </button>
                        <button
                            type="button"
                            class="forum-action"
                            :class="{ active: post.is_saved }"
                            @click="toggleSave(post)"
                        >
                            <i class="fa-regular fa-bookmark"></i>
                            {{ post.is_saved ? "Đã lưu" : "Lưu" }}
                        </button>
                    </div>

                    <section v-if="post.commentsOpen" class="forum-comments">
                        <div v-if="post.commentsLoading" class="forum-comments__loading">
                            Đang tải bình luận...
                        </div>

                        <form
                            v-if="isLoggedIn && !post.is_locked"
                            class="forum-comment-form"
                            @submit.prevent="submitComment(post)"
                        >
                            <div class="forum-avatar forum-avatar--comment">
                                <img v-if="currentAvatarUrl" :src="currentAvatarUrl" alt="" />
                                <span v-else>{{ currentInitial }}</span>
                            </div>
                            <input
                                v-model="commentDrafts[post.id]"
                                placeholder="Viết bình luận..."
                            />
                            <button type="submit" :disabled="!commentDraft(post).trim()">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                        <div v-else-if="post.is_locked" class="forum-locked">
                            <i class="fa-solid fa-lock"></i>
                            Bài viết đã khóa bình luận.
                        </div>

                        <div
                            v-for="comment in post.comments || []"
                            :key="comment.id"
                            class="forum-comment-thread"
                        >
                            <div class="forum-comment">
                                <div class="forum-avatar forum-avatar--comment">
                                    <img v-if="comment.avatar_url" :src="comment.avatar_url" alt="" />
                                    <span v-else>{{ initial(comment.username) }}</span>
                                </div>
                                <div class="forum-comment__body">
                                    <div class="forum-comment__bubble">
                                        <strong>{{ comment.username }}</strong>
                                        <template v-if="editingCommentId === comment.id">
                                            <textarea v-model="editCommentContent" rows="2"></textarea>
                                        </template>
                                        <p v-else v-html="formatMentionText(comment.content)"></p>
                                    </div>
                                    <div class="forum-comment__actions">
                                        <button
                                            type="button"
                                            :class="{ active: comment.liked }"
                                            @click="toggleCommentReaction(comment)"
                                        >
                                            Thích
                                        </button>
                                        <button type="button" @click="startReply(post, comment)">Phản hồi</button>
                                        <button
                                            v-if="comment.can_edit"
                                            type="button"
                                            @click="startEditComment(comment)"
                                        >
                                            Sửa
                                        </button>
                                        <button
                                            v-if="comment.can_delete"
                                            type="button"
                                            @click="deleteComment(post, comment)"
                                        >
                                            Xóa
                                        </button>
                                        <button
                                            v-if="editingCommentId === comment.id"
                                            type="button"
                                            @click="saveComment(comment)"
                                        >
                                            Lưu
                                        </button>
                                        <span>{{ formatRelative(comment.created_at) }}</span>
                                        <span v-if="comment.likes">{{ comment.likes }} thích</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="comment.replies?.length" class="forum-replies">
                                <div
                                    v-for="reply in comment.replies"
                                    :key="reply.id"
                                    class="forum-comment forum-comment--reply"
                                >
                                    <div class="forum-avatar forum-avatar--comment">
                                        <img v-if="reply.avatar_url" :src="reply.avatar_url" alt="" />
                                        <span v-else>{{ initial(reply.username) }}</span>
                                    </div>
                                    <div class="forum-comment__body">
                                        <div class="forum-comment__bubble">
                                            <strong>{{ reply.username }}</strong>
                                            <template v-if="editingCommentId === reply.id">
                                                <textarea v-model="editCommentContent" rows="2"></textarea>
                                            </template>
                                            <p v-else v-html="formatMentionText(reply.content)"></p>
                                        </div>
                                        <div class="forum-comment__actions">
                                            <button
                                                type="button"
                                                :class="{ active: reply.liked }"
                                                @click="toggleCommentReaction(reply)"
                                            >
                                                Thích
                                            </button>
                                            <button type="button" @click="startReply(post, reply)">Phản hồi</button>
                                            <button
                                                v-if="reply.can_edit"
                                                type="button"
                                                @click="startEditComment(reply)"
                                            >
                                                Sửa
                                            </button>
                                            <button
                                                v-if="reply.can_delete"
                                                type="button"
                                                @click="deleteComment(post, reply)"
                                            >
                                                Xóa
                                            </button>
                                            <button
                                                v-if="editingCommentId === reply.id"
                                                type="button"
                                                @click="saveComment(reply)"
                                            >
                                                Lưu
                                            </button>
                                            <span>{{ formatRelative(reply.created_at) }}</span>
                                            <span v-if="reply.likes">{{ reply.likes }} thích</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form
                                v-if="replyingTo[post.id] === comment.id"
                                class="forum-comment-form forum-comment-form--reply"
                                @submit.prevent="submitComment(post, comment.id)"
                            >
                                <div class="forum-avatar forum-avatar--comment">
                                    <img v-if="currentAvatarUrl" :src="currentAvatarUrl" alt="" />
                                    <span v-else>{{ currentInitial }}</span>
                                </div>
                                <input
                                    v-model="replyDrafts[comment.id]"
                                    :placeholder="replyPlaceholder(comment)"
                                />
                                <button type="button" class="plain" @click="cancelReply(post, comment)">
                                    Hủy
                                </button>
                                <button type="submit" :disabled="!replyDraft(comment).trim()">
                                    Gửi
                                </button>
                            </form>
                        </div>
                    </section>
                </article>
                </template>

                <button
                    v-if="page < totalPages && !loading"
                    type="button"
                    class="forum-load-more"
                    :disabled="loadingMore"
                    @click="loadMore"
                >
                    {{ loadingMore ? "Đang tải..." : "Xem thêm bài viết" }}
                </button>
            </main>
        </div>
    </div>
</template>

<script>
import axios from "axios";

const emptyComposer = () => ({
    type: "player_post",
    title: "",
    content: "",
    images: [],
    previews: [],
});

export default {
    name: "ForumPage",
    data() {
        return {
            posts: [],
            stats: {},
            filter: "all",
            sort: localStorage.getItem("token") ? "unread" : "latest",
            search: "",
            page: 1,
            totalPages: 1,
            loading: true,
            loadingMore: false,
            posting: false,
            message: "",
            composer: emptyComposer(),
            currentAvatarUrl: "",
            commentDrafts: {},
            replyDrafts: {},
            replyingTo: {},
            replyTargets: {},
            reactionPickerPostId: null,
            editingPostId: null,
            editPost: { type: "player_post", title: "", content: "" },
            editingCommentId: null,
            editCommentContent: "",
            readObserver: null,
            readTimers: {},
            filterTabs: [
                { key: "all", label: "Tất cả", icon: "fa-solid fa-table-cells-large", countKey: "all" },
                { key: "unread", label: "Chưa đọc", icon: "fa-regular fa-circle-dot", countKey: "unread" },
                { key: "announcements", label: "Thông báo", icon: "fa-solid fa-bullhorn", countKey: "announcements" },
                { key: "players", label: "Người chơi", icon: "fa-regular fa-newspaper", countKey: "players" },
                { key: "feedback", label: "Góp ý", icon: "fa-regular fa-lightbulb", countKey: "feedback" },
                { key: "mine", label: "Bài của tôi", icon: "fa-regular fa-user", countKey: "mine" },
                { key: "saved", label: "Đã lưu", icon: "fa-regular fa-bookmark", countKey: "saved" },
            ],
            sortOptions: [
                { key: "unread", label: "Ưu tiên chưa đọc", icon: "fa-regular fa-circle-dot" },
                { key: "latest", label: "Mới nhất", icon: "fa-regular fa-clock" },
                { key: "hot", label: "Sôi nổi", icon: "fa-solid fa-fire" },
            ],
            reactionOptions: [
                { key: "like", label: "Thích", emoji: "👍" },
                { key: "love", label: "Yêu thích", emoji: "❤️" },
                { key: "haha", label: "Haha", emoji: "😆" },
                { key: "wow", label: "Wow", emoji: "😮" },
                { key: "sad", label: "Buồn", emoji: "😢" },
                { key: "angry", label: "Phẫn nộ", emoji: "😡" },
            ],
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
            return this.initial(this.currentUsername || "U");
        },
    },
    async mounted() {
        await Promise.all([this.loadFeed(true), this.loadCurrentProfileAvatar()]);
    },
    beforeUnmount() {
        this.teardownReadObserver();
    },
    methods: {
        authHeaders() {
            const token = localStorage.getItem("token");
            return token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        },
        requireLogin() {
            if (this.isLoggedIn) return true;
            this.message = "Bạn cần đăng nhập để dùng chức năng này.";
            return false;
        },
        async loadCurrentProfileAvatar() {
            if (!this.isLoggedIn) return;
            try {
                const { data } = await axios.get("/api/profile", this.authHeaders());
                this.currentAvatarUrl = data?.data?.player?.avatar_url || "";
            } catch {
                this.currentAvatarUrl = "";
            }
        },
        async loadFeed(reset = false) {
            if (reset) {
                this.page = 1;
                this.loading = true;
            }
            this.message = "";
            try {
                const params = new URLSearchParams({
                    page: String(this.page),
                    filter: this.filter,
                    sort: this.isLoggedIn || this.sort !== "unread" ? this.sort : "latest",
                    search: this.search,
                });
                const { data } = await axios.get(`/api/forum/posts?${params}`, this.authHeaders());
                const rows = (data.data || []).map((post) => ({
                    ...post,
                    contentExpanded: !this.isLongPost(post),
                    commentsOpen: false,
                    commentsLoaded: false,
                    commentsLoading: false,
                    comments: [],
                }));
                if (reset) {
                    this.resetReadObserver();
                }
                this.posts = reset ? rows : [...this.posts, ...rows];
                this.stats = data.stats || {};
                this.page = data.page || this.page;
                this.totalPages = data.total_pages || 1;
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể tải diễn đàn.";
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },
        async loadMore() {
            if (this.page >= this.totalPages) return;
            this.loadingMore = true;
            this.page += 1;
            await this.loadFeed(false);
        },
        setFilter(filter) {
            if ((filter === "mine" || filter === "saved" || filter === "unread") && !this.requireLogin()) return;
            this.filter = filter;
            this.loadFeed(true);
        },
        setSort(sort) {
            if (sort === "unread" && !this.requireLogin()) return;
            this.sort = sort;
            this.loadFeed(true);
        },
        setComposerFeedback() {
            if (!this.requireLogin()) return;
            this.composer.type = "feedback";
            window.scrollTo({ top: 0, behavior: "smooth" });
        },
        selectImages(event) {
            this.composer.previews.forEach((url) => URL.revokeObjectURL(url));
            const files = Array.from(event.target.files || []).slice(0, 8);
            this.composer.images = files;
            this.composer.previews = files.map((file) => URL.createObjectURL(file));
        },
        async submitPost() {
            if (!this.requireLogin() || !this.composer.content.trim()) return;
            this.posting = true;
            this.message = "";
            try {
                const form = new FormData();
                form.append("type", this.composer.type);
                form.append("title", this.composer.title);
                form.append("content", this.composer.content);
                this.composer.images.forEach((file) => form.append("images[]", file));
                await axios.post("/api/forum/posts", form, this.authHeaders());
                this.composer.previews.forEach((url) => URL.revokeObjectURL(url));
                this.composer = emptyComposer();
                this.message = "Đã đăng bài lên diễn đàn.";
                await this.loadFeed(true);
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể đăng bài lúc này.";
            } finally {
                this.posting = false;
            }
        },
        startEditPost(post) {
            this.editingPostId = post.id;
            this.editPost = {
                type: post.type === "feedback" ? "feedback" : "player_post",
                title: post.title || "",
                content: post.content || "",
            };
        },
        cancelEditPost() {
            this.editingPostId = null;
            this.editPost = { type: "player_post", title: "", content: "" };
        },
        async savePost(post) {
            if (!this.editPost.content.trim()) return;
            try {
                const { data } = await axios.put(
                    `/api/forum/posts/${post.id}`,
                    this.editPost,
                    this.authHeaders(),
                );
                if (data.ok && data.data) {
                    Object.assign(post, data.data);
                    this.cancelEditPost();
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể lưu bài viết.";
            }
        },
        async deletePost(post) {
            if (!confirm("Xóa bài viết này khỏi diễn đàn?")) return;
            try {
                await axios.delete(`/api/forum/posts/${post.id}`, this.authHeaders());
                this.posts = this.posts.filter((item) => item.id !== post.id);
                this.message = "Đã xóa bài viết.";
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể xóa bài viết.";
            }
        },
        toggleReactionPicker(postId) {
            this.reactionPickerPostId = this.reactionPickerPostId === postId ? null : postId;
        },
        async reactToPost(post, reaction) {
            if (!this.requireLogin()) return;
            try {
                const { data } = await axios.post(
                    `/api/forum/posts/${post.id}/reaction`,
                    { type: reaction },
                    this.authHeaders(),
                );
                if (data.ok) {
                    post.user_reaction = data.reaction || null;
                    post.reaction_count = data.reaction_count;
                    post.reaction_counts = data.reaction_counts || {};
                    this.reactionPickerPostId = null;
                    this.markPostRead(post, { silent: true });
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể thả cảm xúc.";
            }
        },
        async toggleSave(post) {
            if (!this.requireLogin()) return;
            try {
                const { data } = await axios.post(
                    `/api/forum/posts/${post.id}/save`,
                    {},
                    this.authHeaders(),
                );
                if (data.ok) {
                    post.is_saved = data.saved;
                    this.markPostRead(post, { silent: true });
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể lưu bài viết.";
            }
        },
        async sharePost(post) {
            try {
                const { data } = await axios.post(`/api/forum/posts/${post.id}/share`);
                post.share_count = data.share_count;
                const url = `${window.location.origin}/forum?post=${post.id}`;
                if (navigator.share) {
                    await navigator.share({ title: post.title || "Bài viết diễn đàn", url });
                } else {
                    await navigator.clipboard.writeText(url);
                    this.message = "Đã sao chép liên kết bài viết.";
                }
            } catch {
                this.message = "Không thể chia sẻ lúc này.";
            }
        },
        async toggleComments(post) {
            post.commentsOpen = !post.commentsOpen;
            if (post.commentsOpen && !post.commentsLoaded) {
                await this.loadComments(post);
            }
            if (post.commentsOpen) {
                this.markPostRead(post, { silent: true });
            }
        },
        async loadComments(post) {
            post.commentsLoading = true;
            try {
                const { data } = await axios.get(
                    `/api/forum/posts/${post.id}/comments`,
                    this.authHeaders(),
                );
                post.comments = data.data || [];
                post.commentsLoaded = true;
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể tải bình luận.";
            } finally {
                post.commentsLoading = false;
            }
        },
        commentDraft(post) {
            return this.commentDrafts[post.id] || "";
        },
        replyDraft(comment) {
            return this.replyDrafts[comment.id] || "";
        },
        replyPlaceholder(comment) {
            return `Trả lời ${this.replyTargets[comment.id] || comment.username}...`;
        },
        async submitComment(post, parentId = null) {
            if (!this.requireLogin()) return;
            const content = parentId ? this.replyDrafts[parentId] || "" : this.commentDrafts[post.id] || "";
            if (!content.trim()) return;

            try {
                const { data } = await axios.post(
                    `/api/forum/posts/${post.id}/comments`,
                    { content, parent_comment_id: parentId },
                    this.authHeaders(),
                );
                if (data.ok) {
                    if (parentId) {
                        this.replyDrafts[parentId] = "";
                        delete this.replyTargets[parentId];
                        this.replyingTo[post.id] = null;
                    } else {
                        this.commentDrafts[post.id] = "";
                    }
                    post.comment_count = data.comment_count;
                    this.markPostRead(post, { silent: true });
                    await this.loadComments(post);
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể gửi bình luận.";
            }
        },
        startReply(post, comment) {
            if (!this.requireLogin()) return;
            const rootId = comment.parent_comment_id || comment.id;
            this.replyingTo[post.id] = rootId;
            this.replyTargets[rootId] = comment.username;
            this.replyDrafts[rootId] = this.withMentionPrefix(this.replyDrafts[rootId] || "", comment.username);
        },
        cancelReply(post, comment) {
            this.replyingTo[post.id] = null;
            delete this.replyTargets[comment.id];
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
            return escaped.replace(/(^|[\s>])(@[\p{L}\p{N}_.-]{1,32})/gu, '$1<span class="forum-mention">$2</span>');
        },
        escapeHtml(value) {
            return String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },
        async toggleCommentReaction(comment) {
            if (!this.requireLogin()) return;
            try {
                const { data } = await axios.post(
                    `/api/forum/comments/${comment.id}/reaction`,
                    {},
                    this.authHeaders(),
                );
                if (data.ok) {
                    comment.liked = data.liked;
                    comment.likes = data.likes;
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể thích bình luận.";
            }
        },
        startEditComment(comment) {
            this.editingCommentId = comment.id;
            this.editCommentContent = comment.content;
        },
        async saveComment(comment) {
            if (!this.editCommentContent.trim()) return;
            try {
                const { data } = await axios.put(
                    `/api/forum/comments/${comment.id}`,
                    { content: this.editCommentContent },
                    this.authHeaders(),
                );
                if (data.ok) {
                    comment.content = data.data.content;
                    this.editingCommentId = null;
                    this.editCommentContent = "";
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể sửa bình luận.";
            }
        },
        async deleteComment(post, comment) {
            if (!confirm("Xóa bình luận này?")) return;
            try {
                await axios.delete(`/api/forum/comments/${comment.id}`, this.authHeaders());
                await this.loadComments(post);
                post.comment_count = Math.max(0, (post.comment_count || 1) - 1);
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể xóa bình luận.";
            }
        },
        isLongPost(post) {
            const content = String(post?.content || "").replace(/<[^>]+>/g, " ");
            return content.length > 420 || content.split(/\r?\n/).length > 6;
        },
        togglePostContent(post) {
            post.contentExpanded = !post.contentExpanded;
            if (post.contentExpanded) {
                this.markPostRead(post, { silent: true });
            }
        },
        observePostCard(el, post) {
            if (!el || !post?.id || !this.isLoggedIn || !post.is_unread) return;
            this.ensureReadObserver();
            if (!this.readObserver) return;
            el.dataset.postId = String(post.id);
            this.readObserver.observe(el);
        },
        ensureReadObserver() {
            if (this.readObserver || typeof window === "undefined" || !("IntersectionObserver" in window)) return;
            this.readObserver = new IntersectionObserver(this.handleReadIntersections, {
                threshold: [0, 0.35, 0.75],
            });
        },
        handleReadIntersections(entries) {
            entries.forEach((entry) => {
                const postId = Number(entry.target.dataset.postId);
                const post = this.posts.find((item) => Number(item.id) === postId);
                if (!post?.is_unread) {
                    this.readObserver?.unobserve(entry.target);
                    return;
                }
                if (entry.isIntersecting && entry.intersectionRatio >= 0.35) {
                    if (this.readTimers[postId]) return;
                    this.readTimers[postId] = window.setTimeout(() => {
                        delete this.readTimers[postId];
                        this.markPostRead(post, { silent: true });
                        this.readObserver?.unobserve(entry.target);
                    }, 1400);
                    return;
                }
                if (this.readTimers[postId]) {
                    window.clearTimeout(this.readTimers[postId]);
                    delete this.readTimers[postId];
                }
            });
        },
        resetReadObserver() {
            this.readObserver?.disconnect();
            Object.values(this.readTimers).forEach((timer) => window.clearTimeout(timer));
            this.readTimers = {};
        },
        teardownReadObserver() {
            this.resetReadObserver();
            this.readObserver = null;
        },
        async markPostRead(post, { silent = false } = {}) {
            if (!this.isLoggedIn || !post?.is_unread) return;
            post.is_unread = false;
            this.stats.unread = Math.max(0, (this.stats.unread || 0) - 1);
            try {
                const { data } = await axios.post(
                    `/api/forum/posts/${post.id}/read`,
                    {},
                    this.authHeaders(),
                );
                if (data.ok) {
                    this.stats.unread = data.unread;
                }
            } catch (err) {
                post.is_unread = true;
                this.stats.unread = (this.stats.unread || 0) + 1;
                if (!silent) {
                    this.message = err.response?.data?.message || "Không thể đánh dấu đã đọc.";
                }
            }
        },
        async markAllRead() {
            if (!this.requireLogin()) return;
            try {
                const { data } = await axios.post(
                    "/api/forum/posts/read-all",
                    {},
                    this.authHeaders(),
                );
                if (data.ok) {
                    this.posts.forEach((post) => {
                        post.is_unread = false;
                    });
                    this.stats.unread = 0;
                    this.message = data.marked ? `Đã đánh dấu ${data.marked} bài là đã đọc.` : "Không còn bài chưa đọc.";
                    if (this.filter === "unread") {
                        await this.loadFeed(true);
                    }
                }
            } catch (err) {
                this.message = err.response?.data?.message || "Không thể đánh dấu tất cả đã đọc.";
            }
        },
        reactionEmoji(key) {
            return this.reactionOptions.find((item) => item.key === key)?.emoji || "👍";
        },
        reactionLabel(key) {
            return this.reactionOptions.find((item) => item.key === key)?.label || "Thích";
        },
        topReactions(post) {
            return Object.entries(post.reaction_counts || {})
                .sort((a, b) => b[1] - a[1])
                .slice(0, 3)
                .map(([key]) => key);
        },
        initial(value) {
            return String(value || "?").trim().slice(0, 1).toUpperCase();
        },
        formatRelative(value) {
            const time = value ? new Date(value).getTime() : 0;
            const diff = Math.max(1, Math.floor((Date.now() - time) / 1000));
            if (diff < 60) return "Vừa xong";
            if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
            if (diff < 604800) return `${Math.floor(diff / 86400)} ngày trước`;
            return new Date(value).toLocaleDateString("vi-VN");
        },
    },
};
</script>

<style scoped>
.forum-page {
    width: min(1220px, calc(100% - 36px));
    padding-top: 0;
    padding-bottom: 64px;
}

.forum-breadcrumb {
    width: min(100%, 1220px);
    margin: 0 auto 8px;
}

.forum-layout {
    width: 100%;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    justify-content: center;
    gap: 16px;
    align-items: start;
}

.forum-rail {
    position: sticky;
    top: 78px;
    display: grid;
    gap: 14px;
}

.forum-panel {
    background: rgba(255, 248, 232, 0.96);
    border: 1px solid rgba(123, 76, 32, 0.24);
    border-radius: 8px;
    box-shadow: 0 18px 40px rgba(58, 28, 10, 0.16);
}

.forum-profile {
    padding: 14px;
    display: flex;
    gap: 12px;
    align-items: center;
}

.forum-profile strong,
.forum-post h2 {
    color: #3f2615;
}

.forum-profile strong {
    display: block;
    line-height: 1.2;
}

.forum-profile span {
    display: block;
    margin-top: 4px;
    font-size: 13px;
}

.forum-profile span,
.forum-head p,
.forum-post__meta div,
.forum-post__summary,
.forum-comment__actions {
    color: #7a624a;
}

.forum-profile__avatar,
.forum-avatar {
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
    font-weight: 800;
}

.forum-profile__avatar img,
.forum-avatar img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.forum-tabs {
    padding: 8px;
}

.forum-tabs button {
    width: 100%;
    border: 0;
    background: transparent;
    display: grid;
    grid-template-columns: 22px 1fr auto;
    gap: 10px;
    align-items: center;
    text-align: left;
    padding: 10px;
    border-radius: 7px;
    color: #4d3623;
    font-weight: 700;
    cursor: pointer;
}

.forum-tabs button:hover,
.forum-tabs button.active {
    background: rgba(198, 123, 33, 0.16);
}

.forum-tabs em {
    font-style: normal;
    font-size: 12px;
    color: #9a7552;
}

.forum-feed {
    display: grid;
    gap: 12px;
    min-width: 0;
    align-self: start;
}

.forum-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 330px);
    gap: 16px;
    align-items: center;
    padding: 0 4px 2px;
}

.forum-head h1 {
    margin: 0;
    font-size: 34px;
    color: #ffe4b5;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.42);
}

.forum-head p {
    margin: 6px 0 0;
    color: rgba(255, 245, 224, 0.82);
}

.forum-feed-tools {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.forum-sort {
    display: inline-flex;
    gap: 6px;
    padding: 4px;
    border-radius: 8px;
    background: rgba(255, 248, 232, 0.92);
    border: 1px solid rgba(123, 76, 32, 0.2);
}

.forum-sort button {
    min-height: 36px;
    border: 0;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 12px;
    color: #60462f;
    background: transparent;
    font-weight: 800;
    cursor: pointer;
}

.forum-sort button:hover,
.forum-sort button.active {
    background: rgba(198, 123, 33, 0.14);
    color: #92470f;
}

.forum-search {
    height: 44px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 248, 232, 0.96);
    border: 1px solid rgba(123, 76, 32, 0.24);
    border-radius: 8px;
    padding: 0 12px;
}

.forum-search input,
.forum-title-input,
.forum-composer textarea,
.forum-edit-box input,
.forum-edit-box textarea,
.forum-comment-form input,
.forum-comment__bubble textarea {
    width: 100%;
    border: 1px solid rgba(123, 76, 32, 0.2);
    background: rgba(255, 253, 247, 0.92);
    border-radius: 8px;
    color: #3e2b1d;
    font: inherit;
}

.forum-search input {
    border: 0;
    background: transparent;
    outline: 0;
}

.forum-composer {
    padding: 14px;
}

.forum-composer__top {
    display: flex;
    gap: 12px;
}

.forum-composer__fields {
    flex: 1;
    display: grid;
    gap: 10px;
}

.forum-segment {
    display: inline-flex;
    width: fit-content;
    padding: 3px;
    border-radius: 8px;
    background: rgba(106, 67, 28, 0.1);
}

.forum-segment button {
    border: 0;
    border-radius: 6px;
    padding: 7px 12px;
    background: transparent;
    color: #6b523b;
    font-weight: 800;
    cursor: pointer;
}

.forum-segment button.active {
    background: #c97723;
    color: #fff;
}

.forum-title-input,
.forum-composer textarea,
.forum-edit-box input,
.forum-edit-box textarea {
    padding: 11px 12px;
    outline: 0;
    resize: vertical;
}

.forum-image-preview,
.forum-post__images {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
    margin-top: 12px;
}

.forum-image-preview figure {
    margin: 0;
}

.forum-image-preview img,
.forum-post__images img {
    width: 100%;
    min-height: 150px;
    max-height: 280px;
    object-fit: cover;
    border-radius: 8px;
}

.forum-composer__actions {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 12px;
    margin-top: 12px;
    border-top: 1px solid rgba(123, 76, 32, 0.15);
}

.forum-tool,
.forum-submit,
.forum-action,
.forum-load-more,
.forum-edit-box__actions button,
.forum-comment-form button {
    border: 0;
    border-radius: 8px;
    font-weight: 800;
    cursor: pointer;
}

.forum-tool {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #6b523b;
    background: rgba(106, 67, 28, 0.08);
    padding: 10px 12px;
}

.forum-tool input {
    display: none;
}

.forum-submit {
    margin-left: auto;
    background: linear-gradient(180deg, #f0ad45, #c87622);
    color: #fff;
    padding: 10px 18px;
}

.forum-submit:disabled,
.forum-comment-form button:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.forum-login-prompt {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    color: #5d442e;
}

.forum-login-prompt a {
    margin-left: auto;
    color: #9d4f0d;
    font-weight: 800;
}

.forum-message {
    padding: 12px 14px;
    border-radius: 8px;
    background: rgba(255, 248, 232, 0.94);
    color: #6b3c14;
    border: 1px solid rgba(123, 76, 32, 0.22);
}

.forum-loading {
    min-height: 180px;
    display: grid;
    place-items: center;
}

.forum-post {
    padding: 14px;
}

.forum-post.pinned {
    border-color: rgba(218, 144, 35, 0.62);
}

.forum-post--unread {
    border-color: rgba(38, 120, 200, 0.38);
    box-shadow:
        inset 4px 0 0 rgba(38, 120, 200, 0.5),
        0 18px 40px rgba(58, 28, 10, 0.16);
}

.forum-post__head {
    display: flex;
    align-items: center;
    gap: 10px;
}

.forum-avatar--post {
    width: 46px;
    height: 46px;
}

.forum-avatar--comment {
    width: 34px;
    height: 34px;
    font-size: 13px;
}

.forum-post__meta {
    min-width: 0;
    flex: 1;
}

.forum-post__meta strong {
    color: #322011;
    display: block;
}

.forum-post__meta div {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 7px;
    font-size: 12px;
}

.forum-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 999px;
    background: rgba(38, 92, 148, 0.12);
    color: #25527f;
    font-weight: 800;
}

.forum-post--announcement .forum-badge {
    background: rgba(191, 61, 42, 0.13);
    color: #9f2d20;
}

.forum-badge--pin {
    background: rgba(200, 126, 28, 0.16);
    color: #9d4f0d;
}

.forum-badge--new {
    background: rgba(38, 120, 200, 0.16);
    color: #215b8f;
}

.forum-badge--new i {
    font-size: 7px;
}

.forum-post__owner {
    display: flex;
    gap: 6px;
}

.forum-post__owner button {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 8px;
    background: rgba(106, 67, 28, 0.08);
    color: #6b523b;
    cursor: pointer;
}

.forum-post h2 {
    margin: 14px 0 8px;
    font-size: 20px;
}

.forum-post__content-wrap {
    position: relative;
}

.forum-post__content-wrap.collapsed {
    max-height: 168px;
    overflow: hidden;
}

.forum-post__content-wrap.collapsed::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 54px;
    background: linear-gradient(180deg, rgba(255, 248, 232, 0), rgba(255, 248, 232, 0.98));
    pointer-events: none;
}

.forum-post__content {
    white-space: pre-wrap;
    color: #3e2b1d;
    line-height: 1.62;
    margin: 12px 0 0;
    overflow-wrap: anywhere;
}

.forum-post__content--rich {
    white-space: normal;
}

.forum-post__content--rich :deep(p),
.forum-post__content--rich :deep(ul),
.forum-post__content--rich :deep(ol),
.forum-post__content--rich :deep(blockquote),
.forum-post__content--rich :deep(h3) {
    margin: 0 0 10px;
}

.forum-post__content--rich :deep(ul),
.forum-post__content--rich :deep(ol) {
    padding-left: 22px;
}

.forum-post__content--rich :deep(blockquote) {
    padding: 8px 12px;
    border-left: 3px solid rgba(198, 123, 33, 0.55);
    background: rgba(198, 123, 33, 0.08);
    border-radius: 0 7px 7px 0;
}

.forum-post__content--rich :deep(a) {
    color: #a65316;
    font-weight: 800;
}

.forum-read-more {
    border: 0;
    background: transparent;
    color: #a65316;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 0;
    margin-top: 2px;
    cursor: pointer;
}

.forum-edit-box {
    display: grid;
    gap: 10px;
    margin-top: 12px;
}

.forum-edit-box__actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.forum-edit-box__actions button,
.forum-comment-form button {
    padding: 8px 12px;
    background: #c97723;
    color: #fff;
}

.forum-edit-box__actions .plain,
.forum-comment-form .plain {
    background: transparent;
    color: #765b43;
}

.forum-post__summary {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 0 8px;
    font-size: 13px;
}

.forum-post__summary button {
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.forum-reaction-stack {
    display: inline-flex;
    margin-right: 4px;
}

.forum-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(116px, 1fr));
    gap: 6px;
    border-top: 1px solid rgba(123, 76, 32, 0.15);
    border-bottom: 1px solid rgba(123, 76, 32, 0.15);
    padding: 6px 0;
}

.forum-action {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    background: transparent;
    color: #644a32;
}

.forum-action:hover,
.forum-action.active {
    background: rgba(198, 123, 33, 0.13);
    color: #b55e18;
}

.forum-reaction-wrap {
    position: relative;
}

.forum-reaction-picker {
    position: absolute;
    left: 0;
    bottom: calc(100% + 8px);
    z-index: 20;
    display: flex;
    gap: 4px;
    padding: 6px;
    background: #fff8e8;
    border: 1px solid rgba(123, 76, 32, 0.18);
    border-radius: 999px;
    box-shadow: 0 10px 26px rgba(58, 28, 10, 0.24);
}

.forum-reaction-picker button {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 50%;
    background: transparent;
    font-size: 22px;
    cursor: pointer;
}

.forum-reaction-picker button:hover {
    transform: translateY(-3px);
}

.forum-comments {
    padding-top: 12px;
    display: grid;
    gap: 10px;
}

.forum-comment-form {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    gap: 8px;
    align-items: center;
}

.forum-comment-form--reply {
    margin-left: 48px;
    grid-template-columns: 34px minmax(0, 1fr) auto auto;
}

.forum-comment-form input {
    min-height: 38px;
    padding: 0 12px;
    outline: 0;
}

.forum-comment-thread {
    display: grid;
    gap: 8px;
}

.forum-comment {
    display: flex;
    gap: 8px;
}

.forum-comment__body {
    min-width: 0;
    flex: 1;
}

.forum-comment__bubble {
    width: fit-content;
    max-width: min(100%, 620px);
    padding: 9px 11px;
    border-radius: 8px;
    background: rgba(106, 67, 28, 0.08);
    color: #3e2b1d;
}

.forum-comment__bubble strong {
    display: block;
    font-size: 13px;
    margin-bottom: 3px;
}

.forum-comment__bubble p {
    margin: 0;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.forum-comment__bubble :deep(.forum-mention) {
    color: #a65316;
    font-weight: 900;
}

.forum-comment__bubble textarea {
    min-width: min(440px, 70vw);
    padding: 8px;
}

.forum-comment__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    align-items: center;
    padding: 4px 0 0 10px;
    font-size: 12px;
}

.forum-comment__actions button {
    border: 0;
    background: transparent;
    color: #6d4d32;
    font-weight: 800;
    cursor: pointer;
}

.forum-comment__actions button.active {
    color: #b55e18;
}

.forum-replies {
    display: grid;
    gap: 8px;
    margin-left: 48px;
}

.forum-locked,
.forum-comments__loading {
    color: #765b43;
    font-size: 13px;
}

.forum-load-more {
    width: 100%;
    padding: 10px 12px;
    background: rgba(198, 123, 33, 0.14);
    color: #8b4611;
}

.forum-load-more {
    background: rgba(255, 248, 232, 0.96);
    border: 1px solid rgba(123, 76, 32, 0.24);
}

@media (max-width: 1120px) {
    .forum-layout {
        grid-template-columns: 200px minmax(0, 1fr);
    }
}

@media (max-width: 820px) {
    .forum-page {
        padding-top: 0;
    }
    .forum-layout {
        width: min(100% - 18px, 680px);
        grid-template-columns: 1fr;
    }
    .forum-rail {
        position: static;
    }
    .forum-rail--left {
        order: 2;
    }
    .forum-tabs {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .forum-head {
        grid-template-columns: 1fr;
    }
    .forum-head h1 {
        font-size: 28px;
    }
    .forum-actions {
        grid-template-columns: repeat(2, 1fr);
    }
    .forum-composer__top {
        align-items: flex-start;
    }
    .forum-composer__actions {
        flex-wrap: wrap;
    }
    .forum-submit {
        width: 100%;
        margin-left: 0;
    }
    .forum-comment-form,
    .forum-comment-form--reply {
        margin-left: 0;
        grid-template-columns: 34px minmax(0, 1fr) auto;
    }
    .forum-comment-form--reply .plain {
        grid-column: 2;
    }
    .forum-replies {
        margin-left: 28px;
    }
}
</style>
