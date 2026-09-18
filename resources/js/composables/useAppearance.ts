import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };
export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const appearance = ref<Appearance>('dark');
const systemDark = ref(false);
let cleanup: (() => void) | undefined;

function isAppearance(value: unknown): value is Appearance {
    return value === 'light' || value === 'dark' || value === 'system';
}

function storedAppearance(): Appearance {
    try {
        const value = localStorage.getItem('appearance');

        if (isAppearance(value)) {
            return value;
        }
    } catch {
        // Storage can be disabled by browser privacy settings.
    }

    const cookie = document.cookie
        .split('; ')
        .find((part) => part.startsWith('appearance='))
        ?.split('=')[1];

    return isAppearance(cookie) ? cookie : 'dark';
}

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle(
        'dark',
        value === 'dark' || (value === 'system' && systemDark.value),
    );
}

export function initializeTheme(): void {
    if (typeof window === 'undefined' || cleanup) {
        return;
    }

    const query = window.matchMedia('(prefers-color-scheme: dark)');
    const sync = () => {
        systemDark.value = query.matches;
        updateTheme(appearance.value);
    };
    const onStorage = (event: StorageEvent) => {
        if (event.key !== 'appearance' && event.key !== null) {
            return;
        }

        appearance.value = storedAppearance();
        sync();
    };
    appearance.value = storedAppearance();
    sync();
    query.addEventListener('change', sync);
    window.addEventListener('storage', onStorage);
    cleanup = () => {
        query.removeEventListener('change', sync);
        window.removeEventListener('storage', onStorage);
        cleanup = undefined;
    };
}

export function useAppearance(): UseAppearanceReturn {
    initializeTheme();
    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        appearance.value === 'system'
            ? systemDark.value
                ? 'dark'
                : 'light'
            : appearance.value,
    );
    function updateAppearance(value: Appearance): void {
        appearance.value = value;

        if (typeof window === 'undefined') {
            return;
        }

        try {
            localStorage.setItem('appearance', value);
        } catch {
            // The in-memory preference still works without local storage.
        }

        document.cookie = `appearance=${value};path=/;max-age=31536000;SameSite=Lax`;
        updateTheme(value);
    }

    return { appearance, resolvedAppearance, updateAppearance };
}

if (import.meta.hot) {
    import.meta.hot.dispose(() => cleanup?.());
}
