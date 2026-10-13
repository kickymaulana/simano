<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import EmployeeLayout from '../../Layouts/EmployeeLayout.vue';
import { route } from 'ziggy-js';

type Target = { id: number; name: string; avatar_url: string | null };
type Period = { id: number; month: number; year: number };
type Comment = { id: number; body: string; created_at: string | null; can_delete: boolean };
type Comments = {
    data: Comment[];
    prev_page_url: string | null;
    next_page_url: string | null;
};

const page = usePage<{ errors: { body?: string }; flash?: { success?: string } }>();
const props = defineProps<{ target: Target; period: Period; comments: Comments; userComment: boolean; isSelf: boolean }>();
const form = useForm({ body: '' });
const submit = () => form.post(route('comments.store', props.target.id), { onSuccess: () => form.reset() });
</script>

<template>
    <Head :title="`Komentar untuk ${target.name}`" />
    <EmployeeLayout :title="isSelf ? 'Kotak Masuk Komentar' : 'Komentar Saya'">
        <div class="space-y-4">
            <section class="sticky top-[57px] z-10 -mx-4 border-b border-slate-200 bg-slate-50/95 px-4 py-3 backdrop-blur sm:top-[61px] sm:-mx-6 sm:px-6">
                <div class="flex items-center gap-3">
                    <img v-if="target.avatar_url" :src="target.avatar_url" :alt="target.name" class="h-12 w-12 rounded-full object-cover" />
                    <span v-else class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-lg font-semibold text-blue-700">{{ target.name.slice(0, 1) }}</span>
                    <div>
                        <h2 class="font-bold">{{ target.name }}</h2>
                        <p class="text-sm text-slate-500">Komentar periode {{ period.month }}/{{ period.year }}</p>
                    </div>
                </div>
            </section>

            <p v-if="page.props.flash?.success" class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ page.props.flash.success }}</p>
            <p v-if="page.props.errors.body" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ page.props.errors.body }}</p>

            <div v-if="comments.data.length" class="space-y-3">
                <article v-for="comment in comments.data" :key="comment.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="whitespace-pre-wrap break-words text-slate-800">{{ comment.body }}</p>
                    <div class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500">
                        <span>{{ comment.created_at }}</span>
                        <Link v-if="comment.can_delete" :href="route('comments.destroy', comment.id)" method="delete" as="button" class="font-semibold text-red-700 hover:underline">Hapus</Link>
                    </div>
                </article>
            </div>
            <p v-else class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-slate-500">Belum ada komentar.</p>

            <nav v-if="comments.prev_page_url || comments.next_page_url" class="flex justify-between gap-3">
                <Link v-if="comments.prev_page_url" :href="comments.prev_page_url" preserve-scroll class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Sebelumnya</Link>
                <span v-else />
                <Link v-if="comments.next_page_url" :href="comments.next_page_url" preserve-scroll class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Selanjutnya</Link>
            </nav>

            <form v-if="!isSelf && !userComment" class="space-y-3 rounded-xl border border-slate-200 bg-white p-4" @submit.prevent="submit">
                <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Gunakan bahasa yang sopan dan membangun. Komentar kasar, menghina, SARA, atau tidak pantas dapat dilacak, dihapus, dan dikenakan sanksi sesuai ketentuan perusahaan.</p>
                <label for="comment" class="block text-sm font-semibold">Tulis komentar</label>
                <textarea id="comment" v-model="form.body" rows="4" maxlength="1000" class="w-full rounded-lg border-slate-300" placeholder="Tulis komentar secara sopan..." required />
                <p class="text-xs text-slate-500">Komentar akan tampil tanpa nama pengirim.</p>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50">{{ form.processing ? 'Mengirim...' : 'Kirim komentar' }}</button>
            </form>
            <p v-else-if="isSelf" class="rounded-xl bg-slate-100 p-4 text-sm text-slate-600">Ini adalah komentar tentang Anda. Identitas pemberi komentar tetap anonim.</p>
            <p v-else class="rounded-xl bg-slate-100 p-4 text-sm text-slate-600">Anda sudah mengirim komentar. Hapus komentar Anda untuk membuat komentar baru.</p>

            <Link :href="isSelf ? route('profile.edit') : route('evaluations.create', target.id)" class="inline-block text-sm font-semibold text-blue-700 hover:underline">{{ isSelf ? 'Kembali ke profil' : 'Kembali ke evaluasi' }}</Link>
        </div>
    </EmployeeLayout>
</template>
