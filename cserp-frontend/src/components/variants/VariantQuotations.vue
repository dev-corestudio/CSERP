<template>
  <v-card class="mb-6" elevation="2">
    <v-card-title class="bg-blue text-white d-flex align-center">
      <v-icon start color="white">mdi-calculator</v-icon>
      Wyceny ({{ quotations.length }})
      <v-spacer />
      <v-btn color="white" variant="text" size="small" @click="$emit('create')">
        <v-icon start>mdi-plus</v-icon>
        Nowa wycena
      </v-btn>
    </v-card-title>

    <v-card-text class="pt-4">
      <div v-if="loading" class="text-center py-6">
        <v-progress-circular indeterminate color="primary" />
      </div>

      <div v-else-if="quotations.length > 0">
        <v-list>
          <v-list-item
            v-for="quotation in quotations"
            :key="quotation.id"
            class="mb-2 pa-3 border rounded"
            :class="{ 'bg-green-lighten-5': quotation.is_approved }"
          >
            <template #prepend>
              <v-avatar
                :color="quotation.is_approved ? 'success' : 'grey'"
                size="36"
                class="mr-2"
              >
                <span class="text-h6 text-white">v{{ quotation.version_number }}</span>
              </v-avatar>
            </template>

            <v-list-item-title>
              <span class="text-h6 font-weight-bold">
                {{ formatCurrency(quotation.total_gross) }}
              </span>
              <v-chip
                v-if="quotation.is_approved"
                size="x-small"
                color="success"
                class="ml-2"
              >
                <v-icon start size="x-small">mdi-check</v-icon>
                Zatwierdzona
              </v-chip>
            </v-list-item-title>

            <v-list-item-subtitle class="mt-1">
              Materiały:
              <strong>{{ formatCurrency(quotation.total_materials_cost) }}</strong> •
              Usługi:
              <strong>{{ formatCurrency(quotation.total_services_cost) }}</strong> •
              Marża: <strong>{{ quotation.margin_percent }}%</strong>
            </v-list-item-subtitle>

            <template #append>
              <!-- Podgląd -->
              <v-btn
                icon="mdi-eye"
                variant="text"
                size="small"
                v-tooltip="'Podgląd wyceny'"
                @click="$emit('view', quotation)"
              />
              <!-- Edycja -->
              <v-btn
                v-if="!quotation.is_approved"
                icon="mdi-pencil"
                variant="text"
                size="small"
                color="blue"
                v-tooltip="'Edytuj wycenę'"
                @click="$emit('edit', quotation)"
              />
              <!-- Duplikowanie -->
              <v-btn
                icon="mdi-content-copy"
                variant="text"
                size="small"
                color="purple"
                :loading="duplicatingId === quotation.id"
                v-tooltip="'Duplikuj jako nową wersję'"
                @click="$emit('duplicate', quotation)"
              />
              <!-- Eksport materiałów -->
              <v-btn
                v-if="quotation.is_approved"
                icon="mdi-export"
                variant="text"
                size="small"
                color="teal"
                v-tooltip="'Eksportuj materiały do wariantu'"
                @click="$emit('export', quotation)"
              />
              <!-- Zatwierdzenie -->
              <v-btn
                v-if="!quotation.is_approved"
                icon="mdi-check"
                variant="text"
                size="small"
                color="success"
                v-tooltip="'Zatwierdź wycenę'"
                @click="$emit('approve', quotation)"
              />
              <!-- Usunięcie -->
              <v-btn
                v-if="!quotation.is_approved"
                icon="mdi-delete"
                variant="text"
                size="small"
                color="error"
                v-tooltip="'Usuń wycenę'"
                @click="$emit('delete', quotation)"
              />
            </template>
          </v-list-item>
        </v-list>
      </div>

      <v-alert v-else type="info" variant="tonal" class="text-body-2">
        Brak wycen. Utwórz pierwszą wycenę klikając „Nowa wycena".
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { useFormatters } from "@/composables/useFormatters";

defineProps<{
  quotations: any[];
  loading: boolean;
  duplicatingId: number | null;
}>();

defineEmits(["create", "view", "edit", "duplicate", "export", "approve", "delete"]);

const { formatCurrency } = useFormatters();
</script>

<style scoped>
.v-list-item.border {
  transition: background-color 0.2s;
}
</style>
