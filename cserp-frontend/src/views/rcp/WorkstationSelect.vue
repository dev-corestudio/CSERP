<!-- src/views/rcp/WorkstationSelect.vue -->
<!-- Panel RCP - Proces wyboru: Wariant → Stanowisko → Czynność -->
<template>
  <v-container
    fluid
    class="fill-height pa-0 d-flex flex-column bg-grey-lighten-4 position-relative"
  >
    <!-- ============================================================== -->
    <!-- PRZYCISK WSTECZ - stała pozycja lewy górny róg                 -->
    <!-- ============================================================== -->
    <v-btn
      v-if="step > 1"
      color="white"
      variant="elevated"
      size="large"
      class="back-button"
      @click="goBack"
    >
      <v-icon start size="24">mdi-arrow-left</v-icon>
      Wstecz
    </v-btn>

    <!-- Loading Overlay -->
    <v-overlay
      :model-value="checkingActiveTask"
      class="align-center justify-center"
      persistent
    >
      <v-progress-circular indeterminate size="64" color="primary" />
      <div class="text-white mt-4 font-weight-bold">Wczytywanie...</div>
    </v-overlay>

    <!-- ============================================================== -->
    <!-- KROK 1: Lista Wariantów do Produkcji                           -->
    <!-- ============================================================== -->
    <div
      v-if="step === 1"
      class="d-flex flex-column flex-grow-1 w-100 pa-4"
      style="height: 100%"
    >
      <!-- Górna belka -->
      <div class="d-flex align-center justify-space-between mb-2 flex-shrink-0">
        <h2 class="text-h5 font-weight-bold text-grey-darken-3">
          <v-icon start color="primary">mdi-clipboard-list</v-icon>
          Dostępne Zlecenia
        </h2>
        <div style="width: 400px">
          <v-text-field
            v-model="variantSearch"
            prepend-inner-icon="mdi-magnify"
            label="Szukaj (Numer, Nazwa, Klient)..."
            variant="outlined"
            density="compact"
            hide-details
            bg-color="white"
            single-line
          ></v-text-field>
        </div>
      </div>

      <!-- Tabela -->
      <v-card
        elevation="1"
        class="flex-grow-1 d-flex flex-column rounded-lg border"
        style="overflow: hidden"
      >
        <v-data-table
          :headers="variantHeaders"
          :items="availableVariants"
          :search="variantSearch"
          :loading="variantsLoading"
          :custom-filter="customSearch"
          density="compact"
          fixed-header
          hover
          items-per-page="-1"
          class="rcp-table flex-grow-1"
          style="height: 100%; overflow-y: auto"
          @click:row="handleVariantClick"
          v-model:sort-by="sortBy"
        >
          <template v-slot:item.priority="{ item }">
            <v-chip
              :color="getPriorityColor(item.priority)"
              label
              size="small"
              class="font-weight-bold text-uppercase"
            >
              {{ getPriorityLabel(item.priority) }}
            </v-chip>
          </template>

          <template v-slot:item.order_info="{ item }">
            <span class="font-weight-bold text-primary text-body-1">
              {{ item.full_project_number }}
            </span>
            <span class="text-caption text-grey ml-1"> [{{ item.variant_number }}] </span>
          </template>

          <template v-slot:item.name="{ item }">
            <span class="text-body-1 font-weight-medium">{{ item.name }}</span>
          </template>

          <template v-slot:item.quantity="{ item }">
            <v-chip size="small" variant="tonal" color="blue-grey">
              {{ item.quantity }} szt.
            </v-chip>
          </template>

          <template v-slot:item.actions>
            <v-icon color="grey-lighten-1">mdi-chevron-right</v-icon>
          </template>
        </v-data-table>
      </v-card>
    </div>

    <!-- ============================================================== -->
    <!-- KROK 2: Wybór Stanowiska                                       -->
    <!-- ============================================================== -->
    <div
      v-else-if="step === 2"
      class="d-flex flex-column flex-grow-1 w-100 pa-4"
      style="height: 100%"
    >
      <!-- Nagłówek -->
      <div class="text-center mb-4 flex-shrink-0">
        <h2 class="text-h4 font-weight-bold">Wybierz stanowisko</h2>
        <div class="text-subtitle-1 text-medium-emphasis mt-1">
          Zlecenie:
          <span class="text-primary font-weight-bold">{{
            selectedVariant?.full_project_number
          }}</span>
          <span class="text-grey ml-2">({{ selectedVariant?.name }})</span>
        </div>
      </div>

      <div
        v-if="workstations.length === 0 && !workstationStore.loading"
        class="d-flex flex-column justify-center align-center flex-grow-1 text-center"
        style="opacity: 0.7"
      >
        <v-icon
          icon="mdi-alert-circle-outline"
          size="80"
          color="warning"
          class="mb-4"
        ></v-icon>

        <h3 class="text-h5 font-weight-bold text-grey-darken-3">Brak stanowisk</h3>

        <p class="text-body-1 text-grey-darken-1 mt-2" style="max-width: 400px">
          Nie znaleziono stanowisk przypisanych do tego użytkownika. Skontatuj się z
          kierownikiem.
        </p>
      </div>

      <!-- Grid Kart (Scrollowalny) -->
      <div
        class="d-flex flex-wrap justify-center align-start gap-4 w-100 pa-2"
        style="overflow-y: auto; flex-grow: 1"
      >
        <v-hover v-for="ws in workstations" :key="ws.id" v-slot="{ isHovering, props }">
          <v-card
            v-bind="props"
            @click="ws.status === 'IDLE' ? selectWorkstation(ws) : null"
            :elevation="isHovering && ws.status === 'IDLE' ? 6 : 1"
            :variant="ws.status === 'IDLE' ? 'elevated' : 'outlined'"
            class="workstation-card d-flex flex-column pa-4 transition-swing position-relative"
            :class="[
              ws.status === 'IDLE'
                ? 'cursor-pointer border-primary-hover'
                : 'bg-grey-lighten-5 cursor-not-allowed opacity-80',
            ]"
            width="300"
            height="200"
            rounded="lg"
          >
            <!-- Górna belka karty: Status -->
            <div class="d-flex justify-space-between align-start mb-3">
              <v-chip
                size="small"
                :color="ws.status === 'IDLE' ? 'success' : 'warning'"
                label
                class="font-weight-bold"
              >
                <v-icon start size="x-small">
                  {{ ws.status === "IDLE" ? "mdi-check-circle" : "mdi-clock-outline" }}
                </v-icon>
                {{ ws.status === "IDLE" ? "WOLNE" : "ZAJĘTE" }}
              </v-chip>
            </div>

            <!-- Nazwa i Lokalizacja -->
            <div class="mb-2">
              <div class="text-h6 font-weight-bold text-high-emphasis text-truncate">
                {{ ws.name }}
              </div>
              <div
                class="text-body-2 text-medium-emphasis d-flex align-center mt-1 text-truncate"
              >
                <v-icon size="small" class="mr-1">mdi-map-marker-outline</v-icon>
                {{ ws.location || "Nie zdefiniowano" }}
              </div>
            </div>

            <v-divider class="mt-auto mb-3" v-if="ws.status !== 'IDLE'"></v-divider>

            <!-- Info o zajętości -->
            <div v-if="ws.status !== 'IDLE'" class="d-flex flex-column mt-auto">
              <div
                class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-1"
              >
                Zajęte przez:
              </div>
              <div class="d-flex align-center">
                <v-avatar size="28" color="grey-lighten-2" class="mr-2">
                  <span class="text-caption font-weight-bold">{{
                    getWorkerInitial(ws)
                  }}</span>
                </v-avatar>
                <span
                  class="text-body-2 font-weight-bold text-grey-darken-3 text-truncate"
                >
                  {{ getWorkerName(ws) }}
                </span>
              </div>
            </div>

            <!-- Akcja (Hover effect dla IDLE) -->
            <div
              v-else
              class="mt-auto pt-2 d-flex align-center text-primary font-weight-bold transition-opacity justify-end text-h6"
              :style="{ opacity: isHovering ? 1 : 0.6 }"
            >
              WYBIERZ
              <v-icon end size="large">mdi-arrow-right</v-icon>
            </div>
          </v-card>
        </v-hover>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- KROK 3: Wybór Usługi (Czynności)                               -->
    <!-- ============================================================== -->
    <div
      v-else-if="step === 3"
      class="d-flex flex-column flex-grow-1 w-100 align-center pa-4"
    >
      <!-- Nagłówek -->
      <div class="text-center mb-6 flex-shrink-0">
        <h2 class="text-h4 font-weight-bold text-grey-darken-3">Co będziesz robić?</h2>
        <div class="text-subtitle-1 text-medium-emphasis mt-1">
          Stanowisko:
          <span class="text-primary font-weight-bold">{{
            selectedWorkstation?.name
          }}</span>
          <span class="text-grey mx-2">|</span>
          Zlecenie:
          <span class="text-primary font-weight-bold">{{
            selectedVariant?.full_project_number
          }}</span>
        </div>
      </div>

      <div v-if="servicesLoading" class="text-center mt-10">
        <v-progress-circular indeterminate color="primary" size="64" />
      </div>

      <div
        v-else-if="allowedServices.length > 0"
        class="w-100 d-flex flex-column align-center"
        style="overflow-y: auto; flex-grow: 1"
      >
        <v-row class="w-100" justify="center" style="max-width: 1000px">
          <v-col v-for="service in allowedServices" :key="service.id" cols="12" sm="6">
            <v-hover v-slot="{ isHovering, props }">
              <v-card
                v-bind="props"
                @click="startTask(service)"
                class="pa-4 cursor-pointer service-card"
                :class="{ 'service-card-hover': isHovering }"
                elevation="0"
                border
              >
                <div class="d-flex align-center">
                  <v-avatar
                    color="primary"
                    size="56"
                    variant="tonal"
                    class="mr-4 rounded-lg"
                  >
                    <v-icon size="32">mdi-wrench</v-icon>
                  </v-avatar>

                  <div class="flex-grow-1">
                    <div class="text-h6 font-weight-bold text-grey-darken-3">
                      {{ service.name }}
                    </div>
                    <div class="text-subtitle-2 text-medium-emphasis text-uppercase mt-1">
                      {{ service.category }}
                    </div>
                  </div>

                  <v-btn
                    color="success"
                    size="large"
                    icon="mdi-play"
                    variant="elevated"
                    :loading="startingTask"
                  ></v-btn>
                </div>
              </v-card>
            </v-hover>
          </v-col>
        </v-row>
      </div>

      <div
        v-else
        class="d-flex flex-column justify-center align-center flex-grow-1 text-center"
        style="opacity: 0.7"
      >
        <v-icon
          icon="mdi-alert-circle-outline"
          size="80"
          color="warning"
          class="mb-4"
        ></v-icon>

        <h3 class="text-h5 font-weight-bold text-grey-darken-3">Brak usług</h3>

        <p class="text-body-1 text-grey-darken-1 mt-2" style="max-width: 400px">
          Nie znaleziono usług przypisanych do tego stanowiska dla wybranego zlecenia.
        </p>

        <v-btn variant="outlined" color="primary" class="mt-6" @click="goBack">
          Wybierz inne stanowisko
        </v-btn>
      </div>
    </div>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useWorkstationStore } from "@/stores/workstations";
