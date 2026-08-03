import { createRouter, createWebHistory } from "vue-router";

const pageLoaders = {
    login: () => import("./features/auth/LoginPage.vue"),
    dashboard: () => import("./features/dashboard/DashboardPage.vue"),
    accounts: () => import("./features/accounts/AccountsPage.vue"),
    accountForm: () => import("./features/accounts/AccountFormPage.vue"),
    players: () => import("./features/runtime/PlayersPage.vue"),
    giftcodes: () => import("./features/giftcodes/GiftcodesPage.vue"),
    giftcodeForm: () =>
        import("./features/giftcodes/GiftcodeFormPage.vue"),
    posts: () => import("./features/posts/PostsPage.vue"),
    postForm: () => import("./features/posts/PostFormPage.vue"),
    postComments: () => import("./features/posts/PostCommentsPage.vue"),
    forum: () => import("./features/forum/ForumPage.vue"),
    items: () => import("./features/items/ItemsPage.vue"),
    giftBoxes: () => import("./features/items/GiftBoxesPage.vue"),
    hiddenOptionGroups: () =>
        import("./features/items/HiddenOptionGroupsPage.vue"),
    npcs: () => import("./features/runtime/NpcsPage.vue"),
    badges: () => import("./features/game-assets/BadgesPage.vue"),
    costumes: () => import("./features/game-assets/CostumesPage.vue"),
    pets: () => import("./features/game-assets/PetsPage.vue"),
    backAccessories: () =>
        import("./features/game-assets/BackAccessoriesPage.vue"),
    flyingBoards: () =>
        import("./features/game-assets/FlyingBoardsPage.vue"),
    milestones: () =>
        import("./features/milestones/MilestonesPage.vue"),
    milestoneForm: () =>
        import("./features/milestones/MilestoneFormPage.vue"),
    welfareConfigs: () =>
        import("./features/milestones/WelfareConfigsPage.vue"),
    welfareConfigForm: () =>
        import("./features/milestones/WelfareConfigFormPage.vue"),
    shops: () => import("./features/shops/ShopsPage.vue"),
    shopTabForm: () => import("./features/shops/ShopTabFormPage.vue"),
    bosses: () => import("./features/runtime/BossesPage.vue"),
    mapMobs: () => import("./features/runtime/MapMobsPage.vue"),
    runtimeBuffs: () =>
        import("./features/runtime/RuntimeBuffsPage.vue"),
    logs: () => import("./features/logs/AdminLogsPage.vue"),
};

const loadedPages = new Map();

function loadAdminPage(key) {
    if (!loadedPages.has(key)) {
        loadedPages.set(key, pageLoaders[key]());
    }
    return loadedPages.get(key);
}

export function prefetchAdminPages(keys) {
    keys.forEach((key) => {
        if (pageLoaders[key]) {
            loadAdminPage(key).catch(() => loadedPages.delete(key));
        }
    });
}

