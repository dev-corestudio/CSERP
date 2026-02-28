<template>
  <v-card
    elevation="2"
    class="mb-4 transition-swing"
    :class="{ 'fullscreen-card': isFullscreen }"
  >
    <!-- NAGŁÓWEK (Bez zmian) -->
    <v-card-title
      class="bg-teal text-white d-flex align-center py-3"
      style="height: 48px"
    >
      <!-- STAN 1: ZAZNACZONO ELEMENTY -->
      <template v-if="selected.length > 0">
        <v-btn icon variant="text" color="white" @click="selected = []">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <span class="text-subtitle-1 font-weight-bold ml-2">
          Wybrano: {{ selected.length }}
        </span>
        <v-spacer />

        <v-menu location="bottom end">
          <template v-slot:activator="{ props }">
            <v-btn
              color="white"
              variant="text"
              class="mr-2"
              v-bind="props"
              prepend-icon="mdi-list-status"
            >
              Zmień status
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item
              v-for="status in materialStatuses"
              :key="status.value"
              :value="status.value"
              @click="bulkUpdateStatus(status.value)"
            >
              <template v-slot:prepend>
                <v-icon :color="status.color" size="small">{{ status.icon }}</v-icon>
              </template>
              <v-list-item-title>{{ status.label }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>

        <v-btn color="white" variant="text" prepend-icon="mdi-delete" @click="bulkDelete">
          Usuń
        </v-btn>

        <v-divider vertical color="white" class="mx-2 opacity-50"></v-divider>
        <v-btn icon size="small" variant="text" color="white" @click="toggleFullscreen">
          <v-icon>{{ isFullscreen ? "mdi-fullscreen-exit" : "mdi-fullscreen" }}</v-icon>
        </v-btn>
      </template>

      <!-- STAN 2: STANDARDOWY -->
      <template v-else>
        <v-icon start color="white">mdi-cube-outline</v-icon>
        Materiały ({{ materials.length }})

        <template v-if="summary && summary.total > 0 && !isFullscreen">
          <v-chip
            v-if="summary.all_ready"
            size="x-small"
            color="white"
            variant="flat"
            class="ml-3 font-weight-bold text-teal"
          >
            <v-icon start size="x-small">mdi-check-all</v-icon> Gotowe
          </v-chip>
          <template v-else>
            <v-chip
              v-if="summary.not_ordered > 0"
              size="x-small"
              color="red-lighten-4"
              class="ml-2 text-red-darken-4 font-weight-bold"
            >
              {{ summary.not_ordered }} niezam.
            </v-chip>
          </template>
        </template>

        <v-spacer />

        <template v-if="!readonly">
          <v-btn
            color="white"
            variant="text"
            size="small"
            class="mr-1"
            @click="$emit('importBatch')"
          >
            <v-icon start>mdi-table-arrow-down</v-icon>
            <span class="d-none d-sm-inline">Importuj</span>
          </v-btn>
          <v-btn color="white" variant="text" size="small" @click="$emit('add')">
            <v-icon start>mdi-plus</v-icon>
            <span class="d-none d-sm-inline">Dodaj</span>
          </v-btn>
        </template>

        <v-divider vertical color="white" class="mx-2 opacity-50"></v-divider>

        <v-btn icon size="small" variant="text" color="white" @click="toggleFullscreen">
          <v-icon>{{ isFullscreen ? "mdi-fullscreen-exit" : "mdi-fullscreen" }}</v-icon>
        </v-btn>
      </template>
    </v-card-title>

    <v-card-text class="pa-0 flex-grow-1 overflow-auto">
      <div v-if="loading" class="text-center py-6">
        <v-progress-circular indeterminate color="teal" />
      </div>

      <div v-else-if="materials.length === 0" class="text-center py-8">
        <v-icon size="64" color="grey-lighten-2">mdi-cube-outline</v-icon>
        <p class="text-body-2 text-medium-emphasis mt-2">Brak materiałów</p>
        <div v-if="!readonly" class="mt-4">
          <v-btn
            variant="outlined"
            color="teal"
            class="mr-2"
            @click="$emit('importBatch')"
            >Importuj</v-btn
          >
          <v-btn variant="elevated" color="teal" @click="$emit('add')"
            >Dodaj ręcznie</v-btn
          >
        </div>
      </div>

      <!-- TABELA Z SORTOWANIEM -->
      <v-table
        v-else
        density="compact"
        hover
        fixed-header
        :height="isFullscreen ? 'calc(100vh - 120px)' : undefined"
      >
        <thead>
          <tr class="bg-grey-lighten-4">
            <!-- Checkbox All -->
            <th style="width: 50px" class="text-center">
              <v-checkbox-btn
                :model-value="areAllSelected"
                :indeterminate="isIndeterminate"
                @update:model-value="toggleSelectAll"
                color="teal"
                density="compact"
                hide-details
              ></v-checkbox-btn>
            </th>
            <!-- Lp -->
            <th style="width: 50px" class="text-center text-medium-emphasis">Lp</th>

            <!-- KOLUMNY SORTOWALNE -->
            <th style="width: 25%" class="sortable-header" @click="handleSort('name')">
              Nazwa materiału
              <v-icon v-if="sortBy === 'name'" size="x-small" class="ml-1">
                {{ sortDesc ? "mdi-arrow-down" : "mdi-arrow-up" }}
              </v-icon>
            </th>

            <th
              class="text-right sortable-header"
              style="width: 10%"
              @click="handleSort('quantity')"
            >
              Ilość
              <v-icon v-if="sortBy === 'quantity'" size="x-small" class="ml-1">
                {{ sortDesc ? "mdi-arrow-down" : "mdi-arrow-up" }}
              </v-icon>
            </th>

            <th
              class="text-right sortable-header"
              style="width: 10%"
              @click="handleSort('unit_price')"
            >
              Cena jedn.
              <v-icon v-if="sortBy === 'unit_price'" size="x-small" class="ml-1">
                {{ sortDesc ? "mdi-arrow-down" : "mdi-arrow-up" }}
              </v-icon>
            </th>

            <th
              class="text-right sortable-header"
              style="width: 10%"
              @click="handleSort('total_cost')"
            >
              Koszt
              <v-icon v-if="sortBy === 'total_cost'" size="x-small" class="ml-1">
                {{ sortDesc ? "mdi-arrow-down" : "mdi-arrow-up" }}
              </v-icon>
            </th>

            <th
              style="width: 15%"
              class="sortable-header"
              @click="handleSort('supplier')"
            >
              Dostawca
              <v-icon v-if="sortBy === 'supplier'" size="x-small" class="ml-1">
                {{ sortDesc ? "mdi-arrow-down" : "mdi-arrow-up" }}
              </v-icon>
            </th>

            <th style="width: 15%">Notatki</th>

            <th
              style="width: 10%; text-align: center"
              class="sortable-header"
              @click="handleSort('expected_delivery_date')"
            >
              Data dostawy
              <v-icon
                v-if="sortBy === 'expected_delivery_date'"
                size="x-small"
                class="ml-1"
              >
                {{ sortDesc ? "mdi-arrow-down" : "mdi-arrow-up" }}
              </v-icon>
            </th>

            <th
              class="text-center sortable-header"
              style="width: 140px"
              @click="handleSort('status')"
            >
              Status
              <v-icon v-if="sortBy === 'status'" size="x-small" class="ml-1">
                {{ sortDesc ? "mdi-arrow-down" : "mdi-arrow-up" }}
              </v-icon>
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- Iterujemy po sortedMaterials zamiast materials -->
          <tr
            v-for="(material, index) in sortedMaterials"
            :key="material.id"
            :class="{ 'bg-blue-lighten-5': selected.includes(material.id) }"
          >
            <!-- Checkbox -->
            <td class="text-center">
              <v-checkbox-btn
                v-model="selected"
                :value="material.id"
                color="teal"
                density="compact"
                hide-details
              ></v-checkbox-btn>
            </td>

            <!-- LP -->
            <td class="text-center text-caption text-medium-emphasis">
              {{ index + 1 }}
            </td>

            <!-- 1. Nazwa -->
            <td class="py-2 align-middle">
              <div class="font-weight-medium text-body-2">
                {{ material.assortment?.name || "Materiał #" + material.assortment_id }}
              </div>
            </td>

            <!-- 2. Ilość -->
            <td class="text-right align-middle">
              <div class="d-flex align-center justify-end">
                <inline-edit-field
                  v-if="!readonly"
                  :model-value="material.quantity"
                  type="number"
                  text-class="font-weight-bold"
                  :show-edit-icon="false"
                  @save="(val) => emitUpdate(material, 'quantity', val)"
                />
                <span v-else class="font-weight-bold">{{ material.quantity }}</span>
                <span class="text-caption text-medium-emphasis ml-1">{{
                  material.unit
                }}</span>
              </div>
              <div
                v-if="material.status === 'PARTIALLY_IN_STOCK'"
                class="text-caption mt-n1 text-right"
              >
                <span class="text-success">{{ material.quantity_in_stock }}</span> /
                <span class="text-orange">{{ material.quantity_ordered }}</span>
              </div>
            </td>

            <!-- 3. Cena Jednostkowa -->
            <td class="text-right align-middle">
              <inline-edit-field
                v-if="!readonly"
                :model-value="material.unit_price"
                textClass="text-right"
                type="number"
                :show-edit-icon="false"
                @save="(val) => emitUpdate(material, 'unit_price', val)"
              >
                <template #display="{ value }">
                  <span class="text-medium-emphasis">{{ formatCurrency(value) }}</span>
                </template>
              </inline-edit-field>
              <span v-else>{{ formatCurrency(material.unit_price) }}</span>
            </td>

            <!-- 4. Koszt -->
            <td class="text-right align-middle font-weight-bold">
              {{ formatCurrency(material.quantity * material.unit_price) }}
            </td>

            <!-- 5. Dostawca -->
            <td class="align-middle">
              <inline-edit-field
                v-if="!readonly"
                :model-value="material.supplier"
                placeholder="—"
                :show-edit-icon="false"
                @save="(val) => emitUpdate(material, 'supplier', val)"
              />
              <span v-else>{{ material.supplier || "—" }}</span>
            </td>

            <!-- 6. Notatki -->
            <td class="align-middle">
              <inline-edit-field
                v-if="!readonly"
                :model-value="material.notes"
                placeholder="+ Dodaj"
                type="textarea"
                text-class="text-caption text-medium-emphasis font-italic"
                :show-edit-icon="false"
                @save="(val) => emitUpdate(material, 'notes', val)"
              />
              <div v-else class="text-caption text-medium-emphasis">
                {{ material.notes }}
              </div>
            </td>

            <!-- 7. Data Dostawy -->
            <td class="align-middle text-center">
              <v-menu
                v-if="!readonly"
                :close-on-content-click="false"
                location="bottom center"
              >
                <template v-slot:activator="{ props }">
                  <div
                    v-bind="props"
                    class="cursor-pointer py-1 px-2 rounded hover-bg"
                    :class="
                      !material.expected_delivery_date ? 'text-disabled text-caption' : ''
                    "
                  >
                    <v-icon
                      v-if="!material.expected_delivery_date"
                      size="x-small"
                      class="mr-1"
                      >mdi-calendar-blank</v-icon
                    >
                    <span
                      :class="isOverdue(material) ? 'text-error font-weight-bold' : ''"
                    >
                      {{
                        material.expected_delivery_date
                          ? formatDate(material.expected_delivery_date)
                          : "Ustaw datę"
                      }}
                    </span>
                  </div>
                </template>

                <v-date-picker
                  color="teal"
                  hide-header
                  @update:model-value="
                    (date) => {
                      updateDate(material, date);
                    }
                  "
                ></v-date-picker>
              </v-menu>

              <div v-else>
                {{ formatDate(material.expected_delivery_date) }}
              </div>
            </td>

            <!-- 8. Status -->
            <td class="text-center align-middle">
              <v-menu v-if="!readonly" location="bottom end">
                <template v-slot:activator="{ props }">
                  <v-chip
                    v-bind="props"
                    :color="getStatusConfig(material.status).color"
                    size="small"
                    variant="flat"
                    class="cursor-pointer font-weight-bold px-2"
                    label
                    style="min-width: 110px; justify-content: center"
                  >
                    <v-icon start size="small">{{
                      getStatusConfig(material.status).icon
                    }}</v-icon>
                    {{ getStatusConfig(material.status).label }}
                  </v-chip>
                </template>
                <v-list density="compact">
                  <v-list-item
                    v-for="status in materialStatuses"
                    :key="status.value"
                    :value="status.value"
                    @click="$emit('statusChange', material, status.value)"
                  >
                    <template v-slot:prepend>
                      <v-icon :color="status.color" size="small">{{
                        status.icon
                      }}</v-icon>
                    </template>
                    <v-list-item-title>{{ status.label }}</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
              <v-chip
                v-else
                :color="getStatusConfig(material.status).color"
                size="small"
                variant="flat"
                label
              >
                {{ getStatusConfig(material.status).label }}
              </v-chip>
            </td>
          </tr>
        </tbody>

        <!-- FOOTER -->
        <tfoot v-if="materials.length > 0">
          <tr class="bg-grey-lighten-4 font-weight-bold">
            <td colspan="5" class="text-right text-uppercase text-caption">
              Suma kosztów:
            </td>
            <td class="text-right text-teal text-body-2">
              {{ formatCurrency(calculatedTotalCost) }}
            </td>
            <td :colspan="4"></td>
          </tr>
        </tfoot>
      </v-table>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import InlineEditField from "@/components/common/InlineEditField.vue";

const props = defineProps({
  materials: { type: Array as () => any[], default: () => [] },
  summary: { type: Object as () => any, default: null },
  totalCost: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
});

const emit = defineEmits([
  "add",
  "edit",
  "delete",
  "statusChange",
  "markAllOrdered",
  "importBatch",
  "updateItem",
  "bulkDelete",
  "bulkStatusChange",
]);

const isFullscreen = ref(false);
const selected = ref<number[]>([]);

// Stan sortowania
const sortBy = ref<string | null>(null);
const sortDesc = ref(false);

// --- LOGIKA SORTOWANIA (COMPUTED) ---
const sortedMaterials = computed(() => {
  if (!sortBy.value) {
    return props.materials;
  }

  const items = [...props.materials];

  return items.sort((a, b) => {
    let valA = a[sortBy.value!];
    let valB = b[sortBy.value!];

    // Specjalna obsługa dla nazw (zagnieżdżony obiekt assortment)
    if (sortBy.value === "name") {
      valA = a.assortment?.name || "";
      valB = b.assortment?.name || "";
    }
    // Specjalna obsługa dla kosztu całkowitego (wyliczanego)
    else if (sortBy.value === "total_cost") {
      valA = a.quantity * a.unit_price;
      valB = b.quantity * b.unit_price;
    }

    // Sortowanie numeryczne
    if (typeof valA === "number" && typeof valB === "number") {
      return sortDesc.value ? valB - valA : valA - valB;
    }

    // Sortowanie stringów
    valA = String(valA || "").toLowerCase();
    valB = String(valB || "").toLowerCase();

    if (valA < valB) return sortDesc.value ? 1 : -1;
    if (valA > valB) return sortDesc.value ? -1 : 1;
    return 0;
  });
});

const handleSort = (key: string) => {
  if (sortBy.value === key) {
    sortDesc.value = !sortDesc.value; // Odwróć kolejność
  } else {
    sortBy.value = key;
    sortDesc.value = false; // Domyślnie rosnąco
  }
};

// --- LOGIKA CHECKBOXÓW ---
const areAllSelected = computed(() => {
  return props.materials.length > 0 && selected.value.length === props.materials.length;
});

const isIndeterminate = computed(() => {
  return selected.value.length > 0 && selected.value.length < props.materials.length;
});

const toggleSelectAll = () => {
  if (areAllSelected.value) {
    selected.value = [];
  } else {
    selected.value = props.materials.map((m) => m.id);
  }
};

const bulkDelete = () => {
  if (confirm(`Czy na pewno chcesz usunąć ${selected.value.length} elementów?`)) {
    emit("bulkDelete", [...selected.value]);
    selected.value = [];
  }
};

const bulkUpdateStatus = (status: string) => {
  emit("bulkStatusChange", [...selected.value], status);
  selected.value = [];
};

const calculatedTotalCost = computed(() => {
  return props.materials.reduce((sum, material) => {
    return sum + Number(material.quantity || 0) * Number(material.unit_price || 0);
  }, 0);
});

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value;
};

