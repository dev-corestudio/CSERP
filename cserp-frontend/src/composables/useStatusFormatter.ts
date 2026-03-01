import { useMetadataStore } from '@/stores/metadata'

interface FormattedStatus {
    label: string;
    color: string;
    icon: string;
}

export function useStatusFormatter() {
    const metadataStore = useMetadataStore()

    const format = (groupName: string, value: string): FormattedStatus => {
        const config = metadataStore.getConfig(groupName, value)
        return {
            label: config?.label ?? (value || '-'),
            color: config?.color ?? 'grey',
            icon: config?.icon ?? 'mdi-circle-small',
        }
    }

    return {
        // Projekty
        formatProjectStatus: (status: string) => format('projectStatuses', status),
        formatPaymentStatus: (status: string) => format('paymentStatuses', status),
        formatPriority: (priority: string) => format('projectPriorities', priority),

        // WARIANTY (NOWE)
        formatVariantStatus: (status: string) => format('variantStatuses', status),
        formatVariantType: (type: string) => format('variantTypes', type),

        // Asortyment
        formatAssortmentType: (type: string) => format('assortmentTypes', type),

        // Stanowiska
        formatWorkstationType: (type: string) => format('workstationTypes', type),
        formatWorkstationStatus: (status: string) => format('workstationStatuses', status),

        // Produkcja
        formatProductionStatus: (status: string) => format('productionStatuses', status),
        formatTestResult: (result: string) => format('testResults', result),
        formatEventType: (type: string) => format('eventTypes', type),

        // Dostawy i faktury
        formatDeliveryStatus: (status: string) => format('deliveryStatuses', status),
        formatInvoiceStatus: (status: string) => format('invoiceStatuses', status),
        formatPaymentMethod: (method: string) => format('paymentMethods', method),

        // Historia
        formatHistoryAction: (action: string) => format('assortmentHistoryActions', action),

        // Role i typy
        formatUserRole: (role: string) => format('userRoles', role),
        formatCustomerType: (type: string) => format('customerTypes', type),

        // Materiały
        formatMaterialStatus: (status: string) => format('materialStatuses', status),

        // Uniwersalne
        format
    }
}
