<template>
  <v-container fluid>
    <page-header
      title="Klienci"
      subtitle="Zarządzaj bazą klientów"
      icon="mdi-account-group"
      icon-color="teal"
      :breadcrumbs="[{ title: 'Klienci', disabled: true }]"
    >
      <template #actions>
        <v-btn
          color="primary"
          variant="elevated"
          prepend-icon="mdi-plus"
          size="large"
          @click="openCreateDialog"
        >
          NOWY KLIENT
        </v-btn>
      </template>
    </page-header>

    <!-- Filtry -->
    <v-card elevation="2" class="mb-4">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="4">
            <v-text-field
              v-model="search"
              label="Szukaj klienta"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              placeholder="Nazwa, NIP, email, telefon..."
            />
          </v-col>

          <v-col cols="12" md="2">
            <v-select
              v-model="filters.type"
              label="Typ klienta"
              :items="typeOptions"
              variant="outlined"
              density="compact"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="2">
            <v-select
              v-model="filters.is_active"
              label="Status"
              :items="activeOptions"
              variant="outlined"
              density="compact"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="4" class="d-flex justify-end gap-1">
            <v-tooltip text="Odśwież" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" @click="fetchData">
                  <v-icon>mdi-refresh</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
            <v-tooltip text="Resetuj filtry" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" @click="resetFilters">
                  <v-icon>mdi-filter-remove</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Statystyki (obliczane z paginowanej meta, lub osobny endpoint) -->
    <!-- UWAGA: Statystyki "Wszystkich klientów" itd. wymagają osobnego lekkiego
         endpointu GET /api/customers/stats, bo teraz nie mamy ALL w jednym request.
         Tymczasowo ukrywamy je lub zostawiamy jako TODO. -->

    <!-- Loading -->
    <v-card v-if="loading && items.length === 0" elevation="2" class="text-center py-12">
      <v-progress-circular indeterminate size="64" color="primary" />
      <p class="mt-4 text-h6">Ładowanie klientów...</p>
    </v-card>

    <!-- Error -->
    <v-alert
      v-else-if="error"
      type="error"
      variant="tonal"
      prominent
      closable
      class="mb-4"
      @click:close="error = null"
    >
      <v-alert-title>Błąd</v-alert-title>
      {{ error }}
      <template v-slot:append>
        <v-btn color="error" variant="outlined" @click="fetchData">
          Spróbuj ponownie
        </v-btn>
      </template>
    </v-alert>

    <!-- Tabela klientów z server-side pagination -->
    <v-card v-else elevation="2">
      <v-data-table-server
        :headers="headers"
        :items="items"
        :items-length="totalItems"
        :loading="loading"
        :items-per-page="options.itemsPerPage"
        :page="options.page"
        :sort-by="options.sortBy"
        hover
        class="customers-table"
        @click:row="handleRowClick"
        @update:options="updateOptions"
      >
        <!-- Nazwa klienta -->
        <template v-slot:item.name="{ item }">
          <div class="d-flex align-center py-2">
            <v-avatar
              :color="item.type === 'B2B' ? 'blue' : 'purple'"
              size="40"
              class="mr-3"
            >
              <v-icon color="white">
                {{ item.type === "B2B" ? "mdi-domain" : "mdi-account" }}
              </v-icon>
            </v-avatar>
            <div>
              <div class="font-weight-bold">{{ item.name }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ item.type }}
                <span v-if="item.nip"> • NIP: {{ item.nip }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Kontakt -->
        <template v-slot:item.contact="{ item }">
          <div>
            <div v-if="item.email" class="text-body-2">
              <v-icon size="small" class="mr-1">mdi-email</v-icon>
              {{ item.email }}
            </div>
            <div v-if="item.phone" class="text-body-2">
              <v-icon size="small" class="mr-1">mdi-phone</v-icon>
              {{ item.phone }}
            </div>
            <span v-if="!item.email && !item.phone" class="text-medium-emphasis">-</span>
          </div>
        </template>

        <!-- Adres -->
        <template v-slot:item.address="{ item }">
          <div class="text-truncate" style="max-width: 250px">
            {{ item.address || "-" }}
          </div>
        </template>

        <!-- Liczba zamówień -->
        <template v-slot:item.orders_count="{ item }">
          <v-chip
            size="small"
            :color="item.orders_count > 0 ? 'blue' : 'grey'"
            variant="tonal"
          >
            {{ item.orders_count || 0 }} {{ getOrdersLabel(item.orders_count) }}
          </v-chip>
        </template>

        <!-- Status -->
        <template v-slot:item.is_active="{ item }">
          <v-chip
            size="small"
            :color="item.is_active ? 'success' : 'error'"
            variant="flat"
          >
            {{ item.is_active ? "Aktywny" : "Nieaktywny" }}
          </v-chip>
        </template>

        <!-- Akcje -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex gap-1">
            <v-tooltip text="Szczegóły">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  color="info"
                  @click.stop="viewCustomer(item.id)"
                />
              </template>
            </v-tooltip>

            <v-tooltip text="Edytuj">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  color="warning"
                  @click.stop="openEditDialog(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip :text="item.is_active ? 'Dezaktywuj' : 'Aktywuj'">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  :icon="item.is_active ? 'mdi-account-off' : 'mdi-account-check'"
                  size="small"
                  variant="text"
                  :color="item.is_active ? 'error' : 'success'"
                  @click.stop="toggleActive(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip text="Usuń">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  :disabled="item.orders_count > 0"
                  @click.stop="confirmDelete(item)"
                />
              </template>
            </v-tooltip>
          </div>
        </template>

        <!-- Brak danych -->
        <template v-slot:no-data>
          <div class="text-center py-8">
            <v-icon size="64" color="grey">mdi-account-group-outline</v-icon>
            <div class="text-h6 mt-4 text-medium-emphasis">Brak klientów</div>
            <v-btn
              color="primary"
              variant="outlined"
              class="mt-4"
              @click="openCreateDialog"
            >
              Dodaj pierwszego klienta
            </v-btn>
          </div>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Dialog formularza klienta -->
    <customer-form-dialog
      v-model="formDialog"
      :customer="editingCustomer"
      @saved="handleCustomerSaved"
    />

    <!-- Dialog potwierdzenia usunięcia -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title>Potwierdzenie usunięcia</v-card-title>
        <v-card-text>
          Czy na pewno chcesz usunąć klienta
          <strong>{{ customerToDelete?.name }}</strong
          >? Tej operacji nie można cofnąć.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Anuluj</v-btn>
          <v-btn
            color="error"
            variant="elevated"
            :loading="deleting"
            @click="deleteCustomer"
          >
            Usuń
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

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
import { useRouter } from "vue-router";
import PageHeader from "@/components/layout/PageHeader.vue";
import CustomerFormDialog from "@/components/customers/CustomerFormDialog.vue";
import customerService from "@/services/customerService";
import { useServerTable } from "@/composables/useServerTable";
import { usePersistedFilters } from "@/composables/usePersistedFilters";

const router = useRouter();

// Filtry (persystowane w localStorage)
const filters = usePersistedFilters("customers:filters", {
  type: "all",
  is_active: "all", // "all" | "true" | "false"
});

// Server-side table
const {
  items,
  totalItems,
  loading,
  error,
  search,
  options,
  fetchData,
  updateOptions,
} = useServerTable("/customers", {
  defaultPerPage: 15,
  defaultSortBy: "name",
  defaultSortDir: "asc",
  extraFilters: filters,
  persistKey: "customers",
});

// Dialog state
const formDialog = ref(false);
const editingCustomer = ref(null);
const deleteDialog = ref(false);
const customerToDelete = ref<any>(null);
const deleting = ref(false);

// Snackbar
const snackbar = ref({
  show: false,
  message: "",
  color: "success",
});

// Options
const typeOptions = [
  { title: "Wszyscy", value: "all" },
  { title: "B2B (Firmy)", value: "B2B" },
  { title: "B2C (Indywidualni)", value: "B2C" },
];

const activeOptions = [
  { title: "Wszyscy", value: "all" },
  { title: "Aktywni", value: "true" },
  { title: "Nieaktywni", value: "false" },
];

// Table headers
const headers = [
  { title: "Klient", key: "name", width: "250px" },
  { title: "Kontakt", key: "contact", width: "220px", sortable: false },
  { title: "Adres", key: "address", width: "250px", sortable: false },
  { title: "Zamówienia", key: "orders_count", align: "center", width: "120px" },
  { title: "Status", key: "is_active", align: "center", width: "110px", sortable: false },
  { title: "Akcje", key: "actions", align: "center", width: "160px", sortable: false },
];

// Helpers
const getOrdersLabel = (count: number) => {
  if (!count || count === 0) return "zamówień";
  if (count === 1) return "zamówienie";
  if (count >= 2 && count <= 4) return "zamówienia";
  return "zamówień";
};

const resetFilters = () => {
  search.value = "";
  filters.value = { type: "all", is_active: "all" };
};

// Actions
const openCreateDialog = () => {
  editingCustomer.value = null;
  formDialog.value = true;
};

const openEditDialog = (customer: any) => {
  editingCustomer.value = { ...customer };
  formDialog.value = true;
};

const handleCustomerSaved = async () => {
  await fetchData();
  snackbar.value = {
    show: true,
    message: "Klient zapisany pomyślnie",
    color: "success",
  };
};

const viewCustomer = (id: number) => {
  router.push(`/customers/${id}`);
};

const handleRowClick = (_event: any, { item }: any) => {
  viewCustomer(item.id);
};

const toggleActive = async (customer: any) => {
  try {
    await customerService.toggleActive(customer.id);
    await fetchData();
    snackbar.value = {
      show: true,
      message: customer.is_active ? "Klient dezaktywowany" : "Klient aktywowany",
      color: "success",
    };
  } catch (err) {
    snackbar.value = {
      show: true,
      message: "Błąd zmiany statusu",
      color: "error",
    };
  }
};

const confirmDelete = (customer: any) => {
  customerToDelete.value = customer;
  deleteDialog.value = true;
};

const deleteCustomer = async () => {
  try {
    deleting.value = true;
    await customerService.delete(customerToDelete.value.id);
    deleteDialog.value = false;
    await fetchData();
    snackbar.value = {
      show: true,
      message: "Klient usunięty",
      color: "success",
    };
  } catch (err: any) {
    snackbar.value = {
      show: true,
      message: err.response?.data?.message || "Błąd usuwania klienta",
      color: "error",
    };
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.customers-table :deep(tbody tr) {
  cursor: pointer;
  transition: background-color 0.2s;
}
</style>
