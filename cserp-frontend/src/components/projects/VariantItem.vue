<template>
  <v-card class="mb-3" variant="outlined">
    <v-card-text>
      <div class="d-flex align-center">
        <v-avatar
          :color="formatVariantStatus(line.status).color"
          size="40"
          class="mr-3"
        >
          <span class="text-h6 font-weight-bold text-white">{{
            line.variant_number
          }}</span>
        </v-avatar>
        <div class="flex-grow-1">
          <div class="text-h6 font-weight-bold">{{ line.name }}</div>
          <div class="text-caption text-medium-emphasis">
            <v-chip size="x-small" class="mr-2">
              <v-icon start size="x-small">mdi-package</v-icon>
              {{ line.quantity }} szt
            </v-chip>
            <v-chip size="x-small" :color="formatVariantStatus(line.status).color">
              {{ formatVariantStatus(line.status).label }}
            </v-chip>
          </div>
        </div>

        <div class="text-right">
          <v-btn-group variant="outlined" density="compact">
            <v-btn icon="mdi-eye" size="small" @click="$emit('view')" />
            <v-btn icon="mdi-pencil" size="small" @click="$emit('edit')" />
            <v-btn
              icon="mdi-delete"
              size="small"
              color="error"
              @click="$emit('delete')"
            />
          </v-btn-group>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>
<script setup lang="ts">
import { useStatusFormatter } from "@/composables/useStatusFormatter";

const { formatVariantStatus } = useStatusFormatter();

const props = defineProps({
  line: {
    type: Object,
    required: true,
  },
});

defineEmits(["view", "edit", "delete"]);
</script>
