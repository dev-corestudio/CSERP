<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="1200"
    scrollable
  >
    <v-card v-if="quotation">
      <!-- Header -->
      <v-card-title class="bg-primary text-white pa-4">
        <div class="d-flex align-center justify-space-between w-100">
          <div class="d-flex align-center">
            <v-icon start color="white" size="large">mdi-file-document-outline</v-icon>
            <div>
              <div class="text-h5 font-weight-bold">
                Wycena - Wersja {{ quotation.version_number }}
              </div>
              <div class="text-caption opacity-90 mt-1">
                Utworzono: {{ formatDate(quotation.created_at) }}
              </div>
            </div>
          </div>
          <v-chip
            v-if="quotation.is_approved"
            color="success"
            size="large"
            class="font-weight-bold"
            variant="flat"
          >
            <v-icon start size="small">mdi-check-decagram</v-icon>
            Zatwierdzona
          </v-chip>
          <v-chip v-else size="large" class="font-weight-bold">
            <v-icon start size="small">mdi-clock-outline</v-icon>
            Oczekująca
          </v-chip>
        </div>
      </v-card-title>
      <div class="px-6 pt-4">
        <!-- Informacje o zatwierdzeniu - jeden Wariant -->
        <v-alert
          v-if="quotation.is_approved"
          type="success"
          variant="tonal"
          prominent
          border="start"
          class="mb-4"
          icon="mdi-check-decagram"
        >
          <div class="text-body-1">
            <strong
              >Wycena zatwierdzona dnia
              {{ formatApprovalDate(quotation.approved_at) }} przez
              {{ quotation.approved_by?.name || "System" }}</strong
            >
          </div>
        </v-alert>
      </div>

      <v-card-text class="px-6 pt-0">
        <!-- Podsumowanie finansowe - 4 kolumny w jednym rzędzie -->
        <div class="summary-grid mb-6">
          <!-- Materiały -->
          <v-card variant="flat" color="grey-lighten-3" class="pa-4">
            <div class="text-caption text-medium-emphasis mb-2">Materiały</div>
            <div class="text-h5 font-weight-bold text-grey-darken-3">
              {{ formatCurrency(quotation.total_materials_cost) }}
            </div>
          </v-card>

          <!-- Usługi -->
          <v-card variant="flat" color="grey-lighten-3" class="pa-4">
            <div class="text-caption text-medium-emphasis mb-2">Usługi</div>
            <div class="text-h5 font-weight-bold text-grey-darken-3">
              {{ formatCurrency(quotation.total_services_cost) }}
            </div>
          </v-card>

          <!-- Netto -->
          <v-card variant="flat" color="blue-lighten-4" class="pa-4">
            <div class="text-caption text-grey-darken-2 mb-2">Netto</div>
            <div class="text-h5 font-weight-bold text-blue-darken-3">
              {{ formatCurrency(quotation.total_net) }}
            </div>
            <div class="text-caption text-grey-darken-1 mt-1">
              Marża: {{ quotation.margin_percent }}%
            </div>
          </v-card>

          <!-- Brutto -->
          <v-card variant="flat" color="green-lighten-4" class="pa-4">
            <div class="text-caption text-grey-darken-2 mb-2">Brutto</div>
            <div class="text-h4 font-weight-bold text-green-darken-3">
              {{ formatCurrency(quotation.total_gross) }}
            </div>
            <div class="text-caption text-grey-darken-1 mt-1">23% VAT</div>
          </v-card>
        </div>

        <!-- Materiały -->
        <v-card class="mb-6 overflow-hidden" elevation="0" outlined>
          <v-card-title class="bg-grey-lighten-3 d-flex align-center pa-4">
            <v-icon start color="grey-darken-3" size="28">mdi-package-variant</v-icon>
            <span class="text-h6 font-weight-bold">Materiały</span>
            <v-chip size="small" color="grey-darken-1" class="ml-2">
              {{ totalMaterialsCount }} poz.
            </v-chip>
            <v-spacer />
            <span class="text-h6 font-weight-bold">
              {{ formatCurrency(quotation.total_materials_cost) }}
            </span>
          </v-card-title>

          <table class="quotation-table">
            <thead>
              <tr>
                <th style="width: 60px; text-align: center">LP</th>
                <th style="width: 35%; text-align: left">NAZWA MATERIAŁU</th>
                <th style="width: 20%; text-align: left">KATEGORIA</th>
                <th style="width: 10%; text-align: right">ILOŚĆ</th>
                <th style="width: 8%; text-align: center">JEDN.</th>
                <th style="width: 12%; text-align: right">CENA JEDN.</th>
                <th style="width: 15%; text-align: right">WARTOŚĆ</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="item in quotation.items" :key="`mat-item-${item.id}`">
                <tr
                  v-for="(material, idx) in item.materials"
                  :key="`mat-${material.id}`"
                  :class="{ 'row-even': idx % 2 === 0 }"
                >
                  <td style="text-align: center">{{ idx + 1 }}</td>
                  <td style="text-align: left">
                    <strong>{{ material.assortment_item?.name || "Brak nazwy" }}</strong>
                  </td>
                  <td style="text-align: left">
                    {{ material.assortment_item?.category || "-" }}
                  </td>
                  <td style="text-align: right">
                    <strong>{{ formatNumber(material.quantity) }}</strong>
                  </td>
                  <td style="text-align: center">
                    <v-chip size="x-small" variant="tonal" color="grey">
                      {{ material.unit }}
                    </v-chip>
                  </td>
                  <td style="text-align: right">
                    {{ formatCurrency(material.unit_price) }}
                  </td>
                  <td style="text-align: right">
                    <strong>{{ formatCurrency(material.total_cost) }}</strong>
                  </td>
                </tr>
              </template>
              <tr v-if="totalMaterialsCount === 0">
                <td colspan="7" style="text-align: center; padding: 24px">
                  Brak materiałów
                </td>
              </tr>
            </tbody>
          </table>
        </v-card>

        <!-- Usługi -->
        <v-card class="mb-6 overflow-hidden" elevation="0" outlined>
          <v-card-title class="bg-blue-lighten-5 d-flex align-center pa-4">
            <v-icon start color="blue-darken-2" size="28">mdi-wrench</v-icon>
            <span class="text-h6 font-weight-bold">Usługi</span>
            <v-chip size="small" color="blue-darken-1" class="ml-2">
              {{ totalServicesCount }} poz.
            </v-chip>
            <v-spacer />
            <span class="text-h6 font-weight-bold">
              {{ formatCurrency(quotation.total_services_cost) }}
            </span>
          </v-card-title>

          <table class="quotation-table">
            <thead>
              <tr>
                <th style="width: 60px; text-align: center">LP</th>
                <th style="width: 30%; text-align: left">NAZWA USŁUGI</th>
                <th style="width: 20%; text-align: left">KATEGORIA</th>
                <th style="width: 10%; text-align: right">ILOŚĆ</th>
                <th style="width: 10%; text-align: right">CZAS (H)</th>
                <th style="width: 12%; text-align: right">STAWKA /H</th>
                <th style="width: 15%; text-align: right">WARTOŚĆ</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="item in quotation.items" :key="`srv-item-${item.id}`">
                <tr
                  v-for="(service, idx) in item.services"
                  :key="`srv-${service.id}`"
                  :class="{ 'row-even': idx % 2 === 0 }"
                >
                  <td style="text-align: center">{{ idx + 1 }}</td>
                  <td style="text-align: left">
                    <strong>{{ service.assortment_item?.name || "Brak nazwy" }}</strong>
                  </td>
                  <td style="text-align: left">
                    {{ service.assortment_item?.category || "-" }}
                  </td>
                  <td style="text-align: right">
                    <strong>{{ formatNumber(service.estimated_quantity) }}</strong>
                  </td>
                  <td style="text-align: right">
                    <v-chip size="x-small" color="blue" variant="tonal">
                      {{ formatNumber(service.estimated_time_hours) }} h
                    </v-chip>
                  </td>
                  <td style="text-align: right">
                    {{ formatCurrency(service.unit_price) }}
                  </td>
                  <td style="text-align: right">
                    <strong>{{ formatCurrency(service.total_cost) }}</strong>
                  </td>
                </tr>
              </template>
              <tr v-if="totalServicesCount === 0">
                <td colspan="7" style="text-align: center; padding: 24px">Brak usług</td>
              </tr>
            </tbody>
          </table>
        </v-card>

        <!-- Notatki -->
        <v-card v-if="quotation.notes" variant="outlined" class="mb-4">
          <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
            <v-icon start size="small">mdi-note-text</v-icon>
            Notatki
          </v-card-title>
          <v-card-text class="pa-4">
            {{ quotation.notes }}
          </v-card-text>
        </v-card>
      </v-card-text>

      <!-- Footer -->
      <v-divider />
      <v-card-actions class="px-6 py-4 bg-grey-lighten-4">
        <v-btn
          variant="outlined"
          prepend-icon="mdi-download"
          :loading="loadingPdf"
          size="large"
          @click="downloadPdf"
        >
          POBIERZ PDF
        </v-btn>
        <v-spacer />
        <v-btn variant="elevated" color="primary" size="large" @click="$emit('close')">
          ZAMKNIJ
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- Loading state -->
    <v-card v-else>
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" />
        <p class="mt-4 text-h6">Ładowanie wyceny...</p>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useQuotationsStore } from "@/stores/quotations";
