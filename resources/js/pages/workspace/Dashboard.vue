<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    CalendarDays,
    CircleCheck,
    Clock3,
    FilePenLine,
    ImagePlus,
    Layers3,
    MessagesSquare,
    Plus,
    Send,
} from 'lucide-vue-next';
import { computed } from 'vue';
import WorkspaceLayout from '../../workspace/WorkspaceLayout.vue';
import type { Conversation } from '../../workspace/types';
const props = defineProps<{
    stats: { themes: number; conversations: number; unread: number; published: string | null; draft: boolean };
    activity: { id: number; action: string; created_at: string; name: string }[];
    recent: Conversation[];
    chart: { label: string; count: number }[];
    landingUrl: string;
}>();
const date = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
const maximum = computed(() => Math.max(4, ...props.chart.map((d) => d.count)));
const points = computed(() => props.chart.map((d, i) => `${i * 100},${160 - (d.count / maximum.value) * 130}`).join(' '));
const total = computed(() => props.chart.reduce((sum, d) => sum + d.count, 0));
const labels: Record<string, string> = {
    draft: 'Draf konten disimpan',
    published: 'Konten website diterbitkan',
    restored: 'Versi konten dipulihkan',
};
const cards = computed(() => [
    { label: 'Koleksi tema', value: props.stats.themes, icon: Layers3, detail: 'Tema dalam draf', class: 'cyan', url: '/content' },
    {
        label: 'Total percakapan',
        value: props.stats.conversations,
        icon: MessagesSquare,
        detail: 'Semua percakapan pengunjung',
        class: 'violet',
        url: '/inbox',
    },
    { label: 'Menunggu balasan', value: props.stats.unread, icon: Send, detail: 'Percakapan belum dibaca', class: 'blue', url: '/inbox' },
    {
        label: 'Status konten',
        value: props.stats.draft ? 'Draf' : 'Terbit',
        icon: FilePenLine,
        detail: props.stats.published ? 'Terakhir: ' + props.stats.published : 'Belum pernah diterbitkan',
        class: 'teal',
        url: '/content',
    },
]);
</script>
<template>
    <WorkspaceLayout title="Ringkasan">
        <section class="page-heading">
            <div>
                <p class="eyebrow"><span class="live-dot"></span> RUANG UNTUK CERITA BARU</p>
                <h1>Semua cerita, dalam kendali<span class="heading-dot">.</span></h1>
                <p class="subheading">Selamat datang kembali. Mari buat sesuatu yang berkesan hari ini.</p>
            </div>
            <Link href="/content" class="button primary"><Plus :size="16" /> Kelola konten</Link>
        </section>
        <div class="date-line"><CalendarDays :size="14" />{{ date }}<span class="line-separator"></span><span>Workspace overview</span></div>
        <section class="stats-grid" aria-label="Ringkasan workspace">
            <Link v-for="card in cards" :key="card.label" :href="card.url" class="stat-card glass"
                ><div class="stat-top">
                    <span>{{ card.label }}</span
                    ><span class="stat-icon" :class="card.class"><component :is="card.icon" :size="17" /></span>
                </div>
                <div class="stat-value">{{ card.value }}</div>
                <div class="stat-detail">{{ card.detail }}<ArrowUpRight :size="14" /></div
            ></Link>
        </section>
        <div class="dashboard-grid">
            <section class="glass chart-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">INTERAKSI PENGUNJUNG</p>
                        <h2>Setiap pesan, awal sebuah cerita.</h2>
                    </div>
                    <span class="chip">7 hari terakhir</span>
                </div>
                <div class="chart-summary">
                    <strong>{{ total }}</strong
                    ><span>pesan masuk<span class="muted">dari pengunjung website</span></span>
                </div>
                <div class="chart-wrap">
                    <div class="chart-y">
                        <span>{{ maximum }}</span
                        ><span>{{ Math.round(maximum / 2) }}</span
                        ><span>0</span>
                    </div>
                    <div class="chart-area">
                        <svg viewBox="0 0 600 180" preserveAspectRatio="none" role="img" :aria-label="total + ' pesan dalam tujuh hari'">
                            <defs>
                                <linearGradient id="area-fill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#67e8f9" stop-opacity=".25" />
                                    <stop offset="100%" stop-color="#67e8f9" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="M0 30H600 M0 95H600 M0 160H600" stroke="rgba(255,255,255,.07)" stroke-dasharray="4 5" />
                            <polygon :points="'0,180 ' + points + ' 600,180'" fill="url(#area-fill)" />
                            <polyline :points="points" fill="none" stroke="#67e8f9" stroke-width="2.5" vector-effect="non-scaling-stroke" />
                        </svg>
                        <div class="chart-x">
                            <span v-for="(day, i) in chart" :key="i">{{ day.label }}</span>
                        </div>
                    </div>
                </div>
                <div class="chart-bottom">
                    <span><i class="legend-dot"></i> Pesan pengunjung</span><Link href="/inbox">Buka percakapan <ArrowRight :size="14" /></Link>
                </div>
            </section>
            <section class="glass website-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">WEBSITE ANDA</p>
                        <h2>Siap menyambut cerita.</h2>
                    </div>
                    <span class="stat-icon teal"><Layers3 :size="17" /></span>
                </div>
                <div class="site-illustration">
                    <div class="mock-window">
                        <div class="mock-top"><i></i><i></i><i></i><span>amertanala.</span></div>
                        <div class="mock-hero">
                            <span>THE ART OF INVITATION</span><strong>Cerita Anda.<br /><em>Kesan yang tinggal.</em></strong>
                            <div class="mock-pill"></div>
                        </div>
                    </div>
                    <span class="illustration-orbit"></span>
                </div>
                <div class="website-status">
                    <span :class="['status-pill', stats.published ? 'success' : 'warning']"
                        ><CircleCheck v-if="stats.published" :size="13" /><Clock3 v-else :size="13" />{{
                            stats.published ? 'Konten diterbitkan' : 'Menunggu publikasi'
                        }}</span
                    ><span>{{ stats.themes }} tema</span>
                </div>
                <a :href="landingUrl" target="_blank" rel="noopener noreferrer" class="button secondary full"
                    >Kunjungi landing page <ArrowUpRight :size="15"
                /></a>
            </section>
            <section class="glass activity-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">CATATAN WORKSPACE</p>
                        <h2>Aktivitas terbaru</h2>
                    </div>
                    <Link href="/content" class="text-link">Lihat semua <ArrowUpRight :size="14" /></Link>
                </div>
                <div v-if="!activity.length" class="empty-state">
                    <span class="empty-icon"><Clock3 :size="23" /></span>
                    <h3>Awal yang baru.</h3>
                    <p>Aktivitas penyimpanan dan publikasi akan muncul di sini.</p>
                    <Link href="/content" class="text-link">Mulai kelola konten <ArrowRight :size="14" /></Link>
                </div>
                <div v-for="item in activity" :key="item.id" class="activity-row">
                    <span class="activity-dot"></span>
                    <div>
                        <strong>{{ labels[item.action] || item.action }}</strong>
                        <p>{{ item.name || 'Administrator' }}</p>
                    </div>
                    <time>{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</time>
                </div>
            </section>
            <section class="glass quick-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">LANGKAH BERIKUTNYA</p>
                        <h2>Akses cepat</h2>
                    </div>
                </div>
                <Link href="/content" class="quick-action"
                    ><span class="stat-icon cyan"><FilePenLine :size="18" /></span
                    ><span><strong>Perbarui landing page</strong><small>Simpan draf, tinjau, lalu terbitkan</small></span
                    ><ArrowUpRight :size="17" /></Link
                ><Link href="/media" class="quick-action"
                    ><span class="stat-icon violet"><ImagePlus :size="18" /></span
                    ><span><strong>Tambahkan media</strong><small>Lengkapi koleksi gambar Anda</small></span
                    ><ArrowUpRight :size="17" /></Link
                ><Link href="/inbox" class="quick-action"
                    ><span class="stat-icon teal"><MessagesSquare :size="18" /></span
                    ><span
                        ><strong>Sapa pengunjung</strong
                        ><small>{{ stats.unread ? stats.unread + ' percakapan menunggu' : 'Buka inbox live chat' }}</small></span
                    ><ArrowUpRight :size="17"
                /></Link>
            </section>
        </div>
    </WorkspaceLayout>
</template>
