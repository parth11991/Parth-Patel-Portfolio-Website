<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard, home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/filament/admin/pages';
import { edit as profile } from '@/routes/profile';
import { edit as security } from '@/routes/security';

const page = usePage();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});
const links = [
    {
        title: 'View portfolio',
        description: 'Visit the public website.',
        href: home(),
    },
    {
        title: 'Profile settings',
        description: 'Update your account details.',
        href: profile(),
    },
    {
        title: 'Security settings',
        description: 'Manage your password.',
        href: security(),
    },
];
</script>

<template>
    <Head title="Dashboard" />
    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <p class="mt-2 text-muted-foreground">
                Manage your account or visit your portfolio.
            </p>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <Link
                v-for="link in links"
                :key="link.title"
                :href="link.href"
                class="rounded-xl border p-5 transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring"
            >
                <h2 class="font-semibold">{{ link.title }}</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ link.description }}
                </p>
            </Link>
            <a
                v-if="page.props.auth.user?.is_admin"
                :href="adminDashboard.url()"
                class="rounded-xl border p-5 transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring"
            >
                <h2 class="font-semibold">Content administration</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Manage website content and contact messages.
                </p>
            </a>
        </div>
    </div>
</template>
