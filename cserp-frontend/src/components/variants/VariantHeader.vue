<template>
  <page-header
    :title="variant ? variant.name : 'Ładowanie...'"
    :subtitle="variant?.project?.full_project_number || ''"
    icon="mdi-package-variant"
    :icon-color="variantStatusColor"
    :breadcrumbs="breadcrumbItems"
  >
    <!-- Edycja nazwy wariantu inline -->
    <template #title>
      <div class="d-flex align-center w-100">
        <inline-edit-field
          v-if="variant"
          :model-value="variant.name"
          text-class="text-h4 font-weight-bold text-high-emphasis"
          placeholder="Wpisz nazwę wariantu..."
          :loading="loading"
          class="variant-name-input flex-grow-1"
          @save="handleNameSave"
        />
        <span v-else>Ładowanie...</span>
      </div>
    </template>

    <template #subtitle>
      Wariant {{ variant?.variant_number || "#" }} •
      {{ variant?.project?.full_project_number }}
    </template>

    <template #actions>
      <v-btn
        variant="outlined"
        prepend-icon="mdi-arrow-left"
        class="mr-2"
        @click="$emit('go-back')"
      >
        Wróć
      </v-btn>
      <v-btn
        variant="flat"
        color="primary"
        prepend-icon="mdi-pencil"
        :disabled="!variant"
        @click="$emit('edit-full')"
      >
        Pełna Edycja
      </v-btn>
    </template>
  </page-header>
</template>

<script setup lang="ts">
import { computed } from "vue";
import PageHeader from "@/components/layout/PageHeader.vue";
import InlineEditField from "@/components/common/InlineEditField.vue";
import { useStatusFormatter } from "@/composables/useStatusFormatter";

const props = defineProps<{
  variant: any;
  loading: boolean;
}>();

const emit = defineEmits(["update-name", "go-back", "edit-full"]);

const { formatVariantStatus } = useStatusFormatter();

const variantStatusColor = computed(() => {
  return props.variant ? formatVariantStatus(props.variant.status).color : "grey";
});

const breadcrumbItems = computed(() => [
  { title: "Projekty", to: "/projects", disabled: false },
  {
    title: props.variant?.project?.full_project_number || "...",
    to: `/projects/${props.variant?.project_id}`,
    disabled: false,
  },
  { title: `Wariant ${props.variant?.variant_number || ""}`, disabled: true },
]);

const handleNameSave = (val: string) => {
  emit("update-name", val);
};
</script>

<style scoped>
.variant-name-input {
  width: 100%;
}
.variant-name-input :deep(.v-field) {
  padding: 0 !important;
  min-height: auto !important;
  background-color: transparent !important;
  box-shadow: none !important;
}
</style>
