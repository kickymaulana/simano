<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type User = {
    id: number;
    name: string;
    position_id?: number | null;
    department_id?: number | null;
    departments?: Array<{ id: number; name: string }>;
    factories?: Array<{ id: number; name: string }>;
};
type Option = { id: number; name: string };

const props = defineProps<{ user: User; positions: Option[]; departments: Option[]; factories: Option[] }>();

const form = useForm({
    name: props.user.name,
    position_id: props.user.position_id ?? null,
    department_ids: props.user.departments?.map((department) => department.id) ?? [],
    factory_ids: props.user.factories?.map((factory) => factory.id) ?? [],
});

const toggleDepartment = (id: number) => {
    const index = form.department_ids.indexOf(id);
    if (index === -1) {
        form.department_ids.push(id);
    } else {
        form.department_ids.splice(index, 1);
    }
};
</script>

<template>
    <Head :title="`Edit user: ${user.name}`" />
    <AdminLayout :title="`Edit user: ${user.name}`">
        <form class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="() => form.put(route('admin.users.update', user.id))">
            <label class="block text-sm font-semibold">Nama
                <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
            </label>
            <label class="block text-sm font-semibold">Jabatan
                <select v-model="form.position_id" class="mt-1 w-full rounded-lg border-slate-300">
                    <option :value="null">-</option>
                    <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.name }}</option>
                </select>
                <span v-if="form.errors.position_id" class="text-xs text-red-600">{{ form.errors.position_id }}</span>
            </label>
            <fieldset class="block text-sm font-semibold">Departemen (bisa lebih dari satu)
                <label v-for="department in departments" :key="department.id" class="mt-1 flex items-center gap-2">
                    <input type="checkbox" :checked="form.department_ids.includes(department.id)" class="rounded" @change="toggleDepartment(department.id)" /> {{ department.name }}
                </label>
                <span v-if="form.errors.department_ids" class="text-xs text-red-600">{{ form.errors.department_ids }}</span>
            </fieldset>
            <fieldset class="block text-sm font-semibold">Pabrik
                <label v-for="factory in factories" :key="factory.id" class="flex items-center gap-2">
                    <input v-model="form.factory_ids" type="checkbox" :value="factory.id" class="rounded" /> {{ factory.name }}
                </label>
                <span v-if="form.errors.factory_ids" class="text-xs text-red-600">{{ form.errors.factory_ids }}</span>
            </fieldset>
            <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Simpan</button>
                <Link :href="route('admin.users.index')" class="rounded-lg border border-slate-300 px-4 py-2">Batal</Link>
            </div>
        </form>
    </AdminLayout>
</template>