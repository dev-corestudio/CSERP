<template>
  <v-dialog :model-value="modelValue" @update:model-value="close" max-width="500">
    <v-card>
      <v-card-title
        :class="action === 'approve' ? 'bg-success text-white' : 'bg-error text-white'"
      >
        {{ action === "approve" ? "Zatwierdź Prototyp" : "Odrzuć Prototyp" }}
      </v-card-title>
      <v-card-text class="pt-4">
        <p class="mb-4">
          {{
            action === "approve"
              ? "Potwierdzasz, że prototyp spełnia wymagania klienta?"
              : "Czy na pewno chcesz odrzucić ten prototyp?"
          }}
        </p>
        <v-textarea
          v-model="feedback"
          label="Notatki / Feedback klienta"
          variant="outlined"
          rows="3"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn @click="close">Anuluj</v-btn>
        <v-btn
          :color="action === 'approve' ? 'success' : 'error'"
          variant="elevated"
          :loading="loading"
          @click="submit"
        >
          {{ action === "approve" ? "Zatwierdź" : "Odrzuć" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

const props = defineProps<{
  modelValue: boolean;
  action: "approve" | "reject";
  loading: boolean;
}>();

const emit = defineEmits(["update:modelValue", "submit"]);

const feedback = ref("");

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      feedback.value = "";
    }
  }
);

const close = () => emit("update:modelValue", false);
const submit = () => emit("submit", feedback.value);
</script>
