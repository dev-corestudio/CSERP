import {
    format,
    formatDistanceToNow,
    parseISO,
    isValid,
} from 'date-fns'
import { pl } from 'date-fns/locale'

// -----------------------------------------------------------------------------
// TYPY
// -----------------------------------------------------------------------------

export interface FormattersReturn {
    /** Formatuje liczbę jako PLN: 1234.5 → "1 234,50 PLN" */
    formatCurrency: (value: number | string | null | undefined) => string

    /** Formatuje datę: "2024-01-15T10:30:00Z" → "15.01.2024 10:30" */
    formatDate: (dateStr: string | null | undefined) => string

    /** Formatuje datę bez godziny: "2024-01-15T10:30:00Z" → "15.01.2024" */
    formatDateOnly: (dateStr: string | null | undefined) => string

    /** Formatuje sekundy jako HH:MM:SS: 3665 → "01:01:05" */
    formatDuration: (totalSeconds: number) => string

    /** Formatuje godziny jako HH:MM:SS: 1.0167 → "01:01:05" */
    formatHours: (hours: number) => string

    /** Konwertuje ISO na format datetime-local dla inputa HTML: "2024-01-15T10:30:00Z" → "2024-01-15T10:30" */
    toInputDate: (isoString: string | null | undefined) => string

    /** Formatuje względny czas: "2 godziny temu", "za 3 dni" */
    formatRelative: (dateStr: string | null | undefined) => string

    /** Formatuje wartość procentową z znakiem: -3.1 → "-3.1%", 5.2 → "+5.2%" */
    formatVariancePercent: (percent: number | null | undefined) => string

    /** Zwraca kolor Vuetify dla wariancji: green/red/warning */
    varianceColor: (percent: number | null | undefined) => string
}

// -----------------------------------------------------------------------------
// IMPLEMENTACJA
// -----------------------------------------------------------------------------

