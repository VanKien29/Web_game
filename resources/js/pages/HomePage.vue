<template>
    <div class="pixel-home">
        <section class="pixel-home-hero">
            <div class="pixel-home-hero__sky">
                <div class="pixel-home-hero__content">
                    <h1><span>Ngọc Rồng</span> <strong>Horizon</strong></h1>
                </div>
            </div>

            <div class="pixel-home-hero__ground">
                <div class="pixel-home-hero__status">
                    <i class="fa-solid fa-server" aria-hidden="true"></i>
                    <div>
                        <small>Máy chủ</small>
                        <strong>Online 24/7 mượt mà</strong>
                    </div>
                </div>

                <div class="pixel-home-hero__status">
                    <i class="fa-solid fa-dragon"></i>
                    <div>
                        <small>Lối chơi</small>
                        <strong>Đa dạng chuẩn dame gốc</strong>
                    </div>
                </div>

                <div class="pixel-home-hero__status">
                    <i class="fa-solid fa-fire"></i>
                    <div>
                        <small>Sự kiện</small>
                        <strong>Cập nhật liên tục</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="pixel-home-section pixel-home-section--quick">
            <div class="pixel-section-heading">
                <div>
                    <span class="pixel-kicker">Bắt đầu hành trình</span>
                    <h2>Trạm dịch chuyển</h2>
                </div>
                <p>Chọn điểm đến của chiến binh!</p>
            </div>

            <div class="pixel-action-grid">
                <a
                    :href="settings.ios_download_url || '#'"
                    class="pixel-action-card"
                >
                    <span class="pixel-action-card__icon">
                        <i class="fa-brands fa-apple" aria-hidden="true"></i>
                    </span>
                    <strong>iOS</strong>
                    <small>Tải cho iPhone</small>
                </a>
                <a
                    :href="settings.android_download_url || '#'"
                    class="pixel-action-card"
                >
                    <span class="pixel-action-card__icon">
                        <i class="fa-brands fa-android" aria-hidden="true"></i>
                    </span>
                    <strong>Android</strong>
                    <small>Tải cho Android</small>
                </a>
                <a
                    :href="settings.apk_download_url || '#'"
                    class="pixel-action-card"
                >
                    <span class="pixel-action-card__icon">
                        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                    </span>
                    <strong>PC</strong>
                    <small>Tải cho PC</small>
                </a>
                <router-link to="/nap-card" class="pixel-action-card">
                    <span class="pixel-action-card__icon">
                        <i class="fa-solid fa-coins" aria-hidden="true"></i>
                    </span>
                    <strong>Nạp thẻ</strong>
                    <small>Nạp nhanh vào game</small>
                </router-link>
                <router-link to="/giftcode" class="pixel-action-card">
                    <span class="pixel-action-card__icon">
                        <i class="fa-solid fa-gift" aria-hidden="true"></i>
                    </span>
                    <strong>Giftcode</strong>
                    <small>Nhận quà sự kiện</small>
                </router-link>
            </div>
        </section>

        <section class="pixel-home-section">
            <div class="pixel-section-heading">
                <div>
                    <span class="pixel-kicker">Thông tin mới</span>
                    <h2>Bảng tin hành tinh</h2>
                </div>
                <router-link to="/forum" class="pixel-text-link">
                    Đến diễn đàn
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </router-link>
            </div>

            <div class="pixel-news-layout">
                <article class="pixel-news-feature">
                    <div class="pixel-news-feature__scene" aria-hidden="true">
                        <span class="pixel-orb pixel-orb--large">
                            <span>H</span>
                        </span>
                    </div>
                    <div>
                        <span class="pixel-kicker">Horizon nhật báo</span>
                        <h3>Đừng bỏ lỡ hoạt động mới trong game</h3>
                        <p>
                            Theo dõi thông báo máy chủ, sự kiện và hướng dẫn
                            dành cho chiến binh mới ngay hôm nay.
                        </p>
                    </div>
                </article>

                <div class="pixel-news-panel">
                    <div class="pixel-segment" role="tablist">
                        <button
                            v-for="tab in newsTabs"
                            :key="tab.key"
                            type="button"
                            :class="{ active: activeTab === tab.key }"
                            role="tab"
                            :aria-selected="activeTab === tab.key"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <div
                        v-if="loading"
                        class="page-loading page-loading--compact"
                    >
                        <div class="page-loading__spinner"></div>
                    </div>

                    <div v-else-if="activePosts.length" class="pixel-news-list">
                        <router-link
                            v-for="post in activePosts.slice(0, 5)"
                            :key="post.id"
                            :to="`/post/${post.slug}`"
                            class="pixel-news-item"
                        >
                            <span class="pixel-news-item__marker">
                                <i
                                    class="fa-solid fa-scroll fa-lg"
                                    aria-hidden="true"
                                ></i>
                            </span>
                            <span>
                                <strong>{{ post.title }}</strong>
                                <small>{{ formatDate(post.created_at) }}</small>
                            </span>
                            <i
                                class="fa-solid fa-chevron-right"
                                aria-hidden="true"
                            ></i>
                        </router-link>
                    </div>

                    <div v-else class="client-empty">
                        Chưa có bài viết trong mục này.
                    </div>
                </div>
            </div>
        </section>

        <section class="pixel-home-section">
            <div class="pixel-section-heading">
                <div>
                    <span class="pixel-kicker">Khám phá Horizon</span>
                    <h2>Ba hành tinh — một cuộc đua</h2>
                </div>
            </div>

            <div class="pixel-feature-grid">
                <article
                    v-for="character in horizonCharacters"
                    :key="character.name"
                    class="pixel-character-card"
                >
                    <div class="pixel-character-card__portrait">
                        <img
                            :src="character.avatar"
                            :alt="`Nhân vật ${character.name}`"
                            width="198"
                            height="227"
                            loading="lazy"
                            decoding="async"
                        />
                    </div>
                    <span class="pixel-character-card__origin">
                        {{ character.origin }}
                    </span>
                    <h3>{{ character.name }}</h3>
                    <p>{{ character.description }}</p>
                </article>
            </div>
        </section>

        <section class="pixel-community-banner">
            <div>
                <span class="pixel-kicker">Tín hiệu từ Trái Đất</span>
                <h2>Sẵn sàng gia nhập cộng đồng?</h2>
                <p>Cùng trao đổi chiến thuật và nhận thông báo sớm nhất.</p>
            </div>
            <div class="pixel-community-banner__actions">
                <a
                    href="https://zalo.me/g/8shvq0alkwjqkuherfvg"
                    class="client-btn client-btn--primary"
                >
                    Nhóm Zalo
                </a>
                <a :href="settings.facebook_url || '#'" class="client-btn">
                    Facebook
                </a>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import axios from "axios";
