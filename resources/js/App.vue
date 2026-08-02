<template>
    <div class="pixel-app">
        <header
            class="game-header"
            :class="{
                'game-header--scrolled': scrolled,
                'game-header--menu-open': menuOpen,
            }"
        >
            <div class="game-header__inner">
                <router-link
                    to="/"
                    class="pixel-brand game-header__logo"
                    aria-label="Ngọc Rồng Horizon - Trang chủ"
                    @click="menuOpen = false"
                >
                    <span class="pixel-orb" aria-hidden="true">
                        <span>H</span>
                    </span>
                    <span class="pixel-brand__copy">
                        <strong>Ngọc Rồng</strong>
                        <small>Horizon</small>
                    </span>
                </router-link>

                <nav
                    id="main-navigation"
                    class="game-nav"
                    :class="{ 'game-nav--open': menuOpen }"
                    aria-label="Điều hướng chính"
                >
                    <router-link
                        v-for="item in primaryNavigation"
                        :key="item.to"
                        :to="item.to"
                        class="game-nav__link"
                        @click="menuOpen = false"
                    >
                        <i :class="item.icon" aria-hidden="true"></i>
                        <span>{{ item.label }}</span>
                    </router-link>

                    <template v-if="isLoggedIn">
                        <router-link
                            to="/profile"
                            class="game-nav__link game-nav__link--user"
                            @click="menuOpen = false"
                        >
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            <span>{{ username }}</span>
                        </router-link>
                        <button
                            type="button"
                            class="game-nav__link game-nav__link--logout"
                            @click="logout"
                        >
                            <i
                                class="fa-solid fa-right-from-bracket"
                                aria-hidden="true"
                            ></i>
                            <span>Đăng xuất</span>
                        </button>
                    </template>
                    <template v-else>
                        <router-link
                            to="/login"
                            class="game-nav__btn game-nav__btn--login"
                            @click="menuOpen = false"
                        >
                            <i
                                class="fa-solid fa-right-to-bracket"
                                aria-hidden="true"
                            ></i>
                            <span>Đăng nhập</span>
                        </router-link>
                        <router-link
                            to="/register"
                            class="game-nav__btn game-nav__btn--register"
                            @click="menuOpen = false"
                        >
                            <i
                                class="fa-solid fa-user-plus"
                                aria-hidden="true"
                            ></i>
                            <span>Đăng ký</span>
                        </router-link>
                    </template>
                </nav>

                <button
                    class="game-header__toggle"
                    type="button"
                    :aria-expanded="menuOpen"
                    aria-controls="main-navigation"
                    :aria-label="menuOpen ? 'Đóng menu' : 'Mở menu'"
                    @click="menuOpen = !menuOpen"
                >
                    <img
                        src="/assets/pixel/mobile-menu.png"
                        alt=""
                        aria-hidden="true"
                        :class="{ open: menuOpen }"
                    />
                </button>
            </div>

            <transition name="mobile-overlay-fade">
                <button
                    v-if="menuOpen"
                    type="button"
                    class="mobile-overlay"
                    aria-label="Đóng menu"
                    @click="menuOpen = false"
                ></button>
            </transition>
        </header>

        <main
            :class="{
                'inner-page': !isHome,
                'inner-page--forum': isForum,
                'auth-page-host': isAuthPage,
            }"
        >
            <router-view v-slot="{ Component }">
                <transition name="page-switch" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>

        <transition name="site-loading-fade">
            <div
                v-if="isAppLoading"
                class="site-loading-overlay"
                role="status"
                aria-live="polite"
            >
                <div class="site-loading-card">
                    <div class="site-loading-emblem" aria-hidden="true">
                        <span></span>
                        <span></span>
                    </div>
                    <p>Đang dịch chuyển...</p>
                    <div class="site-loading-bar"><span></span></div>
                </div>
            </div>
        </transition>

        <footer class="game-footer">
            <div class="game-footer__inner">
                <div class="game-footer__left">
                    <div class="pixel-brand pixel-brand--footer">
                        <span class="pixel-orb" aria-hidden="true">
                            <span>H</span>
                        </span>
                        <span class="pixel-brand__copy">
                            <strong>Ngọc Rồng</strong>
                            <small>Horizon</small>
                        </span>
                    </div>
                    <p class="game-footer__tagline">
                        Máy chủ nhập vai dành cho cộng đồng chiến binh.
                    </p>
                </div>

                <div class="game-footer__center">
                    <div class="game-footer__nav">
                        <router-link to="/bxh">Đua top</router-link>
                        <router-link to="/giftcode">Giftcode</router-link>
                        <router-link to="/nap-atm">Nạp tiền</router-link>
                        <router-link to="/forum">Diễn đàn</router-link>
                    </div>
                    <div class="game-footer__socials">
                        <a href="https://zalo.me/g/mkbvtsed62u6avwyvgld">
                            Nhóm Zalo
                        </a>
                        <a
                            href="https://zalo.me/g/mkbvtsed62u6avwyvgld"
                        >
                            Facebook
                        </a>
                    </div>
                </div>

                <div class="game-footer__right">
                    <p>Chơi mọi lúc - vui mọi nơi</p>
                    <p>
                        © 2026 Code By
                        <span class="game-footer__developer">Vkien</span>
                    </p>
                </div>
            </div>
            <div class="game-footer__ground" aria-hidden="true"></div>
        </footer>

        <aside
            v-if="!isAuthPage"
            class="pixel-quickbar hidden__mobile"
            :class="{ 'pixel-quickbar--open': sidebarOpen }"
            aria-label="Liên kết nhanh"
        >
            <button
                type="button"
                class="pixel-quickbar__toggle"
                :aria-expanded="sidebarOpen"
                @click="toggleSidebar"
            >
                <i
                    :class="
                        sidebarOpen
                            ? 'fa-solid fa-chevron-right'
                            : 'fa-solid fa-chevron-left'
                    "
                    aria-hidden="true"
                ></i>
            </button>
            <div class="pixel-quickbar__content">
                <img
                    src="/assets/frontend/home/v1/images/sibarRight/qr.png"
                    alt="Mã QR cộng đồng"
                />
                <a href="https://zalo.me/g/mkbvtsed62u6avwyvgld">
                    <i class="fa-solid fa-comments" aria-hidden="true"></i>
                    Zalo
                </a>
                <a href="https://zalo.me/g/mkbvtsed62u6avwyvgld">
                    <i class="fa-brands fa-facebook" aria-hidden="true"></i>
                    Facebook
                </a>
                <router-link to="/nap-atm">
                    <i class="fa-solid fa-coins" aria-hidden="true"></i>
                    Nạp tiền
                </router-link>
                <button type="button" @click="scrollTop">
                    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                    Lên đầu
                </button>
            </div>
        </aside>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

