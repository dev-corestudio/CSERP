<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    fullscreen
    transition="dialog-bottom-transition"
  >
    <v-card>
      <!-- Toolbar -->
      <v-toolbar color="primary">
        <v-btn icon="mdi-close" @click="closeDialog" />
        <v-toolbar-title>
          {{ isEdit ? `Edycja Wyceny v${quotation.version_number}` : "Nowa Wycena" }}
        </v-toolbar-title>
        <v-spacer />
        <v-toolbar-items>
          <v-btn variant="text" :loading="saving" @click="saveQuotation">
            <v-icon start>mdi-content-save</v-icon>
            Zapisz
          </v-btn>
        </v-toolbar-items>
      </v-toolbar>

      <v-card-text class="bg-grey-lighten-4 pa-4">
        <v-row>
          <!-- LEWA KOLUMNA: Edytor -->
          <v-col cols="12" md="8">
            <!-- Sekcja Materiałów -->
            <v-card class="mb-4" elevation="1">
              <v-card-title class="d-flex align-center py-3 bg-white">
                <v-icon start color="teal">mdi-cube-outline</v-icon>
                Materiały
                <v-spacer />
                <v-btn
                  size="small"
                  color="teal"
                  variant="tonal"
                  prepend-icon="mdi-table-arrow-down"
                  class="mr-2"
                  @click="batchDialog = true"
                >
                  Importuj
                </v-btn>
                <v-btn
                  size="small"
                  color="teal"
                  variant="tonal"
                  prepend-icon="mdi-plus"
                  @click="addMaterial"
                >
                  Dodaj materiał
                </v-btn>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-0">
                <v-table density="compact">
                  <thead>
                    <tr>
                      <th style="width: 40%">Nazwa</th>
                      <th style="width: 15%">Ilość</th>
                      <th style="width: 10%">J.m.</th>
                      <th style="width: 15%">Cena jedn.</th>
                      <th style="width: 15%">Wartość</th>
                      <th style="width: 5%"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in form.materials" :key="index">
                      <td class="py-2">
                        <v-autocomplete
                          v-model="item.assortment_item_id"
                          :items="quotationsStore.materials"
                          item-title="name"
                          item-value="id"
                          density="compact"
                          variant="outlined"
                          hide-details
                          placeholder="Wybierz materiał"
                          auto-select-first
                          @update:model-value="(val) => onMaterialSelect(item, val)"
                        >
                          <template #item="{ props: itemProps, item: material }">
                            <v-list-item
                              v-bind="itemProps"
                              :subtitle="`${material.raw.default_price} PLN / ${material.raw.unit}`"
                            />
                          </template>
                        </v-autocomplete>
                      </td>
                      <td>
                        <v-text-field
                          v-model.number="item.quantity"
                          type="number"
                          min="0"
                          step="0.01"
                          density="compact"
                          variant="outlined"
                          hide-details
                        />
                      </td>
                      <td class="text-caption text-medium-emphasis">
                        {{ item.unit || "-" }}
                      </td>
                      <td>
                        <v-text-field
                          v-model.number="item.unit_price"
                          type="number"
                          min="0"
                          step="0.01"
                          density="compact"
                          variant="outlined"
                          hide-details
                          suffix="zł"
                        />
                      </td>
                      <td class="text-right font-weight-bold">
                        {{ formatCurrency(item.quantity * item.unit_price) }}
                      </td>
                      <td>
                        <v-btn
                          icon="mdi-delete"
                          size="x-small"
                          color="grey"
                          variant="text"
                          @click="removeMaterial(index)"
                        />
                      </td>
                    </tr>
                    <tr v-if="form.materials.length === 0">
                      <td
                        colspan="6"
                        class="text-center text-caption pa-4 text-medium-emphasis"
                      >
                        Brak materiałów. Kliknij "Dodaj materiał" lub "Importuj".
                      </td>
                    </tr>
                  </tbody>
                </v-table>
              </v-card-text>
            </v-card>

            <!-- Sekcja Usług -->
            <v-card elevation="1">
              <v-card-title class="d-flex align-center py-3 bg-white">
                <v-icon start color="orange">mdi-wrench-outline</v-icon>
                Usługi / Robocizna
                <v-spacer />
                <v-btn
                  size="small"
                  color="orange-darken-2"
                  variant="tonal"
                  prepend-icon="mdi-table-arrow-down"
                  class="mr-2"
                  @click="batchServiceDialog = true"
                >
                  Importuj
                </v-btn>
                <v-btn
                  size="small"
                  color="orange-darken-2"
                  variant="tonal"
                  prepend-icon="mdi-plus"
                  @click="addService"
                >
                  Dodaj usługę
                </v-btn>
              </v-card-title>

              <v-divider />

              <v-card-text class="pa-0">
                <v-table density="compact">
                  <thead>
                    <tr>
                      <th style="width: 40%">Nazwa usługi</th>
                      <th style="width: 15%">Godziny (h)</th>
                      <th style="width: 10%"></th>
                      <th style="width: 15%">Stawka/h</th>
                      <th style="width: 15%">Wartość</th>
                      <th style="width: 5%"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in form.services" :key="index">
                      <td class="py-2">
                        <v-autocomplete
                          v-model="item.assortment_item_id"
                          :items="quotationsStore.services"
                          item-title="name"
                          item-value="id"
                          density="compact"
                          variant="outlined"
                          hide-details
                          placeholder="Wybierz usługę"
                          @update:model-value="(val) => onServiceSelect(item, val)"
                        />
                      </td>
                      <td>
                        <v-text-field
                          v-model.number="item.estimated_time_hours"
                          type="number"
                          min="0"
                          step="0.5"
                          density="compact"
                          variant="outlined"
                          hide-details
                        />
                      </td>
                      <td></td>
                      <td>
                        <v-text-field
                          v-model.number="item.unit_price"
                          type="number"
                          min="0"
                          step="1"
                          density="compact"
                          variant="outlined"
                          hide-details
                          suffix="zł"
                        />
                      </td>
                      <td class="text-right font-weight-bold">
                        {{ formatCurrency(item.estimated_time_hours * item.unit_price) }}
                      </td>
                      <td>
                        <v-btn
                          icon="mdi-delete"
                          size="x-small"
                          color="grey"
                          variant="text"
                          @click="removeService(index)"
                        />
                      </td>
                    </tr>
                    <tr v-if="form.services.length === 0">
                      <td
                        colspan="6"
                        class="text-center text-caption pa-4 text-medium-emphasis"
                      >
                        Brak usług. Kliknij "Dodaj usługę" lub "Importuj".
                      </td>
                    </tr>
                  </tbody>
                </v-table>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- PRAWA KOLUMNA: Podsumowanie -->
          <v-col cols="12" md="4">
            <v-card class="position-sticky" style="top: 20px">
              <v-card-title class="bg-grey-lighten-3">Podsumowanie</v-card-title>
              <v-card-text class="pa-4">
                <div class="d-flex justify-space-between mb-2">
                  <span class="text-medium-emphasis">Materiały:</span>
                  <span class="font-weight-bold">{{
                    formatCurrency(totals.materials)
                  }}</span>
                </div>
                <div class="d-flex justify-space-between mb-2">
                  <span class="text-medium-emphasis">Usługi:</span>
                  <span class="font-weight-bold">{{
                    formatCurrency(totals.services)
                  }}</span>
                </div>
                <div class="d-flex justify-space-between mb-4">
                  <span class="text-medium-emphasis">Koszty łącznie:</span>
                  <span class="font-weight-bold">{{ formatCurrency(totals.cost) }}</span>
                </div>

                <v-divider class="mb-4" />

                <div class="mb-4">
                  <div class="text-caption mb-1">Marża (%)</div>
                  <v-slider
                    v-model="form.margin_percent"
                    :min="0"
                    :max="100"
                    step="1"
                    thumb-label
                    color="primary"
                    hide-details
                  >
                    <template #append>
                      <v-text-field
                        v-model.number="form.margin_percent"
                        type="number"
                        style="width: 80px"
                        density="compact"
                        variant="outlined"
                        hide-details
                        suffix="%"
                      />
                    </template>
                  </v-slider>
                </div>

                <v-divider class="mb-4" />

                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-subtitle-1">Netto:</span>
                  <span class="text-h6 text-primary font-weight-bold">
                    {{ formatCurrency(totals.net) }}
                  </span>
                </div>
                <div class="d-flex justify-space-between align-center">
                  <span class="text-subtitle-1">Brutto (23%):</span>
                  <span class="text-h5 font-weight-black">
                    {{ formatCurrency(totals.gross) }}
                  </span>
                </div>

                <v-divider class="my-4" />

                <v-textarea
                  v-model="form.notes"
                  label="Notatki do wyceny"
                  variant="outlined"
                  rows="3"
                  hide-details
                  class="mb-2"
                />
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Dialog Importu Materiałów -->
    <batch-material-add-dialog
      v-model="batchDialog"
      :loading="batchLoading"
      @save="handleBatchImport"
    />

    <!-- Dialog Importu Usług -->
    <batch-service-add-dialog
      v-model="batchServiceDialog"
      :loading="batchServiceLoading"
      @save="handleBatchServiceImport"
    />
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useQuotationsStore } from "@/stores/quotations";
import { useFormatters } from "@/composables/useFormatters";
import { assortmentService } from "@/services/assortmentService"; // DODANE
import BatchMaterialAddDialog from "@/components/materials/BatchMaterialAddDialog.vue";
import BatchServiceAddDialog from "@/components/quotations/BatchServiceAddDialog.vue";

