<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Pagination<T> = { data: T[]; current_page: number; last_page: number; total: number; links: { url: string | null; label: string; active: boolean }[] };
type User = {
    id: number;
    name: string;
    email: string;
    nik: string;
    role: string;
    active: boolean;
    created_at: string;
    position?: { name: string } | null;
    department?: { name: string } | null;
    departments?: Array<{ name: string }>;
    factories?: Array<{ name: string }>;
};

defineProps<{ users: Pagination<User> }>();

const toggleActive = (user: User) => {
    const action = user.active ? 'Deactive' : 'Aktif kembali';
    if (confirm(`Sicuer ${action.toLowerCase()} user ${user.name}?`)) {
        router.post(route('admin.users.toggle', user.id));
    }
};
</script>

<template>
    <Head title="User" />
    <AdminLayout title="User">
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <div v-if="!users.data.length" class="py-8 text-center text-slate-500">Belum ada user dibersetuju.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="p-3">Nama</th><th class="p-3">NIK</th><th class="p-3">Role</th><th class="p-3">Jabatan</th><th class="p-3">Departemen</th><th class="p-3">Pabrik</th><th class="p-3">Status</th><th class="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-b border-slate-100">
                            <td class="p-3 font-semibold">{{ user.name }}<small class="block font-normal text-slate-500">{{ user.email }}</small></td>
                            <td class="p-3">{{ user.nik }}</td>
                            <td class="p-3 uppercase">{{ user.role }}</td>
                            <td class="p-3">{{ user.position?.name ?? '-' }}</td>
                            <td class="p-3">{{ user.departments?.map((d) => d.name).join(', ') || '-' }}</td>
                            <td class="p-3">{{ user.factories?.map((f) => f.name).join(', ') || '-' }}</td>
                            <td class="p-3">
                                <span :class="user.active ? 'rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs text-emerald-700' : 'rounded-full bg-red-100 px-2.5 py-0.5 text-xs text-red-700'">{{ user.active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="flex gap-2 p-3">
                                <Link :href="route('admin.users.edit', user.id)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Edit</Link>
                                <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm" :class="{ 'text-red-700': user.active, 'text-emerald-700': !user.active }" @click="toggleActive(user)">{{ user.active ? 'Deactive' : 'Aktif kembali' }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="users.last_page > 1" class="mt-4 flex flex-wrap gap-2">
                <Link v-for="link in users.links" :key="link.label" :href="link.url ?? undefined" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-700 text-white': link.active }" v-html="link.label" />
            </div>
        </div>
    </AdminLayout>
</template>