<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import EmployeeLayout from '../Layouts/EmployeeLayout.vue';
</script>

<template>
    <Head title="Dashboard" />
    <EmployeeLayout title="Dashboard">
        <p class="text-slate-600">Dashboard penilaian tersedia saat periode aktif.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <Link :href="route('targets.index')" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Pilih target evaluasi</Link>
            <Link :href="route('evaluations.status')" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold">Status evaluasi</Link>
        </div>
    </EmployeeLayout>
</template>
