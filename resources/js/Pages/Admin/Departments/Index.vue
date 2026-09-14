<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Department = { id: number; name: string };
type Pagination<T> = { data: T[]; current_page: number; last_page: number; total: number; links: { url: string | null; label: string; active: boolean }[] };

defineProps<{ departments: Pagination<Department> }>();

const remove = (department: Department) => {
    if (confirm(`Hapus departemen ${department.name}?`)) {
        router.delete(route('admin.departments.destroy', department.id));
    }
};
</script>

<template>
    <Head title="Departemen" />
    <AdminLayout title="Departemen">
        <template #header-actions>
            <Link :href="route('admin.departments.create')" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Tambah departemen</Link>
        </template>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="!departments.data.length" class="p-6 text-slate-600">Belum ada departemen.</div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="department in departments.data" :key="department.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <p class="font-semibold">{{ department.name }}</p>
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.departments.edit', department.id)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Edit</Link>
                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700" @click="remove(department)">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="departments.last_page > 1" class="mt-4 flex flex-wrap gap-2">
            <Link v-for="link in departments.links" :key="link.label" :href="link.url ?? undefined" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-700 text-white': link.active }" v-html="link.label" />
        </div>
    </AdminLayout>
</template>
