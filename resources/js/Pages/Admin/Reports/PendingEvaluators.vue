<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Evaluator = { id: number; name: string; avatar_url?: string | null; nik?: string; position?: string | null; departments?: string | null; factories?: string | null };
type Paginator = { data: Evaluator[]; total: number; current_page: number; per_page: number; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
const props = defineProps<{ period: { id: number; month: number; year: number }; target: { id: number; name: string; nik?: string }; targetPosition: { id: number; name: string } | null; pendingEvaluators: Paginator }>();
const reportUrl = route('admin.reports.evaluations.atasan', { period: props.period.id, target: props.target.id });
</script>

<template>
    <Head :title="`Belum Menilai ${target.name}`" />
    <AdminLayout title="Daftar Belum Menilai">
        <template #header-actions><Link :href="reportUrl" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold">Kembali</Link></template>
        <div class="space-y-4">
            <div class="rounded-xl bg-white p-5"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-bold">{{ target.name }}<span v-if="targetPosition"> - {{ targetPosition.name }}</span></h2><p class="text-sm text-slate-500">Periode {{ period.month }}/{{ period.year }} · NIK {{ target.nik ?? '-' }} · {{ pendingEvaluators.total }} orang belum menilai</p></div><a :href="route('admin.reports.evaluations.atasan.pending-evaluators.export', { period: period.id, target: target.id })" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Export Excel</a></div></div>
            <div class="overflow-hidden rounded-xl border border-amber-200 bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[750px] text-left text-sm"><thead class="bg-amber-50 text-amber-900"><tr><th class="p-3">No.</th><th class="p-3">Foto</th><th class="p-3">Nama</th><th class="p-3">NIK</th><th class="p-3">Jabatan</th><th class="p-3">Departemen</th><th class="p-3">Pabrik</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(evaluator, index) in pendingEvaluators.data" :key="evaluator.id"><td class="p-3">{{ (pendingEvaluators.current_page - 1) * pendingEvaluators.per_page + index + 1 }}</td><td class="p-3"><img v-if="evaluator.avatar_url" :src="evaluator.avatar_url" :alt="evaluator.name" class="h-10 w-10 rounded-full object-cover" /><span v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 font-semibold text-amber-700">{{ evaluator.name.slice(0, 1) }}</span></td><td class="p-3 font-semibold">{{ evaluator.name }}</td><td class="p-3">{{ evaluator.nik ?? '-' }}</td><td class="p-3">{{ evaluator.position ?? '-' }}</td><td class="p-3">{{ evaluator.departments ?? '-' }}</td><td class="p-3">{{ evaluator.factories ?? '-' }}</td></tr><tr v-if="!pendingEvaluators.data.length"><td colspan="7" class="p-8 text-center text-slate-500">Semua penilai wajib sudah menilai.</td></tr></tbody></table></div><div v-if="pendingEvaluators.links.length > 3" class="flex flex-wrap gap-2 border-t border-slate-200 p-4"><template v-for="link in pendingEvaluators.links" :key="link.label"><Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-1.5 text-sm" :class="link.active ? 'border-amber-700 bg-amber-700 text-white' : 'border-slate-300'" v-html="link.label" /><span v-else class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-400" v-html="link.label" /></template></div></div>
        </div>
    </AdminLayout>
</template>
