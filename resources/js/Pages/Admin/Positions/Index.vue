<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Position = { id: number; name: string; level: number | null };

defineProps<{ positions: Position[] }>();

const remove = (position: Position) => {
    if (confirm(`Hapus jabatan ${position.name}?`)) {
        router.delete(route('admin.positions.destroy', position.id));
    }
};
</script>

<template>
    <Head title="Jabatan" />
    <AdminLayout title="Jabatan">
        <template #header-actions>
            <Link :href="route('admin.positions.create')" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Tambah jabatan</Link>
        </template>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="!positions.length" class="p-6 text-slate-600">Belum ada jabatan.</div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="position in positions" :key="position.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold">{{ position.name }}</p>
                        <p v-if="position.level !== null" class="text-sm text-slate-500">Level {{ position.level }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.positions.edit', position.id)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Edit</Link>
                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700" @click="remove(position)">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
