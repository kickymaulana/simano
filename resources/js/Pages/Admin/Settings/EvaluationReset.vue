<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

type Period = { id: number; month: number; year: number; status: string };
type Target = { id: number; name: string; nik: string };
const props = defineProps<{ periods: Period[]; targets: Target[] }>();
const scope = ref<'all' | 'period' | 'target'>('period');
const periodId = ref<number | ''>('');
const targetId = ref<number | ''>('');
const targetSearch = ref('');
const matchingTargets = computed(() => {
    const term = targetSearch.value.trim().toLowerCase();

    return term ? props.targets.filter((target) => `${target.name} ${target.nik}`.toLowerCase().includes(term)) : [];
});
const selectTarget = (target: Target) => {
    targetId.value = target.id;
    targetSearch.value = `${target.name} — ${target.nik}`;
};
const reset = () => {
    if ((scope.value !== 'all' && !periodId.value) || (scope.value === 'target' && !targetId.value)) return;
    const label = scope.value === 'all' ? 'seluruh evaluasi' : scope.value === 'period' ? 'seluruh evaluasi pada periode terpilih' : 'seluruh evaluasi target pada periode terpilih';
    if (confirm(`Hapus ${label} beserta seluruh detail jawaban secara permanen?`)) {
        router.post(route('admin.settings.evaluation-reset.store'), { scope: scope.value, period_id: periodId.value, target_id: targetId.value });
    }
};
</script>

<template>
    <Head title="Reset Penilaian" />
    <AdminLayout title="Reset Penilaian">
        <div class="max-w-2xl rounded-xl border border-red-200 bg-red-50 p-6">
            <h1 class="text-xl font-bold text-red-900">Reset Penilaian</h1>
            <p class="mt-2 text-sm text-red-800">Tindakan ini menghapus evaluasi dan seluruh detail jawaban secara permanen.</p>
            <div class="mt-6 space-y-4">
                <label class="block text-sm font-semibold">Cakupan
                    <select v-model="scope" class="mt-1 w-full rounded-lg border-slate-300">
                        <option value="period">Periode tertentu</option>
                        <option value="target">Target pada periode tertentu</option>
                        <option value="all">Semua evaluasi</option>
                    </select>
                </label>
                <label v-if="scope !== 'all'" class="block text-sm font-semibold">Periode
                    <select v-model="periodId" class="mt-1 w-full rounded-lg border-slate-300">
                        <option value="">Pilih periode</option>
                        <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.month }}/{{ period.year }} · {{ period.status }}</option>
                    </select>
                </label>
                <div v-if="scope === 'target'" class="relative">
                    <label class="block text-sm font-semibold">Target
                        <input v-model="targetSearch" type="search" placeholder="Ketik nama atau NIK..." class="mt-1 w-full rounded-lg border-slate-300" @input="targetId = ''" />
                    </label>
                    <div v-if="targetSearch && !targetId" class="absolute z-10 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                        <button v-for="target in matchingTargets" :key="target.id" type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50" @click="selectTarget(target)">{{ target.name }} — {{ target.nik }}</button>
                        <p v-if="!matchingTargets.length" class="px-3 py-2 text-sm text-slate-500">Target tidak ditemukan.</p>
                    </div>
                </div>
                <button type="button" class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-400" :disabled="(scope !== 'all' && !periodId) || (scope === 'target' && !targetId)" @click="reset">Reset penilaian</button>
            </div>
        </div>
    </AdminLayout>
</template>
