<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { PublicProfile } from '@/types/public-home';
import EmptyState from './EmptyState.vue';
import SectionShell from './SectionShell.vue';

const props = withDefaults(
    defineProps<{
        profile?: PublicProfile | null;
    }>(),
    {
        profile: null,
    },
);

const sectionRef = ref<HTMLElement | null>(null);
const isVisible = ref(false);
const isReducedMotion = ref(false);
const hasPlayed = ref(false);
const typedQuestionLength = ref(0);
const typedAnswerLength = ref(0);
const showCursor = ref(false);

let observer: IntersectionObserver | null = null;
let reducedMotionQuery: MediaQueryList | null = null;
let typingTimer = 0;
let cursorTimer = 0;

const aboutQuestion = computed(
    () => props.profile?.aboutQuestion?.trim() ?? '',
);
const aboutAnswer = computed(() => props.profile?.aboutAnswer?.trim() ?? '');
const aboutText = computed(() => props.profile?.aboutText?.trim() ?? '');
const hasChatContent = computed(
    () => Boolean(aboutQuestion.value) || Boolean(aboutAnswer.value),
);
const stats = computed(() => {
    const values: Array<{ label: string; value: string }> = [];

    if (
        props.profile?.yearsExperience !== null &&
        props.profile?.yearsExperience !== undefined
    ) {
        values.push({
            label: 'Experience',
            value: `${props.profile.yearsExperience}+ years`,
        });
    }

    if (props.profile?.location) {
        values.push({ label: 'Location', value: props.profile.location });
    }

    if (props.profile?.availabilityText) {
        values.push({
            label: 'Availability',
            value: props.profile.availabilityText,
        });
    }

    return values;
});
const hasProfileContent = computed(
    () =>
        Boolean(props.profile) &&
        (Boolean(aboutText.value) ||
            hasChatContent.value ||
            stats.value.length > 0),
);
const typedQuestion = computed(() =>
    aboutQuestion.value.slice(0, typedQuestionLength.value),
);
const typedAnswer = computed(() =>
    aboutAnswer.value.slice(0, typedAnswerLength.value),
);
const isTyping = computed(
    () =>
        hasChatContent.value &&
        !isReducedMotion.value &&
        (typedQuestionLength.value < aboutQuestion.value.length ||
            typedAnswerLength.value < aboutAnswer.value.length),
);

function clearTypingTimer() {
    window.clearTimeout(typingTimer);
    typingTimer = 0;
}

function clearCursorTimer() {
    window.clearInterval(cursorTimer);
    cursorTimer = 0;
    showCursor.value = false;
}

function completeChat() {
    clearTypingTimer();
    typedQuestionLength.value = aboutQuestion.value.length;
    typedAnswerLength.value = aboutAnswer.value.length;
    clearCursorTimer();
}

function resetChat() {
    clearTypingTimer();
    typedQuestionLength.value = 0;
    typedAnswerLength.value = 0;
    showCursor.value = false;
}

function syncReducedMotion() {
    isReducedMotion.value = Boolean(reducedMotionQuery?.matches);

    if (isReducedMotion.value) {
        hasPlayed.value = true;
        completeChat();
    }
}

function ensureCursor() {
    if (cursorTimer || isReducedMotion.value) {
        return;
    }

    showCursor.value = true;
    cursorTimer = window.setInterval(() => {
        showCursor.value = !showCursor.value;
    }, 520);
}

function startTyping() {
    clearTypingTimer();

    if (!hasChatContent.value || hasPlayed.value || !isVisible.value) {
        return;
    }

    if (isReducedMotion.value) {
        hasPlayed.value = true;
        completeChat();

        return;
    }

    hasPlayed.value = true;
    resetChat();
    ensureCursor();

    const step = () => {
        if (typedQuestionLength.value < aboutQuestion.value.length) {
            typedQuestionLength.value += 1;
            typingTimer = window.setTimeout(
                step,
                aboutQuestion.value[typedQuestionLength.value - 1] === '\n'
                    ? 180
                    : 34,
            );

            return;
        }

        if (typedAnswerLength.value < aboutAnswer.value.length) {
            typedAnswerLength.value += 1;
            typingTimer = window.setTimeout(
                step,
                aboutAnswer.value[typedAnswerLength.value - 1] === '\n'
                    ? 190
                    : 22,
            );

            return;
        }

        clearTypingTimer();
        clearCursorTimer();
    };

    typingTimer = window.setTimeout(step, 240);
}

