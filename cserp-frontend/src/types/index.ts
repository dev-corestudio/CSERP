export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
}

export interface Customer {
    id: number;
    name: string;
    type: 'B2B' | 'B2C';
    nip?: string;
    email?: string;
    phone?: string;
    address?: string;
    is_active: boolean;
    stats?: {
        total_orders: number;
        active_orders: number;
        completed_orders: number;
        total_budget: number;
        paid_orders: number;
        unpaid_orders: number;
    };
}

export interface Project {
    id: number;
    project_number: string;
    series: string;
    full_project_number: string;
    customer_id: number;
    customer?: Customer;
    description: string;
    planned_delivery_date?: string;
    overall_status: string;
    payment_status: string;
    priority: string;
    created_at: string;
    updated_at: string;
    variants?: Variant[];
    materials_cost?: number;
    services_cost?: number;
    total_price?: number;
}

// ZMIANY W VARIANT:
export interface Variant {
    id: number;
    project_id: number;
    variant_number: string;
    name: string;
    description?: string; // NOWE
    quantity: number;
    type: 'PROTOTYPE' | 'SERIAL'; // NOWE
    status: string; // ZAMIAST status
    is_approved: boolean; // NOWE (dla prototypu)
    feedback_notes?: string; // NOWE

    // TKW z wyceny — koszt wytworzenia 1 szt. (auto przy zatwierdzeniu wyceny, można nadpisać)
    // TKW rzeczywiste NIE jest przechowywane — obliczane dynamicznie: (mat+usł) / ilość
    tkw_z_wyceny?: number | null;

    created_at: string;
    updated_at: string; // WAŻNE: data ostatniej aktualizacji

    production_order?: any;
    prototypes?: any[];
}

export interface AssortmentItem {
    id: number;
    name: string;
    type: 'material' | 'service';
    category: string;
    unit: string;
    default_price: number;
    description?: string;
    is_active: boolean;
}

export interface Workstation {
    id: number;
    name: string;
    type: string;
    status: string;
    location?: string;
    operators?: User[];
}

export interface ApiResponse<T> {
    data: T;
    message?: string;
    success?: boolean;
    meta?: {
        current_page: number;
        last_page: number;
        total: number;
    };
}