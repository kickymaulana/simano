<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EmployeeLayout from '../../Layouts/EmployeeLayout.vue';
import LottieState from '../../Components/LottieState.vue';
import { route } from 'ziggy-js';

type Target = { id: number; name: string; avatar_url: string | null; role: string };
const props = defineProps<{ targets: Target[]; filters: { q?: string; category?: string } }>();
const query = ref(props.filters.q ?? '');
let timer: ReturnType<typeof setTimeout>;
watch(query, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => router.get(route('targets.index'), { q: value || undefined }, { preserveState: true, replace: true }), 250);
});
</script>

<template>
    <Head title="Pilih target" />
    <EmployeeLayout title="Pilih target evaluasi">
        <template #subtitle><p class="mt-2 text-slate-600">Cari atasan berdasarkan nama atau NIK.</p></template>
        <div class="space-y-4">
            <input v-model="query" type="search" autocomplete="off" placeholder="Cari nama atau NIK" class="w-full rounded-xl border-slate-300 px-4 py-3" />
            <LottieState v-if="!targets.length" title="Target tidak ditemukan" message="Coba nama atau NIK lain." />
            <div v-else class="space-y-2">
                <Link v-for="target in targets" :key="target.id" :href="route('evaluations.create', target.id)" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <img v-if="target.avatar_url" :src="target.avatar_url" :alt="target.name" class="h-11 w-11 rounded-full object-cover" />
                    <span v-else class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-700">{{ target.name.slice(0, 1) }}</span>
                    <span><strong class="block">{{ target.name }}</strong><small class="text-slate-500">{{ target.role }}</small></span>
                </Link>
            </div>
        </div>
    </EmployeeLayout>
</template>
