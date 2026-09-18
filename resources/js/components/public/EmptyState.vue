<script setup lang="ts">
defineProps<{
    label: string;
}>();
</script>

<template>
    <div
        class="rounded-md border border-dashed border-public-line bg-public-muted px-4 py-5 text-sm text-public-subtle"
    >
        {{ label }}
    </div>
</template>
