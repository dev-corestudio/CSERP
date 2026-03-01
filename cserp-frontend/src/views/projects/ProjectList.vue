<template>
  <v-container fluid>
    <page-header
      title="Projekty"
      subtitle="Zarządzaj projektami klientów"
      icon="mdi-clipboard-text"
      icon-color="blue"
      :breadcrumbs="[{ title: 'Projekty', disabled: true }]"
    >
      <template #actions>
        <v-btn
          color="primary"
          variant="elevated"
          prepend-icon="mdi-plus"
          size="large"
          @click="createNewProject"
        >
          NOWY PROJEKT
        </v-btn>
      </template>
    </page-header>

    <!-- Filtry -->
    <v-card elevation="2" class="mb-4">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="4">
            <v-text-field
              v-model="search"
              label="Szukaj projektu lub klienta"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-select
              v-model="filters.status"
              label="Filtruj po statusie"
              :items="statusOptions"
              item-title="label"
              item-value="value"
              variant="outlined"
              density="compact"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-select
              v-model="filters.quick_filter"
              label="Zakres projektów"
              :items="quickFilterOptions"
              item-title="label"
              item-value="value"
              variant="outlined"
              density="compact"
              hide-details
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

    <!-- Error -->
    <v-alert
      v-if="error"
      type="error"
      variant="tonal"
      prominent
      closable
      class="mb-4"
      @click:close="error = null"
    >
      <v-alert-title>Błąd</v-alert-title>
      {{ error }}
      <template v-slot:append>
        <v-btn color="error" variant="outlined" @click="fetchData">
          Spróbuj ponownie
        </v-btn>
      </template>
    </v-alert>

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
        class="projects-table"
        @click:row="handleRowClick"
        @update:options="updateOptions"
      >
        <!-- Pełny numer projektu -->
        <template v-slot:item.full_project_number="{ item }">
          <span class="font-weight-bold text-primary">{{ item.full_project_number }}</span>
        </template>

        <template v-slot:item.customer="{ item }">
          <div v-if="item.customer">
            <div class="font-weight-bold">{{ item.customer.name }}</div>
          </div>
          <span v-else class="text-medium-emphasis">-</span>
        </template>

        <template v-slot:item.description="{ item }">
          <div class="text-truncate" style="max-width: 400px">
            {{ item.description || "-" }}
          </div>
        </template>

        <template v-slot:item.lines="{ item }">
          <v-chip size="small" color="blue" variant="tonal">
            {{ item.variants?.length || item.product_lines?.length || 0 }}
          </v-chip>
        </template>

        <template v-slot:item.planned_delivery_date="{ item }">
          <span v-if="item.planned_delivery_date">
            {{ formatDate(item.planned_delivery_date) }}
          </span>
          <span v-else class="text-medium-emphasis">-</span>
        </template>

        <template v-slot:item.overall_status="{ item }">
          <v-chip
            size="small"
            :color="formatProjectStatus(item.overall_status).color"
            variant="flat"
          >
            <v-icon start size="small">
              {{ formatProjectStatus(item.overall_status).icon }}
            </v-icon>
            {{ formatProjectStatus(item.overall_status).label }}
          </v-chip>
        </template>

        <template v-slot:item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template v-slot:no-data>
          <div class="text-center py-8">
            <v-icon size="64" color="grey">mdi-clipboard-text-off</v-icon>
            <div class="text-h6 mt-4 text-medium-emphasis">Brak projektów</div>
            <v-btn
              color="primary"
              variant="outlined"
              class="mt-4"
              @click="createNewProject"
            >
              Utwórz pierwszy projekt
            </v-btn>
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Dialog tworzenia/edycji projektu -->
    <project-form-dialog
      v-model="projectDialog"
      :project="editingProject"
      @saved="handleProjectSaved"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PageHeader from "@/components/layout/PageHeader.vue";
import ProjectFormDialog from "@/components/projects/ProjectFormDialog.vue";
import { useMetadataStore } from "@/stores/metadata";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import { useServerTable } from "@/composables/useServerTable";
import { usePersistedFilters } from "@/composables/usePersistedFilters";

const metadataStore = useMetadataStore();
const { formatProjectStatus } = useStatusFormatter();
const router = useRouter();

// Filtry (reaktywne — automatycznie przeładowują tabelę, persystowane w localStorage)
const filters = usePersistedFilters("projects:filters", {
  status: "all",
  quick_filter: "active",
});

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
} = useServerTable("/projects", {
  defaultPerPage: 15,
  defaultSortBy: "created_at",
  defaultSortDir: "desc",
  extraFilters: filters,
  persistKey: "projects",
});

const hasActiveFilters = computed(
  () => search.value !== "" || filters.value.status !== "all" || filters.value.quick_filter !== "active"
);

const resetFilters = () => {
  search.value = "";
  filters.value = { status: "all", quick_filter: "active" };
};

// Dialog state
const projectDialog = ref(false);
const editingProject = ref(null);

const statusOptions = computed(() => [
  { label: "Wszystkie", value: "all" },
  ...metadataStore.projectStatuses,
]);

const quickFilterOptions = [
  { label: "Aktywne", value: "active" },
  { label: "Zakończone", value: "completed" },
  { label: "Wszystkie", value: "all" },
];

const headers = [
  { title: "Numer", key: "full_project_number", width: "160px", sortable: false },
  { title: "Klient", key: "customer", width: "200px", sortable: false },
  { title: "Opis", key: "description", width: "400px", sortable: false },
  { title: "Warianty", key: "lines", align: "center", width: "20px", sortable: false },
  { title: "Realizacja", key: "planned_delivery_date", align: "end", width: "130px" },
  { title: "Status", key: "overall_status", align: "center", width: "160px" },
  { title: "Utworzono", key: "created_at", align: "center", width: "120px" },
];

const formatDate = (date: string) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("pl-PL");
};

const createNewProject = () => {
  editingProject.value = null;
  projectDialog.value = true;
};

const handleProjectSaved = async () => {
  await fetchData();
};

const viewProject = (id: number) => {
  router.push(`/projects/${id}`);
};

const handleRowClick = (_event: any, { item }: any) => {
  viewProject(item.id);
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.projects-table :deep(tbody tr) {
  cursor: pointer;
  transition: background-color 0.2s;
}
.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
