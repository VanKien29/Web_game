<template>
    <div class="admin-app" :class="layoutClass">
        <!-- Login page — no layout -->
        <template v-if="$route.meta.guest">
            <router-view v-slot="{ Component }">
                <transition name="admin-page-switch" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </template>

        <!-- Authenticated layout -->
        <template v-else>
            <!-- Sidebar -->
            <aside id="miniSidebar">
                <div class="brand-logo">
                    <router-link to="/admin" class="brand-link">
                        <div class="brand-icon">
                            <i class="fa-regular fa-house"></i>
                        </div>
                        <span class="site-logo-text">Admin Panel</span>
                    </router-link>
                </div>
                <nav class="navbar-nav">
                    <div class="nav-heading">Tổng quan</div>
                    <hr class="nav-line" />
                    <div class="nav-item">
                        <router-link
                            to="/admin"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.dashboard',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">dashboard</span>
                            <span class="text">Dashboard</span>
                        </router-link>
                    </div>

                    <div class="nav-heading">Quản Lý</div>
                    <hr class="nav-line" />
                    <div class="nav-item">
                        <router-link
                            to="/admin/accounts"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith(
                                    'admin.accounts',
                                ),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">people</span>
                            <span class="text">Tài khoản</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/giftcodes"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith(
                                    'admin.giftcodes',
                                ),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">card_giftcard</span>
                            <span class="text">Giftcode</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/players"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.players' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">person_search</span>
                            <span class="text">Người chơi</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/shops"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith('admin.shops'),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">storefront</span>
                            <span class="text">Shop</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/milestones/moc_nap"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith(
                                    'admin.milestones',
                                ),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">emoji_events</span>
                            <span class="text">Mốc thưởng</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/bosses"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.bosses' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">sports_martial_arts</span>
                            <span class="text">Boss</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/map-mobs"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.map_mobs',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">terrain</span>
                            <span class="text">Map - Mob</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/npcs"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.npcs' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">support_agent</span>
                            <span class="text">NPC</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/runtime-buffs"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.runtime_buffs',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">bolt</span>
                            <span class="text">Buff command</span>
                        </router-link>
                    </div>

                    <div class="nav-heading">Bài Viết</div>
                    <hr class="nav-line" />
                    <div class="nav-item">
                        <router-link
                            to="/admin/forum"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith('admin.forum'),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">forum</span>
                            <span class="text">Diễn đàn</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/posts"
                            class="nav-link"
                            :class="{
                                active: $route.name?.startsWith('admin.posts'),
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">article</span>
                            <span class="text">Quản lý bài viết</span>
                        </router-link>
                    </div>

                    <div class="nav-heading">Thêm Vật Phẩm</div>
                    <hr class="nav-line" />
                    <div class="nav-item">
                        <router-link
                            to="/admin/items"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.items' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">inventory_2</span>
                            <span class="text">Items</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/gift-boxes"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.gift_boxes' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">redeem</span>
                            <span class="text">Hộp quà</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/badges"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.badges' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">workspace_premium</span>
                            <span class="text">Danh hiệu</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/costumes"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.costumes',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">face_retouching_natural</span>
                            <span class="text">Cải trang</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/pets"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.pets' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">pets</span>
                            <span class="text">Pet</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/back-accessories"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.back_accessories',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">backpack</span>
                            <span class="text">Đeo lưng</span>
                        </router-link>
                    </div>
                    <div class="nav-item">
                        <router-link
                            to="/admin/flying-boards"
                            class="nav-link"
                            :class="{
                                active: $route.name === 'admin.flying_boards',
                            }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">flight</span>
                            <span class="text">Ván bay</span>
                        </router-link>
                    </div>

                    <div class="nav-heading">Settings</div>
                    <hr class="nav-line" />
                    <div class="nav-item">
                        <router-link
                            to="/admin/admin-logs"
                            class="nav-link"
                            :class="{ active: $route.name === 'admin.logs' }"
                            @click="closeMobile"
                        >
                            <span class="nav-icon mi">history</span>
                            <span class="text">Nhật ký admin</span>
                        </router-link>
                    </div>
                </nav>
            </aside>

            <!-- Main content -->
            <div id="content">
                <header class="navbar-glass">
                    <div class="topbar-inner">
                        <div class="topbar-left">
                            <button
                                class="topbar-btn"
                                @click="toggleSidebar"
                                title="Toggle sidebar"
                            >
                                <span class="mi collapse-mini">menu</span>
                                <span class="mi collapse-expanded"
                                    >menu_open</span
                                >
                            </button>
                            <button
                                class="topbar-btn"
                                @click="toggleTheme"
                                :title="
                                    theme === 'dark'
                                        ? 'Chế độ sáng'
                                        : 'Chế độ tối'
                                "
                            >
                                <span class="mi">{{
                                    theme === "dark"
                                        ? "light_mode"
                                        : "dark_mode"
                                }}</span>
                            </button>
                        </div>
                        <div class="topbar-right">
                            <div
                                class="topbar-user"
                                @click="showUserMenu = !showUserMenu"
                            >
                                <div class="avatar">
                                    <img
                                        src="/assets/frontend/home/admin_avatar.jpg"
                                        alt="Admin"
                                        style="
                                            width: 100%;
                                            height: 100%;
                                            border-radius: 50%;
                                            object-fit: cover;
                                        "
                                    />
                                </div>
                                <div class="user-info">
                                    <div class="user-name">
                                        {{ adminUser?.username || "Admin" }}
                                    </div>
                                    <div class="user-role">Administrator</div>
                                </div>
                            </div>
                            <div
                                v-if="showUserMenu"
                                class="user-dropdown"
                                @click.stop
                            >
                                <button class="dropdown-item" @click="logout">
                                    <span class="mi" style="font-size: 18px"
                                        >logout</span
                                    >
                                    Đăng xuất
                                </button>
                                <a href="/" class="dropdown-item">
                                    <span class="mi" style="font-size: 18px"
                                        >home</span
                                    >
                                    Về trang chủ
                                </a>
                            </div>
                        </div>
                    </div>
                </header>
                <main class="page-content">
                    <router-view v-slot="{ Component }">
                        <transition name="admin-page-switch" mode="out-in">
                            <component :is="Component" />
                        </transition>
                    </router-view>
                </main>
            </div>
        </template>

        <transition name="admin-loading-fade">
            <div
                v-if="isAdminLoading"
                class="admin-route-loading"
                role="status"
                aria-live="polite"
            >
                <div class="admin-loading-spinner"></div>
            </div>
        </transition>

        <div class="admin-toast-stack" aria-live="polite" aria-atomic="true">
            <transition-group name="admin-toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="admin-toast"
                    :class="'admin-toast--' + toast.type"
                >
                    <span class="mi admin-toast__icon">{{
                        toastIcon(toast.type)
                    }}</span>
                    <div class="admin-toast__body">
                        <strong>{{ toastTitle(toast.type) }}</strong>
                        <span>{{ toast.message }}</span>
                    </div>
                    <button
                        type="button"
                        class="admin-toast__close"
                        title="Đóng"
                        @click="dismissToast(toast.id)"
                    >
                        <span class="mi">close</span>
                    </button>
                </div>
            </transition-group>
        </div>

        <transition name="admin-confirm-fade">
            <div
                v-if="confirmDialog.open"
                class="admin-confirm-overlay"
                @click.self="resolveAdminConfirm(false)"
            >
                <section
                    class="admin-confirm-panel"
                    :class="'admin-confirm-panel--' + confirmDialog.tone"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="admin-confirm-title"
                >
                    <div class="admin-confirm-icon">
                        <span class="mi">{{ confirmIcon(confirmDialog.tone) }}</span>
                    </div>
                    <div class="admin-confirm-content">
                        <h3 id="admin-confirm-title">
                            {{ confirmDialog.title }}
                        </h3>
                        <p>{{ confirmDialog.message }}</p>
                    </div>
                    <div class="admin-confirm-actions">
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="resolveAdminConfirm(false)"
                        >
                            {{ confirmDialog.cancelText }}
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="
                                confirmDialog.tone === 'danger'
                                    ? 'btn-danger'
                                    : 'btn-primary'
                            "
                            @click="resolveAdminConfirm(true)"
                        >
                            {{ confirmDialog.confirmText }}
                        </button>
                    </div>
                </section>
            </div>
        </transition>
    </div>
