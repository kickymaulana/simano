<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

defineProps<{
    users: Array<{
        id: number;
        nik: string;
        name: string;
        email?: string;
        requested_role: string;
        created_at: string;
        requested_position?: { name: string } | null;
        requested_departments?: Array<{ name: string }>;
        requested_factories?: Array<{ name: string }>;
    }>;
}>();

const submit = (url: string) => router.post(url);
</script>

<template>
    <Head title="Pending User" />
    <AdminLayout title="Pending User">
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <div v-if="!users.length" class="py-8 text-center text-slate-500">Belum ada user menunggu persetujuan.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="p-3">Nama</th><th class="p-3">NIK</th><th class="p-3">Role diminta</th><th class="p-3">Jabatan</th><th class="p-3">Departemen</th><th class="p-3">Pabrik</th><th class="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-b border-slate-100">
                            <td class="p-3 font-semibold">{{ user.name }}<small class="block font-normal text-slate-500">{{ user.email }}</small></td>
                            <td class="p-3">{{ user.nik }}</td>
                            <td class="p-3 uppercase">{{ user.requested_role }}</td>
                            <td class="p-3">{{ user.requested_position?.name ?? '-' }}</td>
                            <td class="p-3">{{ user.requested_departments?.map((d) => d.name).join(', ') || '-' }}</td>
                            <td class="p-3">{{ user.requested_factories?.map((f) => f.name).join(', ') || '-' }}</td>
                            <td class="flex gap-2 p-3">
                                <button class="rounded-lg bg-emerald-600 px-3 py-2 font-semibold text-white" @click="submit(route('admin.pending-users.approve', user.id))">Approve</button>
                                <button class="rounded-lg bg-red-600 px-3 py-2 font-semibold text-white" @click="submit(route('admin.pending-users.reject', user.id))">Reject</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
