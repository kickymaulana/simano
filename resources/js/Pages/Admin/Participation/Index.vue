<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Item = { id: number; name: string };
type Period = { id: number; month: number; year: number; status: string };
type User = { id: number; name: string; nik: string; position?: Item | null; departments: Item[]; factories: Item[]; evaluation_count: number; evaluator_count: number; last_evaluated_at: string | null };
type Pagination = { data: User[]; current_page: number; last_page: number; total: number; links: { url: string | null; label: string; active: boolean }[] };
const props = defineProps<{ periods: Period[]; selectedPeriod: Period | null; positions: Item[]; factories: Item[]; departments: Item[]; filters: { position_id?: number; factory_id?: number; department_id?: number }; users: Pagination }>();
const filters = { period: props.selectedPeriod?.id, position_id: props.filters.position_id ?? '', factory_id: props.filters.factory_id ?? '', department_id: props.filters.department_id ?? '' };
const apply = () => router.get(route('admin.evaluation-participation.index'), filters, { preserveState: true, replace: true });
const changePeriod = (event: Event) => { filters.period = Number((event.target as HTMLSelectElement).value); apply(); };
const formatDate = (value: string | null) => value ? new Date(value).toLocaleString('id-ID') : '-';
</script>

<template>
    <Head title="Partisipasi evaluasi" />
    <AdminLayout title="Partisipasi evaluasi">
        <div class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-sm font-semibold">Periode<select class="mt-1 w-full rounded-lg border-slate-300" :value="selectedPeriod?.id" @change="changePeriod"><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.month }}/{{ period.year }} · {{ period.status }}</option></select></label>
                <label class="text-sm font-semibold">Jabatan<select v-model="filters.position_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua jabatan</option><option v-for="item in positions" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                <label class="text-sm font-semibold">Pabrik<select v-model="filters.factory_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua pabrik</option><option v-for="item in factories" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                <label class="text-sm font-semibold">Departemen<select v-model="filters.department_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Semua departemen</option><option v-for="item in departments" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
            </div>
            <button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="apply">Terapkan filter</button>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white"><table class="w-full min-w-[1000px] text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr><th class="p-4">Karyawan</th><th class="p-4">Jabatan</th><th class="p-4">Departemen</th><th class="p-4">Pabrik</th><th class="p-4">Evaluasi</th><th class="p-4">Evaluator</th><th class="p-4">Terakhir</th><th class="p-4">Status</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="user in users.data" :key="user.id"><td class="p-4 font-semibold">{{ user.name }}<small class="block font-normal text-slate-500">{{ user.nik }}</small></td><td class="p-4">{{ user.position?.name ?? '-' }}</td><td class="p-4">{{ user.departments.map((item) => item.name).join(', ') || '-' }}</td><td class="p-4">{{ user.factories.map((item) => item.name).join(', ') || '-' }}</td><td class="p-4">{{ user.evaluation_count }}</td><td class="p-4">{{ user.evaluator_count }}</td><td class="p-4">{{ formatDate(user.last_evaluated_at) }}</td><td class="p-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="user.evaluation_count ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ user.evaluation_count ? 'Sudah dinilai' : 'Belum dinilai' }}</span></td></tr><tr v-if="!users.data.length"><td colspan="8" class="p-6 text-center text-slate-600">Belum ada data.</td></tr></tbody></table></div>
            <div v-if="users.last_page > 1" class="flex flex-wrap gap-2"><Link v-for="link in users.links" :key="link.label" :href="link.url ?? undefined" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-700 text-white': link.active }" v-html="link.label" /></div>
        </div>
    </AdminLayout>
</template>
