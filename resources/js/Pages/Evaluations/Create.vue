<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '../../Layouts/EmployeeLayout.vue';
import LottieState from '../../Components/LottieState.vue';
import { route } from 'ziggy-js';

type Question = { id: number; question_number: number; question_text: string };
type Target = { id: number; name: string; avatar_url: string | null; role: string };
type Period = { id: number; month: number; year: number };
type Template = { id: number; target_category: string; description: string | null };
const props = defineProps<{ target: Target; period: Period; template: Template; questions: Question[] }>();
const scaleLabels = [
    { score: 1, label: 'Tidak sama sekali', full: 'Tidak sama sekali, tidak peduli, tidak respon.' },
    { score: 2, label: 'Kurang peduli', full: 'Ya pernah, kurang peduli & respon.' },
    { score: 3, label: 'Peduli & respon', full: 'Ya pernah, peduli & respon.' },
    { score: 4, label: 'Sering peduli', full: 'Ya sering, peduli & respon.' },
    { score: 5, label: 'Selalu, semangat', full: 'Ya selalu, peduli, respon, kasi semangat.' },
];
const form = useForm({
    target_id: props.target.id,
    template_id: props.template.id,
    scores: Object.fromEntries(props.questions.map((question) => [question.id, 0])) as Record<number, number>,
});
const submit = () => form.post(route('evaluations.store'));
</script>

<template>
    <Head title="Evaluasi" />
    <EmployeeLayout title="Evaluasi kinerja">
        <template #subtitle><p class="mt-2 text-slate-600">{{ target.name }} · {{ template.target_category }} · {{ period.month }}/{{ period.year }}</p></template>
        <form class="space-y-5" @submit.prevent="submit">
            <p v-if="template.description" class="rounded-xl bg-blue-50 p-4 text-sm text-blue-900">{{ template.description }}</p>
            <div class="space-y-3">
                <div class="rounded-xl bg-slate-100 p-3 text-xs text-slate-600">
                    <p class="font-semibold text-slate-700">Skala penilaian</p>
                    <ul class="mt-1 space-y-0.5">
                        <li v-for="item in scaleLabels" :key="item.score"><span class="font-semibold">{{ item.score }}</span> = {{ item.full }}</li>
                    </ul>
                </div>
                <fieldset v-for="question in questions" :key="question.id" class="rounded-xl border border-slate-200 bg-white p-4">
                    <legend class="font-semibold">{{ question.question_number }}. {{ question.question_text }}</legend>
                    <div class="mt-4 grid grid-cols-5 gap-2">
                        <label v-for="item in scaleLabels" :key="item.score" :title="item.full" class="cursor-pointer text-center text-sm"><input v-model="form.scores[question.id]" :value="item.score" type="radio" :name="`score-${question.id}`" class="sr-only peer" /><span class="block rounded-lg border border-slate-300 px-2 py-2 peer-checked:border-blue-700 peer-checked:bg-blue-700 peer-checked:text-white"><span class="block text-base font-bold">{{ item.score }}</span><span class="mt-0.5 block text-[11px] leading-tight">{{ item.label }}</span></span></label>
                    </div>
                </fieldset>
            </div>
            <LottieState v-if="form.processing" title="Mengirim evaluasi" message="Jawaban sedang disimpan dengan aman." />
            <LottieState v-else-if="Object.keys(form.errors).length" title="Pengiriman gagal" message="Periksa jawaban lalu coba lagi." />
            <p v-if="form.errors.scores" class="text-sm text-red-600">{{ form.errors.scores }}</p>
            <div class="flex gap-3"><button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white">Kirim evaluasi</button><Link :href="route('targets.index')" class="rounded-lg border border-slate-300 px-5 py-3">Batal</Link></div>
        </form>
    </EmployeeLayout>
</template>
