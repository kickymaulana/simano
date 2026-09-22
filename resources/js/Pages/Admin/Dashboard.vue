<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Period = { id: number; month: number; year: number; status: string; closes_at: string | null };
type Stats = { evaluation_count: number; evaluated_target_count: number; unevaluated_target_count: number; participation_percentage: number; evaluator_count: number; active_user_count: number; average_score: number; pending_user_count: number; target_attention_count: number; question_attention_count: number };
defineProps<{ period: Period | null; stats: Stats }>();
</script>

<template>
    <Head title="Dashboard HR" />
    <AdminLayout title="Dashboard HR">
        <p class="text-slate-600">{{ period ? `Periode aktif ${period.month}/${period.year}` : 'Belum ada periode aktif' }}</p>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Evaluasi masuk</p><strong class="mt-2 block text-3xl">{{ stats.evaluation_count }}</strong></div>
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Karyawan dinilai</p><strong class="mt-2 block text-3xl">{{ stats.evaluated_target_count }}</strong></div>
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Belum dinilai</p><strong class="mt-2 block text-3xl">{{ stats.unevaluated_target_count }}</strong></div>
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Partisipasi</p><strong class="mt-2 block text-3xl">{{ stats.participation_percentage.toFixed(1) }}%</strong></div>
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Evaluator aktif</p><strong class="mt-2 block text-3xl">{{ stats.evaluator_count }}</strong></div>
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">User aktif</p><strong class="mt-2 block text-3xl">{{ stats.active_user_count }}</strong></div>
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Rata-rata skor</p><strong class="mt-2 block text-3xl">{{ stats.average_score.toFixed(2) }}</strong></div>
            <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Pending user</p><strong class="mt-2 block text-3xl">{{ stats.pending_user_count }}</strong></div>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <Link :href="route('admin.attention.index')" class="rounded-xl border border-amber-200 bg-amber-50 p-5"><p class="text-sm text-amber-800">Target perlu perhatian (&lt;80% nilai 4+5)</p><strong class="mt-2 block text-3xl text-amber-900">{{ stats.target_attention_count }}</strong></Link>
            <Link :href="route('admin.question-analysis.index')" class="rounded-xl border border-amber-200 bg-amber-50 p-5"><p class="text-sm text-amber-800">Item pertanyaan perlu perhatian (&lt;80% nilai 4+5)</p><strong class="mt-2 block text-3xl text-amber-900">{{ stats.question_attention_count }}</strong></Link>
        </div>
        <div class="mt-5 flex flex-wrap gap-3"><Link :href="route('admin.reports.evaluations')" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Buka rekap evaluasi</Link><Link :href="route('admin.evaluation-participation.index')" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold">Partisipasi evaluasi</Link></div>
    </AdminLayout>
</template>
