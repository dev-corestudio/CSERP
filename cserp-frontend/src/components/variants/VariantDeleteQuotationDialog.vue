<template>
  <v-dialog :model-value="modelValue" @update:model-value="close" max-width="500">
    <v-card>
      <v-card-title class="bg-error text-white">Usuń wycenę</v-card-title>
      <v-card-text class="pt-4">
        Czy na pewno chcesz usunąć wycenę
        <strong>v{{ versionNumber }}</strong
        >? Operacja jest nieodwracalna.
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn @click="close">Anuluj</v-btn>
        <v-btn
          color="error"
          variant="elevated"
          :loading="loading"
          @click="$emit('confirm')"
        >
          Usuń
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean;
  versionNumber?: number;
  loading: boolean;
}>();

const emit = defineEmits(["update:modelValue", "confirm"]);

const close = () => emit("update:modelValue", false);
</script>
