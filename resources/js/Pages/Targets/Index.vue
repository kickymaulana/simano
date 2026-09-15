<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import EmployeeLayout from '../../Layouts/EmployeeLayout.vue';
import LottieState from '../../Components/LottieState.vue';
import { route } from 'ziggy-js';

type Target = {
    id: number;
    name: string;
    avatar_url: string | null;
    role: string;
    position?: { name: string } | null;
    department?: { name: string } | null;
    factories?: Array<{ name: string }>;
};
type SelectItem = { id: number; name: string };
type Filters = { q?: string; position_id?: string; department_id?: string; factory_id?: string };

const page = usePage<{ errors: { evaluation?: string } }>();
const props = defineProps<{
    targets: Target[];
    filters: Filters;
    positions: SelectItem[];
    factories: SelectItem[];
    departments: SelectItem[];
    pagination: { current_page: number; last_page: number; total: number };
}>();

const query = ref(props.filters.q ?? '');
const positionId = ref(props.filters.position_id ?? '');
const departmentId = ref(props.filters.department_id ?? '');
const factoryId = ref(props.filters.factory_id ?? '');
const selectedTarget = ref<Target | null>(null);

const closePreview = () => {
    selectedTarget.value = null;
};

const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') closePreview();
};

onMounted(() => window.addEventListener('keydown', handleEscape));
onBeforeUnmount(() => window.removeEventListener('keydown', handleEscape));

let timer: ReturnType<typeof setTimeout>;
watch([positionId, departmentId, factoryId], () => applyFilters());
watch(query, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => applyFilters({ q: value }), 250);
});

const applyFilters = (overrides?: { q?: string }) => {
    router.get(
        route('targets.index'),
        {
            q: (overrides?.q ?? query.value) || undefined,
            position_id: positionId.value || undefined,
            department_id: departmentId.value || undefined,
            factory_id: factoryId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const pageUrl = (page: number) => {
    const params = new URLSearchParams();
    if (query.value) params.set('q', query.value);
    if (positionId.value) params.set('position_id', positionId.value);
    if (departmentId.value) params.set('department_id', departmentId.value);
    if (factoryId.value) params.set('factory_id', factoryId.value);
    if (page > 1) params.set('page', String(page));
    return `${route('targets.index')}?${params.toString()}`;
};
</script>

<template>
    <Head title="Pilih target" />
    <EmployeeLayout title="Pilih target evaluasi">
        <template #subtitle><p class="mt-2 text-slate-600">Cari atasan berdasarkan nama, NIK, jabatan, pabrik, atau departemen.</p></template>
        <div class="space-y-4">
            <div v-if="page.props.errors.evaluation" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                {{ page.props.errors.evaluation }}
            </div>

            <input v-model="query" type="search" autocomplete="off" placeholder="Cari nama atau NIK" class="w-full rounded-xl border-slate-300 px-4 py-3" />
            <div class="grid gap-3 sm:grid-cols-3">
                <select v-model="positionId" class="w-full rounded-xl border-slate-300">
                    <option value="">Semua jabatan</option>
                    <option v-for="position in positions" :key="position.id" :value="String(position.id)">{{ position.name }}</option>
                </select>
                <select v-model="departmentId" class="w-full rounded-xl border-slate-300">
                    <option value="">Semua departemen</option>
                    <option v-for="department in departments" :key="department.id" :value="String(department.id)">{{ department.name }}</option>
                </select>
                <select v-model="factoryId" class="w-full rounded-xl border-slate-300">
                    <option value="">Semua pabrik</option>
                    <option v-for="factory in factories" :key="factory.id" :value="String(factory.id)">{{ factory.name }}</option>
                </select>
            </div>

            <p v-if="targets.length" class="text-sm text-slate-500">{{ pagination.total }} orang ditemukan</p>

            <LottieState v-if="!targets.length" title="Target tidak ditemukan" message="Coba ubah kata kunci atau filter." />
            <div v-else class="space-y-2">
                <Link v-for="target in targets" :key="target.id" :href="route('evaluations.create', target.id)" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <button v-if="target.avatar_url" type="button" class="shrink-0 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :aria-label="`Lihat foto ${target.name}`" @click.prevent.stop="selectedTarget = target">
                        <img :src="target.avatar_url" :alt="target.name" class="h-11 w-11 rounded-full object-cover" />
                    </button>
                    <span v-else class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-700">{{ target.name.slice(0, 1) }}</span>
                    <span class="min-w-0">
                        <strong class="block">{{ target.name }}</strong>
                        <small class="block text-slate-500">{{ target.position?.name ?? 'Tanpa jabatan' }}</small>
                        <small class="text-slate-500">{{ target.factories?.map((f) => f.name).join(', ') || 'Tanpa pabrik' }}</small>
                    </span>
                </Link>
            </div>

            <nav v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
                <Link
                    v-for="page in pagination.last_page"
                    :key="page"
                    :href="pageUrl(page)"
                    preserve-state
                    class="min-w-9 rounded-lg border px-3 py-2 text-center text-sm"
                    :class="page === pagination.current_page ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300 text-slate-700'"
                >
                    {{ page }}
                </Link>
            </nav>
        </div>
        <div v-if="selectedTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4" role="dialog" aria-modal="true" :aria-label="`Foto ${selectedTarget.name}`" @click.self="closePreview">
            <div class="relative max-h-[90vh] max-w-[90vw] rounded-2xl bg-white p-3 shadow-2xl">
                <button type="button" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-slate-900/70 text-xl text-white hover:bg-slate-900" :aria-label="`Tutup foto ${selectedTarget.name}`" @click="closePreview">&times;</button>
                <img :src="selectedTarget.avatar_url!" :alt="selectedTarget.name" class="max-h-[85vh] max-w-[85vw] rounded-xl object-contain" />
                <p class="px-2 pb-1 pt-3 text-center font-semibold text-slate-800">{{ selectedTarget.name }}</p>
            </div>
        </div>
    </EmployeeLayout>
</template>