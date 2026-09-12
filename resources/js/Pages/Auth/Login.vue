<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps<{ error?: string; errors?: { sso?: string } }>();
</script>

<template>
    <Head title="Login" />
    <main class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
        <section class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">SIMANO</p>
            <h1 class="mt-3 text-2xl font-bold">Login karyawan</h1>
            <p class="mt-2 text-sm text-slate-600">Gunakan akun SSO perusahaan untuk masuk.</p>
            <p v-if="error || errors?.sso" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error || errors?.sso }}</p>
            <a :href="route('sso.login')" class="mt-6 block rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white">Login dengan SSO</a>
            <Link :href="route('home')" class="mt-4 block text-sm text-slate-500">Kembali</Link>
        </section>
    </main>
</template>
