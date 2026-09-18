<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

type Evaluator = { evaluation_id: number; name: string; avatar_url?: string | null; nik?: string; position?: string | null; departments?: string | null; submitted_at?: string | null };
type PageLink = { url: string | null; label: string; active: boolean };
type Paginator = { data: Evaluator[]; total: number; current_page: number; per_page: number; links: PageLink[] };
const props = defineProps<{ period: { id: number; month: number; year: number }; target: { id: number; name: string; nik?: string }; targetPosition: { id: number; name: string } | null; evaluators: Paginator }>();
const reportUrl = route('admin.reports.evaluations.atasan', { period: props.period.id, target: props.target.id });
const deleteEvaluator = (evaluator: Evaluator) => {
    if (confirm(`Hapus evaluasi dari ${evaluator.name}? Semua jawaban dan skor akan dihapus permanen.`)) {
        router.delete(route('admin.reports.evaluations.atasan.evaluators.destroy', { period: props.period.id, target: props.target.id, evaluation: evaluator.evaluation_id }));
    }
};
</script>

<template>
    <Head :title="`Daftar Penilai ${target.name}`" />
    <AdminLayout title="Daftar Penilai">
        <template #header-actions><Link :href="reportUrl" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold">Kembali</Link></template>
        <div class="space-y-4">
            <div class="rounded-xl bg-white p-5"><h2 class="text-lg font-bold">{{ target.name }}<span v-if="targetPosition"> - {{ targetPosition.name }}</span></h2><p class="text-sm text-slate-500">Periode {{ period.month }}/{{ period.year }} · NIK {{ target.nik ?? '-' }} · {{ evaluators.total }} penilai</p></div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">No.</th><th class="p-3">Foto</th><th class="p-3">Nama</th><th class="p-3">NIK</th><th class="p-3">Jabatan</th><th class="p-3">Departemen</th><th class="p-3">Waktu Penilaian</th><th class="p-3">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(evaluator, index) in evaluators.data" :key="evaluator.evaluation_id"><td class="p-3">{{ (evaluators.current_page - 1) * evaluators.per_page + index + 1 }}</td><td class="p-3"><img v-if="evaluator.avatar_url" :src="evaluator.avatar_url" :alt="evaluator.name" class="h-10 w-10 rounded-full object-cover" /><span v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-700">{{ evaluator.name.slice(0, 1) }}</span></td><td class="p-3 font-semibold">{{ evaluator.name }}</td><td class="p-3">{{ evaluator.nik ?? '-' }}</td><td class="p-3">{{ evaluator.position ?? '-' }}</td><td class="p-3">{{ evaluator.departments ?? '-' }}</td><td class="p-3">{{ evaluator.submitted_at ?? '-' }}</td><td class="p-3"><button type="button" class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-semibold text-red-700" @click="deleteEvaluator(evaluator)">Hapus</button></td></tr><tr v-if="!evaluators.data.length"><td colspan="8" class="p-8 text-center text-slate-500">Belum ada data penilai.</td></tr></tbody></table></div>
                <div v-if="evaluators.links.length > 3" class="flex flex-wrap gap-2 border-t border-slate-200 p-4"><template v-for="link in evaluators.links" :key="link.label"><Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-1.5 text-sm" :class="link.active ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300'" v-html="link.label" /><span v-else class="cursor-not-allowed rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-400" v-html="link.label" /></template></div>
            </div>
        </div>
    </AdminLayout>
</template>
