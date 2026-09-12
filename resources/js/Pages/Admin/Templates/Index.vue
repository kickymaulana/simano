<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Template = { id: number; target_category: string; description: string | null; active: boolean; questions_count: number };
defineProps<{ templates: Template[] }>();

const remove = (template: Template) => {
    if (confirm(`Hapus template ${template.target_category}?`)) router.delete(route('admin.evaluation-templates.destroy', template.id));
};
</script>

<template>
    <Head title="Template evaluasi" />
    <AdminLayout title="Template evaluasi">
        <template #header-actions>
            <Link :href="route('admin.evaluation-templates.create')" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Tambah template</Link>
        </template>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div v-if="!templates.length" class="p-6 text-slate-600">Belum ada template.</div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="template in templates" :key="template.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold">{{ template.target_category }}</p>
                        <p class="text-sm text-slate-500">{{ template.questions_count }} pertanyaan · {{ template.active ? 'Aktif' : 'Nonaktif' }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('admin.evaluation-templates.edit', template.id)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Edit</Link>
                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700" @click="remove(template)">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