</template>

<script>
import { prefetchAdminPages } from "./router";

export default {
    data() {
        const themeVersion = "forest-earth-v1";
        const savedThemeVersion = localStorage.getItem("adminThemeVersion");
        let savedTheme = localStorage.getItem("adminTheme");
        if (savedThemeVersion !== themeVersion) {
            savedTheme = "light";
            localStorage.setItem("adminTheme", savedTheme);
            localStorage.setItem("adminThemeVersion", themeVersion);
        }
        return {
            sidebarCollapsed:
                localStorage.getItem("sidebarCollapsed") === "true",
            theme: savedTheme === "dark" ? "dark" : "light",
            adminUser: null,
            showUserMenu: false,
            scrollLockObserver: null,
            bodyScrollLocked: false,
            lockedScrollY: 0,
            bootLoading: true,
            routeLoading: false,
            routeLoadingTimer: null,
            toasts: [],
            toastSeed: 0,
            toastTimers: {},
            alertObserver: null,
            previousAdminNotify: null,
            previousAdminConfirm: null,
            confirmDialog: {
                open: false,
                title: "Xác nhận thao tác",
                message: "",
                tone: "danger",
                confirmText: "Xác nhận",
                cancelText: "Hủy",
                resolve: null,
            },
        };
    },
    computed: {
        isAdminLoading() {
            return this.bootLoading || this.routeLoading;
        },
        layoutClass() {
            return (
                (this.sidebarCollapsed
                    ? "sidebar-collapsed"
                    : "sidebar-expanded") +
                " theme-" +
                this.theme
            );
        },
    },
    watch: {
        "$route.name"() {
            if (this.$route.meta.auth && !this.adminUser) {
                this.fetchUser();
            }
            this.showUserMenu = false;
        },
    },
    created() {
        if (this.$route.meta.auth) {
            this.fetchUser();
        }
        this.applyBodyBg();
    },
    mounted() {
        document.addEventListener("click", this.closeMenus);
        document.addEventListener("wheel", this.preventScrollBleed, {
            capture: true,
            passive: false,
        });
        this.scrollLockObserver = new MutationObserver(this.updateScrollLock);
        this.scrollLockObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ["class", "style"],
        });
        this.updateScrollLock();
        this.installAdminFeedbackBridge();
        this.observeAdminAlerts();

        this._onAdminRouteLoading = (event) => {
            const loading = !!event.detail?.loading;
            window.clearTimeout(this.routeLoadingTimer);

            if (loading) {
                this.routeLoading = true;
                return;
            }

            this.routeLoadingTimer = window.setTimeout(() => {
                this.routeLoading = false;
            }, 160);
        };
        window.addEventListener(
            "admin-route-loading",
            this._onAdminRouteLoading,
        );

        this.$nextTick(() => {
            window.setTimeout(() => {
                this.bootLoading = false;
            }, 280);
        });

        const preload = () =>
            prefetchAdminPages([
                "accounts",
                "giftcodes",
                "posts",
                "forum",
                "postForm",
                "postComments",
                "items",
                "giftBoxes",
                "badges",
                "costumes",
                "pets",
                "backAccessories",
                "shops",
                "milestones",
                "bosses",
                "mapMobs",
                "runtimeBuffs",
                "logs",
            ]);
        if ("requestIdleCallback" in window) {
            window.requestIdleCallback(preload, { timeout: 2500 });
        } else {
            window.setTimeout(preload, 1200);
        }
    },
    unmounted() {
        document.removeEventListener("click", this.closeMenus);
        document.removeEventListener("wheel", this.preventScrollBleed, {
            capture: true,
        });
        if (this.scrollLockObserver) {
            this.scrollLockObserver.disconnect();
            this.scrollLockObserver = null;
        }
        window.removeEventListener(
            "admin-route-loading",
            this._onAdminRouteLoading,
        );
        window.clearTimeout(this.routeLoadingTimer);
        if (this.alertObserver) {
            this.alertObserver.disconnect();
            this.alertObserver = null;
        }
        Object.values(this.toastTimers).forEach((timer) =>
            window.clearTimeout(timer),
        );
        window.removeEventListener("admin-notify", this.onAdminNotify);
        window.adminNotify = this.previousAdminNotify;
        window.adminConfirm = this.previousAdminConfirm;
        this.unlockBodyScroll();
    },
    methods: {
        closeMenus(e) {
            if (!e.target.closest(".topbar-right")) {
                this.showUserMenu = false;
            }
        },
        closeMobile() {
            if (window.innerWidth <= 990) {
                this.sidebarCollapsed = true;
                localStorage.setItem("sidebarCollapsed", "true");
            }
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem(
                "sidebarCollapsed",
                String(this.sidebarCollapsed),
            );
        },
        toggleTheme() {
            this.theme = this.theme === "dark" ? "light" : "dark";
            localStorage.setItem("adminTheme", this.theme);
            this.applyBodyBg();
        },
        applyBodyBg() {
            document.body.style.background =
                this.theme === "dark" ? "#1c2a1f" : "#f8f5f0";
        },
        updateScrollLock() {
            const hasFloatingMenu = !!document.querySelector(
                ".admin-app .modal-overlay, .admin-app .picker-overlay, .admin-app .admin-confirm-overlay",
            );
            if (hasFloatingMenu) {
                this.lockBodyScroll();
            } else {
                this.unlockBodyScroll();
            }
        },
        lockBodyScroll() {
            if (this.bodyScrollLocked) return;
            this.lockedScrollY =
                window.scrollY || document.documentElement.scrollTop || 0;
            document.documentElement.classList.add("admin-scroll-lock");
            document.body.classList.add("admin-scroll-lock");
            document.body.style.top = `-${this.lockedScrollY}px`;
            this.bodyScrollLocked = true;
        },
        unlockBodyScroll() {
            if (!this.bodyScrollLocked) return;
            document.documentElement.classList.remove("admin-scroll-lock");
            document.body.classList.remove("admin-scroll-lock");
            document.body.style.top = "";
            window.scrollTo(0, this.lockedScrollY);
            this.bodyScrollLocked = false;
        },
        preventScrollBleed(event) {
            const boundary = event.target?.closest?.(
                ".modal-panel, .picker-panel, .option-dropdown, .user-dropdown, .boss-side, .group-grid, .catalog-list",
            );
            if (!boundary) return;

            const scrollable = this.closestScrollable(event.target, boundary);
            if (!scrollable) {
                event.preventDefault();
                return;
            }

            const deltaY = event.deltaY;
            const atTop = scrollable.scrollTop <= 0;
            const atBottom =
                Math.ceil(scrollable.scrollTop + scrollable.clientHeight) >=
                scrollable.scrollHeight;
            if ((deltaY < 0 && atTop) || (deltaY > 0 && atBottom)) {
                event.preventDefault();
            }
        },
        closestScrollable(target, boundary) {
            let node = target;
            while (node && node !== document.body) {
                if (node.nodeType === 1) {
                    const style = window.getComputedStyle(node);
                    const canScrollY = /(auto|scroll)/.test(style.overflowY);
                    if (
                        canScrollY &&
                        node.scrollHeight > node.clientHeight + 1
                    ) {
                        return node;
                    }
                }
                if (node === boundary) break;
                node = node.parentElement;
            }
            return boundary.scrollHeight > boundary.clientHeight + 1
                ? boundary
                : null;
        },
        installAdminFeedbackBridge() {
            this.previousAdminNotify = window.adminNotify;
            this.previousAdminConfirm = window.adminConfirm;
            window.adminNotify = (message, type = "success", options = {}) =>
                this.pushToast(message, type, options);
            window.adminConfirm = (input) => this.openAdminConfirm(input);
            window.addEventListener("admin-notify", this.onAdminNotify);
        },
        observeAdminAlerts() {
            this.alertObserver = new MutationObserver(() =>
                this.captureAdminAlerts(),
            );
            this.alertObserver.observe(this.$el, {
                childList: true,
                subtree: true,
                characterData: true,
            });
            this.$nextTick(() => this.captureAdminAlerts());
        },
        onAdminNotify(event) {
            const detail = event.detail || {};
            this.pushToast(
                detail.message || detail.text || "",
                detail.type || "success",
                detail,
            );
        },
        captureAdminAlerts() {
            this.$el
                .querySelectorAll(".alert-success, .alert-error")
                .forEach((node) => {
                    const message = String(node.textContent || "").trim();
                    if (!message) return;
                    const type = node.classList.contains("alert-error")
                        ? "error"
                        : "success";
                    const key = `${type}:${message}`;
                    if (node.getAttribute("data-admin-toast-key") === key) return;
                    node.setAttribute("data-admin-toast-key", key);
                    this.pushToast(message, type);
                });
        },
        pushToast(message, type = "success", options = {}) {
            const text = String(message || "").trim();
            if (!text) return null;
            const normalizedType = ["success", "error", "warning", "info"].includes(
                type,
            )
                ? type
                : "info";
            const id = ++this.toastSeed;
            this.toasts.push({
                id,
                type: normalizedType,
                message: text,
            });
            const timeout = Number(options.timeout ?? 3000);
            if (timeout > 0) {
                this.toastTimers[id] = window.setTimeout(
                    () => this.dismissToast(id),
                    timeout,
                );
            }
            return id;
        },
        dismissToast(id) {
            if (this.toastTimers[id]) {
                window.clearTimeout(this.toastTimers[id]);
                delete this.toastTimers[id];
            }
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
        toastIcon(type) {
            return {
                success: "check_circle",
                error: "error",
                warning: "warning",
                info: "info",
            }[type || "info"];
        },
        toastTitle(type) {
            return {
                success: "Thành công",
                error: "Có lỗi",
                warning: "Cần chú ý",
                info: "Thông báo",
            }[type || "info"];
        },
        openAdminConfirm(input) {
            const options =
                typeof input === "string" ? { message: input } : input || {};
            if (this.confirmDialog.resolve) {
                this.confirmDialog.resolve(false);
            }
            const message = String(options.message || "").trim();
            const destructiveWords = /(xóa|xoá|thu hồi|reset|bỏ thay đổi)/i;
            const tone =
                options.tone || (destructiveWords.test(message) ? "danger" : "primary");
            return new Promise((resolve) => {
                this.confirmDialog = {
                    open: true,
                    title: options.title || "Xác nhận thao tác",
                    message,
                    tone,
                    confirmText:
                        options.confirmText ||
                        (tone === "danger" ? "Xác nhận" : "Đồng ý"),
                    cancelText: options.cancelText || "Hủy",
                    resolve,
                };
            });
        },
        resolveAdminConfirm(value) {
            const resolve = this.confirmDialog.resolve;
            this.confirmDialog = {
                ...this.confirmDialog,
                open: false,
                resolve: null,
            };
            if (resolve) resolve(!!value);
        },
        confirmIcon(tone) {
            return tone === "danger" ? "priority_high" : "help";
        },
        async fetchUser() {
            try {
                const res = await fetch("/admin/api/me", {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.adminUser = data.user;
                }
            } catch {
                // ignore
            }
        },
        async logout() {
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            await fetch("/admin/api/logout", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": token,
                    "Content-Type": "application/json",
                },
            });
            this.adminUser = null;
            this.$router.push({ name: "admin.login" });
        },
    },
};
</script>

