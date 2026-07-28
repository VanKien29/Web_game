<template>
    <div class="client-page client-page--profile profile-page">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Thông tin cá nhân</span>
        </div>

        <div v-if="loading" class="page-loading profile-page__loading">
            <div class="page-loading__spinner"></div>
            <span>Đang gọi chiến binh...</span>
        </div>

        <div v-else-if="profile" class="profile-dashboard">
            <div
                v-if="message"
                class="alert profile-alert"
                :class="
                    messageType === 'success' ? 'alert-success' : 'alert-error'
                "
                role="status"
            >
                {{ message }}
            </div>

            <section class="profile-sanctuary">
                <div class="profile-sanctuary__character">
                    <ProfileCharacterStage
                        v-if="player.has_character"
                        :appearance="player.appearance"
                        :fallback-avatar-url="player.avatar_url"
                    />
                    <div v-else class="profile-no-character">
                        <strong>Chưa có chiến binh</strong>
                        <span>Hãy vào game và tạo nhân vật để bắt đầu.</span>
                    </div>
                </div>

                <article class="profile-dossier">
                    <header class="profile-dossier__head">
                        <div>
                            <span class="profile-kicker">Hồ sơ chiến binh</span>
                            <h1>
                                {{
                                    player.has_character
                                        ? player.name
                                        : profile.user.username
                                }}
                            </h1>
                        </div>
                        <span
                            class="profile-status"
                            :class="{
                                'profile-status--active': profile.user.active,
                            }"
                        >
                            {{
                                profile.user.active
                                    ? "Đã kích hoạt"
                                    : "Chưa kích hoạt"
                            }}
                        </span>
                    </header>

                    <template v-if="player.has_character">
                        <div class="profile-identity">
                            <div>
                                <span>Hành tinh</span>
                                <strong>{{ player.gender_text }}</strong>
                            </div>
                            <div>
                                <span>Ngoại hình</span>
                                <strong>{{ appearanceName }}</strong>
                            </div>
                        </div>

                        <div class="profile-mission">
                            <span>Nhiệm vụ hiện tại</span>
                            <strong>{{ player.task_name }}</strong>
                        </div>

                        <div class="profile-stats" aria-label="Chỉ số nhân vật">
                            <div
                                v-for="stat in statCards"
                                :key="stat.key"
                                class="profile-stat"
                                :class="`profile-stat--${stat.key}`"
                            >
                                <span>{{ stat.label }}</span>
                                <strong>{{ stat.value }}</strong>
                            </div>
                        </div>
                    </template>

                    <div v-else class="profile-dossier__empty">
                        Trang này sẽ tự cập nhật ngoại hình và chỉ số sau khi
                        nhân vật đầu tiên được tạo trong game.
                    </div>
                </article>
            </section>

            <section class="profile-economy client-panel">
                <header class="profile-section-head">
                    <div>
                        <span class="profile-kicker">Kho tài nguyên</span>
                        <h2>Tài sản của {{ profile.user.username }}</h2>
                    </div>
                    <span class="profile-economy__note">
                        Dữ liệu đồng bộ từ máy chủ
                    </span>
                </header>

                <div class="profile-resource-grid">
                    <div
                        v-for="resource in resourceCards"
                        :key="resource.key"
                        class="profile-resource"
                        :class="`profile-resource--${resource.key}`"
                    >
                        <span class="profile-resource__gem" aria-hidden="true">
                            {{ resource.symbol }}
                        </span>
                        <div>
                            <span>{{ resource.label }}</span>
                            <strong>{{ resource.value }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="profile-command client-panel">
                <div class="profile-command__copy">
                    <span class="profile-kicker">Trạm điều khiển</span>
                    <h2>Quản lý tài khoản</h2>
                    <p>Bảo vệ tài khoản và kết nối với cộng đồng Horizon.</p>
                </div>

                <div class="profile-command__actions">
                    <button
                        type="button"
                        class="profile-button profile-button--primary"
                        @click="activeModal = 'password'"
                    >
                        Đổi mật khẩu
                    </button>
                    <button
                        v-if="!profile.user.active"
                        type="button"
                        class="profile-button"
                        @click="activeModal = 'activate'"
                    >
                        Kích hoạt tài khoản
                    </button>
                    <a
                        href="https://zalo.me/g/8shvq0alkwjqkuherfvg"
                        class="profile-button"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Nhóm Zalo
                    </a>
                    <button
                        type="button"
                        class="profile-button profile-button--danger"
                        @click="logout"
                    >
                        Đăng xuất
                    </button>
                </div>
            </section>
        </div>

        <div v-else class="client-panel client-empty profile-page__error">
            <strong>Không thể tải hồ sơ</strong>
            <span>{{ loadError || "Vui lòng thử lại sau." }}</span>
            <button type="button" class="profile-button" @click="loadProfile">
                Tải lại
            </button>
        </div>

        <Teleport to="body">
            <Transition name="profile-modal">
                <div
                    v-if="activeModal === 'password'"
                    class="profile-modal-backdrop"
                    role="presentation"
                    @click.self="closeModal"
                >
                    <section
                        class="profile-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="change-password-title"
                    >
                        <header>
                            <span class="profile-kicker">Bảo mật</span>
                            <h2 id="change-password-title">Đổi mật khẩu</h2>
                            <button
                                type="button"
                                aria-label="Đóng"
                                @click="closeModal"
                            >
                                ×
                            </button>
                        </header>
                        <form @submit.prevent="changePassword">
                            <label>
                                <span>Mật khẩu mới</span>
                                <input
                                    v-model="passwordForm.newPassword"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                />
                            </label>
                            <label>
                                <span>Nhập lại mật khẩu</span>
                                <input
                                    v-model="passwordForm.confirmPassword"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                />
                            </label>
                            <div class="profile-modal__actions">
                                <button
                                    type="button"
                                    class="profile-button"
                                    @click="closeModal"
                                >
                                    Hủy
                                </button>
                                <button
                                    type="submit"
                                    class="profile-button profile-button--primary"
                                    :disabled="submitting"
                                >
                                    {{
                                        submitting
                                            ? "Đang cập nhật..."
                                            : "Cập nhật"
                                    }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </Transition>

            <Transition name="profile-modal">
                <div
                    v-if="activeModal === 'activate'"
                    class="profile-modal-backdrop"
                    role="presentation"
                    @click.self="closeModal"
                >
                    <section
                        class="profile-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="activate-account-title"
                    >
                        <header>
                            <span class="profile-kicker">Xác nhận</span>
                            <h2 id="activate-account-title">
                                Kích hoạt tài khoản
                            </h2>
                            <button
                                type="button"
                                aria-label="Đóng"
                                @click="closeModal"
                            >
                                ×
                            </button>
                        </header>
                        <p class="profile-modal__copy">
                            Kích hoạt sẽ sử dụng 10.000 VNĐ trong số dư tài
                            khoản game.
                        </p>
                        <div class="profile-modal__actions">
                            <button
                                type="button"
                                class="profile-button"
                                @click="closeModal"
                            >
                                Hủy
                            </button>
                            <button
                                type="button"
                                class="profile-button profile-button--primary"
                                :disabled="submitting"
                                @click="activateAccount"
                            >
                                {{
                                    submitting
                                        ? "Đang kích hoạt..."
                                        : "Xác nhận"
                                }}
                            </button>
                        </div>
                    </section>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import axios from "axios";
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    shallowRef,
    watch,
} from "vue";
import { useRouter } from "vue-router";
import ProfileCharacterStage from "../components/profile/ProfileCharacterStage.vue";
import type {
    PlayerInventory,
    PlayerStats,
    ProfileData,
    ProfilePlayer,
} from "../types/profile";

type ModalName = "password" | "activate" | null;
type MessageType = "success" | "error" | "";

const router = useRouter();
const profile = shallowRef<ProfileData | null>(null);
const loading = shallowRef(true);
const submitting = shallowRef(false);
const loadError = shallowRef("");
const message = shallowRef("");
const messageType = shallowRef<MessageType>("");
const activeModal = shallowRef<ModalName>(null);
const passwordForm = reactive({
    newPassword: "",
    confirmPassword: "",
});
const profileRequest = new AbortController();

const emptyStats: PlayerStats = {
    potential: 0,
    hp: 0,
    ki: 0,
    damage: 0,
    defense: 0,
    critical: 0,
};
const emptyInventory: PlayerInventory = {
    gold: 0,
    gem: 0,
    ruby: 0,
    thoi_vang: 0,
};

const player = computed<ProfilePlayer>(
    () => profile.value?.player ?? { has_character: false },
);
const stats = computed(() => player.value.stats ?? emptyStats);
const inventory = computed(() => player.value.inventory ?? emptyInventory);
const appearanceName = computed(
    () => player.value.appearance?.costume_name || "Trang phục đang mặc",
);
const statCards = computed(() => [
    {
        key: "power",
        label: "Sức mạnh",
        value: formatNumber(player.value.power),
    },
    {
        key: "potential",
        label: "Tiềm năng",
        value: formatNumber(stats.value.potential),
    },
    { key: "hp", label: "HP", value: formatNumber(stats.value.hp) },
    { key: "ki", label: "KI", value: formatNumber(stats.value.ki) },
    {
        key: "damage",
        label: "Sức đánh",
        value: formatNumber(stats.value.damage),
    },
    {
        key: "defense",
        label: "Giáp / Chí mạng",
        value: `${formatNumber(stats.value.defense)} / ${formatNumber(
            stats.value.critical,
        )}%`,
    },
]);
const resourceCards = computed(() => [
    {
        key: "cash",
        label: "Số dư",
        value: `${formatNumber(profile.value?.user.cash)} VNĐ`,
        symbol: "₫",
    },
    {
        key: "topup",
        label: "Đã nạp",
        value: `${formatNumber(profile.value?.user.danap)} VNĐ`,
        symbol: "↑",
    },
    {
        key: "gold",
        label: "Vàng",
        value: formatNumber(inventory.value.gold),
        symbol: "●",
    },
    {
        key: "gem",
        label: "Ngọc xanh",
        value: formatNumber(inventory.value.gem),
        symbol: "◆",
    },
    {
        key: "ruby",
        label: "Hồng ngọc",
        value: formatNumber(inventory.value.ruby),
        symbol: "◆",
    },
    {
        key: "bar",
        label: "Thỏi vàng",
        value: formatNumber(inventory.value.thoi_vang),
        symbol: "▰",
    },
]);

watch(activeModal, (modal) => {
    document.body.style.overflow = modal ? "hidden" : "";
});

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
    void loadProfile();
});