import { format } from "date-fns";

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  quotation: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "close"]);

const quotationsStore = useQuotationsStore();
const loadingPdf = ref(false);

// Obliczanie łącznej liczby materiałów
const totalMaterialsCount = computed(() => {
  if (!props.quotation?.items) return 0;
  return props.quotation.items.reduce((sum, item) => {
    return sum + (item.materials?.length || 0);
  }, 0);
});

// Obliczanie łącznej liczby usług
const totalServicesCount = computed(() => {
  if (!props.quotation?.items) return 0;
  return props.quotation.items.reduce((sum, item) => {
    return sum + (item.services?.length || 0);
  }, 0);
});

// Formatowanie walut - ZAWSZE konwertuj na number!
const formatCurrency = (value) => {
  // Upewnij się że value jest liczbą
  let numValue = value;
  if (typeof value === "string") {
    numValue = parseFloat(value.replace(/[^\d.-]/g, ""));
  }
  numValue = Number(numValue) || 0;

  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numValue);
};

// Formatowanie liczb
const formatNumber = (value) => {
  let numValue = value;
  if (typeof value === "string") {
    numValue = parseFloat(value.replace(/[^\d.-]/g, ""));
  }
  numValue = Number(numValue) || 0;

  return new Intl.NumberFormat("pl-PL", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numValue);
};

