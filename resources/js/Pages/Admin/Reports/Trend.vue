<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Item = { id: number; name: string };
type Target = Item & { nik: string; position?: Item | null };
type Point = { period: { id: number; month: number; year: number }; target: { id: number; name: string; role: string }; evaluation_count: number; average_score: number };
type Filters = { target?: number; position_id?: number; factory_id?: number; department_id?: number };
const props = defineProps<{ targets: Target[]; positions: Item[]; factories: Item[]; departments: Item[]; filters: Filters; trend: Point[] }>();
const filters = {
    target: props.filters.target ?? '',
    position_id: props.filters.position_id ?? '',
    factory_id: props.filters.factory_id ?? '',
    department_id: props.filters.department_id ?? '',
};
const apply = () => router.get(route('admin.reports.evaluations.trend'), filters, { preserveState: true, replace: true });
const label = (point: Point) => `${point.period.month}/${point.period.year}`;
</script>

<template>
    <Head title="Tren evaluasi" />
    <AdminLayout title="Tren evaluasi">
        <template #header-actions><Link :href="route('admin.reports.evaluations')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Rekap</Link></template>
        <div class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-sm font-semibold">Target<select v-model="filters.target" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua target</option><option v-for="target in targets" :key="target.id" :value="target.id">{{ target.name }} — {{ target.nik }} — {{ target.position?.name ?? 'Tanpa jabatan' }}</option></select></label>
                <label class="text-sm font-semibold">Jabatan<select v-model="filters.position_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua jabatan</option><option v-for="position in positions" :key="position.id" :value="position.id">{{ position.name }}</option></select></label>
                <label class="text-sm font-semibold">Pabrik<select v-model="filters.factory_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua pabrik</option><option v-for="factory in factories" :key="factory.id" :value="factory.id">{{ factory.name }}</option></select></label>
                <label class="text-sm font-semibold">Departemen<select v-model="filters.department_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua departemen</option><option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option></select></label>
            </div>
            <button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="apply">Terapkan filter</button>
            <div v-if="!trend.length" class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-slate-600">Belum ada data tren.</div>
            <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="w-full min-w-[650px] text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr><th class="p-4">Periode</th><th class="p-4">Target</th><th class="p-4">Jumlah</th><th class="p-4">Rata-rata</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(point, index) in trend" :key="`${point.period.id}-${point.target.id}`"><td class="p-4">{{ label(point) }}<small v-if="index && point.average_score > trend[index - 1].average_score" class="ml-2 text-green-700">Naik</small><small v-if="index && point.average_score < trend[index - 1].average_score" class="ml-2 text-red-700">Turun</small></td><td class="p-4 font-semibold">{{ point.target.name }}</td><td class="p-4">{{ point.evaluation_count }}</td><td class="p-4 font-semibold">{{ point.average_score.toFixed(2) }}</td></tr></tbody></table>
            </div>
        </div>
    </AdminLayout>
</template>
