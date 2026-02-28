<template>
  <div class="page-header">
    <!-- Breadcrumbs -->
    <v-breadcrumbs
      v-if="breadcrumbs.length > 0"
      :items="breadcrumbItems"
      density="compact"
      class="pa-0 mb-2"
    >
      <template v-slot:divider>
        <v-icon size="small">mdi-chevron-right</v-icon>
      </template>

      <template v-slot:item="{ item }">
        <v-breadcrumbs-item :to="item.to" :disabled="item.disabled" class="text-caption">
          {{ item.title }}
        </v-breadcrumbs-item>
      </template>
    </v-breadcrumbs>

    <!-- Title Section -->
    <div class="d-flex align-center mb-2">
      <!-- Icon -->
      <v-avatar v-if="icon" size="56" class="mr-4" rounded>
        <v-icon :color="iconColor" size="32">{{ icon }}</v-icon>
      </v-avatar>

      <!-- Title & Subtitle -->
      <div class="flex-grow-1">
        <h1 class="text-h4 font-weight-bold title">
          <!-- ZMIANA: Dodano slot 'title' z fallbackiem do props.title -->
          <slot name="title">
            {{ title }}
          </slot>
        </h1>
        <div class="text-subtitle-1 text-medium-emphasis ma-0">
          <!-- ZMIANA: Dodano slot 'subtitle' z fallbackiem do props.subtitle -->
          <slot name="subtitle">
            {{ subtitle }}
          </slot>
        </div>
      </div>

      <!-- Actions Slot -->
      <div v-if="$slots.actions" class="ml-4">
        <slot name="actions" />
      </div>
    </div>

    <!-- Divider -->
    <v-divider class="mb-6" />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: "",
  },
  icon: {
    type: String,
    default: "",
  },
  iconColor: {
    type: String,
    default: "primary",
  },
  breadcrumbs: {
    type: Array,
    default: () => [],
  },
});

// Breadcrumb items with home
const breadcrumbItems = computed(() => {
  const items = [
    {
      title: "Dashboard",
      to: "/dashboard",
      disabled: false,
    },
  ];

  return [...items, ...props.breadcrumbs];
});
</script>

<style scoped>
.page-header {
  padding-top: 0px;
  padding-bottom: 0;
}
.title {
  border: 1px solid transparent;
}
</style>
