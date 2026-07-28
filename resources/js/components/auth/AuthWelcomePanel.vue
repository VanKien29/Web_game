<template>
    <section class="client-panel client-auth-card">
        <div class="client-auth-art" aria-hidden="true">
            <span class="pixel-orb pixel-orb--large"><span>H</span></span>
            <div class="client-auth-art__hud">
                <span></span>
                <span></span>
            </div>
        </div>
        <div class="client-panel__eyebrow">{{ eyebrow }}</div>
        <h1 class="client-panel__title">{{ title }}</h1>
        <p class="client-panel__desc">{{ description }}</p>
        <div class="client-auth-links">
            <router-link to="/giftcode">Giftcode</router-link>
            <router-link to="/bxh">Bảng xếp hạng</router-link>
            <router-link to="/nap-atm">Nạp tiền</router-link>
        </div>
    </section>
</template>

<script setup lang="ts">
defineProps<{
    eyebrow: string;
    title: string;
    description: string;
}>();
</script>
