<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Item = { id: number; name: string };
type Period = { id: number; month: number; year: number; status: string };
type Row = { target: { id: number; name: string; nik: string; position?: Item | null; departments: Item[]; factories: Item[] }; evaluation_count: number; average_score: number; score_4_5_percentage: number; previous_average_score: number | null };
type Pagination = { data: Row[]; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
const props = defineProps<{ periods: Period[]; selectedPeriod: Period | null; positions: Item[]; factories: Item[]; departments: Item[]; filters: { threshold: number; position_id?: number; factory_id?: number; department_id?: number }; rows: Pagination }>();
const filters = { period: props.selectedPeriod?.id, threshold: props.filters.threshold, position_id: props.filters.position_id ?? '', factory_id: props.filters.factory_id ?? '', department_id: props.filters.department_id ?? '' };
const apply = () => router.get(route('admin.attention.index'), filters, { preserveState: true, replace: true });
const trend = (row: Row) => row.previous_average_score === null ? 'Belum ada pembanding' : row.average_score < row.previous_average_score ? `Turun ${(row.previous_average_score - row.average_score).toFixed(2)}` : row.average_score > row.previous_average_score ? `Naik ${(row.average_score - row.previous_average_score).toFixed(2)}` : 'Tetap';
</script>

<template>
    <Head title="Perlu perhatian" />
    <AdminLayout title="Perlu perhatian">
        <div class="space-y-4">
            <p class="text-slate-600">Menampilkan karyawan dengan persentase jawaban nilai 4+5 di bawah target.</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <label class="text-sm font-semibold">Periode<select v-model="filters.period" class="mt-1 w-full rounded-lg border-slate-300"><option v-for="item in periods" :key="item.id" :value="item.id">{{ item.month }}/{{ item.year }} · {{ item.status }}</option></select></label>
                <label class="text-sm font-semibold">Target nilai 4+5 (%)<input v-model.number="filters.threshold" type="number" min="0" max="100" step="1" class="mt-1 w-full rounded-lg border-slate-300" /></label>
                <label class="text-sm font-semibold">Jabatan<select v-model="filters.position_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua jabatan</option><option v-for="item in positions" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                <label class="text-sm font-semibold">Pabrik<select v-model="filters.factory_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua pabrik</option><option v-for="item in factories" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                <label class="text-sm font-semibold">Departemen<select v-model="filters.department_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua departemen</option><option v-for="item in departments" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
            </div>
            <button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="apply">Terapkan filter</button>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr><th class="p-4">Karyawan</th><th class="p-4">Jabatan</th><th class="p-4">Departemen</th><th class="p-4">Pabrik</th><th class="p-4 text-right">Penilai</th><th class="p-4 text-right">Nilai 4+5</th><th class="p-4 text-right">Rata-rata</th><th class="p-4">Tren</th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><tr v-for="row in rows.data" :key="row.target.id"><td class="p-4"><Link :href="route('admin.reports.evaluations.atasan', { target: row.target.id, period: selectedPeriod?.id })" class="font-semibold text-blue-700 hover:underline">{{ row.target.name }}</Link><p class="text-slate-500">{{ row.target.nik }}</p></td><td class="p-4">{{ row.target.position?.name ?? '-' }}</td><td class="p-4">{{ row.target.departments.map((item) => item.name).join(', ') || '-' }}</td><td class="p-4">{{ row.target.factories.map((item) => item.name).join(', ') || '-' }}</td><td class="p-4 text-right">{{ row.evaluation_count }}</td><td class="p-4 text-right font-semibold text-amber-700">{{ row.score_4_5_percentage.toFixed(1) }}%</td><td class="p-4 text-right">{{ row.average_score.toFixed(2) }}</td><td class="p-4">{{ trend(row) }}</td></tr><tr v-if="!rows.data.length"><td colspan="8" class="p-6 text-center text-slate-500">Tidak ada karyawan di bawah target.</td></tr></tbody>
                </table>
            </div>
            <div v-if="rows.last_page > 1" class="flex flex-wrap gap-2"><Link v-for="link in rows.links" :key="link.label" :href="link.url ?? ''" :class="link.active ? 'bg-blue-700 text-white' : 'border border-slate-300'" class="rounded px-3 py-1" preserve-scroll v-html="link.label" /></div>
        </div>
    </AdminLayout>
</template>
