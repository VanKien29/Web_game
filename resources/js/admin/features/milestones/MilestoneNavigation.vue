<template>
    <nav class="milestone-nav" aria-label="Điều hướng mốc thưởng">
        <div class="milestone-nav__primary">
            <router-link
                v-for="option in MILESTONE_OPTIONS"
                :key="option.value"
                :to="{
                    name: milestoneRouteName,
                    params: { type: option.value },
                }"
                class="milestone-nav__button"
                :class="{ active: milestoneType === option.value }"
                :aria-current="
                    milestoneType === option.value ? 'page' : undefined
                "
            >
                {{ option.label }}
            </router-link>

            <router-link
                :to="welfareTarget"
                class="milestone-nav__button milestone-nav__welfare-toggle"
                :class="{ active: isWelfare }"
                :aria-expanded="isWelfare"
            >
                Phúc lợi
                <span class="mi milestone-nav__toggle-icon">
                    {{ isWelfare ? "expand_less" : "expand_more" }}
                </span>
            </router-link>
        </div>

        <div
            v-if="isWelfare"
            class="milestone-nav__secondary"
            aria-label="Danh mục phúc lợi"
        >
            <router-link
                v-for="option in WELFARE_TYPE_OPTIONS"
                :key="option.value"
                :to="{
                    name: 'admin.welfare-configs',
                    params: { type: option.value },
                }"
                class="milestone-nav__sub-button"
                :class="{ active: welfareType === option.value }"
                :aria-current="
                    welfareType === option.value ? 'page' : undefined
                "
            >
                {{ option.label }}
            </router-link>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { WELFARE_TYPE_OPTIONS, type WelfareType } from "./welfareTypes";

interface Props {
    milestoneType?: string;
    welfareType?: WelfareType;
    milestoneRouteName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    milestoneRouteName: "admin.milestones",
});

const MILESTONE_OPTIONS = [
    { value: "moc_nap", label: "Mốc nạp" },
    { value: "moc_nap_top", label: "Mốc nạp top" },
    { value: "moc_nhiem_vu_top", label: "Mốc nhiệm vụ top" },
    { value: "moc_suc_manh_top", label: "Mốc sức mạnh top" },
] as const;

const isWelfare = computed(() => Boolean(props.welfareType));
const welfareTarget = computed(() => ({
    name: "admin.welfare-configs",
    params: { type: props.welfareType || "attendance_daily" },
}));
</script>

<style scoped>
.milestone-nav {
    margin-bottom: 16px;
}

.milestone-nav__primary,
.milestone-nav__secondary {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.milestone-nav__button,
.milestone-nav__sub-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    border: 1px solid var(--ds-border);
    border-radius: 6px;
    background: var(--ds-surface);
    color: var(--ds-text);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.2;
    text-decoration: none;
    transition:
        border-color 0.15s,
        background-color 0.15s,
        color 0.15s;
}

.milestone-nav__button {
    padding: 7px 12px;
}

.milestone-nav__button:hover {
    border-color: rgba(var(--ds-primary-rgb), 0.48);
    background: rgba(var(--ds-primary-rgb), 0.06);
}

.milestone-nav__button.active {
    border-color: rgba(var(--ds-primary-rgb), 0.58);
    background: rgba(var(--ds-primary-rgb), 0.15);
    color: var(--ds-primary);
}

.milestone-nav__welfare-toggle {
    gap: 3px;
}

.milestone-nav__welfare-toggle.active {
    border-color: rgba(var(--ds-warning-rgb), 0.58);
    background: rgba(var(--ds-warning-rgb), 0.14);
    color: var(--ds-warning);
}

.milestone-nav__toggle-icon {
    font-size: 16px;
}

.milestone-nav__secondary {
    margin-top: 8px;
    padding: 8px;
    border: 1px solid rgba(var(--ds-warning-rgb), 0.25);
    border-left: 3px solid var(--ds-warning);
    border-radius: 6px;
    background: rgba(var(--ds-warning-rgb), 0.055);
}

.milestone-nav__sub-button {
    min-height: 30px;
    padding: 6px 10px;
    border-color: rgba(var(--ds-warning-rgb), 0.25);
    background: var(--ds-surface);
}

.milestone-nav__sub-button:hover {
    border-color: rgba(var(--ds-warning-rgb), 0.5);
    background: rgba(var(--ds-warning-rgb), 0.09);
}

.milestone-nav__sub-button.active {
    border-color: rgba(var(--ds-warning-rgb), 0.62);
    background: rgba(var(--ds-warning-rgb), 0.18);
    color: var(--ds-warning);
}

.milestone-nav__button:focus-visible,
.milestone-nav__sub-button:focus-visible {
    outline: 2px solid var(--ds-primary);
    outline-offset: 2px;
}

@media (max-width: 640px) {
    .milestone-nav__primary,
    .milestone-nav__secondary {
        gap: 6px;
    }

    .milestone-nav__button,
    .milestone-nav__sub-button {
        flex: 1 1 auto;
    }
}
</style>
