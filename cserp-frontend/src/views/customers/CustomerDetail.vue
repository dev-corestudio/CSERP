<template>
  <v-container fluid>
    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <v-progress-circular indeterminate size="64" color="primary" />
      <p class="mt-4 text-h6">Ładowanie danych klienta...</p>
    </div>

    <!-- Error -->
    <v-alert v-else-if="error" type="error" variant="tonal" prominent>
      <v-alert-title>Błąd</v-alert-title>
      {{ error }}
      <template v-slot:append>
        <v-btn color="error" variant="outlined" @click="fetchCustomer">
          Spróbuj ponownie
        </v-btn>
      </template>
    </v-alert>

    <!-- Content -->
    <template v-else-if="customer">
      <!-- Nagłówek -->
      <page-header
        :title="customer.name"
        :subtitle="`${customer.type} ${customer.nip ? '• NIP: ' + customer.nip : ''}`"
        :icon="customer.type === 'B2B' ? 'mdi-domain' : 'mdi-account'"
        :icon-color="customer.type === 'B2B' ? 'blue' : 'purple'"
        :breadcrumbs="[
          { title: 'Klienci', to: '/customers' },
          { title: customer.name, disabled: true },
        ]"
      >
        <template #actions>
          <v-chip
            :color="customer.is_active ? 'success' : 'error'"
            variant="flat"
            class="mr-4"
          >
            {{ customer.is_active ? "Aktywny" : "Nieaktywny" }}
          </v-chip>

          <v-btn
            color="warning"
            variant="elevated"
            prepend-icon="mdi-pencil"
            class="mr-2"
            @click="openEditDialog"
          >
            EDYTUJ
          </v-btn>

          <v-btn
            :color="customer.is_active ? 'error' : 'success'"
            variant="outlined"
            :prepend-icon="customer.is_active ? 'mdi-account-off' : 'mdi-account-check'"
            @click="toggleActive"
          >
            {{ customer.is_active ? "DEZAKTYWUJ" : "AKTYWUJ" }}
          </v-btn>
        </template>
      </page-header>

      <v-row>
        <!-- Lewa kolumna - projekty -->
        <v-col cols="12" md="8">
          <v-card class="bg-white border" elevation="1">
            <v-card-title class="d-flex align-center pb-2">
              <v-icon start size="small" color="primary">mdi-clipboard-list</v-icon>
              Projekty
              <v-spacer />
              <v-btn
                color="primary"
                variant="flat"
                size="small"
                prepend-icon="mdi-plus"
                @click="openProjectDialog"
              >
                Nowy projekt
              </v-btn>
            </v-card-title>
            <v-divider class="mb-2 mx-4" />

            <v-card-text class="pa-0">
              <v-data-table
                :headers="projectsHeaders"
                :items="customer.projects || []"
                :items-per-page="10"
                hover
                class="projects-table"
                @click:row="viewProject"
              >
                <template v-slot:item.full_project_number="{ item }">
                  <span class="font-weight-bold text-primary">{{ item.full_project_number }}</span>
                </template>

                <template v-slot:item.description="{ item }">
                  <div class="text-truncate" style="max-width: 300px">
                    {{ item.description || "-" }}
                  </div>
                </template>

                <template v-slot:item.overall_status="{ item }">
                  <v-chip
                    size="small"
                    :color="getStatusColor(item.overall_status)"
                    variant="flat"
                  >
                    {{ getStatusLabel(item.overall_status) }}
                  </v-chip>
                </template>

                <template v-slot:item.payment_status="{ item }">
                  <v-chip
                    size="small"
                    :color="getPaymentColor(item.payment_status)"
                    variant="tonal"
                  >
                    {{ getPaymentLabel(item.payment_status) }}
                  </v-chip>
                </template>

                <template v-slot:item.created_at="{ item }">
                  {{ formatDate(item.created_at) }}
                </template>

                <template v-slot:no-data>
                  <div class="text-center py-8">
                    <v-icon size="64" color="grey">mdi-clipboard-text-off</v-icon>
                    <div class="text-h6 mt-4 text-medium-emphasis">Brak projektów</div>
                    <v-btn
                      color="primary"
                      variant="outlined"
                      class="mt-4"
                      @click="openProjectDialog"
                    >
                      Utwórz pierwszy projekt
                    </v-btn>
                  </div>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Prawa kolumna - sidebar -->
        <v-col cols="12" md="4">
          <div class="sticky-sidebar">
            <!-- Dane kontaktowe -->
            <v-card class="mb-4 bg-white border" elevation="1">
              <v-card-title class="d-flex align-center pb-2">
                <v-icon start size="small" color="primary">mdi-card-account-details</v-icon>
                Dane klienta
              </v-card-title>
              <v-divider class="mb-2 mx-4" />

              <v-card-text class="pa-4 pt-0">
                <div class="detail-row">
                  <div class="label">Typ</div>
                  <div class="value">
                    <v-chip
                      size="small"
                      :color="customer.type === 'B2B' ? 'blue' : 'purple'"
                      variant="tonal"
                    >
                      {{ customer.type === "B2B" ? "Firma (B2B)" : "Indywidualny (B2C)" }}
                    </v-chip>
                  </div>
                </div>

                <div v-if="customer.nip" class="detail-row">
                  <div class="label">NIP</div>
                  <div class="value font-weight-bold">{{ customer.nip }}</div>
                </div>

                <div v-if="customer.email" class="detail-row">
                  <div class="label">Email</div>
                  <div class="value">
                    <a :href="`mailto:${customer.email}`" class="text-decoration-none text-primary">
                      {{ customer.email }}
                    </a>
                  </div>
                </div>

                <div v-if="customer.phone" class="detail-row">
                  <div class="label">Telefon</div>
                  <div class="value font-weight-bold">{{ customer.phone }}</div>
                </div>

                <div v-if="customer.address" class="detail-row">
                  <div class="label">Adres</div>
                  <div class="value text-right" style="max-width: 180px">{{ customer.address }}</div>
                </div>

                <div class="detail-row">
                  <div class="label">Data utworzenia</div>
                  <div class="value text-medium-emphasis">{{ formatDate(customer.created_at) }}</div>
                </div>
              </v-card-text>
            </v-card>

            <!-- Statystyki -->
            <v-card class="bg-white border" elevation="1">
              <v-card-title class="d-flex align-center pb-2">
                <v-icon start size="small" color="primary">mdi-chart-bar</v-icon>
                Statystyki projektów
              </v-card-title>
              <v-divider class="mb-2 mx-4" />

              <v-card-text class="pa-4 pt-2">
                <v-row dense>
                  <v-col cols="6">
                    <div class="stat-box bg-blue-lighten-5">
                      <div class="stat-value text-blue">{{ customer.stats?.total_projects || 0 }}</div>
                      <div class="stat-label">Wszystkich</div>
                    </div>
                  </v-col>
                  <v-col cols="6">
                    <div class="stat-box bg-orange-lighten-5">
                      <div class="stat-value text-orange">{{ customer.stats?.active_projects || 0 }}</div>
                      <div class="stat-label">Aktywnych</div>
                    </div>
                  </v-col>
                  <v-col cols="6">
                    <div class="stat-box bg-green-lighten-5">
                      <div class="stat-value text-green">{{ customer.stats?.completed_projects || 0 }}</div>
                      <div class="stat-label">Zakończonych</div>
                    </div>
                  </v-col>
                  <v-col cols="6">
                    <div class="stat-box bg-red-lighten-5">
                      <div class="stat-value text-red">{{ customer.stats?.cancelled_projects || 0 }}</div>
                      <div class="stat-label">Anulowanych</div>
                    </div>
                  </v-col>
                </v-row>

                <v-divider class="my-3" />

                <div class="detail-row">
                  <div class="label">Opłacone</div>
                  <div class="value">
                    <v-chip size="x-small" color="success" variant="tonal">
                      {{ customer.stats?.paid_projects || 0 }}
                    </v-chip>
                  </div>
                </div>
                <div class="detail-row">
                  <div class="label">Nieopłacone</div>
                  <div class="value">
                    <v-chip size="x-small" color="warning" variant="tonal">
                      {{ customer.stats?.unpaid_projects || 0 }}
                    </v-chip>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </div>
        </v-col>
      </v-row>
    </template>

    <!-- Dialog edycji klienta -->
    <customer-form-dialog
      v-model="editDialog"
      :customer="customer"
      @saved="handleCustomerSaved"
    />

    <!-- Dialog nowego projektu -->
    <project-form-dialog
      v-model="projectDialog"
      :project="null"
      :preselected-customer-id="customer?.id"
      @saved="handleProjectSaved"
    />

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">Zamknij</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import PageHeader from "@/components/layout/PageHeader.vue";
import CustomerFormDialog from "@/components/customers/CustomerFormDialog.vue";
import ProjectFormDialog from "@/components/projects/ProjectFormDialog.vue";
import customerService from "@/services/customerService";

