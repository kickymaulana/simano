<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Period = { id: number; month: number; year: number; status: string; opens_at: string | null; closes_at: string | null };

defineProps<{ periods: Period[] }>();

const remove = (period: Period) => {
    if (confirm(`Hapus periode ${period.month}/${period.year}?`)) {
        router.delete(route('admin.evaluation-periods.destroy', period.id));
    }
};
</script>

<template>
    <Head title="Periode evaluasi" />
    <AdminLayout title="Periode evaluasi">
        <template #header-actions>
            <Link :href="route('admin.evaluation-periods.create')" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Tambah periode</Link>
        </template>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="!periods.length" class="p-6 text-slate-600">Belum ada periode.</div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="period in periods" :key="period.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold">{{ period.month }}/{{ period.year }}</p>
                        <p class="text-sm text-slate-500">{{ period.opens_at ?? 'Waktu buka belum diatur' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">{{ period.status }}</span>
                        <Link :href="route('admin.evaluation-periods.edit', period.id)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Edit</Link>
                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700" @click="remove(period)">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
