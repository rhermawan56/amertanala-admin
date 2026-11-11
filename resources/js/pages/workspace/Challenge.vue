<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';
const recovery = ref(false);
const form = useForm({ code: '', recovery_code: '' });
function submit() {
    if (recovery.value) form.code = '';
    else form.recovery_code = '';
    form.post('/two-factor-challenge', { onFinish: () => form.reset() });
}
</script>
<template>
    <Head title="Verifikasi MFA" />
    <main class="auth-centered">
        <section class="auth-card glass">
            <span class="auth-badge"><ShieldCheck :size="18" /> Verifikasi keamanan</span>
            <h2>Satu langkah lagi.</h2>
            <p>{{ recovery ? 'Masukkan satu kode pemulihan yang belum digunakan.' : 'Masukkan kode enam digit dari aplikasi autentikator.' }}</p>
            <form @submit.prevent="submit">
                <label
                    >{{ recovery ? 'Kode pemulihan' : 'Kode autentikator'
                    }}<input v-if="recovery" v-model="form.recovery_code" autocomplete="one-time-code" required /><input
                        v-else
                        v-model="form.code"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                /></label>
                <p class="field-error">{{ form.errors.code || form.errors.recovery_code }}</p>
                <button class="button primary full" :disabled="form.processing">Verifikasi</button>
            </form>
            <button
                class="text-link recovery-toggle"
                @click="
                    recovery = !recovery;
                    form.clearErrors();
                "
            >
                {{ recovery ? 'Gunakan kode autentikator' : 'Gunakan kode pemulihan' }}
            </button>
        </section>
    </main>
</template>
