<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { route } from 'ziggy-js';

type Question = { id?: number; question_number: number; question_text: string; active: boolean };
type Template = { id?: number; target_category: string; description: string | null; active: boolean; questions?: Question[] };
const props = defineProps<{ template: Template | null }>();
const form = useForm({
    target_category: props.template?.target_category ?? '',
    description: props.template?.description ?? '',
    active: props.template?.active ?? true,
    questions: props.template?.questions?.map((question) => ({ ...question })) ?? [{ question_number: 1, question_text: '', active: true }],
});
const addQuestion = () => form.questions.push({ question_number: form.questions.length + 1, question_text: '', active: true });
const removeQuestion = (index: number) => form.questions.splice(index, 1);
const submit = () => props.template?.id
    ? form.put(route('admin.evaluation-templates.update', props.template.id))
    : form.post(route('admin.evaluation-templates.store'));
</script>

<template>
    <Head :title="template ? 'Edit template' : 'Tambah template'" />
    <AdminLayout :title="template ? 'Edit template' : 'Tambah template'">
        <form class="space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-semibold">Kategori target
                    <input v-model="form.target_category" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Atasan" />
                    <span v-if="form.errors.target_category" class="text-xs text-red-600">{{ form.errors.target_category }}</span>
                </label>
                <label class="flex items-center gap-2 pt-6 text-sm font-semibold"><input v-model="form.active" type="checkbox" /> Aktif</label>
            </div>
            <label class="block text-sm font-semibold">Deskripsi
                <textarea v-model="form.description" rows="2" class="mt-1 w-full rounded-lg border-slate-300" />
            </label>
            <div class="space-y-3">
                <div class="flex items-center justify-between"><h2 class="font-semibold">Pertanyaan</h2><button type="button" class="text-sm font-semibold text-blue-700" @click="addQuestion">+ Tambah soal</button></div>
                <p v-if="form.errors.questions" class="text-sm text-red-600">{{ form.errors.questions }}</p>
                <div v-for="(question, index) in form.questions" :key="index" class="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-[5rem_1fr_auto]">
                    <input v-model="question.question_number" type="number" min="1" class="rounded-lg border-slate-300" aria-label="Nomor pertanyaan" />
                    <input v-model="question.question_text" class="rounded-lg border-slate-300" :placeholder="`Pertanyaan ${index + 1}`" />
                    <button v-if="form.questions.length > 1" type="button" class="text-sm text-red-700" @click="removeQuestion(index)">Hapus</button>
                </div>
            </div>
            <div class="flex gap-3"><button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Simpan</button><Link :href="route('admin.evaluation-templates.index')" class="rounded-lg border border-slate-300 px-4 py-2">Batal</Link></div>
        </form>
    </AdminLayout>
</template>
