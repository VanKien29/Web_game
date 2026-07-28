<template>
    <div class="profile-character" aria-label="Ngoại hình nhân vật">
        <div
            v-if="visibleLayers.length"
            class="profile-character__sprite"
            :class="{
                'profile-character__sprite--costume':
                    appearance?.mode === 'costume',
            }"
        >
            <img
                v-for="layer in visibleLayers"
                :key="`${layer.key}-${layer.part_id}-${layer.icon_id}`"
                :src="layer.url"
                :alt="layerAlt(layer.key)"
                class="profile-character__layer"
                :style="layerStyle(layer)"
                draggable="false"
                decoding="async"
            />
        </div>

        <div v-else class="profile-character__fallback">
            <img
                v-if="fallbackAvatarUrl"
                :src="fallbackAvatarUrl"
                alt="Ảnh đại diện nhân vật"
                decoding="async"
            />
            <span v-else>?</span>
        </div>

        <div class="profile-character__plate">
            <strong>{{ characterName }}</strong>
            <span>{{ appearanceLabel }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { CharacterAppearance, CharacterLayer } from "../../types/profile";

const props = withDefaults(
    defineProps<{
        appearance?: CharacterAppearance;
        fallbackAvatarUrl?: string;
        characterName?: string;
    }>(),
    {
        appearance: undefined,
        fallbackAvatarUrl: "",
        characterName: "Chiến binh",
    },
);

const visibleLayers = computed(() =>
    [
        ...(props.appearance?.layers ?? []),
        ...(props.appearance?.extensions ?? []),
    ]
        .filter((layer) => layer.url && Number.isFinite(layer.z_index))
        .sort((left, right) => left.z_index - right.z_index),
);

const appearanceLabel = computed(() => {
    if (props.appearance?.mode === "equipment-fallback") {
        return "Trang bị hiện tại";
    }

    if (props.appearance?.mode === "costume" && props.appearance.costume_name) {
        return props.appearance.costume_name;
    }

    return "Trang phục chiến đấu";
});

function layerStyle(layer: CharacterLayer): Record<string, string | number> {
    const zoom = Math.max(
        1,
        Math.min(4, Number(props.appearance?.pose.zoom) || 4),
    );
    const x = Math.max(-96, Math.min(96, Number(layer.x) || 0)) * zoom;
    const y = Math.max(-96, Math.min(96, Number(layer.y) || 0)) * zoom;

    return {
        left: `calc(50% + ${x}px)`,
        top: `calc(85% + ${y}px)`,
        zIndex: layer.z_index,
    };
}

function layerAlt(key: string): string {
    return (
        {
            head: "Đầu nhân vật",
            body: "Thân nhân vật",
            leg: "Chân nhân vật",
        }[key] ?? ""
    );
}
</script>

<style scoped>
.profile-character {
    position: relative;
    display: grid;
    width: min(100%, 330px);
    height: 330px;
    justify-items: center;
    pointer-events: none;
}

.profile-character__sprite {
    position: absolute;
    bottom: 70px;
    left: 50%;
    width: 190px;
    height: 190px;
    transform: translateX(-50%) scale(1.3);
    transform-origin: center bottom;
    animation: profile-character-idle 2.8s steps(2, end) infinite;
}

.profile-character__layer {
    position: absolute;
    width: auto;
    max-width: none;
    height: auto;
    image-rendering: pixelated;
    user-select: none;
}

.profile-character__fallback {
    position: absolute;
    bottom: 78px;
    display: grid;
    width: 160px;
    height: 160px;
    place-items: center;
    overflow: hidden;
    color: var(--pixel-cream);
    background: rgb(61 42 34 / 76%);
    border: 3px solid var(--pixel-line);
    box-shadow: 4px 4px 0 rgb(61 42 34 / 30%);
    font-family: var(--pixel-font);
    font-size: 3rem;
}

.profile-character__fallback img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    image-rendering: pixelated;
}

.profile-character__plate {
    position: absolute;
    bottom: 0;
    left: 50%;
    display: grid;
    min-width: 220px;
    gap: 2px;
    padding: 8px 18px 9px;
    transform: translateX(-50%);
    color: var(--pixel-ink);
    background: rgb(255 246 211 / 94%);
    border: 2px solid var(--pixel-line);
    box-shadow: 3px 3px 0 rgb(61 42 34 / 26%);
    text-align: center;
}

.profile-character__plate strong {
    font-family: var(--pixel-font);
    font-size: 1.45rem;
    line-height: 1;
}

.profile-character__plate span {
    color: var(--pixel-muted);
    font-size: 0.7rem;
}

@keyframes profile-character-idle {
    0%,
    100% {
        transform: translateX(-50%) translateY(0) scale(1.3);
    }

    50% {
        transform: translateX(-50%) translateY(-3px) scale(1.3);
    }
}

@media (max-width: 820px) {
    .profile-character {
        height: 300px;
    }

    .profile-character__sprite {
        bottom: 66px;
        transform: translateX(-50%) scale(1.15);
    }

    @keyframes profile-character-idle {
        0%,
        100% {
            transform: translateX(-50%) translateY(0) scale(1.15);
        }

        50% {
            transform: translateX(-50%) translateY(-2px) scale(1.15);
        }
    }
}

@media (prefers-reduced-motion: reduce) {
    .profile-character__sprite {
        animation: none;
    }
}
</style>