<style>
/* ═══════════════════════════════════════
   ADMIN THEME TOKENS
   ═══════════════════════════════════════ */

/* ── CSS Variables ── */
:root {
    --card: #f8f5f0;
    --ring: #2e7d32;
    --input: #e0d6c9;
    --muted: #f0e9e0;
    --accent: #c8e6c9;
    --border: #e0d6c9;
    --radius: 0.5rem;
    --chart-1: #4caf50;
    --chart-2: #388e3c;
    --chart-3: #2e7d32;
    --chart-4: #1b5e20;
    --chart-5: #0a1f0c;
    --popover: #f8f5f0;
    --primary: #2e7d32;
    --sidebar: #f0e9e0;
    --font-mono: "Source Code Pro", ui-monospace, SFMono-Regular, Consolas, monospace;
    --font-sans: Montserrat, "Be Vietnam Pro", ui-sans-serif, system-ui, sans-serif;
    --secondary: #e8f5e9;
    --background: #f8f5f0;
    --font-serif: Merriweather, Georgia, serif;
    --foreground: #3e2723;
    --destructive: #c62828;
    --sidebar-ring: #2e7d32;
    --sidebar-accent: #c8e6c9;
    --sidebar-border: #e0d6c9;
    --card-foreground: #3e2723;
    --sidebar-primary: #2e7d32;
    --muted-foreground: #6d4c41;
    --accent-foreground: #1b5e20;
    --popover-foreground: #3e2723;
    --primary-foreground: #ffffff;
    --sidebar-foreground: #3e2723;
    --secondary-foreground: #1b5e20;
    --destructive-foreground: #ffffff;
    --sidebar-accent-foreground: #1b5e20;
    --sidebar-primary-foreground: #ffffff;

    --color-card: var(--card);
    --color-ring: var(--ring);
    --color-input: var(--input);
    --color-muted: var(--muted);
    --color-accent: var(--accent);
    --color-border: var(--border);
    --color-radius: var(--radius);
    --color-chart-1: var(--chart-1);
    --color-chart-2: var(--chart-2);
    --color-chart-3: var(--chart-3);
    --color-chart-4: var(--chart-4);
    --color-chart-5: var(--chart-5);
    --color-popover: var(--popover);
    --color-primary: var(--primary);
    --color-sidebar: var(--sidebar);
    --color-font-mono: var(--font-mono);
    --color-font-sans: var(--font-sans);
    --color-secondary: var(--secondary);
    --color-background: var(--background);
    --color-font-serif: var(--font-serif);
    --color-foreground: var(--foreground);
    --color-destructive: var(--destructive);
    --color-sidebar-ring: var(--sidebar-ring);
    --color-sidebar-accent: var(--sidebar-accent);
    --color-sidebar-border: var(--sidebar-border);
    --color-card-foreground: var(--card-foreground);
    --color-sidebar-primary: var(--sidebar-primary);
    --color-muted-foreground: var(--muted-foreground);
    --color-accent-foreground: var(--accent-foreground);
    --color-popover-foreground: var(--popover-foreground);
    --color-primary-foreground: var(--primary-foreground);
    --color-sidebar-foreground: var(--sidebar-foreground);
    --color-secondary-foreground: var(--secondary-foreground);
    --color-destructive-foreground: var(--destructive-foreground);
    --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
    --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);

    --ds-primary: var(--primary);
    --ds-primary-rgb: 46, 125, 50;
    --ds-primary-lighter: #4caf50;
    --ds-primary-darker: #1b5e20;
    --ds-primary-soft: var(--accent);
    --ds-danger: var(--destructive);
    --ds-danger-rgb: 198, 40, 40;
    --ds-warning: #9f6b16;
    --ds-warning-rgb: 159, 107, 22;
    --ds-info: #388e3c;
    --ds-info-rgb: 56, 142, 60;
    --ds-success: #2e7d32;
    --ds-success-rgb: 46, 125, 50;

    --ds-gray-100: var(--muted);
    --ds-gray-200: #e9dfd4;
    --ds-gray-300: var(--input);
    --ds-gray-400: #b8a99b;
    --ds-gray-500: #8d7668;
    --ds-gray-600: var(--muted-foreground);
    --ds-gray-700: #5d4037;
    --ds-gray-800: var(--foreground);
    --ds-gray-900: #241612;

    --ds-body-bg: var(--background);
    --ds-surface: var(--card);
    --ds-surface-2: var(--muted);
    --ds-muted: var(--muted);
    --ds-accent: var(--accent);
    --ds-popover: var(--popover);
    --ds-input-bg: rgba(255, 252, 247, 0.72);
    --ds-border: var(--border);
    --ds-text: var(--foreground);
    --ds-text-emphasis: #271511;
    --ds-text-muted: var(--muted-foreground);
    --ds-sidebar-bg: var(--sidebar);
    --ds-sidebar-border: var(--sidebar-border);
    --ds-topbar-bg: rgba(248, 245, 240, 0.9);
    --ds-overlay-bg: rgba(62, 39, 35, 0.28);

    --ds-shadow-xl:
        0 1px 2px rgba(62, 39, 35, 0.08), 0 12px 28px -24px rgba(62, 39, 35, 0.28);
    --ds-shadow-sm: 0 8px 18px -14px rgba(62, 39, 35, 0.28);
    --ds-radius: 4px;
    --ds-radius-lg: 8px;
}

