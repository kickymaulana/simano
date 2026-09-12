<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Position = { id?: number; name: string; level: number | null };

const props = defineProps<{ position: Position | null }>();
const form = useForm({
    name: props.position?.name ?? '',
    level: props.position?.level ?? null,
});

const submit = () => {
    props.position?.id
        ? form.put(route('admin.positions.update', props.position.id))
        : form.post(route('admin.positions.store'));
};
</script>

<template>
    <Head :title="position ? 'Edit jabatan' : 'Tambah jabatan'" />
    <AdminLayout :title="position ? 'Edit jabatan' : 'Tambah jabatan'">
        <form class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <label class="block text-sm font-semibold">Nama jabatan
                <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
            </label>
            <label class="block text-sm font-semibold">Level
                <input v-model.number="form.level" type="number" min="0" class="mt-1 w-full rounded-lg border-slate-300" />
                <span v-if="form.errors.level" class="text-xs text-red-600">{{ form.errors.level }}</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Simpan</button>
                <Link :href="route('admin.positions.index')" class="rounded-lg border border-slate-300 px-4 py-2">Batal</Link>
            </div>
        </form>
    </AdminLayout>
</template>
