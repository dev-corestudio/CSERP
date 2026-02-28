<template>
  <v-card elevation="2">
    <v-card-title class="bg-blue-grey text-white d-flex align-center">
      <v-icon start color="white">mdi-chart-areaspline</v-icon>
      Kontrola Kosztów
      <v-spacer />
      <v-progress-circular
        v-if="loading"
        indeterminate
        size="18"
        width="2"
        color="white"
      />
    </v-card-title>

    <v-card-text class="pt-6">
      <!-- Skeleton -->
      <template v-if="loading">
        <v-skeleton-loader type="heading" class="mb-4" />
        <v-row>
          <v-col cols="6"><v-skeleton-loader type="card" /></v-col>
          <v-col cols="6"><v-skeleton-loader type="card" /></v-col>
        </v-row>
      </template>

      <!-- Brak zatwierdzonych wycen -->
      <template
        v-else-if="!financialSummary || financialSummary.total_approved_gross === 0"
      >
        <div class="text-center py-4">
          <v-icon size="40" color="grey-lighten-1">mdi-file-chart-outline</v-icon>
          <p class="mt-2 text-body-2 text-medium-emphasis">
            Brak zatwierdzonych wycen w wariantach
          </p>
          <p class="text-caption text-medium-emphasis">
            Zatwierdź wycenę wariantu, aby zobaczyć budżet
          </p>
        </div>
      </template>

      <!-- Dane finansowe -->
      <template v-else>
        <!-- Budżet vs Rzeczywistość -->
        <v-row class="mb-4">
          <v-col cols="6">
            <div
              class="text-caption text-uppercase font-weight-bold text-medium-emphasis"
            >
              Budżet (z wycen)
            </div>
            <div class="text-h4 font-weight-black text-blue-grey-darken-2">
              {{ formatCurrency(financialSummary.total_approved_gross) }}
            </div>
            <div class="text-caption text-medium-emphasis mt-1">brutto</div>
          </v-col>
          <v-col cols="6" class="text-right">
            <div
              class="text-caption text-uppercase font-weight-bold text-medium-emphasis"
            >
              Rzeczywiste koszty
            </div>
            <div
              class="text-h4 font-weight-black"
              :class="
                financialSummary.total_actual > financialSummary.total_approved_gross
                  ? 'text-error'
                  : 'text-success'
              "
            >
              {{ formatCurrency(financialSummary.total_actual) }}
            </div>
            <div class="mt-1">
              <v-chip
                size="x-small"
                :color="
                  financialSummary.total_actual > financialSummary.total_approved_gross
                    ? 'error'
                    : 'success'
                "
                variant="tonal"
                class="font-weight-bold"
              >
                {{ utilizationPercent }}%
              </v-chip>
            </div>
          </v-col>
        </v-row>

        <v-divider class="mb-4" />

        <!-- Materiały vs Usługi -->
        <v-row>
          <v-col cols="12" md="6">
            <v-card variant="outlined" class="pa-3">
              <div class="d-flex align-center mb-2">
                <v-icon color="blue" class="mr-2">mdi-cube-outline</v-icon>
                <span class="text-subtitle-1 font-weight-bold">Materiały</span>
              </div>
              <div class="d-flex justify-space-between text-body-2 mb-1">
                <span class="text-medium-emphasis">Budżet:</span>
                <strong>{{
                  formatCurrency(financialSummary.total_approved_materials)
                }}</strong>
              </div>
              <div class="d-flex justify-space-between text-body-2 mb-2">
                <span class="text-medium-emphasis">Rzeczywiste:</span>
                <strong
                  :class="
                    financialSummary.total_actual_materials >
                    financialSummary.total_approved_materials
                      ? 'text-error'
                      : 'text-grey-darken-3'
                  "
                >
                  {{ formatCurrency(financialSummary.total_actual_materials) }}
                </strong>
              </div>
              <v-progress-linear
                :model-value="
                  calcPercent(
                    financialSummary.total_actual_materials,
                    financialSummary.total_approved_materials
                  )
                "
                :color="
                  financialSummary.total_actual_materials >
                  financialSummary.total_approved_materials
                    ? 'error'
                    : 'blue'
                "
                height="8"
                rounded
              />
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card variant="outlined" class="pa-3">
              <div class="d-flex align-center mb-2">
                <v-icon color="orange" class="mr-2">mdi-wrench-outline</v-icon>
                <span class="text-subtitle-1 font-weight-bold">Usługi</span>
              </div>
              <div class="d-flex justify-space-between text-body-2 mb-1">
                <span class="text-medium-emphasis">Budżet:</span>
                <strong>{{
                  formatCurrency(financialSummary.total_approved_services)
                }}</strong>
              </div>
              <div class="d-flex justify-space-between text-body-2 mb-2">
                <span class="text-medium-emphasis">Rzeczywiste:</span>
                <strong
                  :class="
                    financialSummary.total_actual_services >
                    financialSummary.total_approved_services
                      ? 'text-error'
                      : 'text-grey-darken-3'
                  "
                >
                  {{ formatCurrency(financialSummary.total_actual_services) }}
                </strong>
              </div>
              <v-progress-linear
                :model-value="
                  calcPercent(
                    financialSummary.total_actual_services,
                    financialSummary.total_approved_services
                  )
                "
                :color="
                  financialSummary.total_actual_services >
                  financialSummary.total_approved_services
                    ? 'error'
                    : 'orange'
                "
                height="8"
                rounded
              />
            </v-card>
          </v-col>
        </v-row>

        <!-- Zysk -->
        <v-card class="mt-4 pa-3" variant="outlined">
          <div class="d-flex justify-space-between align-center">
            <div class="d-flex align-center">
              <v-icon :color="varianceColor" size="small" class="mr-2">
                {{ varianceIcon }}
              </v-icon>
              <span class="text-body-2 font-weight-bold">Zysk</span>
            </div>
            <span
              class="text-subtitle-1 font-weight-bold"
              :class="`text-${varianceColor}`"
            >
              {{ varianceSign
              }}{{ formatCurrency(Math.abs(financialSummary.total_variance)) }}
              <span
                v-if="financialSummary.variance_percent !== null"
                class="text-caption ml-1"
              >
                ({{ varianceSign }}{{ Math.abs(financialSummary.variance_percent) }}%)
              </span>
            </span>
          </div>
        </v-card>
      </template>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useFormatters } from "@/composables/useFormatters";

const props = defineProps<{
  financialSummary: any;
  loading: boolean;
}>();

const { formatCurrency } = useFormatters();

const calcPercent = (actual: number, total: number): number => {
  if (!total || total === 0) return 0;
  return Math.min(Math.round((actual / total) * 100), 100);
};

const utilizationPercent = computed<number>(() => {
  if (!props.financialSummary) return 0;
  return calcPercent(
    props.financialSummary.total_actual,
    props.financialSummary.total_approved_gross
  );
});

const varianceColor = computed<string>(() => {
  if (!props.financialSummary) return "grey";
  const v = props.financialSummary.total_variance;
  if (v > 0) return "error";
  if (v < 0) return "success";
  return "grey";
});

const varianceIcon = computed<string>(() => {
  if (!props.financialSummary) return "mdi-trending-neutral";
  const v = props.financialSummary.total_variance;
  if (v > 0) return "mdi-trending-down";
  if (v < 0) return "mdi-trending-up";
  return "mdi-trending-neutral";
});

const varianceSign = computed<string>(() => {
  if (!props.financialSummary) return "";
  return props.financialSummary.total_variance > 0 ? "+" : "";
});
</script>
