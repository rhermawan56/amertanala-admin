<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
const form = useForm({ password: '' });
</script>
<template>
    <Head title="Konfirmasi kata sandi" />
    <main class="auth-centered">
        <section class="auth-card glass">
            <p class="eyebrow">KEAMANAN AKUN</p>
            <h2>Pastikan ini Anda.</h2>
            <p>Konfirmasi kata sandi sebelum mengubah keamanan akun.</p>
            <form @submit.prevent="form.post('/user/confirm-password', { onFinish: () => form.reset() })">
                <label>Kata sandi<input v-model="form.password" type="password" autocomplete="current-password" required /></label>
                <p class="field-error">{{ form.errors.password }}</p>
                <button class="button primary full" :disabled="form.processing">Konfirmasi</button>
            </form>
        </section>
    </main>
</template>
