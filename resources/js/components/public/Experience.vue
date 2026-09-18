<script setup lang="ts">
import { MapPin } from 'lucide-vue-next';
import type { Experience } from '@/types/public-home';
import EmptyState from './EmptyState.vue';
import SectionShell from './SectionShell.vue';

withDefaults(
    defineProps<{
        experiences?: Experience[];
    }>(),
    {
        experiences: () => [],
    },
);

function formatDateRange(
    startedAt: string | null,
    endedAt: string | null,
    isCurrent = false,
) {
    return `${startedAt ?? 'Start'} - ${isCurrent ? 'Present' : (endedAt ?? 'Now')}`;
}
</script>

<template>
    <SectionShell id="experience" eyebrow="Experience" title="Recent work">
        <ol
            v-if="experiences.length"
            class="relative space-y-6 before:absolute before:top-3 before:bottom-3 before:left-3 before:w-px before:bg-gradient-to-b before:from-public-accent before:via-public-line before:to-transparent"
            aria-label="Experience timeline"
        >
            <li
                v-for="item in experiences"
                :key="item.id"
                class="relative pl-9"
            >
                <span
                    class="absolute top-2 left-0 z-10 size-6 rounded-full border border-public-accent bg-public-bg shadow-[0_0_0_6px_color-mix(in_srgb,var(--public-accent)_16%,transparent)]"
                    aria-hidden="true"
                />
                <article
                    class="rounded-md border border-public-line bg-public-muted/70 p-5 shadow-2xl shadow-black/10 backdrop-blur"
                    :class="
                        item.isCurrent ? 'ring-1 ring-public-accent/50' : ''
                    "
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0">
                            <h3
                                class="text-lg font-semibold text-public-strong"
                            >
                                {{ item.roleTitle }}
                            </h3>
                            <p class="text-public-body">
                                <a
                                    v-if="item.companyUrl"
                                    :href="item.companyUrl"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="underline-offset-4 hover:text-public-strong hover:underline focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                >
                                    {{ item.companyName }}
                                </a>
                                <span v-else>{{ item.companyName }}</span>
                            </p>
                        </div>
                        <p class="text-sm text-public-subtle">
                            {{
                                formatDateRange(
                                    item.startedAt,
                                    item.endedAt,
                                    item.isCurrent,
                                )
                            }}
                        </p>
                    </div>
                    <p
                        v-if="item.summary"
                        class="mt-4 text-sm leading-7 text-public-body"
                    >
                        {{ item.summary }}
                    </p>
                    <div
                        class="mt-4 flex flex-wrap gap-2 text-xs font-medium text-public-subtle"
                    >
                        <span
                            v-if="item.isCurrent"
                            class="rounded-sm bg-public-accent/15 px-2 py-1 text-public-accent"
                        >
                            Current
                        </span>
                        <span
                            v-if="item.location || item.employmentType"
                            class="inline-flex items-center gap-1 rounded-sm border border-public-line bg-public-bg/60 px-2 py-1"
                        >
                            <MapPin
                                v-if="item.location"
                                class="size-3"
                                aria-hidden="true"
                            />
                            {{
                                [item.location, item.employmentType]
                                    .filter(Boolean)
                                    .join(' / ')
                            }}
                        </span>
                    </div>
                </article>
            </li>
        </ol>
        <EmptyState v-else label="Visible experience not added yet." />
    </SectionShell>
</template>
