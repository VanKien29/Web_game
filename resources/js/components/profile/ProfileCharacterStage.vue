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
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { CharacterAppearance, CharacterLayer } from "../../types/profile";

const props = withDefaults(
    defineProps<{
        appearance?: CharacterAppearance;
        fallbackAvatarUrl?: string;
    }>(),
    {
        appearance: undefined,
        fallbackAvatarUrl: "",
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

function layerStyle(layer: CharacterLayer): Record<string, string | number> {
    const zoom = Math.max(
        1,
        Math.min(4, Number(props.appearance?.pose.zoom) || 4),
    );
    const x = Math.max(-96, Math.min(96, Number(layer.x) || 0)) * zoom;
    const y = Math.max(-96, Math.min(96, Number(layer.y) || 0)) * zoom;

    return {
        left: `calc(50% + ${x}px)`,
        top: `calc(162px + ${y}px)`,
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
    bottom: 24px;
    left: calc(50% - 95px);
    width: 190px;
    height: 190px;
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
    bottom: 24px;
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

@media (max-width: 820px) {
    .profile-character {
        height: 300px;
    }

    .profile-character__sprite {
        bottom: 32px;
    }
}
</style>
