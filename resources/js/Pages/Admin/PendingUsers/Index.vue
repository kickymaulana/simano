<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

defineProps<{
    users: {
        data: Array<{
        id: number;
        nik: string;
        name: string;
        avatar_url?: string | null;
        email?: string;
        requested_role: string;
        created_at: string;
        requested_position?: { name: string } | null;
        requested_departments?: Array<{ name: string }>;
        requested_factories?: Array<{ name: string }>;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
}>();

const submit = (url: string) => router.post(url);
const selectedUser = ref<{ name: string; avatar_url: string } | null>(null);
const closePreview = () => {
    selectedUser.value = null;
};
const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') closePreview();
};
onMounted(() => window.addEventListener('keydown', handleEscape));
onBeforeUnmount(() => window.removeEventListener('keydown', handleEscape));
</script>

<template>
    <Head title="Pending User" />
    <AdminLayout title="Pending User">
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <div v-if="!users.data.length" class="py-8 text-center text-slate-500">Belum ada user menunggu persetujuan.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="p-3">Foto</th><th class="p-3">Nama</th><th class="p-3">NIK</th><th class="p-3">Role diminta</th><th class="p-3">Jabatan</th><th class="p-3">Departemen</th><th class="p-3">Pabrik</th><th class="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-b border-slate-100">
                            <td class="p-3">
                                <button v-if="user.avatar_url" type="button" class="rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500" :aria-label="`Lihat foto ${user.name}`" @click="selectedUser = { name: user.name, avatar_url: user.avatar_url }">
                                    <img :src="user.avatar_url" :alt="user.name" class="h-11 w-11 rounded-full object-cover" />
                                </button>
                                <span v-else class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-700">{{ user.name.slice(0, 1) }}</span>
                            </td>
                            <td class="p-3 font-semibold">{{ user.name }}<small class="block font-normal text-slate-500">{{ user.email }}</small></td>
                            <td class="p-3">{{ user.nik }}</td>
                            <td class="p-3 uppercase">{{ user.requested_role }}</td>
                            <td class="p-3">{{ user.requested_position?.name ?? '-' }}</td>
                            <td class="p-3">{{ user.requested_departments?.map((d) => d.name).join(', ') || '-' }}</td>
                            <td class="p-3">{{ user.requested_factories?.map((f) => f.name).join(', ') || '-' }}</td>
                            <td class="flex gap-2 p-3">
                                <Link :href="route('admin.pending-users.edit', user.id)" class="rounded-lg border border-blue-600 px-3 py-2 font-semibold text-blue-700">Edit</Link>
                                <button class="rounded-lg bg-emerald-600 px-3 py-2 font-semibold text-white" @click="submit(route('admin.pending-users.approve', user.id))">Approve</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nav v-if="users.links.length > 3" class="mt-4 flex flex-wrap justify-center gap-1">
                <Link v-for="link in users.links" :key="link.label" :href="link.url ?? ''" :disabled="!link.url" preserve-scroll class="rounded-lg border px-3 py-2 text-sm" :class="link.active ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300 text-slate-700 disabled:cursor-not-allowed disabled:opacity-50'" v-html="link.label" />
            </nav>
        </div>
        <div v-if="selectedUser" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4" role="dialog" aria-modal="true" @click.self="closePreview">
            <div class="relative max-h-[90vh] max-w-[90vw] rounded-2xl bg-white p-3 shadow-2xl">
                <button type="button" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-slate-900/70 text-xl text-white" :aria-label="`Tutup foto ${selectedUser.name}`" @click="closePreview">&times;</button>
                <img :src="selectedUser.avatar_url" :alt="selectedUser.name" class="max-h-[85vh] max-w-[85vw] rounded-xl object-contain" />
                <p class="px-2 pb-1 pt-3 text-center font-semibold text-slate-800">{{ selectedUser.name }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
