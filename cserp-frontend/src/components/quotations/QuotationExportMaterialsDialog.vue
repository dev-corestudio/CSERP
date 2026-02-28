<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="handleClose"
    max-width="480"
    persistent
  >
    <v-card>
      <!-- Nagłówek -->
      <v-card-title class="bg-teal text-white d-flex align-center">
        <v-icon start color="white">mdi-export</v-icon>
        Eksportuj materiały do wariantu
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" color="white" @click="handleClose" />
      </v-card-title>

      <!-- ================================================================ -->
      <!-- WIDOK 1: WYBÓR TRYBU (przed eksportem)                          -->
      <!-- ================================================================ -->
      <template v-if="!exportResult">
        <v-card-text class="pt-5">
          <!-- Info o wycenie -->
          <v-alert type="info" variant="tonal" density="compact" class="mb-5">
            Wycena <strong>v{{ quotation?.version_number }}</strong> zawiera
            <strong>{{ materialsCount }}</strong> materiałów o łącznej wartości
            <strong>{{ formatCurrency(quotation?.total_materials_cost) }}</strong
            >.
          </v-alert>

          <!-- Wybór trybu -->
          <div class="text-subtitle-2 font-weight-bold mb-3">
            Jak obsłużyć materiały już istniejące w wariancie?
          </div>

          <v-radio-group v-model="selectedMode" hide-details>
            <v-radio value="skip" color="teal" class="mb-2">
              <template #label>
                <div>
                  <div class="font-weight-medium">Pomiń istniejące</div>
                  <div class="text-caption text-medium-emphasis">
                    Dodaj tylko nowe materiały. Istniejące pozostają bez zmian.
                  </div>
                </div>
              </template>
            </v-radio>

            <v-radio value="merge" color="teal" class="mb-2">
              <template #label>
                <div>
                  <div class="font-weight-medium">Scal ilości</div>
                  <div class="text-caption text-medium-emphasis">
                    Dodaj ilość z wyceny do już istniejącej. Cena pozostaje bez zmian.
                  </div>
                </div>
              </template>
            </v-radio>

            <v-radio value="replace" color="teal">
              <template #label>
                <div>
                  <div class="font-weight-medium">Zastąp całą listę</div>
                  <div class="text-caption text-medium-emphasis">
                    Usuń wszystkie aktualne materiały wariantu i wstaw tylko te z wyceny.
                  </div>
                </div>
              </template>
            </v-radio>
          </v-radio-group>
        </v-card-text>

        <v-card-actions class="pa-4 bg-grey-lighten-5">
          <v-spacer />
          <v-btn variant="text" color="grey-darken-1" @click="handleClose">
            Anuluj
          </v-btn>
          <v-btn
            color="teal"
            variant="elevated"
            :loading="loading"
            prepend-icon="mdi-check"
            @click="doExport"
          >
            Eksportuj
          </v-btn>
        </v-card-actions>
      </template>

      <!-- ================================================================ -->
      <!-- WIDOK 2: PODSUMOWANIE (po eksporcie)                            -->
      <!-- ================================================================ -->
      <template v-else>
        <v-card-text class="pt-5">
          <!-- Ikona sukcesu -->
          <div class="text-center mb-5">
            <v-icon size="56" color="teal">mdi-check-circle</v-icon>
            <div class="text-h6 font-weight-bold mt-2">Eksport zakończony</div>
            <div class="text-caption text-medium-emphasis mt-1">
              Tryb: <strong>{{ modeLabel }}</strong>
            </div>
          </div>

          <!-- Lista statystyk -->
          <v-list density="compact" class="pa-0 border rounded">
            <v-list-item v-if="exportResult.stats.exported > 0" class="py-3">
              <template #prepend>
                <v-icon color="success" class="mr-3">mdi-plus-circle</v-icon>
              </template>
              <v-list-item-title>Dodano nowych</v-list-item-title>
              <template #append>
                <v-chip color="success" size="small" label>
                  {{ exportResult.stats.exported }}
                </v-chip>
              </template>
            </v-list-item>

            <v-divider v-if="exportResult.stats.exported > 0 && hasMoreStats" />

            <v-list-item v-if="exportResult.stats.replaced > 0" class="py-3">
              <template #prepend>
                <v-icon color="blue" class="mr-3">mdi-refresh</v-icon>
              </template>
              <v-list-item-title>Zastąpionych (nadpisanych)</v-list-item-title>
              <template #append>
                <v-chip color="blue" size="small" label>
                  {{ exportResult.stats.replaced }}
                </v-chip>
              </template>
            </v-list-item>

            <v-divider
              v-if="
                exportResult.stats.replaced > 0 &&
                (exportResult.stats.merged > 0 || exportResult.stats.skipped > 0)
              "
            />

            <v-list-item v-if="exportResult.stats.merged > 0" class="py-3">
              <template #prepend>
                <v-icon color="orange" class="mr-3">mdi-merge</v-icon>
              </template>
              <v-list-item-title>Scalonych (ilości zsumowane)</v-list-item-title>
              <template #append>
                <v-chip color="orange" size="small" label>
                  {{ exportResult.stats.merged }}
                </v-chip>
              </template>
            </v-list-item>

            <v-divider
              v-if="exportResult.stats.merged > 0 && exportResult.stats.skipped > 0"
            />

            <v-list-item v-if="exportResult.stats.skipped > 0" class="py-3">
              <template #prepend>
                <v-icon color="grey" class="mr-3">mdi-minus-circle</v-icon>
              </template>
              <v-list-item-title class="text-medium-emphasis">
                Pominiętych (już istniały)
              </v-list-item-title>
              <template #append>
                <v-chip color="grey" size="small" label>
                  {{ exportResult.stats.skipped }}
                </v-chip>
              </template>
            </v-list-item>

            <!-- Brak zmian -->
            <v-list-item v-if="totalProcessed === 0" class="py-3">
              <template #prepend>
                <v-icon color="grey" class="mr-3">mdi-information</v-icon>
              </template>
              <v-list-item-title class="text-medium-emphasis">
                Brak materiałów do przetworzenia
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card-text>

        <v-card-actions class="pa-4 bg-grey-lighten-5">
          <v-spacer />
          <v-btn
            color="teal"
            variant="elevated"
            prepend-icon="mdi-close"
            @click="handleClose"
          >
            Zamknij
          </v-btn>
        </v-card-actions>
      </template>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useQuotationsStore } from "@/stores/quotations";

