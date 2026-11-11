<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
defineProps<{ status?: string }>();
const form = useForm({ email: '' });
</script>
<template>
    <Head title="Pulihkan akun" />
    <main class="auth-centered">
        <section class="auth-card glass">
            <p class="eyebrow">AKSES ADMINISTRATOR</p>
            <h2>Pulihkan akun Anda.</h2>
            <p>Masukkan email akun. Jika memenuhi syarat, Anda akan menerima tautan pengaturan ulang.</p>
            <p v-if="status" role="status">{{ status }}</p>
            <form @submit.prevent="form.post('/forgot-password')">
                <label>Email<input v-model="form.email" type="email" autocomplete="username" required /></label>
                <p class="field-error">{{ form.errors.email }}</p>
                <button class="button primary full" :disabled="form.processing">Kirim tautan</button>
            </form>
            <Link href="/login" class="text-link mt-5">Kembali masuk</Link>
        </section>
    </main>
</template>