onBeforeUnmount(() => {
    profileRequest.abort();
    window.removeEventListener("keydown", handleKeydown);
    document.body.style.overflow = "";
});

function formatNumber(value?: number): string {
    const number = Number(value ?? 0);

    return Number.isFinite(number) ? number.toLocaleString("vi-VN") : "0";
}

function authHeaders() {
    return {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
        },
    };
}

async function loadProfile(): Promise<void> {
    const token = localStorage.getItem("token");
    if (!token) {
        await router.push("/login");
        return;
    }

    loading.value = true;
    loadError.value = "";
    try {
        const { data } = await axios.get<{ ok: boolean; data: ProfileData }>(
            "/api/profile",
            {
                ...authHeaders(),
                signal: profileRequest.signal,
            },
        );
        if (data.ok) {
            profile.value = data.data;
        } else {
            loadError.value = "Máy chủ chưa trả về dữ liệu hồ sơ.";
        }
    } catch (error: unknown) {
        if (axios.isCancel(error)) return;
        if (axios.isAxiosError(error) && error.response?.status === 401) {
            clearSession();
            await router.push("/login");
            return;
        }
        loadError.value = "Không thể kết nối đến máy chủ hồ sơ.";
    } finally {
        loading.value = false;
    }
}

async function changePassword(): Promise<void> {
    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
        setMessage("Mật khẩu nhập lại không khớp.", "error");
        return;
    }

    submitting.value = true;
    try {
        const { data } = await axios.post<{ ok: boolean; message?: string }>(
            "/api/auth/change-password",
            { new_password: passwordForm.newPassword },
            authHeaders(),
        );
        setMessage(
            data.message || "Đổi mật khẩu thành công.",
            data.ok ? "success" : "error",
        );
        if (data.ok) {
            passwordForm.newPassword = "";
            passwordForm.confirmPassword = "";
            closeModal();
        }
    } catch (error: unknown) {
        setMessage(responseMessage(error, "Không thể đổi mật khẩu."), "error");
    } finally {
        submitting.value = false;
    }
}

