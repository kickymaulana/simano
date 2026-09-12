<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Point = { period: { id: number; month: number; year: number }; target: { id: number; name: string; role: string }; category: string; evaluation_count: number; average_score: number };
const props = defineProps<{ categories: string[]; targets: { id: number; name: string }[]; filters: { category?: string; target?: number }; trend: Point[] }>();
const filters = { category: props.filters.category ?? '', target: props.filters.target ?? '' };
const apply = () => router.get(route('admin.reports.evaluations.trend'), filters, { preserveState: true, replace: true });
const label = (point: Point) => `${point.period.month}/${point.period.year}`;
</script>

<template>
    <Head title="Tren evaluasi" />
    <AdminLayout title="Tren evaluasi">
        <template #header-actions><Link :href="route('admin.reports.evaluations')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Rekap</Link></template>
        <div class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-sm font-semibold">Kategori<select v-model="filters.category" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua kategori</option><option v-for="category in categories" :key="category" :value="category">{{ category }}</option></select></label>
                <label class="text-sm font-semibold">Target<select v-model="filters.target" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua target</option><option v-for="target in targets" :key="target.id" :value="target.id">{{ target.name }}</option></select></label>
            </div>
            <button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="apply">Terapkan filter</button>
            <div v-if="!trend.length" class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-slate-600">Belum ada data tren.</div>
            <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full min-w-[650px] text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr><th class="p-4">Periode</th><th class="p-4">Target</th><th class="p-4">Kategori</th><th class="p-4">Jumlah</th><th class="p-4">Rata-rata</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(point, index) in trend" :key="`${point.period.id}-${point.target.id}-${point.category}`"><td class="p-4">{{ label(point) }}<small v-if="index && point.average_score > trend[index - 1].average_score" class="ml-2 text-green-700">Naik</small><small v-if="index && point.average_score < trend[index - 1].average_score" class="ml-2 text-red-700">Turun</small></td><td class="p-4 font-semibold">{{ point.target.name }}</td><td class="p-4">{{ point.category }}</td><td class="p-4">{{ point.evaluation_count }}</td><td class="p-4 font-semibold">{{ point.average_score.toFixed(2) }}</td></tr></tbody></table>
            </div>
        </div>
    </AdminLayout>
</template>
