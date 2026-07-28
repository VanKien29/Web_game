<template>
    <div class="client-page client-page--giftcode">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Giftcode</span>
        </div>

        <section class="giftcode-hub" aria-labelledby="giftcode-title">
            <div class="giftcode-hero">
                <div class="giftcode-hero__copy">
                    <span class="giftcode-kicker">Kho quà Horizon</span>
                    <h1 id="giftcode-title">Giftcode chiến binh</h1>
                    <p>
                        Chọn một mã đang hoạt động, xem trước phần thưởng rồi
                        sao chép để nhập trực tiếp trong game.
                    </p>

                    <div
                        class="giftcode-hero__stats"
                        aria-label="Thống kê giftcode"
                    >
                        <div>
                            <strong>{{ giftcodes.length }}</strong>
                            <span>Mã khả dụng</span>
                        </div>
                        <div>
                            <strong>{{ totalRewardTypes }}</strong>
                            <span>Loại vật phẩm</span>
                        </div>
                    </div>
                </div>

                <div class="giftcode-hero__art" aria-hidden="true">
                    <span class="giftcode-hero__burst"></span>
                    <img
                        class="giftcode-hero__crystal"
                        src="/assets/pixel/gift-crystal.png"
                        alt=""
                    />
                    <img
                        class="giftcode-hero__gold giftcode-hero__gold--left"
                        src="/assets/pixel/gift-gold.png"
                        alt=""
                    />
                    <img
                        class="giftcode-hero__gold giftcode-hero__gold--right"
                        src="/assets/pixel/gift-gold.png"
                        alt=""
                    />
                    <span
                        class="giftcode-hero__spark giftcode-hero__spark--one"
                    ></span>
                    <span
                        class="giftcode-hero__spark giftcode-hero__spark--two"
                    ></span>
                    <span
                        class="giftcode-hero__spark giftcode-hero__spark--three"
                    ></span>
                </div>
            </div>

            <!-- <div class="giftcode-guide" aria-label="Hướng dẫn sử dụng giftcode">
                <div>
                    <span>01</span>
                    <p><strong>Mở mã quà</strong>Kiểm tra vật phẩm bên trong.</p>
                </div>
                <i aria-hidden="true">›</i>
                <div>
                    <span>02</span>
                    <p><strong>Sao chép mã</strong>Chỉ cần bấm một lần.</p>
                </div>
                <i aria-hidden="true">›</i>
                <div>
                    <span>03</span>
                    <p><strong>Nhập trong game</strong>Nhận quà vào hành trang.</p>
                </div>
            </div> -->

            <div ref="giftcodeBoard" class="giftcode-board">
                <div class="giftcode-board__head">
                    <div>
                        <span class="giftcode-board__eyebrow"
                            >Đang phát hành</span
                        >
                        <h2>Giftcode đang hoạt động</h2>
                    </div>
                    <p v-if="!loading && !errorMessage">
                        {{ giftcodes.length }} mã có thể sử dụng
                    </p>
                </div>

                <div
                    class="giftcode-copy-status"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    {{ copyMessage }}
                </div>

                <div v-if="loading" class="giftcode-loading" role="status">
                    <img src="/assets/pixel/gift-crystal.png" alt="" />
                    <strong>Đang mở kho quà...</strong>
                    <span>Vui lòng chờ trong giây lát.</span>
                </div>

                <div v-else-if="errorMessage" class="giftcode-state">
                    <img src="/assets/pixel/gift-gold.png" alt="" />
                    <strong>Chưa thể tải kho quà</strong>
                    <span>{{ errorMessage }}</span>
                    <button type="button" @click="loadGiftcodes">
                        Thử tải lại
                    </button>
                </div>

                <div v-else-if="giftcodes.length === 0" class="giftcode-state">
                    <img src="/assets/pixel/gift-crystal.png" alt="" />
                    <strong>Kho quà đang được chuẩn bị</strong>
                    <span
                        >Hiện chưa có giftcode khả dụng, hãy quay lại sau.</span
                    >
                </div>

                <div v-else class="giftcode-list">
                    <article
                        v-for="(giftcode, index) in paginatedGiftcodes"
                        :key="giftcode.id"
                        class="giftcode-pass"
                        :class="{
                            'giftcode-pass--open':
                                expandedGiftcodeId === giftcode.id,
                        }"
                    >
                        <div class="giftcode-pass__header">
                            <button
                                class="giftcode-pass__toggle"
                                type="button"
                                :aria-expanded="
                                    expandedGiftcodeId === giftcode.id
                                "
                                :aria-controls="`giftcode-rewards-${giftcode.id}`"
                                @click="toggleGiftcode(giftcode.id)"
                            >
                                <span class="giftcode-pass__emblem">
                                    <img
                                        :src="
                                            giftcodeNumber(index) % 2 === 1
                                                ? '/assets/pixel/gift-crystal.png'
                                                : '/assets/pixel/gift-gold.png'
                                        "
                                        alt=""
                                    />
                                </span>

                                <span class="giftcode-pass__identity">
                                    <small
                                        >Mã quà #{{
                                            giftcodeNumber(index)
                                        }}</small
                                    >
                                    <strong>{{ giftcode.code }}</strong>
                                    <span>
                                        {{ giftcode.items.length }} loại vật
                                        phẩm
                                    </span>
                                </span>

                                <span class="giftcode-pass__stock">
                                    <small>Còn lại</small>
                                    <strong>{{
                                        formatNumber(giftcode.count_left)
                                    }}</strong>
                                </span>

                                <span
                                    class="giftcode-pass__arrow"
                                    aria-hidden="true"
                                ></span>
                            </button>

                            <button
                                class="giftcode-copy"
                                type="button"
                                :class="{
                                    'giftcode-copy--done':
                                        copiedCode === giftcode.code,
                                }"
                                :aria-label="`Sao chép mã ${giftcode.code}`"
                                @click="copyCode(giftcode.code)"
                            >
                                <span aria-hidden="true"></span>
                                {{
                                    copiedCode === giftcode.code
                                        ? "Đã chép"
                                        : "Sao chép"
                                }}
                            </button>
                        </div>

                        <Transition name="gift-reveal">
                            <div
                                v-if="expandedGiftcodeId === giftcode.id"
                                :id="`giftcode-rewards-${giftcode.id}`"
                                class="giftcode-pass__rewards"
                            >
                                <div class="giftcode-pass__rewards-head">
                                    <div>
                                        <span>Phần thưởng</span>
                                        <strong
                                            >Vật phẩm nhận được khi đổi
                                            mã</strong
                                        >
                                    </div>
                                    <small
                                        >Bấm vào vật phẩm để xem chỉ số</small
                                    >
                                </div>

                                <div
                                    v-if="giftcode.items.length"
                                    class="giftcode-reward-grid"
                                >
                                    <button
                                        v-for="(
                                            item, itemIndex
                                        ) in giftcode.items"
                                        :key="
                                            rewardKey(
                                                giftcode.id,
                                                item,
                                                itemIndex,
                                            )
                                        "
                                        type="button"
                                        class="giftcode-reward"
                                        :class="{
                                            'giftcode-reward--selected':
                                                selectedRewardKey ===
                                                rewardKey(
                                                    giftcode.id,
                                                    item,
                                                    itemIndex,
                                                ),
                                        }"
                                        :aria-expanded="
                                            selectedRewardKey ===
                                            rewardKey(
                                                giftcode.id,
                                                item,
                                                itemIndex,
                                            )
                                        "
                                        @click="
                                            toggleReward(
                                                rewardKey(
                                                    giftcode.id,
                                                    item,
                                                    itemIndex,
                                                ),
                                            )
                                        "
                                    >
                                        <span class="giftcode-reward__icon">
                                            <img
                                                :src="itemIconUrl(item.icon_id)"
                                                :alt="item.name || 'Vật phẩm'"
                                                loading="lazy"
                                                @error="useRewardFallback"
                                            />
                                            <b>x{{ item.quantity }}</b>
                                        </span>
                                        <span class="giftcode-reward__body">
                                            <strong>{{
                                                item.name || "Vật phẩm bí ẩn"
                                            }}</strong>
                                            <small>{{
                                                item.options.length
                                                    ? `${item.options.length} chỉ số`
                                                    : "Vật phẩm thường"
                                            }}</small>
                                        </span>

                                        <span
                                            v-if="
                                                selectedRewardKey ===
                                                rewardKey(
                                                    giftcode.id,
                                                    item,
                                                    itemIndex,
                                                )
                                            "
                                            class="giftcode-reward__details"
                                        >
                                            <span v-if="item.description">
                                                {{ item.description }}
                                            </span>
                                            <span
                                                v-for="option in item.options"
                                                :key="option.id"
                                            >
                                                {{ option.text }}
                                            </span>
                                            <span
                                                v-if="
                                                    !item.description &&
                                                    !item.options.length
                                                "
                                            >
                                                Không có chỉ số bổ sung.
                                            </span>
                                        </span>
                                    </button>
                                </div>

                                <div v-else class="giftcode-pass__empty">
                                    Chưa có thông tin phần thưởng cho mã này.
                                </div>
                            </div>
                        </Transition>
                    </article>

                    <nav
                        v-if="totalPages > 1"
                        class="giftcode-pagination"
                        aria-label="Phân trang giftcode"
                    >
                        <button
                            type="button"
                            :disabled="currentPage === 1"
                            aria-label="Trang giftcode trước"
                            @click="changePage(currentPage - 1)"
                        >
                            <span aria-hidden="true">‹</span>
                            Trước
                        </button>

                        <div class="giftcode-pagination__pages">
                            <template
                                v-for="item in paginationItems"
                                :key="item.key"
                            >
                                <span
                                    v-if="item.page === null"
                                    class="giftcode-pagination__ellipsis"
                                    aria-hidden="true"
                                >
                                    …
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    :class="{
                                        active: item.page === currentPage,
                                    }"
                                    :aria-current="
                                        item.page === currentPage
                                            ? 'page'
                                            : undefined
                                    "
                                    :aria-label="`Trang giftcode ${item.page}`"
                                    @click="changePage(item.page)"
                                >
                                    {{ item.page }}
                                </button>
                            </template>
                        </div>

                        <button
                            type="button"
                            :disabled="currentPage === totalPages"
                            aria-label="Trang giftcode tiếp theo"
                            @click="changePage(currentPage + 1)"
                        >
                            Sau
                            <span aria-hidden="true">›</span>
                        </button>

                        <small>
                            Trang {{ currentPage }} / {{ totalPages }}
                        </small>
                    </nav>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import axios from "axios";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

