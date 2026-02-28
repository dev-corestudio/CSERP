import { defineStore } from 'pinia'
import api from '@/services/api'
import {
    ORDER_STATUSES,
    PAYMENT_STATUSES,
    ORDER_PRIORITIES,
    ASSORTMENT_TYPES,
    WORKSTATION_TYPES,
    WORKSTATION_STATUSES,
    PRODUCTION_STATUSES,
    TEST_RESULTS,
    EVENT_TYPES,
    DELIVERY_STATUSES,
    INVOICE_STATUSES,
    PAYMENT_METHODS,
    ASSORTMENT_HISTORY_ACTIONS,
    USER_ROLES,
    CUSTOMER_TYPES,
    MATERIAL_STATUSES,
    VARIANT_STATUSES, // NOWY IMPORT
    VARIANT_TYPES, // NOWY IMPORT
} from '@/config/statusMappings'

interface MetadataItem {
    value: string;
    label: string;
    color?: string;
    icon?: string;
}

interface MetadataState {
    userRoles: MetadataItem[];
    customerTypes: MetadataItem[];
    assortmentTypes: MetadataItem[];
    units: any[];
    orderStatuses: MetadataItem[];
    paymentStatuses: MetadataItem[];
    orderPriorities: MetadataItem[];
    workstationTypes: MetadataItem[];
    workstationStatuses: MetadataItem[];
    productionStatuses: MetadataItem[];
    testResults: MetadataItem[];
    eventTypes: MetadataItem[];
    deliveryStatuses: MetadataItem[];
    invoiceStatuses: MetadataItem[];
    paymentMethods: MetadataItem[];
    assortmentHistoryActions: MetadataItem[];
    materialStatuses: MetadataItem[];
    categories: any[];

    // NOWE
    variantTypes: MetadataItem[];
    variantStatuses: MetadataItem[];

    loaded: boolean;
}

export const useMetadataStore = defineStore('metadata', {
    state: (): MetadataState => ({
        userRoles: [],
        customerTypes: [],
        assortmentTypes: [],
        units: [],
        orderStatuses: [],
        paymentStatuses: [],
        orderPriorities: [],
        workstationTypes: [],
        workstationStatuses: [],
        productionStatuses: [],
        testResults: [],
        eventTypes: [],
        deliveryStatuses: [],
        invoiceStatuses: [],
        paymentMethods: [],
        assortmentHistoryActions: [],
        materialStatuses: [],
        categories: [],

        variantTypes: [],
        variantStatuses: [],

        loaded: false
    }),

    actions: {
        async fetchMetadata() {
            if (this.loaded) return

            try {
                const { data } = await api.get('/metadata')

                const merge = (apiData: any[], uiConfig: any) => {
                    return apiData.map(item => ({
                        value: item.value,
                        label: item.label,
                        // Fix: sprawdź czy uiConfig[item.value] istnieje
                        color: uiConfig[item.value]?.color || item.color || 'grey',
                        icon: uiConfig[item.value]?.icon || item.icon || 'mdi-circle-small'
                    }))
                }

                // ... mapowania ...
                this.userRoles = merge(data.user_roles || [], USER_ROLES)
                this.customerTypes = merge(data.customer_types || [], CUSTOMER_TYPES)
                this.assortmentTypes = merge(data.assortment_types || [], ASSORTMENT_TYPES)
                this.units = data.units || []

                this.orderStatuses = merge(data.order_statuses || [], ORDER_STATUSES)
                this.paymentStatuses = merge(data.payment_statuses || [], PAYMENT_STATUSES)
                this.orderPriorities = merge(data.order_priorities || [], ORDER_PRIORITIES)

                this.workstationTypes = merge(data.workstation_types || [], WORKSTATION_TYPES)
                this.workstationStatuses = merge(data.workstation_statuses || [], WORKSTATION_STATUSES)

                this.productionStatuses = merge(data.production_statuses || [], PRODUCTION_STATUSES)
                this.testResults = merge(data.test_results || [], TEST_RESULTS)
                this.eventTypes = merge(data.event_types || [], EVENT_TYPES)

                this.deliveryStatuses = merge(data.delivery_statuses || [], DELIVERY_STATUSES)
                this.invoiceStatuses = merge(data.invoice_statuses || [], INVOICE_STATUSES)
                this.paymentMethods = merge(data.payment_methods || [], PAYMENT_METHODS)

                this.assortmentHistoryActions = merge(data.assortment_history_actions || [], ASSORTMENT_HISTORY_ACTIONS)
                this.materialStatuses = merge(data.material_statuses || [], MATERIAL_STATUSES)

                this.categories = data.assortment_categories || []

                // NOWE: Variant Metadata
                this.variantTypes = merge(data.variant_types || [], VARIANT_TYPES);
                this.variantStatuses = merge(data.variant_statuses || [], VARIANT_STATUSES);

                this.loaded = true
            } catch (e) {
                console.error('Błąd pobierania metadanych:', e)
            }
        },

        getConfig(groupName: keyof MetadataState, value: string) {
            if (!value || !this[groupName]) return null
            const group = this[groupName] as any[]
            const normalizedValue = String(value).toLowerCase()
            const found = group.find(item =>
                String(item.value).toLowerCase() === normalizedValue
            )
            return found || null
        },

        getLabel(groupName: keyof MetadataState, value: string) {
            const config = this.getConfig(groupName, value)
            return config ? config.label : (value || '-')
        },

        getColor(groupName: keyof MetadataState, value: string, defaultColor = 'grey') {
            const config = this.getConfig(groupName, value)
            return config ? config.color : defaultColor
        },

        getIcon(groupName: keyof MetadataState, value: string, defaultIcon = 'mdi-circle-small') {
            const config = this.getConfig(groupName, value)
            return config ? config.icon : defaultIcon
        }
    }
})