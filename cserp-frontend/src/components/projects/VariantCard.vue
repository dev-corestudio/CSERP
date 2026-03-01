<template>
  <v-card
    elevation="2"
    class="variant-card"
    :class="{ 'border-success': variant.is_approved && variant.type === 'PROTOTYPE' }"
  >
    <v-card-title class="d-flex align-center pa-4">
      <!-- Avatar z numerem wariantu -->
      <v-avatar :color="statusConfig.color" size="48" class="mr-3">
        <span class="text-h5 font-weight-bold text-white">
          {{ variant.variant_number }}
        </span>
      </v-avatar>

      <!-- Nazwa i info -->
      <div class="flex-grow-1">
        <div class="d-flex align-center flex-wrap">
          <div class="text-h6 font-weight-bold mr-2">{{ variant.name }}</div>

          <!-- Typ -->
          <v-chip
            v-if="variant.type === 'PROTOTYPE'"
            size="x-small"
            color="purple"
            variant="flat"
            label
            class="mr-1 mb-1"
          >
            PROTOTYP
          </v-chip>
          <v-chip
            v-else
            size="x-small"
            color="blue"
            variant="flat"
            label
            class="mr-1 mb-1"
          >
            PRODUCKJA SERYJNA
          </v-chip>

          <!-- Chip: ma rodzica (dziecko) -->
          <v-chip
            v-if="variant.parent_variant_id"
            size="x-small"
            color="deep-purple"
            variant="tonal"
            class="mr-1 mb-1"
          >
            <v-icon start size="x-small">mdi-source-branch</v-icon>
            Podwariant
          </v-chip>
        </div>

        <div class="text-caption text-medium-emphasis mt-1 d-flex flex-wrap gap-1">
          <!-- Ilość -->
          <v-chip size="x-small" class="mr-1" variant="outlined">
            <v-icon start size="x-small">mdi-package</v-icon>
            {{ variant.quantity }} szt
          </v-chip>

          <!-- Status -->
          <v-chip size="x-small" :color="statusConfig.color" variant="tonal">
            <v-icon start size="x-small">{{ statusConfig.icon }}</v-icon>
            {{ statusConfig.label }}
          </v-chip>
        </div>
      </div>

      <!-- Menu akcji -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn v-bind="props" icon="mdi-dots-vertical" variant="text" />
        </template>
        <v-list density="compact">
          <v-list-item @click="$emit('view')">
            <template v-slot:prepend>
              <v-icon>mdi-eye</v-icon>
            </template>
            <v-list-item-title>Szczegóły</v-list-item-title>
          </v-list-item>

          <v-list-item @click="$emit('edit')">
            <template v-slot:prepend>
              <v-icon>mdi-pencil</v-icon>
            </template>
            <v-list-item-title>Edytuj</v-list-item-title>
          </v-list-item>

          <!-- DUPLIKUJ -->
          <v-list-item @click="$emit('duplicate')">
            <template v-slot:prepend>
              <v-icon color="deep-purple">mdi-content-copy</v-icon>
            </template>
            <v-list-item-title class="text-deep-purple">Duplikuj</v-list-item-title>
          </v-list-item>

          <v-divider />

          <v-list-item
            v-if="variant.status !== 'CANCELLED'"
            @click="$emit('delete')"
            class="text-error"
          >
            <template v-slot:prepend>
              <v-icon color="error">mdi-delete</v-icon>
            </template>
            <v-list-item-title>Usuń</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-card-title>

    <v-card-text class="pt-0">
      <!-- Opis (skrócony) -->
      <div
        v-if="variant.description"
        class="text-body-2 text-truncate mb-3 text-medium-emphasis"
      >
        {{ variant.description }}
      </div>
      <div v-else class="text-caption text-medium-emphasis font-italic mb-3">
        Brak opisu
      </div>

      <!-- Info o zatwierdzeniu (tylko prototyp) -->
      <div
        v-if="variant.type === 'PROTOTYPE'"
        class="mb-3 pa-2 bg-grey-lighten-4 rounded"
      >
        <div
          v-if="variant.is_approved"
          class="text-caption text-success font-weight-bold d-flex align-center"
        >
          <v-icon size="small" color="success" class="mr-1">mdi-check-decagram</v-icon>
          ZATWIERDZONY
        </div>
        <div v-else class="text-caption text-grey-darken-1 d-flex align-center">
          <v-icon size="small" class="mr-1">mdi-clock-outline</v-icon>
          Oczekuje na decyzję
        </div>

        <div
          v-if="variant.feedback_notes"
          class="text-caption text-medium-emphasis mt-1 text-truncate"
        >
          Feedback: {{ variant.feedback_notes }}
        </div>
      </div>

      <!-- Daty -->
      <v-divider class="mb-2" />
      <div class="d-flex justify-space-between text-caption text-medium-emphasis">
        <div title="Data utworzenia">
          <v-icon size="small" class="mr-1">mdi-calendar-plus</v-icon>
          {{ formatDate(variant.created_at) }}
        </div>
        <div
          v-if="variant.updated_at !== variant.created_at"
          title="Ostatnia aktualizacja"
        >
          <v-icon size="small" class="mr-1">mdi-update</v-icon>
          {{ formatDate(variant.updated_at) }}
        </div>
      </div>
    </v-card-text>

    <v-card-actions>
      <v-btn size="small" variant="text" color="primary" @click="$emit('view')">
        <v-icon start>mdi-eye</v-icon>
        Szczegóły
      </v-btn>
      <v-spacer />
      <!-- Szybki przycisk duplikuj -->
      <v-btn size="small" variant="text" color="deep-purple" @click="$emit('duplicate')">
        <v-icon start>mdi-content-copy</v-icon>
        Duplikuj
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Variant } from "@/types";
import { useStatusFormatter } from "@/composables/useStatusFormatter";

const { formatVariantStatus } = useStatusFormatter();

const props = defineProps<{
  variant: Variant;
}>();

defineEmits<{
  view: [];
  edit: [];
  delete: [];
  duplicate: []; // ← NOWE
}>();

const statusConfig = computed(() => formatVariantStatus(props.variant.status));

const formatDate = (date: string | undefined): string => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("pl-PL");
};
</script>

<style scoped>
.variant-card {
  transition: all 0.3s ease;
}

.variant-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.border-success {
  border: 1px solid rgb(var(--v-theme-success)) !important;
}

.gap-1 {
  gap: 4px;
}
</style>
