<script setup lang="ts">
defineProps<{
    id: string;
    eyebrow: string;
    title: string;
}>();
</script>

<template>
    <section
        :id="id"
        class="relative border-t border-white/10 py-8 sm:py-10"
        data-public-reveal
    >
        <div
            class="mx-auto grid w-full max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.3fr_0.7fr] lg:px-8"
        >
            <div class="space-y-3">
                <p
                    class="text-xs font-semibold tracking-[0.22em] text-public-accent uppercase"
                >
                    {{ eyebrow }}
                </p>
                <h2
                    class="text-3xl leading-tight font-semibold text-public-strong sm:text-4xl"
                >
                    {{ title }}
                    <span class="text-public-accent">.</span>
                </h2>
            </div>

            <div class="min-w-0">
                <slot />
            </div>
        </div>
    </section>
</template>
