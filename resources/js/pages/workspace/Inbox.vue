<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCheck, Search, Send } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import WorkspaceLayout from '../../workspace/WorkspaceLayout.vue';
import { api } from '../../workspace/api';
import { realtime } from '../../workspace/realtime';
import type { ChatMessage, Conversation, PageList } from '../../workspace/types';
import { useDelivery } from '../../workspace/useDelivery';
const props = defineProps<{ conversations: PageList<Conversation>; active: Conversation | null; messages: ChatMessage[]; query: string }>();
const innerWidth = ref(window.innerWidth);
const resized = () => {
    innerWidth.value = window.innerWidth;
};
const list = ref<ChatMessage[]>([...props.messages]),
    body = ref(''),
    query = ref(props.query),
    scroller = ref<HTMLElement | null>(null),
    showList = ref(!new URLSearchParams(location.search).has('conversation')),
    pollError = ref(''),
    olderBusy = ref(false),
    hasOlder = ref(props.messages.length === 100);
const { busy, error, state, deliver } = useDelivery();
let timer: ReturnType<typeof setInterval>,
    syncing = false;
function merge(incoming: ChatMessage[]) {
    const known = new Set(list.value.map((m) => m.id));
    list.value.push(...incoming.filter((m) => !known.has(m.id)));
    list.value.sort((a, b) => a.id - b.id);
}
async function scroll() {
    await nextTick();
    scroller.value?.scrollTo({ top: scroller.value.scrollHeight, behavior: 'instant' });
}
async function markRead() {
    if (props.active && document.visibilityState === 'visible' && (!showList.value || innerWidth.value > 760) && list.value.length)
        await api('/inbox/' + props.active.id + '/read', 'POST', { id: list.value.at(-1)?.id });
}
async function sync() {
    if (syncing || !props.active || document.visibilityState !== 'visible') return;
    syncing = true;
    try {
        const result = await api<{ messages: ChatMessage[] }>('/inbox/' + props.active.id + '/messages?after=' + (list.value.at(-1)?.id || 0));
        merge(result.messages);
        if (result.messages.length) await scroll();
        await markRead();
        pollError.value = '';
    } catch (e) {
        pollError.value = (e as Error).message;
    } finally {
        syncing = false;
    }
}
async function older() {
    if (!props.active || !list.value.length) return;
    olderBusy.value = true;
    try {
        const result = await api<{ messages: ChatMessage[] }>('/inbox/' + props.active.id + '/messages?before=' + list.value[0].id);
        merge(result.messages);
        hasOlder.value = result.messages.length === 100;
    } catch (e) {
        pollError.value = (e as Error).message;
    } finally {
        olderBusy.value = false;
    }
}
async function send() {
    if (!props.active || !body.value.trim()) return;
    const sent = body.value;
    const result = await deliver('/inbox/' + props.active.id + '/messages', sent);
    if (result) {
        merge([result.message]);
        if (body.value === sent) body.value = '';
        await scroll();
    }
}
function filter() {
    router.get('/inbox', { q: query.value }, { preserveState: false });
}
function subscribe() {
    realtime()
        ?.private('admin.inbox')
        .listen('.chat.updated', () => {
            void sync();
            router.reload({ only: ['conversations'] });
        });
}
watch(
    () => props.active?.id,
    () => {
        list.value = [...props.messages];
        body.value = '';
        hasOlder.value = props.messages.length === 100;
        void scroll();
        void markRead();
    },
);
onMounted(() => {
    window.addEventListener('resize', resized);
    void scroll();
    void markRead();
    subscribe();
    timer = setInterval(() => {
        void sync();
        if (document.visibilityState === 'visible') router.reload({ only: ['conversations'] });
    }, 15000);
});
onUnmounted(() => {
    window.removeEventListener('resize', resized);
    clearInterval(timer);
    realtime()?.leave('admin.inbox');
});
</script>
<template>
    <WorkspaceLayout title="Live chat"
        ><section class="page-heading">
            <div>
                <p class="eyebrow">CONVERSATION SPACE</p>
                <h1>Setiap sapaan berarti<span class="heading-dot">.</span></h1>
                <p class="subheading">Balas pengunjung dalam percakapan yang tepat. Nama dan tema membantu menjaga konteks.</p>
            </div>
            <span class="chip">Inbox pengunjung</span>
        </section>
        <section class="glass inbox-layout" :class="{ 'has-active': active && !showList }">
            <aside class="conversation-list">
                <form class="conversation-search" @submit.prevent="filter">
                    <label class="sr-only" for="inbox-search">Cari nama atau tema</label
                    ><input id="inbox-search" v-model="query" placeholder="Cari nama atau tema..." /><button type="submit" class="text-link mt-2">
                        <Search :size="12" /> Cari percakapan
                    </button>
                </form>
                <Link
                    v-for="item in conversations.data"
                    :key="item.id"
                    :href="'/inbox?conversation=' + item.id + '&q=' + encodeURIComponent(query)"
                    :class="['conversation-item', { selected: active?.id === item.id }]"
                    @click="showList = false"
                    ><span class="conversation-avatar">{{ item.name.slice(0, 1).toUpperCase() }}</span
                    ><span class="conversation-copy"
                        ><strong>{{ item.name }}</strong
                        ><small>{{ item.theme || 'Konsultasi undangan' }}</small
                        ><small
                            >{{ item.status === 'closed' ? 'Selesai' : 'Aktif' }} ·
                            {{ new Date(item.last_activity_at).toLocaleDateString('id-ID') }}</small
                        ></span
                    ><span v-if="item.unread_count" class="unread-badge">{{ item.unread_count }}</span></Link
                >
                <div v-if="!conversations.data.length" class="empty-state">
                    <p>Belum ada percakapan{{ query ? ' yang cocok' : '' }}.</p>
                </div>
                <nav class="pagination">
                    <Link v-if="conversations.prev_page_url" :href="conversations.prev_page_url" class="text-link">Kembali</Link
                    ><Link v-if="conversations.next_page_url" :href="conversations.next_page_url" class="text-link">Lanjut</Link>
                </nav>
            </aside>
            <div v-if="active" class="chat-panel" :style="showList && innerWidth <= 760 ? 'display:none' : ''">
                <header class="chat-header">
                    <div class="flex items-center gap-3">
                        <button class="icon-button inbox-back" style="display: none" aria-label="Kembali ke daftar" @click="showList = true">
                            <ArrowLeft :size="18" />
                        </button>
                        <div>
                            <h2>{{ active.name }}</h2>
                            <p>{{ active.theme || 'Konsultasi undangan' }} · #{{ active.id.slice(0, 8) }}</p>
                        </div>
                    </div>
                    <Link
                        v-if="active.status !== 'closed'"
                        :href="'/inbox/' + active.id + '/close'"
                        method="post"
                        as="button"
                        class="button secondary"
                        ><CheckCheck :size="14" /> Selesai</Link
                    >
                </header>
                <div ref="scroller" class="chat-messages" aria-label="Riwayat percakapan" role="log" aria-live="polite">
                    <button v-if="hasOlder" class="text-link self-center" :disabled="olderBusy" @click="older">Muat pesan sebelumnya</button>
                    <article v-for="message in list" :key="message.id" :class="['message', { admin: message.sender === 'admin' }]">
                        {{ message.body
                        }}<small
                            >{{ message.sender === 'admin' ? 'Admin' : 'Pengunjung' }} ·
                            {{ new Date(message.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</small
                        >
                    </article>
                </div>
                <div class="chat-composer">
                    <form @submit.prevent="send">
                        <label class="sr-only" for="reply">Balasan</label
                        ><textarea
                            id="reply"
                            v-model="body"
                            placeholder="Tulis balasan yang hangat..."
                            maxlength="2000"
                            rows="2"
                            :disabled="busy"
                        ></textarea
                        ><button class="button primary" :disabled="busy || !body.trim()" aria-label="Kirim balasan"><Send :size="17" /></button>
                    </form>
                    <p>{{ body.length }}/2000 · {{ state || 'Pesan tersimpan sebelum dinyatakan terkirim.' }}</p>
                    <p v-if="error || pollError" class="field-error" role="alert">{{ error || pollError }}</p>
                </div>
            </div>
            <div v-else class="inbox-empty">
                <div class="empty-state">
                    <h2>Ruang untuk percakapan baru.</h2>
                    <p>Pesan pengunjung akan muncul di sini saat chat dimulai dari landing page.</p>
                </div>
            </div>
        </section></WorkspaceLayout
    >
</template>
