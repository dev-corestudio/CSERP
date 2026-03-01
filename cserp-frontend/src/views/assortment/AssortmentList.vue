<template>
  <v-container fluid>
    <page-header
      title="Asortyment"
      subtitle="Zarządzaj bazą materiałów i usług"
      icon="mdi-package-variant-closed"
      icon-color="orange"
      :breadcrumbs="[{ title: 'Asortyment', disabled: true }]"
    >
      <template #actions>
        <v-btn
          color="primary"
          variant="elevated"
          prepend-icon="mdi-plus"
          size="large"
          @click="openCreateDialog"
        >
          DODAJ POZYCJĘ
        </v-btn>
      </template>
    </page-header>

    <!-- Filtry -->
    <v-card elevation="2" class="mb-4">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="3">
            <v-text-field
              v-model="search"
              label="Szukaj po nazwie lub opisie"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            />
          </v-col>

          <v-col cols="12" md="2">
            <v-select
              v-model="filters.type"
              label="Typ"
              :items="typeOptions"
              item-title="label"
              item-value="value"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              prepend-inner-icon="mdi-filter-variant"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-autocomplete
              v-model="filters.category"
              label="Kategoria"
              :items="categories"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              prepend-inner-icon="mdi-shape"
              placeholder="Wybierz kategorię"
              no-data-text="Brak kategorii dla wybranego typu"
            />
          </v-col>

          <v-col cols="12" md="2">
            <v-switch
              v-model="showInactive"
              label="Pokaż nieaktywne"
              color="error"
              hide-details
              density="compact"
              @update:model-value="onInactiveToggle"
            />
          </v-col>

          <v-col cols="12" md="2" class="d-flex justify-end gap-1">
            <v-tooltip text="Resetuj filtry" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" :color="hasActiveFilters ? 'warning' : undefined" @click="resetFilters">
                  <v-icon>mdi-filter-remove</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
            <v-tooltip text="Odśwież" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" :loading="loading" @click="fetchData">
                  <v-icon>mdi-refresh</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabela z paginacją server-side -->
    <v-card elevation="2">
      <v-data-table-server
        :headers="headers"
        :items="items"
        :items-length="totalItems"
        :loading="loading"
        :items-per-page="options.itemsPerPage"
        :page="options.page"
        :sort-by="options.sortBy"
        hover
        @update:options="updateOptions"
      >
        <!-- Kolumna: Nazwa -->
        <template v-slot:item.name="{ item }">
          <div class="font-weight-bold">{{ item.name }}</div>
          <div
            class="text-caption text-medium-emphasis text-truncate"
            style="max-width: 300px"
          >
            {{ item.description }}
          </div>
        </template>

        <!-- Kolumna: Typ -->
        <template v-slot:item.type="{ item }">
          <v-chip
            size="small"
            :color="formatAssortmentType(item.type).color"
            variant="tonal"
          >
            <v-icon start size="small">
              {{ formatAssortmentType(item.type).icon }}
            </v-icon>
            {{ formatAssortmentType(item.type).label }}
          </v-chip>
        </template>

        <!-- Kolumna: Cena -->
        <template v-slot:item.default_price="{ item }">
          <span class="font-weight-bold">
            {{ formatCurrency(item.default_price) }}
          </span>
          <span class="text-caption text-medium-emphasis ml-1">
            / {{ getUnitLabel(item.unit) }}
          </span>
        </template>

        <!-- Kolumna: Akcje -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-tooltip text="Edytuj" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-pencil"
                  variant="text"
                  size="small"
                  color="primary"
                  v-bind="props"
                  @click="openEditDialog(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip :text="item.is_active ? 'Dezaktywuj' : 'Aktywuj'" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  :icon="item.is_active ? 'mdi-eye-off' : 'mdi-eye'"
                  variant="text"
                  size="small"
                  :color="item.is_active ? 'grey' : 'success'"
                  v-bind="props"
                  @click="toggleActive(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip text="Historia zmian" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-history"
                  variant="text"
                  size="small"
                  color="info"
                  v-bind="props"
                  @click="openHistoryDialog(item)"
                />
              </template>
            </v-tooltip>
          </div>
        </template>

        <template v-slot:no-data>
          <div class="text-center py-8">
            <v-icon size="64" color="grey-lighten-1">mdi-package-variant-closed</v-icon>
            <div class="text-h6 mt-4 text-medium-emphasis">Brak asortymentu</div>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Zmień filtry lub dodaj nową pozycję.
            </p>
            <v-btn color="primary" variant="outlined" @click="openCreateDialog">
              Dodaj pozycję
            </v-btn>
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Dialogi -->
    <assortment-form-dialog
      v-model="formDialog"
      :item="editingItem"
      @saved="refreshData"
    />

    <assortment-history-dialog v-model="historyDialog" :item="historyItem" />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import PageHeader from "@/components/layout/PageHeader.vue";
