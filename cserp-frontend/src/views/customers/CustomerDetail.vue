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
        <!-- Lewa kolumna - dane klienta -->
        <v-col cols="12" md="4">
          <!-- Dane kontaktowe -->
          <v-card elevation="2" class="mb-4">
            <v-card-title class="bg-grey-lighten-4">
              <v-icon class="mr-2">mdi-card-account-details</v-icon>
              Dane kontaktowe
            </v-card-title>
            <v-card-text class="pa-4">
              <v-list density="compact" class="bg-transparent">
                <v-list-item v-if="customer.email">
                  <template v-slot:prepend>
                    <v-icon color="blue">mdi-email</v-icon>
                  </template>
                  <v-list-item-title>{{ customer.email }}</v-list-item-title>
                  <v-list-item-subtitle>Email</v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="customer.phone">
                  <template v-slot:prepend>
                    <v-icon color="green">mdi-phone</v-icon>
                  </template>
                  <v-list-item-title>{{ customer.phone }}</v-list-item-title>
                  <v-list-item-subtitle>Telefon</v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="customer.address">
                  <template v-slot:prepend>
                    <v-icon color="red">mdi-map-marker</v-icon>
                  </template>
                  <v-list-item-title>{{ customer.address }}</v-list-item-title>
                  <v-list-item-subtitle>Adres</v-list-item-subtitle>
                </v-list-item>

                <v-list-item v-if="customer.nip">
                  <template v-slot:prepend>
                    <v-icon color="purple">mdi-identifier</v-icon>
                  </template>
                  <v-list-item-title>{{ customer.nip }}</v-list-item-title>
                  <v-list-item-subtitle>NIP</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="teal">mdi-tag</v-icon>
                  </template>
                  <v-list-item-title>
                    <v-chip
                      size="small"
                      :color="customer.type === 'B2B' ? 'blue' : 'purple'"
                    >
                      {{ customer.type === "B2B" ? "Firma (B2B)" : "Indywidualny (B2C)" }}
                    </v-chip>
                  </v-list-item-title>
                  <v-list-item-subtitle>Typ klienta</v-list-item-subtitle>
                </v-list-item>

                <v-list-item>
                  <template v-slot:prepend>
                    <v-icon color="grey">mdi-calendar</v-icon>
                  </template>
                  <v-list-item-title>{{
                    formatDate(customer.created_at)
                  }}</v-list-item-title>
                  <v-list-item-subtitle>Data utworzenia</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Statystyki -->
          <v-card elevation="2">
            <v-card-title class="bg-grey-lighten-4">
              <v-icon class="mr-2">mdi-chart-bar</v-icon>
              Statystyki
            </v-card-title>
            <v-card-text class="pa-4">
              <v-row dense>
                <v-col cols="6">
                  <div class="text-center pa-3 rounded bg-blue-lighten-5">
                    <div class="text-h4 font-weight-bold text-blue">
                      {{ customer.stats?.total_orders || 0 }}
                    </div>
                    <div class="text-caption">Wszystkich zamówień</div>
                  </div>
                </v-col>
                <v-col cols="6">
                  <div class="text-center pa-3 rounded bg-orange-lighten-5">
                    <div class="text-h4 font-weight-bold text-orange">
                      {{ customer.stats?.active_orders || 0 }}
                    </div>
                    <div class="text-caption">Aktywnych</div>
                  </div>
                </v-col>
                <v-col cols="6">
                  <div class="text-center pa-3 rounded bg-green-lighten-5">
                    <div class="text-h4 font-weight-bold text-green">
                      {{ customer.stats?.completed_orders || 0 }}
                    </div>
                    <div class="text-caption">Zakończonych</div>
                  </div>
                </v-col>
                <v-col cols="6">
                  <div class="text-center pa-3 rounded bg-red-lighten-5">
                    <div class="text-h4 font-weight-bold text-red">
                      {{ customer.stats?.cancelled_orders || 0 }}
                    </div>
                    <div class="text-caption">Anulowanych</div>
                  </div>
                </v-col>
              </v-row>

              <v-divider class="my-4" />

              <!-- Status płatności -->
              <div class="text-subtitle-2 mb-2">Status płatności</div>
              <div class="d-flex justify-space-between mb-1">
                <span class="text-body-2">Opłacone:</span>
                <v-chip size="x-small" color="success">{{
                  customer.stats?.paid_orders || 0
                }}</v-chip>
              </div>
              <div class="d-flex justify-space-between">
                <span class="text-body-2">Nieopłacone:</span>
                <v-chip size="x-small" color="warning">{{
                  customer.stats?.unpaid_orders || 0
                }}</v-chip>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Prawa kolumna - zamówienia -->
        <v-col cols="12" md="8">
          <v-card elevation="2">
            <v-card-title class="bg-grey-lighten-4 d-flex align-center">
              <v-icon class="mr-2">mdi-clipboard-list</v-icon>
              Ostatnie zamówienia
              <v-spacer />
              <v-btn
                color="primary"
                variant="elevated"
                size="small"
                prepend-icon="mdi-plus"
                @click="openOrderDialog"
              >
                Nowe zamówienie
              </v-btn>
            </v-card-title>

            <v-card-text class="pa-0">
              <v-data-table
                :headers="ordersHeaders"
                :items="customer.orders || []"
                :items-per-page="10"
                hover
                class="orders-table"
                @click:row="viewOrder"
              >
                <!-- Numer zamówienia (Pełny) -->
                <template v-slot:item.full_order_number="{ item }">
                  <span class="font-weight-bold text-primary">{{
                    item.full_order_number
                  }}</span>
                </template>

                <!-- Opis (zamiast Brief) -->
                <template v-slot:item.description="{ item }">
                  <div class="text-truncate" style="max-width: 300px">
                    {{ item.description || "-" }}
                  </div>
                </template>

                <!-- Status -->
                <template v-slot:item.overall_status="{ item }">
                  <v-chip
                    size="small"
                    :color="getStatusColor(item.overall_status)"
                    variant="flat"
                  >
                    {{ getStatusLabel(item.overall_status) }}
                  </v-chip>
                </template>

                <!-- Płatność -->
                <template v-slot:item.payment_status="{ item }">
                  <v-chip
                    size="small"
                    :color="getPaymentColor(item.payment_status)"
                    variant="tonal"
                  >
                    {{ getPaymentLabel(item.payment_status) }}
                  </v-chip>
                </template>

                <!-- Data -->
                <template v-slot:item.created_at="{ item }">
                  {{ formatDate(item.created_at) }}
                </template>

                <!-- Brak zamówień -->
                <template v-slot:no-data>
                  <div class="text-center py-8">
                    <v-icon size="64" color="grey">mdi-clipboard-text-off</v-icon>
                    <div class="text-h6 mt-4 text-medium-emphasis">Brak zamówień</div>
                    <v-btn
                      color="primary"
                      variant="outlined"
                      class="mt-4"
                      @click="openOrderDialog"
                    >
                      Utwórz pierwsze zamówienie
                    </v-btn>
                  </div>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>

    <!-- Dialog edycji klienta -->
    <customer-form-dialog
      v-model="editDialog"
      :customer="customer"
      @saved="handleCustomerSaved"
    />

    <!-- Dialog nowego zamówienia -->
    <order-form-dialog
      v-model="orderDialog"
      :order="null"
      :preselected-customer-id="customer?.id"
      @saved="handleOrderSaved"
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
import OrderFormDialog from "@/components/orders/OrderFormDialog.vue";
import customerService from "@/services/customerService";

const route = useRoute();
const router = useRouter();

// State
const loading = ref(true);
const error = ref(null);
const customer = ref(null);

// Dialog states
const editDialog = ref(false);
const orderDialog = ref(false);

// Snackbar
const snackbar = ref({
  show: false,
  message: "",
  color: "success",
});

// Orders table headers (Zaktualizowane)
const ordersHeaders = [
  { title: "Numer", key: "full_order_number", width: "160px" },
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

// Customer dialog handlers
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

// Order dialog handlers
const openOrderDialog = () => {
  orderDialog.value = true;
};

const handleOrderSaved = async () => {
  // Odśwież dane klienta (w tym listę zamówień)
  await fetchCustomer();
  showSnackbar("Zamówienie utworzone pomyślnie", "success");
};

// Navigation
const viewOrder = (event, { item }) => {
  router.push(`/orders/${item.id}`);
};

// Helpers
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

// Lifecycle
onMounted(() => {
  fetchCustomer();
});
</script>

<style scoped>
.orders-table :deep(tbody tr) {
  cursor: pointer;
  transition: background-color 0.2s;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
