<template>
  <v-container fluid>
    <page-header
      title="Stanowiska Robocze"
      subtitle="Zarządzaj parkiem maszynowym i operatorami"
      icon="mdi-factory"
      icon-color="blue-grey"
      :breadcrumbs="[{ title: 'Stanowiska', disabled: true }]"
    >
      <template #actions>
        <v-btn
          color="primary"
          variant="elevated"
          prepend-icon="mdi-plus"
          @click="openCreate"
        >
          Nowe Stanowisko
        </v-btn>
      </template>
    </page-header>

    <!-- Filtry -->
    <v-card elevation="2" class="mb-4">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Szukaj (nazwa, lokalizacja, pracownik)..."
              hide-details
              density="compact"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" class="d-flex justify-end gap-1">
            <v-tooltip text="Resetuj filtry" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" :color="hasActiveFilters ? 'warning' : undefined" @click="resetFilters">
                  <v-icon>mdi-filter-remove</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
            <v-tooltip text="Odśwież" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" :loading="workstationStore.loading" @click="refreshList">
                  <v-icon>mdi-refresh</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabela -->
    <v-card elevation="2">
      <v-data-table
        :headers="headers"
        :items="workstationStore.items"
        :loading="workstationStore.loading"
        :search="search"
        :custom-filter="customFilter"
        hover
      >
        <template v-slot:item.type="{ item }">
          <v-chip
            size="small"
            :color="getTypeColor(item.type)"
            variant="tonal"
            class="font-weight-bold"
          >
            <!-- ZMIANA: Używamy helpera do etykiety -->
            {{ getTypeLabel(item.type) }}
          </v-chip>
        </template>

        <!-- Status -->
        <template v-slot:item.status="{ item }">
          <!-- ZMIANA: Używamy helperów do koloru i etykiety -->
          <v-chip
            size="small"
            :color="getStatusColor(item.status)"
            label
            class="font-weight-bold"
          >
            {{ getStatusLabel(item.status) }}
          </v-chip>
        </template>

        <!-- Operatorzy (AWATARY) -->
        <template v-slot:item.operators="{ item }">
          <div
            v-if="item.operators && item.operators.length > 0"
            class="d-flex align-center pl-2"
          >
            <!-- Awatary operatorów -->
            <v-avatar
              v-for="(op, index) in item.operators.slice(0, 3)"
              :key="op.id"
              size="32"
              color="grey-lighten-2"
              class="avatar-stack-item"
            >
              <span class="text-grey-darken-3 font-weight-bold" style="font-size: 12px">
                {{ getInitials(op.name) }}
              </span>
              <v-tooltip activator="parent" location="top">{{ op.name }}</v-tooltip>
            </v-avatar>

            <!-- Licznik (jeśli jest więcej) -->
            <v-avatar
              v-if="item.operators.length > 3"
              size="32"
              color="grey-darken-3"
              class="avatar-stack-item"
            >
              <span class="text-white font-weight-bold" style="font-size: 11px">
                +{{ item.operators.length - 3 }}
              </span>
              <v-tooltip activator="parent" location="top">
                Pozostali pracownicy (łącznie: {{ item.operators.length }})
              </v-tooltip>
            </v-avatar>
          </div>
          <span v-else class="text-caption text-disabled font-italic pl-2">Brak</span>
        </template>

        <!-- Akcje -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex justify-end align-center" style="white-space: nowrap">
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              color="primary"
              @click="openEdit(item)"
              title="Edytuj"
            ></v-btn>
            <v-btn
              icon="mdi-delete"
              size="small"
              variant="text"
              color="error"
              @click="deleteItem(item)"
              title="Usuń"
            ></v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <workstation-form-dialog
      v-model="dialog"
      :workstation="editingItem"
      @saved="refreshList"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useWorkstationStore } from "@/stores/workstations";
import PageHeader from "@/components/layout/PageHeader.vue";
import WorkstationFormDialog from "@/components/workstations/WorkstationFormDialog.vue";
// Import API tylko do pobrania metadanych
import api from "@/services/api";
import { usePersistedFilters } from "@/composables/usePersistedFilters";

const workstationStore = useWorkstationStore();

const search = usePersistedFilters<string>("workstations:search", "");
const dialog = ref(false);
const editingItem = ref(null);