import { timerService } from "@/services/timerService";
import api from "@/services/api";

const router = useRouter();
const workstationStore = useWorkstationStore();

// ============================================================
// STATE
// ============================================================
const checkingActiveTask = ref(true);
const step = ref(1);
const variantsLoading = ref(false);
const servicesLoading = ref(false);
const startingTask = ref(false);
const variantSearch = ref("");

const sortBy = ref([{ key: "priority", order: "desc" }]);

const availableVariants = ref<any[]>([]);
const workstations = computed(() => workstationStore.myItems);
const allowedServices = ref<any[]>([]);

const selectedVariant = ref<any>(null);
const selectedWorkstation = ref<any>(null);

const priorityMap: Record<string, number> = { urgent: 4, high: 3, normal: 2, low: 1 };

// ============================================================
// NAGŁÓWKI TABELI
// ============================================================
const variantHeaders = [
  {
    title: "Priorytet",
    key: "priority",
    align: "center" as const,
    width: "100px",
    sort: (a: string, b: string) =>
      priorityMap[a?.toLowerCase()] - priorityMap[b?.toLowerCase()],
  },
  { title: "Zlecenie", key: "order_info", align: "start" as const, width: "180px" },
  { title: "Produkt", key: "name", align: "start" as const },
  { title: "Klient", key: "customer_name", align: "start" as const, width: "180px" },
  { title: "Ilość", key: "quantity", align: "center" as const, width: "90px" },
  { title: "", key: "actions", align: "end" as const, sortable: false, width: "50px" },
];

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(async () => {
  await checkForActiveTask();
  if (!checkingActiveTask.value) {
    loadVariants();
    loadWorkstations();
  }
});