interface GiftcodeOption {
    id: number;
    text: string;
    param: number;
    raw: string;
}

interface GiftcodeItem {
    temp_id: number;
    name: string;
    description: string;
    icon_id: number;
    quantity: number;
    options: GiftcodeOption[];
}

interface Giftcode {
    id: number;
    code: string;
    count_left: number;
    expired?: string | number | null;
    items: GiftcodeItem[];
}

interface GiftcodeResponse {
    ok: boolean;
    data?: Giftcode[];
}

interface PaginationItem {
    key: string;
    page: number | null;
}

const GIFTCODES_PER_PAGE = 5;

const giftcodes = ref<Giftcode[]>([]);
const giftcodeBoard = ref<HTMLElement | null>(null);
const loading = ref(true);
const errorMessage = ref("");
const copyMessage = ref("");
const copiedCode = ref("");
const expandedGiftcodeId = ref<number | null>(null);
const selectedRewardKey = ref<string | null>(null);
const currentPage = ref(1);

let requestController: AbortController | null = null;
let copyTimer: ReturnType<typeof window.setTimeout> | null = null;

const totalRewardTypes = computed(() =>
    giftcodes.value.reduce(
        (total, giftcode) => total + giftcode.items.length,
        0,
    ),
);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(giftcodes.value.length / GIFTCODES_PER_PAGE)),
);