async function activateAccount(): Promise<void> {
    submitting.value = true;
    try {
        const { data } = await axios.post<{ ok: boolean; message?: string }>(
            "/api/auth/activate",
            {},
            authHeaders(),
        );
        setMessage(
            data.message || "Kích hoạt tài khoản thành công.",
            data.ok ? "success" : "error",
        );
        if (data.ok && profile.value) {
            profile.value = {
                ...profile.value,
                user: { ...profile.value.user, active: 1 },
            };
            closeModal();
        }
    } catch (error: unknown) {
        setMessage(
            responseMessage(error, "Không thể kích hoạt tài khoản."),
            "error",
        );
    } finally {
        submitting.value = false;
    }
}

function responseMessage(error: unknown, fallback: string): string {
    if (axios.isAxiosError<{ message?: string }>(error)) {
        return error.response?.data?.message || fallback;
    }

    return fallback;
}

function setMessage(text: string, type: MessageType): void {
    message.value = text;
    messageType.value = type;
    window.scrollTo({ top: 0, behavior: "smooth" });
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === "Escape" && activeModal.value) {
        closeModal();
    }
}

function closeModal(): void {
    activeModal.value = null;
}

function clearSession(): void {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
}

async function logout(): Promise<void> {
    clearSession();
    await router.push("/");
    window.location.reload();
}
</script>

