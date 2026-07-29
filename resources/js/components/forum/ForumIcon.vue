<template>
    <i
        :class="['forum-fa-icon', iconClass]"
        style="font-family: 'Font Awesome 7 Free' !important"
        aria-hidden="true"
    ></i>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    name: string;
}>();

const icons: Record<string, string> = {
    image: "fa-regular fa-image",
    tag: "fa-solid fa-hashtag",
    send: "fa-solid fa-paper-plane",
    pin: "fa-solid fa-thumbtack",
    edit: "fa-regular fa-pen-to-square",
    trash: "fa-regular fa-trash-can",
    "chevron-up": "fa-solid fa-chevron-up",
    "chevron-down": "fa-solid fa-chevron-down",
    comment: "fa-regular fa-comment-dots",
    share: "fa-solid fa-share-nodes",
    bookmark: "fa-regular fa-bookmark",
    lock: "fa-solid fa-lock",
    clock: "fa-regular fa-clock",
    fire: "fa-solid fa-fire-flame-curved",
    dot: "fa-regular fa-circle-dot",
    reply: "fa-solid fa-reply",
    heart: "fa-regular fa-heart",
    sliders: "fa-solid fa-sliders",
    envelope: "fa-regular fa-envelope",
    user: "fa-regular fa-circle-user",
    users: "fa-solid fa-user-group",
    "user-pen": "fa-solid fa-user-pen",
    comments: "fa-regular fa-comments",
    bullhorn: "fa-solid fa-bullhorn",
    grid: "fa-solid fa-table-cells-large",
    exclamation: "fa-solid fa-exclamation",
};

const iconClass = computed(
    () => icons[props.name] || "fa-regular fa-circle",
);
</script>

<style scoped>
.forum-fa-icon {
    width: 1.15em;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    height: 1em !important;
    color: inherit !important;
    background-image: none !important;
    font-family: "Font Awesome 7 Free" !important;
    line-height: 1;
    image-rendering: auto;
    vertical-align: 0;
}

.forum-fa-icon::before {
    display: inline-block !important;
    width: auto !important;
    height: auto !important;
    color: inherit !important;
    background-image: none !important;
    font-family: inherit !important;
    content: var(--fa) !important;
}
</style>
