<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="650"
    persistent
  >
    <v-card>
      <v-card-title class="d-flex align-center pa-4 bg-primary">
        <v-icon class="mr-2">{{
          isEditing ? "mdi-clipboard-edit" : "mdi-clipboard-plus"
        }}</v-icon>
        {{ isEditing ? "Edytuj zamówienie" : "Nowe zamówienie" }}
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" density="compact" @click="closeDialog" />
      </v-card-title>

      <v-form ref="formRef" v-model="formValid" @submit.prevent="saveOrder">
        <v-card-text class="pa-6">
          <v-row>
            <!-- Numer zamówienia - tylko podgląd (serwer auto-przypisuje) -->
            <v-col cols="12" md="6">
              <!-- TRYB TWORZENIA: Pokaż podgląd auto-generowanego numeru -->
              <v-text-field
                v-if="!isEditing"
                :model-value="nextNumberPreview"
                label="Numer zamówienia"
                variant="outlined"
                prepend-inner-icon="mdi-pound"
                readonly
                :loading="loadingNumber"
                hint="Automatycznie przydzielany przez system"
                persistent-hint
                bg-color="grey-lighten-4"
              >
                <template v-slot:append-inner>
                  <v-tooltip text="Odśwież podgląd numeru">
                    <template v-slot:activator="{ props: tooltipProps }">
                      <v-btn
                        v-bind="tooltipProps"
                        icon="mdi-refresh"
                        size="small"
                        variant="text"
                        color="grey"
                        :loading="loadingNumber"
                        @click="fetchNextNumber"
                      />
                    </template>
                  </v-tooltip>
                </template>
              </v-text-field>

              <!-- TRYB EDYCJI: Pokaż bieżący numer jako read-only -->
              <v-text-field
                v-else
                :model-value="order?.order_number"
                label="Numer zamówienia"
                variant="outlined"
                prepend-inner-icon="mdi-pound"
                readonly
                bg-color="grey-lighten-4"
                hint="Numer zamówienia nie może być zmieniony"
                persistent-hint
              />
            </v-col>

            <!-- Data realizacji -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.planned_delivery_date"
                label="Planowana data realizacji *"
                type="date"
                variant="outlined"
                prepend-inner-icon="mdi-calendar"
                :rules="[rules.required]"
              />
            </v-col>

            <!-- Klient -->
            <v-col cols="12">
              <v-autocomplete
                v-model="form.customer_id"
                :items="customers"
                item-title="name"
                item-value="id"
                label="Klient *"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                :rules="[rules.required]"
                :loading="loadingCustomers"
                placeholder="Wybierz lub wyszukaj klienta..."
                no-data-text="Brak klientów. Dodaj nowego!"
                clearable
                :custom-filter="filterCustomers"
              >
                <template v-slot:item="{ item, props }">
                  <v-list-item v-bind="props">
                    <template v-slot:prepend>
                      <v-avatar
                        :color="item.raw.type === 'B2B' ? 'blue' : 'purple'"
                        size="32"
                      >
                        <v-icon size="small" color="white">
                          {{ item.raw.type === "B2B" ? "mdi-domain" : "mdi-account" }}
                        </v-icon>
                      </v-avatar>
                    </template>
                    <template v-slot:subtitle>
                      <span>{{ item.raw.type }}</span>
                      <span v-if="item.raw.nip"> • NIP: {{ item.raw.nip }}</span>
                    </template>
                  </v-list-item>
                </template>

                <template v-slot:selection="{ item }">
                  <v-chip
                    size="small"
                    :color="item.raw.type === 'B2B' ? 'blue' : 'purple'"
                  >
                    <v-icon start size="small">
                      {{ item.raw.type === "B2B" ? "mdi-domain" : "mdi-account" }}
                    </v-icon>
                    {{ item.raw.name }}
                  </v-chip>
                </template>

                <template v-slot:append>
                  <v-tooltip text="Dodaj nowego klienta">
                    <template v-slot:activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-account-plus"
                        color="success"
                        variant="flat"
                        rounded="sm"
                        height="56"
                        width="56"
                        class="ml-2"
                        @click="openCustomerDialog"
                      />
                    </template>
                  </v-tooltip>
                </template>
              </v-autocomplete>

              <v-expand-transition>
                <v-alert
                  v-if="selectedCustomer"
                  type="info"
                  variant="tonal"
                  density="compact"
                  class="mt-2"
                >
                  <div class="d-flex align-center">
                    <div>
                      <strong>{{ selectedCustomer.name }}</strong>
                      <div class="text-caption">
                        {{
                          selectedCustomer.nip
                            ? "NIP: " + selectedCustomer.nip
                            : "E-mail: " + selectedCustomer.email
                        }}
                      </div>
                    </div>
                    <v-spacer />
                    <v-chip
                      size="x-small"
                      :color="selectedCustomer.type === 'B2B' ? 'blue' : 'purple'"
                    >
                      {{ selectedCustomer.type }}
                    </v-chip>
                  </div>
                </v-alert>
              </v-expand-transition>
            </v-col>

            <!-- Opis (Brief) -->
            <v-col cols="12">
              <v-textarea
                v-model="form.description"
                label="Opis zamówienia *"
                prepend-inner-icon="mdi-text"
                variant="outlined"
                rows="4"
                :rules="[rules.required]"
                placeholder="Opisz szczegółowo czego potrzebuje klient: wymiary, materiały, kolory, ilości..."
                counter
                maxlength="2000"
              />
            </v-col>

            <!-- Priorytet -->
            <v-col cols="12" md="6">
              <v-select
                v-model="form.priority"
                label="Priorytet"
                prepend-inner-icon="mdi-flag"
                variant="outlined"
                :items="priorityOptions"
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-4">
          <v-btn
            variant="text"
            color="grey"
            prepend-icon="mdi-close"
            @click="closeDialog"
            :disabled="saving"
          >
            Anuluj
          </v-btn>
          <v-spacer />
          <v-btn
            color="primary"
            variant="elevated"
            type="submit"
            :loading="saving"
            :disabled="!formValid"
            prepend-icon="mdi-content-save"
          >
            {{ isEditing ? "Zapisz zmiany" : "Utwórz zamówienie" }}
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>

  <customer-form-dialog
    v-model="customerDialog"
    :customer="null"
    @saved="handleCustomerSaved"
  />
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import CustomerFormDialog from "@/components/customers/CustomerFormDialog.vue";
import api from "@/services/api";
import { orderService } from "@/services/orderService";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  order: {
    type: Object,
    default: null,
  },
  preselectedCustomerId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

