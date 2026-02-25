<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        MomentDesk logo mark: stylised "M" whose centre-dip forms a heart.
        Uses a violet → indigo gradient so it renders beautifully on both
        light and dark backgrounds.  All path coordinates live in a 40 × 40
        viewBox so it scales perfectly at any size.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
        fill="none"
    >
        <defs>
            <linearGradient id="md-grad" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                <stop offset="0%"   stop-color="#a78bfa" /> <!-- violet-400 -->
                <stop offset="100%" stop-color="#4f46e5" /> <!-- indigo-600 -->
            </linearGradient>
        </defs>

        <!--
            The shape is a single closed path drawn with rounded stroke caps:
              - Left vertical bar of the M
              - Left diagonal down to the heart dip
              - Heart curve at the bottom centre
              - Right diagonal back up
              - Right vertical bar of the M

            Rendered as a thick rounded stroke so it looks identical to the
            reference image – clean, modern, works at 16 px or 512 px.
        -->
        <path
            d="
              M 6 32
              L 6 10
              Q 6 8 8 8
              Q 10 8 11 10
              L 20 24
              L 29 10
              Q 30 8 32 8
              Q 34 8 34 10
              L 34 32
            "
            stroke="url(#md-grad)"
            stroke-width="5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <!--
            Heart dip: two bezier curves that meet at the lowest point of the
            M's centre valley, creating the heart silhouette.
        -->
        <path
            d="
              M 11 18
              C 11 14 16 12 20 17
              C 24 12 29 14 29 18
              C 29 22 25 25 20 30
              C 15 25 11 22 11 18
              Z
            "
            fill="url(#md-grad)"
        />
    </svg>
</template>
