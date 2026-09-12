<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Factory = { id: number; name: string };

defineProps<{ factories: Factory[] }>();

const remove = (factory: Factory) => {
    if (confirm(`Hapus pabrik ${factory.name}?`)) {
        router.delete(route('admin.factories.destroy', factory.id));
    }
};
</script>

<template>
    <Head title="Pabrik" />
    <AdminLayout title="Pabrik">
        <template #header-actions>
            <Link :href="route('admin.factories.create')" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Tambah pabrik</Link>
        </template>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="!factories.length" class="p-6 text-slate-600">Belum ada pabrik.</div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="factory in factories" :key="factory.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <p class="font-semibold">{{ factory.name }}</p>
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.factories.edit', factory.id)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Edit</Link>
                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700" @click="remove(factory)">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
