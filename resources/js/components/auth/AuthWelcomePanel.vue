<template>
    <section class="client-auth-scene" :class="`client-auth-scene--${variant}`">
        <img
            class="client-auth-scene__image"
            :src="imageUrl"
            alt=""
            width="768"
            height="512"
            fetchpriority="high"
            aria-hidden="true"
        />
        <div class="client-auth-scene__shade" aria-hidden="true"></div>
        <div class="client-auth-scene__content">
            <span class="pixel-kicker">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
                {{ eyebrow }}
            </span>
            <h1>{{ title }}</h1>
            <!-- <p>{{ description }}</p> -->
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";

type AuthVariant = "login" | "register";

const props = defineProps<{
    variant: AuthVariant;
    eyebrow: string;
    title: string;
    description: string;
}>();

const imageUrl = computed(() =>
    props.variant === "register"
        ? "/assets/pixel/auth/register-adventure.webp"
        : "/assets/pixel/auth/login-adventure.webp",
);
</script>

<style scoped>
.client-auth-scene {
    position: relative;
    min-height: 560px;
    overflow: hidden;
    background: #51c9e8;
    border: 3px solid var(--pixel-line);
    box-shadow: 5px 5px 0 rgb(63 41 28 / 18%);
    isolation: isolate;
}

.client-auth-scene__image,
.client-auth-scene__shade {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.client-auth-scene__image {
    object-fit: cover;
    object-position: 48% center;
    image-rendering: pixelated;
}

.client-auth-scene--register .client-auth-scene__image {
    object-position: 51% center;
}

.client-auth-scene__shade {
    z-index: 1;
    background:
        linear-gradient(
            180deg,
            transparent 34%,
            rgb(15 43 48 / 18%) 58%,
            rgb(42 25 16 / 82%) 100%
        ),
        linear-gradient(90deg, transparent 56%, rgb(255 255 255 / 9%));
}

.client-auth-scene__content {
    position: absolute;
    z-index: 2;
    right: 24px;
    bottom: 24px;
    left: 24px;
    padding: 20px;
    color: #fffef7;
    background: rgb(46 27 16 / 86%);
    border: 2px solid #f6bd63;
    box-shadow: 4px 4px 0 rgb(24 14 9 / 35%);
}

.client-auth-scene__content .pixel-kicker {
    color: #ffd476 !important;
}

.client-auth-scene__content h1 {
    margin: 10px 0 5px;
    font-family: var(--pixel-font);
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    font-weight: 850;
    line-height: 0.9;
    text-transform: uppercase;
}

.client-auth-scene__content p {
    max-width: 540px;
    margin: 0;
    color: #fff5da;
    font-size: 0.86rem;
    font-weight: 650;
    line-height: 1.65;
}

.client-auth-scene__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.client-auth-scene__chips span {
    display: inline-flex;
    min-height: 30px;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    color: var(--pixel-ink);
    background: var(--pixel-paper);
    border: 1px solid #f6bd63;
    font-size: 0.7rem;
    font-weight: 800;
}

@media (max-width: 860px) {
    .client-auth-scene {
        min-height: 290px;
    }

    .client-auth-scene__image,
    .client-auth-scene--register .client-auth-scene__image {
        object-position: center 52%;
    }

    .client-auth-scene__content {
        right: 12px;
        bottom: 12px;
        left: 12px;
        padding: 12px;
    }

    .client-auth-scene__content h1 {
        font-size: 2rem;
    }

    .client-auth-scene__content p {
        display: none;
    }

    .client-auth-scene__chips {
        margin-top: 8px;
    }
}

@media (max-width: 480px) {
    .client-auth-scene {
        min-height: 230px;
    }

    .client-auth-scene__chips span:last-child {
        display: none;
    }
}
</style>
