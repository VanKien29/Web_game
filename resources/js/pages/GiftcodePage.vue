<template>
    <div class="client-page client-page--giftcode">
        <div class="breadcrumb client-breadcrumb">
            <router-link to="/">Trang chủ</router-link>
            <span>›</span>
            <span>Giftcode</span>
        </div>

        <section class="client-panel giftcode-container">
            <div class="client-page-head">
                <div>
                    <div class="client-panel__eyebrow">Quà tặng</div>
                    <h1 class="client-panel__title">Danh sách giftcode</h1>
                </div>
                <p class="client-panel__desc">
                    Bấm vào mã để mở phần thưởng, bấm tên mã để sao chép.
                </p>
            </div>

            <div v-if="loading" class="page-loading">
                <div class="page-loading__spinner"></div>
            </div>

            <div v-else-if="giftcodes.length === 0" class="no-giftcodes">
                Hiện chưa có giftcode nào.
            </div>

            <div v-else class="giftcode-list">
                <div
                    v-for="(gc, i) in giftcodes"
                    :key="gc.id"
                    class="giftcode-item"
                    :class="{ expanded: expandedIndex === i }"
                >
                    <div class="giftcode-header" @click="toggleGiftcode(i)">
                        <div class="gift-icon"></div>
                        <div class="giftcode-info">
                            <div
                                class="giftcode-name copy-code"
                                @click.stop="copyCode(gc.code)"
                            >
                                {{ gc.code }}
                                <span class="copy-icon">Copy</span>
                            </div>
                            <div class="giftcode-stats">
                                Còn lại: {{ gc.count_left }}
                            </div>
                        </div>
                        <div class="expand-icon"></div>
                    </div>
                    <div class="giftcode-details">
                        <div
                            v-if="gc.items && gc.items.length"
                            class="items-grid"
                        >
                            <div
                                v-for="item in gc.items"
                                :key="item.temp_id"
                                class="item-card"
                            >
                                <div class="item-icon-wrapper">
                                    <div
                                        class="item-icon"
                                        :style="{
                                            backgroundImage: `url('/assets/frontend/home/v1/images/x4/${item.icon_id}.png')`,
                                        }"
                                    ></div>
                                    <div
                                        v-if="
                                            item.options && item.options.length
                                        "
                                        class="item-tooltip"
                                    >
                                        <div
                                            v-for="opt in item.options"
                                            :key="opt.id"
                                            class="tooltip-line"
                                        >
                                            {{ opt.text }}
                                        </div>
                                    </div>
                                </div>
                                <div class="item-name">
                                    {{ item.name || "Không rõ" }}
                                </div>
                                <div class="item-quantity">
                                    x{{ item.quantity }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="client-empty">
                            Không có thông tin phần thưởng
                        </div>
                        <p class="giftcode-hint">
                            Click vào ảnh vật phẩm để xem chỉ số.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "GiftcodePage",
    data() {
        return { giftcodes: [], loading: true, expandedIndex: null };
    },
    methods: {
        toggleGiftcode(index) {
            this.expandedIndex = this.expandedIndex === index ? null : index;
        },
        copyCode(code) {
            navigator.clipboard.writeText(code).catch(() => {});
        },
    },
    async mounted() {
        try {
            const { data } = await axios.get("/api/giftcodes");
            if (data.ok) {
                this.giftcodes = (data.data || []).filter(
                    (gc) => gc.count_left > 0,
                );
            }
        } catch (err) {
            console.error(err);
        } finally {
            this.loading = false;
        }
    },
};
</script>