// Słowniki metadanych (do tłumaczeń statusów)
const workstationStatuses = ref([]);
const workstationTypes = ref([]);

const headers = [
  { title: "Nazwa", key: "name", align: "start" },
  { title: "Typ", key: "type", align: "start" },
  { title: "Lokalizacja", key: "location", align: "start" },
  { title: "Operatorzy", key: "operators", align: "start", sortable: false },
  { title: "Status", key: "status", align: "center" },
  { title: "Akcje", key: "actions", align: "end", sortable: false, width: "120px" },
];

const hasActiveFilters = computed(() => search.value !== "");

const resetFilters = () => {
  search.value = "";
};

const refreshList = async () => {
  await workstationStore.fetchWorkstations();
};

const customFilter = (value, query, item) => {
  if (value == null || query == null) return false;
  const searchText = query.toString().toLowerCase();

  // Filtrowanie po polach zagnieżdżonych (operators)
  if (item.raw.name && item.raw.name.toLowerCase().includes(searchText)) return true;
  if (item.raw.location && item.raw.location.toLowerCase().includes(searchText))
    return true;
  if (item.raw.operators && Array.isArray(item.raw.operators)) {
    return item.raw.operators.some((op) => op.name.toLowerCase().includes(searchText));
  }
  return false;
};

const openCreate = () => {
  editingItem.value = null;
  dialog.value = true;
};

const openEdit = (item) => {
  // Klonowanie obiektu, aby edycja w dialogu nie zmieniała od razu tabeli
  editingItem.value = JSON.parse(JSON.stringify(item));
  dialog.value = true;
};

const deleteItem = async (item) => {
  if (confirm(`Czy na pewno usunąć stanowisko ${item.name}?`)) {
    try {
      await workstationStore.deleteWorkstation(item.id);
    } catch (e) {
      alert("Błąd usuwania: " + e.message);
    }
  }
};

// --- HELPERY ENUM ---

const getTypeLabel = (typeValue) => {
  const type = workstationTypes.value.find((t) => t.value === typeValue);
  return type ? type.label : typeValue;
};

const getTypeColor = (type) => {
  const map = { LASER: "red", CNC: "blue", ASSEMBLY: "green", PAINTING: "orange" };
  return map[type] || "grey";
};

const getStatusLabel = (statusValue) => {
  const status = workstationStatuses.value.find((s) => s.value === statusValue);
  return status ? status.label : statusValue;
};

const getStatusColor = (statusValue) => {
  // Priorytet: kolor z backendu -> mapa lokalna -> grey
  const status = workstationStatuses.value.find((s) => s.value === statusValue);
  if (status && status.color) return status.color;

  const map = {
    IDLE: "success",
    ACTIVE: "primary",
    PAUSED: "warning",
    MAINTENANCE: "error",
  };
  return map[statusValue] || "grey";
};

const getInitials = (name) => {
  if (!name) return "?";
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .substring(0, 2)
    .toUpperCase();
};

// --- INICJALIZACJA ---

onMounted(async () => {
  // Pobieramy listę stanowisk
  refreshList();

  // Pobieramy metadane do tłumaczeń (typy, statusy)
  try {
    const metaRes = await api.get("/metadata");
    if (metaRes.data) {
      workstationStatuses.value = metaRes.data.workstation_statuses || [];
      workstationTypes.value = metaRes.data.workstation_types || [];
    }
  } catch (error) {
    console.error("Błąd pobierania metadanych:", error);
  }
});
</script>

<style scoped>
/* Styl dla nakładających się awatarów */
.avatar-stack-item {
  border: 2px solid white; /* Biała ramka oddzielająca */
  margin-left: -10px; /* Ujemny margines powoduje nakładanie */
  transition: transform 0.2s, z-index 0.2s; /* Animacja przy najechaniu */
  cursor: default;
}

/* Pierwszy awatar nie może mieć ujemnego marginesu */
.avatar-stack-item:first-child {
  margin-left: 0;
}

/* Efekt po najechaniu myszką - wyciągnięcie na wierzch */
.avatar-stack-item:hover {
  z-index: 10;
  transform: translateY(-3px);
  border-color: #f5f5f5;
}
</style>