import { computed, onMounted, ref } from "vue";

interface PostSummary {
    id: number | string;
    slug: string;
    title: string;
    created_at?: string;
}

interface HomeSettings {
    ios_download_url?: string;
    android_download_url?: string;
    apk_download_url?: string;
    facebook_url?: string;
}

type NewsTabKey = "tin-tuc" | "su-kien" | "huong-dan";

interface HomeResponse {
    tin_tuc?: PostSummary[];
    su_kien?: PostSummary[];
    huong_dan?: PostSummary[];
    settings?: HomeSettings;
}

interface HorizonCharacter {
    name: string;
    origin: string;
    description: string;
    avatar: string;
}

const newsTabs: Array<{ key: NewsTabKey; label: string }> = [
    { key: "tin-tuc", label: "Tin tức" },
    { key: "su-kien", label: "Sự kiện" },
    { key: "huong-dan", label: "Hướng dẫn" },
];

const horizonCharacters: HorizonCharacter[] = [
    {
        name: "Vegeta",
        origin: "Chiến binh Saiyan",
        description:
            "Saiyan kiêu hãnh, sức mạnh vượt trội cùng khả năng lì đòn.",
        avatar: "/assets/pixel/characters/vegeta.webp",
    },
    {
        name: "Piccolo",
        origin: "Chiến binh Namek",
        description:
            "Điềm tĩnh, bền bỉ và tinh thông chiến thuật trong mọi trận chiến.",
        avatar: "/assets/pixel/characters/piccolo.webp",
    },
    {
        name: "Songoku",
        origin: "Chiến binh Trái Đất",
        description: "Sức mạnh vô hạn, tinh thần bất khuất và hút gái.",
        avatar: "/assets/pixel/characters/goku.webp",
    },
];

const tinTuc = ref<PostSummary[]>([]);
const suKien = ref<PostSummary[]>([]);
const huongDan = ref<PostSummary[]>([]);
const settings = ref<HomeSettings>({});
const activeTab = ref<NewsTabKey>("tin-tuc");
const loading = ref(true);

const activePosts = computed(() => {
    const posts: Record<NewsTabKey, PostSummary[]> = {
        "tin-tuc": tinTuc.value,
        "su-kien": suKien.value,
        "huong-dan": huongDan.value,
    };
    return posts[activeTab.value];
});

function formatDate(dateString?: string): string {
    if (!dateString) return "";
    return new Intl.DateTimeFormat("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    }).format(new Date(dateString));
}

async function loadData(): Promise<void> {
    try {
        const { data } = await axios.get<HomeResponse>("/api/home");
        tinTuc.value = data.tin_tuc || [];
        suKien.value = data.su_kien || [];
        huongDan.value = data.huong_dan || [];
        settings.value = data.settings || {};
    } catch (error) {
        console.error("Failed to load home data:", error);
    } finally {
        loading.value = false;
    }
}

onMounted(loadData);
</script>