// TYPY
interface MaterialItem {
  assortment_item_id: number | null;
  quantity: number;
  unit: string;
  unit_price: number;
}

interface ServiceItem {
  assortment_item_id: number | null;
  estimated_time_hours: number;
  unit_price: number;
}

const props = defineProps({
  modelValue: Boolean,
  variant: {
    type: Object,
    required: true,
  },
  quotation: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const quotationsStore = useQuotationsStore();
const { formatCurrency } = useFormatters();

const saving = ref(false);
const batchDialog = ref(false);
const batchLoading = ref(false);
const batchServiceDialog = ref(false);
const batchServiceLoading = ref(false);

const isEdit = computed(() => !!props.quotation);

const form = ref({
  materials: [] as MaterialItem[],
  services: [] as ServiceItem[],
  margin_percent: 30,
  notes: "",
});

// Obliczenia sumaryczne dla podsumowania
const totals = computed(() => {
  const materialsCost = form.value.materials.reduce(
    (sum, item) => sum + (item.quantity * item.unit_price || 0),
    0
  );
  const servicesCost = form.value.services.reduce(
    (sum, item) => sum + (item.estimated_time_hours * item.unit_price || 0),
    0
  );

  const cost = materialsCost + servicesCost;
  const marginValue = cost * (form.value.margin_percent / 100);
  const net = cost + marginValue;
  const gross = net * 1.23;

  return {
    materials: materialsCost,
    services: servicesCost,
    cost,
    marginValue,
    net,
    gross,
  };
});

// Inicjalizacja formularza przy otwarciu dialogu
watch(
  () => props.modelValue,
  async (isOpen) => {
    if (!isOpen) return;

    await Promise.all([
      quotationsStore.fetchMaterials(),
      quotationsStore.fetchServices(),
    ]);

    if (props.quotation) {
      // Tryb edycji — mapujemy istniejące pozycje wyceny
      const q = props.quotation;
      const materials: MaterialItem[] = [];
      const services: ServiceItem[] = [];

      q.items?.forEach((item: any) => {
        item.materials?.forEach((m: any) => {
          materials.push({
            assortment_item_id: m.assortment_item_id,
            quantity: m.quantity,
            unit: m.unit || "",
            unit_price: m.unit_price,
          });
        });
        item.services?.forEach((s: any) => {
          services.push({
            assortment_item_id: s.assortment_item_id,
            estimated_time_hours: s.estimated_time_hours,
            unit_price: s.unit_price,
          });
        });
      });

      form.value = {
        materials,
        services,
        margin_percent: q.margin_percent ?? 30,
        notes: q.notes ?? "",
      };
    } else {
      // Tryb tworzenia — pusty formularz
      form.value = { materials: [], services: [], margin_percent: 30, notes: "" };
    }
  }
);

// --- Operacje na materiałach ---

const addMaterial = (): void => {
  form.value.materials.push({
    assortment_item_id: null,
    quantity: 1,
    unit: "",
    unit_price: 0,
  });
};

const removeMaterial = (index: number): void => {
  form.value.materials.splice(index, 1);
};

// ID jest przekazywane jako value, szukamy obiektu w store
const onMaterialSelect = (item: MaterialItem, id: number): void => {
  if (!id) return;
  const selected = quotationsStore.materials.find((m: any) => m.id === id);
  if (selected) {
    item.unit_price = selected.default_price ?? 0;
    item.unit = selected.unit ?? "";
  }
};

// --- Operacje na usługach ---

const addService = (): void => {
  form.value.services.push({
    assortment_item_id: null,
    estimated_time_hours: 1,
    unit_price: 0,
  });
};

const removeService = (index: number): void => {
  form.value.services.splice(index, 1);
};

// ID jest przekazywane jako value, szukamy obiektu w store
const onServiceSelect = (item: ServiceItem, id: number): void => {
  if (!id) return;
  const selected = quotationsStore.services.find((s: any) => s.id === id);
  if (selected) {
    item.unit_price = selected.default_price ?? 0;
  }
};

// --- Import wsadowy (ZMODYFIKOWANE) ---

const handleBatchImport = async (items: any[]): Promise<void> => {
  batchLoading.value = true;
  try {
    // 1. Wyślij do backendu celem stworzenia/sprawdzenia asortymentu
    const resolvedItems = await assortmentService.batchCheckOrCreate(items, "MATERIAL");

    // 2. Odśwież lokalny sklep materiałów, aby v-autocomplete widział nowe ID
    await quotationsStore.fetchMaterials();

    // 3. Dodaj przetworzone pozycje do formularza
    resolvedItems.forEach((item) => {
      form.value.materials.push({
        assortment_item_id: item.assortment_item_id,
        quantity: item.quantity,
        unit: item.unit || "SZT",
        unit_price: item.unit_price,
      });
    });

    batchDialog.value = false;
  } catch (error: any) {
    console.error("Błąd importu:", error);
    alert("Błąd podczas importu: " + (error.response?.data?.message || error.message));
  } finally {
    batchLoading.value = false;
  }
};

const handleBatchServiceImport = async (items: any[]): Promise<void> => {
  batchServiceLoading.value = true;
  try {
    // 1. Wyślij do backendu
    const resolvedItems = await assortmentService.batchCheckOrCreate(items, "SERVICE");

    // 2. Odśwież listę usług
    await quotationsStore.fetchServices();

    // 3. Dodaj pozycje (mapując ilość na godziny)
    resolvedItems.forEach((item) => {
      form.value.services.push({
        assortment_item_id: item.assortment_item_id,
        estimated_time_hours: item.quantity,
        unit_price: item.unit_price,
      });
    });

    batchServiceDialog.value = false;
  } catch (error: any) {
    console.error("Błąd importu usług:", error);
    alert(
      "Błąd podczas importu usług: " + (error.response?.data?.message || error.message)
    );
  } finally {
    batchServiceLoading.value = false;
  }
};

// --- Zapis ---

const saveQuotation = async (): Promise<void> => {
  saving.value = true;
  try {
    const payload = {
      materials: form.value.materials,
      services: form.value.services,
      margin_percent: form.value.margin_percent,
      notes: form.value.notes,
    };

    if (isEdit.value) {
      await quotationsStore.updateQuotation(props.quotation.id, payload);
    } else {
      await quotationsStore.createQuotation(props.variant.id, payload);
    }

    emit("saved");
    closeDialog();
  } catch (error: any) {
    console.error("Błąd zapisu wyceny:", error);
    alert("Błąd zapisu: " + (error.response?.data?.message || "Nieznany błąd"));
  } finally {
    saving.value = false;
  }
};

const closeDialog = (): void => {
  emit("update:modelValue", false);
};
</script>
