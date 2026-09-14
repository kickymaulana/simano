<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Period = { id: number; month: number; year: number; status: string; opens_at: string | null; closes_at: string | null };
type Pagination<T> = { data: T[]; current_page: number; last_page: number; total: number; links: { url: string | null; label: string; active: boolean }[] };

const page = usePage<{ errors: { period?: string } }>();
defineProps<{ periods: Pagination<Period> }>();

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
        <div v-if="page.props.errors.period" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            {{ page.props.errors.period }}
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="!periods.data.length" class="p-6 text-slate-600">Belum ada periode.</div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="period in periods.data" :key="period.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
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
        <div v-if="periods.last_page > 1" class="mt-4 flex flex-wrap gap-2">
            <Link v-for="link in periods.links" :key="link.label" :href="link.url ?? undefined" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-700 text-white': link.active }" v-html="link.label" />
        </div>
    </AdminLayout>
</template>
