<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Period = { id: number; month: number; year: number; status: string };
type Target = { id: number; name: string; position?: { name: string } | null };
type Row = { question_number: number; question_text: string; response_count: number; total_score: number; percentages: Record<string, number> };
const props = defineProps<{ periods: Period[]; targets: Target[]; selectedPeriod: Period | null; selectedTarget: { id: number; name: string; nik?: string; avatar_url?: string | null } | null; targetPosition: { id: number; name: string } | null; rows: Row[]; evaluatorCount: number; filters: { period: number | null; target: number | null } }>();
const filters = { period: props.filters.period ?? props.selectedPeriod?.id ?? '', target: props.filters.target ?? '' };
const targetSearch = ref('');
const showSuggestions = ref(false);
const filteredTargets = computed(() => props.targets.filter((target) => target.name.toLowerCase().includes(targetSearch.value.toLowerCase())));
const apply = () => router.get(route('admin.reports.evaluations.atasan'), filters, { preserveState: true, replace: true });
const printReport = () => window.print();
const average = (score: number) => {
    const total = props.rows.reduce((sum, row) => sum + row.percentages[String(score)], 0);
    return props.rows.length ? Math.round(total / props.rows.length) : 0;
};
</script>

<template>
    <Head title="Laporan Evaluasi per Atasan" />
    <AdminLayout title="Laporan Evaluasi per Atasan">
        <div class="space-y-4">
            <div class="no-print grid gap-3 rounded-xl bg-white p-5 sm:grid-cols-2"><label class="text-sm font-semibold">Periode<select v-model="filters.period" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Pilih periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.month }}/{{ period.year }} · {{ period.status }}</option></select></label><label class="relative text-sm font-semibold">Cari nama<input v-model="targetSearch" type="search" placeholder="Ketik nama..." class="mt-1 w-full rounded-lg border-slate-300" @focus="showSuggestions = true" @input="filters.target = ''; showSuggestions = true" />
                    <div v-if="showSuggestions && targetSearch && filteredTargets.length" class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                        <button v-for="target in filteredTargets" :key="target.id" type="button" class="block w-full px-3 py-2 text-left hover:bg-blue-50" @click="filters.target = target.id; targetSearch = target.name; showSuggestions = false"><span class="font-semibold">{{ target.name }}</span><small v-if="target.position" class="block text-slate-500">{{ target.position.name }}</small></button>
                    </div>
                </label><div class="flex items-end gap-2"><button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="apply">Tampilkan laporan</button><button type="button" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold" @click="printReport">Print</button></div></div>
            <div v-if="selectedTarget" class="report-content rounded-xl bg-white p-5"><div class="flex items-center gap-3"><img v-if="selectedTarget.avatar_url" :src="selectedTarget.avatar_url" :alt="selectedTarget.name" class="h-14 w-14 rounded-full object-cover" /><span v-else class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-xl font-bold text-blue-700">{{ selectedTarget.name.slice(0, 1) }}</span><div><h2 class="text-lg font-bold">{{ selectedTarget.name }}<span v-if="targetPosition"> - {{ targetPosition.name }}</span></h2><p class="text-sm text-slate-500">{{ selectedPeriod?.month }}/{{ selectedPeriod?.year }} · {{ selectedTarget.nik ?? '-' }} · <Link :href="route('admin.reports.evaluations.atasan.evaluators', { period: selectedPeriod?.id, target: selectedTarget.id })" class="no-print font-semibold text-blue-700 underline">Jumlah Penilai: {{ evaluatorCount }} orang</Link><span class="hidden print:inline">Jumlah Penilai: {{ evaluatorCount }} orang</span></p></div></div></div>
            <div v-if="!selectedTarget || !rows.length" class="rounded-xl bg-white p-8 text-center text-slate-500">Pilih periode dan atasan untuk melihat laporan.</div>
            <div v-else class="report-content overflow-x-auto rounded-xl border border-slate-200 bg-white"><table class="w-full min-w-[1050px] text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th rowspan="2" class="p-3">No.</th><th rowspan="2" class="p-3">Pertanyaan</th><th colspan="5" class="p-3 text-center">Persentase Jawaban</th></tr><tr><th v-for="score in [5, 4, 3, 2, 1]" :key="score" class="p-3 text-center">{{ score }}</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in rows" :key="row.question_number"><td class="p-3">{{ row.question_number }}</td><td class="p-3">{{ row.question_text }}</td><td v-for="score in [5, 4, 3, 2, 1]" :key="score" class="p-3 text-center">{{ row.percentages[String(score)] }}%</td></tr><tr class="bg-slate-50 font-bold"><td colspan="2" class="p-3">Average</td><td v-for="score in [5, 4, 3, 2, 1]" :key="score" class="p-3 text-center">{{ average(score) }}%</td></tr></tbody></table><div class="p-5 text-sm leading-7"><p>*). Berikut ini penilaian BAWAHAN ANDA terhadap Anda sebagai Atasan</p><p>*). Mohon untuk diperbaiki untuk setiap item yang masih kurang</p><p>*). Penilaian ini akan kita laksanakan kembali 2 bulan lagi</p><p>*). Target Untuk Nilai 4 &amp; 5 Minimal 80%</p></div></div>
         </div>
     </AdminLayout>
</template>

<style>
@media print {
    @page { size: A4 portrait; margin: 10mm; }
    aside, header, nav { display: none !important; }
    main { padding: 0 !important; }
    main > div { max-width: none !important; }
    .no-print { display: none !important; }
    html, body, body > div, body > div > div, main, main > div { background: white !important; }
    .report-content { box-shadow: none !important; border: 0 !important; background: white !important; }
    body, table, thead, tbody, tr, th, td { background: white !important; }
    table { min-width: 0 !important; width: 100% !important; font-size: 8pt !important; }
    th, td { padding: 4px !important; }
    th:first-child, td:first-child { width: 5%; white-space: nowrap; }
    th:nth-child(2), td:nth-child(2) { white-space: normal; overflow-wrap: break-word; }
    th:nth-child(n + 3), td:nth-child(n + 3) { width: 7%; white-space: nowrap; }
    thead { color: #1e293b !important; }
    tbody tr:last-child { color: #1e293b !important; }
    .report-content:last-child > div { background: white !important; color: #1e293b !important; }
}
</style>