interface NavigationItem {
    to: string;
    label: string;
    icon: string;
}

interface StoredUser {
    id?: number;
    username?: string;
    player_name?: string | null;
}

const GAME_IDLE_TIMEOUT_MS = 15 * 60 * 1000;
const LAST_ACTIVITY_KEY = "game_last_activity_at";
const ACTIVITY_THROTTLE_MS = 1000;
const activityEvents = [
    "pointerdown",
    "keydown",
    "mousemove",
    "scroll",
    "touchstart",
    "wheel",
] as const;

const route = useRoute();
const router = useRouter();

const primaryNavigation: NavigationItem[] = [
    { to: "/", label: "Trang chủ", icon: "fa-solid fa-house" },
    { to: "/bxh", label: "Đua top", icon: "fa-solid fa-ranking-star" },
    { to: "/giftcode", label: "Giftcode", icon: "fa-solid fa-gift" },
    { to: "/forum", label: "Diễn đàn", icon: "fa-solid fa-comments" },
    { to: "/nap-atm", label: "Nạp tiền", icon: "fa-solid fa-coins" },
];

const menuOpen = ref(false);
const sidebarOpen = ref(false);
const scrolled = ref(false);
const loggedIn = ref(!!localStorage.getItem("token"));
const storedUser = ref<StoredUser>(readStoredUser());
const bootLoading = ref(true);
const routeLoading = ref(false);
let routeLoadingTimer: ReturnType<typeof window.setTimeout> | null = null;
let idleTimer: ReturnType<typeof window.setTimeout> | null = null;
let lastActivityAt = 0;
let scrollFrame: number | null = null;

const isAppLoading = computed(() => bootLoading.value || routeLoading.value);
const isLoggedIn = computed(() => loggedIn.value);
const isHome = computed(() => route.path === "/");
const isForum = computed(() => route.path.startsWith("/forum"));
const isAuthPage = computed(
    () => route.path === "/login" || route.path === "/register",
);
const username = computed(() => {
    return (
        storedUser.value.username ||
        storedUser.value.player_name ||
        "Tài khoản"
    );
});

function readStoredUser(): StoredUser {
    try {
        return JSON.parse(localStorage.getItem("user") || "{}") as StoredUser;
    } catch {
        return {};
    }
}

function clearIdleTimer(): void {
    if (idleTimer !== null) {
        window.clearTimeout(idleTimer);
        idleTimer = null;
    }
}

