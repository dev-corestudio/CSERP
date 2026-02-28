<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="closeDialog"
    max-width="800"
    persistent
    scrollable
  >
    <v-card class="rounded-lg d-flex flex-column">
      <!-- Header -->
      <v-toolbar color="primary" density="comfortable" flat>
        <v-icon class="ml-4">{{ isEdit ? "mdi-pencil" : "mdi-plus" }}</v-icon>
        <v-toolbar-title class="text-subtitle-1 font-weight-bold">
          {{ isEdit ? "Edycja Stanowiska" : "Nowe Stanowisko" }}
        </v-toolbar-title>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="closeDialog" />
      </v-toolbar>

      <!-- Tabs Navigation -->
      <div class="bg-grey-lighten-4">
        <v-tabs v-model="activeTab" color="primary" align-tabs="start" density="compact">
          <v-tab value="basic" class="text-capitalize">
            <v-icon start>mdi-information-outline</v-icon>
            Dane podstawowe
          </v-tab>
          <v-tab value="operators" :disabled="!isEdit" class="text-capitalize">
            <v-icon start>mdi-account-group-outline</v-icon>
            Operatorzy
          </v-tab>
          <v-tab value="services" :disabled="!isEdit" class="text-capitalize">
            <v-icon start>mdi-wrench-outline</v-icon>
            Przypisane usługi
          </v-tab>
        </v-tabs>
      </div>

      <v-divider />

      <!-- Content Area -->
      <v-card-text class="pa-0 flex-grow-1" style="min-height: 400px">
        <v-window v-model="activeTab" class="h-100">
          <!-- TAB: Dane podstawowe -->
          <v-window-item value="basic" class="pa-6">
            <v-form ref="formRef" @submit.prevent="handleSubmit">
              <v-row dense>
                <v-col cols="12" md="8">
                  <v-text-field
                    v-model="form.name"
                    label="Nazwa stanowiska *"
                    placeholder="np. Laser-01"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-label"
                    :rules="rules.required"
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-select
                    v-model="form.type"
                    :items="metadataStore.workstationTypes"
                    item-title="label"
                    item-value="value"
                    label="Typ stanowiska *"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-shape"
                    :rules="rules.required"
                  />
                </v-col>

                <v-col cols="12">
                  <v-text-field
                    v-model="form.location"
                    label="Lokalizacja"
                    placeholder="np. Hala A - Sekcja 1"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-map-marker"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.status"
                    :items="statusOptions"
                    item-title="title"
                    item-value="value"
                    label="Status techniczny"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="mdi-traffic-light"
                  >
                    <template v-slot:selection="{ item }">
                      <v-chip
                        :color="item.raw.color"
                        size="small"
                        label
                        class="font-weight-medium"
                      >
                        {{ item.title }}
                      </v-chip>
                    </template>
                    <template v-slot:item="{ props, item }">
                      <v-list-item v-bind="props" density="compact">
                        <template v-slot:prepend>
                          <v-icon :color="item.raw.color" size="small">mdi-circle</v-icon>
                        </template>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>
              </v-row>
            </v-form>
          </v-window-item>

          <!-- TAB: Operatorzy (Zmieniona struktura) -->
          <v-window-item value="operators" class="pa-0">
            <!-- Sekcja dodawania (szare tło) -->
            <div class="bg-grey-lighten-5 pa-6 border-b">
              <div class="d-flex align-center gap-4">
                <v-autocomplete
                  v-model="operatorsToAdd"
                  :items="availableOperators"
                  item-title="name"
                  item-value="id"
                  label="Wybierz operatorów"
                  placeholder="Wyszukaj pracownika..."
                  variant="outlined"
                  density="comfortable"
                  bg-color="white"
                  hide-details
                  class="flex-grow-1 mr-2"
                  prepend-inner-icon="mdi-account-search"
                  multiple
                  chips
                  closable-chips
                  no-data-text="Brak dostępnych operatorów"
                >
                  <!-- Naprawa przyciętego awatara w chipie -->
                  <template v-slot:chip="{ props, item }">
                    <v-chip
                      v-bind="props"
                      color="primary"
                      variant="tonal"
                      pill
                      label
                      class="pr-2"
                    >
                      <v-avatar start size="24" color="primary" class="text-white ml-0">
                        {{ item.raw.name?.charAt(0) }}
                      </v-avatar>
                      <span class="text-truncate">{{ item.raw.name }}</span>
                    </v-chip>
                  </template>

                  <template v-slot:item="{ props, item }">
                    <v-list-item v-bind="props" :subtitle="item.raw.email">
                      <template v-slot:prepend>
                        <v-avatar color="grey-lighten-3" size="32" variant="flat">
                          {{ item.raw.name?.charAt(0) }}
                        </v-avatar>
                      </template>
                    </v-list-item>
                  </template>
                </v-autocomplete>

                <v-btn
                  color="secondary"
                  height="48"
                  :disabled="operatorsToAdd.length === 0"
                  @click="handleAddOperators"
                  class="px-6"
                >
                  <v-icon start>mdi-account-plus</v-icon>
                  Dodaj
                </v-btn>
              </div>
            </div>

            <!-- Lista przypisanych (Tabela) -->
            <div class="pa-0">
              <v-table v-if="assignedOperators.length > 0" hover density="comfortable">
                <thead>
                  <tr>
                    <th class="text-left font-weight-bold">Pracownik</th>
                    <th class="text-left font-weight-bold">Email/ID</th>
                    <th class="text-right font-weight-bold" style="width: 100px">
                      Akcje
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="op in assignedOperators" :key="op.id">
                    <td>
                      <div class="d-flex align-center py-2">
                        <v-avatar color="secondary" size="36" variant="flat" class="mr-3">
                          <span class="text-white font-weight-bold">{{
                            op.name?.charAt(0)
                          }}</span>
                        </v-avatar>
                        <span class="font-weight-medium">{{ op.name }}</span>
                      </div>
                    </td>
                    <td class="text-medium-emphasis">{{ op.email }}</td>
                    <td class="text-right">
                      <v-btn
                        icon="mdi-delete"
                        size="small"
                        color="error"
                        variant="text"
                        title="Usuń przypisanie"
                        @click="handleRemoveOperator(op.id)"
                      />
                    </td>
                  </tr>
                </tbody>
              </v-table>

              <div v-else class="text-center py-12 text-medium-emphasis">
                <v-icon size="64" class="mb-4 opacity-20">mdi-account-off-outline</v-icon>
                <div class="text-body-1">Brak przypisanych operatorów</div>
                <div class="text-caption">Wybierz pracowników z listy powyżej</div>
              </div>
            </div>
          </v-window-item>

          <!-- TAB: Usługi -->
          <v-window-item value="services" class="pa-0">
            <!-- Sekcja dodawania -->
            <div class="bg-grey-lighten-5 pa-6 border-b">
              <div class="d-flex align-center gap-4">
                <v-autocomplete
                  v-model="serviceToAdd"
                  :items="availableServices"
                  item-title="name"
                  item-value="id"
                  label="Dodaj usługę do stanowiska"
                  placeholder="Wpisz nazwę usługi..."
                  variant="outlined"
                  density="comfortable"
                  bg-color="white"
                  hide-details
                  :loading="loadingServices"
                  class="flex-grow-1 mr-2"
                  no-data-text="Brak dostępnych usług do przypisania"
                >
                  <template v-slot:item="{ props, item }">
                    <v-list-item v-bind="props" :subtitle="item.raw.category">
                    </v-list-item>
                  </template>
                </v-autocomplete>

                <v-btn
                  color="secondary"
                  height="48"
                  :disabled="!serviceToAdd"
                  :loading="attaching"
                  @click="handleAttachService"
                  class="px-6"
                >
                  <v-icon start>mdi-link-plus</v-icon>
                  Dodaj
                </v-btn>
              </div>
            </div>

            <!-- Lista przypisanych -->
            <div class="pa-0">
              <div v-if="loadingAssigned" class="d-flex justify-center align-center py-8">
                <v-progress-circular indeterminate color="primary" />
              </div>

              <v-table
                v-else-if="workstationStore.currentWorkstationServices.length > 0"
                density="comfortable"
                hover
              >
                <thead>
                  <tr>
                    <th class="text-left font-weight-bold">Nazwa Usługi</th>
                    <th class="text-left font-weight-bold">Kategoria</th>
                    <th class="text-right font-weight-bold">Akcje</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in workstationStore.currentWorkstationServices"
                    :key="item.id"
                  >
                    <td class="font-weight-medium">{{ item.name }}</td>
                    <td>
                      <v-chip size="small" label>{{ item.category }}</v-chip>
                    </td>
                    <td class="text-right">
                      <v-btn
                        icon="mdi-link-variant-off"
                        size="small"
                        color="error"
                        variant="text"
                        title="Odłącz usługę"
                        @click="handleDetachService(item.id)"
                        :loading="detachingId === item.id"
                      />
                    </td>
                  </tr>
                </tbody>
              </v-table>

              <div v-else class="text-center py-12 text-medium-emphasis">
                <v-icon size="64" class="mb-4 opacity-20">mdi-wrench-outline</v-icon>
                <div class="text-body-1">Brak przypisanych usług</div>
                <div class="text-caption">
                  Dodaj usługi korzystając z formularza powyżej
                </div>
              </div>
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>

      <v-divider />

      <!-- Footer Actions -->
      <v-card-actions class="pa-4 bg-white">
        <v-btn
          variant="text"
          color="grey-darken-1"
          @click="closeDialog"
          :disabled="saving"
        >
          Anuluj
        </v-btn>
        <v-spacer />
        <v-btn
          color="primary"
          variant="elevated"
          @click="handleSubmit"
          :loading="saving"
          class="px-6"
        >
          {{ isEdit ? "Zapisz zmiany" : "Utwórz stanowisko" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useWorkstationStore } from "@/stores/workstations";
import { useAssortmentStore } from "@/stores/assortment";
import { useMetadataStore } from "@/stores/metadata";

// Props & Emits
const props = defineProps({
  modelValue: Boolean,
  workstation: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

// Stores
const workstationStore = useWorkstationStore();
const assortmentStore = useAssortmentStore();
const metadataStore = useMetadataStore();

// State
const activeTab = ref("basic");
const formRef = ref(null);
const saving = ref(false);
const loadingServices = ref(false);
const loadingAssigned = ref(false);
const attaching = ref(false);
const detachingId = ref(null);

// Form Data & Buffers
const defaultForm = {
  name: "",
  type: null,
  location: "",
  status: "IDLE",
  operator_ids: [],
};
const form = ref({ ...defaultForm });

const serviceToAdd = ref(null);
const operatorsToAdd = ref([]); // Bufor dla selecta operatorów

// Constants & Helpers
const rules = {
  required: [(v) => !!v || "To pole jest wymagane"],
};

const statusOptions = [
  { title: "Dostępne (Idle)", value: "IDLE", color: "success" },
  { title: "W użyciu (Active)", value: "ACTIVE", color: "primary" },
  { title: "Przerwa (Paused)", value: "PAUSED", color: "warning" },
  { title: "Konserwacja (Maintenance)", value: "MAINTENANCE", color: "error" },
];

const isEdit = computed(() => !!props.workstation);

// --- Computed dla Operatorów ---

// 1. Zwraca obiekty operatorów, którzy są już przypisani (bazując na form.operator_ids)
const assignedOperators = computed(() => {
  if (!workstationStore.workers) return [];
  return workstationStore.workers.filter((w) => form.value.operator_ids.includes(w.id));
});

// 2. Zwraca listę dostępnych operatorów (wyklucza już przypisanych)
const availableOperators = computed(() => {
  if (!workstationStore.workers) return [];
  return workstationStore.workers.filter((w) => !form.value.operator_ids.includes(w.id));
});

// --- Computed dla Usług ---

const availableServices = computed(() => {
  const assignedIds = new Set(
    workstationStore.currentWorkstationServices.map((s) => s.id)
  );
  return assortmentStore.items.filter(
    (item) => item.type?.toUpperCase() === "SERVICE" && !assignedIds.has(item.id)
  );
});

// Watchers
watch(
  () => props.modelValue,
  async (isOpen) => {
    if (isOpen) {
      activeTab.value = "basic";
      serviceToAdd.value = null;
      operatorsToAdd.value = [];

      // Reset lub wczytanie danych
      if (props.workstation) {
        form.value = {
          name: props.workstation.name,
          type: props.workstation.type,
          location: props.workstation.location,
          status: props.workstation.status,
          operator_ids: props.workstation.operators?.map((u) => u.id) || [],
        };

        await Promise.all([workstationStore.fetchWorkers(), loadServicesData()]);
      } else {
        form.value = { ...defaultForm };
        workstationStore.currentWorkstationServices = [];
        workstationStore.fetchWorkers();
      }
    }
  }
);

// Methods
const loadServicesData = async () => {
  loadingAssigned.value = true;
  loadingServices.value = true;
  try {
    await assortmentStore.fetchItems({ type: "service" });
    if (props.workstation?.id) {
      await workstationStore.fetchWorkstationServices(props.workstation.id);
    }
  } catch (e) {
    console.error("Błąd pobierania danych usług", e);
  } finally {
    loadingAssigned.value = false;
    loadingServices.value = false;
  }
};

// Logika dodawania/usuwania operatorów w GUI (przed zapisem)
const handleAddOperators = () => {
  if (operatorsToAdd.value.length === 0) return;

  // Dodaj wybrane ID do formularza
  form.value.operator_ids = [...form.value.operator_ids, ...operatorsToAdd.value];

  // Wyczyść selekcję
  operatorsToAdd.value = [];
};

const handleRemoveOperator = (id) => {
  form.value.operator_ids = form.value.operator_ids.filter((opId) => opId !== id);
};

const handleSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  saving.value = true;
  try {
    if (isEdit.value) {
      await workstationStore.updateWorkstation(props.workstation.id, form.value);
    } else {
      await workstationStore.createWorkstation(form.value);
    }
    emit("saved");
    if (!isEdit.value) closeDialog();
  } catch (error) {
    console.error(error);
    alert(
      "Wystąpił błąd podczas zapisu: " +
        (error.response?.data?.message || "Nieznany błąd")
    );
  } finally {
    saving.value = false;
  }
};

const handleAttachService = async () => {
  if (!serviceToAdd.value || !props.workstation?.id) return;

  attaching.value = true;
  try {
    await workstationStore.attachService(props.workstation.id, serviceToAdd.value);
    serviceToAdd.value = null;
    await workstationStore.fetchWorkstationServices(props.workstation.id);
  } catch (e) {
    alert("Nie udało się przypisać usługi.");
  } finally {
    attaching.value = false;
  }
};

const handleDetachService = async (serviceId) => {
  if (!confirm("Czy na pewno chcesz odłączyć tę usługę?")) return;

  detachingId.value = serviceId;
  try {
    await workstationStore.detachService(props.workstation.id, serviceId);
    await workstationStore.fetchWorkstationServices(props.workstation.id);
  } catch (e) {
    alert("Nie udało się odłączyć usługi.");
  } finally {
    detachingId.value = null;
  }
};

const closeDialog = () => {
  emit("update:modelValue", false);
};
</script>
