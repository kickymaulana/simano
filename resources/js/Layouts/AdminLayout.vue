<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps<{ title?: string }>();

const form = useForm({});
const logout = () => form.post(route('logout'));
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <div class="flex min-h-screen">
            <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:block">
                <div class="sticky top-0 flex h-screen flex-col p-5">
                    <Link :href="route('home')" class="mb-8 text-xl font-bold text-blue-700">SIMANO Admin</Link>
                    <nav class="space-y-1">
                        <Link :href="route('admin.dashboard')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Dashboard</Link>
                        <Link :href="route('admin.pending-users.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Pending user</Link>
                        <Link :href="route('admin.evaluation-periods.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Periode evaluasi</Link>
                        <Link :href="route('admin.evaluation-templates.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Template evaluasi</Link>
                        <Link :href="route('admin.reports.evaluations')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Rekap evaluasi</Link>
                        <Link :href="route('admin.audit-logs.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Audit log</Link>
                        <slot name="navigation" />
                    </nav>
                    <div class="mt-auto text-xs text-slate-500">Panel administrasi</div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-6">
                    <div class="flex items-center justify-between lg:max-w-none">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-blue-600">Admin/HR</p>
                            <h1 class="text-lg font-bold sm:text-xl">{{ title ?? 'Dashboard' }}</h1>
                        </div>
                        <slot name="header-actions" />
                        <form @submit.prevent="logout">
                            <button type="submit" :disabled="form.processing" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Logout</button>
                        </form>
                    </div>
                </header>
                <main class="p-4 sm:p-6 lg:p-8">
                    <div class="mx-auto max-w-7xl">
                        <slot />
                    </div>
                </main>
            </div>
        </div>

        <nav class="fixed inset-x-0 bottom-0 z-10 flex border-t border-slate-200 bg-white px-2 py-2 lg:hidden">
            <div class="flex w-full justify-around">
                <slot name="mobile-navigation" />
            </div>
        </nav>
    </div>
</template>
