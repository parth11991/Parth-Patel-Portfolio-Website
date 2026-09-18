<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    GraduationCap,
    Home as HomeIcon,
    Mail,
    Rocket,
    Sparkles,
    UserRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import LiquidGlassNavigation from '@/components/portfolio/LiquidGlassNavigation.vue';
import About from '@/components/public/About.vue';
import Contact from '@/components/public/Contact.vue';
import CosmicBackdrop from '@/components/public/CosmicBackdrop.vue';
import EducationSection from '@/components/public/Education.vue';
import ExperienceSection from '@/components/public/Experience.vue';
import Footer from '@/components/public/Footer.vue';
import Header from '@/components/public/Header.vue';
import Hero from '@/components/public/Hero.vue';
import PortfolioSection from '@/components/public/Portfolio.vue';
import Skills from '@/components/public/Skills.vue';

import { usePortfolioScroll } from '@/composables/usePortfolioScroll';
import type { SectionId } from '@/composables/usePortfolioScroll';
import type {
    Education,
    Experience,
    MorePortfolio,
    Portfolio,
    PublicProfile,
    PublicSettings,
    Skill,
    SocialLink,
} from '@/types/public-home';

const props = withDefaults(
    defineProps<{
        settings: PublicSettings | null;
        profile: PublicProfile | null;
        structuredData: Record<string, unknown>[];
        socialLinks: SocialLink[];
        skills: Skill[];
        experiences: Experience[];
        education: Education[];
        portfolios: Portfolio[];
        morePortfolios: MorePortfolio[];
    }>(),
    {
        settings: null,
        profile: null,
        structuredData: () => [],
        socialLinks: () => [],
        skills: () => [],
        experiences: () => [],
        education: () => [],
        portfolios: () => [],
        morePortfolios: () => [],
    },
);

const sections: Array<{
    id: SectionId;
    label: string;
    icon: Component;
}> = [
    { id: 'home', label: 'Home', icon: HomeIcon },
    { id: 'about', label: 'About', icon: UserRound },
    { id: 'skills', label: 'Skills', icon: Sparkles },
    { id: 'experience', label: 'Experience', icon: BriefcaseBusiness },
    { id: 'education', label: 'Education', icon: GraduationCap },
    { id: 'projects', label: 'Projects', icon: Rocket },
    { id: 'contact', label: 'Contact', icon: Mail },
];

const siteName =
    props.settings?.siteName ?? props.profile?.fullName ?? 'Portfolio';
const titleBase = props.settings?.metaTitle ?? siteName;
const profileTitle = props.profile?.professionalTitle?.trim();
const title =
    profileTitle && !titleBase.includes(profileTitle)
        ? `${titleBase} - ${profileTitle}`
        : titleBase;
const description =
    props.settings?.metaDescription ??
    props.profile?.heroIntro ??
    'Developer portfolio built with Laravel, Vue, and Inertia.';

const shellStyle = computed(() => ({
    '--public-accent': props.settings?.accentColor ?? '#2f7d72',
}));
const {
    activeSection,
    contentViewportRef,
    contentTrackRef,
    isSectionDragging,
    trackStyle,
    chooseSection,
    chooseFromNav,
    onNavDrag,
    onContentWheel,
    onContentTouchStart,
    onContentTouchMove,
    onContentKeydown,
    onContentFocus,
    skipToContent,
} = usePortfolioScroll(sections);
</script>