// ============================================================
// METODY
// ============================================================

/**
 * Cofnij do poprzedniego kroku
 */
const goBack = () => {
  if (step.value > 1) {
    step.value--;
  }
};

/**
 * Własny filtr wyszukiwania po numerze zlecenia, nazwie produktu, kliencie
 */
const customSearch = (value: any, query: string, item: any) => {
  if (!query) return true;
  const q = query.toLowerCase();
  const raw = item.raw;
  return (
    (raw.full_project_number && raw.full_project_number.toLowerCase().includes(q)) ||
    (raw.project_number && raw.project_number.toLowerCase().includes(q)) ||
    (raw.name && raw.name.toLowerCase().includes(q)) ||
    (raw.customer_name && raw.customer_name.toLowerCase().includes(q))
  );
};

/**
 * Sprawdź czy pracownik ma aktywne zadanie — jeśli tak, przekieruj do timera
 */
const checkForActiveTask = async () => {
  checkingActiveTask.value = true;
  try {
    const result = await timerService.checkActiveTask();
    if (result.has_active_task && result.task_id) {
      router.push({ name: "Timer", params: { taskId: result.task_id } });
    }
  } catch (error) {
    console.error("Błąd sprawdzania aktywnego zadania:", error);
  } finally {
    checkingActiveTask.value = false;
  }
};