export function useFormatters(): FormattersReturn {

    // ---
    // formatCurrency
    // ---
    // Przed refaktorem: każdy komponent miał własną kopię:
    //   const formatCurrency = (value) => new Intl.NumberFormat('pl-PL', {...}).format(value)
    // ---
    const formatCurrency = (value: number | string | null | undefined): string => {
        const num = Number(value)
        if (isNaN(num)) return '0,00 PLN'

        return new Intl.NumberFormat('pl-PL', {
            style: 'currency',
            currency: 'PLN',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num)
    }

    // ---
    // formatDate
    // ---
    // Przed refaktorem: 3 różne implementacje używając new Date().toLocaleString()
    // Teraz używamy date-fns (już zainstalowane w projekcie!) dla spójności
    // ---
    const formatDate = (dateStr: string | null | undefined): string => {
        if (!dateStr) return '-'

        try {
            // Obsługujemy zarówno ISO string jak i inne formaty
            const date = typeof dateStr === 'string' ? parseISO(dateStr) : new Date(dateStr)
            if (!isValid(date)) return '-'

            return format(date, 'dd.MM.yyyy HH:mm', { locale: pl })
        } catch {
            return '-'
        }
    }

    // ---
    // formatDateOnly
    // ---
    const formatDateOnly = (dateStr: string | null | undefined): string => {
        if (!dateStr) return '-'

        try {
            const date = typeof dateStr === 'string' ? parseISO(dateStr) : new Date(dateStr)
            if (!isValid(date)) return '-'

            return format(date, 'dd.MM.yyyy', { locale: pl })
        } catch {
            return '-'
        }
    }

    // ---
    // formatDuration
    // ---
    // Przed refaktorem: identyczna logika padStart była w:
    //   - useTimer.ts (formattedTime computed)
    //   - stores/timer.ts (formattedTime computed)
    //   - VariantDetail.vue (liveTimerDisplay computed)
    //   - TimeLogsDialog.vue (formatDuration)
    // ---
    const formatDuration = (totalSeconds: number): string => {
        if (!totalSeconds || totalSeconds < 0) return '00:00:00'

        const h = Math.floor(totalSeconds / 3600)
        const m = Math.floor((totalSeconds % 3600) / 60)
        const s = Math.floor(totalSeconds % 60)

        return [h, m, s].map(v => String(v).padStart(2, '0')).join(':')
    }

    // ---
    // formatHours
    // ---
    // Konwertuje godziny (np. 1.5) na HH:MM:SS
    // Używane w liveTimerDisplay w VariantDetail zamiast ręcznych obliczeń
    // ---
    const formatHours = (hours: number): string => {
        if (!hours || hours < 0) return '00:00:00'
        return formatDuration(Math.floor(hours * 3600))
    }

    // ---
    // toInputDate
    // ---
    // Przed refaktorem: identyczna funkcja w TaskEditDialog.vue i TimeLogsDialog.vue:
    //   const toInputDate = (isoString) => {
    //     if (!isoString) return ''
    //     const date = new Date(isoString)
    //     const tzOffset = date.getTimezoneOffset() * 60000
    //     return new Date(date.getTime() - tzOffset).toISOString().slice(0, 16)
    //   }
    // Teraz używamy date-fns
    // ---
    const toInputDate = (isoString: string | null | undefined): string => {
        if (!isoString) return ''

        try {
            const date = parseISO(isoString)
            if (!isValid(date)) return ''

            // Format dla datetime-local: "yyyy-MM-ddTHH:mm"
            return format(date, "yyyy-MM-dd'T'HH:mm")
        } catch {
            return ''
        }
    }

    // ---
    // formatRelative
    // ---
    const formatRelative = (dateStr: string | null | undefined): string => {
        if (!dateStr) return '-'

        try {
            const date = parseISO(dateStr)
            if (!isValid(date)) return '-'

            return formatDistanceToNow(date, { locale: pl, addSuffix: true })
        } catch {
            return '-'
        }
    }

    // ---
    // formatVariancePercent
    // ---
    const formatVariancePercent = (percent: number | null | undefined): string => {
        if (percent == null) return '-'
        const sign = percent > 0 ? '+' : ''
        return `${sign}${Number(percent).toFixed(1)}%`
    }

    // ---
    // varianceColor
    // ---
    // Przed refaktorem: logika kolorów wariancji była w useTimer.ts
    // Tutaj jest centralne miejsce, używane też w RCP admin tabeli
    // ---
    const varianceColor = (percent: number | null | undefined): string => {
        if (percent == null) return 'grey'
        if (percent < -10) return 'success'  // szybciej niż plan — zielony
        if (percent > 10) return 'error'      // wolniej o >10% — czerwony
        if (percent < 0) return 'success'     // trochę szybciej
        if (percent > 0) return 'warning'     // trochę wolniej
        return 'success'                      // dokładnie na czas
    }

    return {
        formatCurrency,
        formatDate,
        formatDateOnly,
        formatDuration,
        formatHours,
        toInputDate,
        formatRelative,
        formatVariancePercent,
        varianceColor,
    }
}

// =============================================================================
// EKSPORT FUNKCJI STANDALONE (bez composable, dla store/service)
// =============================================================================
// Używaj gdy potrzebujesz formatowania poza komponentem Vue (np. w store)

export const formatCurrency = (value: number | string | null | undefined): string => {
    const num = Number(value)
    if (isNaN(num)) return '0,00 PLN'
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(num)
}

export const formatDuration = (totalSeconds: number): string => {
    if (!totalSeconds || totalSeconds < 0) return '00:00:00'
    const h = Math.floor(totalSeconds / 3600)
    const m = Math.floor((totalSeconds % 3600) / 60)
    const s = Math.floor(totalSeconds % 60)
    return [h, m, s].map(v => String(v).padStart(2, '0')).join(':')
}

export const formatDate = (dateStr: string | null | undefined): string => {
    if (!dateStr) return '-'
    try {
        const date = parseISO(dateStr)
        if (!isValid(date)) return '-'
        return format(date, 'dd.MM.yyyy HH:mm', { locale: pl })
    } catch {
        return '-'
    }
}