const route = useRoute();
const router = useRouter();

// State
const loading = ref(true);
const error = ref(null);
const customer = ref(null);

// Dialog states
const editDialog = ref(false);
const projectDialog = ref(false);

// Snackbar
const snackbar = ref({
  show: false,
  message: "",
  color: "success",
});

// Projects table headers
const projectsHeaders = [
  { title: "Numer", key: "full_project_number", width: "160px" },
  { title: "Opis", key: "description" },
  { title: "Status", key: "overall_status", align: "center", width: "130px" },
  { title: "Płatność", key: "payment_status", align: "center", width: "120px" },
  { title: "Data", key: "created_at", align: "center", width: "110px" },
];

// Methods
const fetchCustomer = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await customerService.getById(route.params.id);
    customer.value = response.data || response;
  } catch (err) {
    console.error("Błąd pobierania klienta:", err);
    error.value = err.response?.data?.message || "Nie udało się pobrać danych klienta";
  } finally {
    loading.value = false;
  }
};

const openEditDialog = () => {
  editDialog.value = true;
};

const handleCustomerSaved = async () => {
  await fetchCustomer();
  showSnackbar("Dane klienta zaktualizowane", "success");
};

const toggleActive = async () => {
  try {
    await customerService.toggleActive(customer.value.id);
    await fetchCustomer();
    showSnackbar(
      customer.value.is_active ? "Klient dezaktywowany" : "Klient aktywowany",
      "success"
    );
  } catch (err) {
    console.error("Błąd zmiany statusu:", err);
    showSnackbar(err.response?.data?.message || "Błąd zmiany statusu", "error");
  }
};

