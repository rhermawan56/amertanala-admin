<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, ShieldCheck, Sparkles } from 'lucide-vue-next';
defineProps<{ status?: string }>();
const form = useForm({ email: '', password: '', remember: false });
function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>
<template>
    <Head title="Masuk" />
    <main class="auth-scene">
        <section class="auth-story">
            <a class="studio-brand" href="/"
                ><span class="brand-mark"><Sparkles :size="22" /></span>amertanala.</a
            >
            <div>
                <p class="eyebrow">YOUR CREATIVE WORKSPACE</p>
                <h1>Ruang tenang.<br />Cerita tanpa batas.</h1>
                <p>
                    Kelola konten, sambut percakapan, dan siapkan<br class="desktop-only" />
                    setiap detail dari satu ruang.
                </p>
            </div>
            <span class="auth-footnote">AMERTANALA STUDIO · ADMIN WORKSPACE</span>
        </section>
        <section class="auth-card glass">
            <span class="auth-badge"><ShieldCheck :size="17" /> Akses administrator</span>
            <h2>Selamat datang kembali<span class="heading-dot">.</span></h2>
            <p>Masuk untuk melanjutkan cerita Anda.</p>
            <div v-if="status" class="flash-success">{{ status }}</div>
            <form @submit.prevent="submit">
                <label
                    >Email<input v-model="form.email" type="email" autocomplete="username" required autofocus placeholder="nama@contoh.com"
                /></label>
                <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
                <label
                    >Kata sandi<input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="Masukkan kata sandi"
                /></label>
                <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>
                <div class="form-between">
                    <span class="muted small">Akun khusus administrator</span><Link href="/forgot-password" class="text-link">Lupa kata sandi?</Link>
                </div>
                <button class="button primary full" :disabled="form.processing">
                    {{ form.processing ? 'Memeriksa...' : 'Masuk ke workspace' }}<ArrowRight :size="16" />
                </button>
            </form>
            <p class="auth-security"><ShieldCheck :size="14" /> Akses khusus administrator.</p>
        </section>
    </main>
</template>
