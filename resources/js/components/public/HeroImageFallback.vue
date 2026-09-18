<script setup lang="ts">
import { ref } from 'vue';
defineProps<{ src: string; alt: string }>();
const failed = ref(false);
</script>

<template>
    <img
        v-if="!failed"
        :src="src"
        :alt="alt"
        class="h-full w-full object-contain"
        @error="failed = true"
    />
    <div
        v-else
        role="img"
        :aria-label="alt"
        class="grid h-full place-items-center text-public-subtle"
    >
        {{ alt }}
    </div>
</template>