.admin-app.theme-light {
    color-scheme: light;
}

.dark,
.admin-app.theme-dark {
    --card: #2d3a2e;
    --ring: #4caf50;
    --input: #3e4a3d;
    --muted: #252f26;
    --accent: #388e3c;
    --border: #3e4a3d;
    --radius: 0.5rem;
    --chart-1: #81c784;
    --chart-2: #66bb6a;
    --chart-3: #4caf50;
    --chart-4: #43a047;
    --chart-5: #388e3c;
    --popover: #2d3a2e;
    --primary: #4caf50;
    --sidebar: #1c2a1f;
    --secondary: #3e4a3d;
    --background: #1c2a1f;
    --foreground: #f0ebe5;
    --destructive: #c62828;
    --sidebar-ring: #4caf50;
    --sidebar-accent: #388e3c;
    --sidebar-border: #3e4a3d;
    --card-foreground: #f0ebe5;
    --sidebar-primary: #4caf50;
    --muted-foreground: #d7cfc4;
    --accent-foreground: #f0ebe5;
    --popover-foreground: #f0ebe5;
    --primary-foreground: #0a1f0c;
    --sidebar-foreground: #f0ebe5;
    --secondary-foreground: #d7e0d6;
    --destructive-foreground: #f0ebe5;
    --sidebar-accent-foreground: #f0ebe5;
    --sidebar-primary-foreground: #0a1f0c;

    --ds-primary: var(--primary);
    --ds-primary-rgb: 76, 175, 80;
    --ds-primary-lighter: #81c784;
    --ds-primary-darker: #388e3c;
    --ds-primary-soft: var(--accent);
    --ds-danger: var(--destructive);
    --ds-danger-rgb: 198, 40, 40;
    --ds-warning: #d7b46a;
    --ds-warning-rgb: 215, 180, 106;
    --ds-info: #66bb6a;
    --ds-info-rgb: 102, 187, 106;
    --ds-success: #81c784;
    --ds-success-rgb: 129, 199, 132;

    --ds-gray-100: var(--muted);
    --ds-gray-200: #2d382e;
    --ds-gray-300: var(--input);
    --ds-gray-400: #596756;
    --ds-gray-500: #8c9988;
    --ds-gray-600: var(--muted-foreground);
    --ds-gray-700: #e0d8ce;
    --ds-gray-800: var(--foreground);
    --ds-gray-900: #ffffff;

    --ds-body-bg: var(--background);
    --ds-surface: var(--card);
    --ds-surface-2: var(--muted);
    --ds-muted: var(--muted);
    --ds-accent: var(--accent);
    --ds-popover: var(--popover);
    --ds-input-bg: rgba(37, 47, 38, 0.72);
    --ds-border: var(--border);
    --ds-text: var(--foreground);
    --ds-text-emphasis: #fff8ef;
    --ds-text-muted: var(--muted-foreground);
    --ds-sidebar-bg: var(--sidebar);
    --ds-sidebar-border: var(--sidebar-border);
    --ds-topbar-bg: rgba(28, 42, 31, 0.9);
    --ds-overlay-bg: rgba(10, 31, 12, 0.58);

    --ds-shadow-xl:
        0 1px 2px rgba(0, 0, 0, 0.22), 0 16px 34px -26px rgba(0, 0, 0, 0.5);
    --ds-shadow-sm: 0 8px 18px -14px rgba(0, 0, 0, 0.55);
    color-scheme: dark;
}

/* ── Reset & Base ── */
.admin-app *,
.admin-app *::before,
.admin-app *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
.admin-app {
    font-family: var(--font-sans);
    background: var(--ds-body-bg);
    color: var(--ds-text);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}
.admin-app a {
    text-decoration: none;
}
.admin-app a:not(.btn) {
    color: var(--ds-primary);
}
.admin-app a:not(.btn):hover {
    color: var(--ds-primary-lighter);
}