// Formatowanie daty utworzenia (z sekundami)
const formatDate = (date) => {
  if (!date) return "-";
  return new Date(date).toLocaleString("pl-PL", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

// Formatowanie daty zatwierdzenia (bez "Utworzono:")
const formatApprovalDate = (date) => {
  if (!date) return "-";
  return new Date(date).toLocaleString("pl-PL", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const downloadPdf = async () => {
  if (!props.quotation?.id) return;

  loadingPdf.value = true;
  try {
    // 1. Pobierz plik (Blob) ze Store'a
    const blobData = await quotationsStore.downloadPdf(props.quotation.id);

    // 2. Utwórz obiekt Blob z odpowiednim typem MIME
    const blob = new Blob([blobData], { type: "application/pdf" });

    // 3. Utwórz tymczasowy URL dla tego pliku
    const url = window.URL.createObjectURL(blob);

    // 4. Stwórz niewidoczny element <a>, ustaw URL i wymuś kliknięcie
    const link = document.createElement("a");
    link.href = url;

    const formattedDate = format(new Date(props.quotation.updated_at), "yyyy-MM-dd");

    link.setAttribute(
      "download",
      `Wycena_v${props.quotation.version_number}_${formattedDate}.pdf`
    );
    document.body.appendChild(link);
    link.click();

    // 5. Posprzątaj (usuń element i zwolnij pamięć)
    link.parentNode.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Download PDF error:", error);
    alert("Błąd podczas pobierania PDF");
  } finally {
    loadingPdf.value = false;
  }
};
</script>

<style scoped>
/* Grid 1x4 dla podsumowania (4 kolumny w jednym rzędzie) */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

/* Responsive - na małych ekranach 2x2 */
@media (max-width: 960px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Responsive - na bardzo małych ekranach 1 kolumna */
@media (max-width: 600px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
}

/* Tabela custom - pełna kontrola nad wyrównaniem */
.quotation-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.quotation-table thead {
  background-color: rgb(250, 250, 250);
  border-bottom: 2px solid rgba(0, 0, 0, 0.12);
}

.quotation-table thead th {
  font-weight: 700;
  font-size: 0.75rem;
  color: rgba(0, 0, 0, 0.87);
  padding: 14px 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
  border-bottom: 2px solid rgba(0, 0, 0, 0.12);
}

.quotation-table tbody td {
  padding: 12px 16px;
  font-size: 0.875rem;
  color: rgba(0, 0, 0, 0.87);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.quotation-table tbody tr:hover {
  background-color: rgba(33, 150, 243, 0.08);
  transition: background-color 0.2s;
}

.quotation-table tbody tr.row-even {
  background-color: rgb(252, 252, 252);
}

.quotation-table tbody tr.row-even:hover {
  background-color: rgba(33, 150, 243, 0.08);
}
</style>
