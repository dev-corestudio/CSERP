<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="700"
  >
    <v-card>
      <v-card-title class="bg-info text-white">
        <v-icon start color="white">mdi-history</v-icon>
        Historia zmian: {{ item?.name }}
      </v-card-title>

      <v-card-text class="pt-6" style="max-height: 600px; overflow-y: auto">
        <div v-if="loading" class="text-center py-8">
          <v-progress-circular indeterminate color="info" />
        </div>

        <v-timeline v-else-if="history.length > 0" density="compact" align="start">
          <v-timeline-item
            v-for="entry in history"
            :key="entry.id"
            :dot-color="formatHistoryAction(entry.action).color"
            size="small"
          >
            <template #opposite>
              <!-- ZMIANA: nowa Date().toLocaleString() → formatDate z useFormatters (date-fns) -->
              <div class="text-caption text-medium-emphasis">
                {{ formatDate(entry.created_at) }}
              </div>
            </template>

            <div class="mb-2">
              <div class="font-weight-bold">
                <!-- ZMIANA: lokalna mapa getActionLabel → formatHistoryAction().label -->
                {{ formatHistoryAction(entry.action).label }}
                <span class="text-caption font-weight-regular ml-2">
                  przez {{ entry.user?.name || "System" }}
                </span>
              </div>
              <div class="text-body-2 text-medium-emphasis">
                {{ entry.change_description || entry.description }}
              </div>
            </div>
          </v-timeline-item>
        </v-timeline>

        <v-alert v-else type="info" variant="tonal" class="mt-2">
          Brak historii zmian dla tej pozycji.
        </v-alert>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn @click="$emit('update:modelValue', false)">Zamknij</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { useAssortmentStore } from "@/stores/assortment";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import { useFormatters } from "@/composables/useFormatters";

const props = defineProps({
  modelValue: Boolean,
  item: Object,
});

defineEmits(["update:modelValue"]);

const assortmentStore = useAssortmentStore();
const loading = computed(() => assortmentStore.loading);
const history = computed(() => assortmentStore.history);

// ZMIANA: pobieramy z composables zamiast definiować lokalnie
const { formatHistoryAction } = useStatusFormatter();
const { formatDate } = useFormatters();

watch(
  () => props.modelValue,
  async (isOpen) => {
    if (isOpen && props.item) {
      await assortmentStore.fetchHistory(props.item.id);
    }
  }
);
</script>
