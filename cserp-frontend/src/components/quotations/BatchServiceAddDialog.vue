<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="900"
    persistent
  >
    <v-card>
      <v-card-title class="bg-orange-darken-1 text-white d-flex align-center">
        <v-icon start color="white">mdi-table-arrow-down</v-icon>
        Import usług
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>

      <v-card-text class="pt-6">
        <v-row>
          <!-- LEWA STRONA: Wklejanie -->
          <v-col cols="12" md="5">
            <div class="text-subtitle-2 mb-2">Skopiuj kolumny (bez nagłówków):</div>
            <div class="text-caption text-medium-emphasis mb-2 font-italic">
              Kolejność: <strong>[Nazwa usługi]</strong> [TAB]
              <strong>[Ilość godzin]</strong> [TAB] <strong>[Stawka/h]</strong>
            </div>

            <v-textarea
              v-model="rawInput"
              placeholder="Wklej tutaj dane...

Przykładowe dane:
Drukowanie UV   20   100
Laser CNC       1    140
Obróbka ręczna  3    70"
              variant="outlined"
              rows="14"
              no-resize
              class="font-monospace"
              @update:model-value="parseInput"
            ></v-textarea>
          </v-col>

          <!-- ŚRODEK -->
          <v-col cols="12" md="1" class="d-flex align-center justify-center">
            <v-icon size="32" color="grey-lighten-1">mdi-arrow-right-bold</v-icon>
          </v-col>

          <!-- PRAWA STRONA: Podgląd -->
          <v-col cols="12" md="6">
            <div class="text-subtitle-2 mb-2 d-flex justify-space-between">
              <span>2. Podgląd danych:</span>
              <span v-if="parsedItems.length > 0" class="text-orange font-weight-bold">
                {{ parsedItems.length }} pozycji
              </span>
            </div>

            <v-table density="compact" class="border rounded" fixed-header height="414px">
              <thead>
                <tr class="bg-grey-lighten-4">
                  <th>Nazwa usługi</th>
                  <th class="text-right">Godziny (h)</th>
                  <th class="text-right">Stawka</th>
                  <th class="text-center" style="width: 50px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in parsedItems" :key="index">
                  <td class="text-truncate" style="max-width: 150px" :title="item.name">
                    {{ item.name }}
                  </td>
                  <td class="text-right font-weight-bold">{{ item.quantity }} h</td>
                  <td class="text-right">{{ formatCurrency(item.unit_price) }}</td>
                  <td class="text-center">
                    <v-btn
                      icon="mdi-close"
                      size="x-small"
                      variant="text"
                      color="error"
                      @click="removeItem(index)"
                    />
                  </td>
                </tr>
                <tr v-if="parsedItems.length === 0">
                  <td colspan="4" class="text-center text-medium-emphasis py-12">
                    <v-icon class="mb-2">mdi-clipboard-text-off-outline</v-icon><br />
                    Wklej dane po lewej stronie.
                  </td>
                </tr>
              </tbody>
            </v-table>

            <v-alert
              v-if="validationError"
              type="warning"
              variant="tonal"
              density="compact"
              class="mt-2"
              icon="mdi-alert"
            >
              {{ validationError }}
            </v-alert>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4 bg-grey-lighten-5">
        <v-btn variant="text" color="grey-darken-1" @click="clear">Wyczyść</v-btn>
        <v-spacer />
        <v-btn variant="text" @click="close">Anuluj</v-btn>
        <v-btn
          color="orange"
          variant="elevated"
          :disabled="parsedItems.length === 0 || !!validationError"
          :loading="loading"
          @click="save"
          prepend-icon="mdi-check"
        >
          Importuj ({{ parsedItems.length }})
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

const props = defineProps({
  modelValue: Boolean,
  loading: Boolean,
});

const emit = defineEmits(["update:modelValue", "save"]);

const rawInput = ref("");
const parsedItems = ref<any[]>([]);
const validationError = ref<string | null>(null);

watch(
  () => props.modelValue,
  (val) => {
    if (val) clear();
  }
);

const clear = () => {
  rawInput.value = "";
  parsedItems.value = [];
  validationError.value = null;
};

const parseInput = () => {
  if (!rawInput.value) {
    parsedItems.value = [];
    validationError.value = null;
    return;
  }

  const rows = rawInput.value.trim().split(/\r?\n/);
  const items: any[] = [];
  let error: string | null = null;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i].trim();
    if (!row) continue;

    // Rozdzielanie po tabulatorze lub wielu spacjach
    const parts = row.split(/\t|\s{2,}/);

    // Oczekujemy: [0] Nazwa, [1] Godziny, [2] Stawka
    const name = parts[0]?.trim();
    if (!name) continue;

    // Parsowanie Godzin
    let quantityStr = parts[1]?.trim();
    quantityStr = quantityStr ? quantityStr.replace(/\s/g, "").replace(",", ".") : "0";
    const quantity = parseFloat(quantityStr);

    if (isNaN(quantity)) {
      error = `Wiersz ${i + 1}: "${name}" - nieprawidłowa ilość godzin.`;
      break;
    }

    // Parsowanie Stawki (Unit Price)
    let priceStr = parts[2]?.trim();
    let unitPrice = 0;

    if (priceStr) {
      priceStr = priceStr.replace(/[^\d.,]/g, "").replace(",", ".");
      const parsedPrice = parseFloat(priceStr);
      if (!isNaN(parsedPrice)) unitPrice = parsedPrice;
    }

    items.push({
      name,
      quantity, // To będą estimated_time_hours
      unit: "h", // Domyślna jednostka dla usług z tego formularza
      unit_price: unitPrice,
    });
  }

  parsedItems.value = items;
  validationError.value = error;
};

const removeItem = (index: number) => {
  parsedItems.value.splice(index, 1);
};

const save = () => {
  // Wysyłamy dane do rodzica
  emit("save", parsedItems.value);
};

const close = () => {
  emit("update:modelValue", false);
};

const formatCurrency = (val: any) =>
  new Intl.NumberFormat("pl-PL", { style: "currency", currency: "PLN" }).format(val || 0);
</script>

<style scoped>
.font-monospace {
  font-family: "Roboto Mono", monospace !important;
  font-size: 0.85rem;
}
</style>
