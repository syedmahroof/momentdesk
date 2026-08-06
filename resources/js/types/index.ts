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
    from_lead?: boolean;
    lead_source_label?: string | null;
    created_at?: string;
};

export type LeadStatus = 'new' | 'contacted' | 'qualified' | 'won' | 'lost';

export type LeadSource =
    | 'walk_in'
    | 'referral'
    | 'instagram'
    | 'whatsapp'
    | 'phone_call'
    | 'other';

export type Lead = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    whatsapp_number: string | null;
    source: LeadSource;
    source_label: string;
    status: LeadStatus;
    status_label: string;
    status_badge_classes: string;
    follow_up_at: string | null;
    follow_up_overdue: boolean;
    notes: string | null;
    is_converted: boolean;
    customer: { id: number; name: string } | null;
    created_at?: string;
};

export type LeadStatusOption = {
    value: LeadStatus;
    label: string;
    badge_classes: string;
};

export type LeadSourceOption = {
    value: LeadSource;
    label: string;
};

/**
 * Shape backing the create/edit lead form. Dates and nullable columns are
 * empty strings rather than null so they bind cleanly to inputs. Status is
 * absent by design — it only moves via the dedicated status action.
 */
export type LeadFormFields = {
    name: string;
    phone: string;
    email: string;
    whatsapp_number: string;
    source: LeadSource;
    follow_up_at: string;
    notes: string;
};

export type FlyerElement = {
    id: string;
    type: 'text' | 'image';
    key: string | null;
    label: string;
    content: string | null;
    placeholder: string | null;
    x: number;
    y: number;
    width: number | null;
    height: number | null;
    font_size: number | null;
    color: string | null;
    alignment: 'left' | 'center' | 'right' | null;
    font_weight: 'normal' | 'medium' | 'semibold' | 'bold' | null;
};

export type FlyerTemplate = {
    id: number;
    title: string;
    category: 'daily_gold_rate' | 'daily_wishes' | 'product_rate' | 'custom';
    paper_size: 'a4' | 'custom';
    canvas_width: number;
    canvas_height: number;
    background_type: 'color' | 'image';
    background_color: string | null;
    background_image_path?: string | null;
    background_image_url: string | null;
    elements: FlyerElement[];
    is_active?: boolean;
    created_at?: string;
};

export type Flyer = {
    id: number;
    title: string;
    paper_size: 'a4' | 'custom';
    canvas_width: number;
    canvas_height: number;
    field_values: Record<string, string>;
    element_overrides: Record<string, Partial<FlyerElement>>;
    asset_paths: Record<string, string>;
    asset_urls: Record<string, string>;
    template_snapshot: Omit<FlyerTemplate, 'id' | 'is_active' | 'created_at'> & { background_image_path?: string | null };
    flyer_template?: { id: number; title: string } | null;
    created_at?: string;
};

export type MessageLog = {
    id: number;
    channel: string;
    message: string;
    status: 'pending' | 'sent' | 'failed' | 'delivered';
    sent_at: string | null;
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
