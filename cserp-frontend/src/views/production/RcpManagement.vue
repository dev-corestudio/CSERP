<template>
  <v-container fluid>
    <page-header
      title="Zarządzanie RCP"
      subtitle="Korekta czasów pracy i monitoring zadań"
      icon="mdi-timer-cog"
      icon-color="teal"
      :breadcrumbs="[{ title: 'RCP Admin', disabled: true }]"
    />
    <!-- Filtry -->
    <v-card elevation="2" class="mb-4">
      <v-card-text>
        <v-row align="center" dense>
          <!-- Szukaj -->
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.search"
              label="Szukaj (zadanie, wariant, klient)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="debouncedSearch"
            />
          </v-col>

          <!-- Status -->
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.status"
              label="Status"
              :items="statusOptions"
              item-title="label"
              item-value="value"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="applyFilters"
            />
          </v-col>

          <!-- Pracownik -->
          <v-col cols="12" md="3">
            <v-autocomplete
              v-model="filters.worker_id"
              label="Pracownik"
              :items="workers"
              item-title="name"
              item-value="id"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              placeholder="Wszyscy"
              @update:model-value="applyFilters"
            />
          </v-col>

          <!-- Stanowisko -->
          <v-col cols="12" md="3">
            <v-autocomplete
              v-model="filters.workstation_id"
              label="Stanowisko"
              :items="workstations"
              item-title="name"
              item-value="id"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              placeholder="Wszystkie"
              @update:model-value="applyFilters"
            />
          </v-col>

          <!-- Data OD (datetime-local) -->
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.date_from"
              label="Data i czas od"
              type="datetime-local"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              prepend-inner-icon="mdi-calendar-start"
              @update:model-value="applyFilters"
            />
          </v-col>

          <!-- Data DO (datetime-local) -->
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.date_to"
              label="Data i czas do"
              type="datetime-local"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              prepend-inner-icon="mdi-calendar-end"
              @update:model-value="applyFilters"
            />
          </v-col>

          <!-- Reset -->
          <v-col cols="12" md="6" class="d-flex justify-end align-center gap-1">
            <v-tooltip text="Resetuj filtry" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" @click="resetFilters">
                  <v-icon>mdi-filter-remove</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabela Zadań -->
    <v-card elevation="2">
      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="rcpAdminStore.tasks"
        :items-length="rcpAdminStore.totalTasks"
        :loading="rcpAdminStore.loading"
        :page="rcpAdminStore.currentPage"
        @update:options="handleTableOptions"
        hover
        density="comfortable"
        class="rcp-admin-table"
        :row-props="rowProps"
      >
        <!-- Kolumna: Zadanie + Wariant Produktowa -->
        <template v-slot:item.task_info="{ item }">
          <div class="py-2">
            <div
              class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-1 d-flex align-center"
            >
              {{ item.service_name }}
              <v-chip
                v-if="item.status === 'IN_PROGRESS'"
                size="x-small"
                color="red"
                variant="flat"
                class="ml-2 font-weight-bold pulse-animation"
                label
              >
                LIVE
              </v-chip>
            </div>

            <div class="d-flex align-center flex-wrap gap-2">
              <v-chip
                v-if="item.production_order?.variant?.variant_number"
                size="x-small"
                color="blue-grey"
                variant="tonal"
                class="font-weight-bold"
              >
                Wariant {{ item.production_order.variant.variant_number }}
              </v-chip>

              <router-link
                v-if="item.production_order?.variant?.id"
                :to="`/orders/${item.production_order.variant.order_id}/variants/${item.production_order.variant.id}`"
                class="text-caption text-decoration-none text-primary font-weight-medium hover-underline"
                @click.stop
              >
                {{ item.production_order.variant.name }}
              </router-link>
              <span v-else class="text-caption text-medium-emphasis">-</span>

              <span
                class="text-caption text-medium-emphasis text-truncate"
                style="max-width: 150px"
              >
                • {{ item.production_order?.variant?.order?.customer?.name }}
              </span>
            </div>
          </div>
        </template>

        <!-- Kolumna: Pracownik -->
        <template v-slot:item.worker="{ item }">
          <div class="d-flex align-center justify-start" v-if="item.assigned_worker">
            <v-tooltip location="top">
              <template v-slot:activator="{ props }">
                <v-avatar
                  v-bind="props"
                  color="primary"
                  size="32"
                  variant="tonal"
                  class="cursor-help mr-2"
                >
                  <span class="text-caption font-weight-bold">
                    {{ item.assigned_worker.name?.charAt(0) }}
                  </span>
                </v-avatar>
              </template>
              <span>{{ item.assigned_worker.name }}</span>
            </v-tooltip>
            <span class="text-body-2 font-weight-medium">
              {{ formatWorkerName(item.assigned_worker.name) }}
            </span>
          </div>
          <div v-else class="text-medium-emphasis">-</div>
        </template>

        <!-- Kolumna: Start -->
        <template v-slot:item.actual_start_date="{ item }">
          <div class="text-caption font-weight-medium text-grey-darken-2">
            {{ formatDateTime(item.actual_start_date) }}
          </div>
        </template>

        <!-- Kolumna: Koniec -->
        <template v-slot:item.actual_end_date="{ item }">
          <div class="text-caption font-weight-medium text-grey-darken-2">
            {{ formatDateTime(item.actual_end_date) }}
          </div>
        </template>

        <!-- Kolumna: Stanowisko -->
        <template v-slot:item.workstation="{ item }">
          <div class="text-body-2 text-medium-emphasis">
            {{ item.workstation?.name || "-" }}
          </div>
        </template>

        <!-- Kolumna: Czas -->
        <template v-slot:item.time="{ item }">
          <div class="d-flex flex-column align-end">
            <span
              class="text-body-2 font-weight-bold"
              :class="
                item.status === 'IN_PROGRESS'
                  ? 'text-green-darken-2'
                  : 'text-grey-darken-3'
              "
            >
              {{ calculateLiveDuration(item) }}
            </span>
            <span class="text-caption text-medium-emphasis">
              Est: {{ formatDuration(item.estimated_time_hours) }}
            </span>
          </div>
        </template>

        <!-- Kolumna: Status -->
        <template v-slot:item.status="{ item }">
          <v-chip
            size="small"
            :color="formatProductionStatus(item.status).color"
            variant="flat"
            class="font-weight-bold"
          >
            {{ formatProductionStatus(item.status).label }}
          </v-chip>
        </template>

        <!-- Akcje -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              color="primary"
              @click="openEditDialog(item)"
              title="Edytuj zadanie"
            />
            <v-btn
              icon="mdi-history"
              size="small"
              variant="text"
              color="orange"
              @click="openLogsDialog(item)"
              title="Logi czasu"
            />
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <task-edit-dialog
      v-model="editDialog"
      :task="selectedTask"
      :workers="workers"
      @saved="refreshData"
    />

    <time-logs-dialog v-model="logsDialog" :task="selectedTask" @updated="refreshData" />
  </v-container>
