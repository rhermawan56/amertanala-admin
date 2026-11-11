<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Eye, Plus, RotateCcw, Save, Send, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import WorkspaceLayout from '../../workspace/WorkspaceLayout.vue';
import type { Content } from '../../workspace/types';
const props = defineProps<{
    site: { draft: Content; published: Content | null; version: number; published_at: string | null };
    revisions: { id: number; action: string; created_at: string }[];
}>();
const form = useForm<{ version: number; content: Content }>({ version: props.site.version, content: JSON.parse(JSON.stringify(props.site.draft)) });
const tab = ref('general'),
    dialog = ref<HTMLDialogElement | null>(null),
    preview = ref(false),
    publishing = ref(false);
const crypto = window.crypto;
const tabs = [
    ['general', 'Identitas & SEO'],
    ['hero', 'Intro & halaman'],
    ['themes', 'Koleksi tema'],
    ['features', 'Fitur layanan'],
    ['footer', 'Konsultasi & footer'],
];
const fields: Record<string, { key: string; label: string; area?: boolean }[]> = {
    general: [
        { key: 'brand', label: 'Nama merek' },
        { key: 'title', label: 'Judul halaman / Open Graph' },
        { key: 'description', label: 'Deskripsi SEO', area: true },
        { key: 'contact_email', label: 'Email konsultasi (opsional)' },
    ],
    hero: [
        { key: 'intro_title', label: 'Judul intro' },
        { key: 'intro_emphasis', label: 'Penekanan intro' },
        { key: 'hero_title', label: 'Judul hero' },
        { key: 'hero_emphasis', label: 'Penekanan hero' },
        { key: 'hero_description', label: 'Deskripsi hero', area: true },
        { key: 'collection_title', label: 'Judul koleksi' },
        { key: 'features_title', label: 'Judul fitur' },
        { key: 'steps_title', label: 'Judul langkah' },
        { key: 'steps_description', label: 'Deskripsi langkah', area: true },
    ],
    footer: [
        { key: 'order_title', label: 'Judul konsultasi' },
        { key: 'order_button', label: 'Teks tombol konsultasi' },
        { key: 'footer_tagline', label: 'Tagline footer' },
    ],
};
const errors = computed(() => Object.values(form.errors));
function save() {
    form.put('/content', {
        preserveScroll: true,
        onSuccess: () => {
            form.version = props.site.version;
            form.defaults();
        },
    });
}
function publish() {
    publishing.value = true;
    router.post(
        '/content/publish',
        { version: props.site.version },
        {
            preserveScroll: true,
            onSuccess: () => {
                form.version = props.site.version;
                form.defaults();
                dialog.value?.close();
            },
            onFinish: () => (publishing.value = false),
        },
    );
}
function move<T>(items: T[], i: number, d: number) {
    const n = i + d;
    if (n < 0 || n >= items.length) return;
    const [item] = items.splice(i, 1);
    items.splice(n, 0, item);
}
function restore(id: number) {
    router.post(
        '/content/restore/' + id,
        { version: props.site.version },
        {
            preserveScroll: true,
            onSuccess: () => {
                form.content = JSON.parse(JSON.stringify(props.site.draft));
                form.version = props.site.version;
                form.defaults();
            },
        },
    );
}
</script>
<template>
    <WorkspaceLayout title="Konten website"
        ><section class="page-heading">
            <div>
                <p class="eyebrow">CONTENT STUDIO</p>
                <h1>Rangkai kesan pertama<span class="heading-dot">.</span></h1>
                <p class="subheading">Kelola kata dan koleksi. Draf tetap privat sampai Anda menerbitkannya.</p>
            </div>
            <button class="button primary" :disabled="form.processing" @click="save">
                <Save :size="15" />{{ form.processing ? 'Menyimpan...' : 'Simpan draf' }}
            </button>
        </section>
        <nav class="content-tabs" aria-label="Bagian konten">
            <button v-for="[id, label] in tabs" :key="id" :class="{ selected: tab === id }" :aria-pressed="tab === id" @click="tab = id">
                {{ label }}
            </button>
        </nav>
        <div v-if="errors.length" role="alert" class="field-error glass editor-panel">
            <p v-for="(error, i) in errors" :key="i">{{ error }}</p>
        </div>
        <div class="editor-grid">
            <section class="glass editor-panel">
                <template v-if="fields[tab]"
                    ><h2>{{ tabs.find((item) => item[0] === tab)?.[1] }}</h2>
                    <div class="form-grid">
                        <label v-for="field in fields[tab]" :key="field.key" :class="{ 'span-2': field.area }"
                            >{{ field.label
                            }}<textarea
                                v-if="field.area"
                                :value="String(form.content[field.key] ?? '')"
                                maxlength="1000"
                                @input="form.content[field.key] = ($event.target as HTMLTextAreaElement).value"
                            ></textarea
                            ><input
                                v-else
                                :type="field.key === 'contact_email' ? 'email' : 'text'"
                                :value="String(form.content[field.key] ?? '')"
                                maxlength="1000"
                                @input="form.content[field.key] = ($event.target as HTMLInputElement).value"
                        /></label>
                    </div>
                    <label v-if="tab === 'footer'"
                        ><input v-model="form.content.chat_enabled" type="checkbox" /> Aktifkan live chat pada landing page</label
                    ></template
                ><template v-if="tab === 'themes'"
                    ><div class="row-heading">
                        <h2>Koleksi tema</h2>
                        <button
                            class="button secondary"
                            @click="
                                form.content.themes.push({
                                    id: 'tema-' + crypto.randomUUID().slice(0, 8),
                                    name: 'Tema baru',
                                    eyebrow: 'THE WEDDING OF',
                                    names: ['Nama pasangan'],
                                    caption: 'Tanggal acara',
                                    preview_url: '',
                                    alt: '',
                                })
                            "
                        >
                            <Plus :size="14" /> Tema
                        </button>
                    </div>
                    <p class="editor-note">
                        Urutan mengikuti tampilan landing page. ID tema tetap menjaga konteks chat. Gunakan URL gambar dari pustaka media.
                    </p>
                    <article v-for="(theme, i) in form.content.themes" :key="theme.id" class="theme-editor">
                        <div class="row-heading">
                            <h3>{{ String(i + 1).padStart(2, '0') }} · {{ theme.name }}</h3>
                            <div class="row-buttons">
                                <button :disabled="i === 0" aria-label="Naikkan tema" @click="move(form.content.themes, i, -1)">
                                    <ArrowUp :size="13" /></button
                                ><button
                                    :disabled="i === form.content.themes.length - 1"
                                    aria-label="Turunkan tema"
                                    @click="move(form.content.themes, i, 1)"
                                >
                                    <ArrowDown :size="13" /></button
                                ><button
                                    :disabled="form.content.themes.length <= 1"
                                    aria-label="Hapus tema dari draf"
                                    @click="form.content.themes.splice(i, 1)"
                                >
                                    <Trash2 :size="13" />
                                </button>
                            </div>
                        </div>
                        <div class="form-grid">
                            <label>Nama tema<input v-model="theme.name" maxlength="150" /></label
                            ><label>Teks pembuka<input v-model="theme.eyebrow" maxlength="150" /></label
                            ><label class="span-2"
                                >Nama pada pratinjau (satu baris per nama)<textarea
                                    :value="theme.names.join('\n')"
                                    @input="theme.names = ($event.target as HTMLTextAreaElement).value.split('\n').slice(0, 3)"
                                ></textarea></label
                            ><label>Caption<input v-model="theme.caption" maxlength="150" /></label
                            ><label>Teks alternatif gambar<input v-model="theme.alt" maxlength="255" /></label
                            ><label class="span-2"
                                >URL gambar pratinjau (opsional)<input
                                    v-model="theme.preview_url"
                                    type="url"
                                    placeholder="http://localhost:8001/storage/media/..."
                            /></label>
                        </div></article></template
                ><template v-if="tab === 'features'"
                    ><div class="row-heading">
                        <h2>Fitur layanan</h2>
                        <button class="button secondary" @click="form.content.features.push({ title: 'Fitur baru', description: 'Deskripsi fitur' })">
                            <Plus :size="14" /> Fitur
                        </button>
                    </div>
                    <article v-for="(feature, i) in form.content.features" :key="i" class="theme-editor">
                        <div class="row-heading">
                            <h3>Fitur {{ i + 1 }}</h3>
                            <div class="row-buttons">
                                <button :disabled="i === 0" aria-label="Naikkan fitur" @click="move(form.content.features, i, -1)">
                                    <ArrowUp :size="13" /></button
                                ><button
                                    :disabled="i === form.content.features.length - 1"
                                    aria-label="Turunkan fitur"
                                    @click="move(form.content.features, i, 1)"
                                >
                                    <ArrowDown :size="13" /></button
                                ><button
                                    :disabled="form.content.features.length <= 1"
                                    aria-label="Hapus fitur"
                                    @click="form.content.features.splice(i, 1)"
                                >
                                    <Trash2 :size="13" />
                                </button>
                            </div>
                        </div>
                        <label>Judul<input v-model="feature.title" maxlength="150" /></label
                        ><label>Deskripsi<textarea v-model="feature.description" maxlength="500"></textarea></label></article
                ></template>
                <div class="editor-actions">
                    <button class="button primary" :disabled="form.processing" @click="save"><Save :size="15" /> Simpan draf</button
                    ><span class="muted small"
                        >{{ form.isDirty ? 'Perubahan belum disimpan' : 'Draf tersimpan' }} · Versi {{ props.site.version }}</span
                    >
                </div>
            </section>
            <aside>
                <section class="glass publish-summary">
                    <p class="eyebrow">PUBLIKASI</p>
                    <h2>Dari draf menjadi cerita.</h2>
                    <span class="status-pill" :class="site.published_at ? 'success' : 'warning'">{{
                        site.published_at ? 'Sudah memiliki konten terbit' : 'Belum diterbitkan'
                    }}</span>
                    <p>Konten terbit diperbarui melalui API. Simpan semua perubahan sebelum menerbitkan.</p>
                    <button class="button primary full" :disabled="form.isDirty || publishing" @click="dialog?.showModal()">
                        <Send :size="14" /> Terbitkan draf</button
                    ><button class="button secondary full" @click="preview = !preview">
                        <Eye :size="14" />{{ preview ? 'Tutup' : 'Tinjau' }} isi draf
                    </button>
                    <p v-if="site.published_at">Terakhir terbit: {{ new Date(site.published_at).toLocaleString('id-ID') }}</p>
                </section>
                <section v-if="preview" class="preview-card revision-list">
                    <p>{{ form.content.brand }}</p>
                    <h3>{{ form.content.hero_title }} {{ form.content.hero_emphasis }}</h3>
                    <p>{{ form.content.hero_description }}</p>
                    <hr class="my-5 border-white/10" />
                    <p v-for="theme in form.content.themes" :key="theme.id">{{ theme.name }}</p>
                    <p class="muted small mt-4">Tinjauan isi. Tata letak publik mengikuti desain landing page.</p>
                </section>
                <section class="revision-list glass publish-summary">
                    <p class="eyebrow">RIWAYAT VERSI</p>
                    <p v-if="!revisions.length">Riwayat muncul setelah penyimpanan pertama.</p>
                    <div v-for="item in revisions" :key="item.id" class="revision-item">
                        <span>{{ item.action }}<br />{{ new Date(item.created_at).toLocaleString('id-ID') }}</span
                        ><button :disabled="form.isDirty" @click="restore(item.id)"><RotateCcw :size="12" /> Pulihkan</button>
                    </div>
                </section>
            </aside>
        </div>
        <dialog ref="dialog" class="confirm-dialog">
            <h2>Terbitkan perubahan?</h2>
            <p>Pengunjung landing page akan melihat konten ini. Riwayat versi tetap tersedia untuk pemulihan.</p>
            <div class="editor-actions">
                <button class="button secondary" @click="dialog?.close()">Batal</button
                ><button class="button primary" :disabled="publishing" @click="publish">{{ publishing ? 'Menerbitkan...' : 'Ya, terbitkan' }}</button>
            </div>
        </dialog></WorkspaceLayout
    >
</template>