const paginatedGiftcodes = computed(() => {
    const start = (currentPage.value - 1) * GIFTCODES_PER_PAGE;
    return giftcodes.value.slice(start, start + GIFTCODES_PER_PAGE);
});

const paginationItems = computed<PaginationItem[]>(() => {
    const total = totalPages.value;
    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => ({
            key: `page-${index + 1}`,
            page: index + 1,
        }));
    }

    if (currentPage.value <= 4) {
        return [
            ...[1, 2, 3, 4, 5].map((page) => ({
                key: `page-${page}`,
                page,
            })),
            { key: "ellipsis-end", page: null },
            { key: `page-${total}`, page: total },
        ];
    }

    if (currentPage.value >= total - 3) {
        return [
            { key: "page-1", page: 1 },
            { key: "ellipsis-start", page: null },
            ...[total - 4, total - 3, total - 2, total - 1, total].map(
                (page) => ({
                    key: `page-${page}`,
                    page,
                }),
            ),
        ];
    }

    return [
        { key: "page-1", page: 1 },
        { key: "ellipsis-start", page: null },
        ...[
            currentPage.value - 1,
            currentPage.value,
            currentPage.value + 1,
        ].map((page) => ({
            key: `page-${page}`,
            page,
        })),
        { key: "ellipsis-end", page: null },
        { key: `page-${total}`, page: total },
    ];
});

function formatNumber(value: number): string {
    return Number(value || 0).toLocaleString("vi-VN");
}

function giftcodeNumber(index: number): number {
    return (currentPage.value - 1) * GIFTCODES_PER_PAGE + index + 1;
}

