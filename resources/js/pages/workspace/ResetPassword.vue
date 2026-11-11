<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
const props = defineProps<{ email: string; token: string }>();
const form = useForm({ email: props.email, token: props.token, password: '', password_confirmation: '' });
</script>
<template>
    <Head title="Atur kata sandi" />
    <main class="auth-centered">
        <section class="auth-card glass">
            <h2>Kata sandi baru.</h2>
            <form @submit.prevent="form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') })">
                <label>Email<input v-model="form.email" type="email" autocomplete="username" required /></label
                ><label>Kata sandi baru<input v-model="form.password" type="password" autocomplete="new-password" minlength="12" required /></label
                ><label>Ulangi kata sandi<input v-model="form.password_confirmation" type="password" autocomplete="new-password" required /></label>
                <p v-for="(error, key) in form.errors" :key="key" class="field-error">{{ error }}</p>
                <button class="button primary full" :disabled="form.processing">Simpan kata sandi</button>
            </form>
        </section>
    </main>
</template>
