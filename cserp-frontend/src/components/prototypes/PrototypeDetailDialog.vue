<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="1100"
    scrollable
  >
    <v-card v-if="prototype">
      <!-- Header -->
      <v-card-title class="bg-purple text-white d-flex align-center pa-4">
        <v-avatar color="purple-lighten-2" size="48" class="mr-3">
          <span class="text-h5 font-weight-bold text-white"
            >v{{ prototype.version_number }}</span
          >
        </v-avatar>
        <div>
          <div class="text-h6">
            Prototyp v{{ prototype.version_number }}
            <v-chip
              v-if="prototype.is_approved"
              color="success"
              size="small"
              class="ml-2"
            >
              <v-icon start size="x-small">mdi-check</v-icon>
              Zatwierdzony
            </v-chip>
            <v-chip v-if="prototype.is_rejected" color="error" size="small" class="ml-2">
              <v-icon start size="x-small">mdi-close</v-icon>
              Odrzucony
            </v-chip>
          </div>
          <div class="text-caption opacity-80">
            {{ prototype.description || "Brak opisu" }}
          </div>
        </div>
        <v-spacer />

        <!-- Actions -->
        <v-btn
          v-if="!prototype.is_approved && !prototype.is_rejected"
          color="white"
          variant="text"
          size="small"
          @click="$emit('approve', prototype)"
        >
          <v-icon start>mdi-check</v-icon>
          Zatwierdź
        </v-btn>
        <v-btn
          v-if="!prototype.is_approved && !prototype.is_rejected"
          color="white"
          variant="text"
          size="small"
          @click="$emit('reject', prototype)"
        >
          <v-icon start>mdi-close</v-icon>
          Odrzuć
        </v-btn>
        <v-btn icon="mdi-close" color="white" variant="text" @click="close" />
      </v-card-title>

      <!-- Tabs -->
      <v-tabs v-model="activeTab" bg-color="purple-lighten-5" color="purple">
        <v-tab value="materials">
          <v-icon start>mdi-package-variant-closed</v-icon>
          Materiały
          <v-badge
            v-if="materials.length > 0"
            :content="materials.length"
            color="purple"
            class="ml-2"
            inline
          />
        </v-tab>
        <v-tab value="services">
          <v-icon start>mdi-cog</v-icon>
          RCP / Zadania
          <v-badge
            v-if="services.length > 0"
            :content="services.length"
            color="purple"
            class="ml-2"
            inline
          />
        </v-tab>
        <v-tab value="info">
          <v-icon start>mdi-information</v-icon>
          Info
        </v-tab>
      </v-tabs>

      <v-card-text
        class="pa-0"
        style="min-height: 400px; max-height: 70vh; overflow-y: auto"
      >
        <v-tabs-window v-model="activeTab">
          <!-- ============================================================ -->
          <!-- TAB: MATERIAŁY -->
          <!-- ============================================================ -->
          <v-tabs-window-item value="materials">
            <div class="pa-4">
              <materials-panel
                :materials="materials"
                :summary="materialsSummary"
                :total-cost="materialsTotalCost"
                :loading="loadingMaterials"
                :readonly="prototype.is_approved || prototype.is_rejected"
                @add="
                  materialFormDialog = true;
                  editingMaterial = null;
                "
                @edit="editMaterial"
                @delete="confirmDeleteMaterial"
                @status-change="handleMaterialStatusChange"
                @mark-all-ordered="markAllMaterialsOrdered"
              />
            </div>
          </v-tabs-window-item>

          <!-- ============================================================ -->
          <!-- TAB: USŁUGI / RCP -->
          <!-- ============================================================ -->
          <v-tabs-window-item value="services">
            <div class="pa-4">
              <v-card elevation="2" class="mb-4">
                <v-card-title class="bg-deep-purple text-white d-flex align-center">
                  <v-icon start color="white">mdi-cog</v-icon>
                  Zadania RCP ({{ services.length }})
                  <v-spacer />
                  <v-btn
                    v-if="!prototype.is_approved && !prototype.is_rejected"
                    color="white"
                    variant="text"
                    size="small"
                    @click="
                      serviceFormDialog = true;
                      editingService = null;
                    "
                  >
                    <v-icon start>mdi-plus</v-icon>
                    Dodaj zadanie
                  </v-btn>
                </v-card-title>

                <v-card-text class="pa-0">
                  <div v-if="loadingServices" class="text-center py-6">
                    <v-progress-circular indeterminate color="deep-purple" />
                  </div>

                  <div v-else-if="services.length === 0" class="text-center py-6">
                    <v-icon size="64" color="grey-lighten-2">mdi-cog</v-icon>
                    <p class="text-body-2 text-medium-emphasis mt-2">Brak zadań RCP</p>
                    <v-btn
                      v-if="!prototype.is_approved && !prototype.is_rejected"
                      variant="outlined"
                      color="deep-purple"
                      size="small"
                      class="mt-2"
                      @click="
                        serviceFormDialog = true;
                        editingService = null;
                      "
                    >
                      <v-icon start>mdi-plus</v-icon>
                      Dodaj pierwsze zadanie
                    </v-btn>
                  </div>

                  <v-table v-else density="compact" hover>
                    <thead>
                      <tr class="bg-grey-lighten-4">
                        <th style="width: 50px">#</th>
                        <th>Zadanie</th>
                        <th>Stanowisko</th>
                        <th>Pracownik</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Czas (h)</th>
                        <th class="text-right">Koszt</th>
                        <th
                          v-if="!prototype.is_approved && !prototype.is_rejected"
                          class="text-center"
                          style="width: 100px"
                        >
                          Akcje
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="service in sortedServices" :key="service.id">
                        <td class="text-center font-weight-medium text-grey">
                          {{ service.step_number }}
                        </td>
                        <td>
                          <div class="font-weight-medium">{{ service.service_name }}</div>
                          <div
                            v-if="service.worker_notes"
                            class="text-caption text-medium-emphasis"
                          >
                            {{ service.worker_notes }}
                          </div>
                        </td>
                        <td>
                          <span v-if="service.workstation">{{
                            service.workstation.name
                          }}</span>
                          <span v-else class="text-medium-emphasis">—</span>
                        </td>
                        <td>
                          <div v-if="service.assigned_worker" class="d-flex align-center">
                            <v-avatar size="24" color="grey-lighten-3" class="mr-2">
                              <span class="text-caption font-weight-bold">
                                {{ service.assigned_worker.name?.charAt(0) || "?" }}
                              </span>
                            </v-avatar>
                            {{ service.assigned_worker.name }}
                          </div>
                          <span v-else class="text-medium-emphasis">—</span>
                        </td>
                        <td class="text-center">
                          <v-chip
                            :color="getServiceStatusColor(service.status)"
                            size="small"
                          >
                            {{ getServiceStatusLabel(service.status) }}
                          </v-chip>
                        </td>
                        <td class="text-right">
                          <div>
                            <span class="text-medium-emphasis">Plan: </span>
                            {{ service.estimated_time_hours || "—" }}
                          </div>
                          <div v-if="service.actual_time_hours">
                            <span class="text-medium-emphasis">Fakt: </span>
                            <span class="font-weight-medium">{{
                              service.actual_time_hours
                            }}</span>
                          </div>
                        </td>
                        <td class="text-right">
                          <div>
                            {{ formatCurrency(service.estimated_cost) }}
                          </div>
                          <div v-if="service.actual_cost" class="font-weight-medium">
                            {{ formatCurrency(service.actual_cost) }}
                          </div>
                        </td>
                        <td
                          v-if="!prototype.is_approved && !prototype.is_rejected"
                          class="text-center"
                        >
                          <v-btn
                            icon="mdi-pencil"
                            variant="text"
                            size="x-small"
                            color="primary"
                            @click="editService(service)"
                          />
                          <v-btn
                            icon="mdi-delete"
                            variant="text"
                            size="x-small"
                            color="error"
                            @click="confirmDeleteService(service)"
                          />
                        </td>
                      </tr>
                    </tbody>

                    <tfoot v-if="services.length > 0">
                      <tr class="bg-grey-lighten-4">
                        <td colspan="5" class="text-right font-weight-bold">Suma:</td>
                        <td class="text-right font-weight-medium">
                          {{ totalEstimatedHours.toFixed(1) }} h
                        </td>
                        <td class="text-right font-weight-bold text-deep-purple">
                          {{ formatCurrency(totalEstimatedCost) }}
                        </td>
                        <td v-if="!prototype.is_approved && !prototype.is_rejected"></td>
                      </tr>
                    </tfoot>
                  </v-table>
                </v-card-text>
              </v-card>
            </div>
          </v-tabs-window-item>

          <!-- ============================================================ -->
          <!-- TAB: INFO -->
          <!-- ============================================================ -->
          <v-tabs-window-item value="info">
            <div class="pa-6">
              <v-row>
                <v-col cols="6">
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis">Wersja</div>
                    <div class="text-h6">v{{ prototype.version_number }}</div>
                  </div>
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis">Opis</div>
                    <div>{{ prototype.description || "—" }}</div>
                  </div>
                  <div class="mb-4">
                    <div class="text-caption text-medium-emphasis">Utworzony</div>
                    <div>{{ formatDateTime(prototype.created_at) }}</div>
                  </div>
                </v-col>
                <v-col cols="6">
                  <v-card variant="outlined" class="pa-4">
                    <div class="text-subtitle-2 font-weight-bold mb-3">
                      <v-icon start size="small">mdi-chart-box</v-icon>
                      Podsumowanie kosztów
                    </div>
                    <div class="d-flex justify-space-between mb-2">
                      <span class="text-medium-emphasis">Materiały:</span>
                      <span class="font-weight-medium">{{
                        formatCurrency(materialsTotalCost)
                      }}</span>
                    </div>
                    <div class="d-flex justify-space-between mb-2">
                      <span class="text-medium-emphasis">Usługi (plan):</span>
                      <span class="font-weight-medium">{{
                        formatCurrency(totalEstimatedCost)
                      }}</span>
                    </div>
                    <v-divider class="my-2" />
                    <div class="d-flex justify-space-between">
                      <span class="font-weight-bold">Razem:</span>
                      <span class="font-weight-bold text-purple">
                        {{ formatCurrency(materialsTotalCost + totalEstimatedCost) }}
                      </span>
                    </div>
                  </v-card>
                </v-col>
              </v-row>
            </div>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card-text>
    </v-card>

    <!-- Material Form Dialog -->
    <material-form-dialog
      v-model="materialFormDialog"
      :material="editingMaterial"
      @saved="handleMaterialSaved"
    />

    <!-- Prototype Service Form Dialog -->
    <prototype-service-form-dialog
      v-model="serviceFormDialog"
      :service="editingService"
      :next-step-number="nextStepNumber"
      @saved="handleServiceSaved"
    />

    <!-- Delete Material Confirm -->
    <v-dialog v-model="deleteMaterialDialog" max-width="400">
      <v-card>
        <v-card-title class="bg-error text-white">
          <v-icon start color="white">mdi-alert</v-icon>
          Usuń materiał
        </v-card-title>
        <v-card-text class="pt-4">
          Czy na pewno chcesz usunąć
          <strong>{{ deletingMaterial?.assortment?.name || "ten materiał" }}</strong
          >?
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="deleteMaterialDialog = false">Anuluj</v-btn>
          <v-btn color="error" variant="elevated" @click="handleDeleteMaterial"
            >Usuń</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Service Confirm -->
    <v-dialog v-model="deleteServiceDialog" max-width="400">
      <v-card>
        <v-card-title class="bg-error text-white">
          <v-icon start color="white">mdi-alert</v-icon>
          Usuń zadanie
        </v-card-title>
        <v-card-text class="pt-4">
          Czy na pewno chcesz usunąć zadanie
          <strong>{{ deletingService?.service_name }}</strong
          >?
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="deleteServiceDialog = false">Anuluj</v-btn>
          <v-btn color="error" variant="elevated" @click="handleDeleteService"
            >Usuń</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { prototypeMaterialService } from "@/services/prototypeMaterialService";
