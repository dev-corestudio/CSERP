<template>
  <v-card elevation="2" :class="{ 'border-success': quotation.is_approved }">
    <v-card-title class="d-flex align-center">
      <v-chip
        :color="quotation.is_approved ? 'success' : 'grey'"
        size="small"
        class="mr-3"
      >
        v{{ quotation.version_number }}
      </v-chip>

      <span class="flex-grow-1"> Wycena #{{ quotation.version_number }} </span>

      <v-chip v-if="quotation.is_approved" color="success" variant="flat">
        <v-icon start>mdi-check-circle</v-icon>
        ZATWIERDZONA
      </v-chip>
    </v-card-title>

    <v-card-text>
      <v-row dense>
        <v-col cols="6">
          <div class="text-caption text-medium-emphasis">Materiały</div>
          <div class="text-h6 font-weight-bold">
            {{ formatCurrency(quotation.total_materials_cost) }}
          </div>
        </v-col>
        <v-col cols="6">
          <div class="text-caption text-medium-emphasis">Usługi</div>
          <div class="text-h6 font-weight-bold">
            {{ formatCurrency(quotation.total_services_cost) }}
          </div>
        </v-col>
      </v-row>

      <v-divider class="my-3" />

      <v-row dense>
        <v-col cols="6">
          <div class="text-caption text-medium-emphasis">Netto</div>
          <div class="text-h5 font-weight-bold text-primary">
            {{ formatCurrency(quotation.total_net) }}
          </div>
        </v-col>
        <v-col cols="6">
          <div class="text-caption text-medium-emphasis">Brutto (VAT 23%)</div>
          <div class="text-h5 font-weight-bold">
            {{ formatCurrency(quotation.total_gross) }}
          </div>
        </v-col>
      </v-row>

      <v-divider class="my-3" />

      <div class="d-flex align-center">
        <v-icon size="small" class="mr-2">mdi-percent</v-icon>
        <span class="text-caption">Marża: {{ quotation.margin_percent }}%</span>
        <v-spacer />
        <v-icon size="small" class="mr-2">mdi-calendar</v-icon>
        <span class="text-caption">{{ formatDate(quotation.created_at) }}</span>
      </div>

      <div v-if="quotation.notes" class="mt-3">
        <v-chip size="small" variant="outlined">
          <v-icon start size="small">mdi-note-text</v-icon>
          {{ quotation.notes }}
        </v-chip>
      </div>
    </v-card-text>

    <v-card-actions>
      <v-btn size="small" variant="text" color="primary" @click="$emit('view')">
        <v-icon start>mdi-eye</v-icon>
        Podgląd
      </v-btn>

      <v-btn
        v-if="!quotation.is_approved"
        size="small"
        variant="text"
        color="success"
        @click="$emit('approve')"
      >
        <v-icon start>mdi-check</v-icon>
        Zatwierdź
      </v-btn>

      <v-spacer />

      <v-btn
        size="small"
        icon="mdi-file-pdf"
        variant="text"
        color="error"
        @click="$emit('pdf')"
      />
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps({
  quotation: {
    type: Object,
    required: true,
  },
});

defineEmits(["view", "approve", "pdf"]);

const formatCurrency = (value) => {
  if (!value) return "0,00 PLN";
  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  }).format(value);
};

const formatDate = (date) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("pl-PL");
};
</script>

<style scoped>
.border-success {
  border: 2px solid #4caf50 !important;
}
</style>
