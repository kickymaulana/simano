<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Item = { id: number; name: string };
type Target = Item & { nik: string; position?: Item | null };
type Period = { id: number; month: number; year: number; status: string };
type Row = { target: Target & { avatar_url: string | null; role: string; departments: Item[]; factories: Item[] }; evaluation_count: number; average_score: number };
type Pagination = { data: Row[]; current_page: number; last_page: number; total: number; links: { url: string | null; label: string; active: boolean }[] };
type Filters = { target?: number; position_id?: number; factory_id?: number; department_id?: number };
const props = defineProps<{ periods: Period[]; selectedPeriod: Period | null; targets: Target[]; positions: Item[]; factories: Item[]; departments: Item[]; filters: Filters; rows: Pagination }>();
const filters = {
    period: props.selectedPeriod?.id,
    target: props.filters.target ?? '',
    position_id: props.filters.position_id ?? '',
    factory_id: props.filters.factory_id ?? '',
    department_id: props.filters.department_id ?? '',
};
const applyFilter = () => router.get(route('admin.reports.evaluations'), filters, { preserveState: true, replace: true });
const changePeriod = (event: Event) => { filters.period = Number((event.target as HTMLSelectElement).value); applyFilter(); };
const exportUrl = () => route('admin.reports.evaluations.export', filters);
const exportPdfUrl = () => route('admin.reports.evaluations.export.pdf', filters);
</script>

<template>
    <Head title="Rekap evaluasi" />
    <AdminLayout title="Rekap evaluasi">
        <template #header-actions><div class="flex flex-wrap gap-2"><Link :href="route('admin.reports.evaluations.trend')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Tren</Link><Link :href="route('admin.dashboard')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Dashboard</Link></div></template>
        <div class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <label class="text-sm font-semibold">Periode
                    <select class="mt-1 w-full rounded-lg border-slate-300" :value="selectedPeriod?.id" @change="changePeriod"><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.month }}/{{ period.year }} · {{ period.status }}</option></select>
                </label>
                <label class="text-sm font-semibold">Target
                    <select v-model="filters.target" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua target</option><option v-for="target in targets" :key="target.id" :value="target.id">{{ target.name }} — {{ target.nik }} — {{ target.position?.name ?? 'Tanpa jabatan' }}</option></select>
                </label>
                <label class="text-sm font-semibold">Jabatan
                    <select v-model="filters.position_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua jabatan</option><option v-for="position in positions" :key="position.id" :value="position.id">{{ position.name }}</option></select>
                </label>
                <label class="text-sm font-semibold">Pabrik
                    <select v-model="filters.factory_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua pabrik</option><option v-for="factory in factories" :key="factory.id" :value="factory.id">{{ factory.name }}</option></select>
                </label>
                <label class="text-sm font-semibold">Departemen
                    <select v-model="filters.department_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua departemen</option><option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option></select>
                </label>
            </div>
            <div class="flex flex-wrap gap-2"><button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="applyFilter">Terapkan filter</button><a :href="exportUrl()" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold">Export CSV</a><a :href="exportPdfUrl()" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold">Export PDF</a></div>
            <div v-if="!rows.data.length" class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-slate-600">Belum ada data evaluasi.</div>
            <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr><th class="p-4">Target</th><th class="p-4">Jabatan</th><th class="p-4">Departemen</th><th class="p-4">Pabrik</th><th class="p-4">Jumlah</th><th class="p-4">Rata-rata</th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><tr v-for="row in rows.data" :key="row.target.id"><td class="p-4 font-semibold">{{ row.target.name }}<small class="block font-normal text-slate-500">{{ row.target.nik }}</small></td><td class="p-4">{{ row.target.position?.name ?? '-' }}</td><td class="p-4">{{ row.target.departments.map((item) => item.name).join(', ') || '-' }}</td><td class="p-4">{{ row.target.factories.map((item) => item.name).join(', ') || '-' }}</td><td class="p-4">{{ row.evaluation_count }}</td><td class="p-4 font-semibold">{{ row.average_score.toFixed(2) }}</td></tr></tbody>
                </table>
            </div>
            <div v-if="rows.last_page > 1" class="flex flex-wrap gap-2"><a v-for="link in rows.links" :key="link.label" :href="link.url ?? undefined" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-700 text-white': link.active }" v-html="link.label" /></div>
        </div>
    </AdminLayout>
</template>
