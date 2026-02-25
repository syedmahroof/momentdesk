<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <!-- Icon mark in a soft white/frosted container so the gradient shows through -->
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-black/5 dark:bg-white/10 dark:ring-white/10"
    >
        <AppLogoIcon class="size-5" />
    </div>

    <div class="ml-1 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-bold tracking-tight">Moment<span class="text-violet-500">Desk</span></span>
    </div>
</template>
