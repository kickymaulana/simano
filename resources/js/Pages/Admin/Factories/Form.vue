<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Factory = { id?: number; name: string };

const props = defineProps<{ factory: Factory | null }>();
const form = useForm({ name: props.factory?.name ?? '' });

const submit = () => {
    props.factory?.id
        ? form.put(route('admin.factories.update', props.factory.id))
        : form.post(route('admin.factories.store'));
};
</script>

<template>
    <Head :title="factory ? 'Edit pabrik' : 'Tambah pabrik'" />
    <AdminLayout :title="factory ? 'Edit pabrik' : 'Tambah pabrik'">
        <form class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <label class="block text-sm font-semibold">Nama pabrik
                <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Simpan</button>
                <Link :href="route('admin.factories.index')" class="rounded-lg border border-slate-300 px-4 py-2">Batal</Link>
            </div>
        </form>
    </AdminLayout>
</template>
