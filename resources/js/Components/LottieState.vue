<script setup lang="ts">
import lottie, { type AnimationItem } from 'lottie-web';
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineProps<{ title: string; message?: string }>();
const container = ref<HTMLElement | null>(null);
let animation: AnimationItem | undefined;
const animationData = { v: '5.7.4', fr: 30, ip: 0, op: 60, w: 120, h: 120, nm: 'state', ddd: 0, assets: [], layers: [{ ddd: 0, ind: 1, ty: 4, nm: 'dot', sr: 1, ks: { o: { a: 0, k: 100 }, r: { a: 1, k: [{ t: 0, s: [0] }, { t: 60, s: [360] }] }, p: { a: 0, k: [60, 60, 0] }, a: { a: 0, k: [0, 0, 0] }, s: { a: 0, k: [100, 100, 100] } }, ao: 0, shapes: [{ ty: 'gr', it: [{ d: 1, ty: 'el', s: { a: 0, k: [36, 36] }, p: { a: 0, k: [0, 0] } }, { ty: 'st', c: { a: 0, k: [0.12, 0.32, 0.75, 1] }, o: { a: 0, k: 100 }, w: { a: 0, k: 6 }, lc: 2, lj: 2 }, { ty: 'tr', p: { a: 0, k: [0, 0] }, a: { a: 0, k: [0, 0] }, s: { a: 0, k: [100, 100] }, r: { a: 0, k: 0 }, o: { a: 0, k: 100 } }] }] }] };
onMounted(() => { if (container.value) animation = lottie.loadAnimation({ container: container.value, renderer: 'svg', loop: true, autoplay: true, animationData }); });
onBeforeUnmount(() => animation?.destroy());
</script>

<template>
    <div class="flex flex-col items-center justify-center py-8 text-center" role="status">
        <div ref="container" class="h-24 w-24" aria-hidden="true" />
        <p class="font-semibold text-slate-700">{{ title }}</p>
        <p v-if="message" class="mt-1 text-sm text-slate-500">{{ message }}</p>
    </div>
</template>
