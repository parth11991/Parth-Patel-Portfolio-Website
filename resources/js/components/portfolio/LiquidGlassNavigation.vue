<script setup lang="ts">
import gsap from 'gsap';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
} from 'vue';
import type { Component } from 'vue';

type NavItem = {
    id: string;
    label: string;
    icon: Component;
};

const props = defineProps<{
    items: NavItem[];
    activeId: string;
}>();

const emit = defineEmits<{
    select: [id: string];
    preview: [id: string | null];
    drag: [
        payload: {
            isDragging: boolean;
            index: number;
            nearestId: string | null;
        },
    ];
}>();

const railRef = ref<HTMLElement | null>(null);
const beadRef = ref<HTMLElement | null>(null);
const tabRefs = shallowRef<HTMLElement[]>([]);
const beadY = ref(0);
const focusedIndex = ref(0);
const previewId = ref<string | null>(null);
const isDragging = ref(false);
const reducedMotion = ref(false);

let mediaReduce: MediaQueryList | null = null;
let resizeObserver: ResizeObserver | null = null;
let pointerId: number | null = null;
let pendingClientY = 0;
let raf = 0;

const activeIndex = computed(() =>
    Math.max(
        0,
        props.items.findIndex((item) => item.id === props.activeId),
    ),
);
const activeItem = computed(
    () => props.items[activeIndex.value] ?? props.items[0],
);

function setTabRef(element: unknown, index: number) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    tabRefs.value[index] = element;
}

function tabCenters(): number[] {
    const rail = railRef.value;

    if (!rail) {
        return [];
    }

    const railRect = rail.getBoundingClientRect();

    return tabRefs.value.map((tab) => {
        const rect = tab.getBoundingClientRect();

        return rect.top - railRect.top + rect.height / 2;
    });
}

function nearestIndex(y: number): number {
    const centers = tabCenters();

    if (!centers.length) {
        return activeIndex.value;
    }

    return centers.reduce((nearest, center, index) => {
        const best = Math.abs(centers[nearest] - y);
        const next = Math.abs(center - y);

        return next < best ? index : nearest;
    }, 0);
}

function normalizedIndex(y: number): number {
    const centers = tabCenters();

    if (!centers.length) {
        return activeIndex.value;
    }

    if (y <= centers[0]) {
        return 0;
    }

    const lastIndex = centers.length - 1;

    if (y >= centers[lastIndex]) {
        return lastIndex;
    }

    for (let index = 0; index < lastIndex; index += 1) {
        const start = centers[index];
        const end = centers[index + 1];

        if (y >= start && y <= end) {
            return index + (y - start) / (end - start);
        }
    }

    return activeIndex.value;
}

function clampToRail(clientY: number): number {
    const rail = railRef.value;

    if (!rail) {
        return beadY.value;
    }

    const railRect = rail.getBoundingClientRect();
    const beadHeight = beadRef.value?.offsetHeight ?? 46;
    const min = beadHeight / 2 + 8;
    const max = railRect.height - beadHeight / 2 - 8;

    return Math.min(max, Math.max(min, clientY - railRect.top));
}

function animateBead(y: number) {
    const bead = beadRef.value;

    if (!bead) {
        beadY.value = y;

        return;
    }

    if (reducedMotion.value) {
        gsap.set(bead, { y });
        beadY.value = y;

        return;
    }

    gsap.to(bead, {
        y,
        duration: isDragging.value ? 0.16 : 0.58,
        ease: isDragging.value ? 'power2.out' : 'elastic.out(1, 0.72)',
        overwrite: true,
        onUpdate: () => {
            const current = gsap.getProperty(bead, 'y');
            beadY.value = typeof current === 'number' ? current : y;
        },
        onComplete: () => {
            beadY.value = y;
        },
    });
}

function syncToActive() {
    nextTick(() => {
        const centers = tabCenters();
        const center = centers[activeIndex.value];

        if (center !== undefined) {
            animateBead(center);
        }
    });
}

function selectIndex(index: number) {
    const item = props.items[index];

    if (!item) {
        return;
    }

    focusedIndex.value = index;
    previewId.value = null;
    emit('preview', null);

    emit('select', item.id);
    syncToActive();
}

function updateDragPreview(y: number) {
    const index = nearestIndex(y);
    const id = props.items[index]?.id ?? null;

    if (id !== previewId.value) {
        previewId.value = id;
        emit('preview', id);
    }

    emit('drag', {
        isDragging: true,
        index: normalizedIndex(y),
        nearestId: id,
    });
}

function applyDragFrame() {
    raf = 0;

    if (!isDragging.value) {
        return;
    }

    const y = clampToRail(pendingClientY);
    gsap.set(beadRef.value, { y });
    beadY.value = y;
    updateDragPreview(y);
}

function queueDrag(clientY: number) {
    pendingClientY = clientY;

    if (!raf) {
        raf = window.requestAnimationFrame(applyDragFrame);
    }
}

function startDrag(event: PointerEvent) {
    if (!railRef.value) {
        return;
    }

    event.preventDefault();
    pointerId = event.pointerId;
    isDragging.value = true;
    gsap.killTweensOf(beadRef.value);
    railRef.value.setPointerCapture(event.pointerId);
    emit('drag', {
        isDragging: true,
        index: activeIndex.value,
        nearestId: props.activeId,
    });
    queueDrag(event.clientY);
}

function moveDrag(event: PointerEvent) {
    if (!isDragging.value || event.pointerId !== pointerId) {
        return;
    }

    event.preventDefault();
    queueDrag(event.clientY);
}

