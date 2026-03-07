import { useMetadataStore } from '@/stores/metadata'

interface FormattedStatus {
    label: string;
    color: string;
    icon: string;
}

export function useStatusFormatter() {
    const metadataStore = useMetadataStore()

    const format = (groupName: string, value: string): FormattedStatus => {
        return {
            label: metadataStore.getLabel(groupName, value),
            color: metadataStore.getColor(groupName, value),
            icon: metadataStore.getIcon(groupName, value)
        }
    }

    return {
        // Projekty
        formatProjectStatus: (status: string) => format('projectStatuses', status),
        formatPaymentStatus: (status: string) => format('paymentStatuses', status),
        formatPriority: (priority: string) => format('projectPriorities', priority),

        // Warianty
        formatVariantStatus: (status: string) => format('variantStatuses', status),
        formatVariantType: (type: string) => format('variantTypes', type),

        // Asortyment
        formatAssortmentType: (type: string) => format('assortmentTypes', type),

        // Produkcja
        formatProductionStatus: (status: string) => format('productionStatuses', status),

        // Historia
        formatHistoryAction: (action: string) => format('assortmentHistoryActions', action),

        // Role i typy
        formatUserRole: (role: string) => format('userRoles', role),

        // Uniwersalne
        format
    }
}