function changePage(page: number): void {
    const nextPage = Math.min(Math.max(page, 1), totalPages.value);
    if (nextPage === currentPage.value) {
        return;
    }

    currentPage.value = nextPage;
    expandedGiftcodeId.value = null;
    selectedRewardKey.value = null;

    window.requestAnimationFrame(() => {
        const reduceMotion = window.matchMedia(
            "(prefers-reduced-motion: reduce)",
        ).matches;
        giftcodeBoard.value?.scrollIntoView({
            behavior: reduceMotion ? "auto" : "smooth",
            block: "start",
        });
    });
}

function itemIconUrl(iconId: number): string {
    return `/assets/frontend/home/v1/images/x4/${iconId}.png`;
}

function rewardKey(
    giftcodeId: number,
    item: GiftcodeItem,
    itemIndex: number,
): string {
    return `${giftcodeId}-${item.temp_id}-${item.icon_id}-${itemIndex}`;
}

function toggleGiftcode(giftcodeId: number): void {
    expandedGiftcodeId.value =
        expandedGiftcodeId.value === giftcodeId ? null : giftcodeId;
    selectedRewardKey.value = null;
}

function toggleReward(key: string): void {
    selectedRewardKey.value = selectedRewardKey.value === key ? null : key;
}

function useRewardFallback(event: Event): void {
    const image = event.currentTarget as HTMLImageElement;
    if (image.dataset.fallbackApplied) {
        return;
    }
    image.dataset.fallbackApplied = "true";
    image.src = "/assets/pixel/gift-crystal.png";
}

function fallbackCopy(code: string): boolean {
    const input = document.createElement("textarea");
    input.value = code;
    input.setAttribute("readonly", "");
    input.style.position = "fixed";
    input.style.opacity = "0";
    document.body.appendChild(input);
    input.select();
    const copied = document.execCommand("copy");
    input.remove();
    return copied;
}

async function copyCode(code: string): Promise<void> {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(code);
        } else if (!fallbackCopy(code)) {
            throw new Error("Clipboard unavailable");
        }

        copiedCode.value = code;
        copyMessage.value = `Đã sao chép mã ${code}.`;
    } catch {
        copiedCode.value = "";
        copyMessage.value = "Không thể sao chép tự động. Hãy chọn và chép mã.";
    }

    if (copyTimer) {
        window.clearTimeout(copyTimer);
    }
    copyTimer = window.setTimeout(() => {
        copiedCode.value = "";
        copyMessage.value = "";
    }, 2200);
}

async function loadGiftcodes(): Promise<void> {
    requestController?.abort();
    requestController = new AbortController();
    loading.value = true;
    errorMessage.value = "";
    expandedGiftcodeId.value = null;
    selectedRewardKey.value = null;

    try {
        const { data } = await axios.get<GiftcodeResponse>("/api/giftcodes", {
            signal: requestController.signal,
        });

        if (!data.ok) {
            throw new Error("Invalid giftcode response");
        }

        giftcodes.value = (data.data || []).filter(
            (giftcode) => Number(giftcode.count_left) > 0,
        );
        currentPage.value = 1;
    } catch (error) {
        if (axios.isCancel(error)) {
            return;
        }
        errorMessage.value =
            "Máy chủ chưa phản hồi. Bạn có thể thử tải lại ngay.";
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    void loadGiftcodes();
});

onBeforeUnmount(() => {
    requestController?.abort();
    if (copyTimer) {
        window.clearTimeout(copyTimer);
    }
});
</script>

<style scoped>
.client-page--giftcode button,
.client-page--giftcode input,
.client-page--giftcode select {
    font-family: inherit;
}

.giftcode-hub {
    display: grid;
    gap: 16px;
}

.giftcode-hero {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    min-height: 264px;
    overflow: hidden;
    background:
        linear-gradient(135deg, rgb(255 251 226 / 98%), rgb(247 226 169 / 97%)),
        url("/assets/pixel/nro-page-map.webp") center / cover;
    border: 2px solid var(--pixel-line);
    border-radius: 3px;
    box-shadow: 4px 4px 0 rgb(63 41 28 / 16%);
}

.giftcode-hero::before {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 8px;
    content: "";
    background: repeating-linear-gradient(
        90deg,
        var(--pixel-grass-dark) 0 8px,
        var(--pixel-grass) 8px 16px
    );
}

.giftcode-hero__copy {
    position: relative;
    z-index: 2;
    display: grid;
    align-content: center;
    justify-items: start;
    gap: 10px;
    padding: 30px 34px 36px;
}