watch([aboutQuestion, aboutAnswer], () => {
    hasPlayed.value = false;

    if (isReducedMotion.value) {
        completeChat();

        return;
    }

    resetChat();
    startTyping();
});

onMounted(() => {
    reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    syncReducedMotion();
    reducedMotionQuery.addEventListener('change', syncReducedMotion);

    observer = new IntersectionObserver(
        ([entry]) => {
            isVisible.value = Boolean(entry?.isIntersecting);
            startTyping();
        },
        { threshold: 0.25 },
    );

    if (sectionRef.value) {
        observer.observe(sectionRef.value);
    }
});

onBeforeUnmount(() => {
    observer?.disconnect();
    reducedMotionQuery?.removeEventListener('change', syncReducedMotion);
    clearTypingTimer();
    clearCursorTimer();
});
</script>

<template>
    <SectionShell id="about" eyebrow="About" title="How I build">
        <div
            v-if="hasProfileContent"
            ref="sectionRef"
            class="space-y-6 text-base leading-8 text-public-body"
        >
            <p
                v-if="aboutText"
                class="max-w-3xl text-lg leading-9 whitespace-pre-line"
            >
                {{ aboutText }}
            </p>

            <div
                v-if="hasChatContent"
                class="space-y-4 rounded-md border border-public-line bg-public-muted/70 p-4 shadow-2xl shadow-black/10 backdrop-blur sm:p-5"
            >
                <div class="sr-only">
                    <p v-if="aboutQuestion">{{ aboutQuestion }}</p>
                    <p v-if="aboutAnswer">{{ aboutAnswer }}</p>
                </div>

                <div aria-hidden="true" class="space-y-3">
                    <p
                        v-if="aboutQuestion"
                        class="ml-auto max-w-[88%] rounded-md border border-public-line bg-public-bg/80 px-4 py-3 text-sm leading-6 font-medium text-public-strong shadow-sm"
                    >
                        {{ typedQuestion }}
                        <span
                            v-if="
                                isTyping &&
                                typedQuestionLength < aboutQuestion.length &&
                                showCursor
                            "
                            class="text-public-accent"
                            >|</span
                        >
                    </p>

                    <p
                        v-if="aboutAnswer"
                        class="max-w-[94%] rounded-md border border-public-line bg-public-bg/80 px-4 py-3 leading-8 whitespace-pre-line text-public-body shadow-sm"
                    >
                        {{ typedAnswer }}
                        <span
                            v-if="
                                isTyping &&
                                typedQuestionLength >= aboutQuestion.length &&
                                typedAnswerLength < aboutAnswer.length &&
                                showCursor
                            "
                            class="text-public-accent"
                            >|</span
                        >
                    </p>
                </div>

                <button
                    v-if="isTyping"
                    type="button"
                    class="inline-flex items-center rounded-md border border-public-line px-3 py-1.5 text-sm font-medium text-public-strong transition hover:border-public-accent focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    @click="completeChat"
                >
                    Skip animation
                </button>
            </div>

            <dl v-if="stats.length" class="grid gap-3 sm:grid-cols-3">
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="rounded-md border border-public-line bg-public-muted/60 p-4"
                >
                    <dt
                        class="text-xs font-semibold tracking-[0.16em] text-public-subtle uppercase"
                    >
                        {{ stat.label }}
                    </dt>
                    <dd class="mt-2 text-base font-semibold text-public-strong">
                        {{ stat.value }}
                    </dd>
                </div>
            </dl>
        </div>
        <EmptyState v-else label="About content not published yet." />
    </SectionShell>
</template>
