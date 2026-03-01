<template>
  <v-card elevation="2">
    <!-- Nagłówek -->
    <v-card-title class="bg-deep-purple-darken-1 text-white d-flex align-center pa-3">
      <v-icon start color="white" size="small">mdi-layers</v-icon>
      <span class="text-body-1 font-weight-bold">
        Serie projektu
        <span class="text-body-2 opacity-70 ml-1">{{ projectNumber }}</span>
      </span>
      <v-spacer />
      <v-progress-circular
        v-if="loading"
        indeterminate
        size="16"
        width="2"
        color="white"
      />
      <template v-else>
        <v-btn
          size="small"
          color="white"
          variant="outlined"
          prepend-icon="mdi-refresh"
          class="mr-2"
          @click="loadSeries"
        >
          Odśwież
        </v-btn>
        <v-btn
          size="small"
          color="white"
          variant="outlined"
          prepend-icon="mdi-plus"
          class="mr-2"
          @click="$emit('create-series')"
        >
          Dodaj
        </v-btn>
      </template>
    </v-card-title>

    <v-card-text class="pa-0">
      <!-- Skeleton loader -->
      <div v-if="loading" class="pa-3">
        <v-skeleton-loader
          v-for="i in 3"
          :key="i"
          type="list-item-two-line"
          class="mb-1"
        />
      </div>

      <!-- Brak serii -->
      <div v-else-if="series.length === 0" class="text-center pa-6 text-medium-emphasis">
        <v-icon size="32" color="grey">mdi-layers-off</v-icon>
        <p class="text-body-2 mt-2">Brak serii</p>
      </div>

      <!-- Lista serii -->
      <v-list v-else density="compact" class="pa-0">
        <v-list-item
          v-for="serie in series"
          :key="serie.id"
          :class="{
            'current-series': serie.id === currentProjectId,
            'other-series': serie.id !== currentProjectId,
          }"
          class="series-item px-3 py-2"
          @click="navigateToSeries(serie)"
        >
          <!-- Prefiks numeryczny serii -->
          <template v-slot:prepend>
            <span
              class="text-caption font-weight-bold mr-4"
              :class="
                serie.id === currentProjectId ? 'text-deep-purple' : 'text-grey-darken-2'
              "
            >
              #{{ serie.series }}
            </span>
          </template>

          <!-- Treść -->
          <v-list-item-title class="text-body-2">
            <span class="font-weight-bold">{{ serie.full_project_number }}</span>
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            <span v-if="serie.description" class="text-truncate">
              {{ serie.description }}
            </span>
            <span v-else class="text-medium-emphasis font-italic">Brak opisu</span>
          </v-list-item-subtitle>

          <!-- Status + data utworzenia -->
          <template v-slot:append>
            <div class="d-flex flex-column align-end">
              <v-chip
                :color="getStatusColor(serie.overall_status)"
                size="x-small"
                variant="tonal"
                class="mb-1"
              >
                {{ getStatusLabel(serie.overall_status) }}
              </v-chip>
              <span class="text-caption text-medium-emphasis">
                {{ formatDate(serie.created_at) }}
              </span>
            </div>
          </template>
        </v-list-item>
      </v-list>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useRouter } from "vue-router";
import { seriesService } from "@/services/seriesService";
import type { SeriesListItem } from "@/services/seriesService";
import { useMetadataStore } from "@/stores/metadata";

// ─── Props / Emits ────────────────────────────────────────────────────────────

const props = defineProps<{
  /** ID aktualnie wyświetlanego projektu */
  currentProjectId: number;
  /** Numer projektu do wyświetlenia (np. "P/0001") */
  projectNumber?: string;
}>();

const emit = defineEmits<{
  "create-series": [];
  "series-changed": [projectId: number];
}>();

// ─── Instancje ────────────────────────────────────────────────────────────────

const router = useRouter();
const metadataStore = useMetadataStore();

// ─── Stan ─────────────────────────────────────────────────────────────────────

const series = ref<SeriesListItem[]>([]);
const loading = ref(false);

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  loadSeries();
});

watch(
  () => props.currentProjectId,
  () => {
    loadSeries();
  }
);

// ─── Metody ───────────────────────────────────────────────────────────────────

/** Załaduj listę serii */
const loadSeries = async () => {
  loading.value = true;
  try {
    series.value = await seriesService.getAllSeries(props.currentProjectId);
  } catch (err) {
    console.error("Błąd ładowania serii:", err);
    series.value = [];
  } finally {
    loading.value = false;
  }
};

/** Nawiguj do innej serii */
const navigateToSeries = (serie: SeriesListItem) => {
  if (serie.id === props.currentProjectId) return;
  emit("series-changed", serie.id);
  router.push(`/projects/${serie.id}`);
};

/** Kolor statusu projektu */
const getStatusColor = (status: string): string => {
  const config = metadataStore.getConfig("projectStatuses", status);
  return config?.color || "grey";
};

/** Etykieta statusu projektu */
const getStatusLabel = (status: string): string => {
  return metadataStore.getLabel("projectStatuses", status);
};

/** Formatuj datę jako DD.MM.YYYY */
const formatDate = (dateStr: string): string => {
  if (!dateStr) return "—";
  return new Date(dateStr).toLocaleDateString("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

// ─── Eksport ──────────────────────────────────────────────────────────────────

defineExpose({ loadSeries });
</script>

<style scoped>
.series-item {
  cursor: pointer;
  transition: background-color 0.15s ease;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  min-height: 56px;
}

.series-item:last-child {
  border-bottom: none;
}

.current-series {
  background-color: rgba(103, 58, 183, 0.06) !important;
}

.other-series:hover {
  background-color: rgba(0, 0, 0, 0.04) !important;
}
</style>
