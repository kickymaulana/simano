<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';

type User = {
    id: number;
    name: string;
    nik: string;
    role: string;
    position_id?: number | null;
    department_id?: number | null;
    departments?: Array<{ id: number; name: string }>;
    factories?: Array<{ id: number; name: string }>;
    evaluation_template_id?: number | null;
};
type Option = { id: number; name: string };
type EvaluationTemplateOption = { id: number; target_category: string; active: boolean };

const props = defineProps<{ user: User; positions: Option[]; departments: Option[]; factories: Option[]; evaluationTemplates: EvaluationTemplateOption[] }>();

const deleteForm = useForm({});
const showDeleteModal = ref(false);
const copied = ref(false);
const copyNik = async () => {
    await navigator.clipboard.writeText(props.user.nik);
    copied.value = true;
};
const deleteUser = () => deleteForm.delete(route('admin.users.destroy', props.user.id));

const form = useForm({
    name: props.user.name,
    role: props.user.role,
    position_id: props.user.position_id ?? null,
    evaluation_template_id: props.user.evaluation_template_id ?? null,
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
            <label class="block text-sm font-semibold">Role
                <select v-model="form.role" class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="employee">Employee</option>
                    <option value="hr">HR</option>
                    <option value="admin">Admin</option>
                </select>
                <span v-if="form.errors.role" class="text-xs text-red-600">{{ form.errors.role }}</span>
            </label>
            <label class="block text-sm font-semibold">Jabatan
                <select v-model="form.position_id" class="mt-1 w-full rounded-lg border-slate-300">
                    <option :value="null">-</option>
                    <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.name }}</option>
                </select>
                <span v-if="form.errors.position_id" class="text-xs text-red-600">{{ form.errors.position_id }}</span>
            </label>
            <label class="block text-sm font-semibold">Template evaluasi target
                <select v-model="form.evaluation_template_id" class="mt-1 w-full rounded-lg border-slate-300"><option :value="null">Belum dipilih</option><option v-for="template in evaluationTemplates" :key="template.id" :value="template.id">{{ template.target_category }}{{ template.active ? '' : ' (Nonaktif)' }}</option></select>
                <span v-if="form.errors.evaluation_template_id" class="text-xs text-red-600">{{ form.errors.evaluation_template_id }}</span>
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
        <section class="mt-5 max-w-xl rounded-xl border border-red-200 bg-red-50 p-5">
            <h2 class="font-bold text-red-900">Hapus user</h2>
            <p class="mt-1 text-sm text-red-800">Seluruh relasi organisasi, role, dan riwayat evaluasi user akan dihapus permanen.</p>
            <button type="button" :disabled="deleteForm.processing" class="mt-4 rounded-lg bg-red-700 px-4 py-2 font-semibold text-white disabled:opacity-50" @click="showDeleteModal = true">Hapus User</button>
        </section>
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4" role="dialog" aria-modal="true" aria-labelledby="delete-user-title">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
                <h2 id="delete-user-title" class="text-lg font-bold text-red-900">Konfirmasi hapus user</h2>
                <p class="mt-3 text-sm text-slate-700">User <strong>{{ user.name }}</strong> dengan NIK:</p>
                <div class="mt-2 flex items-center gap-2 rounded-lg bg-slate-100 p-3"><code class="flex-1 font-semibold">{{ user.nik }}</code><button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-sm" @click="copyNik">{{ copied ? 'Tersalin' : 'Salin NIK' }}</button></div>
                <p class="mt-4 text-sm text-red-800">Hapus user ini dari SIMANO, lalu hapus juga akun dengan NIK tersebut di SSO agar user dapat registrasi ulang. Seluruh relasi dan riwayat evaluasi akan dihapus permanen.</p>
                <div class="mt-6 flex justify-end gap-3"><button type="button" class="rounded-lg border border-slate-300 px-4 py-2" @click="showDeleteModal = false">Batal</button><button type="button" :disabled="deleteForm.processing" class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white disabled:opacity-50" @click="deleteUser">Hapus Permanen</button></div>
            </div>
        </div>
    </AdminLayout>
</template>