html.admin-scroll-lock,
body.admin-scroll-lock {
    overflow: hidden !important;
}
body.admin-scroll-lock {
    position: fixed;
    left: 0;
    right: 0;
    width: 100%;
}
.admin-app .modal-overlay,
.admin-app .picker-overlay {
    z-index: 3000 !important;
    align-items: flex-start !important;
    justify-content: center !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    padding: 18px !important;
}
.admin-app .modal-panel,
.admin-app .picker-panel {
    max-height: calc(100dvh - 36px) !important;
    overflow: auto !important;
    overscroll-behavior: contain;
    margin: 0 auto;
}
.admin-app .option-dropdown,
.admin-app .item-search-results,
.admin-app .user-dropdown,
.admin-app .boss-side,
.admin-app .group-grid,
.admin-app .catalog-list {
    overscroll-behavior: contain;
}
.admin-app .option-dropdown,
.admin-app .item-search-results {
    position: absolute !important;
    z-index: 7000 !important;
    max-height: min(320px, 52vh) !important;
    overflow-y: auto !important;
    background: var(--ds-popover, var(--ds-surface)) !important;
    border: 1px solid var(--ds-border) !important;
    box-shadow: var(--ds-shadow-xl) !important;
}
.admin-app .option-select-wrap,
.admin-app .item-search-wrap {
    position: relative !important;
    z-index: 100 !important;
}
.admin-app .option-select-wrap:focus-within,
.admin-app .item-search-wrap:focus-within {
    z-index: 7100 !important;
}
.admin-app .card,
.admin-app .item-card,
.admin-app .items-table-wrap,
.admin-app .buff-items-table-wrap {
    overflow: visible !important;
}
.admin-app table,
.admin-app tbody,
.admin-app tr,
.admin-app td {
    overflow: visible !important;
}

/* Material icon shorthand */
.mi {
    font-family: "Material Icons Round";
    font-weight: normal;
    font-style: normal;
    font-size: 20px;
    display: inline-block;
    line-height: 1;
    text-transform: none;
    letter-spacing: normal;
    word-wrap: normal;
    white-space: nowrap;
    direction: ltr;
    -webkit-font-smoothing: antialiased;
}

/* ═══ SIDEBAR ═══ */
#miniSidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    background: var(--ds-sidebar-bg);
    border-right: 1px solid var(--ds-sidebar-border);
    z-index: 1050;
    transition: width 0.3s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.brand-logo {
    position: sticky;
    top: 0;
    background: var(--ds-sidebar-bg);
    padding: 0.75rem 1rem;
    z-index: 2;
}
.brand-link {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none !important;
}
.brand-icon {
    width: 36px;
    height: 36px;
    background: var(--ds-primary);
    border: 1px solid var(--ds-primary);
    border-radius: var(--ds-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-foreground);
    flex-shrink: 0;
}
.site-logo-text {
    font-size: 16px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
    letter-spacing: 0;
    white-space: nowrap;
}

.navbar-nav {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 20px;
}
.nav-heading {
    color: var(--ds-text-muted);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0;
    padding: 16px 20px 4px;
    text-transform: uppercase;
}
.nav-line {
    border: none;
    border-top: 1px solid var(--ds-sidebar-border);
    margin: 4px 12px;
}
.nav-item {
}
.nav-link {
    display: flex;
    align-items: center;
    gap: 0;
    white-space: nowrap;
    overflow: hidden;
    padding: 10px 12px;
    color: var(--ds-text-muted);
    font-weight: 500;
    font-size: 14px;
    margin: 2px 8px;
    border: 1px solid transparent;
    border-radius: var(--ds-radius);
    background: transparent;
    transition: all 0.2s;
    text-decoration: none !important;
    line-height: 1.2;
}
.admin-app #miniSidebar a.nav-link {
    color: var(--sidebar-foreground) !important;
}
.admin-app #miniSidebar a.nav-link .nav-icon {
    color: var(--sidebar-primary);
}
.admin-app #miniSidebar a.nav-link:hover {
    color: var(--sidebar-accent-foreground) !important;
    background: var(--sidebar-accent);
    border-color: var(--sidebar-border);
}
.admin-app #miniSidebar a.nav-link:hover .nav-icon {
    color: var(--sidebar-accent-foreground);
}
.admin-app #miniSidebar a.nav-link:hover .text {
    color: var(--sidebar-accent-foreground);
}
.admin-app #miniSidebar a.nav-link.active {
    color: var(--sidebar-primary-foreground) !important;
    background: var(--sidebar-primary);
    border-color: var(--sidebar-primary);
}
.admin-app #miniSidebar a.nav-link.active .nav-icon {
    color: var(--sidebar-primary-foreground);
}
.admin-app #miniSidebar a.nav-link.active .text {
    color: var(--sidebar-primary-foreground);
}
.nav-icon {
    font-size: 20px;
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}
.nav-link .text {
    margin-left: 10px;
    font-weight: 500;
    vertical-align: middle;
}

/* ── Collapsed sidebar ── */
.sidebar-collapsed #miniSidebar {
    width: 60px;
}
.sidebar-collapsed #miniSidebar .site-logo-text,
.sidebar-collapsed #miniSidebar .nav-heading,
.sidebar-collapsed #miniSidebar .nav-link .text {
    display: none;
}
.sidebar-collapsed #miniSidebar .nav-line {
    display: block;
}

/* ── Expanded sidebar ── */
.sidebar-expanded #miniSidebar {
    width: 250px;
}
.sidebar-expanded #miniSidebar .nav-line {
    display: none;
}

/* ═══ MAIN CONTENT ═══ */
.sidebar-collapsed #content {
    margin-left: 60px;
    transition: margin-left 0.3s;
}
.sidebar-expanded #content {
    margin-left: 250px;
    transition: margin-left 0.3s;
}
#content {
    min-height: 100vh;
}

/* ═══ TOPBAR ═══ */
.navbar-glass {
    position: sticky;
    top: 0;
    z-index: 1030;
    backdrop-filter: blur(10px);
    background-color: var(--ds-topbar-bg);
    border-bottom: 1px solid var(--ds-border);
}
.topbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    padding: 0 24px;
}
.topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.topbar-btn {
    width: 36px;
    height: 36px;
    border-radius: var(--ds-radius);
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    color: var(--ds-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
.topbar-btn:hover {
    background: var(--ds-accent, var(--accent));
    color: var(--accent-foreground);
    border-color: var(--ds-primary);
}
.sidebar-collapsed .collapse-expanded {
    display: none;
}
.sidebar-collapsed .collapse-mini {
    display: block;
}
.sidebar-expanded .collapse-mini {
    display: none;
}
.sidebar-expanded .collapse-expanded {
    display: block;
}

/* ── Avatar / User ── */
.topbar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 4px 8px;
    border: 1px solid transparent;
    border-radius: var(--ds-radius);
    transition: background 0.2s;
}
.topbar-user:hover {
    background: var(--accent);
    border-color: var(--ds-border);
}
.avatar {
    width: 36px;
    height: 36px;
    border-radius: var(--ds-radius);
    background: var(--ds-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: var(--primary-foreground);
    flex-shrink: 0;
}
.user-info {
}
.user-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--ds-text-emphasis);
}
.user-role {
    font-size: 11px;
    color: var(--ds-text-muted);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius-lg);
    box-shadow: var(--ds-shadow-xl);
    min-width: 180px;
    z-index: 50;
    padding: 6px;
}
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px;
    border: none;
    background: none;
    color: var(--ds-text);
    font-size: 14px;
    font-family: inherit;
    border-radius: var(--ds-radius);
    cursor: pointer;
    transition: all 0.15s;
}
.dropdown-item:hover {
    background: rgba(var(--ds-danger-rgb), 0.1);
    color: var(--ds-danger);
}

