<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="600"
    persistent
  >
    <v-card>
      <v-card-title class="bg-primary text-white d-flex align-center">
        <v-icon start>mdi-pencil</v-icon>
        Edycja Zadania
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>

      <v-card-text class="pt-6">
        <v-form ref="formRef" @submit.prevent="save">
          <div class="mb-4">
            <div class="text-caption text-medium-emphasis">Zadanie</div>
            <div class="text-h6">{{ task?.service_name }}</div>
          </div>

          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="form.status"
                label="Status *"
                :items="statusList"
                item-title="label"
                item-value="value"
                variant="outlined"
                prepend-inner-icon="mdi-list-status"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="form.assigned_to_user_id"
                label="Pracownik"
                :items="workers"
                item-title="name"
                item-value="id"
                variant="outlined"
                prepend-inner-icon="mdi-account"
                clearable
              />
            </v-col>

            <!-- Data Startu -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.actual_start_date"
                label="Data Startu"
                type="datetime-local"
                variant="outlined"
                prepend-inner-icon="mdi-calendar-start"
                @change="recalculateDuration"
              />
            </v-col>

            <!-- Data Końca -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.actual_end_date"
                label="Data Zakończenia"
                type="datetime-local"
                variant="outlined"
                prepend-inner-icon="mdi-calendar-end"
                :disabled="form.status === 'IN_PROGRESS'"
                @change="recalculateDuration"
              />
            </v-col>

            <!-- Czas Rzeczywisty (automatycznie przeliczany) -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model.number="form.actual_time_hours"
                label="Czas Rzeczywisty (h)"
                type="number"
                step="0.01"
                min="0"
                variant="outlined"
                prepend-inner-icon="mdi-clock-outline"
                suffix="h"
                hint="Obliczany z dat lub edycja ręczna"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model.number="form.actual_quantity"
                label="Ilość wykonana"
                type="number"
                min="0"
                variant="outlined"
                prepend-inner-icon="mdi-counter"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.worker_notes"
                label="Notatki"
                variant="outlined"
                rows="3"
                prepend-inner-icon="mdi-note-text"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="close">Anuluj</v-btn>
        <v-btn color="primary" variant="elevated" :loading="saving" @click="save">
          <v-icon start>mdi-content-save</v-icon>
          Zapisz
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { parseISO, differenceInSeconds, isValid } from "date-fns";
import { rcpAdminService } from "@/services/rcpAdminService";
import { useFormatters } from "@/composables/useFormatters";

const props = defineProps({
  modelValue: Boolean,
  task: Object,
  workers: Array,
});

const emit = defineEmits(["update:modelValue", "saved"]);

const formRef = ref(null);
const saving = ref(false);

// ZMIANA: importujemy toInputDate z useFormatters
// PRZED: lokalna implementacja (identyczna kopia była też w TimeLogsDialog.vue):
//   const toInputDate = (isoString) => {
//     if (!isoString) return ''
//     const date = new Date(isoString)
//     const tzOffset = date.getTimezoneOffset() * 60000
//     return new Date(date.getTime() - tzOffset).toISOString().slice(0, 16)
//   }
const { toInputDate } = useFormatters();

const form = ref({
  status: "",
  assigned_to_user_id: null as number | null,
  actual_start_date: "",
  actual_end_date: "",
  actual_time_hours: 0,
  actual_quantity: 0,
  worker_notes: "",
});

// ZMIANA: zamiast hardcoded tablicy — można podłączyć metadataStore
// Zostawione jako stała bo statusy edycji są ograniczone (nie wszystkie stany są dostępne)
const statusList = [
  { label: "W toku", value: "IN_PROGRESS" },
  { label: "Zakończone", value: "COMPLETED" },
  { label: "Anulowane", value: "CANCELLED" },
];

// ZMIANA: recalculateDuration używa date-fns differenceInSeconds zamiast ręcznych obliczeń
// PRZED:
//   const diffMs = end.getTime() - start.getTime()
//   const hours = diffMs / (1000 * 60 * 60)
const recalculateDuration = (): void => {
  if (!form.value.actual_start_date || !form.value.actual_end_date) return;

  const start = parseISO(form.value.actual_start_date);
  const end = parseISO(form.value.actual_end_date);

  if (!isValid(start) || !isValid(end) || end <= start) return;

  const totalSeconds = differenceInSeconds(end, start);
  form.value.actual_time_hours = parseFloat((totalSeconds / 3600).toFixed(2));
};

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen && props.task) {
      form.value = {
        status: props.task.status,
        assigned_to_user_id: props.task.assigned_to_user_id,
        // ZMIANA: toInputDate z useFormatters zamiast lokalnej funkcji
        actual_start_date: toInputDate(props.task.actual_start_date),
        actual_end_date: toInputDate(props.task.actual_end_date),
        actual_time_hours: props.task.actual_time_hours,
        actual_quantity: props.task.actual_quantity,
        worker_notes: props.task.worker_notes,
      };
    }
  }
);

const save = async (): Promise<void> => {
  saving.value = true;
  try {
    const payload = { ...form.value };

    // Jeśli status "W toku", czyścimy datę końca
    if (payload.status === "IN_PROGRESS") {
      payload.actual_end_date = "";
    }

    await rcpAdminService.updateTask(props.task.id, payload);
    emit("saved");
    close();
  } catch (error: any) {
    console.error("Save error:", error);
    alert("Błąd zapisu: " + (error.response?.data?.message || "Nieznany błąd"));
  } finally {
    saving.value = false;
  }
};

const close = (): void => {
  emit("update:modelValue", false);
};
</script>
