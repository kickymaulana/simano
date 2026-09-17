<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

 defineProps<{ title?: string }>();

const page = usePage();
const reportPaths = ['/admin/reports/evaluations', '/admin/evaluation-participation', '/admin/question-analysis', '/admin/organization-scorecard', '/admin/attention', '/admin/audit-logs'];
const isReportPage = computed(() => reportPaths.some((path) => page.url.startsWith(path)));
const isReportsOpen = ref(isReportPage.value);
const isMobileMenuOpen = ref(false);
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
                        <Link :href="route('admin.users.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">User</Link>
                        <Link :href="route('admin.positions.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Jabatan</Link>
                        <Link :href="route('admin.factories.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Pabrik</Link>
                        <Link :href="route('admin.departments.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Departemen</Link>
                        <Link :href="route('admin.evaluation-periods.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Periode evaluasi</Link>
                        <Link :href="route('admin.evaluation-templates.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Template evaluasi</Link>
                        <div>
                            <button type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-100" @click="isReportsOpen = !isReportsOpen">
                                Laporan & Analisis
                                <span :class="isReportsOpen ? 'rotate-180' : ''" class="transition-transform">⌄</span>
                            </button>
                            <div v-if="isReportsOpen" class="ml-3 space-y-1 border-l border-slate-200 pl-2">
                                <Link :href="route('admin.reports.evaluations')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" :class="{ 'bg-blue-50 font-semibold text-blue-700': page.url === '/admin/reports/evaluations' }">Rekap evaluasi</Link>
                                <Link :href="route('admin.reports.evaluations.atasan')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" :class="{ 'bg-blue-50 font-semibold text-blue-700': page.url.startsWith('/admin/reports/evaluations/atasan') }">Laporan per karyawan</Link>
                                <Link :href="route('admin.evaluation-participation.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" :class="{ 'bg-blue-50 font-semibold text-blue-700': page.url.startsWith('/admin/evaluation-participation') }">Partisipasi evaluasi</Link>
                                <Link :href="route('admin.question-analysis.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" :class="{ 'bg-blue-50 font-semibold text-blue-700': page.url.startsWith('/admin/question-analysis') }">Analisis pertanyaan</Link>
                                <Link :href="route('admin.organization-scorecard.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" :class="{ 'bg-blue-50 font-semibold text-blue-700': page.url.startsWith('/admin/organization-scorecard') }">Scorecard organisasi</Link>
                                <Link :href="route('admin.attention.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" :class="{ 'bg-blue-50 font-semibold text-blue-700': page.url.startsWith('/admin/attention') }">Perlu perhatian</Link>
                                <Link :href="route('admin.audit-logs.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" :class="{ 'bg-blue-50 font-semibold text-blue-700': page.url.startsWith('/admin/audit-logs') }">Audit log</Link>
                            </div>
                        </div>
                        <slot name="navigation" />
                    </nav>
                    <div class="mt-auto text-xs text-slate-500">Panel administrasi</div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:px-6">
<div class="flex items-center justify-between lg:max-w-none">
                         <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-lg leading-none lg:hidden" aria-label="Buka menu" @click="isMobileMenuOpen = !isMobileMenuOpen">☰</button>
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
                 <div v-if="isMobileMenuOpen" class="border-b border-slate-200 bg-white p-4 lg:hidden">
                     <nav class="space-y-1">
                         <Link :href="route('admin.dashboard')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Dashboard</Link>
                         <Link :href="route('admin.pending-users.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Pending user</Link>
                         <Link :href="route('admin.users.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">User</Link>
                         <Link :href="route('admin.positions.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Jabatan</Link>
                         <Link :href="route('admin.factories.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Pabrik</Link>
                         <Link :href="route('admin.departments.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Departemen</Link>
                         <Link :href="route('admin.evaluation-periods.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Periode evaluasi</Link>
                         <Link :href="route('admin.evaluation-templates.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Template evaluasi</Link>
                         <div class="border-l border-slate-200 pl-2">
                             <p class="px-3 py-2 text-xs font-semibold uppercase tracking-widest text-slate-500">Laporan &amp; Analisis</p>
                             <Link :href="route('admin.reports.evaluations')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Rekap evaluasi</Link>
                             <Link :href="route('admin.reports.evaluations.atasan')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Laporan per karyawan</Link>
                             <Link :href="route('admin.evaluation-participation.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Partisipasi evaluasi</Link>
                             <Link :href="route('admin.question-analysis.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Analisis pertanyaan</Link>
                             <Link :href="route('admin.organization-scorecard.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Scorecard organisasi</Link>
                             <Link :href="route('admin.attention.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Perlu perhatian</Link>
                             <Link :href="route('admin.audit-logs.index')" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100" @click="isMobileMenuOpen = false">Audit log</Link>
                         </div>
                     </nav>
                 </div>
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