</template>
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import PageHeader from "@/components/layout/PageHeader.vue";
import TaskEditDialog from "@/components/production/TaskEditDialog.vue";
import TimeLogsDialog from "@/components/production/TimeLogsDialog.vue";
import { useRcpAdminStore } from "@/stores/rcpAdmin";
import { useWorkstationStore } from "@/stores/workstations";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import { usePersistedFilters } from "@/composables/usePersistedFilters";
import { debounce } from "lodash";

const rcpAdminStore = useRcpAdminStore();
const workstationStore = useWorkstationStore();
const { formatProductionStatus } = useStatusFormatter();

const itemsPerPage = ref(20);
const sortBy = ref([{ key: "updated_at", order: "desc" }]);
const editDialog = ref(false);
const logsDialog = ref(false);
const selectedTask = ref(null);
const workers = ref([]);
const workstations = ref([]);

// Filtry - persystowane w localStorage
const filters = usePersistedFilters("rcp:filters", {
  search: "",
  status: "all",
  worker_id: null,
  workstation_id: null,
  date_from: null,
  date_to: null,
});

const now = ref(new Date());
let timerInterval: ReturnType<typeof setInterval> | null = null;

const statusOptions = [
  { label: "Wszystkie", value: "all" },
  { label: "W toku", value: "IN_PROGRESS" },
  { label: "Zakończone", value: "COMPLETED" },
  { label: "Anulowane", value: "CANCELLED" },
];