<template>
    <Head :title="title">
        <meta
            head-key="description"
            name="description"
            :content="description"
        />
        <link
            v-if="settings?.canonicalUrl"
            head-key="canonical"
            rel="canonical"
            :href="settings.canonicalUrl"
        />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:title" property="og:title" :content="title" />
        <meta
            head-key="og:description"
            property="og:description"
            :content="description"
        />
        <meta
            v-if="settings?.canonicalUrl"
            head-key="og:url"
            property="og:url"
            :content="settings.canonicalUrl"
        />
        <meta
            v-if="settings?.ogImageUrl"
            head-key="og:image"
            property="og:image"
            :content="settings.ogImageUrl"
        />
        <component
            :is="'script'"
            v-for="(schema, index) in structuredData"
            :key="index"
            type="application/ld+json"
        >
            {{ JSON.stringify(schema) }}
        </component>
    </Head>

    <div
        class="relative isolate h-[100dvh] overflow-hidden bg-public-bg text-public-body"
        :style="shellStyle"
    >
        <a
            href="#portfolio-content"
            class="sr-only fixed top-2 left-2 z-50 rounded-md bg-public-bg p-3 text-public-strong focus:not-sr-only"
            @click.prevent="skipToContent"
            >Skip to content</a
        >
        <CosmicBackdrop />
        <Header :site-name="siteName" />

        <main
            class="absolute inset-x-0 top-0 bottom-0 z-10 overflow-hidden pt-[5.25rem] pr-[4.25rem] pb-4 pl-0 sm:pr-24"
        >
            <div
                id="portfolio-content"
                ref="contentViewportRef"
                tabindex="-1"
                aria-label="Portfolio content"
                class="h-full overflow-hidden"
                :class="isSectionDragging ? 'pointer-events-none' : ''"
                @keydown="onContentKeydown"
                @focusin="onContentFocus"
                @wheel="onContentWheel"
                @touchstart="onContentTouchStart"
                @touchmove="onContentTouchMove"
            >
                <div
                    ref="contentTrackRef"
                    class="transition-transform ease-out will-change-transform"
                    :style="trackStyle"
                >
                    <section
                        id="portfolio-panel-home"
                        class="transition-opacity duration-500 ease-out motion-reduce:transition-none"
                        role="tabpanel"
                        aria-labelledby="portfolio-tab-home"
                        :inert="isSectionDragging || undefined"
                    >
                        <Hero
                            :profile="profile"
                            :social-links="socialLinks"
                            @navigate="chooseSection"
                        />
                    </section>

                    <section
                        id="portfolio-panel-about"
                        class="transition-opacity duration-500 ease-out motion-reduce:transition-none"
                        role="tabpanel"
                        aria-labelledby="portfolio-tab-about"
                        :inert="isSectionDragging || undefined"
                    >
                        <About :profile="profile" />
                    </section>

                    <section
                        id="portfolio-panel-skills"
                        class="transition-opacity duration-500 ease-out motion-reduce:transition-none"
                        role="tabpanel"
                        aria-labelledby="portfolio-tab-skills"
                        :inert="isSectionDragging || undefined"
                    >
                        <Skills :skills="skills" />
                    </section>

                    <section
                        id="portfolio-panel-experience"
                        class="transition-opacity duration-500 ease-out motion-reduce:transition-none"
                        role="tabpanel"
                        aria-labelledby="portfolio-tab-experience"
                        :inert="isSectionDragging || undefined"
                    >
                        <ExperienceSection :experiences="experiences" />
                    </section>

                    <section
                        id="portfolio-panel-education"
                        class="transition-opacity duration-500 ease-out motion-reduce:transition-none"
                        role="tabpanel"
                        aria-labelledby="portfolio-tab-education"
                        :inert="isSectionDragging || undefined"
                    >
                        <EducationSection :education="education" />
                    </section>

                    <section
                        id="portfolio-panel-projects"
                        class="transition-opacity duration-500 ease-out motion-reduce:transition-none"
                        role="tabpanel"
                        aria-labelledby="portfolio-tab-projects"
                        :inert="isSectionDragging || undefined"
                    >
                        <PortfolioSection
                            :portfolios="portfolios"
                            :more-portfolios="morePortfolios"
                        />
                    </section>

                    <section
                        id="portfolio-panel-contact"
                        class="transition-opacity duration-500 ease-out motion-reduce:transition-none"
                        role="tabpanel"
                        aria-labelledby="portfolio-tab-contact"
                        :inert="isSectionDragging || undefined"
                    >
                        <Contact
                            :profile="profile"
                            :social-links="socialLinks"
                        />
                        <Footer
                            :site-name="siteName"
                            :profile="profile"
                            :social-links="socialLinks"
                            @navigate="chooseSection"
                        />
                    </section>
                </div>
            </div>
        </main>

        <LiquidGlassNavigation
            :items="sections"
            :active-id="activeSection"
            @select="chooseFromNav"
            @drag="onNavDrag"
        />
    </div>
</template>