const emitUpdate = (material: any, field: string, value: any) => {
  emit("updateItem", material, { [field]: value });
};

const updateDate = (material: any, dateVal: any) => {
  if (!dateVal) return;
  const d = new Date(dateVal);
  const offset = d.getTimezoneOffset();
  const adjustedDate = new Date(d.getTime() - offset * 60 * 1000);
  const dateString = adjustedDate.toISOString().split("T")[0];
  emitUpdate(material, "expected_delivery_date", dateString);
};

const materialStatuses = [
  { value: "NOT_ORDERED", label: "Niezamówiony", color: "red", icon: "mdi-cart-off" },
  { value: "ORDERED", label: "Zamówiony", color: "orange", icon: "mdi-truck-fast" },
  {
    value: "PARTIALLY_IN_STOCK",
    label: "Częściowo",
    color: "blue",
    icon: "mdi-chart-pie",
  },
  {
    value: "IN_STOCK",
    label: "Na stanie",
    color: "green",
    icon: "mdi-package-variant-closed-check",
  },
];

const hasUnordered = computed(() =>
  props.materials.some((m: any) => m.status === "NOT_ORDERED")
);

const getStatusConfig = (status: string) => {
  return (
    materialStatuses.find((s) => s.value === status) || {
      label: status,
      color: "grey",
      icon: "mdi-help-circle",
    }
  );
};

