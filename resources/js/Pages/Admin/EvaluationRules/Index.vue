<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

type Rule = { id: number; evaluator_position?: string | null; target_position?: string | null; target_category?: string | null; same_department: boolean; same_factory: boolean; active: boolean };
defineProps<{ rules: Rule[] }>();
</script>

<template>
    <Head title="Aturan Evaluasi" />
    <AdminLayout title="Aturan Evaluasi">
        <div class="space-y-4">
            <div class="rounded-xl bg-white p-5"><h2 class="text-lg font-bold">Aturan yang Dipakai Saat Ini</h2><p class="mt-1 text-sm text-slate-500">Daftar aturan aktif dari evaluation_rules. Aturan nonaktif tetap ditampilkan sebagai referensi.</p></div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">No.</th><th class="p-3">Jabatan Penilai</th><th class="p-3">Target Jabatan</th><th class="p-3">Kategori Target</th><th class="p-3">Departemen Sama</th><th class="p-3">Lokasi/Pabrik Sama</th><th class="p-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(rule, index) in rules" :key="rule.id"><td class="p-3">{{ index + 1 }}</td><td class="p-3 font-semibold">{{ rule.evaluator_position ?? '-' }}</td><td class="p-3">{{ rule.target_position ?? '-' }}</td><td class="p-3">{{ rule.target_category ?? '-' }}</td><td class="p-3">{{ rule.same_department ? 'Ya' : 'Tidak' }}</td><td class="p-3">{{ rule.same_factory ? 'Ya' : 'Tidak' }}</td><td class="p-3"><span class="rounded-full px-2 py-1 text-xs font-semibold" :class="rule.active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ rule.active ? 'Aktif' : 'Nonaktif' }}</span></td></tr><tr v-if="!rules.length"><td colspan="7" class="p-8 text-center text-slate-500">Belum ada aturan evaluasi.</td></tr></tbody></table></div></div>
        </div>
    </AdminLayout>
</template>