<style scoped>
.profile-page {
    width: min(1260px, calc(100% - 32px));
    padding-top: 0;
    padding-bottom: 64px;
}

.profile-page__loading,
.profile-page__error {
    min-height: 360px;
}

.profile-page__loading {
    display: grid;
    place-items: center;
    align-content: center;
    gap: 14px;
    color: var(--pixel-muted);
    font-family: var(--pixel-font);
    font-size: 1.3rem;
}

.profile-dashboard {
    display: grid;
    gap: 18px;
}

.profile-alert {
    margin: 0;
}

.profile-sanctuary {
    position: relative;
    display: grid;
    min-height: 660px;
    grid-template-columns: minmax(0, 58%) minmax(360px, 42%);
    align-items: center;
    overflow: hidden;
    background:
        linear-gradient(
            90deg,
            rgb(19 55 45 / 2%) 0%,
            rgb(19 55 45 / 3%) 49%,
            rgb(19 55 45 / 18%) 100%
        ),
        url("/assets/pixel/profile-sanctuary.webp") center / cover no-repeat;
    border: 3px solid var(--pixel-line);
    box-shadow: 6px 6px 0 rgb(61 42 34 / 24%);
}

.profile-sanctuary::after {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 7px;
    content: "";
    background: repeating-linear-gradient(
        90deg,
        #83502c 0 13px,
        #a96b37 13px 26px
    );
}

.profile-sanctuary__character {
    position: relative;
    z-index: 1;
    display: grid;
    min-width: 0;
    min-height: 500px;
    place-items: center;
    transform: translateY(42px);
}

.profile-dossier {
    position: relative;
    z-index: 2;
    display: grid;
    gap: 14px;
    width: calc(100% - 36px);
    max-width: 500px;
    justify-self: end;
    padding: 22px;
    color: var(--pixel-ink);
    background: rgb(255 247 216 / 94%);
    border: 3px solid var(--pixel-line);
    box-shadow: 6px 6px 0 rgb(61 42 34 / 25%);
}

.profile-dossier::before,
.profile-economy::before,
.profile-command::before {
    position: absolute;
    z-index: 0;
    inset: 5px;
    content: "";
    border: 1px dashed rgb(126 76 42 / 38%);
    pointer-events: none;
}

.profile-dossier > *,
.profile-economy > *,
.profile-command > * {
    position: relative;
    z-index: 1;
}

