<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    Github,
    ImageIcon,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type {
    MorePortfolio,
    Portfolio,
    PortfolioMedia,
} from '@/types/public-home';
import EmptyState from './EmptyState.vue';
import SectionShell from './SectionShell.vue';

const props = withDefaults(
    defineProps<{
        portfolios?: Portfolio[];
        morePortfolios?: MorePortfolio[];
    }>(),
    {
        portfolios: () => [],
        morePortfolios: () => [],
    },
);

const currentSlides = ref<Record<string, number>>({});
const pausedProjects = new Set<string>();
const autoplayTimers = new Map<string, number>();
const isReducedMotion = ref(false);
const isFinePointer = ref(false);
const isDocumentVisible = ref(true);
const touchStartX = ref(0);
const touchStartY = ref(0);
const selectedProject = ref<Portfolio | null>(null);
const modalSlide = ref(0);
const isMorePortfolioOpen = ref(false);
const isModalImageZoomed = ref(false);
const modalImageTransformOrigin = ref('center center');

let reducedMotionQuery: MediaQueryList | null = null;
let finePointerQuery: MediaQueryList | null = null;

const projectSlugs = computed(() =>
    props.portfolios.map((project) => project.slug),
);

function usableMedia(project: Portfolio): PortfolioMedia[] {
    return (project.media ?? []).filter((media) => Boolean(media.url));
}

const selectedProjectMedia = computed(() =>
    selectedProject.value ? usableMedia(selectedProject.value) : [],
);

const selectedProjectMediaItem = computed(
    () => selectedProjectMedia.value[modalSlide.value] ?? null,
);

function currentIndex(project: Portfolio) {
    const media = usableMedia(project);

    if (media.length === 0) {
        return 0;
    }

    return Math.min(currentSlides.value[project.slug] ?? 0, media.length - 1);
}

function setSlide(project: Portfolio, index: number) {
    const media = usableMedia(project);

    if (media.length === 0) {
        return;
    }

    const nextIndex = (index + media.length) % media.length;
    currentSlides.value = {
        ...currentSlides.value,
        [project.slug]: nextIndex,
    };
}

function nextSlide(project: Portfolio) {
    setSlide(project, currentIndex(project) + 1);
}

function previousSlide(project: Portfolio) {
    setSlide(project, currentIndex(project) - 1);
}

function setModalSlide(index: number) {
    const media = selectedProjectMedia.value;

    resetModalImageZoom();

    if (media.length === 0) {
        modalSlide.value = 0;

        return;
    }

    modalSlide.value = (index + media.length) % media.length;
}

function nextModalSlide() {
    setModalSlide(modalSlide.value + 1);
}

function previousModalSlide() {
    setModalSlide(modalSlide.value - 1);
}

function openProject(project: Portfolio) {
    selectedProject.value = project;
    modalSlide.value = currentIndex(project);
    pauseProject(project);
}

function closeProject() {
    const project = selectedProject.value;

    selectedProject.value = null;
    modalSlide.value = 0;
    resetModalImageZoom();

    if (project) {
        resumeProject(project);
    }
}

function openMorePortfolio() {
    isMorePortfolioOpen.value = true;
}

function closeMorePortfolio() {
    isMorePortfolioOpen.value = false;
}

function resetModalImageZoom() {
    isModalImageZoomed.value = false;
    modalImageTransformOrigin.value = 'center center';
}

function stopAutoplay(slug: string) {
    const timer = autoplayTimers.get(slug);

    if (timer) {
        window.clearInterval(timer);
        autoplayTimers.delete(slug);
    }
}

function shouldAutoplay(project: Portfolio) {
    return (
        usableMedia(project).length > 1 &&
        !isReducedMotion.value &&
        isDocumentVisible.value &&
        !pausedProjects.has(project.slug)
    );
}

function syncAutoplay() {
    props.portfolios.forEach((project) => {
        if (!shouldAutoplay(project)) {
            stopAutoplay(project.slug);

            return;
        }

        if (!autoplayTimers.has(project.slug)) {
            autoplayTimers.set(
                project.slug,
                window.setInterval(() => nextSlide(project), 7000),
            );
        }
    });

    Array.from(autoplayTimers.keys()).forEach((slug) => {
        if (!projectSlugs.value.includes(slug)) {
            stopAutoplay(slug);
        }
    });
}

function pauseProject(project: Portfolio) {
    pausedProjects.add(project.slug);
    stopAutoplay(project.slug);
}

