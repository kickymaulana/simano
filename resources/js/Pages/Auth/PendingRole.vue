<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps<{ user: { name: string; nik: string } }>();
const form = useForm({ role: '' });
const roles = [
    { value: 'employee', label: 'Employee', description: 'Mengisi evaluasi kinerja.' },
    { value: 'hr', label: 'HR', description: 'Mengelola periode, template, dan laporan.' },
    { value: 'admin', label: 'Admin', description: 'Mengelola seluruh fitur SIMANO.' },
];
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

            <button
                type="button"
                class="mt-6 w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!form.role || form.processing"
                @click="form.post(route('pending-role.store'))"
            >
                Kirim Permintaan Role
            </button>
        </section>
    </main>
</template>