.profile-dossier__head,
.profile-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.profile-kicker {
    display: block;
    color: var(--pixel-orange-dark);
    font-family: var(--pixel-font);
    font-size: 0.95rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.profile-dossier h1,
.profile-section-head h2,
.profile-command h2,
.profile-modal h2 {
    margin: 2px 0 0;
    color: var(--pixel-ink);
    font-family: var(--pixel-font);
    line-height: 0.95;
}

.profile-dossier h1 {
    font-size: clamp(2.4rem, 4vw, 4rem);
}

.profile-status {
    flex: 0 0 auto;
    padding: 6px 8px;
    color: #8a351e;
    background: #ffd6b7;
    border: 2px solid #9e492e;
    font-size: 0.68rem;
    font-weight: 800;
}

.profile-status--active {
    color: #205b2b;
    background: #d9f3bd;
    border-color: #4d8a3d;
}

.profile-identity {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.profile-identity > div,
.profile-mission,
.profile-stat {
    display: grid;
    gap: 3px;
    padding: 9px 10px;
    background: rgb(248 228 173 / 88%);
    border: 2px solid rgb(126 76 42 / 56%);
}

.profile-identity span,
.profile-mission span,
.profile-stat span,
.profile-resource div > span {
    color: var(--pixel-muted);
    font-size: 0.68rem;
}

.profile-identity strong,
.profile-mission strong {
    overflow: hidden;
    font-size: 0.82rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-mission {
    background: rgb(255 241 197 / 94%);
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.profile-stat {
    min-width: 0;
    border-left-width: 5px;
}

.profile-stat--power,
.profile-stat--damage {
    border-left-color: #dd6121;
}

.profile-stat--potential,
.profile-stat--ki {
    border-left-color: #2998ca;
}

.profile-stat--hp {
    border-left-color: #69a93d;
}

.profile-stat--defense {
    border-left-color: #8e6fb0;
}

.profile-stat strong {
    overflow: hidden;
    font-family: var(--pixel-font);
    font-size: 1.25rem;
    line-height: 1;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-dossier__empty {
    padding: 18px;
    color: var(--pixel-muted);
    background: var(--pixel-paper);
    border: 2px dashed var(--pixel-line);
    line-height: 1.6;
}

.profile-no-character {
    display: grid;
    max-width: 280px;
    gap: 6px;
    padding: 16px 20px;
    color: var(--pixel-ink);
    background: rgb(255 247 216 / 92%);
    border: 3px solid var(--pixel-line);
    box-shadow: 4px 4px 0 rgb(61 42 34 / 22%);
    text-align: center;
}

.profile-no-character strong {
    font-family: var(--pixel-font);
    font-size: 1.65rem;
}

.profile-no-character span {
    color: var(--pixel-muted);
    font-size: 0.78rem;
}

.profile-economy,
.profile-command {
    position: relative;
    padding: 22px;
}

.profile-section-head h2,
.profile-command h2 {
    font-size: 2rem;
}

.profile-economy__note {
    color: var(--pixel-muted);
    font-size: 0.7rem;
}

.profile-resource-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 16px;
}

.profile-resource {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: var(--pixel-paper);
    border: 2px solid var(--pixel-line-soft);
}

.profile-resource__gem {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    place-items: center;
    color: #fff5c8;
    background: #c77823;
    border: 2px solid var(--pixel-line);
    box-shadow: 2px 2px 0 rgb(61 42 34 / 20%);
    font-family: var(--pixel-font);
    font-size: 1.2rem;
}

.profile-resource--gem .profile-resource__gem {
    background: #2c9dc1;
}

.profile-resource--ruby .profile-resource__gem {
    background: #bb3c4c;
}

.profile-resource--topup .profile-resource__gem {
    background: #6b9a3a;
}

.profile-resource--bar .profile-resource__gem {
    color: #5b351d;
    background: #e6b64b;
}

.profile-resource > div {
    display: grid;
    min-width: 0;
    gap: 2px;
}

.profile-resource strong {
    overflow: hidden;
    color: var(--pixel-ink);
    font-family: var(--pixel-font);
    font-size: 1.25rem;
    line-height: 1;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-command {
    display: grid;
    grid-template-columns: minmax(220px, 0.55fr) minmax(0, 1.45fr);
    align-items: center;
    gap: 22px;
}

.profile-command p {
    margin: 5px 0 0;
    color: var(--pixel-muted);
    font-size: 0.78rem;
}

.profile-command__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.profile-button {
    display: inline-flex;
    min-height: 42px;
    align-items: center;
    justify-content: center;
    padding: 8px 15px;
    color: var(--pixel-ink);
    background: var(--pixel-cream);
    border: 2px solid var(--pixel-line);
    box-shadow: 2px 2px 0 rgb(61 42 34 / 22%);
    font-family: var(--font-sans);
    font-size: 0.74rem;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition:
        transform 120ms ease,
        box-shadow 120ms ease,
        background-color 120ms ease;
}

.profile-button:hover {
    transform: translate(-1px, -1px);
    box-shadow: 3px 3px 0 rgb(61 42 34 / 22%);
}

.profile-button:disabled {
    opacity: 0.58;
    cursor: wait;
}

.profile-button--primary {
    color: var(--pixel-white);
    background: var(--pixel-orange);
}

.profile-button--danger {
    color: #812c23;
    background: #ffe0c9;
}

.profile-page__error {
    display: grid;
    place-items: center;
    align-content: center;
    gap: 8px;
    text-align: center;
}

.profile-page__error strong {
    font-family: var(--pixel-font);
    font-size: 2rem;
}

.profile-page__error span {
    color: var(--pixel-muted);
}

.profile-modal-backdrop {
    position: fixed;
    z-index: 5200;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 16px;
    background: rgb(40 28 21 / 58%);
}

.profile-modal {
    width: min(460px, 100%);
    padding: 22px;
    color: var(--pixel-ink);
    background: var(--pixel-cream);
    border: 3px solid var(--pixel-line);
    box-shadow: 7px 7px 0 rgb(40 28 21 / 32%);
}

.profile-modal header {
    position: relative;
    padding-right: 42px;
}

.profile-modal header > button {
    position: absolute;
    top: -4px;
    right: 0;
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    color: var(--pixel-ink);
    background: var(--pixel-paper);
    border: 2px solid var(--pixel-line);
    font-size: 1.3rem;
    cursor: pointer;
}

.profile-modal form {
    display: grid;
    gap: 12px;
    margin-top: 18px;
}

.profile-modal label {
    display: grid;
    gap: 5px;
    color: var(--pixel-muted);
    font-size: 0.72rem;
    font-weight: 700;
}

.profile-modal input {
    width: 100%;
    min-height: 44px;
    padding: 9px 11px;
    color: var(--pixel-ink);
    background: #fffdf4;
    border: 2px solid var(--pixel-line);
    outline: 0;
}

.profile-modal input:focus {
    border-color: var(--pixel-orange-dark);
    box-shadow: 0 0 0 2px rgb(221 97 33 / 18%);
}

.profile-modal__copy {
    margin: 16px 0 0;
    color: var(--pixel-muted);
    line-height: 1.6;
}

.profile-modal__actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 18px;
}

.profile-modal-enter-active,
.profile-modal-leave-active {
    transition: opacity 150ms ease;
}

.profile-modal-enter-active .profile-modal,
.profile-modal-leave-active .profile-modal {
    transition:
        transform 150ms ease,
        opacity 150ms ease;
}

.profile-modal-enter-from,
.profile-modal-leave-to {
    opacity: 0;
}

.profile-modal-enter-from .profile-modal,
.profile-modal-leave-to .profile-modal {
    opacity: 0;
    transform: translateY(10px) scale(0.98);
}

@media (max-width: 980px) {
    .profile-sanctuary {
        min-height: 620px;
        grid-template-columns: minmax(0, 52%) minmax(350px, 48%);
    }

    .profile-dossier {
        width: calc(100% - 22px);
        padding: 17px;
    }

    .profile-resource-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 820px) {
    .profile-page {
        width: min(100% - 24px, 620px);
    }

    .profile-sanctuary {
        min-height: 0;
        grid-template-columns: 1fr;
        align-items: start;
        padding: 16px 12px;
        background-color: #b8e2c3;
        background-position: 34% top;
        background-size: auto 520px;
    }

    .profile-sanctuary__character {
        min-height: 440px;
        align-items: end;
        transform: none;
    }

    .profile-dossier {
        width: 100%;
        max-width: none;
        justify-self: stretch;
        margin-bottom: 2px;
        padding: 18px 16px;
    }

    .profile-command {
        grid-template-columns: 1fr;
    }

    .profile-command__actions {
        justify-content: flex-start;
    }
}

@media (max-width: 560px) {
    .profile-page {
        width: calc(100% - 20px);
        padding-bottom: 44px;
    }

    .profile-sanctuary {
        background-position: 33% top;
        background-size: auto 500px;
    }

    .profile-sanctuary__character {
        min-height: 410px;
    }

    .profile-dossier__head,
    .profile-section-head {
        align-items: stretch;
        flex-direction: column;
    }

    .profile-status {
        align-self: flex-start;
    }

    .profile-identity,
    .profile-stats,
    .profile-resource-grid {
        grid-template-columns: 1fr;
    }

    .profile-economy,
    .profile-command {
        padding: 18px 15px;
    }

    .profile-command__actions {
        display: grid;
        grid-template-columns: 1fr;
    }

    .profile-button {
        width: 100%;
    }

    .profile-modal__actions {
        display: grid;
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    .profile-button,
    .profile-modal-enter-active,
    .profile-modal-leave-active,
    .profile-modal-enter-active .profile-modal,
    .profile-modal-leave-active .profile-modal {
        transition: none;
    }
}
</style>
