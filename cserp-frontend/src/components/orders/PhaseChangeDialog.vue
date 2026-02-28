<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="500"
  >
    <v-card>
      <v-card-title class="bg-primary text-white">
        <v-icon start color="white">mdi-timeline</v-icon>
        Zmień fazę wariantu
      </v-card-title>

      <v-card-text class="pt-6">
        <div class="mb-4">
          <div class="text-caption text-medium-emphasis">Wariant:</div>
          <div class="text-h6 font-weight-bold">
            {{ variant?.variant_number }} - {{ variant?.name }}
          </div>
        </div>

        <div class="mb-4">
          <div class="text-caption text-medium-emphasis">Obecna faza:</div>
          <v-chip :color="currentPhaseColor" class="mt-1">
            {{ currentPhaseLabel }}
          </v-chip>
        </div>

        <v-select
          v-model="selectedPhase"
          :items="availablePhases"
          item-title="label"
          item-value="value"
          label="Nowa faza *"
          variant="outlined"
          prepend-inner-icon="mdi-arrow-right"
        />

        <v-alert type="info" variant="tonal" class="mt-4">
          Zmiana fazy może wpłynąć na przepływ pracy całego zamówienia
        </v-alert>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn @click="$emit('update:modelValue', false)">Anuluj</v-btn>
        <v-btn
          color="primary"
          variant="elevated"
          :loading="loading"
          :disabled="!selectedPhase || selectedPhase === variant?.status"
          @click="handleSubmit"
        >
          <v-icon start>mdi-check</v-icon>
          Zmień fazę
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useVariantsStore } from "@/stores/variants";
import { useMetadataStore } from "@/stores/metadata";

const props = defineProps({
  modelValue: Boolean,
  variant: Object, // ZMIANA: było line
});

const emit = defineEmits(["update:modelValue", "saved"]);

const variantsStore = useVariantsStore();
const metadataStore = useMetadataStore();
const loading = ref(false);
const selectedPhase = ref(null);

const availablePhases = computed(() => {
  // Pobierz statusy z metadata, odfiltruj obecny
  return metadataStore.orderStatuses.filter(
    (p) => p.value !== props.variant?.status
  );
});

const currentPhaseLabel = computed(() => {
  return metadataStore.getLabel("orderStatuses", props.variant?.status);
});

const currentPhaseColor = computed(() => {
  const colors: Record<string, string> = {
    QUOTATION: "blue",
    PROTOTYPE: "purple",
    PRODUCTION: "orange",
    DELIVERY: "cyan",
    COMPLETED: "green",
    CANCELLED: "red",
    DRAFT: "grey",
  };
  return colors[props.variant?.status] || "grey";
});

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      selectedPhase.value = null;
    }
  }
);

const handleSubmit = async () => {
  if (!selectedPhase.value) return;

  loading.value = true;
  try {
    await variantsStore.updatePhase(props.variant.id, selectedPhase.value);
    emit("saved");
    emit("update:modelValue", false);
  } catch (error: any) {
    console.error("Phase change error:", error);
    alert(
      "Błąd podczas zmiany fazy: " + (error.response?.data?.message || error.message)
    );
  } finally {
    loading.value = false;
  }
};
</script>
