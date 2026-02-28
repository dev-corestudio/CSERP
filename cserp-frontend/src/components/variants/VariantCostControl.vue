<template>
  <v-card class="mb-6" elevation="2">
    <v-card-title class="bg-blue-grey text-white d-flex align-center">
      <v-icon start color="white">mdi-chart-areaspline</v-icon>
      Kontrola Kosztów Produkcji
    </v-card-title>

    <v-card-text class="pt-6">
      <!-- Główne podsumowanie -->
      <v-row class="mb-4">
        <!-- LEWA: Budżet -->
        <v-col cols="6">
          <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis">
            Budżet Całkowity
          </div>
          <div class="text-h4 font-weight-black text-blue-grey-darken-2">
            {{ formatCurrency(budgetTotal) }}
          </div>
          <!-- TKW z wyceny (na sztukę) -->
          <div class="text-caption text-medium-emphasis mt-1">
            <span v-if="tkwZWyceny != null">
              TKW z wyceny:
              <strong class="text-deep-purple-darken-1">{{ formatCurrency(tkwZWyceny) }} / szt.</strong>
            </span>
            <span v-else class="font-italic">Brak TKW z wyceny</span>
          </div>
        </v-col>

        <!-- PRAWA: Rzeczywiste -->
        <v-col cols="6" class="text-right">
          <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis">
            Wykorzystanie budżetu
          </div>
          <div
            class="text-h4 font-weight-black"
            :class="totalCostActual > budgetTotal && budgetTotal > 0 ? 'text-error' : 'text-success'"
          >
            {{ formatCurrency(totalCostActual) }}
          </div>
          <!-- TKW rzeczywiste (obliczane automatycznie) -->
          <div class="text-caption text-medium-emphasis mt-1">
            <span v-if="quantity > 0 && totalCostActual > 0">
              TKW rzeczywiste:
              <strong
                :class="
                  tkwZWyceny != null && tkwRzeczywisteCalc > tkwZWyceny
                    ? 'text-error'
                    : tkwZWyceny != null && tkwRzeczywisteCalc < tkwZWyceny
                    ? 'text-success'
                    : 'text-grey-darken-2'
                "
              >
                {{ formatCurrency(tkwRzeczywisteCalc) }} / szt.
              </strong>
            </span>
            <span v-else class="font-italic">Brak kosztów rzeczywistych</span>
          </div>
          <!-- % wykorzystania -->
          <div v-if="budgetTotal > 0" class="text-caption mt-1">
            {{ calculatePercentage(totalCostActual, budgetTotal) }}% budżetu
          </div>
        </v-col>
      </v-row>

      <!-- Pasek budżetu -->
      <v-progress-linear
        v-if="budgetTotal > 0"
        :model-value="calculatePercentage(totalCostActual, budgetTotal)"
        :color="totalCostActual > budgetTotal ? 'error' : 'blue-grey'"
        height="8"
        rounded
        class="mb-6"
      />

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
                  budgetMaterials > 0 && actualMaterialsCost > budgetMaterials
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
                  budgetServices > 0 && actualServicesCost > budgetServices
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
  quantity: number;
  tkwZWyceny?: number | null;
}>();

const { formatCurrency } = useFormatters();

// Budżet całkowity: z wyceny jeśli istnieje, w przeciwnym razie TKW z wyceny × ilość
const budgetTotal = computed(() => {
  const fromQuotation = props.budgetMaterials + props.budgetServices;
  if (fromQuotation > 0) return fromQuotation;
  if (props.tkwZWyceny && props.quantity > 0) return props.tkwZWyceny * props.quantity;
  return 0;
});

const totalCostActual = computed(
  () => props.actualMaterialsCost + props.actualServicesCost
);

// TKW rzeczywiste = koszty rzeczywiste / ilość (obliczane automatycznie, nie z bazy)
const tkwRzeczywisteCalc = computed(() => {
  if (!props.quantity || props.quantity === 0) return 0;
  return totalCostActual.value / props.quantity;
});

const calculatePercentage = (actual: number, total: number): number => {
  if (!total || total === 0) return 0;
  return Math.min(Math.round((actual / total) * 100), 100);
};
</script>
