<template>
    <span class="admin-icon-frame" :class="{ 'is-empty': !resolvedSrc || failed }">
        <img
            v-if="resolvedSrc && !failed"
            :key="resolvedSrc"
            :src="resolvedSrc"
            :alt="alt"
            loading="lazy"
            decoding="async"
            @load="loaded = true"
            @error="handleError"
        />
    </span>
</template>

<script>
export default {
    name: "AdminIcon",
    props: {
        iconId: {
            type: [Number, String],
            default: null,
        },
        alt: {
            type: String,
            default: "",
        },
        cacheBust: {
            type: [Number, String],
            default: "",
        },
    },
    data() {
        return {
            failed: false,
            loaded: false,
            fallbackStep: 0,
            cacheKey: Date.now(),
        };
    },
    computed: {
        resolvedSrc() {
            if (
                this.iconId === null ||
                this.iconId === undefined ||
                this.iconId === ""
            ) {
                return "";
            }
            const id = Number(this.iconId);
            if (!Number.isInteger(id) || id < 0) return "";
            if (this.fallbackStep === 1) {
                return `/admin/api/title-items/icon/${id}?v=${this.resolvedCacheKey}`;
            }
            if (this.fallbackStep >= 2) {
                return `/assets/frontend/home/v1/images/x4/${id}.png`;
            }

            return `/assets/game-icons/x4/${id}.png?v=${this.resolvedCacheKey}`;
        },
        resolvedCacheKey() {
            return this.cacheBust || this.cacheKey;
        },
    },
    watch: {
        resolvedSrc() {
            this.failed = false;
            this.loaded = false;
        },
        iconId() {
            this.fallbackStep = 0;
            this.cacheKey = Date.now();
        },
        cacheBust() {
            this.fallbackStep = 0;
            this.cacheKey = Date.now();
        },
    },
    methods: {
        handleError() {
            if (this.fallbackStep < 2) {
                this.fallbackStep += 1;
                this.failed = false;
                return;
            }
            this.failed = true;
        },
    },
};
</script>

<style>
.admin-icon-frame {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--ds-gray-100);
    border: 1px solid var(--ds-border);
    overflow: hidden;
    flex-shrink: 0;
    vertical-align: middle;
    contain: strict;
}
.admin-icon-frame img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}
.admin-icon-frame.is-empty::before {
    content: "";
    width: 40%;
    height: 40%;
    border-radius: 50%;
    background: rgba(var(--ds-primary-rgb), 0.18);
}
</style>
