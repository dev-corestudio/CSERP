<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="800"
    scrollable
  >
    <v-card>
      <v-card-title class="bg-orange text-white d-flex align-center">
        <v-icon start>mdi-history</v-icon>
        Logi Czasu Pracy (Korekta)
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>

      <div class="pa-4 bg-grey-lighten-4 border-b">
        <div class="text-subtitle-1 font-weight-bold">
          {{ task?.service_name }}
        </div>
        <div class="text-caption">
          Pracownik: <strong>{{ task?.assigned_worker?.name || "Brak" }}</strong>
        </div>
        <div class="mt-2">
          <v-btn
            size="small"
            prepend-icon="mdi-plus"
            color="primary"
            variant="flat"
            @click="openAddLog"
          >
            Dodaj wpis ręcznie
          </v-btn>
        </div>
      </div>

      <v-card-text class="pa-0">
        <v-data-table
          :headers="headers"
          :items="logs"
          :loading="loading"
          density="compact"
          class="logs-table"
          hide-default-footer
          items-per-page="-1"
        >
          <!-- Typ zdarzenia -->
          <template v-slot:item.event_type="{ item }">
            <v-chip
              size="small"
              :color="getEventColor(item)"
              label
              class="font-weight-bold"
            >
              {{ getEventLabel(item) }}
            </v-chip>
          </template>

          <!-- Czas zdarzenia -->
          <template v-slot:item.event_timestamp="{ item }">
            {{ formatDateTime(item.event_timestamp) }}
          </template>

          <!-- Czas trwania (tylko dla STOP/PAUSE) -->
          <template v-slot:item.elapsed_seconds="{ item }">
            <span
              v-if="
                item.event_type === 'STOP' &&
                item.elapsed_seconds === 0 &&
                task?.status === 'CANCELLED'
              "
            >
              -
            </span>
            <span
              v-else-if="
                ['STOP', 'PAUSE'].includes(item.event_type) || item.elapsed_seconds > 0
              "
            >
              {{ formatDuration(item.elapsed_seconds) }}
            </span>
            <span v-else class="text-grey">-</span>
          </template>

          <!-- Akcje -->
          <template v-slot:item.actions="{ item }">
            <v-btn
              icon="mdi-pencil"
              size="x-small"
              variant="text"
              color="blue"
              @click="editLog(item)"
            />
            <v-btn
              icon="mdi-delete"
              size="x-small"
              variant="text"
              color="red"
              @click="deleteLog(item)"
            />
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Dialog edycji pojedynczego logu -->
    <v-dialog v-model="logFormDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h6">
          {{ isEditMode ? "Edytuj wpis" : "Dodaj wpis" }}
        </v-card-title>
        <v-card-text class="pt-4">
          <v-form ref="formRef" @submit.prevent="saveLog">
            <v-select
              v-model="form.event_type"
              label="Typ zdarzenia *"
              :items="eventTypes"
              variant="outlined"
              prepend-inner-icon="mdi-flag"
            />

            <v-text-field
              v-model="form.event_timestamp"
              label="Data i czas *"
              type="datetime-local"
              variant="outlined"
              prepend-inner-icon="mdi-calendar-clock"
            />

            <v-text-field
              v-if="form.event_type !== 'CANCELLED_VIRTUAL'"
              v-model.number="form.elapsed_seconds"
              label="Czas trwania (sekundy)"
              type="number"
              variant="outlined"
              prepend-inner-icon="mdi-timer-sand"
              hint="Tylko dla Zakończenia lub Pauzy"
              persistent-hint
            />
            <v-alert v-else type="info" density="compact" variant="tonal" class="mt-2">
              Czas trwania dla anulowania zostanie ustawiony na 0.
            </v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="logFormDialog = false">Anuluj</v-btn>
          <v-btn color="primary" variant="elevated" @click="saveLog" :loading="saving">
            Zapisz
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { rcpAdminService } from "@/services/rcpAdminService";

const props = defineProps({
  modelValue: Boolean,
  task: Object,
});

const emit = defineEmits(["update:modelValue", "updated"]);

const logs = ref([]);
const loading = ref(false);
const saving = ref(false);
const logFormDialog = ref(false);
const editingLogId = ref(null);

const isEditMode = computed(() => !!editingLogId.value);