import AssortmentFormDialog from "@/components/assortment/AssortmentFormDialog.vue";
import AssortmentHistoryDialog from "@/components/assortment/AssortmentHistoryDialog.vue";
import { useAssortmentStore } from "@/stores/assortment";
import { useMetadataStore } from "@/stores/metadata";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import { useServerTable } from "@/composables/useServerTable";
import { usePersistedFilters } from "@/composables/usePersistedFilters";
import api from "@/services/api";

const { formatAssortmentType } = useStatusFormatter();
const assortmentStore = useAssortmentStore();
const metadataStore = useMetadataStore();

// Filtry (persystowane w localStorage)
const filters = usePersistedFilters<Record<string, any>>("assortment:filters", {
  type: null,
  category: null,
  is_active: true, // domyślnie tylko aktywne
});

// showInactive wynika z is_active — odtwarzamy stan na podstawie przywróconych filtrów
const showInactive = ref(filters.value.is_active === null);
const categories = ref<string[]>([]);

// Server-side table
const {
  items,
  totalItems,
  loading,
  error,
  search,
  options,
  fetchData,
  updateOptions,
} = useServerTable("/assortment", {
  defaultPerPage: 20,
  defaultSortBy: "category",
  defaultSortDir: "asc",
  extraFilters: filters,
  persistKey: "assortment",
});

// Dialogi
const formDialog = ref(false);
const historyDialog = ref(false);
const editingItem = ref(null);
const historyItem = ref(null);

const typeOptions = [
  { label: "Wszystkie", value: null },
  { label: "Materiał", value: "material" },
  { label: "Usługa", value: "service" },
];

const headers = [
  { title: "Nazwa", key: "name", width: "30%" },
  { title: "Typ", key: "type", width: "15%" },
  { title: "Kategoria", key: "category", width: "20%" },
  { title: "Cena domyślna", key: "default_price", align: "end", width: "20%" },
  { title: "Akcje", key: "actions", align: "end", sortable: false, width: "15%" },
];

// Załaduj kategorie (z osobnego endpointu — lekkie dane)
const loadCategories = async () => {
  try {
    const params: any = {};
    if (filters.value.type) params.type = filters.value.type;
    const response = await api.get("/assortment-categories", { params });
    categories.value = response.data || [];
  } catch {
    categories.value = [];
  }
};

// Watch typ → przeładuj kategorie i zresetuj filtr kategorii
watch(
  () => filters.value.type,
  async (newType, oldType) => {
    if (newType !== oldType) {
      filters.value.category = null;
      await loadCategories();
    }
  }
);

const onInactiveToggle = (val: boolean) => {
  filters.value.is_active = val ? null : true;
};

const hasActiveFilters = computed(
  () => search.value !== "" || showInactive.value || filters.value.type !== null || filters.value.category !== null
);

const resetFilters = () => {
  search.value = "";
  showInactive.value = false;
  filters.value = { type: null, category: null, is_active: true };
};

// Helpers
const formatCurrency = (val: any) =>
  new Intl.NumberFormat("pl-PL", { style: "currency", currency: "PLN" }).format(val || 0);

const getUnitLabel = (unit: string) => {
  const unitMap: Record<string, string> = {
    m2: "m²",
    m: "mb",
    kg: "kg",
    szt: "szt",
    h: "h",
    l: "l",
  };
  return unitMap[unit] || unit;
};

// Akcje
const openCreateDialog = () => {
  editingItem.value = null;
  formDialog.value = true;
};

const openEditDialog = (item: any) => {
  editingItem.value = { ...item };
  formDialog.value = true;
};

const openHistoryDialog = (item: any) => {
  historyItem.value = item;
  historyDialog.value = true;
};

const toggleActive = async (item: any) => {
  try {
    await api.patch(`/assortment/${item.id}/toggle-active`);
    await fetchData();
  } catch (err) {
    console.error("Błąd zmiany statusu:", err);
  }
};

const refreshData = async () => {
  await fetchData();
  await loadCategories();
};

onMounted(async () => {
  await loadCategories();
  await fetchData();
});
</script>