function finishDrag(event?: PointerEvent) {
    if (!isDragging.value) {
        return;
    }

    if (event && event.pointerId !== pointerId) {
        return;
    }

    if (raf) {
        window.cancelAnimationFrame(raf);
        raf = 0;
    }

    const index = nearestIndex(beadY.value);
    const id = props.items[index]?.id ?? null;
    isDragging.value = false;
    pointerId = null;
    emit('drag', {
        isDragging: false,
        index,
        nearestId: id,
    });
    selectIndex(index);
}

function onKeydown(event: KeyboardEvent, index: number) {
    const lastIndex = props.items.length - 1;
    let nextIndex = index;

    if (event.key === 'ArrowUp') {
        nextIndex = Math.max(0, index - 1);
    } else if (event.key === 'ArrowDown') {
        nextIndex = Math.min(lastIndex, index + 1);
    } else if (event.key === 'Home') {
        nextIndex = 0;
    } else if (event.key === 'End') {
        nextIndex = lastIndex;
    } else if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        selectIndex(index);

        return;
    } else {
        return;
    }

    event.preventDefault();
    focusedIndex.value = nextIndex;
    tabRefs.value[nextIndex]?.focus();
}

function syncReducedMotion() {
    reducedMotion.value = Boolean(mediaReduce?.matches);
}

function onResize() {
    if (isDragging.value) {
        finishDrag();

        return;
    }

    syncToActive();
}

watch(
    () => props.activeId,
    () => {
        focusedIndex.value = activeIndex.value;
        syncToActive();
    },
);

onMounted(() => {
    mediaReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    syncReducedMotion();
    mediaReduce.addEventListener('change', syncReducedMotion);
    resizeObserver = new ResizeObserver(onResize);

    if (railRef.value) {
        resizeObserver.observe(railRef.value);
    }

    window.addEventListener('resize', onResize, { passive: true });
    syncToActive();
});

onBeforeUnmount(() => {
    if (raf) {
        window.cancelAnimationFrame(raf);
    }

    if (isDragging.value) {
        emit('drag', {
            isDragging: false,
            index: activeIndex.value,
            nearestId: props.activeId,
        });
    }

    mediaReduce?.removeEventListener('change', syncReducedMotion);
    resizeObserver?.disconnect();
    window.removeEventListener('resize', onResize);
    gsap.killTweensOf(beadRef.value);
});
</script>

<template>
    <nav
        ref="railRef"
        class="fixed top-1/2 right-2 z-40 -translate-y-1/2 rounded-full border border-white/20 bg-white/20 px-1.5 py-2 shadow-[0_24px_70px_rgba(0,0,0,0.28),inset_0_1px_0_rgba(255,255,255,0.36)] backdrop-blur-2xl backdrop-saturate-150 sm:right-4 sm:px-2 sm:py-3 dark:border-white/10 dark:bg-public-muted/60"
        aria-label="Portfolio sections"
        role="tablist"
        aria-orientation="vertical"
        @pointermove="moveDrag"
        @pointerup="finishDrag"
        @pointercancel="finishDrag"
    >
        <div
            class="pointer-events-none absolute inset-1 rounded-full bg-[linear-gradient(110deg,rgba(255,255,255,0.32),transparent_34%,rgba(255,255,255,0.12)_68%,transparent)]"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute right-1/2 h-15 w-12 translate-x-1/2 rounded-full bg-public-accent/20 blur-xl transition-[top] duration-300 motion-reduce:transition-none"
            :style="{ top: `${beadY - 30}px` }"
            aria-hidden="true"
        />
        <div
            ref="beadRef"
            class="absolute top-0 left-1/2 z-10 grid size-11 -translate-x-1/2 -translate-y-1/2 touch-none place-items-center rounded-full border border-white/35 bg-[radial-gradient(circle_at_32%_22%,rgba(255,255,255,0.86),rgba(255,255,255,0.24)_28%,transparent_48%),linear-gradient(145deg,var(--public-accent),color-mix(in_srgb,var(--public-accent)_55%,#ffffff_45%))] text-white shadow-[0_12px_34px_color-mix(in_srgb,var(--public-accent)_45%,transparent),inset_0_1px_0_rgba(255,255,255,0.55)] sm:size-12"
            aria-hidden="true"
            @pointerdown="startDrag"
        >
            <component
                :is="activeItem?.icon"
                class="size-5 drop-shadow"
                aria-hidden="true"
            />
        </div>

        <button
            v-for="(item, index) in items"
            :id="`portfolio-tab-${item.id}`"
            :key="item.id"
            :ref="(element) => setTabRef(element, index)"
            type="button"
            class="group relative z-20 grid size-11 place-items-center rounded-full text-public-strong/72 transition hover:text-public-strong focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none aria-selected:text-transparent sm:size-12 dark:text-white/72 dark:hover:text-white"
            :class="[
                previewId === item.id && item.id !== activeId
                    ? 'scale-[1.08] text-public-accent dark:text-public-accent'
                    : '',
                activeId === item.id ? 'pointer-events-none' : '',
            ]"
            role="tab"
            :aria-selected="activeId === item.id"
            :aria-controls="`portfolio-panel-${item.id}`"
            :aria-label="item.label"
            :tabindex="focusedIndex === index ? 0 : -1"
            :title="item.label"
            @click="selectIndex(index)"
            @keydown="onKeydown($event, index)"
            @focus="focusedIndex = index"
        >
            <component :is="item.icon" class="size-5" aria-hidden="true" />
            <span
                class="pointer-events-none absolute right-14 rounded-sm border border-white/15 bg-public-strong/90 px-2 py-1 text-xs font-semibold whitespace-nowrap text-public-bg opacity-0 shadow-xl backdrop-blur transition group-hover:opacity-100 group-focus-visible:opacity-100"
            >
                {{ item.label }}
            </span>
        </button>
    </nav>
</template>
