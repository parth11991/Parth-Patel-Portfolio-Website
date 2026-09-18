import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

export type SectionId =
    | 'home'
    | 'about'
    | 'skills'
    | 'experience'
    | 'education'
    | 'projects'
    | 'contact';
const aliases: Record<string, SectionId> = {
    top: 'home',
    home: 'home',
    about: 'about',
    skills: 'skills',
    work: 'experience',
    experience: 'experience',
    study: 'education',
    education: 'education',
    portfolio: 'projects',
    projects: 'projects',
    contact: 'contact',
};

export function usePortfolioScroll(sections: { id: SectionId }[]) {
    const activeSection = ref<SectionId>('home');
    const contentViewportRef = ref<HTMLElement | null>(null);
    const contentTrackRef = ref<HTMLElement | null>(null);
    const scrollY = ref(0);
    const isSectionDragging = ref(false);
    const animate = ref(false);
    const reducedMotion = ref(false);
    let offsets: number[] = [];
    let maxScroll = 0;
    let height = 0;
    let mounted = false;
    let measured = false;
    let timer = 0;
    let touch: { x: number; y: number } | null = null;
    let previousBodyOverflow = '';
    let previousHtmlOverflow = '';
    let observer: ResizeObserver | undefined;
    let motion: MediaQueryList | undefined;

    const trackStyle = computed(() => ({
        transform: `translate3d(0, ${-scrollY.value}px, 0)`,
        transitionDuration:
            animate.value && !isSectionDragging.value && !reducedMotion.value
                ? '500ms'
                : '0ms',
    }));
    const sectionFromHash = (hash: string): SectionId =>
        aliases[hash.replace(/^#/, '').toLowerCase()] ?? 'home';
    const clamp = (value: number) => Math.min(maxScroll, Math.max(0, value));
    const offsetFor = (id: SectionId) =>
        offsets[sections.findIndex((section) => section.id === id)] ?? 0;

    function updateHash(id: SectionId, replace = false) {
        const url = `${window.location.pathname}${window.location.search}${id === 'home' ? '' : `#${id}`}`;

        if (
            url ===
            `${window.location.pathname}${window.location.search}${window.location.hash}`
        ) {
            return;
        }

        const state = { ...window.history.state };

        // Preserve Inertia's page/scroll state instead of replacing it with a section-only object.
        if (state.page && typeof state.page === 'object') {
            state.page = { ...state.page, url };
        }

        window.history[replace ? 'replaceState' : 'pushState'](state, '', url);
    }

    function moveTo(value: number, animated = false) {
        window.clearTimeout(timer);
        animate.value = animated;
        scrollY.value = clamp(value);

        if (animated) {
            timer = window.setTimeout(() => {
                animate.value = false;
            }, 520);
        }
    }

    function chooseSection(id: SectionId, pushHistory = true) {
        activeSection.value = id;
        moveTo(offsetFor(id), true);

        if (pushHistory) {
            updateHash(id);
        }
    }

    function syncActive() {
        // Track the panel at the viewport's leading edge, not the nearest panel midpoint.
        let index = 0;
        offsets.forEach((offset, next) => {
            if (offset <= scrollY.value + 1) {
                index = next;
            }
        });
        activeSection.value = sections[index]?.id ?? 'home';
        updateHash(activeSection.value, true);
    }

    function scrollBy(delta: number) {
        moveTo(scrollY.value + delta);
        syncActive();
    }

    function nativeTarget(target: EventTarget | null): boolean {
        return (
            target instanceof Element &&
            Boolean(
                target.closest(
                    'input, textarea, select, [contenteditable="true"], [role="slider"], [data-native-gesture]',
                ),
            )
        );
    }

    function nestedScroll(target: EventTarget | null, delta: number): boolean {
        let element = target instanceof Element ? target : null;

        while (element && element !== contentViewportRef.value) {
            if (
                element instanceof HTMLElement &&
                /auto|scroll/.test(getComputedStyle(element).overflowY)
            ) {
                if (
                    delta < 0
                        ? element.scrollTop > 0
                        : element.scrollTop + element.clientHeight <
                          element.scrollHeight - 1
                ) {
                    return true;
                }
            }

            element = element.parentElement;
        }

        return false;
    }

    function onContentWheel(event: WheelEvent) {
        if (
            event.ctrlKey ||
            event.shiftKey ||
            Math.abs(event.deltaX) > Math.abs(event.deltaY) ||
            nativeTarget(event.target) ||
            isSectionDragging.value
        ) {
            return;
        }

        const unit =
            event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? height : 1;
        const delta = event.deltaY * unit;

        if (nestedScroll(event.target, delta)) {
            return;
        }

        event.preventDefault();
        scrollBy(delta);
    }

    function onContentTouchStart(event: TouchEvent) {
        const point = event.touches.length === 1 ? event.touches[0] : null;
        touch =
            point && !nativeTarget(event.target)
                ? { x: point.clientX, y: point.clientY }
                : null;
    }

    function onContentTouchMove(event: TouchEvent) {
        if (!touch || event.touches.length !== 1 || isSectionDragging.value) {
            return;
        }

        const point = event.touches[0];
        const delta = touch.y - point.clientY;
        const horizontal = touch.x - point.clientX;
        touch = { x: point.clientX, y: point.clientY };

        if (
            Math.abs(horizontal) > Math.abs(delta) ||
            nestedScroll(event.target, delta) ||
            window.getSelection()?.toString()
        ) {
            return;
        }

        event.preventDefault();
        scrollBy(delta);
    }

    function onContentKeydown(event: KeyboardEvent) {
        if (
            event.defaultPrevented ||
            event.altKey ||
            event.ctrlKey ||
            event.metaKey ||
            nativeTarget(event.target) ||
            window.getSelection()?.toString()
        ) {
            return;
        }

        if (
            event.target instanceof Element &&
            event.target.closest('button, a, [role="tab"], [role="button"]')
        ) {
            return;
        }

        const page = Math.max(40, height * 0.85);
        const deltas: Record<string, number> = {
            ArrowDown: 40,
            ArrowUp: -40,
            PageDown: page,
            PageUp: -page,
            ' ': event.shiftKey ? -page : page,
            Home: -maxScroll,
            End: maxScroll,
        };
        const delta = deltas[event.key];

        if (delta === undefined || nestedScroll(event.target, delta)) {
            return;
        }

        event.preventDefault();
        scrollBy(delta);
    }

    function onContentFocus(event: FocusEvent) {
        const viewport = contentViewportRef.value;
        const target = event.target;

        if (
            !viewport ||
            !(target instanceof HTMLElement) ||
            target === viewport
        ) {
            return;
        }

        // Native focus scrolling must not stack with the translated track.
        viewport.scrollTop = 0;
        const bounds = viewport.getBoundingClientRect();
        const element = target.getBoundingClientRect();

        if (element.top < bounds.top + 12) {
            moveTo(scrollY.value + element.top - bounds.top - 12);
        } else if (element.bottom > bounds.bottom - 12) {
            moveTo(scrollY.value + element.bottom - bounds.bottom + 12);
        }

        syncActive();
    }

    function syncMeasurements() {
        const viewport = contentViewportRef.value;
        const track = contentTrackRef.value;

        if (!mounted || !viewport || !track) {
            return;
        }

        const relative = scrollY.value - offsetFor(activeSection.value);
        height = viewport.clientHeight;
        maxScroll = Math.max(0, track.scrollHeight - height);
        offsets = sections.map(({ id }) => {
            const panel = document.getElementById(`portfolio-panel-${id}`);

            return panel
                ? clamp(
                      panel.getBoundingClientRect().top -
                          track.getBoundingClientRect().top,
                  )
                : 0;
        });
        moveTo(offsetFor(activeSection.value) + (measured ? relative : 0));
        measured = true;
    }

    function onHistory() {
        chooseSection(sectionFromHash(window.location.hash), false);
    }

    function onNavDrag(payload: {
        isDragging: boolean;
        index: number;
        nearestId: string | null;
    }) {
        isSectionDragging.value = payload.isDragging;
        const index = Math.max(0, Math.min(sections.length - 1, payload.index));
        const lower = offsets[Math.floor(index)] ?? 0;
        const upper = offsets[Math.ceil(index)] ?? lower;
        moveTo(lower + (upper - lower) * (index - Math.floor(index)));

        if (!payload.isDragging && payload.nearestId) {
            chooseSection(sectionFromHash(payload.nearestId));
        }
    }

    function syncMotion() {
        reducedMotion.value = Boolean(motion?.matches);
    }
    function skipToContent() {
        contentViewportRef.value?.focus({ preventScroll: true });
    }

    onMounted(() => {
        mounted = true;
        activeSection.value = sectionFromHash(window.location.hash);
        previousBodyOverflow = document.body.style.overflow;
        previousHtmlOverflow = document.documentElement.style.overflow;
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        window.addEventListener('popstate', onHistory);
        window.addEventListener('hashchange', onHistory);
        motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        motion.addEventListener('change', syncMotion);
        syncMotion();
        observer = new ResizeObserver(syncMeasurements);

        if (contentViewportRef.value) {
            observer.observe(contentViewportRef.value);
        }

        if (contentTrackRef.value) {
            observer.observe(contentTrackRef.value);
        }

        void nextTick(syncMeasurements);
    });

    onBeforeUnmount(() => {
        mounted = false;
        window.clearTimeout(timer);
        observer?.disconnect();
        motion?.removeEventListener('change', syncMotion);
        window.removeEventListener('popstate', onHistory);
        window.removeEventListener('hashchange', onHistory);
        document.body.style.overflow = previousBodyOverflow;
        document.documentElement.style.overflow = previousHtmlOverflow;
    });

    return {
        activeSection,
        contentViewportRef,
        contentTrackRef,
        isSectionDragging,
        trackStyle,
        chooseSection,
        onNavDrag,
        onContentWheel,
        onContentTouchStart,
        onContentTouchMove,
        onContentKeydown,
        onContentFocus,
        skipToContent,
        chooseFromNav: (id: string) => chooseSection(sectionFromHash(id)),
    };
}