.giftcode-kicker,
.giftcode-board__eyebrow {
    color: var(--pixel-orange-dark);
    font-family: var(--pixel-font);
    font-size: 1rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.giftcode-hero h1,
.giftcode-board h2 {
    margin: 0;
    color: var(--pixel-ink);
    font-family: var(--pixel-font);
    font-weight: 900;
    line-height: 0.95;
}

.giftcode-hero h1 {
    font-size: clamp(2.8rem, 6vw, 4.9rem);
    letter-spacing: 0.015em;
}

.giftcode-hero__copy > p {
    max-width: 610px;
    margin: 0;
    color: var(--pixel-muted);
    font-size: 0.95rem;
    line-height: 1.65;
}

.giftcode-hero__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.giftcode-hero__stats div {
    display: grid;
    grid-template-columns: auto auto;
    align-items: baseline;
    gap: 7px;
    padding: 7px 10px;
    background: rgb(255 254 247 / 78%);
    border: 1px solid rgb(111 67 39 / 28%);
}

.giftcode-hero__stats strong {
    color: var(--pixel-orange-dark);
    font-family: var(--pixel-font);
    font-size: 1.45rem;
    line-height: 1;
}

.giftcode-hero__stats span {
    color: var(--pixel-muted);
    font-size: 0.72rem;
    font-weight: 800;
}

.giftcode-hero__art {
    position: relative;
    display: grid;
    min-height: 260px;
    place-items: center;
    isolation: isolate;
}

.giftcode-hero__burst {
    position: absolute;
    z-index: -1;
    width: 220px;
    height: 220px;
    background: repeating-conic-gradient(
        from 0deg,
        rgb(255 255 255 / 52%) 0deg 12deg,
        rgb(255 207 63 / 20%) 12deg 24deg
    );
    border: 2px dashed rgb(169 68 24 / 28%);
    border-radius: 50%;
}

.giftcode-hero__crystal {
    width: 116px;
    height: auto;
    filter: drop-shadow(0 8px 0 rgb(21 85 127 / 24%));
    image-rendering: pixelated;
}

.giftcode-hero__gold {
    position: absolute;
    width: 62px;
    height: auto;
    filter: drop-shadow(0 5px 0 rgb(169 68 24 / 18%));
    image-rendering: pixelated;
}

.giftcode-hero__gold--left {
    bottom: 49px;
    left: 38px;
}

.giftcode-hero__gold--right {
    right: 36px;
    bottom: 54px;
    transform: scaleX(-1);
}

.giftcode-hero__spark {
    position: absolute;
    width: 9px;
    height: 9px;
    background: var(--pixel-focus);
    border: 2px solid var(--pixel-orange-dark);
    box-shadow: 0 -8px 0 -2px var(--pixel-focus);
}

.giftcode-hero__spark--one {
    top: 46px;
    left: 47px;
}

.giftcode-hero__spark--two {
    top: 64px;
    right: 43px;
}

.giftcode-hero__spark--three {
    right: 65px;
    bottom: 96px;
}

.giftcode-guide {
    display: grid;
    grid-template-columns: 1fr auto 1fr auto 1fr;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--pixel-paper);
    border: 1px solid rgb(111 67 39 / 38%);
    box-shadow: 2px 2px 0 rgb(63 41 28 / 10%);
}

.giftcode-guide > div {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.giftcode-guide > div > span {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    place-items: center;
    color: var(--pixel-white);
    background: var(--pixel-orange);
    border: 2px solid var(--pixel-line);
    box-shadow: 2px 2px 0 var(--pixel-orange-dark);
    font-family: var(--pixel-font);
    font-size: 1rem;
    font-weight: 900;
}

.giftcode-guide p {
    display: grid;
    gap: 2px;
    margin: 0;
    color: var(--pixel-muted);
    font-size: 0.72rem;
    line-height: 1.35;
}

.giftcode-guide strong {
    color: var(--pixel-ink);
    font-size: 0.82rem;
}

.giftcode-guide > i {
    color: var(--pixel-line-soft);
    font-family: var(--pixel-font);
    font-size: 1.7rem;
    font-style: normal;
    font-weight: 900;
}

.giftcode-board {
    display: grid;
    gap: 14px;
    padding: 22px;
    scroll-margin-top: 88px;
    background:
        linear-gradient(rgb(255 248 223 / 96%), rgb(255 248 223 / 96%)),
        repeating-linear-gradient(
            0deg,
            transparent 0 31px,
            rgb(200 140 80 / 12%) 31px 32px
        );
    border: 2px solid var(--pixel-line);
    border-radius: 3px;
    box-shadow: 4px 4px 0 rgb(63 41 28 / 14%);
}

.giftcode-board__head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 18px;
    padding-bottom: 14px;
    border-bottom: 1px dashed rgb(111 67 39 / 34%);
}

