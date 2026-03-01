// src/config/statusMappings.ts

interface StatusConfig {
    color: string;
    icon: string;
}

interface StatusMap {
    [key: string]: StatusConfig;
}

// ============================================================================
// ZAMÓWIENIA
// ============================================================================

export const ORDER_STATUSES: StatusMap = {
    draft: {
        color: 'grey',
        icon: 'mdi-file-document-outline'
    },
    quotation: {
        color: 'blue',
        icon: 'mdi-calculator'
    },
    prototype: {
        color: 'purple',
        icon: 'mdi-test-tube'
    },
    production: {
        color: 'orange',
        icon: 'mdi-cog'
    },
    delivery: {
        color: 'cyan',
        icon: 'mdi-truck-delivery'
    },
    completed: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    cancelled: {
        color: 'red',
        icon: 'mdi-cancel'
    }
}

export const PAYMENT_STATUSES: StatusMap = {
    unpaid: {
        color: 'grey',
        icon: 'mdi-clock-outline'
    },
    partial: {
        color: 'orange',
        icon: 'mdi-clock-alert'
    },
    paid: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    overdue: {
        color: 'red',
        icon: 'mdi-alert-circle'
    }
}

export const ORDER_PRIORITIES: StatusMap = {
    LOW: {
        color: 'grey',
        icon: 'mdi-flag-outline'
    },
    NORMAL: {
        color: 'blue',
        icon: 'mdi-flag'
    },
    HIGH: {
        color: 'orange',
        icon: 'mdi-flag'
    },
    URGENT: {
        color: 'red',
        icon: 'mdi-fire'
    }
}

// ============================================================================
// ASORTYMENT
// ============================================================================

export const ASSORTMENT_TYPES: StatusMap = {
    material: {
        color: 'blue',
        icon: 'mdi-package-variant'
    },
    service: {
        color: 'orange',
        icon: 'mdi-wrench'
    }
}

// ============================================================================
// STANOWISKA
// ============================================================================

export const WORKSTATION_TYPES: StatusMap = {
    cnc: {
        color: 'blue',
        icon: 'mdi-laser-pointer'
    },
    laser: {
        color: 'red',
        icon: 'mdi-laser-pointer'
    },
    assembly: {
        color: 'green',
        icon: 'mdi-puzzle'
    },
    welding: {
        color: 'orange',
        icon: 'mdi-fire'
    },
    painting: {
        color: 'purple',
        icon: 'mdi-spray'
    },
    packaging: {
        color: 'brown',
        icon: 'mdi-package-variant'
    },
    quality_control: {
        color: 'teal',
        icon: 'mdi-magnify'
    },
    other: {
        color: 'grey',
        icon: 'mdi-wrench'
    }
}

export const WORKSTATION_STATUSES: StatusMap = {
    idle: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    active: {
        color: 'blue',
        icon: 'mdi-cog'
    },
    maintenance: {
        color: 'orange',
        icon: 'mdi-wrench'
    },
    offline: {
        color: 'red',
        icon: 'mdi-power-off'
    }
}

// ============================================================================
// PRODUKCJA
// ============================================================================

export const PRODUCTION_STATUSES: StatusMap = {
    planned: {
        color: 'grey',
        icon: 'mdi-clock-outline'
    },
    in_progress: {
        color: 'orange',
        icon: 'mdi-cog'
    },
    paused: {
        color: 'yellow',
        icon: 'mdi-pause'
    },
    completed: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    cancelled: {
        color: 'red',
        icon: 'mdi-cancel'
    }
}

export const TEST_RESULTS: StatusMap = {
    pending: {
        color: 'yellow',
        icon: 'mdi-clock-outline'
    },
    passed: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    failed: {
        color: 'red',
        icon: 'mdi-close-circle'
    }
}

export const EVENT_TYPES: StatusMap = {
    start: {
        color: 'green',
        icon: 'mdi-play'
    },
    pause: {
        color: 'orange',
        icon: 'mdi-pause'
    },
    resume: {
        color: 'blue',
        icon: 'mdi-play-circle'
    },
    stop: {
        color: 'red',
        icon: 'mdi-stop'
    }
}

