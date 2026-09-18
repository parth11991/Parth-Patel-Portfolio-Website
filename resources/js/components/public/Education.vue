<script setup lang="ts">
import type { Education } from '@/types/public-home';
import EmptyState from './EmptyState.vue';
import SectionShell from './SectionShell.vue';

withDefaults(
    defineProps<{
        education?: Education[];
    }>(),
    {
        education: () => [],
    },
);

function formatDateRange(startedAt: string | null, endedAt: string | null) {
    return `${startedAt ?? 'Start'} - ${endedAt ?? 'Present'}`;
}
</script>

<template>
    <SectionShell id="education" eyebrow="Education" title="Study and training">
        <ol
            v-if="education.length"
            class="relative space-y-5 before:absolute before:top-3 before:bottom-3 before:left-3 before:w-px before:bg-public-line sm:grid sm:grid-cols-2 sm:gap-5 sm:space-y-0 sm:before:hidden"
            aria-label="Education timeline"
        >
            <li
                v-for="item in education"
                :key="`${item.institution}-${item.qualification}`"
                class="relative pl-9 sm:pl-0"
            >
                <span
                    class="absolute top-2 left-0 z-10 size-6 rounded-full border border-public-hot bg-public-bg sm:hidden"
                    aria-hidden="true"
                />
                <article
                    class="h-full rounded-md border border-public-line bg-public-muted/60 p-5 shadow-xl shadow-black/5 backdrop-blur"
                >
                    <h3 class="font-semibold text-public-strong">
                        {{ item.qualification }}
                    </h3>
                    <p class="mt-1 text-public-body">{{ item.institution }}</p>
                    <p
                        v-if="item.fieldOfStudy"
                        class="mt-1 text-sm text-public-subtle"
                    >
                        {{ item.fieldOfStudy }}
                    </p>
                    <p
                        v-if="item.startedAt || item.endedAt"
                        class="mt-3 text-sm text-public-subtle"
                    >
                        {{ formatDateRange(item.startedAt, item.endedAt) }}
                    </p>
                    <p
                        v-if="item.description"
                        class="mt-4 text-sm leading-7 text-public-body"
                    >
                        {{ item.description }}
                    </p>
                </article>
            </li>
        </ol>
        <EmptyState v-else label="Visible education not added yet." />
    </SectionShell>
</template>
