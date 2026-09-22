<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Position = { id: number; name: string; user_count: number };
type Department = Position & { positions: Position[] };
type Factory = Position & { departments: Department[] };
defineProps<{ factories: Factory[] }>();
const usersUrl = (factoryId: number, filters: { department_id?: number; position_id?: number } = {}): string => route('admin.users.index', { factory_id: factoryId, ...filters });
const printPage = (factoryId?: number) => {
    document.querySelectorAll<HTMLElement>('[data-factory]').forEach((element) => {
        element.classList.toggle('print-hidden', factoryId !== undefined && element.dataset.factory !== String(factoryId));
    });
    window.print();
    document.querySelectorAll<HTMLElement>('[data-factory]').forEach((element) => element.classList.remove('print-hidden'));
};
</script>

<template>
    <Head title="Rekap User" />
    <AdminLayout title="Rekap User">
        <div class="space-y-5">
            <div class="flex justify-end print-hidden"><button type="button" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="printPage()">Print Semua</button></div>
            <section v-for="factory in factories" :key="factory.id" :data-factory="factory.id" class="rounded-xl bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4"><Link :href="usersUrl(factory.id)" class="text-lg font-bold text-slate-900 hover:text-blue-700"><span>{{ factory.name }}</span><span class="ml-2">{{ factory.user_count }} user</span></Link><button type="button" class="print-hidden rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold" @click="printPage(factory.id)">Print</button></div>
                <div class="mt-4">
                    <h2 class="mb-3 font-bold text-slate-700">Departemen dan Jabatan</h2>
                    <div class="space-y-3">
                        <div v-for="department in factory.departments" :key="department.id" class="rounded-lg border border-slate-200 p-3">
                            <Link :href="usersUrl(factory.id, { department_id: department.id })" class="flex items-center justify-between font-semibold text-slate-800 hover:text-blue-700"><span>{{ department.name }}</span><strong>{{ department.user_count }} user</strong></Link>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <Link v-for="position in department.positions" :key="position.id" :href="usersUrl(factory.id, { department_id: department.id, position_id: position.id })" class="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2 text-sm hover:bg-blue-50"><span>{{ position.name }}</span><strong>{{ position.user_count }}</strong></Link>
                            </div>
                            <p v-if="!department.positions.length" class="mt-2 text-sm text-slate-500">Belum ada data jabatan.</p>
                        </div>
                        <p v-if="!factory.departments.length" class="text-sm text-slate-500">Belum ada data.</p>
                    </div>
                </div>
            </section>
            <p v-if="!factories.length" class="rounded-xl bg-white p-5 text-center text-slate-500 shadow-sm">Belum ada data pabrik.</p>
        </div>
    </AdminLayout>
</template>

<style>
@media print {
    .print-hidden,
    aside,
    header {
        display: none !important;
    }

    body,
    main {
        background: white !important;
    }

    [data-factory] {
        break-inside: avoid;
        box-shadow: none !important;
    }
}
</style>
