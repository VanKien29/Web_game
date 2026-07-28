<template>
    <div class="profile-hud" aria-label="HP và MP của nhân vật">
        <img
            class="profile-hud__frame"
            src="/assets/pixel/profile-hud-frame.png"
            alt=""
            aria-hidden="true"
            draggable="false"
        />

        <span class="profile-hud__avatar">
            <img
                v-if="avatarUrl"
                :src="avatarUrl"
                alt="Ảnh đầu nhân vật"
                decoding="async"
                draggable="false"
            />
            <span v-else>?</span>
        </span>

        <span class="profile-hud__bar profile-hud__bar--hp">
            <img
                src="/assets/pixel/profile-hud-hp.png"
                alt=""
                aria-hidden="true"
                draggable="false"
            />
            <strong>HP {{ hp }}</strong>
        </span>

        <span class="profile-hud__bar profile-hud__bar--ki">
            <img
                src="/assets/pixel/profile-hud-ki.png"
                alt=""
                aria-hidden="true"
                draggable="false"
            />
            <strong>MP {{ mp }}</strong>
        </span>

        <span
            class="profile-hud__bar profile-hud__bar--accent"
            aria-hidden="true"
        >
            <img
                src="/assets/pixel/profile-hud-power.png"
                alt=""
                aria-hidden="true"
                draggable="false"
            />
        </span>
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        avatarUrl?: string;
        hp?: string;
        mp?: string;
    }>(),
    {
        avatarUrl: "",
        hp: "0",
        mp: "0",
    },
);
</script>

<style scoped>
.profile-hud {
    position: relative;
    width: min(430px, 100%);
    aspect-ratio: 430 / 135;
    color: var(--pixel-white);
    font-family: var(--pixel-font);
    pointer-events: none;
}

.profile-hud__frame {
    position: absolute;
    z-index: 1;
    inset: 0;
    width: 100%;
    height: 100%;
    image-rendering: pixelated;
}

.profile-hud__avatar {
    position: absolute;
    z-index: 2;
    top: 9.5%;
    left: 3%;
    display: grid;
    width: 25.6%;
    aspect-ratio: 1;
    place-items: center;
    overflow: hidden;
    border-radius: 50%;
}

.profile-hud__avatar img {
    width: 94%;
    height: 94%;
    object-fit: contain;
    image-rendering: pixelated;
}

.profile-hud__avatar > span {
    font-size: clamp(1.5rem, 5vw, 3rem);
}

.profile-hud__bar {
    position: absolute;
    z-index: 2;
    left: 32.4%;
    display: grid;
    align-items: center;
    overflow: hidden;
}

.profile-hud__bar img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    image-rendering: pixelated;
}

.profile-hud__bar strong {
    position: relative;
    z-index: 1;
    overflow: hidden;
    padding-inline: 10px;
    font-size: clamp(0.55rem, 1.1vw, 0.78rem);
    font-weight: 900;
    letter-spacing: 0.025em;
    line-height: 1;
    text-align: left;
    text-overflow: ellipsis;
    text-shadow:
        1px 1px 0 rgb(44 22 10 / 90%),
        -1px -1px 0 rgb(44 22 10 / 42%);
    white-space: nowrap;
}

.profile-hud__bar--hp {
    top: 26%;
    width: 64.2%;
    height: 17%;
}

.profile-hud__bar--ki {
    top: 51.8%;
    width: 60.2%;
    height: 17%;
}

.profile-hud__bar--accent {
    top: 81.5%;
    width: 57.45%;
    height: 3.7%;
}
</style>
