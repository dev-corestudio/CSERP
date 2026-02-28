<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="700"
    persistent
  >
    <v-card>
      <v-card-title class="bg-teal text-white d-flex align-center">
        <v-icon start color="white">{{ isEdit ? "mdi-pencil" : "mdi-plus" }}</v-icon>
        {{ isEdit ? "Edytuj materiał" : "Dodaj materiał" }}
      </v-card-title>

      <v-card-text class="pt-6">
        <v-form ref="formRef" v-model="formValid" @submit.prevent="save">
          <v-row>
            <!-- Assortment select -->
            <v-col cols="12">
              <v-autocomplete
                v-model="form.assortment_id"
                :items="assortmentItems"
                item-title="name"
                item-value="id"
                label="Materiał z asortymentu *"
                :rules="[(v) => !!v || 'Wybierz materiał']"
                variant="outlined"
                density="compact"
                :disabled="isEdit"
                :loading="loadingAssortment"
                placeholder="Wpisz aby wyszukać..."
                @update:model-value="onAssortmentChange"
                auto-select-first
              >
                <template v-slot:item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template v-slot:prepend>
                      <v-icon color="blue" size="small">mdi-package-variant</v-icon>
                    </template>
                    <v-list-item-subtitle>
                      {{
                        item.raw.default_price
                          ? formatCurrency(item.raw.default_price)
                          : ""
                      }}
                      {{ item.raw.unit ? "/ " + item.raw.unit : "" }}
                    </v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <!-- Quantity -->
            <v-col cols="6">
              <v-text-field
                v-model.number="form.quantity"
                label="Ilość *"
                type="number"
                min="0.01"
                step="0.01"
                :rules="[(v) => v > 0 || 'Podaj ilość']"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <!-- Unit (ZMIANA NA SELECT) -->
            <v-col cols="6">
              <v-select
                v-model="form.unit"
                :items="metadataStore.units"
                item-title="label"
                item-value="value"
                label="Jednostka *"
                :rules="[(v) => !!v || 'Podaj jednostkę']"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <!-- Unit Price -->
            <v-col cols="6">
              <v-text-field
                v-model.number="form.unit_price"
                label="Cena jednostkowa (PLN) *"
                type="number"
                min="0"
                step="0.01"
                :rules="[(v) => v >= 0 || 'Podaj cenę']"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <!-- Total (Readonly) -->
            <v-col cols="6">
              <v-text-field
                :model-value="calculatedTotal"
                label="Koszt całkowity (PLN)"
                variant="outlined"
                density="compact"
                readonly
                bg-color="grey-lighten-4"
              />
            </v-col>

            <v-col cols="12">
              <v-divider class="mb-2" />
              <div class="text-subtitle-2 text-medium-emphasis mb-2">
                <v-icon size="small" class="mr-1">mdi-truck-delivery</v-icon>
                Logistyka
              </div>
            </v-col>

            <!-- Status -->
            <v-col cols="6">
              <v-select
                v-model="form.status"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                label="Status"
                variant="outlined"
                density="compact"
              >
                <template v-slot:item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template v-slot:prepend>
                      <v-icon :color="item.raw.color" size="small">{{
                        item.raw.icon
                      }}</v-icon>
                    </template>
                  </v-list-item>
                </template>
                <template v-slot:selection="{ item }">
                  <v-chip
                    :color="item.raw.color"
                    :prepend-icon="item.raw.icon"
                    size="small"
                  >
                    {{ item.raw.label }}
                  </v-chip>
                </template>
              </v-select>
            </v-col>

            <!-- Supplier -->
            <v-col cols="6">
              <v-text-field
                v-model="form.supplier"
                label="Dostawca"
                variant="outlined"
                density="compact"
                clearable
              />
            </v-col>

            <!-- Expected delivery date -->
            <v-col cols="6">
              <v-text-field
                v-model="form.expected_delivery_date"
                label="Oczekiwana data dostawy"
                type="date"
                variant="outlined"
                density="compact"
                clearable
              />
            </v-col>

            <!-- Ordered at -->
            <v-col cols="6">
              <v-text-field
                v-model="form.ordered_at"
                label="Data zamówienia"
                type="date"
                variant="outlined"
                density="compact"
                clearable
              />
            </v-col>

            <!-- Quantity in stock / ordered (for partial) -->
            <template v-if="form.status === 'PARTIALLY_IN_STOCK'">
              <v-col cols="6">
                <v-text-field
                  v-model.number="form.quantity_in_stock"
                  label="Ilość na stanie"
                  type="number"
                  min="0"
                  step="0.01"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model.number="form.quantity_ordered"
                  label="Ilość zamówiona"
                  type="number"
                  min="0"
                  step="0.01"
                  variant="outlined"
                  density="compact"
                />
              </v-col>
            </template>

            <!-- Notes -->
            <v-col cols="12">
              <v-textarea
                v-model="form.notes"
                label="Notatki"
                variant="outlined"
                density="compact"
                rows="2"
                auto-grow
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn @click="close">Anuluj</v-btn>
        <v-btn
          color="teal"
          variant="elevated"
          :loading="saving"
          :disabled="!formValid"
          @click="save"
        >
          {{ isEdit ? "Zapisz" : "Dodaj" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { assortmentService } from "@/services/assortmentService";
import { useMetadataStore } from "@/stores/metadata"; // IMPORT STORE

const props = defineProps({
  modelValue: Boolean,
  material: {
    type: Object as () => any,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

// STORES
const metadataStore = useMetadataStore();

const formRef = ref();
const formValid = ref(false);
const saving = ref(false);
const assortmentItems = ref<any[]>([]);
const loadingAssortment = ref(false);

const isEdit = computed(() => !!props.material?.id);

const statusOptions = [
  { value: "NOT_ORDERED", label: "Niezamówiony", color: "red", icon: "mdi-cart-outline" },
  {
    value: "ORDERED",
    label: "Zamówiony",
    color: "orange",
    icon: "mdi-truck-fast-outline",
  },
  {
    value: "PARTIALLY_IN_STOCK",
    label: "Częściowo na stanie",
    color: "blue",
    icon: "mdi-package-variant",
  },
  {
    value: "IN_STOCK",
    label: "Na stanie",
    color: "green",
    icon: "mdi-package-variant-closed-check",
  },
];

const defaultForm = () => ({
  assortment_id: null as number | null,
  quantity: 1,
  unit: "SZT", // Domyślnie z dużej litery, zgodnie z Enum
  unit_price: 0,
  status: "NOT_ORDERED",
  supplier: "",
  expected_delivery_date: null as string | null,
  ordered_at: null as string | null,
  quantity_in_stock: 0,
  quantity_ordered: 0,
  notes: "",
});

const form = ref(defaultForm());

const calculatedTotal = computed(() => {
  const total = (form.value.quantity || 0) * (form.value.unit_price || 0);
  return new Intl.NumberFormat("pl-PL", { style: "currency", currency: "PLN" }).format(
    total
  );
});

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      // Upewnij się, że metadata są załadowane dla selecta jednostek
      if (!metadataStore.loaded) {
        await metadataStore.fetchMetadata();
      }

      await loadAssortment();

      if (props.material) {
        form.value = {
          assortment_id: props.material.assortment_id,
          quantity: Number(props.material.quantity),
          unit: props.material.unit,
          unit_price: Number(props.material.unit_price),
          status: props.material.status,
          supplier: props.material.supplier || "",
          expected_delivery_date: props.material.expected_delivery_date
            ? props.material.expected_delivery_date.substring(0, 10)
            : null,
          ordered_at: props.material.ordered_at
            ? props.material.ordered_at.substring(0, 10)
            : null,
          quantity_in_stock: Number(props.material.quantity_in_stock || 0),
          quantity_ordered: Number(props.material.quantity_ordered || 0),
          notes: props.material.notes || "",
        };
      } else {
        form.value = defaultForm();
      }
    }
  }
);

// Ładowanie asortymentu z parametrami paginacji
const loadAssortment = async () => {
  loadingAssortment.value = true;
  try {
    const items = await assortmentService.getAll({
      type: "material",
      per_page: 500,
      sort_by: "created_at",
      sort_dir: "desc",
    });
    assortmentItems.value = items;
  } catch (err) {
    console.error("Błąd ładowania asortymentu:", err);
  } finally {
    loadingAssortment.value = false;
  }
};

const onAssortmentChange = (id: number) => {
  const item = assortmentItems.value.find((a: any) => a.id === id);
  if (item) {
    form.value.unit = item.unit || "SZT";
    // POPRAWKA: Używamy default_price, ponieważ w asortymencie nie ma pola unit_price
    form.value.unit_price = Number(item.default_price) || 0;
  }
};

const save = () => {
  const payload = {
    ...form.value,
    total_cost: (form.value.quantity || 0) * (form.value.unit_price || 0),
  };
  saving.value = true;
  emit("saved", payload);
};

// Reset saving when dialog closes
watch(
  () => props.modelValue,
  (open) => {
    if (!open) {
      saving.value = false;
    }
  }
);

const close = () => {
  emit("update:modelValue", false);
};

const formatCurrency = (val: any) =>
  new Intl.NumberFormat("pl-PL", { style: "currency", currency: "PLN" }).format(val || 0);
</script>