/* ═══ PAGE CONTENT ═══ */
.page-content {
    padding: 24px 32px;
}

/* ═══ CARDS ═══ */
.card {
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius-lg);
    box-shadow: var(--ds-shadow-xl);
    padding: 24px;
    margin-bottom: 24px;
}
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.card-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--ds-text-emphasis);
}

/* ── Stat cards ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--ds-surface);
    border-radius: var(--ds-radius-lg);
    box-shadow: var(--ds-shadow-xl);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid var(--ds-border);
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--ds-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.stat-icon.primary {
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-primary);
}
.stat-icon.info {
    background: rgba(var(--ds-info-rgb), 0.16);
    color: var(--ds-info);
}
.stat-icon.warning {
    background: rgba(var(--ds-warning-rgb), 0.16);
    color: var(--ds-warning);
}
.stat-icon.danger {
    background: rgba(var(--ds-danger-rgb), 0.16);
    color: var(--ds-danger);
}
.stat-icon.success {
    background: rgba(var(--ds-success-rgb), 0.16);
    color: var(--ds-success);
}
.stat-title {
    font-size: 13px;
    color: var(--ds-text-muted);
    margin-bottom: 4px;
}
.stat-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--ds-text-emphasis);
}

/* ═══ TABLE ═══ */
.table-wrap {
    overflow-x: auto;
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius-lg);
    background: var(--ds-surface);
}
.admin-app table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
}
.admin-app th,
.admin-app td {
    padding: 12px 16px;
    text-align: left;
}
.admin-app thead {
    background: var(--ds-muted, var(--ds-gray-100));
}
.admin-app th {
    color: var(--ds-text-muted);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0;
    border: 0;
}
.admin-app td {
    border-bottom: 1px solid var(--ds-border);
    color: var(--ds-text);
}
.admin-app tr:last-child td {
    border-bottom-width: 0;
}
.admin-app tr:hover td {
    background: rgba(var(--ds-primary-rgb), 0.06);
}

/* ═══ BUTTONS ═══ */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: var(--ds-radius);
    font-size: 14px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition:
        transform 0.16s ease,
        box-shadow 0.16s ease,
        border-color 0.16s ease,
        background-color 0.16s ease,
        color 0.16s ease,
        opacity 0.16s ease;
    text-decoration: none;
    font-family: inherit;
    line-height: 1.2;
    white-space: nowrap;
    position: relative;
    pointer-events: auto;
}
.btn > * {
    pointer-events: none;
}
.btn:hover {
    opacity: 0.94;
    transform: translateY(0);
    box-shadow: var(--ds-shadow-sm);
}
.btn:active {
    transform: translateY(0);
    box-shadow: none;
}
.btn:focus-visible,
.topbar-btn:focus-visible,
.dropdown-item:focus-visible,
.pagination button:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.2);
}
.btn-primary {
    background: var(--ds-primary);
    border-color: var(--ds-primary);
    color: var(--primary-foreground);
}
.btn-success {
    background: var(--ds-success);
    border-color: var(--ds-success);
    color: var(--primary-foreground);
}
.btn-danger {
    background: var(--ds-danger);
    border-color: var(--ds-danger);
    color: var(--destructive-foreground);
}
.btn-warning {
    background: var(--ds-warning);
    border-color: var(--ds-warning);
    color: #212b36;
}
.btn-outline {
    background: var(--ds-surface);
    color: var(--ds-text-emphasis);
    border: 1px solid var(--ds-border);
}
.btn-outline:hover {
    background: var(--secondary);
    border-color: var(--ds-primary);
    color: var(--ds-text-emphasis);
}
.btn:disabled {
    opacity: 0.56;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}
.btn-block {
    width: 100%;
}

.admin-fab {
    position: fixed !important;
    right: 24px;
    bottom: 24px;
    z-index: 2200;
    display: inline-grid !important;
    place-items: center;
    width: 48px;
    height: 48px;
    min-width: 48px;
    font-size: 0 !important;
    padding: 0 !important;
    border-radius: 999px !important;
    line-height: 1 !important;
    box-shadow:
        0 14px 26px -18px rgba(var(--ds-primary-rgb), 0.72),
        0 0 0 3px rgba(var(--ds-primary-rgb), 0.1) !important;
}
.admin-fab::before {
    content: "+";
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    color: currentColor;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 28px;
    font-weight: 400;
    line-height: 1;
    transform: translateY(-1px);
}
.admin-fab:hover {
    transform: none;
}
.admin-fab:active {
    transform: none;
}
.admin-fab .mi {
    opacity: 0;
    width: 0;
    height: 0;
    overflow: hidden;
}
.admin-fab span:not(.mi) {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

.admin-app .action-cell {
    text-align: right;
    white-space: nowrap;
}
.admin-app .row-actions,
.admin-app .table-actions,
.admin-app .comment-actions,
.admin-app .actions-cell {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    white-space: nowrap;
}
.admin-app .action-cell .btn,
.admin-app .actions-cell .btn,
.admin-app .row-actions .btn,
.admin-app .table-actions .btn,
.admin-app .comment-actions .btn {
    width: 34px;
    height: 34px;
    min-width: 34px;
    padding: 0 !important;
    border-radius: var(--ds-radius) !important;
    font-size: 0 !important;
    gap: 0;
}
.admin-app .action-cell .btn + .btn {
    margin-left: 8px;
}
.admin-app .action-cell .btn .mi,
.admin-app .actions-cell .btn .mi,
.admin-app .row-actions .btn .mi,
.admin-app .table-actions .btn .mi,
.admin-app .comment-actions .btn .mi {
    font-size: 18px !important;
}
.admin-app .action-cell .btn span:not(.mi),
.admin-app .actions-cell .btn span:not(.mi),
.admin-app .row-actions .btn span:not(.mi),
.admin-app .table-actions .btn span:not(.mi),
.admin-app .comment-actions .btn span:not(.mi) {
    display: none !important;
}

/* ═══ FORMS ═══ */
.form-group {
    margin-bottom: 18px;
}
.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ds-text);
    margin-bottom: 6px;
}
.form-input {
    width: 100%;
    padding: 10px 14px;
    background: var(--ds-input-bg);
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius);
    color: var(--ds-text-emphasis);
    font-size: 14px;
    font-family: inherit;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}
