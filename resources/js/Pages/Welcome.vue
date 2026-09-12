<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card as VarCard } from '@varlet/ui';
import '@varlet/ui/es/card/style/index';
import EmployeeLayout from '../Layouts/EmployeeLayout.vue';

const page = usePage<{ auth: { user: unknown } }>();
</script>

<template>
    <Head title="Beranda" />

    <EmployeeLayout title="Evaluasi kinerja lebih mudah.">
        <template #header-actions>
            <Link v-if="page.props.auth.user" :href="route('dashboard')" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Dashboard</Link>
            <a v-else :href="route('sso.login')" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Login dengan SSO</a>
        </template>

        <template #subtitle>
            <p class="mt-2 text-slate-600">Sistem penilaian bulanan yang terintegrasi, terukur, dan menjaga kerahasiaan penilai.</p>
        </template>

        <section class="space-y-4">
            <div class="rounded-2xl bg-blue-700 p-5 text-white shadow-sm sm:p-6">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-200">Periode berjalan</p>
                <p class="mt-2 text-xl font-semibold">Belum ada evaluasi bulan ini</p>
                <p class="mt-1 text-sm text-blue-100">Pilih atasan dan isi penilaian saat periode aktif.</p>
                <Link v-if="page.props.auth.user" :href="route('dashboard')" class="mt-4 inline-block rounded-lg bg-white px-4 py-2 font-semibold text-blue-700">Kerjakan evaluasi →</Link>
                <a v-else :href="route('sso.login')" class="mt-4 inline-block rounded-lg bg-white px-4 py-2 font-semibold text-blue-700">Login dengan SSO</a>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <var-card title="Aman dan anonim" description="Identitas penilai tidak tampil pada rekap." />
                <var-card title="Cepat dan terukur" description="14 pertanyaan dengan skala nilai 1–5." />
            </div>
        </section>
    </EmployeeLayout>
</template>

