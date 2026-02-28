<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="600"
    persistent
  >
    <v-card>
      <v-card-title class="bg-deep-purple text-white d-flex align-center">
        <v-icon start color="white">{{ isEdit ? "mdi-pencil" : "mdi-plus" }}</v-icon>
        {{ isEdit ? "Edytuj zadanie RCP" : "Dodaj zadanie RCP" }}
      </v-card-title>

      <v-card-text class="pt-6">
        <v-form ref="formRef" v-model="formValid">
          <v-row>
            <v-col cols="8">
              <v-text-field
                v-model="form.service_name"
                label="Nazwa usługi / operacji *"
                :rules="[(v) => !!v || 'Podaj nazwę']"
                variant="outlined"
                density="compact"
                placeholder="np. Cięcie laserowe, Montaż, Malowanie"
              />
            </v-col>
            <v-col cols="4">
              <v-text-field
                v-model.number="form.step_number"
                label="Krok #"
                type="number"
                min="1"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <!-- Workstation -->
            <v-col cols="12">
              <v-autocomplete
                v-model="form.workstation_id"
                :items="workstations"
                item-title="name"
                item-value="id"
                label="Stanowisko robocze"
                variant="outlined"
                density="compact"
                clearable
              >
                <template v-slot:item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template v-slot:prepend>
                      <v-icon color="blue" size="small">mdi-desktop-tower</v-icon>
                    </template>
                    <v-list-item-subtitle v-if="item.raw.type">
                      {{ item.raw.type }}
                      {{ item.raw.location ? "• " + item.raw.location : "" }}
                    </v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <!-- Assigned worker -->
            <v-col cols="12">
              <v-autocomplete
                v-model="form.assigned_to_user_id"
                :items="workers"
                item-title="name"
                item-value="id"
                label="Przypisany pracownik"
                variant="outlined"
                density="compact"
                clearable
              >
                <template v-slot:item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template v-slot:prepend>
                      <v-avatar size="28" color="grey-lighten-3">
                        <span class="text-caption font-weight-bold">
                          {{ item.raw.name?.charAt(0) || "?" }}
                        </span>
                      </v-avatar>
                    </template>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <v-col cols="12">
              <v-divider class="mb-2" />
              <div class="text-subtitle-2 text-medium-emphasis mb-2">
                <v-icon size="small" class="mr-1">mdi-calculator</v-icon>
                Szacunkowe wartości
              </div>
            </v-col>

            <v-col cols="4">
              <v-text-field
                v-model.number="form.estimated_quantity"
                label="Ilość"
                type="number"
                min="0"
                step="1"
                variant="outlined"
                density="compact"
              />
            </v-col>
            <v-col cols="4">
              <v-text-field
                v-model.number="form.estimated_time_hours"
                label="Czas (h)"
                type="number"
                min="0"
                step="0.25"
                variant="outlined"
                density="compact"
              />
            </v-col>
            <v-col cols="4">
              <v-text-field
                v-model.number="form.estimated_cost"
                label="Koszt (PLN)"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                density="compact"
              />
            </v-col>

            <!-- Status (only for edit) -->
            <v-col v-if="isEdit" cols="12">
              <v-select
                v-model="form.status"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                label="Status"
                variant="outlined"
                density="compact"
              >
                <template v-slot:item="{ item, props: itemProps }">
                  <v-list-item v-bind="itemProps">
                    <template v-slot:prepend>
                      <v-icon :color="item.raw.color" size="small">{{
                        item.raw.icon
                      }}</v-icon>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.worker_notes"
                label="Notatki"
                variant="outlined"
                density="compact"
                rows="2"
                auto-grow
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn @click="close">Anuluj</v-btn>
        <v-btn
          color="deep-purple"
          variant="elevated"
          :loading="saving"
          :disabled="!formValid"
          @click="save"
        >
          {{ isEdit ? "Zapisz" : "Dodaj" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { workstationService } from "@/services/workstationService";

const props = defineProps({
  modelValue: Boolean,
  service: {
    type: Object as () => any,
    default: null,
  },
  nextStepNumber: {
    type: Number,
    default: 1,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const formRef = ref();
const formValid = ref(false);
const saving = ref(false);
const workstations = ref<any[]>([]);
const workers = ref<any[]>([]);

const isEdit = computed(() => !!props.service?.id);

const statusOptions = [
  { value: "PLANNED", label: "Zaplanowane", color: "grey", icon: "mdi-clock-outline" },
  { value: "IN_PROGRESS", label: "W trakcie", color: "orange", icon: "mdi-cog" },
  { value: "PAUSED", label: "Wstrzymane", color: "yellow", icon: "mdi-pause" },
  { value: "COMPLETED", label: "Zakończone", color: "green", icon: "mdi-check-circle" },
  { value: "CANCELLED", label: "Anulowane", color: "red", icon: "mdi-cancel" },
];

const defaultForm = () => ({
  service_name: "",
  step_number: props.nextStepNumber,
  workstation_id: null as number | null,
  assigned_to_user_id: null as number | null,
  estimated_quantity: 1,
  estimated_time_hours: 1,
  estimated_cost: 0,
  status: "PLANNED",
  worker_notes: "",
});

const form = ref(defaultForm());

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      await loadData();
      if (props.service) {
        form.value = {
          service_name: props.service.service_name,
          step_number: props.service.step_number,
          workstation_id: props.service.workstation_id,
          assigned_to_user_id: props.service.assigned_to_user_id,
          estimated_quantity: Number(props.service.estimated_quantity || 1),
          estimated_time_hours: Number(props.service.estimated_time_hours || 1),
          estimated_cost: Number(props.service.estimated_cost || 0),
          status: props.service.status || "PLANNED",
          worker_notes: props.service.worker_notes || "",
        };
      } else {
        form.value = defaultForm();
      }
    }
  }
);

const loadData = async () => {
  try {
    const [ws, wk] = await Promise.all([
      workstationService.getAll(),
      workstationService.getWorkers(),
    ]);
    workstations.value = ws;
    workers.value = wk;
  } catch (err) {
    console.error("Błąd ładowania danych:", err);
  }
};

const save = () => {
  saving.value = true;
  emit("saved", { ...form.value });
};

watch(
  () => props.modelValue,
  (open) => {
    if (!open) saving.value = false;
  }
);

const close = () => {
  emit("update:modelValue", false);
};
</script>
