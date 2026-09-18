<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { home } from '@/routes';
import type { ThemeChoice } from '@/types/public-home';

const props = defineProps<{
    siteName: string;
}>();

const { appearance, updateAppearance } = useAppearance();

const choices: { value: ThemeChoice; label: string; icon: typeof Sun }[] = [
    { value: 'light', label: 'Light theme', icon: Sun },
    { value: 'dark', label: 'Dark theme', icon: Moon },
    { value: 'system', label: 'System theme', icon: Monitor },
];

const siteNameParts = computed(() => {
    const [first = '', ...rest] = props.siteName.split(' ');

    return {
        first,
        rest: rest.join(' '),
    };
});
</script>

<template>
    <header
        class="fixed top-3 right-3 left-2 z-40 sm:right-24 sm:left-[7.75rem]"
    >
        <div
            class="flex h-18 w-full items-center justify-between gap-2 rounded-md border border-white/20 bg-white/20 px-4 shadow-2xl shadow-black/10 backdrop-blur-2xl backdrop-saturate-150 sm:gap-3 sm:px-5 dark:border-white/10 dark:bg-public-muted/45"
        >
            <div
                class="min-w-0 text-2xl leading-none tracking-wide whitespace-nowrap text-public-strong sm:text-3xl"
            >
                <Link :href="home()" class="flex items-baseline">
                    <span class="truncate font-extrabold">{{
                        siteNameParts.first
                    }}</span>
                    <span v-if="siteNameParts.rest" class="ml-2 font-light">
                        {{ siteNameParts.rest
                        }}<span
                            class="-ml-0.5 text-4xl leading-none text-public-accent sm:text-5xl"
                            >.</span
                        >
                    </span>
                </Link>
            </div>

            <div
                class="flex shrink-0 items-center rounded-md border border-public-line/70 bg-white/10 p-1"
                role="group"
                aria-label="Theme"
            >
                <button
                    v-for="choice in choices"
                    :key="choice.value"
                    type="button"
                    :aria-label="choice.label"
                    :aria-pressed="appearance === choice.value"
                    class="grid size-8 place-items-center rounded-sm text-public-subtle transition hover:text-public-strong focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none aria-pressed:bg-public-accent/20 aria-pressed:text-public-strong"
                    @click="updateAppearance(choice.value)"
                >
                    <component
                        :is="choice.icon"
                        class="size-4"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </div>
    </header>
</template>