.admin-app select.form-input {
    appearance: none;
    background-color: var(--ds-input-bg);
    background-image:
        linear-gradient(45deg, transparent 50%, var(--ds-text-muted) 50%),
        linear-gradient(135deg, var(--ds-text-muted) 50%, transparent 50%);
    background-position:
        calc(100% - 18px) 50%,
        calc(100% - 12px) 50%;
    background-size:
        6px 6px,
        6px 6px;
    background-repeat: no-repeat;
    padding-right: 36px;
}
.admin-app select.form-input option,
.admin-app select option {
    background: var(--ds-popover);
    color: var(--ds-text-emphasis);
    font-family: var(--font-sans);
}
.admin-app select.form-input option:checked,
.admin-app select option:checked {
    background: var(--ds-primary);
    color: var(--primary-foreground);
}
.admin-app select.form-input option:hover,
.admin-app select option:hover {
    background: var(--secondary);
    color: var(--secondary-foreground);
}
.form-input:focus {
    outline: none;
    border-color: var(--ring);
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.16);
}
.form-input:disabled,
.admin-app select:disabled,
.admin-app textarea:disabled {
    opacity: 0.58;
    cursor: not-allowed;
}
.form-input::placeholder {
    color: var(--ds-text-muted);
}
.admin-app input[type="checkbox"] {
    appearance: none;
    width: 18px;
    height: 18px;
    min-width: 18px;
    border-radius: var(--ds-radius);
    border: 1px solid var(--ds-border);
    background: var(--ds-input-bg);
    display: inline-grid;
    place-content: center;
    cursor: pointer;
    transition:
        background-color 0.16s ease,
        border-color 0.16s ease,
        box-shadow 0.16s ease,
        transform 0.16s ease;
    vertical-align: -3px;
}
.admin-app input[type="checkbox"]:checked {
    background: var(--ds-primary);
    border-color: var(--ds-primary);
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='white' d='M6.2 11.3 2.7 7.8l1.4-1.4 2.1 2.1 5.7-5.7 1.4 1.4z'/%3E%3C/svg%3E");
    background-position: center;
    background-repeat: no-repeat;
    background-size: 14px 14px;
}
.admin-app input[type="checkbox"]:hover {
    border-color: var(--ds-primary);
}
.admin-app input[type="checkbox"]:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--ds-primary-rgb), 0.18);
}
.admin-app input[type="checkbox"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ═══ BADGES ═══ */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border: 1px solid transparent;
    border-radius: var(--ds-radius);
    font-size: 12px;
    font-weight: 600;
}
.badge-success {
    background: rgba(var(--ds-success-rgb), 0.16);
    color: var(--ds-success);
}
.badge-danger {
    background: rgba(var(--ds-danger-rgb), 0.16);
    color: var(--ds-danger);
}
.badge-warning {
    background: rgba(var(--ds-warning-rgb), 0.16);
    color: var(--ds-warning);
}
.badge-info {
    background: rgba(var(--ds-info-rgb), 0.16);
    color: var(--ds-info);
}
.badge-primary {
    background: rgba(var(--ds-primary-rgb), 0.16);
    color: var(--ds-primary);
}

/* ═══ ALERTS ═══ */
.alert {
    padding: 14px 18px;
    border-radius: var(--ds-radius-lg);
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success {
    background: rgba(var(--ds-success-rgb), 0.12);
    color: var(--ds-success);
    border: 1px solid rgba(var(--ds-success-rgb), 0.2);
}
.admin-app .alert-success {
    display: none;
}
.alert-error {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
    border: 1px solid rgba(var(--ds-danger-rgb), 0.2);
}

/* ═══ PAGINATION ═══ */
.pagination {
    display: flex;
    gap: 4px;
    margin-top: 20px;
    flex-wrap: wrap;
}
.pagination button {
    padding: 7px 14px;
    border-radius: var(--ds-radius);
    font-size: 13px;
    border: 1px solid var(--ds-border);
    background: transparent;
    color: var(--ds-text);
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s;
}
.pagination button:hover {
    background: rgba(var(--ds-primary-rgb), 0.12);
    color: var(--ds-primary);
}
.pagination button.active {
    background: var(--ds-primary);
    color: var(--primary-foreground);
    border-color: var(--ds-primary);
}
.pagination button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ═══ FEEDBACK ═══ */
.admin-toast-stack {
    position: fixed;
    left: 76px;
    bottom: 18px;
    z-index: 5200;
    display: grid;
    gap: 10px;
    width: min(390px, calc(100vw - 108px));
    pointer-events: none;
}
.sidebar-expanded .admin-toast-stack {
    left: 266px;
    width: min(390px, calc(100vw - 298px));
}
.admin-toast {
    display: grid;
    grid-template-columns: 30px 1fr auto;
    align-items: start;
    gap: 10px;
    min-height: 54px;
    padding: 12px 12px;
    border: 1px solid var(--ds-border);
    border-left-width: 4px;
    border-radius: var(--ds-radius-lg);
    background: var(--ds-popover);
    color: var(--ds-text);
    box-shadow: var(--ds-shadow-xl);
    pointer-events: auto;
}
.admin-toast--success {
    border-left-color: var(--ds-success);
}
.admin-toast--error {
    border-left-color: var(--ds-danger);
}
.admin-toast--warning {
    border-left-color: var(--ds-warning);
}
.admin-toast--info {
    border-left-color: var(--ds-primary);
}
.admin-toast__icon {
    display: grid !important;
    place-items: center;
    width: 30px;
    height: 30px;
    border-radius: var(--ds-radius);
    background: rgba(var(--ds-primary-rgb), 0.1);
    color: var(--ds-primary);
}
.admin-toast--success .admin-toast__icon {
    background: rgba(var(--ds-success-rgb), 0.12);
    color: var(--ds-success);
}
.admin-toast--error .admin-toast__icon {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
}
.admin-toast--warning .admin-toast__icon {
    background: rgba(var(--ds-warning-rgb), 0.14);
    color: var(--ds-warning);
}
.admin-toast__body {
    display: grid;
    gap: 2px;
    min-width: 0;
}
.admin-toast__body strong {
    color: var(--ds-text-emphasis);
    font-size: 13px;
    line-height: 1.2;
}
.admin-toast__body span {
    color: var(--ds-text);
    font-size: 13px;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
.admin-toast__close {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    border: 1px solid transparent;
    border-radius: var(--ds-radius);
    background: transparent;
    color: var(--ds-text-muted);
    cursor: pointer;
}
.admin-toast__close:hover {
    background: var(--secondary);
    color: var(--secondary-foreground);
}
.admin-toast__close .mi {
    font-size: 18px;
}
.admin-toast-enter-active,
.admin-toast-leave-active {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}
.admin-toast-enter-from,
.admin-toast-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

.admin-confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 5100;
    display: grid;
    place-items: center;
    padding: 20px;
    background: var(--ds-overlay-bg);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}
.admin-confirm-panel {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: 14px;
    width: min(460px, 100%);
    padding: 18px;
    border: 1px solid var(--ds-border);
    border-left-width: 4px;
    border-radius: var(--ds-radius-lg);
    background: var(--ds-popover);
    box-shadow: var(--ds-shadow-xl);
}
.admin-confirm-panel--danger {
    border-left-color: var(--ds-danger);
}
.admin-confirm-panel--primary {
    border-left-color: var(--ds-primary);
}
.admin-confirm-icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: var(--ds-radius);
    background: rgba(var(--ds-primary-rgb), 0.1);
    color: var(--ds-primary);
}
.admin-confirm-panel--danger .admin-confirm-icon {
    background: rgba(var(--ds-danger-rgb), 0.12);
    color: var(--ds-danger);
}
.admin-confirm-content h3 {
    margin: 0 0 6px;
    color: var(--ds-text-emphasis);
    font-size: 18px;
    line-height: 1.25;
}
.admin-confirm-content p {
    margin: 0;
    color: var(--ds-text);
    font-size: 14px;
    line-height: 1.5;
    white-space: pre-line;
}
.admin-confirm-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 6px;
}
.admin-confirm-fade-enter-active,
.admin-confirm-fade-leave-active {
    transition: opacity 0.16s ease;
}
.admin-confirm-fade-enter-from,
.admin-confirm-fade-leave-to {
    opacity: 0;
}