const routes = [
    {
        path: "/admin/login",
        name: "admin.login",
        component: () => loadAdminPage("login"),
        meta: { guest: true },
    },
    {
        path: "/admin",
        name: "admin.dashboard",
        component: () => loadAdminPage("dashboard"),
        meta: { auth: true },
    },
    {
        path: "/admin/accounts",
        name: "admin.accounts",
        component: () => loadAdminPage("accounts"),
        meta: { auth: true },
    },
    {
        path: "/admin/accounts/create",
        name: "admin.accounts.create",
        component: () => loadAdminPage("accountForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/accounts/:id/edit",
        name: "admin.accounts.edit",
        component: () => loadAdminPage("accountForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/players",
        name: "admin.players",
        component: () => loadAdminPage("players"),
        meta: { auth: true },
    },
    {
        path: "/admin/giftcodes",
        name: "admin.giftcodes",
        component: () => loadAdminPage("giftcodes"),
        meta: { auth: true },
    },
    {
        path: "/admin/giftcodes/create",
        name: "admin.giftcodes.create",
        component: () => loadAdminPage("giftcodeForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/giftcodes/:id/edit",
        name: "admin.giftcodes.edit",
        component: () => loadAdminPage("giftcodeForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/posts",
        name: "admin.posts",
        component: () => loadAdminPage("posts"),
        meta: { auth: true },
    },
    {
        path: "/admin/posts/create",
        name: "admin.posts.create",
        component: () => loadAdminPage("postForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/posts/:id/edit",
        name: "admin.posts.edit",
        component: () => loadAdminPage("postForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/posts/:id/comments",
        name: "admin.posts.comments",
        component: () => loadAdminPage("postComments"),
        meta: { auth: true },
    },
    {
        path: "/admin/forum",
        name: "admin.forum",
        component: () => loadAdminPage("forum"),
        meta: { auth: true },
    },
    {
        path: "/admin/items",
        name: "admin.items",
        component: () => loadAdminPage("items"),
        meta: { auth: true },
    },
    {
        path: "/admin/gift-boxes",
        name: "admin.gift_boxes",
        component: () => loadAdminPage("giftBoxes"),
        meta: { auth: true },
    },
    {
        path: "/admin/hidden-option-groups",
        name: "admin.hidden_option_groups",
        component: () => loadAdminPage("hiddenOptionGroups"),
        meta: { auth: true },
    },
    {
        path: "/admin/npcs",
        name: "admin.npcs",
        component: () => loadAdminPage("npcs"),
        meta: { auth: true },
    },
    {
        path: "/admin/badges",
        name: "admin.badges",
        component: () => loadAdminPage("badges"),
        meta: { auth: true },
    },
    {
        path: "/admin/costumes",
        name: "admin.costumes",
        component: () => loadAdminPage("costumes"),
        meta: { auth: true },
    },
    {
        path: "/admin/pets",
        name: "admin.pets",
        component: () => loadAdminPage("pets"),
        meta: { auth: true },
    },
    {
        path: "/admin/back-accessories",
        name: "admin.back_accessories",
        component: () => loadAdminPage("backAccessories"),
        meta: { auth: true },
    },
    {
        path: "/admin/flying-boards",
        name: "admin.flying_boards",
        component: () => loadAdminPage("flyingBoards"),
        meta: { auth: true },
    },
    {
        path: "/admin/milestones/:type",
        name: "admin.milestones",
        component: () => loadAdminPage("milestones"),
        meta: { auth: true },
    },
    {
        path: "/admin/milestones/:type/create",
        name: "admin.milestones.create",
        component: () => loadAdminPage("milestoneForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/milestones/:type/:id/edit",
        name: "admin.milestones.edit",
        component: () => loadAdminPage("milestoneForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/welfare-configs",
        redirect: {
            name: "admin.welfare-configs",
            params: { type: "attendance_daily" },
        },
    },
    {
        path: "/admin/welfare-configs/:type",
        name: "admin.welfare-configs",
        component: () => loadAdminPage("welfareConfigs"),
        meta: { auth: true },
    },
    {
        path: "/admin/welfare-configs/:type/create",
        name: "admin.welfare-configs.create",
        component: () => loadAdminPage("welfareConfigForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/welfare-configs/:type/:id/edit",
        name: "admin.welfare-configs.edit",
        component: () => loadAdminPage("welfareConfigForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/shops",
        name: "admin.shops",
        component: () => loadAdminPage("shops"),
        meta: { auth: true },
    },
    {
        path: "/admin/shops/tab/:tabId/edit",
        name: "admin.shops.tab.edit",
        component: () => loadAdminPage("shopTabForm"),
        meta: { auth: true },
    },
    {
        path: "/admin/bosses",
        name: "admin.bosses",
        component: () => loadAdminPage("bosses"),
        meta: { auth: true },
    },
    {
        path: "/admin/map-mobs",
        name: "admin.map_mobs",
        component: () => loadAdminPage("mapMobs"),
        meta: { auth: true },
    },
    {
        path: "/admin/runtime-buffs",
        name: "admin.runtime_buffs",
        component: () => loadAdminPage("runtimeBuffs"),
        meta: { auth: true },
    },
    {
        path: "/admin/admin-logs",
        name: "admin.logs",
        component: () => loadAdminPage("logs"),
        meta: { auth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const CHUNK_RELOAD_KEY = "admin_chunk_reload_once";
let authCache = { ok: false, expiresAt: 0 };

async function checkAdminAuth({ force = false } = {}) {
    const now = Date.now();
    if (!force && authCache.expiresAt > now) {
        return authCache.ok;
    }

    const res = await fetch("/admin/api/me", {
        headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    authCache = {
        ok: res.ok,
        expiresAt: now + (res.ok ? 45000 : 0),
    };
    return res.ok;
}

function emitAdminRouteLoading(loading) {
    if (typeof window === "undefined") return;
    window.dispatchEvent(
        new CustomEvent("admin-route-loading", { detail: { loading } }),
    );
}

router.beforeEach(async (to, from, next) => {
    if (to.fullPath !== from.fullPath) {
        emitAdminRouteLoading(true);
    }

    // Check if route needs auth
    if (to.meta.auth) {
        try {
            if (!(await checkAdminAuth())) {
                return next({ name: "admin.login" });
            }
        } catch {
            authCache = { ok: false, expiresAt: 0 };
            return next({ name: "admin.login" });
        }
    }

    // Redirect logged-in users away from login page
    if (to.meta.guest) {
        try {
            if (await checkAdminAuth({ force: true })) {
                return next({ name: "admin.dashboard" });
            }
        } catch {
            // not logged in, continue
        }
    }

    next();
});

router.afterEach(() => {
    emitAdminRouteLoading(false);
});

router.onError((error, to) => {
    emitAdminRouteLoading(false);

    const msg = String(error?.message || "");
    const isChunkError =
        /Failed to fetch dynamically imported module/i.test(msg) ||
        /Importing a module script failed/i.test(msg) ||
        /error loading dynamically imported module/i.test(msg) ||
        /Loading chunk [\d]+ failed/i.test(msg);

    if (!isChunkError) return;

    const now = Date.now();
    const last = Number(sessionStorage.getItem(CHUNK_RELOAD_KEY) || 0);
    if (!last || now - last > 15000) {
        sessionStorage.setItem(CHUNK_RELOAD_KEY, String(now));
        window.location.assign(to?.fullPath || window.location.href);
    }
});

export default router;