.giftcode-board h2 {
    margin-top: 3px;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
}

.giftcode-board__head > p {
    margin: 0;
    color: var(--pixel-muted);
    font-size: 0.78rem;
    font-weight: 800;
}

.giftcode-copy-status {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
}

.giftcode-list {
    display: grid;
    gap: 12px;
}

.giftcode-pagination {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    margin-top: 4px;
    padding-top: 14px;
    border-top: 1px dashed rgb(111 67 39 / 34%);
}

.giftcode-pagination > button,
.giftcode-pagination__pages button {
    display: inline-flex;
    min-width: 38px;
    min-height: 36px;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 11px;
    cursor: pointer;
    color: var(--pixel-ink);
    background: var(--pixel-paper);
    border: 1px solid rgb(111 67 39 / 48%);
    box-shadow: 2px 2px 0 rgb(63 41 28 / 11%);
    font: inherit;
    font-size: 0.72rem;
    font-weight: 900;
}

.giftcode-pagination > button:hover:not(:disabled),
.giftcode-pagination__pages button:hover,
.giftcode-pagination__pages button.active {
    color: var(--pixel-white);
    background: var(--pixel-orange);
    border-color: var(--pixel-line);
    box-shadow: 2px 2px 0 var(--pixel-orange-dark);
}

.giftcode-pagination > button:disabled {
    cursor: not-allowed;
    opacity: 0.42;
    box-shadow: none;
}

.giftcode-pagination > button span {
    font-family: var(--pixel-font);
    font-size: 1.2rem;
    line-height: 0.7;
}

.giftcode-pagination__pages {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 0;
}

.giftcode-pagination__pages button {
    padding-inline: 8px;
    font-family: var(--pixel-font);
    font-size: 1rem;
}

.giftcode-pagination__ellipsis {
    display: grid;
    min-width: 24px;
    min-height: 36px;
    place-items: center;
    color: var(--pixel-muted);
    font-family: var(--pixel-font);
    font-weight: 900;
}

.giftcode-pagination > small {
    grid-column: 1 / -1;
    color: var(--pixel-muted);
    font-size: 0.66rem;
    font-weight: 800;
    text-align: center;
}

.giftcode-pass {
    content-visibility: auto;
    contain-intrinsic-size: auto 88px;
    overflow: hidden;
    background: var(--pixel-cream);
    border: 1px solid rgb(111 67 39 / 42%);
    box-shadow: 2px 2px 0 rgb(63 41 28 / 10%);
}

.giftcode-pass--open {
    box-shadow: 3px 3px 0 rgb(21 85 127 / 13%);
}

.giftcode-pass__header {
    display: flex;
    align-items: stretch;
    gap: 8px;
    padding: 8px;
    background: linear-gradient(90deg, var(--pixel-paper), #fff5d4);
}

.giftcode-pass__toggle {
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr) minmax(90px, auto) 24px;
    flex: 1;
    align-items: center;
    gap: 12px;
    min-width: 0;
    padding: 4px;
    cursor: pointer;
    color: var(--pixel-ink);
    background: transparent;
    border: 0;
    text-align: left;
}

.giftcode-pass__emblem {
    display: grid;
    width: 52px;
    height: 52px;
    place-items: center;
    background: var(--pixel-white);
    border: 2px solid var(--pixel-line-soft);
    box-shadow: inset -3px -3px 0 rgb(200 140 80 / 16%);
}

.giftcode-pass__emblem img {
    width: 40px;
    max-height: 40px;
    object-fit: contain;
    image-rendering: pixelated;
}

.giftcode-pass__identity {
    display: grid;
    min-width: 0;
    gap: 2px;
}

.giftcode-pass__identity small,
.giftcode-pass__stock small {
    color: var(--pixel-muted);
    font-size: 0.66rem;
    font-weight: 800;
    text-transform: uppercase;
}