// ─── Stan formularza ──────────────────────────────────────────────────────────

const formRef = ref(null);
const formValid = ref(false);
const saving = ref(false);
const loadingNumber = ref(false);

// Podgląd auto-generowanego numeru (tylko informacyjny)
const nextNumberPreview = ref("Ładowanie...");

// Klienci
const customers = ref<any[]>([]);
const loadingCustomers = ref(false);

// Dialog klienta
const customerDialog = ref(false);

// Dane formularza (BEZ order_number — serwer przydziela automatycznie)
const defaultForm = () => ({
  customer_id: null as number | null,
  description: "",
  planned_delivery_date: "",
  priority: "normal",
});

const form = ref(defaultForm());

// ─── Opcje ────────────────────────────────────────────────────────────────────

const priorityOptions = [
  { title: "Niski", value: "low" },
  { title: "Normalny", value: "normal" },
  { title: "Wysoki", value: "high" },
  { title: "Pilny", value: "urgent" },
];

const rules = {
  required: (v: any) => !!v || "Pole wymagane",
};

// ─── Computed ─────────────────────────────────────────────────────────────────

const isEditing = computed(() => !!props.order);

const selectedCustomer = computed(() => {
  if (!form.value.customer_id) return null;
  return customers.value.find((c) => c.id === form.value.customer_id) || null;
});

// ─── Metody ───────────────────────────────────────────────────────────────────

/** Pobierz podgląd kolejnego numeru (tylko informacyjnie, bez rezerwacji) */
const fetchNextNumber = async () => {
  if (isEditing.value) return;
  loadingNumber.value = true;
  try {
    nextNumberPreview.value = await orderService.getNextNumber();
  } catch (err) {
    console.error("Błąd pobierania numeru:", err);
    nextNumberPreview.value = "Auto";
  } finally {
    loadingNumber.value = false;
  }
};

