<template>
  <div class="series-panel">
    <!-- ── Nagłówek ── -->
    <div class="series-header">
      <div class="d-flex align-center gap-2">
        <v-icon size="15" color="deep-purple-lighten-2">mdi-layers</v-icon>
        <span
          class="text-caption font-weight-bold text-uppercase text-medium-emphasis"
          style="letter-spacing: 0.06em"
        >
          Serie zamówienia
        </span>
        <v-chip v-if="orderNumber" size="x-small" color="deep-purple" variant="tonal">
          {{ orderNumber }}
        </v-chip>
      </div>
      <v-btn
        :loading="loading"
        icon="mdi-refresh"
        size="x-small"
        variant="text"
        color="grey"
        @click="loadSeries"
      />
    </div>

    <!-- ── Skeleton ── -->
    <div v-if="loading" class="px-3 py-2">
      <v-skeleton-loader v-for="i in 3" :key="i" type="list-item-two-line" class="mb-1" />
    </div>

    <!-- ── Brak serii ── -->
    <div v-else-if="series.length === 0" class="empty-state">
      <v-icon size="30" color="grey-lighten-1">mdi-layers-off</v-icon>
      <span class="text-caption text-disabled mt-1">Brak serii</span>
    </div>

    <!-- ── Lista serii (oś czasu) ── -->
    <div v-else class="timeline-list">
      <div
        v-for="(serie, index) in series"
        :key="serie.id"
        class="timeline-row"
        :class="{
          'timeline-row--active': serie.id === currentOrderId,
          'timeline-row--clickable': serie.id !== currentOrderId,
        }"
        @click="navigateToSeries(serie)"
      >
        <!-- Pionowa oś czasu -->
        <div class="timeline-axis">
          <div
            class="timeline-dot"
            :class="
              serie.id === currentOrderId ? 'timeline-dot--active' : 'timeline-dot--idle'
            "
          />
          <div v-if="index < series.length - 1" class="timeline-line" />
        </div>

        <!-- Karta serii -->
        <div
          class="timeline-card"
          :class="{ 'timeline-card--active': serie.id === currentOrderId }"
        >
          <!-- Wiersz 1: numer + badge aktualnej + status -->
          <div class="d-flex align-center justify-space-between mb-1">
            <div class="d-flex align-center gap-1">
              <span
                class="text-body-2 font-weight-bold"
                :class="serie.id === currentOrderId ? 'text-deep-purple-darken-1' : ''"
              >
                {{ serie.full_order_number }}
              </span>
              <v-chip
                v-if="serie.id === currentOrderId"
                size="x-small"
                color="deep-purple"
                variant="flat"
                label
              >
                aktualna
              </v-chip>
            </div>

            <v-chip
              :color="getStatusColor(serie.overall_status)"
              size="x-small"
              variant="tonal"
            >
              {{ getStatusLabel(serie.overall_status) }}
            </v-chip>
          </div>

          <!-- Wiersz 2: opis + liczba wariantów -->
          <div class="d-flex align-center justify-space-between">
            <span
              class="text-caption text-medium-emphasis text-truncate"
              style="max-width: 155px"
              :title="serie.description || ''"
            >
              {{ serie.description || "Brak opisu" }}
            </span>
            <span class="text-caption text-disabled d-flex align-center">
              <v-icon size="11" class="mr-1">mdi-puzzle-outline</v-icon>
              {{ serie.variants_count }} war.
            </span>
          </div>

          <!-- Wiersz 3: data + strzałka nawigacji -->
          <div class="d-flex align-center justify-space-between mt-1">
            <span class="text-caption text-disabled">{{
              formatDate(serie.created_at)
            }}</span>
            <v-icon
              v-if="serie.id !== currentOrderId"
              class="nav-arrow"
              size="14"
              color="deep-purple-lighten-2"
            >
              mdi-arrow-right
            </v-icon>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Stopka ── -->
    <div class="series-footer">
      <v-btn
        color="deep-purple"
        variant="tonal"
        size="small"
        prepend-icon="mdi-layers-plus"
        block
        @click="$emit('create-series')"
      >
        Nowa seria
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useRouter } from "vue-router";
import { seriesService } from "@/services/seriesService";
import type { SeriesListItem } from "@/services/seriesService";
import { useMetadataStore } from "@/stores/metadata";

const props = defineProps<{
  currentOrderId: number;
  orderNumber?: string;
}>();

const emit = defineEmits<{
  "create-series": [];
  "series-changed": [orderId: number];
}>();

const router = useRouter();
const metaStore = useMetadataStore();
const series = ref<SeriesListItem[]>([]);
const loading = ref(false);

onMounted(loadSeries);
watch(() => props.currentOrderId, loadSeries);

async function loadSeries() {
  loading.value = true;
  try {
    series.value = await seriesService.getAllSeries(props.currentOrderId);
  } catch (e) {
    console.error("Błąd ładowania serii:", e);
    series.value = [];
  } finally {
    loading.value = false;
  }
}

/** Nawiguj do wybranej serii — router.push wywoła watch w OrderDetail */
function navigateToSeries(serie: SeriesListItem) {
  if (serie.id === props.currentOrderId) return;
  emit("series-changed", serie.id);
  router.push(`/orders/${serie.id}`);
}

function getStatusColor(status: string): string {
  return metaStore.getConfig("orderStatuses", status)?.color || "grey";
}

function getStatusLabel(status: string): string {
  return metaStore.getLabel("orderStatuses", status);
}

function formatDate(d: string): string {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "2-digit",
  });
}

defineExpose({ loadSeries });
</script>

<style scoped>
/* ── Kontener ── */
.series-panel {
  background: #fff;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

/* ── Nagłówek ── */
.series-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 14px 9px;
  background: #f9f9fb;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

/* ── Empty state ── */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 16px;
}

/* ── Lista ── */
.timeline-list {
  padding: 6px 0 2px;
}

/* ── Wiersz ── */
.timeline-row {
  display: flex;
  align-items: stretch;
  padding: 0 10px 0 14px;
  transition: background-color 0.15s;
}

.timeline-row--clickable {
  cursor: pointer;
}
.timeline-row--clickable:hover {
  background: rgba(103, 58, 183, 0.04);
}
.timeline-row--active {
  background: rgba(103, 58, 183, 0.06);
}

/* ── Oś czasu ── */
.timeline-axis {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 18px;
  flex-shrink: 0;
  margin-right: 12px;
  padding-top: 16px;
}

.timeline-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
  z-index: 1;
}

.timeline-dot--active {
  background: #7e57c2;
  box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.2);
}

.timeline-dot--idle {
  background: #e0e0e0;
  border: 2px solid #bdbdbd;
}

.timeline-line {
  width: 2px;
  flex-grow: 1;
  margin-top: 4px;
  background: linear-gradient(to bottom, #d1c4e9, #e0e0e0);
}

/* ── Karta treści ── */
.timeline-card {
  flex-grow: 1;
  padding: 10px 0 10px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.045);
}

.timeline-row:last-child .timeline-card {
  border-bottom: none;
}
.timeline-card--active {
}

/* ── Strzałka ── */
.nav-arrow {
  opacity: 0;
  transform: translateX(-3px);
  transition: opacity 0.15s, transform 0.15s;
}

.timeline-row--clickable:hover .nav-arrow {
  opacity: 1;
  transform: translateX(0);
}

/* ── Stopka ── */
.series-footer {
  padding: 10px 14px;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  background: #f9f9fb;
}

.gap-1 {
  gap: 4px;
}
.gap-2 {
  gap: 8px;
}
</style>