.giftcode-pass__identity strong {
    overflow: hidden;
    font-family: var(--pixel-font);
    font-size: clamp(1.25rem, 2.4vw, 1.7rem);
    font-weight: 900;
    letter-spacing: 0.04em;
    line-height: 1;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.giftcode-pass__identity > span {
    color: var(--pixel-muted);
    font-size: 0.72rem;
}

.giftcode-pass__stock {
    display: grid;
    justify-items: end;
    gap: 2px;
}

.giftcode-pass__stock strong {
    color: var(--pixel-orange-dark);
    font-family: var(--pixel-font);
    font-size: 1.25rem;
    line-height: 1;
}

.giftcode-pass__arrow {
    width: 11px;
    height: 11px;
    border-right: 3px solid var(--pixel-line);
    border-bottom: 3px solid var(--pixel-line);
    transform: rotate(45deg) translate(-2px, 2px);
    transition: transform 140ms steps(3, end);
}

.giftcode-pass--open .giftcode-pass__arrow {
    transform: rotate(225deg) translate(-2px, 2px);
}

.giftcode-copy {
    display: inline-flex;
    min-width: 104px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    cursor: pointer;
    color: var(--pixel-white);
    background: var(--pixel-orange);
    border: 2px solid var(--pixel-line);
    box-shadow: 2px 2px 0 var(--pixel-orange-dark);
    font-size: 0.75rem;
    font-weight: 900;
}

.giftcode-copy > span {
    position: relative;
    width: 12px;
    height: 14px;
    border: 2px solid currentcolor;
}

.giftcode-copy > span::before {
    position: absolute;
    top: -5px;
    left: 2px;
    width: 8px;
    height: 8px;
    content: "";
    border: 2px solid currentcolor;
}

.giftcode-copy:hover {
    translate: 1px 1px;
    box-shadow: 1px 1px 0 var(--pixel-orange-dark);
}

.giftcode-copy--done {
    background: var(--pixel-success);
    box-shadow: 2px 2px 0 #2f6729;
}

.giftcode-pass__rewards {
    display: grid;
    gap: 14px;
    padding: 16px;
    background:
        linear-gradient(rgb(244 226 179 / 94%), rgb(244 226 179 / 94%)),
        repeating-linear-gradient(
            0deg,
            transparent 0 27px,
            rgb(111 67 39 / 7%) 27px 28px
        );
    border-top: 1px dashed rgb(111 67 39 / 36%);
}

.giftcode-pass__rewards-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 12px;
}

.giftcode-pass__rewards-head > div {
    display: grid;
    gap: 2px;
}

.giftcode-pass__rewards-head span {
    color: var(--pixel-orange-dark);
    font-family: var(--pixel-font);
    font-size: 0.92rem;
    font-weight: 900;
    text-transform: uppercase;
}

.giftcode-pass__rewards-head strong {
    color: var(--pixel-ink);
    font-size: 0.84rem;
}

.giftcode-pass__rewards-head small {
    color: var(--pixel-muted);
    font-size: 0.68rem;
}

.giftcode-reward-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 9px;
}

.giftcode-reward {
    display: grid;
    grid-template-columns: 58px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    min-width: 0;
    padding: 9px;
    cursor: pointer;
    color: var(--pixel-ink);
    background: var(--pixel-paper);
    border: 1px solid rgb(111 67 39 / 28%);
    box-shadow: inset -2px -2px 0 rgb(200 140 80 / 12%);
    font: inherit;
    text-align: left;
}

.giftcode-reward:hover,
.giftcode-reward--selected {
    border-color: var(--pixel-blue-dark);
    background: #e4f3df;
    box-shadow: inset 3px 0 0 var(--pixel-blue);
}

.giftcode-reward__icon {
    position: relative;
    display: grid;
    width: 58px;
    height: 58px;
    place-items: center;
    background: transparent;
    border: 0;
}

.giftcode-reward__icon img {
    width: 48px;
    height: 48px;
    object-fit: contain;
    image-rendering: pixelated;
}

.giftcode-reward__icon b {
    position: absolute;
    right: 2px;
    bottom: 2px;
    min-width: 22px;
    padding: 1px 4px;
    color: var(--pixel-white);
    background: var(--pixel-ink);
    font-size: 0.62rem;
    line-height: 1.35;
    text-align: center;
}

.giftcode-reward__body {
    display: grid;
    min-width: 0;
    gap: 3px;
}

