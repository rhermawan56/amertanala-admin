<script setup lang="ts">
import { ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';
import WorkspaceLayout from '../../workspace/WorkspaceLayout.vue';
import { api } from '../../workspace/api';

const currentPassword = ref(''),
    newPassword = ref(''),
    passwordConfirmation = ref('');
const error = ref(''),
    busy = ref(false),
    notice = ref('');

async function changePassword() {
    busy.value = true;
    error.value = '';
    notice.value = '';
    try {
        await api('/user/password', 'PUT', {
            current_password: currentPassword.value,
            password: newPassword.value,
            password_confirmation: passwordConfirmation.value,
        });
        currentPassword.value = '';
        newPassword.value = '';
        passwordConfirmation.value = '';
        notice.value = 'Kata sandi diperbarui.';
    } catch (e) {
        error.value = (e as Error).message;
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <WorkspaceLayout title="Keamanan">
        <section class="page-heading">
            <div>
                <p class="eyebrow">AKSES YANG TERLINDUNGI</p>
                <h1>Keamanan workspace<span class="heading-dot">.</span></h1>
                <p class="subheading">Kelola kata sandi untuk menjaga akses akun Anda.</p>
            </div>
        </section>
        <section class="glass settings-card">
            <span class="stat-icon cyan"><ShieldCheck :size="22" /></span>
            <h2>Keamanan akun</h2>
            <p class="subheading">Sesi berakhir setelah 15 menit tidak aktif, maksimal 8 jam. Pendaftaran publik dinonaktifkan.</p>
            <p v-if="notice" class="flash-success" role="status">{{ notice }}</p>
            <form @submit.prevent="changePassword">
                <h2>Perbarui kata sandi</h2>
                <label>Kata sandi sekarang<input v-model="currentPassword" type="password" autocomplete="current-password" required /></label>
                <label>Kata sandi baru<input v-model="newPassword" type="password" autocomplete="new-password" minlength="12" required /></label>
                <label
                    >Ulangi kata sandi baru<input v-model="passwordConfirmation" type="password" autocomplete="new-password" minlength="12" required
                /></label>
                <button class="button secondary" :disabled="busy">Simpan kata sandi</button>
            </form>
            <p class="field-error" role="alert">{{ error }}</p>
        </section>
    </WorkspaceLayout>
</template>