/** Pobierz listę klientów */
const fetchCustomers = async () => {
  try {
    loadingCustomers.value = true;
    const response = await api.get("/customers/for-select");
    customers.value = response.data || [];
  } catch (error) {
    console.error("Błąd pobierania klientów:", error);
    try {
      const fallbackResponse = await api.get("/customers", {
        params: { is_active: true },
      });
      customers.value = fallbackResponse.data?.data || fallbackResponse.data || [];
    } catch {
      customers.value = [];
    }
  } finally {
    loadingCustomers.value = false;
  }
};

/** Customowy filtr: Nazwa LUB NIP */
const filterCustomers = (itemTitle: string, queryText: string, item: any) => {
  const text = queryText.toLowerCase();
  const name = item.raw.name.toLowerCase();
  const nip = item.raw.nip ? item.raw.nip.replace(/-/g, "") : "";
  return name.includes(text) || nip.includes(text.replace(/-/g, ""));
};

/** Otwórz dialog tworzenia klienta */
const openCustomerDialog = () => {
  customerDialog.value = true;
};

/** Po zapisaniu nowego klienta: odśwież listę i zaznacz go */
const handleCustomerSaved = async (savedCustomer: any) => {
  await fetchCustomers();
  if (savedCustomer?.data?.id) {
    form.value.customer_id = savedCustomer.data.id;
  } else {
    // Fallback: wybierz ostatnio dodanego
    const newestCustomer = customers.value.reduce(
      (max: any, c: any) => (c.id > max.id ? c : max),
      customers.value[0]
    );
    if (newestCustomer) {
      form.value.customer_id = newestCustomer.id;
    }
  }
};

/** Zamknij dialog */
const closeDialog = () => {
  emit("update:modelValue", false);
};

/** Zapisz zamówienie */
const saveOrder = async () => {
  const { valid } = await formRef.value!.validate();
  if (!valid) return;

  try {
    saving.value = true;

    // Payload BEZ order_number (serwer przydziela automatycznie przy tworzeniu)
    const data = {
      customer_id: form.value.customer_id,
      description: form.value.description,
      planned_delivery_date: form.value.planned_delivery_date,
      priority: form.value.priority,
    };

    if (isEditing.value) {
      // Przy edycji: wysyłamy bez order_number (nie może być zmieniony)
      await api.put(`/orders/${props.order!.id}`, data);
    } else {
      // Przy tworzeniu: serwer auto-generuje order_number
      await api.post("/orders", data);
    }

    emit("saved");
    closeDialog();
  } catch (error: any) {
    if (error.response?.status === 422 && error.response.data.errors) {
      const errors = error.response.data.errors;
      const messages = Object.values(errors).flat().join("\n");
      alert("Błąd walidacji:\n" + messages);
    } else {
      alert(
        error.response?.data?.message || "Wystąpił błąd podczas zapisywania zamówienia"
      );
    }
  } finally {
    saving.value = false;
  }
};

// ─── Watchery ─────────────────────────────────────────────────────────────────

/** Wypełnij formularz przy edycji */
watch(
  () => props.order,
  (newVal) => {
    if (newVal) {
      form.value = {
        customer_id: newVal.customer_id || null,
        description: newVal.description || "",
        planned_delivery_date: newVal.planned_delivery_date || "",
        priority: newVal.priority || "normal",
      };
    } else {
      form.value = defaultForm();
      // Domyślna data: dziś + 14 dni
      const date = new Date();
      date.setDate(date.getDate() + 14);
      form.value.planned_delivery_date = date.toISOString().split("T")[0];

      if (props.preselectedCustomerId) {
        form.value.customer_id = props.preselectedCustomerId;
      }
    }
  },
  { immediate: true }
);

/** Inicjalizuj przy otwarciu dialogu */
watch(
  () => props.modelValue,
  async (isOpen) => {
    if (isOpen) {
      await fetchCustomers();

      if (!props.order) {
        // Nowe zamówienie: ustaw domyślne wartości
        form.value = defaultForm();
        const date = new Date();
        date.setDate(date.getDate() + 14);
        form.value.planned_delivery_date = date.toISOString().split("T")[0];

        if (props.preselectedCustomerId) {
          form.value.customer_id = props.preselectedCustomerId;
        }

        formRef.value?.resetValidation();

        // Pobierz podgląd numeru
        await fetchNextNumber();
      }
    }
  }
);
</script>
