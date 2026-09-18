<script setup lang="ts">
import { ArrowUp } from 'lucide-vue-next';
import type { PublicProfile, SocialLink } from '@/types/public-home';

withDefaults(
    defineProps<{
        siteName: string;
        profile?: PublicProfile | null;
        socialLinks?: SocialLink[];
    }>(),
    {
        profile: null,
        socialLinks: () => [],
    },
);

const emit = defineEmits<{
    navigate: [section: 'home'];
}>();

const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-white/10 py-8">
        <div
            class="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 text-sm text-public-subtle sm:px-6 lg:px-8"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-semibold text-public-strong">
                        {{ siteName }}<span class="text-public-accent">.</span>
                    </p>
                    <p v-if="profile?.professionalTitle" class="mt-1">
                        {{ profile.professionalTitle }}
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex w-fit items-center gap-2 rounded-md border border-public-line bg-public-muted/65 px-3 py-2 font-medium text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    @click="emit('navigate', 'home')"
                >
                    <ArrowUp class="size-4" aria-hidden="true" />
                    Back to top
                </button>
            </div>

            <div
                class="flex flex-col gap-3 border-t border-public-line/70 pt-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>&copy; {{ year }} {{ profile?.fullName ?? siteName }}</p>
                <div v-if="socialLinks.length" class="flex flex-wrap gap-3">
                    <a
                        v-for="link in socialLinks"
                        :key="link.url"
                        :href="link.url"
                        target="_blank"
                        rel="noreferrer"
                        class="hover:text-public-strong focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    >
                        {{ link.label }}
                    </a>
                </div>
            </div>
        </div>
    </footer>
</template>
