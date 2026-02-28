<template>
  <v-card class="mb-6" elevation="2">
    <v-card-title class="bg-blue-grey text-white d-flex align-center">
      <v-icon start color="white">mdi-chart-areaspline</v-icon>
      Kontrola Kosztów Produkcji
    </v-card-title>

    <v-card-text class="pt-6">
      <!-- Główne podsumowanie -->
      <v-row class="mb-4">
        <v-col cols="6">
          <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis">
            Budżet Całkowity (Z Wyceny)
          </div>
          <div class="text-h4 font-weight-black text-blue-grey-darken-2">
            {{ formatCurrency(budgetTotal) }}
          </div>
        </v-col>
        <v-col cols="6" class="text-right">
          <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis">
            Wykorzystanie budżetu
          </div>
          <div
            class="text-h4 font-weight-black"
            :class="totalCostActual > budgetTotal ? 'text-error' : 'text-success'"
          >
            {{ formatCurrency(totalCostActual) }}
          </div>
          <div class="text-caption">
            {{ calculatePercentage(totalCostActual, budgetTotal) }}%
          </div>
        </v-col>
      </v-row>

      <v-divider class="mb-6" />

      <!-- Podział: Materiały vs Usługi -->
      <v-row>
        <!-- MATERIAŁY -->
        <v-col cols="12" md="6">
          <v-card variant="outlined" class="pa-3">
            <div class="d-flex align-center mb-2">
              <v-icon color="blue" class="mr-2">mdi-cube-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold">Materiały</span>
            </div>
            <div class="d-flex justify-space-between text-body-2 mb-1">
              <span class="text-medium-emphasis">Budżet:</span>
              <strong>{{ formatCurrency(budgetMaterials) }}</strong>
            </div>
            <div class="d-flex justify-space-between text-body-2 mb-2">
              <span class="text-medium-emphasis">Rzeczywiste:</span>
              <strong
                :class="
                  actualMaterialsCost > budgetMaterials
                    ? 'text-error'
                    : 'text-grey-darken-3'
                "
              >
                {{ formatCurrency(actualMaterialsCost) }}
              </strong>
            </div>
            <v-progress-linear
              :model-value="calculatePercentage(actualMaterialsCost, budgetMaterials)"
              :color="actualMaterialsCost > budgetMaterials ? 'error' : 'blue'"
              height="8"
              rounded
            />
          </v-card>
        </v-col>

        <!-- USŁUGI -->
        <v-col cols="12" md="6">
          <v-card variant="outlined" class="pa-3">
            <div class="d-flex align-center mb-2">
              <v-icon color="orange" class="mr-2">mdi-wrench-outline</v-icon>
              <span class="text-subtitle-1 font-weight-bold">Usługi</span>
            </div>
            <div class="d-flex justify-space-between text-body-2 mb-1">
              <span class="text-medium-emphasis">Budżet:</span>
              <strong>{{ formatCurrency(budgetServices) }}</strong>
            </div>
            <div class="d-flex justify-space-between text-body-2 mb-2">
              <span class="text-medium-emphasis">Rzeczywiste:</span>
              <strong
                :class="
                  actualServicesCost > budgetServices
                    ? 'text-error'
                    : 'text-grey-darken-3'
                "
              >
                {{ formatCurrency(actualServicesCost) }}
              </strong>
            </div>
            <v-progress-linear
              :model-value="calculatePercentage(actualServicesCost, budgetServices)"
              :color="actualServicesCost > budgetServices ? 'error' : 'orange'"
              height="8"
              rounded
            />
          </v-card>
        </v-col>
      </v-row>

      <!-- TKW — widoczne gdy przynajmniej jedna wartość jest ustawiona -->
      <template v-if="tkwZWyceny != null || tkwRzeczywiste != null">
        <v-divider class="my-6" />

        <div class="d-flex align-center mb-3">
          <v-icon color="deep-purple" class="mr-2">mdi-cash-multiple</v-icon>
          <span class="text-subtitle-1 font-weight-bold">TKW — Techniczny Koszt Wytworzenia</span>
        </div>

        <v-row>
          <!-- TKW z wyceny -->
          <v-col cols="12" md="6">
            <v-card variant="outlined" class="pa-3">
              <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-1">
                TKW z wyceny
              </div>
              <div class="text-h5 font-weight-black text-deep-purple-darken-1">
                {{ tkwZWyceny != null ? formatCurrency(tkwZWyceny) : '–' }}
              </div>
            </v-card>
          </v-col>

          <!-- TKW rzeczywiste -->
          <v-col cols="12" md="6">
            <v-card variant="outlined" class="pa-3">
              <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-1">
                TKW rzeczywiste
              </div>
              <div
                class="text-h5 font-weight-black"
                :class="tkwRzeczywiste != null && tkwZWyceny != null
                  ? (tkwRzeczywiste > tkwZWyceny ? 'text-error' : 'text-success')
                  : 'text-deep-purple-darken-1'"
              >
                {{ tkwRzeczywiste != null ? formatCurrency(tkwRzeczywiste) : '–' }}
              </div>
              <div
                v-if="tkwZWyceny != null && tkwRzeczywiste != null"
                class="text-caption mt-1"
                :class="tkwDiff > 0 ? 'text-error' : tkwDiff < 0 ? 'text-success' : 'text-medium-emphasis'"
              >
                {{ tkwDiff > 0 ? '▲' : tkwDiff < 0 ? '▼' : '=' }}
                {{ tkwDiff > 0 ? '+' : '' }}{{ formatCurrency(tkwDiff) }}
                ({{ tkwDiffPercent }}% planu)
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Pasek postępu TKW -->
        <div v-if="tkwZWyceny != null && tkwRzeczywiste != null" class="mt-3">
          <v-progress-linear
            :model-value="calculatePercentage(tkwRzeczywiste, tkwZWyceny)"
            :color="tkwRzeczywiste > tkwZWyceny ? 'error' : 'deep-purple'"
            height="10"
            rounded
          />
          <div class="text-caption text-medium-emphasis text-right mt-1">
            {{ calculatePercentage(tkwRzeczywiste, tkwZWyceny) }}% planu TKW
          </div>
        </div>
      </template>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useFormatters } from "@/composables/useFormatters";

const props = defineProps<{
  budgetMaterials: number;
  budgetServices: number;
  actualMaterialsCost: number;
  actualServicesCost: number;
  tkwZWyceny?: number | null;
  tkwRzeczywiste?: number | null;
}>();

const { formatCurrency } = useFormatters();

const budgetTotal = computed(() => props.budgetMaterials + props.budgetServices);
const totalCostActual = computed(
  () => props.actualMaterialsCost + props.actualServicesCost
);

const tkwDiff = computed(() => {
  if (props.tkwZWyceny == null || props.tkwRzeczywiste == null) return 0;
  return props.tkwRzeczywiste - props.tkwZWyceny;
});

const tkwDiffPercent = computed(() => {
  if (!props.tkwZWyceny || props.tkwRzeczywiste == null) return 0;
  return Math.round((tkwDiff.value / props.tkwZWyceny) * 100);
});

const calculatePercentage = (actual: number, total: number): number => {
  if (!total || total === 0) return 0;
  return Math.min(Math.round((actual / total) * 100), 100);
};
</script>
