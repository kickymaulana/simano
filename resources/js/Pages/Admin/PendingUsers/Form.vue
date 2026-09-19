<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Option = { id: number; name: string };
type User = { id: number; name: string; nik: string; requested_role: string; requested_position_id: number; requested_departments: Option[]; requested_factories: Option[] };

const props = defineProps<{ user: User; positions: Option[]; departments: Option[]; factories: Option[] }>();
const deleteForm = useForm({});
const showDeleteModal = ref(false);
const copied = ref(false);
const copyNik = async () => {
    await navigator.clipboard.writeText(props.user.nik);
    copied.value = true;
};
const deleteUser = () => deleteForm.delete(route('admin.users.destroy', props.user.id));
const form = useForm({
    role: props.user.requested_role,
    position_id: props.user.requested_position_id,
    department_ids: props.user.requested_departments.map((item) => item.id),
    factory_ids: props.user.requested_factories.map((item) => item.id),
});
const multiplePositions = ['DIREKSI', 'GM/FM', 'FM', 'SEKRETARIS', 'MANAGER'];
const position = computed(() => props.positions.find((item) => item.id === Number(form.position_id)));
const multiple = computed(() => multiplePositions.includes((position.value?.name ?? '').toUpperCase()));
const toggle = (items: number[], id: number) => {
    const index = items.indexOf(id);
    index === -1 ? items.push(id) : items.splice(index, 1);
};
watch(multiple, (allowed) => {
    if (!allowed) {
        form.department_ids = form.department_ids.slice(0, 1);
        form.factory_ids = form.factory_ids.slice(0, 1);
    }
});
</script>

<template>
    <Head :title="`Edit pengajuan: ${user.name}`" />
    <AdminLayout :title="`Edit pengajuan: ${user.name}`">
        <form class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="form.put(route('admin.pending-users.update', user.id))">
            <label class="block text-sm font-semibold">Role
                <select v-model="form.role" class="mt-1 w-full rounded-lg border-slate-300"><option value="employee">Employee</option><option value="hr">HR</option><option value="admin">Admin</option></select>
            </label>
            <label class="block text-sm font-semibold">Jabatan
                <select v-model="form.position_id" class="mt-1 w-full rounded-lg border-slate-300"><option v-for="item in positions" :key="item.id" :value="item.id">{{ item.name }}</option></select>
            </label>
            <fieldset><legend class="text-sm font-semibold">Departemen {{ multiple ? '(bisa lebih dari satu)' : '(pilih satu)' }}</legend>
                <label v-for="item in departments" :key="item.id" class="mt-2 flex items-center gap-2"><input type="checkbox" :checked="form.department_ids.includes(item.id)" :disabled="!multiple && form.department_ids.length === 1 && !form.department_ids.includes(item.id)" @change="toggle(form.department_ids, item.id)" />{{ item.name }}</label>
            </fieldset>
            <fieldset><legend class="text-sm font-semibold">Pabrik {{ multiple ? '(bisa lebih dari satu)' : '(pilih satu)' }}</legend>
                <label v-for="item in factories" :key="item.id" class="mt-2 flex items-center gap-2"><input type="checkbox" :checked="form.factory_ids.includes(item.id)" :disabled="!multiple && form.factory_ids.length === 1 && !form.factory_ids.includes(item.id)" @change="toggle(form.factory_ids, item.id)" />{{ item.name }}</label>
            </fieldset>
            <div class="flex gap-3"><button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Simpan</button><Link :href="route('admin.pending-users.index')" class="rounded-lg border px-4 py-2">Batal</Link></div>
        </form>
        <section class="mt-5 max-w-xl rounded-xl border border-red-200 bg-red-50 p-5">
            <h2 class="font-bold text-red-900">Hapus user</h2>
            <p class="mt-1 text-sm text-red-800">Pengajuan dan seluruh relasi user akan dihapus permanen.</p>
            <button type="button" :disabled="deleteForm.processing" class="mt-4 rounded-lg bg-red-700 px-4 py-2 font-semibold text-white disabled:opacity-50" @click="showDeleteModal = true">Hapus User</button>
        </section>
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4" role="dialog" aria-modal="true" aria-labelledby="delete-pending-user-title">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
                <h2 id="delete-pending-user-title" class="text-lg font-bold text-red-900">Konfirmasi hapus user</h2>
                <p class="mt-3 text-sm text-slate-700">User <strong>{{ user.name }}</strong> dengan NIK:</p>
                <div class="mt-2 flex items-center gap-2 rounded-lg bg-slate-100 p-3"><code class="flex-1 font-semibold">{{ user.nik }}</code><button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-sm" @click="copyNik">{{ copied ? 'Tersalin' : 'Salin NIK' }}</button></div>
                <p class="mt-4 text-sm text-red-800">Hapus user ini dari SIMANO, lalu hapus juga akun dengan NIK tersebut di SSO agar user dapat registrasi ulang. Pengajuan dan seluruh relasi user akan dihapus permanen.</p>
                <div class="mt-6 flex justify-end gap-3"><button type="button" class="rounded-lg border border-slate-300 px-4 py-2" @click="showDeleteModal = false">Batal</button><button type="button" :disabled="deleteForm.processing" class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white disabled:opacity-50" @click="deleteUser">Hapus Permanen</button></div>
            </div>
        </div>
    </AdminLayout>
</template>