.giftcode-reward__body strong {
    overflow: hidden;
    font-size: 0.78rem;
    line-height: 1.4;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.giftcode-reward__body small {
    color: var(--pixel-muted);
    font-size: 0.67rem;
}

.giftcode-reward__details {
    display: grid;
    grid-column: 1 / -1;
    gap: 4px;
    padding-top: 8px;
    color: var(--pixel-muted);
    border-top: 1px dashed rgb(111 67 39 / 30%);
    font-size: 0.69rem;
    line-height: 1.45;
}

.giftcode-reward__details span:not(:first-child) {
    color: var(--pixel-blue-dark);
    font-weight: 700;
}

.giftcode-pass__empty {
    padding: 16px;
    color: var(--pixel-muted);
    background: var(--pixel-paper);
    border: 1px dashed var(--pixel-line-soft);
    font-size: 0.8rem;
    text-align: center;
}

.giftcode-loading,
.giftcode-state {
    display: grid;
    min-height: 230px;
    align-content: center;
    justify-items: center;
    gap: 8px;
    padding: 28px;
    color: var(--pixel-muted);
    background: rgb(247 232 189 / 52%);
    border: 1px dashed var(--pixel-line-soft);
    text-align: center;
}

.giftcode-loading img,
.giftcode-state img {
    width: 64px;
    max-height: 64px;
    object-fit: contain;
    image-rendering: pixelated;
}

.giftcode-loading img {
    animation: giftcode-float 900ms steps(4, end) infinite alternate;
}

.giftcode-loading strong,
.giftcode-state strong {
    color: var(--pixel-ink);
    font-family: var(--pixel-font);
    font-size: 1.35rem;
}

.giftcode-loading span,
.giftcode-state span {
    font-size: 0.78rem;
}

.giftcode-state button {
    margin-top: 5px;
    padding: 8px 14px;
    cursor: pointer;
    color: var(--pixel-white);
    background: var(--pixel-orange);
    border: 2px solid var(--pixel-line);
    box-shadow: 2px 2px 0 var(--pixel-orange-dark);
    font: inherit;
    font-size: 0.75rem;
    font-weight: 900;
}

.gift-reveal-enter-active,
.gift-reveal-leave-active {
    transition:
        opacity 120ms linear,
        transform 160ms steps(4, end);
}

.gift-reveal-enter-from,
.gift-reveal-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

@keyframes giftcode-float {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-8px);
    }
}

@media (max-width: 760px) {
    .giftcode-hero {
        grid-template-columns: 1fr;
    }

    .giftcode-hero__art {
        min-height: 170px;
        grid-row: 1;
    }

    .giftcode-hero__burst {
        width: 142px;
        height: 142px;
    }

    .giftcode-hero__crystal {
        width: 82px;
    }

    .giftcode-hero__gold {
        width: 48px;
    }

    .giftcode-hero__gold--left {
        bottom: 26px;
        left: calc(50% - 105px);
    }

    .giftcode-hero__gold--right {
        right: calc(50% - 105px);
        bottom: 30px;
    }

    .giftcode-hero__spark--one {
        top: 32px;
        left: calc(50% - 95px);
    }

    .giftcode-hero__spark--two {
        top: 42px;
        right: calc(50% - 95px);
    }

    .giftcode-hero__spark--three {
        display: none;
    }

    .giftcode-hero__copy {
        grid-row: 2;
        justify-items: center;
        padding: 8px 20px 30px;
        text-align: center;
    }

    .giftcode-hero h1 {
        font-size: clamp(2.7rem, 15vw, 4rem);
    }

    .giftcode-guide {
        grid-template-columns: 1fr;
    }

    .giftcode-guide > i {
        display: none;
    }

    .giftcode-board {
        padding: 14px;
    }

    .giftcode-pagination {
        grid-template-columns: 1fr 1fr;
    }

    .giftcode-pagination__pages {
        grid-row: 1;
        grid-column: 1 / -1;
        flex-wrap: wrap;
    }

    .giftcode-pagination > button {
        width: 100%;
    }

    .giftcode-pagination > small {
        grid-column: 1 / -1;
    }

    .giftcode-board__head {
        align-items: start;
        flex-direction: column;
        gap: 7px;
    }

    .giftcode-pass__header {
        align-items: stretch;
        flex-direction: column;
    }

    .giftcode-pass__toggle {
        grid-template-columns: 46px minmax(0, 1fr) 20px;
        gap: 9px;
    }

    .giftcode-pass__emblem {
        width: 46px;
        height: 46px;
    }

    .giftcode-pass__emblem img {
        width: 35px;
        max-height: 35px;
    }

    .giftcode-pass__stock {
        display: none;
    }

    .giftcode-copy {
        min-height: 40px;
    }

    .giftcode-pass__rewards {
        padding: 12px;
    }

    .giftcode-pass__rewards-head {
        align-items: start;
        flex-direction: column;
        gap: 4px;
    }

    .giftcode-reward-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 390px) {
    .giftcode-hero__stats {
        width: 100%;
    }

    .giftcode-hero__stats div {
        grid-template-columns: 1fr;
        flex: 1;
        justify-items: center;
        gap: 2px;
    }

    .giftcode-board {
        padding: 10px;
    }

    .giftcode-pass__identity strong {
        font-size: 1.2rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .giftcode-loading img,
    .giftcode-pass__arrow {
        animation: none;
        transition: none;
    }

    .gift-reveal-enter-active,
    .gift-reveal-leave-active {
        transition: none;
    }
}
</style>
