import { ref } from 'vue';
import { api, ApiError } from './api';
import type { ChatMessage } from './types';
export function useDelivery() {
    const busy = ref(false),
        error = ref(''),
        state = ref('');
    let pending: { body: string; client_id: string } | null = null;
    async function deliver(url: string, body: string, extra: Record<string, unknown> = {}) {
        if (busy.value) return null;
        busy.value = true;
        error.value = '';
        state.value = 'Mengirim';
        if (!pending || pending.body !== body) pending = { body, client_id: crypto.randomUUID() };
        try {
            for (let attempt = 0; attempt <= 3; attempt++) {
                try {
                    const result = await api<{ message: ChatMessage; conversation?: import('./types').Conversation }>(url, 'POST', {
                        ...extra,
                        ...pending,
                    });
                    pending = null;
                    state.value = 'Terkirim';
                    return result;
                } catch (e) {
                    if (attempt === 3 || (e instanceof ApiError && e.status < 500)) throw e;
                    state.value = 'Menghubungkan ulang…';
                    await new Promise((resolve) => setTimeout(resolve, Math.min(30000, 1000 * 2 ** attempt) + Math.random() * 400));
                }
            }
        } catch (e) {
            error.value = (e as Error).message;
            state.value = 'Gagal. Coba lagi.';
        } finally {
            busy.value = false;
        }
        return null;
    }
    return { busy, error, state, deliver };
}
