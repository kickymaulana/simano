<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import EmployeeLayout from '../Layouts/EmployeeLayout.vue';

const page = usePage<{ auth: { user: unknown } }>();
</script>

<template>
    <Head title="Beranda" />

    <EmployeeLayout title="SIMANO">
        <template #header-actions>
            <Link v-if="page.props.auth.user" :href="route('dashboard')" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Dashboard</Link>
            <a v-else :href="route('sso.login')" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Login dengan SSO</a>
        </template>

        <section class="grid min-h-[60vh] place-items-center rounded-3xl bg-blue-700 p-8 text-center text-white shadow-sm">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-200">Sistem Penilaian Kinerja Atasan & Operator</p>
                <h1 class="mt-3 text-3xl font-bold sm:text-4xl">Rekap kinerja otomatis,<br />kerahasiaan penilai terjaga.</h1>
                <p class="mx-auto mt-3 max-w-xl text-blue-100">Ganti evaluasi manual yang sulit direkap. Isi penilaian dalam hitungan menit.</p>

                <Link v-if="page.props.auth.user" :href="route('dashboard')" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-lg font-bold text-blue-700 shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl">
                    Kerjakan evaluasi →<span class="sr-only">Evaluasi</span>
                </Link>
                <a v-else :href="route('sso.login')" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-lg font-bold text-blue-700 shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl">
                    Login & Kerjakan Evaluasi →
                </a>
            </div>
        </section>
    </EmployeeLayout>
</template>