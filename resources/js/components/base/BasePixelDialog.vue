<template>
    <Teleport to="body">
        <Transition name="pixel-dialog">
            <div
                v-if="open"
                class="pixel-dialog__backdrop"
                @click.self="emitCancel"
            >
                <section
                    class="pixel-dialog"
                    :class="`pixel-dialog--${tone}`"
                    :role="showCancel ? 'alertdialog' : 'dialog'"
                    aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="messageId"
                >
                    <div class="pixel-dialog__icon" aria-hidden="true">
                        <span v-if="tone === 'success'">✓</span>
                        <span v-else-if="tone === 'danger'">!</span>
                        <span v-else-if="tone === 'warning'">?</span>
                        <span v-else>i</span>
                    </div>

                    <div class="pixel-dialog__content">
                        <h2 :id="titleId">{{ title }}</h2>
                        <p :id="messageId">{{ body }}</p>
                    </div>

                    <div class="pixel-dialog__actions">
                        <button
                            v-if="showCancel"
                            type="button"
                            class="pixel-dialog__button"
                            @click="emitCancel"
                        >
                            {{ cancelLabel }}
                        </button>
                        <button
                            ref="confirmButton"
                            type="button"
                            class="pixel-dialog__button pixel-dialog__button--primary"
                            @click="$emit('confirm')"
                        >
                            {{ confirmLabel }}
                        </button>
                    </div>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from "vue";

type DialogTone = "info" | "success" | "warning" | "danger";

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        body: string;
        tone?: DialogTone;
        confirmLabel?: string;
        cancelLabel?: string;
        showCancel?: boolean;
    }>(),
    {
        tone: "info",
        confirmLabel: "Đồng ý",
        cancelLabel: "Hủy",
        showCancel: false,
    },
);

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

const confirmButton = ref<HTMLButtonElement | null>(null);
const instanceId = Math.random().toString(36).slice(2, 9);
const titleId = `pixel-dialog-title-${instanceId}`;
const messageId = `pixel-dialog-message-${instanceId}`;

function emitCancel() {
    if (props.showCancel) {
        emit("cancel");
        return;
    }

    emit("confirm");
}

function onKeydown(event: KeyboardEvent) {
    if (!props.open || event.key !== "Escape") return;
    emitCancel();
}

watch(
    () => props.open,
    async (open) => {
        if (!open) return;
        await nextTick();
        confirmButton.value?.focus();
    },
);

window.addEventListener("keydown", onKeydown);
onBeforeUnmount(() => window.removeEventListener("keydown", onKeydown));
</script>

<style scoped>
.pixel-dialog__backdrop {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(33, 23, 14, 0.58);
}

.pixel-dialog {
    --dialog-accent: var(--pixel-blue, #2494c7);
    width: min(430px, 100%);
    box-sizing: border-box;
    padding: 20px;
    display: grid;
    grid-template-columns: 50px minmax(0, 1fr);
    gap: 14px;
    color: var(--pixel-ink, #3e2617);
    background: var(--pixel-paper, #fff7dc);
    border: 2px solid var(--pixel-line, #70401f);
    box-shadow:
        5px 5px 0 rgba(73, 40, 18, 0.34),
        inset 0 0 0 4px rgba(255, 255, 255, 0.38);
}

.pixel-dialog--success {
    --dialog-accent: #58a63b;
}

.pixel-dialog--warning {
    --dialog-accent: #dc841c;
}

.pixel-dialog--danger {
    --dialog-accent: #c94d31;
}

.pixel-dialog__icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    color: #fff;
    background: var(--dialog-accent);
    border: 2px solid var(--pixel-line, #70401f);
    box-shadow: 2px 2px 0 rgba(73, 40, 18, 0.3);
    font-family: var(--pixel-font, sans-serif);
    font-size: 24px;
    font-weight: 800;
}

.pixel-dialog__content {
    min-width: 0;
}

.pixel-dialog h2 {
    margin: 0 0 7px;
    color: var(--pixel-orange-dark, #b84a14);
    font-family: var(--pixel-font, sans-serif);
    font-size: 24px;
    line-height: 1;
}

.pixel-dialog p {
    margin: 0;
    font-size: 15px;
    line-height: 1.55;
}

.pixel-dialog__actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding-top: 4px;
}

.pixel-dialog__button {
    min-width: 92px;
    min-height: 39px;
    padding: 8px 15px;
    color: var(--pixel-ink, #3e2617);
    background: var(--pixel-cream, #ffedbd);
    border: 2px solid var(--pixel-line, #70401f);
    box-shadow: 2px 2px 0 rgba(73, 40, 18, 0.28);
    font: 700 14px/1.2 Arial, sans-serif;
    cursor: pointer;
}

.pixel-dialog__button--primary {
    color: #fff;
    background: var(--dialog-accent);
}

.pixel-dialog__button:focus-visible {
    outline: 3px solid rgba(36, 148, 199, 0.35);
    outline-offset: 2px;
}

.pixel-dialog-enter-active,
.pixel-dialog-leave-active {
    transition: opacity 150ms ease;
}

.pixel-dialog-enter-active .pixel-dialog,
.pixel-dialog-leave-active .pixel-dialog {
    transition:
        transform 150ms ease,
        opacity 150ms ease;
}

.pixel-dialog-enter-from,
.pixel-dialog-leave-to {
    opacity: 0;
}

.pixel-dialog-enter-from .pixel-dialog,
.pixel-dialog-leave-to .pixel-dialog {
    opacity: 0;
    transform: translateY(10px) scale(0.97);
}

@media (prefers-reduced-motion: reduce) {
    .pixel-dialog-enter-active,
    .pixel-dialog-leave-active,
    .pixel-dialog-enter-active .pixel-dialog,
    .pixel-dialog-leave-active .pixel-dialog {
        transition: none;
    }
}
</style>
