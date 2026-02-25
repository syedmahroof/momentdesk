export * from './auth';
export * from './navigation';
export * from './ui';

export type CustomerDate = {
    id: number;
    customer_id: number;
    type: 'birthday' | 'wedding' | 'work' | 'custom';
    title: string | null;
    date: string;
    reminder_days_before: number;
    active: boolean;
    auto_send: boolean;
    display_title: string;
    ordinal_years: string;
    years: number;
    days_until: number;
};

export type Customer = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    whatsapp_number: string | null;
    notes: string | null;
    dates?: CustomerDate[];
    dates_count?: number;
    upcoming_event?: { display_title: string; days_until: number; type: string } | null;
    created_at?: string;
};

export type Template = {
    id: number;
    name: string;
    type: 'birthday' | 'wedding' | 'work' | 'custom';
    channel: 'whatsapp' | 'email' | 'sms';
    subject: string | null;
    content: string;
    is_default: boolean;
};

export type MessageLog = {
    id: number;
    channel: string;
    message: string;
    status: 'pending' | 'sent' | 'failed' | 'delivered';
    sent_at: string | null;
    template?: Template;
};

export type DashboardStats = {
    total_customers: number;
    today_events: number;
    upcoming_events: number;
    sent_this_month: number;
};

export type TodayEvent = {
    id: number;
    customer_name: string;
    customer_id: number;
    display_title: string;
    type: string;
    years: number;
    ordinal_years: string;
    whatsapp_number: string | null;
    email: string | null;
    phone: string | null;
};

export type UpcomingEvent = {
    id: number;
    customer_name: string;
    customer_id: number;
    display_title: string;
    type: string;
    days_until: number;
    years: number;
    ordinal_years: string;
};