function scheduleIdleLogout(): void {
    clearIdleTimer();

    if (!localStorage.getItem("token")) {
        return;
    }

    const storedActivityAt = Number(
        localStorage.getItem(LAST_ACTIVITY_KEY) || 0,
    );
    const activityAt = storedActivityAt > 0 ? storedActivityAt : Date.now();

    if (!storedActivityAt) {
        localStorage.setItem(LAST_ACTIVITY_KEY, String(activityAt));
    }

    const remaining = GAME_IDLE_TIMEOUT_MS - (Date.now() - activityAt);
    if (remaining <= 0) {
        clearGameSession("idle");
        return;
    }

    idleTimer = window.setTimeout(() => {
        const latestActivityAt = Number(
            localStorage.getItem(LAST_ACTIVITY_KEY) || activityAt,
        );

        if (Date.now() - latestActivityAt >= GAME_IDLE_TIMEOUT_MS) {
            clearGameSession("idle");
            return;
        }

        scheduleIdleLogout();
    }, remaining);
}

function recordActivity(): void {
    if (!localStorage.getItem("token")) {
        return;
    }

    const now = Date.now();
    if (now - lastActivityAt < ACTIVITY_THROTTLE_MS) {
        return;
    }

    lastActivityAt = now;
    localStorage.setItem(LAST_ACTIVITY_KEY, String(now));
    scheduleIdleLogout();
}

function clearGameSession(reason: "manual" | "idle" | "expired"): void {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    localStorage.removeItem(LAST_ACTIVITY_KEY);
    loggedIn.value = false;
    storedUser.value = {};
    clearIdleTimer();
    window.dispatchEvent(
        new CustomEvent("auth-changed", { detail: { reason } }),
    );
}

function handleScroll(): void {
    if (scrollFrame !== null) {
        return;
    }

    scrollFrame = window.requestAnimationFrame(() => {
        const nextScrolled = window.scrollY > 18;
        if (scrolled.value !== nextScrolled) {
            scrolled.value = nextScrolled;
        }
        scrollFrame = null;
    });
}

function handleAuthChanged(event: Event): void {
    const hasToken = !!localStorage.getItem("token");
    const reason = (event as CustomEvent<{ reason?: string }>).detail?.reason;

    loggedIn.value = hasToken;
    storedUser.value = readStoredUser();

    if (!hasToken) {
        clearIdleTimer();
        if (reason !== "manual" && route.meta.requiresAuth) {
            void router.replace({
                path: "/login",
                query: { redirect: route.fullPath },
            });
        }
        return;
    }

    scheduleIdleLogout();
}

function handleStorageChanged(event: StorageEvent): void {
    if (event.key === LAST_ACTIVITY_KEY) {
        scheduleIdleLogout();
        return;
    }

    if (event.key === "token" || event.key === "user") {
        handleAuthChanged(
            new CustomEvent("auth-changed", {
                detail: { reason: "external" },
            }),
        );
    }
}

function handleVisibilityChange(): void {
    if (!document.hidden) {
        scheduleIdleLogout();
    }
}

function handleRouteLoading(event: Event): void {
    const customEvent = event as CustomEvent<{ loading?: boolean }>;
    const loading = !!customEvent.detail?.loading;

    if (routeLoadingTimer) {
        window.clearTimeout(routeLoadingTimer);
    }

    if (loading) {
        routeLoading.value = true;
        return;
    }

    routeLoadingTimer = window.setTimeout(() => {
        routeLoading.value = false;
    }, 140);
}

function logout(): void {
    clearGameSession("manual");
    menuOpen.value = false;
    void router.push("/");
}

function toggleSidebar(): void {
    sidebarOpen.value = !sidebarOpen.value;
}

function scrollTop(): void {
    window.scrollTo({ top: 0, behavior: "smooth" });
}

watch(
    () => route.fullPath,
    () => {
        menuOpen.value = false;
    },
);

onMounted(() => {
    window.addEventListener("scroll", handleScroll, { passive: true });
    window.addEventListener("auth-changed", handleAuthChanged);
    window.addEventListener("storage", handleStorageChanged);
    document.addEventListener("visibilitychange", handleVisibilityChange);
    activityEvents.forEach((eventName) => {
        window.addEventListener(eventName, recordActivity, { passive: true });
    });
    window.addEventListener("route-loading", handleRouteLoading);
    handleScroll();
    scheduleIdleLogout();

    window.requestAnimationFrame(() => {
        bootLoading.value = false;
    });
});

onBeforeUnmount(() => {
    window.removeEventListener("scroll", handleScroll);
    window.removeEventListener("auth-changed", handleAuthChanged);
    window.removeEventListener("storage", handleStorageChanged);
    document.removeEventListener("visibilitychange", handleVisibilityChange);
    activityEvents.forEach((eventName) => {
        window.removeEventListener(eventName, recordActivity);
    });
    window.removeEventListener("route-loading", handleRouteLoading);
    if (routeLoadingTimer) {
        window.clearTimeout(routeLoadingTimer);
    }
    if (scrollFrame !== null) {
        window.cancelAnimationFrame(scrollFrame);
    }
    clearIdleTimer();
});
</script>
