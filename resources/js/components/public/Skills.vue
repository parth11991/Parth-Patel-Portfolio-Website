<script setup lang="ts">
import {
    Braces,
    Code2,
    Cpu,
    Database,
    Layers3,
    PenTool,
    Server,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Skill } from '@/types/public-home';
import EmptyState from './EmptyState.vue';
import SectionShell from './SectionShell.vue';

const props = withDefaults(
    defineProps<{
        skills?: Skill[];
    }>(),
    {
        skills: () => [],
    },
);

const iconMap = {
    backend: Server,
    frontend: Code2,
    database: Database,
    devops: Cpu,
    design: PenTool,
    tooling: Braces,
    other: Layers3,
};

const groupedSkills = computed(() => {
    const groups = new Map<string, Skill[]>();

    props.skills.forEach((skill) => {
        const category = skill.category || 'other';
        groups.set(category, [...(groups.get(category) ?? []), skill]);
    });

    return Array.from(groups.entries()).map(([category, items]) => ({
        category,
        items,
        icon:
            iconMap[category.toLowerCase() as keyof typeof iconMap] ?? Sparkles,
    }));
});

function formatCategory(category: string) {
    return category.replace(/[-_]/g, ' ');
}
</script>

<template>
    <SectionShell id="skills" eyebrow="Skills" title="Tools and systems">
        <div v-if="groupedSkills.length" class="grid gap-4 lg:grid-cols-2">
            <section
                v-for="(group, groupIndex) in groupedSkills"
                :key="group.category"
                class="relative overflow-hidden rounded-md border border-public-line bg-public-muted/70 p-5 shadow-2xl shadow-black/10 backdrop-blur"
            >
                <div
                    class="absolute -top-16 -right-16 size-36 rounded-full border border-public-accent/20"
                    aria-hidden="true"
                />
                <div class="relative flex items-center gap-3">
                    <span
                        class="grid size-11 place-items-center rounded-md bg-public-accent/15 text-public-accent"
                    >
                        <component
                            :is="group.icon"
                            class="size-5"
                            aria-hidden="true"
                        />
                    </span>
                    <h3
                        class="text-sm font-semibold tracking-[0.16em] text-public-strong uppercase"
                    >
                        {{ formatCategory(group.category) }}
                    </h3>
                </div>

                <div
                    class="relative mt-5 flex flex-wrap gap-2"
                    role="list"
                    :aria-label="`${formatCategory(group.category)} skills`"
                >
                    <span
                        v-for="(skill, index) in group.items"
                        :key="`${skill.category}-${skill.name}`"
                        class="inline-flex min-h-10 items-center gap-2 rounded-md border border-public-line bg-public-bg/75 px-3 py-2 text-sm font-medium text-public-body shadow-sm transition hover:-translate-y-0.5 hover:border-public-accent hover:text-public-strong motion-reduce:hover:translate-y-0"
                        :style="{
                            transitionDelay: `${Math.min(index + groupIndex, 8) * 18}ms`,
                        }"
                        role="listitem"
                    >
                        <span
                            v-if="skill.iconKey"
                            class="grid size-6 place-items-center rounded-sm bg-public-accent/15 text-xs font-semibold text-public-accent"
                            aria-hidden="true"
                        >
                            {{ skill.iconKey.slice(0, 2).toUpperCase() }}
                        </span>
                        <span
                            v-else
                            class="size-2 rounded-full bg-public-accent"
                            aria-hidden="true"
                        />
                        {{ skill.name }}
                    </span>
                </div>
            </section>
        </div>
        <EmptyState v-else label="Visible skills not added yet." />
    </SectionShell>
</template>
