<script setup lang="ts">
import {
    ArrowDownToLine,
    ExternalLink,
    FolderKanban,
    Github,
    Globe,
    Instagram,
    Linkedin,
    Mail,
    Twitter,
} from 'lucide-vue-next';
import { computed, defineAsyncComponent } from 'vue';
import HeroImageFallback from '@/components/public/HeroImageFallback.vue';
import type { PublicProfile, SocialLink } from '@/types/public-home';

const HeroImageEffect = defineAsyncComponent({
    loader: () => import('@/components/public/HeroImageEffect.vue'),
    loadingComponent: HeroImageFallback,
    errorComponent: HeroImageFallback,
    delay: 0,
});

const props = withDefaults(
    defineProps<{
        profile: PublicProfile | null;
        socialLinks?: SocialLink[];
    }>(),
    {
        profile: null,
        socialLinks: () => [],
    },
);

const emit = defineEmits<{
    navigate: [section: 'projects' | 'contact'];
}>();

const iconMap = {
    github: Github,
    linkedin: Linkedin,
    twitter: Twitter,
    x: Twitter,
    instagram: Instagram,
    website: Globe,
    globe: Globe,
    external: ExternalLink,
};

const titleParts = computed(() => {
    const title = props.profile?.professionalTitle?.trim();

    if (!title) {
        return ['Developer', 'Portfolio'];
    }

    const words = title.split(' ');
    const split = Math.max(1, Math.ceil(words.length / 2));

    return [words.slice(0, split).join(' '), words.slice(split).join(' ')];
});
const accessibleTitle = computed(() =>
    titleParts.value.filter(Boolean).join(' '),
);
const stats = computed(() => {
    const values: Array<{ label: string; value: string }> = [];

    if (
        props.profile?.yearsExperience !== null &&
        props.profile?.yearsExperience !== undefined
    ) {
        values.push({
            label: 'Years',
            value: `${props.profile.yearsExperience}+`,
        });
    }

    if (props.profile?.location) {
        values.push({ label: 'Base', value: props.profile.location });
    }

    if (props.profile?.availabilityText) {
        values.push({ label: 'Status', value: props.profile.availabilityText });
    }

    return values.slice(0, 3);
});

function socialIcon(link: SocialLink) {
    const key = (link.iconKey || link.label || '')
        .toLowerCase()
        .replace(/[^a-z]/g, '');

    return iconMap[key as keyof typeof iconMap] ?? Globe;
}
</script>

<template>
    <section
        id="top"
        class="relative mx-auto grid h-full w-full max-w-6xl items-start gap-6 px-4 pt-8 pb-4 sm:px-6 lg:grid-cols-[0.98fr_1.02fr] lg:gap-8 lg:px-8"
    >
        <div
            class="pointer-events-none absolute top-24 left-0 h-52 w-52 rounded-full border border-public-accent/20 opacity-70 blur-[1px]"
            aria-hidden="true"
        />

        <div class="relative z-10 min-w-0 space-y-5 lg:space-y-6">
            <div class="space-y-4">
                <h1
                    class="max-w-4xl text-5xl leading-[1.02] font-semibold text-public-strong sm:text-6xl lg:text-6xl xl:text-7xl"
                    :aria-label="accessibleTitle"
                >
                    <span aria-hidden="true">{{ titleParts[0] }}</span>
                    <span v-if="titleParts[1]" aria-hidden="true">
                        <br />
                        <span
                            class="bg-gradient-to-r from-public-accent via-public-hot to-public-strong bg-clip-text text-transparent"
                        >
                            {{ titleParts[1] }}
                        </span>
                    </span>
                </h1>
                <p
                    class="max-w-2xl text-base leading-8 text-public-body sm:text-lg"
                >
                    {{
                        profile?.heroIntro ??
                        'Public content will appear here after visible profile data is added in admin.'
                    }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-public-accent px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-public-accent/20 focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    @click="emit('navigate', 'projects')"
                >
                    <FolderKanban class="size-4" aria-hidden="true" />
                    View Projects
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md border border-public-line bg-white/10 px-5 py-3 text-sm font-semibold text-public-strong backdrop-blur transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    @click="emit('navigate', 'contact')"
                >
                    <Mail class="size-4" aria-hidden="true" />
                    Contact Me
                </button>
                <a
                    v-if="profile?.cvUrl"
                    :href="profile.cvUrl"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md border border-public-line bg-white/10 px-5 py-3 text-sm font-semibold text-public-strong backdrop-blur transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                >
                    <ArrowDownToLine class="size-4" aria-hidden="true" />
                    Download CV
                </a>
            </div>

            <div
                v-if="socialLinks.length"
                class="flex flex-wrap gap-2"
                aria-label="Social links"
            >
                <a
                    v-for="link in socialLinks"
                    :key="link.url"
                    :href="link.url"
                    class="grid size-11 place-items-center rounded-md border border-public-line bg-public-muted/70 text-public-subtle transition hover:border-public-accent hover:text-public-strong focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    rel="noreferrer"
                    target="_blank"
                    :aria-label="link.label"
                    :title="link.label"
                >
                    <component
                        :is="socialIcon(link)"
                        class="size-5"
                        aria-hidden="true"
                    />
                </a>
            </div>

            <dl v-if="stats.length" class="grid gap-3 sm:grid-cols-3">
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="rounded-md border border-public-line bg-public-muted/60 p-4 backdrop-blur"
                >
                    <dt
                        class="text-xs font-semibold tracking-[0.18em] text-public-subtle uppercase"
                    >
                        {{ stat.label }}
                    </dt>
                    <dd class="mt-2 text-lg font-semibold text-public-strong">
                        {{ stat.value }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="relative z-10 min-h-[26rem] min-w-0 lg:min-h-[34rem]">
            <div
                class="absolute inset-x-5 bottom-0 h-[78%] rounded-full bg-public-accent/20 blur-3xl"
                aria-hidden="true"
            />

            <div
                class="relative mx-auto flex h-full max-w-[34rem] items-end justify-center"
            >
                <div
                    v-if="profile?.profileImageUrl"
                    class="relative z-10 h-[min(42rem,calc(100dvh-8rem))] w-full max-w-[42rem] translate-x-14 overflow-visible sm:translate-x-0"
                >
                    <HeroImageEffect
                        :src="profile.profileImageUrl"
                        :alt="profile.fullName"
                    />
                </div>
                <div
                    v-else
                    class="relative z-10 grid aspect-[4/5] w-full max-w-[27rem] place-items-center rounded-md border border-public-line bg-public-muted/70 p-8 text-center text-public-subtle shadow-2xl backdrop-blur"
                >
                    Profile image pending
                </div>
            </div>
        </div>
    </section>
</template>