function resumeProject(project: Portfolio) {
    pausedProjects.delete(project.slug);
    syncAutoplay();
}

function syncMediaQueries() {
    isReducedMotion.value = Boolean(reducedMotionQuery?.matches);
    isFinePointer.value = Boolean(finePointerQuery?.matches);

    if (isReducedMotion.value) {
        autoplayTimers.forEach((timer) => window.clearInterval(timer));
        autoplayTimers.clear();
    } else {
        syncAutoplay();
    }
}

function onVisibilityChange() {
    isDocumentVisible.value = document.visibilityState === 'visible';
    syncAutoplay();
}

function onCarouselKeydown(event: KeyboardEvent, project: Portfolio) {
    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        previousSlide(project);
    }

    if (event.key === 'ArrowRight') {
        event.preventDefault();
        nextSlide(project);
    }
}

function onProjectKeydown(event: KeyboardEvent, project: Portfolio) {
    if (event.target !== event.currentTarget) {
        return;
    }

    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    event.preventDefault();
    openProject(project);
}

function onModalKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        closeProject();
    }

    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        previousModalSlide();
    }

    if (event.key === 'ArrowRight') {
        event.preventDefault();
        nextModalSlide();
    }
}

function onModalImagePointerMove(event: PointerEvent) {
    if (!isFinePointer.value || isReducedMotion.value) {
        return;
    }

    const image = event.currentTarget as HTMLImageElement;
    const rect = image.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    isModalImageZoomed.value = true;
    modalImageTransformOrigin.value = `${x.toFixed(2)}% ${y.toFixed(2)}%`;
}

function onMorePortfolioKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        closeMorePortfolio();
    }
}

function onTouchStart(event: TouchEvent) {
    const touch = event.touches[0];

    if (!touch) {
        return;
    }

    touchStartX.value = touch.clientX;
    touchStartY.value = touch.clientY;
}

function onTouchEnd(event: TouchEvent, project: Portfolio) {
    const touch = event.changedTouches[0];

    if (!touch || usableMedia(project).length < 2) {
        return;
    }

    const deltaX = touch.clientX - touchStartX.value;
    const deltaY = touch.clientY - touchStartY.value;

    if (Math.abs(deltaX) < 42 || Math.abs(deltaX) < Math.abs(deltaY)) {
        return;
    }

    if (deltaX < 0) {
        nextSlide(project);
    } else {
        previousSlide(project);
    }
}

function onModalTouchEnd(event: TouchEvent) {
    const touch = event.changedTouches[0];

    if (!touch || selectedProjectMedia.value.length < 2) {
        return;
    }

    const deltaX = touch.clientX - touchStartX.value;
    const deltaY = touch.clientY - touchStartY.value;

    if (Math.abs(deltaX) < 42 || Math.abs(deltaX) < Math.abs(deltaY)) {
        return;
    }

    if (deltaX < 0) {
        nextModalSlide();
    } else {
        previousModalSlide();
    }
}

function onCardPointerMove(event: PointerEvent) {
    if (!isFinePointer.value || isReducedMotion.value) {
        return;
    }

    const card = event.currentTarget as HTMLElement;
    const rect = card.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width - 0.5;
    const y = (event.clientY - rect.top) / rect.height - 0.5;

    card.style.transform = `perspective(900px) rotateX(${(-y * 4).toFixed(2)}deg) rotateY(${(x * 5).toFixed(2)}deg) translateY(-2px)`;
}

function resetCardTilt(event: Event) {
    const card = event.currentTarget as HTMLElement;
    card.style.transform = '';
}

function cardMediaClass(media: PortfolioMedia) {
    return media.fit === 'contain' || media.type === 'logo'
        ? 'block max-h-[18rem] w-full object-contain p-8 sm:max-h-[20rem] sm:p-10 lg:max-h-[24rem]'
        : 'block h-auto w-full object-top';
}

function modalMediaClass(media: PortfolioMedia) {
    return media.fit === 'contain' || media.type === 'logo'
        ? 'object-contain p-8 sm:p-10'
        : 'object-contain p-2 sm:p-4';
}

function formatDateRange(project: Portfolio) {
    const end = project.isOngoing ? 'Present' : project.completedAt;

    return [project.startedAt, end].filter(Boolean).join(' - ');
}

function displayUrl(url: string | null) {
    if (!url) {
        return '';
    }

    try {
        return new URL(url).hostname.replace(/^www\./, '');
    } catch {
        return url;
    }
}