const form = ref({
  event_type: "START",
  event_timestamp: "",
  elapsed_seconds: 0,
  user_id: null,
});

const headers = [
  { title: "Zdarzenie", key: "event_type" },
  { title: "Data/Czas", key: "event_timestamp" },
  { title: "Naliczony czas", key: "elapsed_seconds", align: "end" },
  { title: "Akcje", key: "actions", align: "end", sortable: false },
];

// Dodano wirtualny typ "Anulowanie"
const eventTypes = [
  { title: "Start", value: "START" },
  { title: "Pauza", value: "PAUSE" },
  { title: "Wznowienie", value: "RESUME" },
  { title: "Stop", value: "STOP" },
  { title: "Anulowanie", value: "CANCELLED_VIRTUAL" },
];

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen && props.task) {
      loadLogs();
    }
  }
);

const loadLogs = async () => {
  loading.value = true;
  try {
    logs.value = await rcpAdminService.getTimeLogs(props.task.id);
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const openAddLog = () => {
  editingLogId.value = null;
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

  form.value = {
    event_type: "START",
    event_timestamp: now.toISOString().slice(0, 16),
    elapsed_seconds: 0,
    user_id: props.task.assigned_to_user_id,
  };
  logFormDialog.value = true;
};

const editLog = (log) => {
  editingLogId.value = log.id;
  const date = new Date(log.event_timestamp);
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

  // Wykrywanie czy to "Anulowanie" (STOP + 0s + Status Zadania CANCELLED)
  let type = log.event_type;
  if (
    props.task?.status === "CANCELLED" &&
    log.event_type === "STOP" &&
    log.elapsed_seconds === 0
  ) {
    type = "CANCELLED_VIRTUAL";
  }

  form.value = {
    event_type: type,
    event_timestamp: date.toISOString().slice(0, 16),
    elapsed_seconds: log.elapsed_seconds || 0,
    user_id: log.user_id,
  };
  logFormDialog.value = true;
};

const saveLog = async () => {
  saving.value = true;
  try {
    const payload = { ...form.value };

    // Obsługa wirtualnego typu "Anulowanie"
    if (payload.event_type === "CANCELLED_VIRTUAL") {
      payload.event_type = "STOP";
      payload.elapsed_seconds = 0;
    }

    if (isEditMode.value) {
      await rcpAdminService.updateTimeLog(editingLogId.value, payload);
    } else {
      await rcpAdminService.addTimeLog(props.task.id, payload);
    }

    await loadLogs();
    emit("updated");
    logFormDialog.value = false;
  } catch (e) {
    alert("Błąd zapisu logu");
  } finally {
    saving.value = false;
  }
};

const deleteLog = async (log) => {
  if (!confirm("Czy na pewno usunąć ten wpis?")) return;
  try {
    await rcpAdminService.deleteTimeLog(log.id);
    await loadLogs();
    emit("updated");
  } catch (e) {
    alert("Błąd usuwania");
  }
};

const close = () => {
  emit("update:modelValue", false);
};

// Helpers
const getEventLabel = (log) => {
  if (
    props.task?.status === "CANCELLED" &&
    log.event_type === "STOP" &&
    log.elapsed_seconds === 0
  ) {
    return "Anulowanie";
  }
  const map = { START: "Start", PAUSE: "Pauza", RESUME: "Wznowienie", STOP: "Stop" };
  return map[log.event_type] || log.event_type;
};

const getEventColor = (log) => {
  if (
    props.task?.status === "CANCELLED" &&
    log.event_type === "STOP" &&
    log.elapsed_seconds === 0
  ) {
    return "grey";
  }
  const map = { START: "green", PAUSE: "orange", RESUME: "blue", STOP: "red" };
  return map[log.event_type] || "grey";
};

const formatDateTime = (dateStr) => {
  return new Date(dateStr).toLocaleString("pl-PL");
};

const formatDuration = (seconds) => {
  if (!seconds && seconds !== 0) return "0s";
  const isNegative = seconds < 0;
  const absSeconds = Math.abs(seconds);

  const h = Math.floor(absSeconds / 3600);
  const m = Math.floor((absSeconds % 3600) / 60);
  const s = absSeconds % 60;

  return `${isNegative ? "-" : ""}${h}h ${m}m ${s}s`;
};
</script>

<style scoped>
.logs-table :deep(th) {
  font-weight: bold !important;
}
</style>