/**
 * Pobierz listę dostępnych wariantów (status PRODUCTION, z aktywnym zleceniem)
 */
const loadVariants = async () => {
  variantsLoading.value = true;
  try {
    const res = await api.get("/rcp/variants");
    availableVariants.value = res.data.map((variant: any) => ({
      ...variant,
      full_project_number:
        variant.full_project_number ||
        `P/${variant.project_number}/${variant.series || "0001"}`,
    }));
  } catch (e) {
    console.error("Błąd pobierania wariantów:", e);
  } finally {
    variantsLoading.value = false;
  }
};

/**
 * Pobierz stanowiska przypisane do zalogowanego pracownika
 */
const loadWorkstations = async () => {
  await workstationStore.fetchMyWorkstations();
};

/**
 * Obsługa kliknięcia w wiersz tabeli (krok 1)
 */
const handleVariantClick = (event: Event, { item }: { item: any }) => {
  selectVariant(item);
};

/**
 * Wybierz wariant i przejdź do kroku 2
 */
const selectVariant = (variant: any) => {
  selectedVariant.value = variant;
  step.value = 2;
};

/**
 * Wybierz stanowisko i przejdź do kroku 3 (ładuje dostępne usługi dla stanowiska)
 */
const selectWorkstation = async (ws: any) => {
  selectedWorkstation.value = ws;
  step.value = 3;
  servicesLoading.value = true;

  try {
    const res = await api.get(`/workstations/${ws.id}/services`);
    allowedServices.value = res.data;
  } catch (e) {
    console.error("Błąd pobierania usług:", e);
  } finally {
    servicesLoading.value = false;
  }
};

/**
 * Uruchom zadanie i przejdź do ekranu timera.
 *
 * Używa timerService.startWork() który wysyła POST /rcp/start z:
 *   variant_id, workstation_id, service_id
 *
 * Backend (RcpService.startWork) tworzy zadanie przez firstOrCreate —
 * jeśli zadanie już istnieje (np. wznowienie), nie tworzy duplikatu.
 */
const startTask = async (service: any) => {
  startingTask.value = true;
  try {
    const result = await timerService.startWork(
      selectedVariant.value.id,
      selectedWorkstation.value.id,
      service.id
    );

    const taskId = result.current_task_id || result.task?.id;
    if (taskId) {
      router.push({ name: "Timer", params: { taskId } });
    } else {
      throw new Error("Nie otrzymano ID zadania");
    }
  } catch (e: any) {
    alert("Błąd startu: " + (e.response?.data?.message || e.message));
    startingTask.value = false;
  }
};

// ============================================================
// HELPERY UI
// ============================================================

const getPriorityColor = (priority: string) => {
  const map: Record<string, string> = {
    urgent: "red",
    high: "orange",
    normal: "blue",
    low: "grey",
  };
  return map[priority?.toLowerCase()] || "grey";
};

const getPriorityLabel = (priority: string) => {
  const map: Record<string, string> = {
    urgent: "PILNE",
    high: "WYSOKI",
    normal: "NORMALNY",
    low: "NISKI",
  };
  return map[priority?.toLowerCase()] || priority || "NORMALNY";
};

const getWorkerName = (ws: any) => {
  if (ws.current_task?.assigned_worker?.name) {
    return ws.current_task.assigned_worker.name;
  }
  if (ws.operators && ws.operators.length > 0) {
    const primary =
      ws.operators.find((op: any) => op.pivot?.is_primary) || ws.operators[0];
    return primary.name;
  }
  return "Nieznany";
};

const getWorkerInitial = (ws: any) => {
  const name = getWorkerName(ws);
  return name.charAt(0).toUpperCase();
};
</script>

<style scoped>
/* Przycisk Wstecz - stała pozycja, kolor biały */
.back-button {
  position: absolute;
  top: 16px;
  left: 16px;
  z-index: 100;
  font-weight: 600;
  text-transform: none;
  color: #333 !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.back-button:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Tabela zleceń */
.rcp-table :deep(td) {
  height: 48px !important;
  cursor: pointer;
}

.rcp-table :deep(tr:hover) {
  background-color: #f0f7ff !important;
}

/* Karty stanowisk */
.workstation-card {
  transition: all 0.2s ease;
}

/* Karty usług */
.service-card {
  border-radius: 12px;
  transition: all 0.15s ease;
}

.service-card-hover {
  border-color: rgb(var(--v-theme-primary)) !important;
  background-color: rgba(var(--v-theme-primary), 0.04) !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
}
</style>