// =========================================================================
// PROPS & EMITS
// =========================================================================

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  quotation: { type: Object, default: null },
});

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  exported: [];
}>();

// =========================================================================
// STATE
// =========================================================================

const quotationsStore = useQuotationsStore();

type ExportMode = "skip" | "merge" | "replace";

const selectedMode = ref<ExportMode>("replace");
const loading = ref(false);

// Po eksporcie — null = pokazuj formularz, obiekt = pokazuj podsumowanie
const exportResult = ref<{ stats: Record<string, number>; message: string } | null>(null);

// Zapamiętaj tryb dla widoku podsumowania (selectedMode może się zmienić przy kolejnym otwarciu)
const usedMode = ref<ExportMode>("replace");

// =========================================================================
// COMPUTED
// =========================================================================

const materialsCount = computed(() => {
  if (!props.quotation?.items) return 0;
  return props.quotation.items.reduce(
    (sum: number, item: any) => sum + (item.materials?.length || 0),
    0
  );
});

const modeLabel = computed(() => {
  const labels: Record<ExportMode, string> = {
    skip: "Pomiń istniejące",
    merge: "Scal ilości",
    replace: "Zastąp istniejące",
  };
  return labels[usedMode.value];
});

const hasMoreStats = computed(() => {
  if (!exportResult.value) return false;
  const s = exportResult.value.stats;
  return (s.replaced || 0) + (s.merged || 0) + (s.skipped || 0) > 0;
});

const totalProcessed = computed(() => {
  if (!exportResult.value) return 0;
  const s = exportResult.value.stats;
  return (s.exported || 0) + (s.replaced || 0) + (s.merged || 0) + (s.skipped || 0);
});

// =========================================================================
// WATCHERS
// =========================================================================

// Reset przy każdym otwarciu dialogu
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      exportResult.value = null;
      selectedMode.value = "replace";
      usedMode.value = "replace";
      loading.value = false;
    }
  }
);

// =========================================================================
// METODY
// =========================================================================

const doExport = async () => {
  if (!props.quotation?.id) return;

  loading.value = true;
  usedMode.value = selectedMode.value; // Zapamiętaj przed eksportem

  try {
    const result = await quotationsStore.exportMaterials(
      props.quotation.id,
      selectedMode.value
    );

    // Przełącz na widok podsumowania
    exportResult.value = result;

    // Powiadom rodzica żeby odświeżył zakładkę materiałów
    emit("exported");
  } catch (error: any) {
    console.error("Export materials error:", error);
    alert("Błąd eksportu: " + (error.response?.data?.message || error.message));
  } finally {
    loading.value = false;
  }
};

const handleClose = () => {
  emit("update:modelValue", false);
  // Krótkie opóźnienie żeby animacja zamknięcia nie migała
  setTimeout(() => {
    exportResult.value = null;
    selectedMode.value = "replace";
    usedMode.value = "replace";
  }, 300);
};

// =========================================================================
// POMOCNICZE
// =========================================================================

const formatCurrency = (val: any) =>
  new Intl.NumberFormat("pl-PL", { style: "currency", currency: "PLN" }).format(val || 0);
</script>