/* ═══ LOADING ═══ */
.admin-page-switch-enter-active,
.admin-page-switch-leave-active {
    transition:
        opacity 0.16s ease,
        transform 0.16s ease;
}
.admin-page-switch-enter-from {
    opacity: 0;
    transform: translateY(8px);
}
.admin-page-switch-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
.admin-loading-fade-enter-active,
.admin-loading-fade-leave-active {
    transition: opacity 0.18s ease;
}
.admin-loading-fade-enter-from,
.admin-loading-fade-leave-to {
    opacity: 0;
}
.admin-route-loading {
    position: fixed;
    inset: 0;
    z-index: 5000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(28, 42, 31, 0.42);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
}
.admin-route-loading__panel {
    min-width: 190px;
    padding: 18px 22px;
    border: 1px solid var(--ds-border);
    border-radius: var(--ds-radius-lg);
    background: var(--ds-surface);
    box-shadow: var(--ds-shadow-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: var(--ds-text-emphasis);
    font-size: 14px;
    font-weight: 600;
}
.admin-loading-spinner {
    display: inline-block;
    position: relative;
    width: 2.4em;
    height: 2.4em;
    cursor: not-allowed;
    border-radius: 50%;
    border: 3px solid rgba(var(--ds-primary-rgb), 0.18);
    border-top-color: var(--ds-primary);
    animation: admin-loader-spin 0.75s linear infinite;
    flex: 0 0 auto;
}
.admin-loading-spinner::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1.1em;
    height: 1.1em;
    border: 2px solid rgba(var(--ds-primary-rgb), 0.2);
    border-top-color: var(--ds-primary-lighter);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}
.admin-loading-inline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--ds-text-muted);
    font-size: 13px;
}
.admin-loading-inline .admin-loading-spinner {
    font-size: 0.45rem;
}
.muted-line .admin-loading-spinner,
.picker-empty .admin-loading-spinner {
    font-size: 0.5rem;
    margin-right: 8px;
    vertical-align: -4px;
}
.admin-loading-block {
    min-height: 170px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: var(--ds-text-muted);
    text-align: center;
}
.admin-loading-row td {
    padding: 32px 16px !important;
    text-align: center !important;
    color: var(--ds-text-muted) !important;
}
.admin-loading-row__content {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
}
.admin-loading-dot {
    display: inline-block;
    position: relative;
    width: 1em;
    height: 1em;
    border-radius: 50%;
    border: 2px solid rgba(var(--ds-primary-rgb), 0.18);
    border-top-color: var(--ds-primary);
    animation: admin-loader-spin 0.75s linear infinite;
    flex: 0 0 auto;
    vertical-align: -2px;
}
.admin-loading-dot::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0.48em;
    height: 0.48em;
    border: 1px solid rgba(var(--ds-primary-rgb), 0.28);
    border-top-color: var(--ds-primary-lighter);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}
@keyframes admin-loader-spin {
    to {
        transform: rotate(360deg);
    }
}
@media (prefers-reduced-motion: reduce) {
    .admin-page-switch-enter-active,
    .admin-page-switch-leave-active,
    .admin-loading-fade-enter-active,
    .admin-loading-fade-leave-active {
        transition: none;
    }
    .admin-loading-spinner,
    .admin-loading-dot {
        animation-duration: 1.5s;
    }
}

/* ═══ TRANSITION ═══ */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* ═══ GLOBAL ADMIN THEME COMPATIBILITY ═══ */
.admin-app .login-container {
    background: var(--ds-body-bg) !important;
    color: var(--ds-text) !important;
    font-family: var(--font-sans) !important;
}
.admin-app .modal-overlay,
.admin-app .editor-overlay,
.admin-app .picker-overlay {
    background: var(--ds-overlay-bg) !important;
}
.admin-app .modal-panel,
.admin-app .editor-panel,
.admin-app .picker-panel,
.admin-app .form-wrapper,
.admin-app .login-card-surface {
    background: var(--ds-surface) !important;
    border: 1px solid var(--ds-border) !important;
    border-radius: var(--ds-radius-lg) !important;
    box-shadow: var(--ds-shadow-xl) !important;
    color: var(--ds-text) !important;
}
.admin-app .drop-box,
.admin-app .file-box,
.admin-app .icon-drop-box,
.admin-app .asset-card,
.admin-app .command-card,
.admin-app .summary-strip > div,
.admin-app .checkbox-card,
.admin-app .toggle-field,
.admin-app .search-card,
.admin-app .filter-card,
.admin-app .group-card,
.admin-app .option-group-card,
.admin-app .reward-card,
.admin-app .panel-card,
.admin-app .meta-card {
    border-color: var(--ds-border) !important;
    border-radius: var(--ds-radius-lg) !important;
}
.admin-app .form-input,
.admin-app input:not([type="checkbox"]):not([type="radio"]),
.admin-app textarea,
.admin-app select {
    background-color: var(--ds-input-bg) !important;
    border-color: var(--ds-border) !important;
    color: var(--ds-text-emphasis) !important;
}
.admin-app option {
    background: var(--ds-surface) !important;
    color: var(--ds-text-emphasis) !important;
}
.admin-app .item-search-results,
.admin-app .option-dropdown,
.admin-app .user-dropdown,
.admin-app .dropdown-menu,
.admin-app .select-menu {
    background: var(--ds-popover) !important;
    border-color: var(--ds-border) !important;
    color: var(--ds-text) !important;
}
.admin-app .modal-panel,
.admin-app .editor-panel,
.admin-app .picker-panel {
    max-width: min(100%, 1240px);
}

/* ═══ MOBILE ═══ */
@media (max-width: 990px) {
    .admin-toast-stack,
    .sidebar-expanded .admin-toast-stack,
    .sidebar-collapsed .admin-toast-stack {
        left: 14px;
        bottom: 14px;
        width: min(390px, calc(100vw - 28px));
    }
    .admin-fab {
        right: 18px;
        bottom: 18px;
    }
    .sidebar-collapsed #content,
    .sidebar-expanded #content {
        margin-left: 0;
    }
    .sidebar-collapsed #miniSidebar {
        transform: translateX(-100%);
    }
    .sidebar-expanded #miniSidebar {
        width: 250px;
    }
    .page-content {
        padding: 20px 16px;
    }
    .user-info {
        display: none;
    }
}

/* ═══ SCROLLBAR ═══ */
.admin-app ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
    background: transparent;
}
.admin-app ::-webkit-scrollbar-track {
    background: var(--ds-gray-100);
    border-radius: 8px;
}
.admin-app ::-webkit-scrollbar-thumb {
    background: var(--ds-gray-300);
    border-radius: 8px;
}
</style>
