<template>
  <v-card v-if="description" elevation="2">
    <v-card-title class="bg-blue text-white d-flex align-center">
      <v-icon start color="white">mdi-text-box-outline</v-icon>
      Opis projektu
    </v-card-title>
    <v-card-text class="pt-4">
      <p class="text-body-1" style="white-space: pre-line">
        {{ description }}
      </p>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
defineProps<{
  description: string | null | undefined;
}>();
</script>
