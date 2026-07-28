<template>
    <div class="client-page forum-page">
        <div class="breadcrumb client-breadcrumb forum-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Diễn đàn</span>
        </div>

        <section class="forum-panel forum-hero">
            <div class="forum-hero__copy">
                <span class="forum-hero__kicker">
                    <img
                        src="/assets/pixel/forum-chat.png"
                        alt=""
                        aria-hidden="true"
                    />
                    Trạm liên lạc Horizon
                </span>
                <h1>Diễn đàn chiến binh</h1>
                <p>
                    Theo dõi tin máy chủ, chia sẻ kinh nghiệm và cùng góp ý để
                    thế giới Ngọc Rồng ngày một hoàn thiện.
                </p>

                <div class="forum-hero__stats" aria-label="Thống kê diễn đàn">
                    <div>
                        <strong>{{ stats.all || 0 }}</strong>
                        <span>Bài viết</span>
                    </div>
                    <div>
                        <strong>{{ stats.announcements || 0 }}</strong>
                        <span>Thông báo</span>
                    </div>
                    <div>
                        <strong>{{ stats.unread || 0 }}</strong>
                        <span>Chưa đọc</span>
                    </div>
                </div>
            </div>

            <div class="forum-hero__art" aria-hidden="true">
                <span class="forum-hero__signal"></span>
                <img src="/assets/pixel/forum-mail.png" alt="" />
                <strong>KÊNH LIÊN LẠC</strong>
                <small>Luôn mở cho mọi chiến binh</small>
            </div>

            <form
                class="forum-search"
                role="search"
                @submit.prevent="loadFeed(true)"
            >
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    v-model.trim="search"
                    aria-label="Tìm kiếm trong diễn đàn"
                    placeholder="Tìm bài viết hoặc tên người đăng..."
                />
                <button type="submit">Tìm kiếm</button>
            </form>
        </section>

        <div class="forum-layout">
            <aside class="forum-rail forum-rail--left">
                <div class="forum-panel forum-profile">
                    <div class="forum-profile__avatar">
                        <img
                            v-if="currentAvatarUrl"
                            :src="currentAvatarUrl"
                            alt=""
                        />
                        <span v-else>{{ currentInitial }}</span>
                    </div>
                    <div>
                        <strong>{{ currentUsername || "Người chơi" }}</strong>
                        <span>{{
                            isLoggedIn ? "Đang trực tuyến" : "Chưa đăng nhập"
                        }}</span>
                    </div>
                    <router-link
                        v-if="!isLoggedIn"
                        class="forum-profile__login"
                        to="/login"
                    >
                        Đăng nhập
                    </router-link>
                </div>

                <nav class="forum-panel forum-tabs">
                    <div class="forum-tabs__head">
                        <img
                            src="/assets/pixel/forum-filter.png"
                            alt=""
                            aria-hidden="true"
                        />
                        <div>
                            <strong>Khu vực diễn đàn</strong>
                            <span>Chọn bảng tin muốn xem</span>
                        </div>
                    </div>
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        type="button"
                        :class="{ active: filter === tab.key }"
                        @click="setFilter(tab.key)"
                    >
                        <img :src="tab.asset" alt="" aria-hidden="true" />
                        <span>{{ tab.label }}</span>
                        <em>{{ stats[tab.countKey] || 0 }}</em>
                    </button>
                </nav>
            </aside>

            <main class="forum-feed">
                <header class="forum-feed-head forum-panel">
                    <div>
                        <span class="forum-feed-head__eyebrow"
                            >Đang hiển thị</span
                        >
                        <h2>{{ activeFilterTab.label }}</h2>
                        <p>{{ activeFilterDescription }}</p>
                    </div>
                    <div class="forum-sort" aria-label="Sắp xếp bài viết">
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
                </header>

                <section class="forum-panel forum-composer">
                    <template v-if="isLoggedIn">
                        <header class="forum-composer__heading">
                            <img
                                src="/assets/pixel/forum-mail.png"
                                alt=""
                                aria-hidden="true"
                            />
                            <div>
                                <strong>Gửi tin đến cộng đồng</strong>
                                <span
                                    >Chia sẻ ngắn gọn, rõ ràng và tôn trọng
                                    nhau.</span
                                >
                            </div>
                        </header>
                        <div class="forum-composer__top">
                            <div class="forum-avatar">
                                <img
                                    v-if="currentAvatarUrl"
                                    :src="currentAvatarUrl"
                                    alt=""
                                />
                                <span v-else>{{ currentInitial }}</span>
                            </div>
                            <div class="forum-composer__fields">
                                <div class="forum-segment">
                                    <button
                                        type="button"
                                        :class="{
                                            active:
                                                composer.type === 'player_post',
                                        }"
                                        @click="composer.type = 'player_post'"
                                    >
                                        Bài viết
                                    </button>
                                    <button
                                        type="button"
                                        :class="{
                                            active:
                                                composer.type === 'feedback',
                                        }"
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

                        <div
                            v-if="composer.previews.length"
                            class="forum-image-preview"
                        >
                            <figure
                                v-for="preview in composer.previews"
                                :key="preview"
                            >
                                <img :src="preview" alt="" />
                            </figure>
                        </div>

                        <div class="forum-composer__actions">
                            <label class="forum-tool">
                                <i class="fa-regular fa-images"></i>
                                <span>Ảnh</span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    @change="selectImages"
                                />
                            </label>
                            <button
                                type="button"
                                class="forum-tool"
                                @click="
                                    composer.content += composer.content
                                        ? '\n#hoi-dap '
                                        : '#hoi-dap '
                                "
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
                        <img
                            src="/assets/pixel/forum-profile.png"
                            alt=""
                            aria-hidden="true"
                        />
                        <div>
                            <strong>Tham gia cuộc trò chuyện</strong>
                            <span
                                >Đăng nhập để đăng bài, bình luận và lưu nội
                                dung bạn quan tâm.</span
                            >
                        </div>
                        <router-link to="/login">Đăng nhập ngay</router-link>
                    </div>
                </section>

                <div v-if="message" class="forum-message">{{ message }}</div>

                <div v-if="loading" class="forum-loading">
                    <div class="page-loading__spinner"></div>
                </div>

                <div v-else-if="!posts.length" class="client-empty forum-empty">
                    <img
                        src="/assets/pixel/forum-chat.png"
                        alt=""
                        aria-hidden="true"
                    />
                    <strong>Chưa có tín hiệu mới</strong>
                    <span>Hãy thử một khu vực khác hoặc thay đổi từ khóa.</span>
                </div>

                <template v-else>
                    <article
                        v-for="post in posts"
                        :key="post.id"
                        :ref="(el) => observePostCard(el, post)"
                        class="forum-panel forum-post"
                        :class="[
                            `forum-post--${post.type}`,
                            {
                                pinned: post.is_pinned,
                                'forum-post--unread': post.is_unread,
                            },
                        ]"
                    >
                        <div class="forum-post__head">
                            <img
                                class="forum-post__type-icon"
                                :src="postTypeAsset(post.type)"
                                alt=""
                                aria-hidden="true"
                            />
                            <div class="forum-avatar forum-avatar--post">
                                <img
                                    v-if="post.author_avatar"
                                    :src="post.author_avatar"
                                    alt=""
                                />
                                <span v-else>{{
                                    initial(post.author_username)
                                }}</span>
                            </div>
                            <div class="forum-post__meta">
                                <strong>{{ post.author_username }}</strong>
                                <div>
                                    <span class="forum-badge">{{
                                        post.type_label
                                    }}</span>
                                    <span
                                        v-if="post.is_pinned"
                                        class="forum-badge forum-badge--pin"
                                    >
                                        <i class="fa-solid fa-thumbtack"></i>
                                        Ghim
                                    </span>
                                    <span
                                        v-if="post.is_unread"
                                        class="forum-badge forum-badge--new"
                                    >
                                        <i class="fa-solid fa-circle"></i>
                                        Mới
                                    </span>
                                    <span>{{
                                        formatRelative(post.created_at)
                                    }}</span>
                                </div>
                            </div>
                            <div
                                v-if="post.can_edit || post.can_delete"
                                class="forum-post__owner"
                            >
                                <button
                                    type="button"
                                    @click="startEditPost(post)"
                                >
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
                                    :class="{
                                        active: editPost.type === 'player_post',
                                    }"
                                    @click="editPost.type = 'player_post'"
                                >
                                    Bài viết
                                </button>
                                <button
                                    type="button"
                                    :class="{
                                        active: editPost.type === 'feedback',
                                    }"
                                    @click="editPost.type = 'feedback'"
                                >
                                    Góp ý
                                </button>
                            </div>
                            <input
                                v-model="editPost.title"
                                maxlength="160"
                                placeholder="Tiêu đề"
                            />
                            <textarea
                                v-model="editPost.content"
                                rows="5"
                            ></textarea>
                            <div class="forum-edit-box__actions">
                                <button
                                    type="button"
                                    class="plain"
                                    @click="cancelEditPost"
                                >
                                    Hủy
                                </button>
                                <button type="submit">Lưu</button>
                            </div>
                        </form>

                        <template v-else>
                            <h2 v-if="post.title">{{ post.title }}</h2>
                            <div
                                class="forum-post__content-wrap"
                                :class="{
                                    collapsed:
                                        isLongPost(post) &&
                                        !post.contentExpanded,
                                }"
                            >
                                <div
                                    v-if="post.type === 'announcement'"
                                    class="forum-post__content forum-post__content--rich"
                                    v-html="post.content"
                                ></div>
                                <p v-else class="forum-post__content">
                                    {{ post.content }}
                                </p>
                            </div>
                            <button
                                v-if="isLongPost(post)"
                                type="button"
                                class="forum-read-more"
                                @click="togglePostContent(post)"
                            >
                                {{
                                    post.contentExpanded
                                        ? "Thu gọn"
                                        : "Xem thêm"
                                }}
                                <i
                                    :class="
                                        post.contentExpanded
                                            ? 'fa-solid fa-chevron-up'
                                            : 'fa-solid fa-chevron-down'
                                    "
                                ></i>
                            </button>
                        </template>

                        <div
                            v-if="post.images?.length"
                            class="forum-post__images"
                            :class="`count-${Math.min(post.images.length, 4)}`"
                        >
                            <img
                                v-for="image in post.images"
                                :key="image"
                                :src="image"
                                alt=""
                            />
                        </div>

                        <div class="forum-post__summary">
                            <button
                                type="button"
                                @click="toggleReactionPicker(post.id)"
                            >
                                <span class="forum-reaction-stack">
                                    <span
                                        v-for="reaction in topReactions(post)"
                                        :key="reaction"
                                    >
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
                                    @click="
                                        post.user_reaction
                                            ? reactToPost(
                                                  post,
                                                  post.user_reaction,
                                              )
                                            : toggleReactionPicker(post.id)
                                    "
                                >
                                    <span>{{
                                        post.user_reaction
                                            ? reactionEmoji(post.user_reaction)
                                            : "👍"
                                    }}</span>
                                    {{
                                        post.user_reaction
                                            ? reactionLabel(post.user_reaction)
                                            : "Cảm xúc"
                                    }}
                                </button>
                                <div
                                    v-if="reactionPickerPostId === post.id"
                                    class="forum-reaction-picker"
                                >
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
                            <button
                                type="button"
                                class="forum-action"
                                @click="toggleComments(post)"
                            >
                                <i class="fa-regular fa-comment"></i>
                                Bình luận
                            </button>
                            <button
                                type="button"
                                class="forum-action"
                                @click="sharePost(post)"
                            >
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

                        <section
                            v-if="post.commentsOpen"
                            class="forum-comments"
                        >
                            <div
                                v-if="post.commentsLoading"
                                class="forum-comments__loading"
                            >
                                Đang tải bình luận...
                            </div>

                            <form
                                v-if="isLoggedIn && !post.is_locked"
                                class="forum-comment-form"
                                @submit.prevent="submitComment(post)"
                            >
                                <div class="forum-avatar forum-avatar--comment">
                                    <img
                                        v-if="currentAvatarUrl"
                                        :src="currentAvatarUrl"
                                        alt=""
                                    />
                                    <span v-else>{{ currentInitial }}</span>
                                </div>
                                <input
                                    v-model="commentDrafts[post.id]"
                                    placeholder="Viết bình luận..."
                                />
                                <button
                                    type="submit"
                                    :disabled="!commentDraft(post).trim()"
                                >
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </form>
                            <div
                                v-else-if="post.is_locked"
                                class="forum-locked"
                            >
                                <i class="fa-solid fa-lock"></i>
                                Bài viết đã khóa bình luận.
                            </div>

                            <div
                                v-for="comment in post.comments || []"
                                :key="comment.id"
                                class="forum-comment-thread"
                            >
                                <div class="forum-comment">
                                    <div
                                        class="forum-avatar forum-avatar--comment"
                                    >
                                        <img
                                            v-if="comment.avatar_url"
                                            :src="comment.avatar_url"
                                            alt=""
                                        />
                                        <span v-else>{{
                                            initial(comment.username)
                                        }}</span>
                                    </div>
                                    <div class="forum-comment__body">
                                        <div class="forum-comment__bubble">
                                            <strong>{{
                                                comment.username
                                            }}</strong>
                                            <template
                                                v-if="
                                                    editingCommentId ===
                                                    comment.id
                                                "
                                            >
                                                <textarea
                                                    v-model="editCommentContent"
                                                    rows="2"
                                                ></textarea>
                                            </template>
                                            <p
                                                v-else
                                                v-html="
                                                    formatMentionText(
                                                        comment.content,
                                                    )
                                                "
                                            ></p>
                                        </div>
                                        <div class="forum-comment__actions">
                                            <button
                                                type="button"
                                                :class="{
                                                    active: comment.liked,
                                                }"
                                                @click="
                                                    toggleCommentReaction(
                                                        comment,
                                                    )
                                                "
                                            >
                                                Thích
                                            </button>
                                            <button
                                                type="button"
                                                @click="
                                                    startReply(post, comment)
                                                "
                                            >
                                                Phản hồi
                                            </button>
                                            <button
                                                v-if="comment.can_edit"
                                                type="button"
                                                @click="
                                                    startEditComment(comment)
                                                "
                                            >
                                                Sửa
                                            </button>
                                            <button
                                                v-if="comment.can_delete"
                                                type="button"
                                                @click="
                                                    deleteComment(post, comment)
                                                "
                                            >
                                                Xóa
                                            </button>
                                            <button
                                                v-if="
                                                    editingCommentId ===
                                                    comment.id
                                                "
                                                type="button"
                                                @click="saveComment(comment)"
                                            >
                                                Lưu
                                            </button>
                                            <span>{{
                                                formatRelative(
                                                    comment.created_at,
                                                )
                                            }}</span>
                                            <span v-if="comment.likes"
                                                >{{ comment.likes }} thích</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="comment.replies?.length"
                                    class="forum-replies"
                                >
                                    <div
                                        v-for="reply in comment.replies"
                                        :key="reply.id"
                                        class="forum-comment forum-comment--reply"
                                    >
                                        <div
                                            class="forum-avatar forum-avatar--comment"
                                        >
                                            <img
                                                v-if="reply.avatar_url"
                                                :src="reply.avatar_url"
                                                alt=""
                                            />
                                            <span v-else>{{
                                                initial(reply.username)
                                            }}</span>
                                        </div>
                                        <div class="forum-comment__body">
                                            <div class="forum-comment__bubble">
                                                <strong>{{
                                                    reply.username
                                                }}</strong>
                                                <template
                                                    v-if="
                                                        editingCommentId ===
                                                        reply.id
                                                    "
                                                >
                                                    <textarea
                                                        v-model="
                                                            editCommentContent
                                                        "
                                                        rows="2"
                                                    ></textarea>
                                                </template>
                                                <p
                                                    v-else
                                                    v-html="
                                                        formatMentionText(
                                                            reply.content,
                                                        )
                                                    "
                                                ></p>
                                            </div>
                                            <div class="forum-comment__actions">
                                                <button
                                                    type="button"
                                                    :class="{
                                                        active: reply.liked,
                                                    }"
                                                    @click="
                                                        toggleCommentReaction(
                                                            reply,
                                                        )
                                                    "
                                                >
                                                    Thích
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="
                                                        startReply(post, reply)
                                                    "
                                                >
                                                    Phản hồi
                                                </button>
                                                <button
                                                    v-if="reply.can_edit"
                                                    type="button"
                                                    @click="
                                                        startEditComment(reply)
                                                    "
                                                >
                                                    Sửa
                                                </button>
                                                <button
                                                    v-if="reply.can_delete"
                                                    type="button"
                                                    @click="
                                                        deleteComment(
                                                            post,
                                                            reply,
                                                        )
                                                    "
                                                >
                                                    Xóa
                                                </button>
                                                <button
                                                    v-if="
                                                        editingCommentId ===
                                                        reply.id
                                                    "
                                                    type="button"
                                                    @click="saveComment(reply)"
                                                >
                                                    Lưu
                                                </button>
                                                <span>{{
                                                    formatRelative(
                                                        reply.created_at,
                                                    )
                                                }}</span>
                                                <span v-if="reply.likes"
                                                    >{{
                                                        reply.likes
                                                    }}
                                                    thích</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form
                                    v-if="replyingTo[post.id] === comment.id"
                                    class="forum-comment-form forum-comment-form--reply"
                                    @submit.prevent="
                                        submitComment(post, comment.id)
                                    "
                                >
                                    <div
                                        class="forum-avatar forum-avatar--comment"
                                    >
                                        <img
                                            v-if="currentAvatarUrl"
                                            :src="currentAvatarUrl"
                                            alt=""
                                        />
                                        <span v-else>{{ currentInitial }}</span>
                                    </div>
                                    <input
                                        v-model="replyDrafts[comment.id]"
                                        :placeholder="replyPlaceholder(comment)"
                                    />
                                    <button
                                        type="button"
                                        class="plain"
                                        @click="cancelReply(post, comment)"
                                    >
                                        Hủy
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="!replyDraft(comment).trim()"
                                    >
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
                {
                    key: "all",
                    label: "Tất cả",
                    asset: "/assets/pixel/forum-filter.png",
                    countKey: "all",
                },
                {
                    key: "unread",
                    label: "Chưa đọc",
                    asset: "/assets/pixel/forum-unread.png",
                    countKey: "unread",
                },
                {
                    key: "announcements",
                    label: "Thông báo",
                    asset: "/assets/pixel/forum-announcement.png",
                    countKey: "announcements",
                },
                {
                    key: "players",
                    label: "Người chơi",
                    asset: "/assets/pixel/forum-players.png",
                    countKey: "players",
                },
                {
                    key: "feedback",
                    label: "Góp ý",
                    asset: "/assets/pixel/forum-feedback.png",
                    countKey: "feedback",
                },
                {
                    key: "mine",
                    label: "Bài của tôi",
                    asset: "/assets/pixel/forum-profile.png",
                    countKey: "mine",
                },
                {
                    key: "saved",
                    label: "Đã lưu",
                    asset: "/assets/pixel/forum-saved.png",
                    countKey: "saved",
                },
            ],
            sortOptions: [
                {
                    key: "unread",
                    label: "Chưa đọc",
                    icon: "fa-regular fa-circle-dot",
                },
                {
                    key: "latest",
                    label: "Mới nhất",
                    icon: "fa-regular fa-clock",
                },
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
                return (
                    JSON.parse(localStorage.getItem("user") || "{}").username ||
                    ""
                );
            } catch {
                return "";
            }
        },
        currentInitial() {
            return this.initial(this.currentUsername || "U");
        },
        activeFilterTab() {
            return (
                this.filterTabs.find((tab) => tab.key === this.filter) ||
                this.filterTabs[0]
            );
        },
        activeFilterDescription() {
            const descriptions = {
                all: "Mọi tín hiệu mới nhất từ cộng đồng Horizon.",
                unread: "Những bài viết bạn chưa ghé qua.",
                announcements: "Tin chính thức và cập nhật từ quản trị viên.",
                players: "Chia sẻ, hỏi đáp và kinh nghiệm của người chơi.",
                feedback: "Ý tưởng và đề xuất giúp máy chủ hoàn thiện hơn.",
                mine: "Các bài viết do chính bạn đăng tải.",
                saved: "Danh sách nội dung bạn đã đánh dấu để xem lại.",
            };

            return descriptions[this.filter] || descriptions.all;
        },
    },
    async mounted() {
        await Promise.all([
            this.loadFeed(true),
            this.loadCurrentProfileAvatar(),
        ]);
    },
    beforeUnmount() {
        this.teardownReadObserver();
    },
    methods: {
        authHeaders() {
            const token = localStorage.getItem("token");
            return token
                ? { headers: { Authorization: `Bearer ${token}` } }
                : {};
        },
        requireLogin() {
            if (this.isLoggedIn) return true;
            this.message = "Bạn cần đăng nhập để dùng chức năng này.";
            return false;
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
                    sort:
                        this.isLoggedIn || this.sort !== "unread"
                            ? this.sort
                            : "latest",
                    search: this.search,
                });
                const { data } = await axios.get(
                    `/api/forum/posts?${params}`,
                    this.authHeaders(),
                );
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
                this.message =
                    err.response?.data?.message || "Không thể tải diễn đàn.";
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
            if (
                (filter === "mine" ||
                    filter === "saved" ||
                    filter === "unread") &&
                !this.requireLogin()
            )
                return;
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
            this.composer.previews = files.map((file) =>
                URL.createObjectURL(file),
            );
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
                this.composer.images.forEach((file) =>
                    form.append("images[]", file),
                );
                await axios.post("/api/forum/posts", form, this.authHeaders());
                this.composer.previews.forEach((url) =>
                    URL.revokeObjectURL(url),
                );
                this.composer = emptyComposer();
                this.message = "Đã đăng bài lên diễn đàn.";
                await this.loadFeed(true);
            } catch (err) {
                this.message =
                    err.response?.data?.message ||
                    "Không thể đăng bài lúc này.";
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
                this.message =
                    err.response?.data?.message || "Không thể lưu bài viết.";
            }
        },
        async deletePost(post) {
            if (!confirm("Xóa bài viết này khỏi diễn đàn?")) return;
            try {
                await axios.delete(
                    `/api/forum/posts/${post.id}`,
                    this.authHeaders(),
                );
                this.posts = this.posts.filter((item) => item.id !== post.id);
                this.message = "Đã xóa bài viết.";
            } catch (err) {
                this.message =
                    err.response?.data?.message || "Không thể xóa bài viết.";
            }
        },
        toggleReactionPicker(postId) {
            this.reactionPickerPostId =
                this.reactionPickerPostId === postId ? null : postId;
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
                this.message =
                    err.response?.data?.message || "Không thể thả cảm xúc.";
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
                this.message =
                    err.response?.data?.message || "Không thể lưu bài viết.";
            }
        },
        async sharePost(post) {
            try {
                const { data } = await axios.post(
                    `/api/forum/posts/${post.id}/share`,
                );
                post.share_count = data.share_count;
                const url = `${window.location.origin}/forum?post=${post.id}`;
                if (navigator.share) {
                    await navigator.share({
                        title: post.title || "Bài viết diễn đàn",
                        url,
                    });
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
                this.message =
                    err.response?.data?.message || "Không thể tải bình luận.";
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
            const content = parentId
                ? this.replyDrafts[parentId] || ""
                : this.commentDrafts[post.id] || "";
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
                this.message =
                    err.response?.data?.message || "Không thể gửi bình luận.";
            }
        },
        startReply(post, comment) {
            if (!this.requireLogin()) return;
            const rootId = comment.parent_comment_id || comment.id;
            this.replyingTo[post.id] = rootId;
            this.replyTargets[rootId] = comment.username;
            this.replyDrafts[rootId] = this.withMentionPrefix(
                this.replyDrafts[rootId] || "",
                comment.username,
            );
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
            return escaped.replace(
                /(^|[\s>])(@[\p{L}\p{N}_.-]{1,32})/gu,
                '$1<span class="forum-mention">$2</span>',
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
                this.message =
                    err.response?.data?.message || "Không thể thích bình luận.";
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
                this.message =
                    err.response?.data?.message || "Không thể sửa bình luận.";
            }
        },
        async deleteComment(post, comment) {
            if (!confirm("Xóa bình luận này?")) return;
            try {
                await axios.delete(
                    `/api/forum/comments/${comment.id}`,
                    this.authHeaders(),
                );
                await this.loadComments(post);
                post.comment_count = Math.max(0, (post.comment_count || 1) - 1);
            } catch (err) {
                this.message =
                    err.response?.data?.message || "Không thể xóa bình luận.";
            }
        },
        isLongPost(post) {
            const content = String(post?.content || "").replace(
                /<[^>]+>/g,
                " ",
            );
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
            if (
                this.readObserver ||
                typeof window === "undefined" ||
                !("IntersectionObserver" in window)
            )
                return;
            this.readObserver = new IntersectionObserver(
                this.handleReadIntersections,
                {
                    threshold: [0, 0.35, 0.75],
                },
            );
        },
        handleReadIntersections(entries) {
            entries.forEach((entry) => {
                const postId = Number(entry.target.dataset.postId);
                const post = this.posts.find(
                    (item) => Number(item.id) === postId,
                );
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
            Object.values(this.readTimers).forEach((timer) =>
                window.clearTimeout(timer),
            );
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
                    this.message =
                        err.response?.data?.message ||
                        "Không thể đánh dấu đã đọc.";
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
                    this.message = data.marked
                        ? `Đã đánh dấu ${data.marked} bài là đã đọc.`
                        : "Không còn bài chưa đọc.";
                    if (this.filter === "unread") {
                        await this.loadFeed(true);
                    }
                }
            } catch (err) {
                this.message =
                    err.response?.data?.message ||
                    "Không thể đánh dấu tất cả đã đọc.";
            }
        },
        reactionEmoji(key) {
            return (
                this.reactionOptions.find((item) => item.key === key)?.emoji ||
                "👍"
            );
        },
        reactionLabel(key) {
            return (
                this.reactionOptions.find((item) => item.key === key)?.label ||
                "Thích"
            );
        },
        postTypeAsset(type) {
            if (type === "announcement") {
                return "/assets/pixel/forum-announcement.png";
            }
            if (type === "feedback") {
                return "/assets/pixel/forum-feedback.png";
            }
            return "/assets/pixel/forum-players.png";
        },
        topReactions(post) {
            return Object.entries(post.reaction_counts || {})
                .sort((a, b) => b[1] - a[1])
                .slice(0, 3)
                .map(([key]) => key);
        },
        initial(value) {
            return String(value || "?")
                .trim()
                .slice(0, 1)
                .toUpperCase();
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
    margin-top: -67px;
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
.forum-search input::placeholder,
.forum-title-input::placeholder,
.forum-composer textarea::placeholder,
.forum-edit-box input::placeholder,
.forum-edit-box textarea::placeholder,
.forum-comment-form input::placeholder,
.forum-comment__bubble textarea::placeholder {
    color: rgba(62, 43, 29, 0.65);
    opacity: 1;
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
    background: linear-gradient(
        180deg,
        rgba(255, 248, 232, 0),
        rgba(255, 248, 232, 0.98)
    );
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

/* Horizon forum redesign */
.forum-page {
    --forum-ink: #3f291f;
    --forum-muted: #806550;
    --forum-line: #754525;
    --forum-line-soft: #c78b4f;
    --forum-paper: #fff7dc;
    --forum-paper-deep: #f8e5b5;
    --forum-paper-soft: #fffdf0;
    --forum-orange: #ec7424;
    --forum-orange-dark: #a74317;
    --forum-blue: #238fc4;
    --forum-blue-dark: #15577c;
    --forum-green: #5a9b42;
    --forum-shadow: rgb(69 42 26 / 20%);

    width: min(1180px, calc(100% - 32px));
    padding: 0 0 72px;
    color: var(--forum-ink);
    font-family: var(--font-sans);
}

.forum-breadcrumb {
    width: 100%;
    margin: 0 0 14px;
}

.forum-panel {
    color: var(--forum-ink) !important;
    background: var(--forum-paper) !important;
    border: 2px solid var(--forum-line) !important;
    border-radius: 2px !important;
    box-shadow: 4px 4px 0 var(--forum-shadow) !important;
}

.forum-hero {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 236px;
    gap: 0 28px;
    min-height: 250px;
    margin-bottom: 20px;
    padding: 30px 32px 24px;
    overflow: hidden;
    background:
        linear-gradient(
            90deg,
            rgb(255 247 220 / 97%) 0%,
            rgb(255 247 220 / 92%) 58%,
            rgb(255 247 220 / 70%) 100%
        ),
        url("/assets/pixel/nro-page-map.webp") center bottom / cover no-repeat !important;
}

.forum-hero::before {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 8px;
    content: "";
    background: repeating-linear-gradient(
        90deg,
        var(--forum-green) 0 12px,
        #86bf4f 12px 24px
    );
}

.forum-hero::after {
    position: absolute;
    right: 20px;
    bottom: 15px;
    width: 102px;
    height: 74px;
    content: "";
    opacity: 0.08;
    background: url("/assets/pixel/brand-orb.png") center / contain no-repeat;
    image-rendering: pixelated;
}

.forum-hero__copy {
    position: relative;
    z-index: 1;
    align-self: center;
}

.forum-hero__kicker {
    display: inline-flex;
    min-height: 32px;
    align-items: center;
    gap: 8px;
    color: var(--forum-orange-dark);
    font-family: var(--pixel-font);
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.forum-hero__kicker img {
    width: 30px;
    height: 30px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-hero h1 {
    margin: 4px 0 8px;
    color: var(--forum-ink) !important;
    font-family: var(--pixel-font) !important;
    font-size: clamp(2.6rem, 6vw, 4.6rem) !important;
    font-weight: 800 !important;
    line-height: 0.84 !important;
    letter-spacing: 0.01em;
    text-shadow: 2px 2px 0 rgb(255 255 255 / 72%) !important;
}

.forum-hero__copy > p {
    max-width: 650px;
    margin: 0;
    color: var(--forum-muted) !important;
    font-size: 0.94rem;
    line-height: 1.65;
}

.forum-hero__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.forum-hero__stats div {
    display: grid;
    min-width: 104px;
    grid-template-columns: auto 1fr;
    align-items: baseline;
    gap: 6px;
    padding: 7px 10px;
    background: rgb(255 253 240 / 85%);
    border: 1px solid var(--forum-line-soft);
    box-shadow: 2px 2px 0 rgb(117 69 37 / 12%);
}

.forum-hero__stats strong {
    color: var(--forum-orange-dark);
    font-family: var(--pixel-font);
    font-size: 1.45rem;
    line-height: 1;
}

.forum-hero__stats span {
    color: var(--forum-muted);
    font-size: 0.7rem;
    font-weight: 700;
}

.forum-hero__art {
    position: relative;
    z-index: 1;
    display: flex;
    align-self: center;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 18px 14px 15px;
    background: rgb(255 245 213 / 88%);
    border: 2px solid var(--forum-line);
    box-shadow:
        inset 0 0 0 4px rgb(255 255 255 / 36%),
        4px 4px 0 rgb(117 69 37 / 17%);
    text-align: center;
}

.forum-hero__art img {
    width: 86px;
    height: 70px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-hero__art strong {
    margin-top: 4px;
    color: var(--forum-orange-dark);
    font-family: var(--pixel-font);
    font-size: 1.2rem;
    letter-spacing: 0.05em;
}

.forum-hero__art small {
    margin-top: 2px;
    color: var(--forum-muted);
    font-size: 0.68rem;
}

.forum-hero__signal {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 9px;
    height: 9px;
    background: #78bc54;
    border: 1px solid #346e2b;
    box-shadow: 0 0 0 3px rgb(120 188 84 / 18%);
}

.forum-search {
    position: relative;
    z-index: 2;
    display: grid;
    width: 100% !important;
    max-width: none !important;
    grid-column: 1 / -1;
    grid-template-columns: 28px minmax(0, 1fr) auto;
    align-items: center;
    gap: 8px;
    margin-top: 22px;
    padding: 7px;
    background: var(--forum-paper-soft) !important;
    border: 2px solid var(--forum-line) !important;
    border-radius: 1px !important;
    box-shadow: 3px 3px 0 rgb(117 69 37 / 16%) !important;
}

.forum-search > i {
    color: var(--forum-orange-dark);
    text-align: center;
}

.forum-search input {
    width: 100%;
    min-height: 40px;
    padding: 8px 10px !important;
    color: var(--forum-ink) !important;
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    font: inherit;
}

.forum-search input:focus {
    outline: 0;
}

.forum-search button {
    min-height: 40px;
    padding: 8px 18px;
    cursor: pointer;
    color: #fff;
    background: var(--forum-orange);
    border: 2px solid var(--forum-orange-dark);
    box-shadow: 2px 2px 0 rgb(117 69 37 / 25%);
    font-family: var(--pixel-font);
    font-size: 1rem;
    font-weight: 800;
}

.forum-layout {
    display: grid;
    width: 100%;
    grid-template-columns: 248px minmax(0, 1fr);
    align-items: start;
    gap: 18px;
    margin: 0;
}

.forum-rail {
    position: sticky;
    top: 88px;
    display: grid;
    gap: 14px;
}

.forum-profile {
    position: relative;
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    padding: 14px !important;
    overflow: hidden;
}

.forum-profile::before {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 5px;
    content: "";
    background: var(--forum-orange);
}

.forum-profile__avatar,
.forum-avatar {
    display: grid;
    flex: 0 0 auto;
    place-items: center;
    overflow: hidden;
    color: var(--forum-ink);
    background: #f2ce82 !important;
    border: 2px solid var(--forum-line) !important;
    border-radius: 2px !important;
    box-shadow: 2px 2px 0 rgb(117 69 37 / 16%);
    font-family: var(--pixel-font);
    font-weight: 900;
}

.forum-profile__avatar {
    width: 48px;
    height: 48px;
    font-size: 1.6rem;
}

.forum-profile__avatar img,
.forum-avatar img {
    display: block;
    width: 100%;
    height: 100%;
    padding: 2px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-profile strong {
    display: block;
    overflow: hidden;
    color: var(--forum-ink) !important;
    font-weight: 800;
    text-overflow: ellipsis;
}

.forum-profile span {
    display: block;
    margin-top: 3px;
    color: var(--forum-muted) !important;
    font-size: 0.72rem;
}

.forum-profile__login {
    grid-column: 1 / -1;
    min-height: 34px;
    padding: 7px 10px;
    color: var(--forum-orange-dark) !important;
    background: #ffedbd;
    border: 1px solid var(--forum-line-soft);
    font-family: var(--pixel-font);
    font-size: 0.95rem;
    font-weight: 800;
    text-align: center;
    text-decoration: none;
}

.forum-tabs {
    display: grid;
    gap: 5px;
    padding: 8px !important;
}

.forum-tabs__head {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    align-items: center;
    gap: 8px;
    margin: -8px -8px 4px;
    padding: 11px 10px;
    background: var(--forum-paper-deep);
    border-bottom: 2px solid var(--forum-line);
}

.forum-tabs__head img {
    width: 44px;
    height: 40px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-tabs__head strong {
    display: block;
    color: var(--forum-ink);
    font-family: var(--pixel-font);
    font-size: 1rem;
    line-height: 1;
}

.forum-tabs__head span {
    display: block;
    margin-top: 4px;
    color: var(--forum-muted);
    font-size: 0.63rem;
}

.forum-tabs button {
    display: grid;
    min-height: 46px;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: 8px;
    padding: 5px 9px !important;
    cursor: pointer;
    color: var(--forum-ink) !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 1px !important;
    box-shadow: none !important;
    font: inherit;
    font-size: 0.78rem;
    font-weight: 800;
    text-align: left;
}

.forum-tabs button img {
    width: 32px;
    height: 32px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-tabs button:hover {
    background: #ffefc4 !important;
    border-color: var(--forum-line-soft) !important;
}

.forum-tabs button.active {
    color: #fff !important;
    background: var(--forum-blue) !important;
    border-color: var(--forum-blue-dark) !important;
    box-shadow: 2px 2px 0 rgb(21 87 124 / 24%) !important;
}

.forum-tabs button.active img {
    filter: drop-shadow(1px 1px 0 rgb(255 255 255 / 45%));
}

.forum-tabs em {
    min-width: 25px;
    padding: 2px 5px;
    color: inherit;
    background: rgb(255 255 255 / 28%);
    border: 1px solid currentcolor;
    font-family: var(--pixel-font);
    font-size: 0.88rem;
    font-style: normal;
    line-height: 1;
    text-align: center;
}

.forum-feed {
    display: grid;
    min-width: 0;
    align-content: start;
    gap: 14px;
}

.forum-feed-head {
    display: flex;
    min-height: 94px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 18px !important;
    background: linear-gradient(90deg, var(--forum-paper), #fff0c5) !important;
}

.forum-feed-head__eyebrow {
    color: var(--forum-orange-dark);
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.forum-feed-head h2 {
    margin: 2px 0 1px;
    color: var(--forum-ink) !important;
    font-family: var(--pixel-font);
    font-size: 2rem;
    line-height: 0.95;
}

.forum-feed-head p {
    margin: 5px 0 0;
    color: var(--forum-muted) !important;
    font-size: 0.72rem;
}

.forum-sort {
    display: inline-flex;
    flex: 0 0 auto;
    gap: 5px;
    padding: 4px;
    background: #f7e2ad;
    border: 1px solid var(--forum-line-soft);
}

.forum-sort button {
    display: inline-flex;
    min-height: 34px;
    align-items: center;
    gap: 6px;
    padding: 6px 9px !important;
    cursor: pointer;
    color: var(--forum-muted) !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 1px !important;
    box-shadow: none !important;
    font-size: 0.7rem;
    font-weight: 800;
}

.forum-sort button:hover,
.forum-sort button.active {
    color: #fff !important;
    background: var(--forum-orange) !important;
    border-color: var(--forum-orange-dark) !important;
}

.forum-composer {
    padding: 0 !important;
    overflow: hidden;
}

.forum-composer__heading {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: var(--forum-paper-deep);
    border-bottom: 2px solid var(--forum-line);
}

.forum-composer__heading img {
    width: 46px;
    height: 38px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-composer__heading strong {
    display: block;
    color: var(--forum-ink);
    font-family: var(--pixel-font);
    font-size: 1.1rem;
    line-height: 1;
}

.forum-composer__heading span {
    display: block;
    margin-top: 4px;
    color: var(--forum-muted);
    font-size: 0.68rem;
}

.forum-composer__top {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    align-items: start;
    gap: 12px;
    padding: 14px 14px 10px;
}

.forum-avatar {
    width: 42px;
    height: 42px;
    font-size: 1.35rem;
}

.forum-avatar--post {
    width: 46px;
    height: 46px;
}

.forum-avatar--comment {
    width: 34px;
    height: 34px;
    font-size: 1rem;
}

.forum-composer__fields {
    display: grid;
    min-width: 0;
    gap: 8px;
}

.forum-segment {
    display: inline-flex;
    width: fit-content;
    gap: 4px;
    padding: 3px;
    background: #f4dda8;
    border: 1px solid var(--forum-line-soft);
}

.forum-segment button {
    min-height: 30px;
    padding: 5px 12px !important;
    cursor: pointer;
    color: var(--forum-muted) !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 1px !important;
    box-shadow: none !important;
    font-family: var(--pixel-font);
    font-size: 0.94rem;
    font-weight: 800;
}

.forum-segment button.active {
    color: #fff !important;
    background: var(--forum-blue) !important;
    border-color: var(--forum-blue-dark) !important;
}

.forum-title-input,
.forum-composer textarea,
.forum-edit-box input,
.forum-edit-box textarea,
.forum-comment-form input,
.forum-comment__bubble textarea {
    width: 100%;
    color: var(--forum-ink) !important;
    background: var(--forum-paper-soft) !important;
    border: 1px solid var(--forum-line-soft) !important;
    border-radius: 1px !important;
    box-shadow: inset 2px 2px 0 rgb(117 69 37 / 5%) !important;
    font: inherit;
}

.forum-title-input,
.forum-edit-box input {
    min-height: 40px;
    padding: 9px 11px !important;
}

.forum-composer textarea,
.forum-edit-box textarea {
    min-height: 104px;
    padding: 10px 11px !important;
    resize: vertical;
    line-height: 1.55;
}

.forum-title-input:focus,
.forum-composer textarea:focus,
.forum-edit-box input:focus,
.forum-edit-box textarea:focus,
.forum-comment-form input:focus,
.forum-comment__bubble textarea:focus {
    border-color: var(--forum-orange) !important;
    outline: 2px solid rgb(236 116 36 / 15%);
}

.forum-title-input::placeholder,
.forum-composer textarea::placeholder,
.forum-edit-box input::placeholder,
.forum-edit-box textarea::placeholder,
.forum-comment-form input::placeholder,
.forum-comment__bubble textarea::placeholder {
    color: #9a8069 !important;
}

.forum-image-preview {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    padding: 0 14px 12px;
}

.forum-image-preview figure {
    aspect-ratio: 1;
    margin: 0;
    overflow: hidden;
    background: #f5dfac;
    border: 1px solid var(--forum-line-soft);
}

.forum-image-preview img,
.forum-post__images img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.forum-composer__actions {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 10px 14px 12px;
    background: #fff0c7;
    border-top: 1px dashed var(--forum-line-soft);
}

.forum-tool,
.forum-submit,
.forum-action,
.forum-load-more,
.forum-edit-box__actions button,
.forum-comment-form button {
    display: inline-flex;
    min-height: 36px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 7px 11px !important;
    cursor: pointer;
    border-radius: 1px !important;
    font: inherit;
    font-size: 0.74rem;
    font-weight: 800;
}

.forum-tool {
    color: var(--forum-ink) !important;
    background: var(--forum-paper) !important;
    border: 1px solid var(--forum-line-soft) !important;
    box-shadow: 2px 2px 0 rgb(117 69 37 / 12%) !important;
}

.forum-tool input {
    display: none;
}

.forum-submit {
    margin-left: auto;
    color: #fff !important;
    background: var(--forum-orange) !important;
    border: 2px solid var(--forum-orange-dark) !important;
    box-shadow: 2px 2px 0 rgb(117 69 37 / 22%) !important;
    font-family: var(--pixel-font);
    font-size: 0.98rem;
}

.forum-submit:disabled,
.forum-comment-form button:disabled {
    cursor: not-allowed;
    opacity: 0.48;
    filter: grayscale(0.35);
}

.forum-login-prompt {
    display: grid;
    min-height: 100px;
    grid-template-columns: 44px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: linear-gradient(90deg, #fff5d7, #f7e0aa) !important;
}

.forum-login-prompt > img {
    width: 42px;
    height: 42px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-login-prompt strong {
    display: block;
    color: var(--forum-ink);
    font-family: var(--pixel-font);
    font-size: 1.18rem;
}

.forum-login-prompt span {
    display: block;
    margin-top: 4px;
    color: var(--forum-muted);
    font-size: 0.72rem;
    line-height: 1.45;
}

.forum-login-prompt a {
    min-height: 38px;
    padding: 8px 13px;
    color: #fff !important;
    background: var(--forum-orange);
    border: 2px solid var(--forum-orange-dark);
    box-shadow: 2px 2px 0 rgb(117 69 37 / 20%);
    font-family: var(--pixel-font);
    font-size: 0.95rem;
    font-weight: 800;
    text-decoration: none;
}

.forum-message {
    padding: 11px 13px;
    color: #744312 !important;
    background: #fff0b8 !important;
    border: 1px dashed #c37d30 !important;
    border-radius: 1px !important;
    font-size: 0.76rem;
    font-weight: 700;
}

.forum-loading {
    display: grid;
    min-height: 180px;
    place-items: center;
    background: rgb(255 247 220 / 72%);
    border: 1px dashed var(--forum-line-soft);
}

.forum-empty {
    display: grid;
    min-height: 220px;
    place-items: center;
    align-content: center;
    gap: 7px;
    margin: 0 !important;
    padding: 28px;
    color: var(--forum-muted) !important;
    background: var(--forum-paper) !important;
    border: 2px dashed var(--forum-line-soft) !important;
    text-align: center;
}

.forum-empty img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-empty strong {
    color: var(--forum-ink);
    font-family: var(--pixel-font);
    font-size: 1.35rem;
}

.forum-empty span {
    font-size: 0.75rem;
}

.forum-post {
    position: relative;
    padding: 0 !important;
    overflow: visible;
    background: var(--forum-paper-soft) !important;
}

.forum-post::before {
    position: absolute;
    z-index: 2;
    top: -2px;
    right: -2px;
    left: -2px;
    height: 5px;
    content: "";
    background: var(--forum-blue);
    border: 1px solid var(--forum-blue-dark);
}

.forum-post--announcement::before {
    background: var(--forum-orange);
    border-color: var(--forum-orange-dark);
}

.forum-post--feedback::before {
    background: #9b6fc5;
    border-color: #67408c;
}

.forum-post.pinned {
    box-shadow: 4px 4px 0 rgb(236 116 36 / 22%) !important;
}

.forum-post--unread {
    background: #fff5cf !important;
}

.forum-post__head {
    display: grid;
    grid-template-columns: 34px 46px minmax(0, 1fr) auto;
    align-items: center;
    gap: 9px;
    padding: 15px 16px 11px;
    background: #ffefc5;
    border-bottom: 1px dashed var(--forum-line-soft);
}

.forum-post__type-icon {
    width: 32px;
    height: 32px;
    object-fit: contain;
    image-rendering: pixelated;
}

.forum-post__meta {
    min-width: 0;
}

.forum-post__meta > strong {
    display: block;
    overflow: hidden;
    color: var(--forum-ink) !important;
    font-size: 0.84rem;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.forum-post__meta > div {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    margin-top: 4px;
    color: var(--forum-muted) !important;
    font-size: 0.66rem;
}

.forum-badge {
    display: inline-flex;
    min-height: 21px;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    color: #fff !important;
    background: var(--forum-blue) !important;
    border: 1px solid var(--forum-blue-dark);
    border-radius: 1px !important;
    font-family: var(--pixel-font);
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1;
}

.forum-post--announcement .forum-badge,
.forum-badge--pin {
    background: var(--forum-orange) !important;
    border-color: var(--forum-orange-dark);
}

.forum-badge--new {
    color: #345d21 !important;
    background: #c8e8a4 !important;
    border-color: #639844;
}

.forum-badge--new i {
    font-size: 0.45rem;
}

.forum-post__owner {
    display: flex;
    gap: 5px;
}

.forum-post__owner button {
    display: grid;
    width: 34px;
    height: 34px;
    cursor: pointer;
    place-items: center;
    color: var(--forum-muted) !important;
    background: var(--forum-paper) !important;
    border: 1px solid var(--forum-line-soft) !important;
    border-radius: 1px !important;
}

.forum-post h2 {
    margin: 16px 18px 7px;
    color: var(--forum-ink) !important;
    font-family: var(--pixel-font);
    font-size: clamp(1.45rem, 3vw, 2rem);
    line-height: 1;
}

.forum-post__content-wrap {
    position: relative;
    margin: 0;
    padding: 5px 18px 4px;
}

.forum-post__content-wrap.collapsed {
    max-height: 158px;
    overflow: hidden;
}

.forum-post__content-wrap.collapsed::after {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 54px;
    content: "";
    background: linear-gradient(transparent, var(--forum-paper-soft));
    pointer-events: none;
}

.forum-post__content {
    margin: 0;
    color: #49342a !important;
    font-family: var(--font-sans);
    font-size: 0.86rem;
    line-height: 1.72;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
}

.forum-post__content--rich {
    white-space: normal;
}

.forum-post__content--rich :deep(p),
.forum-post__content--rich :deep(ul),
.forum-post__content--rich :deep(ol),
.forum-post__content--rich :deep(blockquote),
.forum-post__content--rich :deep(h3) {
    color: inherit !important;
    font-family: var(--font-sans) !important;
}

.forum-post__content--rich :deep(img) {
    max-width: 100%;
    height: auto;
}

.forum-post__content--rich :deep(a) {
    color: var(--forum-blue-dark) !important;
    font-weight: 800;
}

.forum-read-more {
    display: inline-flex;
    min-height: 30px;
    align-items: center;
    gap: 6px;
    margin: 3px 18px 12px;
    padding: 4px 9px;
    cursor: pointer;
    color: var(--forum-orange-dark);
    background: #ffedbd;
    border: 1px solid var(--forum-line-soft);
    font-size: 0.7rem;
    font-weight: 800;
}

.forum-edit-box {
    display: grid;
    gap: 8px;
    margin: 14px 16px;
    padding: 12px;
    background: #f7e3b4;
    border: 1px dashed var(--forum-line-soft);
}

.forum-edit-box__actions {
    display: flex;
    justify-content: flex-end;
    gap: 7px;
}

.forum-edit-box__actions button,
.forum-comment-form button {
    color: #fff !important;
    background: var(--forum-orange) !important;
    border: 1px solid var(--forum-orange-dark) !important;
}

.forum-edit-box__actions .plain,
.forum-comment-form .plain {
    color: var(--forum-muted) !important;
    background: var(--forum-paper) !important;
    border-color: var(--forum-line-soft) !important;
}

.forum-post__images {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 4px;
    margin: 12px 18px 4px;
    overflow: hidden;
    background: #f3db9f;
    border: 1px solid var(--forum-line-soft);
}

.forum-post__images.count-1 {
    grid-template-columns: 1fr;
}

.forum-post__images img {
    min-height: 170px;
    max-height: 310px;
}

.forum-post__summary {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    min-height: 42px;
    padding: 8px 18px;
    color: var(--forum-muted) !important;
    border-bottom: 1px dashed #d7ad6e;
    font-size: 0.68rem;
}

.forum-post__summary button {
    padding: 0;
    cursor: pointer;
    color: inherit;
    background: transparent;
    border: 0;
    font: inherit;
}

.forum-post__summary button:first-child {
    margin-right: auto;
}

.forum-reaction-stack {
    display: inline-flex;
    margin-right: 3px;
}

.forum-reaction-stack span + span {
    margin-left: -4px;
}

.forum-actions {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 5px;
    padding: 7px 12px 10px;
}

.forum-action {
    width: 100%;
    color: var(--forum-muted) !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    box-shadow: none !important;
}

.forum-action:hover,
.forum-action.active {
    color: var(--forum-orange-dark) !important;
    background: #ffebbb !important;
    border-color: var(--forum-line-soft) !important;
}

.forum-reaction-wrap {
    position: relative;
}

.forum-reaction-picker {
    position: absolute;
    z-index: 10;
    bottom: calc(100% + 7px);
    left: 0;
    display: flex;
    gap: 3px;
    padding: 5px;
    background: var(--forum-paper);
    border: 2px solid var(--forum-line);
    box-shadow: 3px 3px 0 var(--forum-shadow);
}

.forum-reaction-picker button {
    display: grid;
    width: 38px;
    height: 38px;
    cursor: pointer;
    place-items: center;
    background: transparent;
    border: 0;
    font-size: 1.25rem;
    transition: transform 100ms steps(2, end);
}

.forum-reaction-picker button:hover {
    transform: translateY(-3px) scale(1.08);
}

.forum-comments {
    padding: 12px 14px 14px;
    background: #f7e4b8;
    border-top: 2px solid var(--forum-line);
}

.forum-comment-form {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) 38px;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
}

.forum-comment-form--reply {
    grid-template-columns: 34px minmax(0, 1fr) auto auto;
    margin: 8px 0 0 44px;
}

.forum-comment-form input {
    min-height: 38px;
    padding: 8px 10px !important;
}

.forum-comment-form button {
    min-width: 38px;
    padding: 6px 9px !important;
}

.forum-comment-thread {
    display: grid;
    gap: 7px;
}

.forum-comment-thread + .forum-comment-thread {
    margin-top: 10px;
}

.forum-comment {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.forum-comment__body {
    min-width: 0;
    flex: 1;
}

.forum-comment__bubble {
    width: fit-content;
    max-width: min(100%, 690px);
    padding: 8px 10px;
    color: var(--forum-ink);
    background: var(--forum-paper-soft);
    border: 1px solid #d5ad72;
    border-radius: 1px;
}

.forum-comment__bubble strong {
    display: block;
    margin-bottom: 3px;
    color: var(--forum-ink);
    font-size: 0.72rem;
}

.forum-comment__bubble p {
    margin: 0;
    color: #513b2e;
    font-size: 0.76rem;
    line-height: 1.55;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
}

.forum-comment__bubble :deep(.forum-mention) {
    color: var(--forum-blue-dark);
    font-weight: 900;
}

.forum-comment__bubble textarea {
    min-width: min(500px, 68vw);
    min-height: 72px;
    padding: 8px !important;
    resize: vertical;
}

.forum-comment__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 9px;
    padding: 4px 2px 0;
    color: var(--forum-muted) !important;
    font-size: 0.65rem;
}

.forum-comment__actions button {
    padding: 0;
    cursor: pointer;
    color: inherit;
    background: transparent;
    border: 0;
    font: inherit;
    font-weight: 800;
}

.forum-comment__actions button:hover,
.forum-comment__actions button.active {
    color: var(--forum-orange-dark);
}

.forum-replies {
    display: grid;
    gap: 7px;
    margin-left: 43px;
    padding-left: 11px;
    border-left: 2px solid #d1a56a;
}

.forum-locked,
.forum-comments__loading {
    padding: 9px 10px;
    color: var(--forum-muted);
    background: #ffefc6;
    border: 1px dashed var(--forum-line-soft);
    font-size: 0.7rem;
}

.forum-load-more {
    width: 100%;
    min-height: 44px;
    color: var(--forum-orange-dark) !important;
    background: var(--forum-paper) !important;
    border: 2px solid var(--forum-line) !important;
    box-shadow: 3px 3px 0 var(--forum-shadow) !important;
    font-family: var(--pixel-font);
    font-size: 1rem;
}

.forum-load-more:hover {
    color: #fff !important;
    background: var(--forum-orange) !important;
    border-color: var(--forum-orange-dark) !important;
}

@media (max-width: 1020px) {
    .forum-layout {
        grid-template-columns: 220px minmax(0, 1fr);
    }

    .forum-hero {
        grid-template-columns: minmax(0, 1fr) 205px;
        padding-inline: 24px;
    }

    .forum-tabs button {
        grid-template-columns: 30px minmax(0, 1fr) auto;
        padding-inline: 6px !important;
    }

    .forum-tabs button img {
        width: 29px;
        height: 29px;
    }
}

@media (max-width: 820px) {
    .forum-page {
        width: min(720px, calc(100% - 20px));
    }

    .forum-hero {
        grid-template-columns: minmax(0, 1fr) 180px;
        margin-bottom: 14px;
        padding: 24px 20px 18px;
    }

    .forum-hero__art {
        padding-inline: 8px;
    }

    .forum-hero__art img {
        width: 72px;
        height: 60px;
    }

    .forum-layout {
        width: 100%;
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .forum-rail,
    .forum-rail--left {
        position: static;
        order: 0;
        gap: 10px;
    }

    .forum-profile {
        grid-template-columns: 44px minmax(0, 1fr) auto;
    }

    .forum-profile__avatar {
        width: 44px;
        height: 44px;
    }

    .forum-profile__login {
        grid-column: auto;
    }

    .forum-tabs {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 5px;
    }

    .forum-tabs__head {
        grid-column: 1 / -1;
    }

    .forum-tabs button {
        min-height: 58px;
        grid-template-columns: 30px minmax(0, 1fr);
        gap: 4px;
        padding: 5px !important;
    }

    .forum-tabs button em {
        display: none;
    }

    .forum-feed-head {
        min-height: 88px;
    }

    .forum-actions {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 620px) {
    .forum-page {
        width: calc(100% - 16px);
        padding-bottom: 46px;
    }

    .forum-breadcrumb {
        margin-bottom: 10px;
    }

    .forum-panel {
        box-shadow: 3px 3px 0 var(--forum-shadow) !important;
    }

    .forum-hero {
        display: block;
        min-height: 0;
        padding: 23px 14px 14px;
    }

    .forum-hero__copy > p {
        font-size: 0.8rem;
        line-height: 1.55;
    }

    .forum-hero h1 {
        font-size: clamp(2.45rem, 14vw, 3.65rem) !important;
    }

    .forum-hero__stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 5px;
        margin-top: 14px;
    }

    .forum-hero__stats div {
        display: flex;
        min-width: 0;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding: 7px 3px;
        text-align: center;
    }

    .forum-hero__stats strong {
        font-size: 1.25rem;
    }

    .forum-hero__stats span {
        font-size: 0.6rem;
    }

    .forum-hero__art {
        display: none;
    }

    .forum-search {
        grid-template-columns: 24px minmax(0, 1fr);
        margin-top: 14px;
        padding: 5px;
    }

    .forum-search input {
        min-width: 0;
        min-height: 38px;
        padding-inline: 5px !important;
        font-size: 0.75rem;
    }

    .forum-search button {
        grid-column: 1 / -1;
        min-height: 36px;
    }

    .forum-profile {
        grid-template-columns: 42px minmax(0, 1fr) auto;
        padding: 10px !important;
    }

    .forum-profile__login {
        min-height: 32px;
        padding: 6px 8px;
    }

    .forum-tabs {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .forum-tabs button {
        grid-template-columns: 31px minmax(0, 1fr) auto;
        min-height: 48px;
    }

    .forum-tabs button em {
        display: block;
    }

    .forum-feed-head {
        display: grid;
        gap: 11px;
        padding: 13px !important;
    }

    .forum-feed-head h2 {
        font-size: 1.7rem;
    }

    .forum-sort {
        display: grid;
        width: 100%;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .forum-sort button {
        justify-content: center;
        padding-inline: 5px !important;
    }

    .forum-composer__top {
        grid-template-columns: 1fr;
        padding-inline: 11px;
    }

    .forum-composer__top > .forum-avatar {
        display: none;
    }

    .forum-composer__heading {
        padding-inline: 11px;
    }

    .forum-composer__heading img {
        width: 40px;
        height: 34px;
    }

    .forum-composer__actions {
        flex-wrap: wrap;
        padding-inline: 11px;
    }

    .forum-submit {
        width: 100%;
        margin-left: 0;
    }

    .forum-login-prompt {
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 9px;
        padding: 12px;
    }

    .forum-login-prompt > img {
        width: 36px;
        height: 36px;
    }

    .forum-login-prompt a {
        grid-column: 1 / -1;
        text-align: center;
    }

    .forum-post__head {
        grid-template-columns: 30px 42px minmax(0, 1fr);
        gap: 7px;
        padding: 13px 11px 9px;
    }

    .forum-post__type-icon {
        width: 28px;
        height: 28px;
    }

    .forum-avatar--post {
        width: 42px;
        height: 42px;
    }

    .forum-post__owner {
        grid-column: 2 / -1;
        justify-content: flex-end;
    }

    .forum-post h2 {
        margin-inline: 13px;
        font-size: 1.45rem;
    }

    .forum-post__content-wrap {
        padding-inline: 13px;
    }

    .forum-post__content {
        font-size: 0.81rem;
    }

    .forum-post__images {
        margin-inline: 13px;
    }

    .forum-post__images img {
        min-height: 120px;
    }

    .forum-post__summary {
        flex-wrap: wrap;
        gap: 7px;
        padding-inline: 13px;
    }

    .forum-actions {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        padding-inline: 9px;
    }

    .forum-reaction-picker {
        position: fixed;
        right: 8px;
        bottom: 12px;
        left: 8px;
        justify-content: space-around;
    }

    .forum-comments {
        padding-inline: 9px;
    }

    .forum-comment-form,
    .forum-comment-form--reply {
        grid-template-columns: 30px minmax(0, 1fr) auto;
        margin-left: 0;
    }

    .forum-comment-form--reply .plain {
        grid-column: 2;
    }

    .forum-replies {
        margin-left: 20px;
        padding-left: 8px;
    }

    .forum-comment__bubble textarea {
        min-width: min(100%, 70vw);
    }
}
</style>
