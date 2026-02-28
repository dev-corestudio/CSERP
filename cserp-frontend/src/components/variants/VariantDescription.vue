<template>
  <v-card class="mb-6" elevation="2">
    <v-card-title class="bg-indigo text-white d-flex align-center">
      <v-icon start color="white">mdi-text-box-outline</v-icon>
      Opis / Specyfikacja
      <v-spacer />
      <v-btn
        v-if="!isEditing"
        icon="mdi-pencil"
        variant="text"
        color="white"
        density="comfortable"
        v-tooltip="'Edytuj opis'"
        @click="startEdit"
      />
    </v-card-title>

    <v-card-text class="pt-4">
      <!-- Tryb wyświetlania -->
      <div
        v-if="!isEditing"
        class="text-body-1"
        style="white-space: pre-wrap; min-height: 60px"
        :class="!variant?.description ? 'text-medium-emphasis font-italic' : ''"
      >
        {{
          variant?.description || "Brak opisu. Kliknij ołówek, aby dodać specyfikację."
        }}
      </div>

      <!-- Tryb edycji -->
      <div v-else>
        <v-textarea
          v-model="buffer"
          variant="outlined"
          rows="5"
          autofocus
          placeholder="Szczegóły techniczne, wymiary, materiały, uwagi..."
          hide-details
          class="mb-3"
        />
        <div class="d-flex gap-2">
          <v-btn color="primary" size="small" :loading="loading" @click="save">
            Zapisz
          </v-btn>
          <v-btn size="small" variant="text" @click="cancel">Anuluj</v-btn>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref } from "vue";

const props = defineProps<{
  variant: any;
  loading: boolean;
}>();

const emit = defineEmits(["update-desc"]);

const isEditing = ref(false);
const buffer = ref("");

const startEdit = () => {
  buffer.value = props.variant?.description || "";
  isEditing.value = true;
};

const cancel = () => {
  isEditing.value = false;
  buffer.value = "";
};

const save = async () => {
  emit("update-desc", buffer.value);
  // Zmiana isEditing na false odbywa się z poziomu rodzica (lub tutaj, jeśli preferujesz optymistyczny update)
  isEditing.value = false;
};
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
