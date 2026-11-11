export interface AdminUser {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
}
export interface Theme {
    id: string;
    name: string;
    eyebrow: string;
    names: string[];
    caption: string;
    preview_url: string;
    alt: string;
}
export interface Feature {
    title: string;
    description: string;
}
export interface Content {
    [key: string]: string | boolean | Theme[] | Feature[];
    brand: string;
    title: string;
    description: string;
    contact_email: string;
    chat_enabled: boolean;
    themes: Theme[];
    features: Feature[];
}
export interface Conversation {
    id: string;
    name: string;
    theme: string | null;
    status: string;
    last_activity_at: string;
    unread_count?: number;
}
export interface ChatMessage {
    id: number;
    conversation_id: string;
    sender: 'admin' | 'guest';
    body: string;
    client_id: string;
    created_at: string;
}
export interface PageList<T> {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
}