import { prototypeServiceService } from "@/services/prototypeServiceService";
import MaterialsPanel from "@/components/materials/MaterialsPanel.vue";
import MaterialFormDialog from "@/components/materials/MaterialFormDialog.vue";
import PrototypeServiceFormDialog from "@/components/prototypes/PrototypeServiceFormDialog.vue";

const props = defineProps({
  modelValue: Boolean,
  prototype: {
    type: Object as () => any,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "approve", "reject", "updated"]);

// State
const activeTab = ref("materials");
const loadingMaterials = ref(false);
const loadingServices = ref(false);
const materials = ref<any[]>([]);
const services = ref<any[]>([]);
const materialsSummary = ref<any>(null);
const materialsTotalCost = ref(0);

// Material dialogs
const materialFormDialog = ref(false);
const editingMaterial = ref<any>(null);
const deleteMaterialDialog = ref(false);
const deletingMaterial = ref<any>(null);

// Service dialogs
const serviceFormDialog = ref(false);
const editingService = ref<any>(null);
const deleteServiceDialog = ref(false);
const deletingService = ref<any>(null);

// Computed
const sortedServices = computed(() =>
  [...services.value].sort((a, b) => (a.step_number || 0) - (b.step_number || 0))
);

const nextStepNumber = computed(() => {
  if (services.value.length === 0) return 1;
  return Math.max(...services.value.map((s: any) => s.step_number || 0)) + 1;
});

const totalEstimatedHours = computed(() =>
  services.value.reduce(
    (sum: number, s: any) => sum + (Number(s.estimated_time_hours) || 0),
    0
  )
);

const totalEstimatedCost = computed(() =>
  services.value.reduce((sum: number, s: any) => sum + (Number(s.estimated_cost) || 0), 0)
);

// Watch for open
watch(
  () => props.modelValue,
  async (open) => {
    if (open && props.prototype) {
      activeTab.value = "materials";
      await Promise.all([loadMaterials(), loadServices()]);
    }
  }
);

// =========================================================================
// MATERIALS
// =========================================================================
const loadMaterials = async () => {
  if (!props.prototype?.id) return;
  loadingMaterials.value = true;
  try {
    const data = await prototypeMaterialService.getAll(props.prototype.id);
    materials.value = data.materials || data || [];
    materialsSummary.value = data.summary || null;
    materialsTotalCost.value = Number(data.total_cost) || 0;
  } catch (err) {
    console.error("Błąd ładowania materiałów prototypu:", err);
    materials.value = [];
  } finally {
    loadingMaterials.value = false;
  }
};

const editMaterial = (material: any) => {
  editingMaterial.value = material;
  materialFormDialog.value = true;
};

const confirmDeleteMaterial = (material: any) => {
  deletingMaterial.value = material;
  deleteMaterialDialog.value = true;
};

const handleMaterialSaved = async (payload: any) => {
  try {
    if (editingMaterial.value?.id) {
      await prototypeMaterialService.update(editingMaterial.value.id, payload);
    } else {
      await prototypeMaterialService.create(props.prototype.id, payload);
    }
    materialFormDialog.value = false;
    await loadMaterials();
    emit("updated");
  } catch (err) {
    console.error("Błąd zapisu materiału:", err);
    alert("Błąd zapisu materiału");
  }
};

const handleDeleteMaterial = async () => {
  try {
    await prototypeMaterialService.delete(deletingMaterial.value.id);
    deleteMaterialDialog.value = false;
    await loadMaterials();
    emit("updated");
  } catch (err) {
    console.error("Błąd usuwania materiału:", err);
  }
};

const handleMaterialStatusChange = async (material: any, newStatus: string) => {
  try {
    await prototypeMaterialService.updateStatus(material.id, newStatus);
    await loadMaterials();
    emit("updated");
  } catch (err) {
    console.error("Błąd zmiany statusu:", err);
  }
};

const markAllMaterialsOrdered = async () => {
  // Mark each NOT_ORDERED as ORDERED
  try {
    const promises = materials.value
      .filter((m: any) => m.status === "NOT_ORDERED")
      .map((m: any) => prototypeMaterialService.updateStatus(m.id, "ORDERED"));
    await Promise.all(promises);
    await loadMaterials();
    emit("updated");
  } catch (err) {
    console.error("Błąd masowego zamawiania:", err);
  }
};

// =========================================================================
// SERVICES (RCP)
// =========================================================================
const loadServices = async () => {
  if (!props.prototype?.id) return;
  loadingServices.value = true;
  try {
    services.value = await prototypeServiceService.getAll(props.prototype.id);
  } catch (err) {
    console.error("Błąd ładowania usług prototypu:", err);
    services.value = [];
  } finally {
    loadingServices.value = false;
  }
};

const editService = (service: any) => {
  editingService.value = service;
  serviceFormDialog.value = true;
};

const confirmDeleteService = (service: any) => {
  deletingService.value = service;
  deleteServiceDialog.value = true;
};

const handleServiceSaved = async (payload: any) => {
  try {
    if (editingService.value?.id) {
      await prototypeServiceService.update(editingService.value.id, payload);
    } else {
      await prototypeServiceService.create(props.prototype.id, payload);
    }
    serviceFormDialog.value = false;
    await loadServices();
    emit("updated");
  } catch (err) {
    console.error("Błąd zapisu zadania RCP:", err);
    alert("Błąd zapisu zadania");
  }
};

const handleDeleteService = async () => {
  try {
    await prototypeServiceService.delete(deletingService.value.id);
    deleteServiceDialog.value = false;
    await loadServices();
    emit("updated");
  } catch (err) {
    console.error("Błąd usuwania zadania:", err);
  }
};

// =========================================================================
// HELPERS
// =========================================================================
const getServiceStatusColor = (status: string) => {
  const map: Record<string, string> = {
    PLANNED: "grey",
    IN_PROGRESS: "orange",
    PAUSED: "yellow",
    COMPLETED: "green",
    CANCELLED: "red",
  };
  return map[status] || "grey";
};

const getServiceStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    PLANNED: "Zaplanowane",
    IN_PROGRESS: "W trakcie",
    PAUSED: "Wstrzymane",
    COMPLETED: "Zakończone",
    CANCELLED: "Anulowane",
  };
  return map[status] || status;
};

const formatCurrency = (val: any) =>
  new Intl.NumberFormat("pl-PL", { style: "currency", currency: "PLN" }).format(val || 0);

const formatDateTime = (date: string) =>
  date ? new Date(date).toLocaleString("pl-PL") : "—";

const close = () => {
  emit("update:modelValue", false);
};
</script>
