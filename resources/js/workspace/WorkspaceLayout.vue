<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Bell,
    Images,
    LayoutDashboard,
    LogOut,
    Menu,
    MessagesSquare,
    PanelsTopLeft,
    Search,
    ShieldCheck,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { AdminUser } from './types';
defineProps<{ title: string; eyebrow?: string }>();
const page = usePage();
const user = computed(() => (page.props.auth as { user: AdminUser | null })?.user);
const flash = computed(() => (page.props.flash as { success?: string })?.success);
const open = ref(false);
const query = ref('');
const nav = [
    { url: '/dashboard', label: 'Ringkasan', icon: LayoutDashboard },
    { url: '/content', label: 'Konten website', icon: PanelsTopLeft },
    { url: '/media', label: 'Pustaka media', icon: Images },
    { url: '/inbox', label: 'Live chat', icon: MessagesSquare },
    { url: '/security', label: 'Keamanan', icon: ShieldCheck },
];
const active = (url: string) => page.url.split('?')[0] === url;
</script>
<template>
    <Head :title="title" />
    <div class="studio-shell">
        <button v-if="open" class="sidebar-backdrop" aria-label="Tutup navigasi" @click="open = false"></button>
        <aside class="studio-sidebar glass" :class="{ 'is-open': open }">
            <Link href="/dashboard" class="studio-brand"
                ><span class="brand-mark"><Sparkles :size="21" /></span
                ><span>amertanala<span class="brand-period">.</span><small>STUDIO WORKSPACE</small></span></Link
            >
            <div class="workspace-switch">
                <span class="workspace-avatar">A</span>
                <div>Amertanala Studio<small>Ruang kerja admin</small></div>
                <span class="tiny-dot"></span>
            </div>
            <p class="nav-caption">WORKSPACE</p>
            <nav aria-label="Navigasi utama">
                <Link
                    v-for="item in nav"
                    :key="item.url"
                    :href="item.url"
                    :class="['nav-item', { active: active(item.url) }]"
                    :aria-current="active(item.url) ? 'page' : undefined"
                    @click="open = false"
                    ><component :is="item.icon" :size="18" /><span>{{ item.label }}</span
                    ><span v-if="active(item.url)" class="active-dot"></span
                ></Link>
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-note">
                    <span class="live-dot"></span><span>Satu ruang. Semua cerita.<small>Kelola dengan lebih tenang.</small></span>
                </div>
                <Link href="/security" class="account-row"
                    ><span class="account-avatar">{{ user?.name?.slice(0, 1) || 'A' }}</span
                    ><span>{{ user?.name || 'Administrator' }}<small>Administrator</small></span></Link
                ><Link href="/logout" method="post" as="button" class="logout-button"><LogOut :size="15" /> Keluar</Link>
            </div>
        </aside>
        <main class="studio-main">
            <header class="studio-topbar glass">
                <div class="breadcrumbs">
                    <button class="mobile-menu icon-button" aria-label="Buka navigasi" :aria-expanded="open" @click="open = !open">
                        <component :is="open ? X : Menu" :size="20" /></button
                    ><span>Workspace</span><span class="breadcrumb-slash">/</span><strong>{{ title }}</strong>
                </div>
                <div class="topbar-actions">
                    <form action="/inbox" class="global-search">
                        <Search :size="15" /><input v-model="query" name="q" placeholder="Cari percakapan..." aria-label="Cari percakapan" /><kbd
                            >↵</kbd
                        >
                    </form>
                    <Link href="/inbox" class="icon-button" aria-label="Buka inbox"><Bell :size="18" /></Link
                    ><a href="http://localhost:8000" target="_blank" rel="noopener noreferrer" class="visit-link"
                        >Lihat website <ArrowUpRight :size="15"
                    /></a>
                </div>
            </header>
            <div v-if="flash" role="status" class="flash-success">{{ flash }}</div>
            <div class="page-content"><slot /></div>
            <footer class="studio-footer"><span>Amertanala Studio</span><span>Dibuat untuk cerita yang berarti.</span></footer>
        </main>
    </div>
</template>
