<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="1000"
    persistent
  >
    <v-card>
      <v-card-title class="bg-teal-darken-1 text-white d-flex align-center">
        <v-icon start color="white">mdi-table-arrow-down</v-icon>
        Import materiałów
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>

      <v-card-text class="pt-6">
        <v-row>
          <!-- LEWA STRONA: Wklejanie -->
          <v-col cols="12" md="5">
            <div class="text-subtitle-2 mb-2">1. Skopiuj kolumny (bez nagłówków):</div>
            <div class="text-caption text-medium-emphasis mb-2 font-italic">
              Kolejność: <strong>[Nazwa]</strong> [TAB] <strong>[Ilość]</strong> [TAB]
              <strong>[J.m.]</strong> [TAB] <strong>[Cena (opcjonalnie)]</strong>
            </div>

            <v-textarea
              v-model="rawInput"
              placeholder="Kliknij tutaj i wciśnij Ctrl+V...

Przykładowe dane:
Śruby M12   20   szt   0,50
Profil 40x40   5,5   mb   12.99
Mydło   2   opk"
              variant="outlined"
              rows="14"
              no-resize
              class="font-monospace"
              @update:model-value="parseInput"
            ></v-textarea>
          </v-col>

          <!-- ŚRODEK: Strzałka -->
          <v-col cols="12" md="1" class="d-flex align-center justify-center">
            <v-icon size="32" color="grey-lighten-1" class="d-none d-md-flex"
              >mdi-arrow-right-bold</v-icon
            >
            <v-icon size="32" color="grey-lighten-1" class="d-flex d-md-none my-2"
              >mdi-arrow-down-bold</v-icon
            >
          </v-col>

          <!-- PRAWA STRONA: Podgląd -->
          <v-col cols="12" md="6">
            <div class="text-subtitle-2 mb-2 d-flex justify-space-between">
              <span>2. Sprawdź poprawność:</span>
              <span v-if="parsedItems.length > 0" class="text-teal font-weight-bold">
                {{ parsedItems.length }} pozycji
              </span>
            </div>

            <v-table density="compact" class="border rounded" fixed-header height="414px">
              <thead>
                <tr class="bg-grey-lighten-4">
                  <th>Nazwa</th>
                  <th class="text-right">Ilość</th>
                  <th class="text-center">J.m.</th>
                  <th class="text-right">Cena jedn.</th>
                  <th class="text-center" style="width: 50px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in parsedItems" :key="index">
                  <td class="text-truncate" style="max-width: 150px" :title="item.name">
                    {{ item.name }}
                  </td>
                  <td class="text-right font-weight-bold">{{ item.quantity }}</td>
                  <td class="text-center">
                    <v-chip
                      size="x-small"
                      :color="item.isUnitRecognized ? 'teal' : 'grey'"
                      label
                    >
                      {{ item.unit }}
                    </v-chip>
                  </td>
                  <td class="text-right">
                    {{ item.unit_price ? formatCurrency(item.unit_price) : "-" }}
                  </td>
                  <td class="text-center">
                    <v-btn
                      icon="mdi-close"
                      size="x-small"
                      variant="text"
                      color="error"
                      @click="removeItem(index)"
                      title="Usuń ten wiersz"
                    />
                  </td>
                </tr>
                <tr v-if="parsedItems.length === 0">
                  <td colspan="5" class="text-center text-medium-emphasis py-12">
                    <v-icon class="mb-2">mdi-clipboard-text-off-outline</v-icon><br />
                    Brak danych.<br />Wklej tekst po lewej stronie.
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
          color="teal"
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

// Reset po otwarciu
watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      clear();
    }
  }
);

const clear = () => {
  rawInput.value = "";
  parsedItems.value = [];
  validationError.value = null;
};

// --- LOGIKA NORMALIZACJI JEDNOSTEK (Bez zmian) ---
const normalizeUnit = (inputUnit: string): { unit: string; recognized: boolean } => {
  if (!inputUnit) return { unit: "SZT", recognized: false };

  const u = inputUnit.toLowerCase().trim().replace(".", "");

  const map: Record<string, string> = {
    szt: "SZT",
    sztuka: "SZT",
    sztuki: "SZT",
    pcs: "SZT",
    stk: "SZT",
    mb: "MB",
    m: "MB",
    metr: "MB",
    metry: "MB",
    biezacy: "MB",
    m2: "M2",
    mkw: "M2",
    sqm: "M2",
    kg: "KG",
    kilogram: "KG",
    op: "OP",
    opk: "OP",
    opak: "OP",
    opakowanie: "OP",
    paczka: "OP",
    karton: "OP",
    kpl: "KPL",
    komplet: "KPL",
    set: "KPL",
    l: "L",
    litr: "L",
    h: "H",
    godz: "H",
    rbh: "H",
  };

  if (map[u]) {
    return { unit: map[u], recognized: true };
  }
  return { unit: "SZT", recognized: false };
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

    // Dzielimy po tabulatorze LUB sekwencji min. 2 spacji.
    const parts = row.split(/\t|\s{2,}/);

    // Oczekujemy: [0] Nazwa, [1] Ilość, [2] Jednostka, [3] Cena (opcjonalna)
    const name = parts[0]?.trim();

    if (!name) continue;

    // Parsowanie ilości
    let quantityStr = parts[1]?.trim();
    if (quantityStr) {
      quantityStr = quantityStr.replace(/\s/g, "").replace(",", ".");
    } else {
      quantityStr = "0";
    }
    const quantity = parseFloat(quantityStr);

    if (isNaN(quantity)) {
      error = `Wiersz ${i + 1}: "${name}" - nieprawidłowa ilość.`;
      break;
    }

    // Parsowanie jednostki
    const rawUnit = parts[2]?.trim() || "";
    const { unit, recognized } = normalizeUnit(rawUnit);

    // Parsowanie ceny (NOWE)
    let unitPrice = null;
    let priceStr = parts[3]?.trim();
    if (priceStr) {
      // Usuwamy "zł", spacje, zamieniamy przecinek na kropkę
      priceStr = priceStr.replace(/[^\d.,]/g, "").replace(",", ".");
      const parsedPrice = parseFloat(priceStr);
      if (!isNaN(parsedPrice)) {
        unitPrice = parsedPrice;
      }
    }

    items.push({
      name,
      quantity,
      unit,
      unit_price: unitPrice, // Może być null
      isUnitRecognized: recognized,
    });
  }

  parsedItems.value = items;
  validationError.value = error;
};

const removeItem = (index: number) => {
  parsedItems.value.splice(index, 1);
};

const save = () => {
  const payload = parsedItems.value.map((item) => ({
    name: item.name,
    quantity: item.quantity,
    unit: item.unit,
    unit_price: item.unit_price, // Przekazujemy cenę
  }));
  emit("save", payload);
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