watch(
    () => props.portfolios,
    () => syncAutoplay(),
    { deep: true },
);

onMounted(() => {
    isDocumentVisible.value = document.visibilityState === 'visible';
    reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    finePointerQuery = window.matchMedia(
        '(hover: hover) and (pointer: fine) and (min-width: 768px)',
    );
    syncMediaQueries();

    reducedMotionQuery.addEventListener('change', syncMediaQueries);
    finePointerQuery.addEventListener('change', syncMediaQueries);
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onBeforeUnmount(() => {
    autoplayTimers.forEach((timer) => window.clearInterval(timer));
    autoplayTimers.clear();
    reducedMotionQuery?.removeEventListener('change', syncMediaQueries);
    finePointerQuery?.removeEventListener('change', syncMediaQueries);
    document.removeEventListener('visibilitychange', onVisibilityChange);
});
</script>

<template>
    <SectionShell id="portfolio" eyebrow="Portfolio" title="Selected projects">
        <div v-if="portfolios.length" class="grid gap-6">
            <article
                v-for="project in portfolios"
                :key="project.slug"
                class="grid cursor-pointer overflow-hidden rounded-md border border-public-line bg-public-muted/70 shadow-2xl shadow-black/15 backdrop-blur transition-transform duration-200 focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none lg:grid-cols-[1.1fr_0.9fr]"
                role="button"
                tabindex="0"
                :aria-label="`Open details for ${project.title}`"
                @click="openProject(project)"
                @keydown="onProjectKeydown($event, project)"
                @pointermove="onCardPointerMove"
                @pointerleave="resetCardTilt"
                @focusout="resetCardTilt"
            >
                <div
                    class="relative max-h-[18rem] self-center overflow-hidden bg-public-bg sm:max-h-[20rem] lg:max-h-[24rem]"
                    tabindex="0"
                    role="group"
                    :aria-label="`${project.title} media carousel`"
                    @keydown="onCarouselKeydown($event, project)"
                    @touchstart.passive="onTouchStart"
                    @touchend.passive="onTouchEnd($event, project)"
                    @pointerenter="pauseProject(project)"
                    @pointerleave="resumeProject(project)"
                    @focusin="pauseProject(project)"
                    @focusout="resumeProject(project)"
                >
                    <template v-if="usableMedia(project).length">
                        <img
                            :src="
                                usableMedia(project)[currentIndex(project)]
                                    .url ?? ''
                            "
                            :alt="
                                usableMedia(project)[currentIndex(project)]
                                    .altText ?? project.title
                            "
                            :class="[
                                cardMediaClass(
                                    usableMedia(project)[currentIndex(project)],
                                ),
                            ]"
                            width="960"
                            height="600"
                            loading="lazy"
                            decoding="async"
                        />

                        <div
                            v-if="usableMedia(project).length > 1"
                            class="absolute inset-x-3 bottom-3 flex items-center justify-between gap-3"
                        >
                            <button
                                type="button"
                                class="grid size-9 place-items-center rounded-md border border-public-line bg-public-bg/90 text-public-strong shadow-sm transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                :aria-label="`Show previous image for ${project.title}`"
                                @click.stop="previousSlide(project)"
                            >
                                <ChevronLeft
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </button>

                            <div
                                class="flex items-center gap-2 rounded-md border border-public-line bg-public-bg/90 px-2 py-1 shadow-sm"
                                :aria-label="`${project.title} image slides`"
                            >
                                <button
                                    v-for="(_media, index) in usableMedia(
                                        project,
                                    )"
                                    :key="`${project.slug}-${index}`"
                                    type="button"
                                    class="size-2.5 rounded-full transition focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                    :class="
                                        currentIndex(project) === index
                                            ? 'bg-public-accent'
                                            : 'bg-public-line'
                                    "
                                    :aria-label="`Show image ${index + 1} of ${usableMedia(project).length} for ${project.title}`"
                                    :aria-current="
                                        currentIndex(project) === index
                                            ? 'true'
                                            : undefined
                                    "
                                    @click.stop="setSlide(project, index)"
                                />
                            </div>

                            <button
                                type="button"
                                class="grid size-9 place-items-center rounded-md border border-public-line bg-public-bg/90 text-public-strong shadow-sm transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                :aria-label="`Show next image for ${project.title}`"
                                @click.stop="nextSlide(project)"
                            >
                                <ChevronRight
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                    </template>

                    <div
                        v-else
                        class="grid h-full min-h-80 place-items-center bg-[radial-gradient(circle_at_center,var(--public-accent)_0,transparent_34%)] px-5 text-center text-sm text-public-subtle"
                    >
                        <div
                            class="space-y-3 rounded-md border border-public-line bg-public-bg/80 p-6 backdrop-blur"
                        >
                            <ImageIcon
                                class="mx-auto size-8 text-public-accent"
                                aria-hidden="true"
                            />
                            <p>Project media pending</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col justify-between gap-5 p-5 sm:p-6">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <h3
                                    class="text-lg font-semibold text-public-strong"
                                >
                                    {{ project.title }}
                                </h3>
                                <p
                                    v-if="formatDateRange(project)"
                                    class="shrink-0 text-sm text-public-subtle"
                                >
                                    {{ formatDateRange(project) }}
                                </p>
                            </div>
                            <p
                                class="text-sm leading-7 text-public-body sm:text-base"
                            >
                                {{ project.shortDescription }}
                            </p>
                        </div>

                        <p
                            v-if="project.experience"
                            class="text-sm text-public-subtle"
                        >
                            Built at {{ project.experience.companyName }}
                        </p>

                        <dl
                            v-if="project.role || project.outcome"
                            class="grid gap-3 text-sm leading-6 text-public-body"
                        >
                            <div v-if="project.role" class="space-y-1">
                                <dt
                                    class="text-xs font-semibold tracking-[0.14em] text-public-subtle uppercase"
                                >
                                    Role
                                </dt>
                                <dd>{{ project.role }}</dd>
                            </div>
                            <div v-if="project.outcome" class="space-y-1">
                                <dt
                                    class="text-xs font-semibold tracking-[0.14em] text-public-subtle uppercase"
                                >
                                    Outcome
                                </dt>
                                <dd>{{ project.outcome }}</dd>
                            </div>
                        </dl>

                        <div
                            v-if="project.technologies.length"
                            class="flex flex-wrap gap-2"
                            aria-label="Technologies"
                        >
                            <span
                                v-for="tech in project.technologies"
                                :key="tech"
                                class="rounded-sm border border-public-line bg-public-bg px-2 py-1 text-xs text-public-subtle"
                            >
                                {{ tech }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="project.projectUrl || project.repositoryUrl"
                        class="flex flex-wrap gap-3 text-sm"
                    >
                        <a
                            v-if="project.projectUrl"
                            :href="project.projectUrl"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex items-center gap-2 rounded-md border border-public-line px-3 py-2 font-medium text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                            @click.stop
                        >
                            <ExternalLink class="size-4" aria-hidden="true" />
                            Project
                        </a>
                        <a
                            v-if="project.repositoryUrl"
                            :href="project.repositoryUrl"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex items-center gap-2 rounded-md border border-public-line px-3 py-2 font-medium text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                            @click.stop
                        >
                            <Github class="size-4" aria-hidden="true" />
                            Source
                        </a>
                    </div>
                </div>
            </article>
        </div>
        <div
            v-if="morePortfolios.length"
            class="mt-6 flex justify-center sm:justify-end"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-md border border-public-line bg-public-muted/75 px-4 py-2 text-sm font-semibold text-public-strong shadow-xl shadow-black/10 transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                @click="openMorePortfolio"
            >
                View more
                <ExternalLink class="size-4" aria-hidden="true" />
            </button>
        </div>
        <EmptyState
            v-else-if="!portfolios.length"
            label="Published projects not added yet."
        />

        <Teleport to="body">
            <div
                v-if="selectedProject"
                class="fixed inset-0 z-50 grid place-items-center bg-black/70 p-3 backdrop-blur-sm sm:p-6"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="`portfolio-modal-${selectedProject.slug}`"
                tabindex="-1"
                @click.self="closeProject"
                @keydown="onModalKeydown"
            >
                <div
                    class="max-h-[92dvh] w-full max-w-6xl overflow-hidden rounded-md border border-public-line bg-public-bg text-public-body shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-public-line px-4 py-4 sm:px-6"
                    >
                        <div class="min-w-0 space-y-1">
                            <p
                                v-if="selectedProject.experience"
                                class="text-xs font-semibold tracking-[0.16em] text-public-subtle uppercase"
                            >
                                {{ selectedProject.experience.companyName }}
                            </p>
                            <h3
                                :id="`portfolio-modal-${selectedProject.slug}`"
                                class="text-xl font-semibold text-public-strong sm:text-2xl"
                            >
                                {{ selectedProject.title }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="grid size-9 shrink-0 place-items-center rounded-md border border-public-line text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                            :aria-label="`Close ${selectedProject.title} details`"
                            @click="closeProject"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </button>
                    </div>

                    <div
                        class="grid max-h-[calc(92dvh-5rem)] overflow-y-auto lg:grid-cols-[1.18fr_0.82fr]"
                    >
                        <div
                            class="relative h-[70dvh] max-h-[42rem] min-h-[18rem] bg-public-muted/45"
                            tabindex="0"
                            :aria-label="`${selectedProject.title} image slider`"
                            @touchstart.passive="onTouchStart"
                            @touchend.passive="onModalTouchEnd"
                        >
                            <img
                                v-if="selectedProjectMediaItem"
                                :src="selectedProjectMediaItem.url ?? ''"
                                :alt="
                                    selectedProjectMediaItem.altText ??
                                    selectedProject.title
                                "
                                :class="[
                                    'h-full w-full cursor-zoom-in transition-transform duration-200 ease-out',
                                    isModalImageZoomed
                                        ? 'scale-[2.1]'
                                        : 'scale-100',
                                    modalMediaClass(selectedProjectMediaItem),
                                ]"
                                :style="{
                                    transformOrigin: modalImageTransformOrigin,
                                }"
                                width="1180"
                                height="740"
                                loading="lazy"
                                decoding="async"
                                @pointermove="onModalImagePointerMove"
                                @pointerleave="resetModalImageZoom"
                            />

                            <div
                                v-else
                                class="grid min-h-[18rem] place-items-center p-6 text-center text-sm text-public-subtle sm:min-h-[24rem]"
                            >
                                <div
                                    class="space-y-3 rounded-md border border-public-line bg-public-bg/80 p-6"
                                >
                                    <ImageIcon
                                        class="mx-auto size-8 text-public-accent"
                                        aria-hidden="true"
                                    />
                                    <p>Project media pending</p>
                                </div>
                            </div>

                            <div
                                v-if="selectedProjectMedia.length > 1"
                                class="absolute inset-x-3 bottom-3 flex items-center justify-between gap-3"
                            >
                                <button
                                    type="button"
                                    class="grid size-10 place-items-center rounded-md border border-public-line bg-public-bg/90 text-public-strong shadow-sm transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                    :aria-label="`Show previous image for ${selectedProject.title}`"
                                    @click="previousModalSlide"
                                >
                                    <ChevronLeft
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </button>

                                <div
                                    class="flex items-center gap-2 rounded-md border border-public-line bg-public-bg/90 px-3 py-2 shadow-sm"
                                    :aria-label="`${selectedProject.title} image slides`"
                                >
                                    <button
                                        v-for="(
                                            _media, index
                                        ) in selectedProjectMedia"
                                        :key="`${selectedProject.slug}-modal-${index}`"
                                        type="button"
                                        class="size-2.5 rounded-full transition focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                        :class="
                                            modalSlide === index
                                                ? 'bg-public-accent'
                                                : 'bg-public-line'
                                        "
                                        :aria-label="`Show image ${index + 1} of ${selectedProjectMedia.length} for ${selectedProject.title}`"
                                        :aria-current="
                                            modalSlide === index
                                                ? 'true'
                                                : undefined
                                        "
                                        @click="setModalSlide(index)"
                                    />
                                </div>

                                <button
                                    type="button"
                                    class="grid size-10 place-items-center rounded-md border border-public-line bg-public-bg/90 text-public-strong shadow-sm transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                    :aria-label="`Show next image for ${selectedProject.title}`"
                                    @click="nextModalSlide"
                                >
                                    <ChevronRight
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </button>
                            </div>
                        </div>

                        <div class="space-y-5 p-4 sm:p-6">
                            <div class="space-y-3">
                                <p
                                    v-if="formatDateRange(selectedProject)"
                                    class="text-sm text-public-subtle"
                                >
                                    {{ formatDateRange(selectedProject) }}
                                </p>
                                <p class="text-sm leading-7 text-public-body">
                                    {{ selectedProject.shortDescription }}
                                </p>
                            </div>

                            <dl
                                v-if="
                                    selectedProject.role ||
                                    selectedProject.outcome
                                "
                                class="grid gap-4 text-sm leading-6"
                            >
                                <div
                                    v-if="selectedProject.role"
                                    class="rounded-md border border-public-line bg-public-muted/55 p-4"
                                >
                                    <dt
                                        class="text-xs font-semibold tracking-[0.14em] text-public-subtle uppercase"
                                    >
                                        Role
                                    </dt>
                                    <dd class="mt-2 text-public-body">
                                        {{ selectedProject.role }}
                                    </dd>
                                </div>
                                <div
                                    v-if="selectedProject.outcome"
                                    class="rounded-md border border-public-line bg-public-muted/55 p-4"
                                >
                                    <dt
                                        class="text-xs font-semibold tracking-[0.14em] text-public-subtle uppercase"
                                    >
                                        Outcome
                                    </dt>
                                    <dd class="mt-2 text-public-body">
                                        {{ selectedProject.outcome }}
                                    </dd>
                                </div>
                            </dl>

                            <div
                                v-if="selectedProject.technologies.length"
                                class="flex flex-wrap gap-2"
                                aria-label="Technologies"
                            >
                                <span
                                    v-for="tech in selectedProject.technologies"
                                    :key="tech"
                                    class="rounded-sm border border-public-line bg-public-muted/55 px-2 py-1 text-xs text-public-subtle"
                                >
                                    {{ tech }}
                                </span>
                            </div>

                            <div
                                v-if="
                                    selectedProject.projectUrl ||
                                    selectedProject.repositoryUrl
                                "
                                class="flex flex-wrap gap-3 text-sm"
                            >
                                <a
                                    v-if="selectedProject.projectUrl"
                                    :href="selectedProject.projectUrl"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="inline-flex items-center gap-2 rounded-md border border-public-line px-3 py-2 font-medium text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                >
                                    <ExternalLink
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                    Project
                                </a>
                                <a
                                    v-if="selectedProject.repositoryUrl"
                                    :href="selectedProject.repositoryUrl"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="inline-flex items-center gap-2 rounded-md border border-public-line px-3 py-2 font-medium text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                >
                                    <Github class="size-4" aria-hidden="true" />
                                    Source
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="isMorePortfolioOpen"
                class="fixed inset-0 z-50 grid place-items-center bg-black/70 p-3 backdrop-blur-sm sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="more-portfolio-title"
                tabindex="-1"
                @click.self="closeMorePortfolio"
                @keydown="onMorePortfolioKeydown"
            >
                <div
                    class="max-h-[92dvh] w-full max-w-5xl overflow-hidden rounded-md border border-public-line bg-public-bg text-public-body shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-public-line px-4 py-4 sm:px-6"
                    >
                        <div class="min-w-0 space-y-1">
                            <p
                                class="text-xs font-semibold tracking-[0.16em] text-public-subtle uppercase"
                            >
                                Additional commercial projects
                            </p>
                            <h3
                                id="more-portfolio-title"
                                class="text-xl font-semibold text-public-strong sm:text-2xl"
                            >
                                More portfolio
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="grid size-9 shrink-0 place-items-center rounded-md border border-public-line text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                            aria-label="Close more portfolio"
                            @click="closeMorePortfolio"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="max-h-[calc(92dvh-5rem)] overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead
                                class="sticky top-0 bg-public-muted text-xs font-semibold tracking-[0.14em] text-public-subtle uppercase"
                            >
                                <tr>
                                    <th class="px-4 py-3 sm:px-6">Project</th>
                                    <th class="px-4 py-3 sm:px-6">
                                        Technologies
                                    </th>
                                    <th class="px-4 py-3 sm:px-6">Link</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-public-line">
                                <tr
                                    v-for="project in morePortfolios"
                                    :key="`${project.title}-${project.projectUrl ?? ''}`"
                                    class="align-top"
                                >
                                    <td
                                        class="px-4 py-4 font-semibold text-public-strong sm:px-6"
                                    >
                                        {{ project.title }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="technology in project.technologies"
                                                :key="`${project.title}-${technology}`"
                                                class="rounded-sm border border-public-line bg-public-muted/55 px-2 py-1 text-xs text-public-subtle"
                                            >
                                                {{ technology }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <a
                                            v-if="project.projectUrl"
                                            :href="project.projectUrl"
                                            target="_blank"
                                            rel="noreferrer"
                                            class="inline-flex items-center gap-2 font-medium text-public-strong underline-offset-4 hover:text-public-accent hover:underline focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                                        >
                                            {{ displayUrl(project.projectUrl) }}
                                            <ExternalLink
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                        </a>
                                        <span v-else class="text-public-subtle">
                                            Not supplied
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Teleport>
    </SectionShell>
</template>
