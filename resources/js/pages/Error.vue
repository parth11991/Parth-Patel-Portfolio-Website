<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { home } from '@/routes';

const props = defineProps<{
    status: number;
}>();

const title = computed(() => {
    if (props.status === 404) {
        return 'Page not found';
    }

    if (props.status === 403) {
        return 'Access denied';
    }

    return 'Page unavailable';
});

const message = computed(() => {
    if (props.status === 404) {
        return 'The page you requested does not exist.';
    }

    if (props.status === 403) {
        return 'You do not have access to this page.';
    }

    return 'The site could not load this page.';
});
</script>

<template>
    <Head :title="title">
        <meta head-key="description" name="description" :content="message" />
        <meta head-key="robots" name="robots" content="noindex" />
    </Head>

    <main
        class="grid min-h-screen place-items-center bg-public-bg px-4 py-12 text-public-body"
    >
        <section
            class="w-full max-w-xl space-y-6 rounded-md border border-public-line bg-public-muted p-6"
            aria-labelledby="error-title"
        >
            <p class="text-sm font-semibold text-public-accent">
                {{ status }}
            </p>
            <div class="space-y-3">
                <h1
                    id="error-title"
                    class="text-3xl font-semibold text-public-strong"
                >
                    {{ title }}
                </h1>
                <p class="text-base leading-7">
                    {{ message }}
                </p>
            </div>
            <Link
                :href="home.url()"
                class="inline-flex items-center rounded-md bg-public-strong px-4 py-2 text-sm font-medium text-public-bg focus-visible:ring-2 focus-visible:ring-public-accent focus-visible:outline-none"
            >
                Go home
            </Link>
        </section>
    </main>
</template>
