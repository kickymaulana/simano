<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import EmployeeLayout from '../../Layouts/EmployeeLayout.vue';
import LottieState from '../../Components/LottieState.vue';
import { route } from 'ziggy-js';

type Period = { id: number; month: number; year: number; status: string };
type Evaluation = { id: number; target: { name: string; avatar_url: string | null; role: string }; category: string; submitted_at: string | null };
const props = defineProps<{ periods: Period[]; selectedPeriod: Period | null; evaluations: Evaluation[] }>();
const changePeriod = (event: Event) => router.get(route('evaluations.status'), { period: (event.target as HTMLSelectElement).value }, { preserveState: true, replace: true });
const formatDate = (date: string | null) => date ? new Date(date).toLocaleString('id-ID') : '-';
</script>

<template>
    <Head title="Status evaluasi" />
    <EmployeeLayout title="Status evaluasi">
        <template #subtitle><p class="mt-2 text-slate-600">Riwayat evaluasi yang sudah dikirim.</p></template>
        <div class="space-y-4">
            <label class="block text-sm font-semibold">Periode
                <select class="mt-1 w-full rounded-lg border-slate-300 sm:max-w-xs" :value="selectedPeriod?.id" @change="changePeriod">
                    <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.month }}/{{ period.year }} · {{ period.status }}</option>
                </select>
            </label>
            <LottieState v-if="!evaluations.length" title="Belum ada evaluasi" message="Evaluasi terkirim akan muncul di sini." />
            <div v-else class="space-y-2">
                <div v-for="evaluation in evaluations" :key="evaluation.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div><p class="font-semibold">{{ evaluation.target.name }}</p><p class="text-sm text-slate-500">{{ evaluation.category }}</p></div>
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Terkirim</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ formatDate(evaluation.submitted_at) }}</p>
                </div>
            </div>
            <Link :href="route('targets.index')" class="inline-block rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Buat evaluasi baru</Link>
        </div>
    </EmployeeLayout>
</template>
