<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Copy, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import WorkspaceLayout from '../../workspace/WorkspaceLayout.vue';
import type { PageList } from '../../workspace/types';
defineProps<{ media: PageList<{ id: number; path: string; name: string; alt: string; size: number }> }>();
const form = useForm<{ image: File | null; alt: string }>({ image: null, alt: '' });
const copied = ref<number | null>(null),
    copyError = ref('');
function upload() {
    form.post('/media', { forceFormData: true, onSuccess: () => form.reset() });
}
async function copy(id: number, path: string) {
    try {
        await navigator.clipboard.writeText(window.location.origin + '/storage/' + path);
        copied.value = id;
        copyError.value = '';
    } catch {
        copyError.value = 'Salin URL dari kolom gambar secara manual.';
    }
}
</script>
<template>
    <WorkspaceLayout title="Pustaka media"
        ><section class="page-heading">
            <div>
                <p class="eyebrow">MEDIA LIBRARY</p>
                <h1>Detail yang menghidupkan cerita<span class="heading-dot">.</span></h1>
                <p class="subheading">Simpan gambar pratinjau untuk koleksi undangan.</p>
            </div>
        </section>
        <section class="glass media-upload">
            <h2>Tambahkan gambar</h2>
            <form @submit.prevent="upload">
                <label
                    >Gambar · JPG, PNG, WebP · maksimal 4 MB<input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        @change="form.image = ($event.target as HTMLInputElement).files?.[0] || null" /></label
                ><label>Teks alternatif<input v-model="form.alt" maxlength="255" required placeholder="Jelaskan isi gambar" /></label
                ><button class="button primary" :disabled="form.processing">
                    <Upload :size="15" />{{ form.processing ? 'Mengunggah...' : 'Unggah gambar' }}
                </button>
            </form>
            <p v-for="(error, key) in form.errors" :key="key" class="field-error">{{ error }}</p>
        </section>
        <p class="field-error">{{ copyError }}</p>
        <section v-if="!media.data.length" class="glass empty-state">
            <h2>Koleksi dimulai dari satu gambar.</h2>
            <p>Gambar yang Anda unggah akan muncul di sini.</p>
        </section>
        <div class="media-grid">
            <article v-for="item in media.data" :key="item.id" class="glass media-card">
                <img :src="'/storage/' + item.path" :alt="item.alt" loading="lazy" />
                <p>{{ item.name }}</p>
                <small>{{ Math.round(item.size / 1024) }} KB · {{ item.alt }}</small
                ><button class="button secondary full" @click="copy(item.id, item.path)">
                    <Copy :size="13" />{{ copied === item.id ? 'URL disalin' : 'Salin URL gambar' }}
                </button>
            </article>
        </div>
        <nav class="pagination" aria-label="Halaman media">
            <Link v-if="media.prev_page_url" :href="media.prev_page_url" class="text-link">Sebelumnya</Link
            ><Link v-if="media.next_page_url" :href="media.next_page_url" class="text-link">Berikutnya</Link>
        </nav></WorkspaceLayout
    >
</template>
