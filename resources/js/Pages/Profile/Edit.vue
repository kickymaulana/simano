<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import EmployeeLayout from '../../Layouts/EmployeeLayout.vue';
import { route } from 'ziggy-js';

type Option = { id: number; name: string };
type ProfileUser = { name: string; nik: string; email: string; avatar_url: string | null; role: string };

const props = defineProps<{
    user: ProfileUser;
    current: { position: Option | null; departments: Option[]; factories: Option[] };
    request: { position_id: number; department_ids: number[]; factory_ids: number[] } | null;
    positions: Option[];
    departments: Option[];
    factories: Option[];
}>();
const form = useForm({
    position_id: props.request?.position_id ?? props.current.position?.id ?? '',
    department_ids: props.request?.department_ids ?? props.current.departments.map((item) => item.id),
    factory_ids: props.request?.factory_ids ?? props.current.factories.map((item) => item.id),
});
const toggle = (items: number[], id: number) => {
    const index = items.indexOf(id);
    index === -1 ? items.push(id) : items.splice(index, 1);
};
</script>

<template>
    <Head title="Profil" />
    <EmployeeLayout title="Profil">
        <div class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-4">
                    <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" class="h-16 w-16 rounded-full object-cover" />
                    <span v-else class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-2xl font-bold text-blue-700">{{ user.name.slice(0, 1) }}</span>
                    <div><h2 class="font-bold">{{ user.name }}</h2><p class="text-sm text-slate-500">{{ user.nik }} · {{ user.email }} · {{ user.role }}</p><p class="mt-1 text-xs text-slate-500">Nama, foto, dan role dikelola melalui SSO.</p></div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="font-bold">Organisasi saat ini</h2>
                <p class="mt-2 text-sm">Jabatan: {{ current.position?.name ?? '-' }}</p>
                <p class="text-sm">Departemen: {{ current.departments.map((item) => item.name).join(', ') || '-' }}</p>
                <p class="text-sm">Pabrik: {{ current.factories.map((item) => item.name).join(', ') || '-' }}</p>
            </section>

            <form class="space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="form.put(route('profile.update'))">
                <div><h2 class="font-bold">Edit organisasi</h2><p class="text-sm text-slate-500">Perubahan langsung tersimpan.</p></div>
                <label class="block text-sm font-semibold">Jabatan<select v-model="form.position_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="" disabled>Pilih jabatan</option><option v-for="item in positions" :key="item.id" :value="item.id">{{ item.name }}</option></select><span v-if="form.errors.position_id" class="text-xs text-red-600">{{ form.errors.position_id }}</span></label>
                <fieldset><legend class="text-sm font-semibold">Departemen (bisa lebih dari satu)</legend><label v-for="item in departments" :key="item.id" class="mt-2 flex items-center gap-2"><input type="checkbox" :checked="form.department_ids.includes(item.id)" @change="toggle(form.department_ids, item.id)" />{{ item.name }}</label><span v-if="form.errors.department_ids" class="text-xs text-red-600">{{ form.errors.department_ids }}</span></fieldset>
                <fieldset><legend class="text-sm font-semibold">Pabrik (bisa lebih dari satu)</legend><label v-for="item in factories" :key="item.id" class="mt-2 flex items-center gap-2"><input type="checkbox" :checked="form.factory_ids.includes(item.id)" @change="toggle(form.factory_ids, item.id)" />{{ item.name }}</label><span v-if="form.errors.factory_ids" class="text-xs text-red-600">{{ form.errors.factory_ids }}</span></fieldset>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50">Update Profil</button>
            </form>
        </div>
    </EmployeeLayout>
</template>
