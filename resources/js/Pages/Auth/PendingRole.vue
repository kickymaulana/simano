<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import { route } from 'ziggy-js';

type Item = { id: number; name: string };

const props = defineProps<{
    user: { name: string; nik: string };
    positions: Item[];
    factories: Item[];
    departments: Item[];
}>();
const form = useForm({ role: '', position_id: '', department_ids: [] as number[], factory_ids: [] as number[] });
const roles = [
    { value: 'employee', label: 'Employee', description: 'Mengisi evaluasi kinerja.' },
];

const toggleFactory = (id: number) => {
    const index = form.factory_ids.indexOf(id);
    if (index === -1) {
        form.factory_ids.push(id);
    } else {
        form.factory_ids.splice(index, 1);
    }
};

const toggleDepartment = (id: number) => {
    const index = form.department_ids.indexOf(id);
    if (index === -1) {
        form.department_ids.push(id);
    } else {
        form.department_ids.splice(index, 1);
    }
};
</script>

<template>
    <Head title="Pilih Role" />
    <main class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
        <section class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">SIMANO</p>
            <h1 class="mt-3 text-2xl font-bold">Pilih role akses</h1>
            <p class="mt-2 text-sm text-slate-600">Halo, {{ props.user.name }} ({{ props.user.nik }}). Pilih role untuk diajukan ke admin.</p>

            <div class="mt-6 space-y-3">
                <button
                    v-for="role in roles"
                    :key="role.value"
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border p-4 text-left transition"
                    :class="form.role === role.value ? 'border-blue-600 bg-blue-50' : 'border-slate-200 hover:border-blue-300'"
                    @click="form.role = role.value"
                >
                    <span>
                        <strong class="block">{{ role.label }}</strong>
                        <small class="text-slate-500">{{ role.description }}</small>
                    </span>
                    <span class="text-xl" :class="form.role === role.value ? 'text-blue-600' : 'text-slate-300'">●</span>
                </button>
            </div>

            <div class="mt-6 space-y-4">
                <label class="block text-sm font-semibold">Jabatan
                    <select v-model="form.position_id" class="mt-1 w-full rounded-lg border-slate-300">
                        <option value="" disabled>Pilih jabatan</option>
                        <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.name }}</option>
                    </select>
                    <span v-if="form.errors.position_id" class="text-xs text-red-600">{{ form.errors.position_id }}</span>
                </label>

                <div class="text-sm font-semibold">Departemen (bisa lebih dari satu)
                    <div class="mt-1 space-y-2">
                        <label
                            v-for="department in departments"
                            :key="department.id"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border p-3"
                            :class="form.department_ids.includes(department.id) ? 'border-blue-600 bg-blue-50' : 'border-slate-200'"
                        >
                            <input type="checkbox" :checked="form.department_ids.includes(department.id)" class="h-4 w-4" @change="toggleDepartment(department.id)" />
                            <span>{{ department.name }}</span>
                        </label>
                    </div>
                    <span v-if="form.errors.department_ids" class="text-xs text-red-600">{{ form.errors.department_ids }}</span>
                </div>

                <div class="text-sm font-semibold">Pabrik (bisa lebih dari satu)
                    <div class="mt-1 space-y-2">
                        <label
                            v-for="factory in factories"
                            :key="factory.id"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border p-3"
                            :class="form.factory_ids.includes(factory.id) ? 'border-blue-600 bg-blue-50' : 'border-slate-200'"
                        >
                            <input type="checkbox" :checked="form.factory_ids.includes(factory.id)" class="h-4 w-4" @change="toggleFactory(factory.id)" />
                            <span>{{ factory.name }}</span>
                        </label>
                    </div>
                    <span v-if="form.errors.factory_ids" class="text-xs text-red-600">{{ form.errors.factory_ids }}</span>
                </div>
            </div>

            <button
                type="button"
                class="mt-6 w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!form.role || !form.position_id || !form.department_ids.length || !form.factory_ids.length || form.processing"
                @click="form.post(route('pending-role.store'))"
            >
                Kirim Permintaan Role
            </button>
        </section>
    </main>
</template>
