<template>
  <v-app>
    <!-- Header bez przycisku wstecz -->
    <v-main class="bg-grey-lighten-4" style="overflow: hidden">
      <v-container class="mt-60 align-center justify-center pa-0">
        <!-- Loading State -->
        <div v-if="loading" class="text-center w-100">
          <v-progress-circular indeterminate size="64" color="primary" />
          <p class="mt-4 text-h6 text-medium-emphasis">Synchronizacja czasu...</p>
        </div>

        <!-- Error State -->
        <v-alert
          v-else-if="error"
          type="error"
          variant="tonal"
          prominent
          class="mx-4"
          max-width="600"
        >
          <v-alert-title>Błąd</v-alert-title>
          {{ error }}
          <template v-slot:append>
            <v-btn variant="outlined" @click="router.push('/rcp/workstation')"
              >Powrót</v-btn
            >
          </template>
        </v-alert>

        <!-- CANCELLED STATE -->
        <div
          v-else-if="task && task.status === 'CANCELLED'"
          class="d-flex flex-column align-center justify-center text-center w-100"
        >
          <v-icon size="120" color="grey-lighten-1" class="mb-6">mdi-cancel</v-icon>
          <h2 class="text-h4 font-weight-bold text-grey-darken-2 mb-2">
            Zadanie Anulowane
          </h2>
          <p class="text-subtitle-1 text-medium-emphasis mb-8">
            To zadanie zostało anulowane przez administratora.
          </p>
          <v-btn
            color="primary"
            size="x-large"
            height="64"
            width="240"
            @click="router.push('/rcp/workstation')"
          >
            POWRÓT DO LISTY
          </v-btn>
        </div>

        <!-- COMPLETED STATE (Zabezpieczenie) -->
        <div
          v-else-if="task && task.status === 'COMPLETED'"
          class="d-flex flex-column align-center justify-center text-center w-100"
        >
          <v-icon size="120" color="success" class="mb-6">mdi-check-circle</v-icon>
          <h2 class="text-h4 font-weight-bold text-success mb-2">Zadanie Zakończone</h2>
          <v-btn
            color="primary"
            size="x-large"
            height="64"
            width="240"
            class="mt-8"
            @click="router.push('/rcp/workstation')"
          >
            POWRÓT
          </v-btn>
        </div>

        <!-- ACTIVE TIMER VIEW -->
        <div
          v-else-if="task"
          class="d-flex flex-column align-center justify-center text-center w-100"
        >
          <!-- Pulsing Icon (Mniejszy dla 1366x768) -->
          <div class="pulse-ring mb-6">
            <v-avatar color="success" size="120" elevation="6">
              <v-icon size="60" color="white">mdi-timer-sand</v-icon>
            </v-avatar>
          </div>

          <!-- Task Name -->
          <div
            class="text-h4 font-weight-bold mb-1 text-high-emphasis text-truncate px-4"
            style="max-width: 90%"
          >
            {{ task.service_name }}
          </div>

          <!-- Workstation Name -->
          <div class="text-h6 text-medium-emphasis mb-4">
            {{ task.workstation?.name }}
          </div>

          <!-- Order Info -->
          <div
            v-if="task.production_order?.variant"
            class="text-subtitle-1 text-grey-darken-1 mb-6 px-4"
          >
            <span class="text-primary font-weight-bold">
              {{ task.production_order.variant.project?.full_project_number || "---" }}
            </span>
            • {{ task.production_order.variant.name }}
          </div>

          <!-- Timer Display (Ogromny) -->
          <div class="timer-display mb-8">
            {{ formattedTime }}
          </div>

          <!-- STOP Button (Tylko jeden przycisk, bardzo duży) -->
          <v-btn
            color="red"
            size="x-large"
            height="72"
            width="240"
            class="text-h5 font-weight-bold rounded-pill elevation-4"
            @click="openCompletionDialog"
          >
            <v-icon start size="36">mdi-stop</v-icon>
            STOP PRACY
          </v-btn>
        </div>
      </v-container>
    </v-main>

    <!-- Completion Dialog -->
    <v-dialog v-model="completionDialog" max-width="500" persistent>
      <v-card class="rounded-lg">
        <v-card-title
          class="bg-primary text-white py-4 px-6 d-flex justify-center align-center"
        >
          <v-icon icon="mdi-check-circle-outline" size="28" class="mr-2"></v-icon>
          <span class="text-h6 font-weight-bold">Potwierdzenie Zakończenia</span>
        </v-card-title>

        <v-card-text class="pt-6 px-6 text-center">
          <div class="text-body-1 font-weight-regular text-grey-darken-3 mb-4">
            Czy na pewno chcesz zakończyć pracę?
          </div>

          <v-sheet border rounded="lg" class="pa-3 bg-grey-lighten-5 mb-2">
            <div
              class="text-caption text-uppercase text-medium-emphasis font-weight-bold mb-1"
            >
              Czas pracy
            </div>
            <div class="text-h4 font-weight-black text-primary font-monospace">
              {{ formattedTime }}
            </div>
          </v-sheet>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-row dense>
            <v-col cols="6">
              <v-btn
                variant="outlined"
                size="large"
                block
                color="grey-darken-1"
                @click="completionDialog = false"
              >
                Anuluj
              </v-btn>
            </v-col>
            <v-col cols="6">
              <v-btn
                color="primary"
                variant="elevated"
                size="large"
                block
                @click="confirmStop"
                :loading="stopping"
              >
                <v-icon start>mdi-check</v-icon>
                Zakończ
              </v-btn>
            </v-col>
          </v-row>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { timerService } from "@/services/timerService";