const openProjectDialog = () => {
  projectDialog.value = true;
};

const handleProjectSaved = async () => {
  await fetchCustomer();
  showSnackbar("Projekt utworzony pomyślnie", "success");
};

const viewProject = (event, { item }) => {
  router.push(`/projects/${item.id}`);
};

const formatDate = (date) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("pl-PL");
};

const getStatusColor = (status) => {
  const colors = {
    draft: "grey",
    quotation: "blue",
    prototype: "purple",
    production: "orange",
    delivery: "cyan",
    completed: "success",
    cancelled: "error",
  };
  return colors[status] || "grey";
};

const getStatusLabel = (status) => {
  const labels = {
    draft: "Szkic",
    quotation: "Wycena",
    prototype: "Prototyp",
    production: "Produkcja",
    delivery: "Dostawa",
    completed: "Zakończone",
    cancelled: "Anulowane",
  };
  return labels[status] || status;
};

const getPaymentColor = (status) => {
  const colors = {
    unpaid: "warning",
    partial: "orange",
    paid: "success",
    overdue: "error",
  };
  return colors[status] || "grey";
};

const getPaymentLabel = (status) => {
  const labels = {
    unpaid: "Nieopłacone",
    partial: "Częściowo",
    paid: "Opłacone",
    overdue: "Zaległe",
  };
  return labels[status] || status;
};

const showSnackbar = (message, color = "success") => {
  snackbar.value = { show: true, message, color };
};

onMounted(() => {
  fetchCustomer();
});
</script>

<style scoped>
.sticky-sidebar {
  position: sticky;
  top: 80px;
  z-index: 1;
}

.projects-table :deep(tbody tr) {
  cursor: pointer;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f5f5f5;
  min-height: 32px;
}

.detail-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.detail-row .label {
  font-size: 0.85rem;
  color: rgba(0, 0, 0, 0.6);
  font-weight: 500;
  min-width: 100px;
  flex-shrink: 0;
}

.detail-row .value {
  font-size: 0.95rem;
  font-weight: 500;
  text-align: right;
  flex-grow: 1;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  word-break: break-word;
}

.stat-box {
  text-align: center;
  padding: 12px 8px;
  border-radius: 8px;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1;
}

.stat-label {
  font-size: 0.75rem;
  color: rgba(0, 0, 0, 0.6);
  margin-top: 4px;
}
</style>
