<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Log = { id: number; action: string; actor: { name: string; role: string } | null; auditable_type: string | null; created_at: string };
type Page = { data: Log[]; last_page: number; links: { url: string | null; label: string; active: boolean }[] };
const props = defineProps<{ filters: { action?: string; actor?: number; from?: string; to?: string }; actions: string[]; actors: { id: number; name: string; role: string }[]; logs: Page }>();
const filters = { action: props.filters.action ?? '', actor: props.filters.actor ?? '', from: props.filters.from ?? '', to: props.filters.to ?? '' };
const apply = () => router.get(route('admin.audit-logs.index'), filters, { preserveState: true, replace: true });
const formatDate = (date: string) => new Date(date).toLocaleString('id-ID');
</script>

<template>
    <Head title="Audit log" />
    <AdminLayout title="Audit log">
        <template #header-actions><Link :href="route('admin.dashboard')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Dashboard</Link></template>
        <div class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-sm font-semibold">Aksi<select v-model="filters.action" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua aksi</option><option v-for="action in actions" :key="action" :value="action">{{ action }}</option></select></label>
                <label class="text-sm font-semibold">Aktor<select v-model="filters.actor" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua aktor</option><option v-for="actor in actors" :key="actor.id" :value="actor.id">{{ actor.name }} · {{ actor.role }}</option></select></label>
                <label class="text-sm font-semibold">Dari<input v-model="filters.from" type="date" class="mt-1 w-full rounded-lg border-slate-300" /></label>
                <label class="text-sm font-semibold">Sampai<input v-model="filters.to" type="date" class="mt-1 w-full rounded-lg border-slate-300" /></label>
            </div>
            <button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="apply">Terapkan filter</button>
            <div v-if="!logs.data.length" class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-slate-600">Belum ada aktivitas.</div>
            <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white"><table class="w-full min-w-[650px] text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr><th class="p-4">Waktu</th><th class="p-4">Aksi</th><th class="p-4">Aktor</th><th class="p-4">Objek</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="log in logs.data" :key="log.id"><td class="p-4">{{ formatDate(log.created_at) }}</td><td class="p-4 font-semibold">{{ log.action }}</td><td class="p-4">{{ log.actor?.name ?? 'System' }}<small v-if="log.actor" class="block text-slate-500">{{ log.actor.role }}</small></td><td class="p-4">{{ log.auditable_type ?? '-' }}</td></tr></tbody></table></div>
            <div v-if="logs.last_page > 1" class="flex flex-wrap gap-2"><a v-for="link in logs.links" :key="link.label" :href="link.url ?? undefined" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-700 text-white': link.active }" v-html="link.label" /></div>
        </div>
    </AdminLayout>
</template>
