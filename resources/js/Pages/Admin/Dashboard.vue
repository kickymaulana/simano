<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout title="Dashboard">
        <p class="text-slate-600">Rekap evaluasi tampil di sini.</p>
        <div class="mt-5 flex flex-wrap gap-3"><Link :href="route('admin.reports.evaluations')" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Buka rekap evaluasi</Link><Link :href="route('admin.audit-logs.index')" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold">Audit log</Link></div>
    </AdminLayout>
</template>
