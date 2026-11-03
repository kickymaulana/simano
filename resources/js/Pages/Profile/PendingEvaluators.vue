<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import EmployeeLayout from '../../Layouts/EmployeeLayout.vue';
import { route } from 'ziggy-js';

type Period = { id: number; month: number; year: number; status: string };
type Evaluator = { id: number; nik?: string | null; name: string; position?: string | null; departments?: string | null; factories?: string | null };
type Paginator = { data: Evaluator[]; total: number; current_page: number; per_page: number; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
const props = defineProps<{ user: { id: number; name: string }; periods: Period[]; selectedPeriod: Period | null; pendingEvaluators: Paginator }>();
const applyPeriod = (value: string) => router.get(route('profile.pending-evaluators'), value ? { period: value } : {}, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Yang Belum Menilai Saya" />
    <EmployeeLayout title="Yang Belum Menilai Saya">
        <div class="space-y-4">
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5"><h2 class="font-bold">Daftar yang Belum Menilai Saya</h2><p class="mt-1 text-sm text-amber-900">Fitur ini masih dalam tahap percobaan. Data yang ditampilkan mungkin belum sepenuhnya akurat.</p></section>
            <section class="rounded-xl border border-slate-200 bg-white p-5"><div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm text-slate-500">Karyawan</p><p class="font-bold">{{ user.name }}</p></div><label class="text-sm font-semibold">Periode<select :value="selectedPeriod?.id ?? ''" class="mt-1 rounded-lg border-slate-300" @change="applyPeriod(($event.target as HTMLSelectElement).value)"><option value="">Pilih periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.month }}/{{ period.year }} · {{ period.status }}</option></select></label></div></section>
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white"><div v-if="!selectedPeriod" class="p-8 text-center text-slate-500">Pilih periode untuk melihat daftar.</div><div v-else class="overflow-x-auto"><table class="w-full min-w-[800px] text-left text-sm"><thead class="bg-amber-50 text-amber-900"><tr><th class="p-3">No.</th><th class="p-3">NIK</th><th class="p-3">Nama</th><th class="p-3">Jabatan</th><th class="p-3">Departemen</th><th class="p-3">Lokasi/Pabrik</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(evaluator, index) in pendingEvaluators.data" :key="evaluator.id"><td class="p-3">{{ (pendingEvaluators.current_page - 1) * pendingEvaluators.per_page + index + 1 }}</td><td class="p-3">{{ evaluator.nik ?? '-' }}</td><td class="p-3 font-semibold">{{ evaluator.name }}</td><td class="p-3">{{ evaluator.position ?? '-' }}</td><td class="p-3">{{ evaluator.departments ?? '-' }}</td><td class="p-3">{{ evaluator.factories ?? '-' }}</td></tr><tr v-if="!pendingEvaluators.data.length"><td colspan="6" class="p-8 text-center text-slate-500">Semua evaluator yang wajib sudah menilai Anda.</td></tr></tbody></table></div><div v-if="selectedPeriod && pendingEvaluators.links.length > 3" class="flex flex-wrap gap-2 border-t border-slate-200 p-4"><template v-for="link in pendingEvaluators.links" :key="link.label"><Link v-if="link.url" :href="link.url" preserve-scroll class="rounded-lg border px-3 py-1.5 text-sm" :class="link.active ? 'border-amber-700 bg-amber-700 text-white' : 'border-slate-300'" v-html="link.label" /><span v-else class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-400" v-html="link.label" /></template></div></section>
            <Link :href="route('profile.edit')" class="inline-block text-sm font-semibold text-blue-700 hover:underline">Kembali ke profil</Link>
        </div>
    </EmployeeLayout>
</template>
