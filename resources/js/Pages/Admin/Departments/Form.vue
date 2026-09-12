<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Department = { id?: number; name: string };

const props = defineProps<{ department: Department | null }>();
const form = useForm({ name: props.department?.name ?? '' });

const submit = () => {
    props.department?.id
        ? form.put(route('admin.departments.update', props.department.id))
        : form.post(route('admin.departments.store'));
};
</script>

<template>
    <Head :title="department ? 'Edit departemen' : 'Tambah departemen'" />
    <AdminLayout :title="department ? 'Edit departemen' : 'Tambah departemen'">
        <form class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <label class="block text-sm font-semibold">Nama departemen
                <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Simpan</button>
                <Link :href="route('admin.departments.index')" class="rounded-lg border border-slate-300 px-4 py-2">Batal</Link>
            </div>
        </form>
    </AdminLayout>
</template>