const headers = [
  { title: "Zadanie / Wariant", key: "task_info", sortable: false, width: "350px" },
  { title: "Pracownik", key: "worker", sortable: false, align: "start", width: "180px" },
  { title: "Stanowisko", key: "workstation", sortable: false, width: "150px" },
  { title: "Start", key: "actual_start_date", width: "140px", sortable: true },
  { title: "Koniec", key: "actual_end_date", width: "140px", sortable: true },
  { title: "Czas", key: "time", align: "end", sortable: false, width: "120px" },
  { title: "Status", key: "status", align: "center", sortable: false, width: "130px" },
  { title: "Akcje", key: "actions", align: "end", sortable: false, width: "100px" },
];

const rowProps = (item) => {
  if (item.item.status === "IN_PROGRESS") {
    return { class: "bg-green-lighten-5" };
  }
  return {};
};

const fetchData = async () => {
  await workstationStore.fetchWorkers();
  workers.value = workstationStore.workers;

  await workstationStore.fetchWorkstations();
  workstations.value = workstationStore.items;
};

const handleTableOptions = ({ page }) => {
  rcpAdminStore.fetchTasks(page);
};

const applyFilters = () => {
  rcpAdminStore.updateFilters(filters.value);
};

const debouncedSearch = debounce(applyFilters, 500);

const resetFilters = () => {
  filters.value = {
    search: "",
    status: "all",
    worker_id: null,
    workstation_id: null,
    date_from: null,
    date_to: null,
  };
  applyFilters();
};

const openEditDialog = (task) => {
  selectedTask.value = task;
  editDialog.value = true;
};

const openLogsDialog = (task) => {
  selectedTask.value = task;
  logsDialog.value = true;
};

const refreshData = () => {
  rcpAdminStore.fetchTasks(rcpAdminStore.currentPage);
};

const calculateLiveDuration = (item) => {
  if (item.status === "IN_PROGRESS" && item.actual_start_date) {
    const start = new Date(item.actual_start_date).getTime();
    const current = now.value.getTime();
    const diffMs = Math.max(0, current - start);
    const hours = diffMs / (1000 * 60 * 60);
    const pauseHours = (item.total_pause_duration_seconds || 0) / 3600;
    return formatDuration(Math.max(0, hours - pauseHours));
  }
  return formatDuration(item.actual_time_hours);
};

const formatDuration = (hours) => {
  if (!hours) return "0h 0m";
  const totalMin = Math.round(hours * 60);
  const h = Math.floor(totalMin / 60);
  const m = totalMin % 60;
  return `${h}h ${m}m`;
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return "-";
  return new Date(dateStr).toLocaleString("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const formatWorkerName = (fullName) => {
  if (!fullName) return "-";
  const parts = fullName.split(" ");
  if (parts.length >= 2) {
    const firstName = parts[0];
    const lastName = parts.slice(1).join(" ");
    return `${firstName.charAt(0)}. ${lastName}`;
  }
  return fullName;
};

onMounted(() => {
  fetchData();
  timerInterval = setInterval(() => {
    now.value = new Date();
  }, 1000);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});
</script>
<style scoped>
.rcp-admin-table :deep(td) {
  vertical-align: middle !important;
}
.hover-underline:hover {
  text-decoration: underline !important;
}
.gap-2 {
  gap: 8px;
}
.cursor-help {
  cursor: help;
}
@keyframes pulse-red {
  0% {
    box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(244, 67, 54, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(244, 67, 54, 0);
  }
}
.pulse-animation {
  animation: pulse-red 1.5s infinite;
}
</style>
