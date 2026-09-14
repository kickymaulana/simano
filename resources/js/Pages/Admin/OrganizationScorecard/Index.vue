<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Period = { id: number; month: number; year: number; status: string };
type Row = { id: number; name: string; employee_count: number; evaluation_count: number; average_score: number; minimum_score: number; maximum_score: number };
type Pagination = { data: Row[]; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
const props = defineProps<{ periods: Period[]; selectedPeriod: Period | null; groupBy: string; rows: Pagination }>();
const filters = { period: props.selectedPeriod?.id, group_by: props.groupBy };
const apply = () => router.get(route('admin.organization-scorecard.index'), filters, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Scorecard organisasi" />
    <AdminLayout title="Scorecard organisasi"><div class="space-y-4"><div class="grid gap-3 sm:grid-cols-2"><label class="text-sm font-semibold">Periode<select v-model="filters.period" class="mt-1 w-full rounded-lg border-slate-300"><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.month }}/{{ period.year }} · {{ period.status }}</option></select></label><label class="text-sm font-semibold">Kelompok<select v-model="filters.group_by" class="mt-1 w-full rounded-lg border-slate-300"><option value="department">Departemen</option><option value="factory">Pabrik</option><option value="position">Jabatan</option></select></label></div><button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="apply">Terapkan filter</button><div class="overflow-x-auto rounded-xl border border-slate-200 bg-white"><table class="w-full min-w-[700px] text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr><th class="p-4">Kelompok</th><th class="p-4">Karyawan dinilai</th><th class="p-4">Evaluasi</th><th class="p-4">Rata-rata</th><th class="p-4">Minimum</th><th class="p-4">Maksimum</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in rows.data" :key="row.id"><td class="p-4 font-semibold">{{ row.name }}</td><td class="p-4">{{ row.employee_count }}</td><td class="p-4">{{ row.evaluation_count }}</td><td class="p-4">{{ Number(row.average_score).toFixed(2) }}</td><td class="p-4">{{ row.minimum_score }}</td><td class="p-4">{{ row.maximum_score }}</td></tr><tr v-if="!rows.data.length"><td colspan="6" class="p-6 text-center text-slate-600">Belum ada data evaluasi.</td></tr></tbody></table></div><div v-if="rows.last_page > 1" class="flex flex-wrap gap-2"><Link v-for="link in rows.links" :key="link.label" :href="link.url ?? undefined" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-700 text-white': link.active }" v-html="link.label" /></div></div></AdminLayout>
</template>
