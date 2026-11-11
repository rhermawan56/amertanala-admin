<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Send, ShieldCheck, Sparkles } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import { api } from '../../workspace/api';
import { realtime } from '../../workspace/realtime';
import type { ChatMessage, Conversation } from '../../workspace/types';
import { useDelivery } from '../../workspace/useDelivery';
const props = defineProps<{ parentOrigins: string[] }>();
const panelVisible = ref(window.parent === window);
const visibility = (event: MessageEvent) => {
    if (
        event.source === window.parent &&
        props.parentOrigins.includes(event.origin) &&
        event.data?.type === 'amertanala-chat-visibility' &&
        typeof event.data.visible === 'boolean'
    ) {
        panelVisible.value = event.data.visible;
        if (panelVisible.value) void sync();
    }
};
const conversation = ref<Conversation | null>(null),
    messages = ref<ChatMessage[]>([]),
    name = ref(''),
    body = ref(''),
    email = ref(''),
    note = ref(''),
    scroller = ref<HTMLElement | null>(null),
    initializing = ref(true),
    website = ref(''),
    recoveryMode = ref('verify');
const themeId = new URLSearchParams(location.search).get('theme') || '';
const { busy, error, state, deliver } = useDelivery();
let timer: ReturnType<typeof setInterval>,
    syncing = false,
    channel = '';
function merge(incoming: ChatMessage[]) {
    const ids = new Set(messages.value.map((m) => m.id));
    messages.value.push(...incoming.filter((m) => !ids.has(m.id)));
    messages.value.sort((a, b) => a.id - b.id);
}
async function scroll() {
    await nextTick();
    scroller.value?.scrollTo({ top: scroller.value.scrollHeight, behavior: 'instant' });
}
async function sync() {
    if (!conversation.value || syncing || !panelVisible.value || document.visibilityState !== 'visible') return;
    syncing = true;
    try {
        const data = await api<{ messages: ChatMessage[]; conversation: Conversation }>('/chat/messages?after=' + (messages.value.at(-1)?.id || 0));
        conversation.value = data.conversation;
        merge(data.messages);
        if (data.messages.length) await scroll();
    } catch (e) {
        note.value = (e as Error).message;
    } finally {
        syncing = false;
    }
}
async function subscribe() {
    try {
        const data = await api<{ channel: string | null }>('/chat/current');
        if (data.channel) {
            channel = data.channel;
            realtime(true)
                ?.private(channel)
                .listen('.chat.updated', () => void sync());
        }
    } catch (e) {
        note.value = (e as Error).message;
    }
}
async function send() {
    if (!body.value.trim()) return;
    const sent = body.value;
    const result = await deliver(conversation.value ? '/chat/messages' : '/chat/start', sent, {
        name: name.value,
        theme_id: themeId,
        website: website.value,
    });
    if (result) {
        if (result.conversation) {
            conversation.value = result.conversation;
            subscribe();
        }
        merge([result.message]);
        if (body.value === sent) body.value = '';
        await scroll();
    }
}
async function recovery() {
    note.value = '';
    try {
        note.value = (
            await api<{ message: string }>('/chat/access', 'POST', {
                email: email.value,
                purpose: conversation.value ? recoveryMode.value : 'recover',
            })
        ).message;
    } catch (e) {
        note.value = (e as Error).message;
    }
}
onMounted(async () => {
    window.addEventListener('message', visibility);
    try {
        conversation.value = (await api<{ conversation: Conversation | null }>('/chat/current')).conversation;
        if (conversation.value) {
            subscribe();
            await sync();
        }
    } catch (e) {
        note.value = (e as Error).message;
    } finally {
        initializing.value = false;
    }
    timer = setInterval(() => void sync(), 15000);
});
onUnmounted(() => {
    window.removeEventListener('message', visibility);
    clearInterval(timer);
    if (channel) realtime(true)?.leave(channel);
});
</script>
<template>
    <Head title="Chat Amertanala" />
    <main class="guest-scene">
        <header class="chat-header">
            <div class="flex items-center gap-3">
                <span class="brand-mark"><Sparkles :size="20" /></span>
                <div>
                    <h2>Amertanala</h2>
                    <p>Mari bicarakan undangan Anda.</p>
                </div>
            </div>
            <ShieldCheck :size="18" class="text-cyan-200/70" />
        </header>
        <div v-if="initializing" class="empty-state"><p>Menghubungkan percakapan...</p></div>
        <section v-else-if="!conversation" class="guest-intro">
            <p class="eyebrow">SETIAP CERITA PUNYA AWAL</p>
            <h1>Halo, ada yang bisa<br />kami bantu?</h1>
            <p>Mulai konsultasi tanpa membuat akun. Tim kami akan membalas melalui percakapan ini.</p>
            <form class="guest-start-form" @submit.prevent="send">
                <label
                    >Nama Anda<input
                        v-model="name"
                        maxlength="100"
                        autocomplete="given-name"
                        required
                        placeholder="Bagaimana kami menyapa Anda?" /></label
                ><label
                    >Pesan<textarea
                        v-model="body"
                        maxlength="2000"
                        required
                        placeholder="Ceritakan rencana undangan Anda..."
                        :disabled="busy"
                    ></textarea></label
                ><label class="sr-only" aria-hidden="true">Website<input v-model="website" tabindex="-1" autocomplete="off" /></label
                ><button class="button primary full" :disabled="busy">{{ busy ? 'Mengirim...' : 'Mulai percakapan' }}<Send :size="15" /></button>
                <p class="muted small mt-3">Riwayat disimpan 90 hari tanpa aktivitas. Cookie menjaga akses Anda; jangan bagikan perangkat.</p>
            </form>
        </section>
        <template v-else
            ><p class="connection-note">
                {{ conversation.theme ? conversation.theme + ' · ' : '' }}Chat untuk {{ conversation.name }}. Balasan tampil otomatis.
            </p>
            <div ref="scroller" class="chat-messages" role="log" aria-live="polite" aria-label="Riwayat chat">
                <article v-for="message in messages" :key="message.id" :class="['message', { admin: message.sender === 'guest' }]">
                    {{ message.body
                    }}<small
                        >{{ message.sender === 'guest' ? 'Anda' : 'Amertanala' }} ·
                        {{ new Date(message.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</small
                    >
                </article>
            </div>
            <div class="chat-composer">
                <form @submit.prevent="send">
                    <label class="sr-only" for="guest-message">Pesan</label
                    ><textarea
                        id="guest-message"
                        v-model="body"
                        maxlength="2000"
                        required
                        :disabled="busy"
                        placeholder="Tulis pesan Anda..."
                        rows="2"
                    ></textarea
                    ><button class="button primary" aria-label="Kirim pesan" :disabled="busy || !body.trim()"><Send :size="17" /></button>
                </form>
                <p>{{ state || 'Draf tersimpan selama halaman ini terbuka. Memuat ulang dapat menghapusnya.' }}</p>
            </div></template
        >
        <p v-if="error" class="field-error" role="alert">{{ error }}</p>
        <details class="guest-recovery">
            <summary>{{ conversation ? 'Simpan akses melalui email' : 'Pulihkan percakapan lama' }}</summary>
            <p class="muted small mt-3">Tanpa email yang telah diverifikasi, akses tidak dapat dipulihkan setelah cookie hilang.</p>
            <form @submit.prevent="recovery">
                <label>Email<input v-model="email" type="email" autocomplete="email" required /></label
                ><button class="button secondary">Kirim tautan</button>
            </form>
        </details>
        <p v-if="note" class="connection-note" role="status">{{ note }}</p>
    </main>
</template>