// ============================================================================
// DOSTAWY I FAKTURY
// ============================================================================

export const DELIVERY_STATUSES: StatusMap = {
    scheduled: {
        color: 'blue',
        icon: 'mdi-calendar-clock'
    },
    in_transit: {
        color: 'orange',
        icon: 'mdi-truck'
    },
    delivered: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    cancelled: {
        color: 'red',
        icon: 'mdi-cancel'
    }
}

export const INVOICE_STATUSES: StatusMap = {
    issued: {
        color: 'blue',
        icon: 'mdi-file-document'
    },
    paid: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    overdue: {
        color: 'red',
        icon: 'mdi-alert'
    },
    cancelled: {
        color: 'grey',
        icon: 'mdi-cancel'
    }
}

export const PAYMENT_METHODS: StatusMap = {
    transfer: {
        color: 'blue',
        icon: 'mdi-bank-transfer'
    },
    cash: {
        color: 'green',
        icon: 'mdi-cash'
    },
    card: {
        color: 'purple',
        icon: 'mdi-credit-card'
    },
    other: {
        color: 'grey',
        icon: 'mdi-dots-horizontal'
    }
}

// ============================================================================
// HISTORIA
// ============================================================================

export const ASSORTMENT_HISTORY_ACTIONS: StatusMap = {
    created: {
        color: 'green',
        icon: 'mdi-plus-circle'
    },
    updated: {
        color: 'blue',
        icon: 'mdi-pencil'
    },
    deleted: {
        color: 'red',
        icon: 'mdi-delete'
    },
    activated: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    deactivated: {
        color: 'orange',
        icon: 'mdi-pause-circle'
    }
}

// ============================================================================
// ROLE I TYPY
// ============================================================================

export const USER_ROLES: StatusMap = {
    admin: {
        color: 'red',
        icon: 'mdi-shield-crown'
    },
    project_manager: {
        color: 'purple',
        icon: 'mdi-account-tie'
    },
    production_manager: {
        color: 'orange',
        icon: 'mdi-account-hard-hat'
    },
    trader: {
        color: 'blue',
        icon: 'mdi-handshake'
    },
    logistics_specialist: {
        color: 'cyan',
        icon: 'mdi-truck-delivery'
    },
    production_employee: {
        color: 'green',
        icon: 'mdi-account-wrench'
    }
}

export const CUSTOMER_TYPES: StatusMap = {
    b2b: {
        color: 'blue',
        icon: 'mdi-domain'
    },
    b2c: {
        color: 'purple',
        icon: 'mdi-account'
    }
}

// ============================================================================
// MATERIAŁY (NOWE)
// ============================================================================

export const MATERIAL_STATUSES: StatusMap = {
    NOT_ORDERED: {
        color: 'red',
        icon: 'mdi-cart-outline'
    },
    ORDERED: {
        color: 'orange',
        icon: 'mdi-truck-fast-outline'
    },
    PARTIALLY_IN_STOCK: {
        color: 'blue',
        icon: 'mdi-package-variant'
    },
    IN_STOCK: {
        color: 'green',
        icon: 'mdi-package-variant-closed-check'
    }
}

export const VARIANT_STATUSES: StatusMap = {
    DRAFT: {
        color: 'grey-lighten-1',
        icon: 'mdi-pencil-outline'
    },
    QUOTATION: {
        color: 'blue',
        icon: 'mdi-calculator'
    },
    PRODUCTION: {
        color: 'orange',
        icon: 'mdi-cog'
    },
    DELIVERY: {
        color: 'cyan',
        icon: 'mdi-truck-delivery'
    },
    COMPLETED: {
        color: 'green',
        icon: 'mdi-check-circle'
    },
    CANCELLED: {
        color: 'red-darken-4',
        icon: 'mdi-cancel'
    }
}

export const VARIANT_TYPES: StatusMap = {
    PROTOTYPE: {
        color: 'purple',
        icon: 'mdi-flask'
    },
    SERIAL: {
        color: 'blue',
        icon: 'mdi-factory'
    }
}