import RcpHeader from "@/components/layout/RcpHeader.vue";
const router = useRouter();
const route = useRoute();
// State
const loading = ref(true);
const stopping = ref(false);
const error = ref<string | null>(null);
const task = ref<any>(null);
const completionDialog = ref(false);
const elapsedSeconds = ref(0);
let timerInterval: ReturnType<typeof setInterval> | null = null;
// Computed for Display
const formattedTime = computed(() => {
  const totalSeconds = Math.floor(elapsedSeconds.value);
  const h = Math.floor(totalSeconds / 3600)
    .toString()
    .padStart(2, "0");
  const m = Math.floor((totalSeconds % 3600) / 60)
    .toString()
    .padStart(2, "0");
  const s = (totalSeconds % 60).toString().padStart(2, "0");
  return `${h}:${m}:${s}`;
});
onMounted(async () => {
  const taskId = route.params.taskId;
  if (!taskId) {
    error.value = "Brak ID zadania";
    loading.value = false;
    return;
  }
  try {
    const data = await timerService.getTaskDetails(taskId);
    task.value = data;
    // Sprawdź status zadania
    if (data.status === "CANCELLED" || data.status === "COMPLETED") {
      loading.value = false;
      return; // Nie uruchamiaj timera dla anulowanych/zakończonych
    }

    elapsedSeconds.value = data.current_duration_seconds || 0;
    startLocalTicker();
  } catch (err: any) {
    console.error("Error loading task:", err);
    error.value = err.response?.data?.message || "Nie udało się załadować zadania";
  } finally {
    loading.value = false;
  }
});
onUnmounted(() => {
  stopLocalTicker();
});
const startLocalTicker = () => {
  stopLocalTicker(); // Ensure clean state
  timerInterval = setInterval(() => {
    elapsedSeconds.value++;
  }, 1000);
};
const stopLocalTicker = () => {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
};
const openCompletionDialog = () => {
  completionDialog.value = true;
};
const confirmStop = async () => {
  stopping.value = true;
  try {
    stopLocalTicker();
    await timerService.stop(task.value.id);
    completionDialog.value = false;
    router.push("/rcp/workstation");
  } catch (e: any) {
    console.error(e);
    alert("Błąd podczas zatrzymywania: " + (e.response?.data?.message || e.message));
    // Wznów timer jeśli błąd
    startLocalTicker();
  } finally {
    stopping.value = false;
  }
};
</script>
<style scoped>
.mt-60 {
  margin-top: 60px;
}
.timer-display {
  font-family: "Roboto Mono", monospace;
  font-size: 5rem; /* Zmniejszono z 6rem dla bezpiecznego fitu */
  font-weight: 900;
  color: #333;
  letter-spacing: -2px;
  line-height: 1;
}

.pulse-ring {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}
.pulse-ring::before {
  content: "";
  position: absolute;
  width: 120px; /* Dopasowane do avatara */
  height: 120px;
  border-radius: 50%;
  border: 4px solid rgba(76, 175, 80, 0.5);
  animation: ripple 2s infinite;
  z-index: 0;
}

@keyframes ripple {
  0% {
    width: 120px;
    height: 120px;
    opacity: 1;
  }
  100% {
    width: 180px;
    height: 180px;
    opacity: 0;
  }
}

.font-monospace {
  font-family: "Roboto Mono", monospace !important;
}
</style>
