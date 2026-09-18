<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

defineProps<{ title?: string }>();

const page = usePage<{ auth: { user: { name: string } | null; roles: string[] } }>();
const canAccessAdmin = computed(() => (page.props.auth.roles ?? []).some((r) => r === 'admin' || r === 'hr'));

const form = useForm({});
const logout = () => form.post(route('logout'));
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-6">
            <div class="mx-auto flex max-w-2xl items-center justify-between">
                <Link :href="route('home')" class="text-lg font-bold text-blue-700">SIMANO</Link>
                <div class="flex items-center gap-2">
                    <span v-if="page.props.auth.user" class="max-w-24 truncate text-sm font-medium text-slate-700">{{ page.props.auth.user.name }}</span>
                    <Link v-if="page.props.auth.user" :href="route('profile.edit')" class="hidden rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 sm:inline-flex">Profil</Link>
                    <Link v-if="canAccessAdmin" :href="route('admin.dashboard')" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Panel Admin</Link>
                    <form v-if="page.props.auth.user" @submit.prevent="logout">
                        <button type="submit" :disabled="form.processing" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-2xl px-4 py-5 pb-24 sm:px-6 sm:py-8 lg:pb-8">
            <div v-if="title" class="mb-5">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ title }}</h1>
                <slot name="subtitle" />
            </div>
            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-10 border-t border-slate-200 bg-white px-4 py-2 safe-area-pb sm:px-6 lg:hidden">
            <div class="mx-auto flex max-w-2xl items-center justify-around">
                <Link :href="route('home')" class="flex min-h-11 min-w-16 flex-col items-center justify-center text-xs text-slate-600">
                    <span class="text-lg">⌂</span>
                    Beranda
                </Link>
                <slot name="navigation" />
                <Link v-if="page.props.auth.user" :href="route('profile.edit')" class="flex min-h-11 min-w-16 flex-col items-center justify-center text-xs text-slate-600">
                    <span class="text-lg">●</span>
                    Profil
                </Link>
            </div>
        </nav>
    </div>
</template>
