<template>
  <v-card elevation="0" class="mb-4 breadcrumbs-bar">
    <v-card-text class="py-3">
      <v-breadcrumbs
        :items="breadcrumbItems"
        class="pa-0"
      >
        <template v-slot:divider>
          <v-icon>mdi-chevron-right</v-icon>
        </template>

        <template v-slot:item="{ item }">
          <v-breadcrumbs-item
            :disabled="item.disabled"
            :to="item.to"
            :exact="item.exact"
          >
            <v-icon v-if="item.icon" :size="18" class="mr-2">
              {{ item.icon }}
            </v-icon>
            {{ item.title }}
          </v-breadcrumbs-item>
        </template>
      </v-breadcrumbs>

      <!-- Current Page Title -->
      <div class="d-flex align-center mt-2">
        <v-icon v-if="pageIcon" size="32" class="mr-3" :color="pageIconColor">
          {{ pageIcon }}
        </v-icon>
        <div>
          <h1 class="text-h4 font-weight-bold">{{ pageTitle }}</h1>
          <p v-if="pageSubtitle" class="text-body-2 text-medium-emphasis mt-1">
            {{ pageSubtitle }}
          </p>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  pageTitle: {
    type: String,
    required: true
  },
  pageSubtitle: {
    type: String,
    default: ''
  },
  pageIcon: {
    type: String,
    default: ''
  },
  pageIconColor: {
    type: String,
    default: 'primary'
  }
})

const breadcrumbItems = computed(() => {
  return [
    {
      title: 'Dashboard',
      icon: 'mdi-home',
      to: { name: 'Dashboard' },
      exact: true
    },
    ...props.items
  ]
})
</script>

<style scoped>
.breadcrumbs-bar {
  background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
  border-bottom: 2px solid #e0e0e0;
}
</style>
