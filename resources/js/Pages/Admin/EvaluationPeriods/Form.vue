<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Period = { id?: number; month: number; year: number; status: string; opens_at: string | null; closes_at: string | null };

const props = defineProps<{ period: Period | null }>();
const form = useForm({
    month: props.period?.month ?? new Date().getMonth() + 1,
    year: props.period?.year ?? new Date().getFullYear(),
    status: props.period?.status ?? 'draft',
    opens_at: props.period?.opens_at?.slice(0, 16) ?? '',
    closes_at: props.period?.closes_at?.slice(0, 16) ?? '',
});

const submit = () => {
    props.period?.id
        ? form.put(route('admin.evaluation-periods.update', props.period.id))
        : form.post(route('admin.evaluation-periods.store'));
};
</script>

<template>
    <Head :title="period ? 'Edit periode' : 'Tambah periode'" />
    <AdminLayout :title="period ? 'Edit periode' : 'Tambah periode'">
        <form class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-semibold">Bulan
                    <input v-model="form.month" type="number" min="1" max="12" class="mt-1 w-full rounded-lg border-slate-300" />
                    <span v-if="form.errors.month" class="text-xs text-red-600">{{ form.errors.month }}</span>
                </label>
                <label class="text-sm font-semibold">Tahun
                    <input v-model="form.year" type="number" min="2020" max="2100" class="mt-1 w-full rounded-lg border-slate-300" />
                    <span v-if="form.errors.year" class="text-xs text-red-600">{{ form.errors.year }}</span>
                </label>
            </div>
            <label class="block text-sm font-semibold">Status
                <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="draft">Draft</option>
                    <option value="active">Aktif</option>
                    <option value="closed">Ditutup</option>
                </select>
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-semibold">Mulai
                    <input v-model="form.opens_at" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-300" />
                </label>
                <label class="text-sm font-semibold">Selesai
                    <input v-model="form.closes_at" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-300" />
                </label>
            </div>
            <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Simpan</button>
                <Link :href="route('admin.evaluation-periods.index')" class="rounded-lg border border-slate-300 px-4 py-2">Batal</Link>
            </div>
        </form>
    </AdminLayout>
</template>
