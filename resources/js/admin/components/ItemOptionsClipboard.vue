<template>
    <span class="item-options-clipboard" @click.stop>
        <button
            type="button"
            class="item-options-clipboard__button"
            :disabled="!canCopy"
            title="Sao chép option của item"
            aria-label="Sao chép option của item"
            @click="copyOptions"
        >
            <span class="mi">content_copy</span>
        </button>
        <button
            type="button"
            class="item-options-clipboard__button"
            :disabled="!hasClipboard"
            title="Dán option đã sao chép vào item"
            aria-label="Dán option đã sao chép vào item"
            @click="pasteOptions"
        >
            <span class="mi">content_paste</span>
        </button>
    </span>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import {
    cloneItemOptions,
    ITEM_OPTIONS_CLIPBOARD_EVENT,
    readItemOptionsClipboard,
    writeItemOptionsClipboard,
} from "../shared/itemOptionsClipboard";

interface ItemOption {
    id?: number | null;
    param?: number;
    _pending?: boolean;
    confirmed?: boolean;
}

const props = defineProps<{ options: ItemOption[] }>();

const emit = defineEmits<{
    paste: [options: Array<{ id: number; param: number }>];
}>();
const hasClipboard = ref(readItemOptionsClipboard().length > 0);
const canCopy = computed(() => cloneItemOptions(props.options).length > 0);

function syncClipboardState() {
    hasClipboard.value = readItemOptionsClipboard().length > 0;
}

function copyOptions() {
    const copied = writeItemOptionsClipboard(props.options);
    hasClipboard.value = copied.length > 0;
}

function pasteOptions() {
    const copied = readItemOptionsClipboard();
    if (copied.length) emit("paste", copied);
}

onMounted(() => {
    window.addEventListener(ITEM_OPTIONS_CLIPBOARD_EVENT, syncClipboardState);
});

onUnmounted(() => {
    window.removeEventListener(ITEM_OPTIONS_CLIPBOARD_EVENT, syncClipboardState);
});
</script>

<style scoped>
.item-options-clipboard {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.item-options-clipboard__button {
    display: inline-grid;
    width: 27px;
    height: 27px;
    place-items: center;
    border: 1px solid var(--ds-border);
    border-radius: 6px;
    background: var(--ds-surface);
    color: var(--ds-text-muted);
    cursor: pointer;
}

.item-options-clipboard__button:hover:not(:disabled) {
    border-color: var(--ds-primary);
    color: var(--ds-primary);
}

.item-options-clipboard__button:disabled {
    cursor: not-allowed;
    opacity: 0.4;
}

.item-options-clipboard__button .mi {
    font-size: 15px;
}
</style>
