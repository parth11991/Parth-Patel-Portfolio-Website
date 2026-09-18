<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Send } from 'lucide-vue-next';
import { computed } from 'vue';
import { store } from '@/routes/contact';
import type { PublicProfile, SocialLink } from '@/types/public-home';
import EmptyState from './EmptyState.vue';
import SectionShell from './SectionShell.vue';

withDefaults(
    defineProps<{
        profile?: PublicProfile | null;
        socialLinks?: SocialLink[];
    }>(),
    {
        profile: null,
        socialLinks: () => [],
    },
);

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
    company: '',
});

const fieldClass =
    'w-full rounded-md border border-public-line bg-public-bg px-3 py-2 text-sm text-public-strong outline-none transition focus:border-public-accent focus:ring-2 focus:ring-public-accent/25 disabled:cursor-not-allowed disabled:opacity-60';

const errorClass = 'text-sm text-red-600 dark:text-red-400';
const contactError = computed(
    () => (form.errors as Record<string, string>).contact,
);

function submit(): void {
    if (form.processing) {
        return;
    }

    form.clearErrors();

    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <SectionShell id="contact" eyebrow="Contact" title="Start a conversation">
        <div class="grid gap-8 lg:grid-cols-[0.42fr_0.58fr]">
            <div v-if="profile?.publicEmail" class="space-y-5">
                <p class="max-w-2xl text-base leading-8 text-public-body">
                    {{
                        profile.availabilityText ??
                        'Open to focused product work, Laravel systems, and Vue interfaces.'
                    }}
                </p>
                <div class="flex flex-wrap gap-3">
                    <a
                        :href="`mailto:${profile.publicEmail}`"
                        class="rounded-md bg-public-strong px-4 py-2 text-sm font-medium text-public-bg focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    >
                        {{ profile.publicEmail }}
                    </a>
                    <a
                        v-if="profile.publicPhone"
                        :href="`tel:${profile.publicPhone}`"
                        class="rounded-md border border-public-line px-4 py-2 text-sm font-medium text-public-strong focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    >
                        {{ profile.publicPhone }}
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
                        target="_blank"
                        rel="noreferrer"
                        class="rounded-md border border-public-line bg-public-muted/65 px-3 py-2 text-sm font-medium text-public-subtle transition hover:border-public-accent hover:text-public-strong focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
                    >
                        {{ link.label }}
                    </a>
                </div>
            </div>
            <EmptyState v-else label="Contact details not published yet." />

            <form
                class="space-y-4 rounded-md border border-public-line bg-public-muted/65 p-4 shadow-2xl shadow-black/10 backdrop-blur sm:p-5"
                novalidate
                @submit.prevent="submit"
            >
                <input
                    v-model="form.company"
                    name="company"
                    type="text"
                    tabindex="-1"
                    autocomplete="off"
                    class="hidden"
                    aria-hidden="true"
                />

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-public-strong"
                            >Name</span
                        >
                        <input
                            v-model="form.name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            :class="fieldClass"
                            :disabled="form.processing"
                            :aria-invalid="Boolean(form.errors.name)"
                            aria-describedby="contact-name-error"
                        />
                        <span
                            v-if="form.errors.name"
                            id="contact-name-error"
                            :class="errorClass"
                        >
                            {{ form.errors.name }}
                        </span>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-public-strong"
                            >Email</span
                        >
                        <input
                            v-model="form.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            :class="fieldClass"
                            :disabled="form.processing"
                            :aria-invalid="Boolean(form.errors.email)"
                            aria-describedby="contact-email-error"
                        />
                        <span
                            v-if="form.errors.email"
                            id="contact-email-error"
                            :class="errorClass"
                        >
                            {{ form.errors.email }}
                        </span>
                    </label>
                </div>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-public-strong"
                        >Subject</span
                    >
                    <input
                        v-model="form.subject"
                        name="subject"
                        type="text"
                        :class="fieldClass"
                        :disabled="form.processing"
                        :aria-invalid="Boolean(form.errors.subject)"
                        aria-describedby="contact-subject-error"
                    />
                    <span
                        v-if="form.errors.subject"
                        id="contact-subject-error"
                        :class="errorClass"
                    >
                        {{ form.errors.subject }}
                    </span>
                </label>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-public-strong"
                        >Message</span
                    >
                    <textarea
                        v-model="form.message"
                        name="message"
                        rows="6"
                        :class="fieldClass"
                        :disabled="form.processing"
                        :aria-invalid="Boolean(form.errors.message)"
                        aria-describedby="contact-message-error"
                    />
                    <span
                        v-if="form.errors.message"
                        id="contact-message-error"
                        :class="errorClass"
                    >
                        {{ form.errors.message }}
                    </span>
                </label>

                <p
                    v-if="form.wasSuccessful"
                    class="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200"
                    role="status"
                >
                    Message sent.
                </p>
                <p
                    v-if="contactError"
                    class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-700 dark:bg-red-950/40 dark:text-red-200"
                    role="alert"
                >
                    {{ contactError }}
                </p>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-md bg-public-strong px-4 py-2 text-sm font-medium text-public-bg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <Send class="size-4" aria-hidden="true" />
                    <span>{{
                        form.processing ? 'Sending...' : 'Send message'
                    }}</span>
                </button>
            </form>
        </div>
    </SectionShell>
</template>
