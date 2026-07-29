<template>
    <span
        class="forum-reaction-icon"
        :class="`forum-reaction-icon--${type}`"
        aria-hidden="true"
    >
        <i
            :class="iconClass"
            style="font-family: 'Font Awesome 7 Free' !important"
        ></i>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    type: string;
}>();

const icons: Record<string, string> = {
    like: "fa-solid fa-thumbs-up",
    love: "fa-solid fa-heart",
    haha: "fa-solid fa-face-laugh-squint",
    wow: "fa-solid fa-face-surprise",
    sad: "fa-solid fa-face-sad-tear",
    angry: "fa-solid fa-face-angry",
};

const iconClass = computed(
    () => icons[props.type] || icons.like,
);
</script>

<style scoped>
.forum-reaction-icon {
    width: 30px;
    height: 30px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    color: #5a351c;
    background: #ffd45a;
    border: 1px solid #a96422;
    border-radius: 50%;
    box-shadow: 1px 2px 0 rgba(80, 42, 16, 0.22);
    font-size: 15px;
    line-height: 1;
}

.forum-reaction-icon > i {
    width: auto !important;
    height: auto !important;
    color: inherit !important;
    background-image: none !important;
    font-family: "Font Awesome 7 Free" !important;
    image-rendering: auto;
    vertical-align: 0;
}

.forum-reaction-icon > i::before {
    display: inline-block !important;
    width: auto !important;
    height: auto !important;
    color: inherit !important;
    background-image: none !important;
    font-family: inherit !important;
    content: var(--fa) !important;
}

.forum-reaction-icon--love {
    color: #fff;
    background: #e84f65;
    border-color: #9a293d;
}

.forum-reaction-icon--haha {
    color: #6c3b12;
    background: #ffc83d;
}

.forum-reaction-icon--wow {
    color: #704018;
    background: #ffd868;
}

.forum-reaction-icon--sad {
    color: #287aa4;
    background: #ffe073;
}

.forum-reaction-icon--angry {
    color: #6d2516;
    background: #ef7542;
    border-color: #a33d25;
}
</style>
