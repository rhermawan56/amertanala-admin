<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
defineProps<{ valid: boolean; token: string }>();
const form = useForm({});
</script>
<template>
    <Head title="Pulihkan chat" />
    <main class="auth-centered">
        <section class="auth-card glass">
            <p class="eyebrow">AKSES PERCAKAPAN</p>
            <h2>{{ valid ? 'Konfirmasi akses chat.' : 'Tautan sudah berakhir.' }}</h2>
            <p>{{ valid ? 'Konfirmasi untuk melanjutkan. Sesi lama akan dicabut.' : 'Minta tautan baru dari panel chat.' }}</p>
            <form v-if="valid" @submit.prevent="form.post('/chat/access/' + token)">
                <button class="button primary full" :disabled="form.processing">Konfirmasi dan buka chat</button>
            </form>
            <Link v-else href="/chat" class="button secondary full mt-5">Kembali ke chat</Link>
        </section>
    </main>
</template>