const isOverdue = (material: any) => {
  if (!material.expected_delivery_date) return false;
  const delivery = new Date(material.expected_delivery_date).setHours(0, 0, 0, 0);
  const today = new Date().setHours(0, 0, 0, 0);
  return delivery < today && material.status !== "IN_STOCK";
};

const formatCurrency = (val: any) =>
  new Intl.NumberFormat("pl-PL", { style: "currency", currency: "PLN" }).format(val || 0);

const formatDate = (date: string) =>
  date ? new Date(date).toLocaleDateString("pl-PL") : "—";
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

:deep(.inline-edit-container) {
  justify-content: flex-end;
}

tbody td:nth-child(7) :deep(.inline-edit-container), /* Dostawca */
tbody td:nth-child(8) :deep(.inline-edit-container) {
  /* Notatki */
  justify-content: flex-start;
}

.v-table__wrapper tbody tr td {
  height: 56px;
  vertical-align: middle;
}

.hover-bg:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.fullscreen-card {
  position: fixed !important;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999;
  border-radius: 0 !important;
  display: flex;
  flex-direction: column;
}

.transition-swing {
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.5, 1);
}

/* Sortable Headers */
.sortable-header {
  cursor: pointer;
  transition: background-color 0.2s;
  user-select: none;
}

.sortable-header:hover {
  background-color: #e0e0e0; /* Delikatne podświetlenie przy najechaniu */
  color: black;
}
td,
th {
  padding: 0 10px !important;
}
</style>
