<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="600"
    persistent
  >
    <v-card>
      <!-- Header -->
      <v-card-title class="d-flex align-center pa-4 bg-teal text-white">
        <v-icon class="mr-2">{{
          isEdit ? "mdi-account-edit" : "mdi-account-plus"
        }}</v-icon>
        {{ isEdit ? "Edytuj klienta" : "Nowy klient" }}
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" density="compact" @click="closeDialog" />
      </v-card-title>

      <v-form ref="formRef" v-model="formValid" @submit.prevent="submitForm">
        <v-card-text class="pa-6">
          <!-- Typ klienta -->
          <v-radio-group v-model="form.type" inline hide-details class="mb-4">
            <v-radio label="B2B (Firma)" value="B2B" color="blue" />
            <v-radio label="B2C (Osoba prywatna)" value="B2C" color="purple" />
          </v-radio-group>

          <v-row>
            <!-- NIP (tylko dla B2B) -->
            <v-col v-if="form.type === 'B2B'" cols="12">
              <v-text-field
                v-model="form.nip"
                label="NIP"
                placeholder="1234567890"
                variant="outlined"
                prepend-inner-icon="mdi-identifier"
                maxlength="13"
                :loading="nipLoading"
                :rules="[rules.nip]"
                :error-messages="nipError"
                hint="Wpisz NIP aby automatycznie pobrać dane firmy"
                persistent-hint
                @update:model-value="onNipInput"
                @blur="lookupNip"
              >
                <template v-slot:append-inner>
                  <v-icon v-if="nipSuccess" color="success">mdi-check-circle</v-icon>
                </template>
              </v-text-field>

              <!-- Komunikat o wyniku wyszukiwania NIP -->
              <v-alert
                v-if="nipMessage"
                :type="nipSuccess ? 'success' : 'warning'"
                variant="tonal"
                density="compact"
                class="mt-2"
              >
                {{ nipMessage }}
              </v-alert>
            </v-col>

            <!-- Nazwa -->
            <v-col cols="12">
              <v-text-field
                v-model="form.name"
                :label="form.type === 'B2B' ? 'Nazwa firmy *' : 'Imię i nazwisko *'"
                :placeholder="form.type === 'B2B' ? 'ACME Sp. z o.o.' : 'Jan Kowalski'"
                variant="outlined"
                prepend-inner-icon="mdi-domain"
                :rules="[rules.required]"
                :bg-color="nipAutoFilled.name ? 'green-lighten-5' : undefined"
              />
            </v-col>

            <!-- Email -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.email"
                label="Email"
                placeholder="kontakt@firma.pl"
                variant="outlined"
                prepend-inner-icon="mdi-email"
                :rules="[rules.email]"
              />
            </v-col>

            <!-- Telefon -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.phone"
                label="Telefon"
                placeholder="+48 123 456 789"
                variant="outlined"
                prepend-inner-icon="mdi-phone"
              />
            </v-col>

            <!-- Adres -->
            <v-col cols="12">
              <v-textarea
                v-model="form.address"
                label="Adres"
                placeholder="ul. Przykładowa 1, 00-001 Warszawa"
                variant="outlined"
                prepend-inner-icon="mdi-map-marker"
                rows="2"
                :bg-color="nipAutoFilled.address ? 'green-lighten-5' : undefined"
              />
            </v-col>

            <!-- Opiekun klienta -->
            <v-col cols="12">
              <v-autocomplete
                v-model="form.assigned_to"
                :items="guardians"
                item-title="name"
                item-value="id"
                label="Opiekun klienta"
                prepend-inner-icon="mdi-account-tie"
                variant="outlined"
                :loading="loadingGuardians"
                placeholder="Wybierz opiekuna..."
                no-data-text="Brak dostępnych opiekunów"
                clearable
              />
            </v-col>

            <!-- Status aktywności -->
            <v-col cols="12">
              <v-switch
                v-model="form.is_active"
                label="Klient aktywny"
                color="success"
                hide-details
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
            color="teal"
            variant="elevated"
            type="submit"
            :loading="saving"
            :disabled="!formValid"
            prepend-icon="mdi-content-save"
          >
            {{ isEdit ? "Zapisz zmiany" : "Dodaj klienta" }}
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed, onMounted } from "vue";
import api from "@/services/api";

// Props - kompatybilne z v-model
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  customer: {
    type: Object,
    default: null,
  },
});

// Emits
const emit = defineEmits(["update:modelValue", "saved"]);

// Form state
const formRef = ref(null);
const formValid = ref(false);
const saving = ref(false);

// Opiekunowie
const guardians = ref<any[]>([]);
const loadingGuardians = ref(false);

// NIP lookup state
const nipLoading = ref(false);
const nipSuccess = ref(false);
const nipMessage = ref("");
const nipError = ref("");
let nipLookupTimeout = null;

// Form data
const defaultForm = {
  name: "",
  type: "B2B",
  nip: "",
  email: "",
  phone: "",
  address: "",
  is_active: true,
  assigned_to: null as number | null,
};

const form = reactive({ ...defaultForm });

// Śledzenie które pola zostały auto-uzupełnione
const nipAutoFilled = reactive({
  name: false,
  address: false,
});

// Computed
const isEdit = computed(() => props.customer !== null);

// Validation rules
const rules = {
  required: (v) => !!v || "Pole wymagane",
  email: (v) =>
    !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || "Nieprawidłowy format email",
  nip: (v) => {
    if (!v) return true;
    const nip = v.replace(/[^0-9]/g, "");
    return nip.length === 0 || nip.length === 10 || "NIP musi mieć 10 cyfr";
  },
};

// Watch for dialog open
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      resetForm();
      if (props.customer) {
        // Edycja - wypełnij formularz
        Object.assign(form, {
          name: props.customer.name || "",
          type: props.customer.type || "B2B",
          nip: props.customer.nip || "",
          email: props.customer.email || "",
          phone: props.customer.phone || "",
          address: props.customer.address || "",
          is_active: props.customer.is_active ?? true,
          assigned_to: props.customer.assigned_to ?? null,
        });
      }
    }
  }
);

// Watch for type change
watch(
  () => form.type,
  () => {
    if (form.type === "B2C") {
      form.nip = "";
      nipMessage.value = "";
      nipSuccess.value = false;
      nipError.value = "";
    }
  }
);

// Methods
function resetForm() {
  Object.assign(form, { ...defaultForm, assigned_to: null });
  Object.assign(nipAutoFilled, { name: false, address: false });
  nipMessage.value = "";
  nipSuccess.value = false;
  nipError.value = "";
  formRef.value?.reset();
}

function closeDialog() {
  emit("update:modelValue", false);
}

// Pobierz listę opiekunów (TRADER + PROJECT_MANAGER)
async function fetchGuardians() {
  loadingGuardians.value = true;
  try {
    const response = await api.get("/users/for-select");
    guardians.value = response.data || [];
  } catch {
    guardians.value = [];
  } finally {
    loadingGuardians.value = false;
  }
}

onMounted(fetchGuardians);

// Formatuj NIP podczas wpisywania
function onNipInput(value) {
  // Usuń nie-cyfry
  form.nip = (value || "").replace(/[^0-9]/g, "");

  // Reset stanów
  nipSuccess.value = false;
  nipMessage.value = "";
  nipError.value = "";

  // Auto-lookup po wpisaniu 10 cyfr (z debounce)
  if (form.nip.length === 10) {
    clearTimeout(nipLookupTimeout);
    nipLookupTimeout = setTimeout(() => {
      lookupNip();
    }, 500);
  }
}

// Pobierz dane firmy po NIP
async function lookupNip() {
  const nip = form.nip.replace(/[^0-9]/g, "");

  if (nip.length !== 10) {
    return;
  }

  nipLoading.value = true;
  nipMessage.value = "";
  nipSuccess.value = false;
  nipError.value = "";

  try {
    const response = await api.get(`/nip/${nip}`);

    if (response.data.success) {
      const data = response.data.data;

      // Auto-uzupełnij pola (tylko jeśli są puste lub user pozwoli nadpisać)
      const shouldOverwrite =
        !form.name || true || confirm("Znaleziono firmę. Czy nadpisać dane?");

      if (shouldOverwrite) {
        form.name = data.name || form.name;
        form.address = data.working_address || data.address || form.address;

        // Oznacz pola jako auto-uzupełnione
        nipAutoFilled.name = !!data.name;
        nipAutoFilled.address = !!(data.working_address || data.address);

        // Usunięcie podświetlenia po 3 sekundach
        setTimeout(() => {
          nipAutoFilled.name = false;
          nipAutoFilled.address = false;
        }, 3000);
      }

      nipSuccess.value = true;
      nipMessage.value = `✓ Znaleziono: ${data.name}`;

      // Status VAT
      if (data.status_vat) {
        nipMessage.value += ` (VAT: ${data.status_vat})`;
      }
    }
  } catch (error) {
    console.error("NIP lookup error:", error);

    if (error.response?.status === 404) {
      nipMessage.value = "Nie znaleziono firmy o podanym NIP";
    } else if (error.response?.status === 422) {
      nipError.value = error.response.data.message || "Nieprawidłowy NIP";
    } else {
      nipMessage.value = "Błąd podczas sprawdzania NIP. Spróbuj ponownie.";
    }
  } finally {
    nipLoading.value = false;
  }
}

// Zapisz formularz
async function submitForm() {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  saving.value = true;

  try {
    const payload = {
      name: form.name.trim(),
      type: form.type,
      nip: form.type === "B2B" ? form.nip || null : null,
      email: form.email || null,
      phone: form.phone || null,
      address: form.address || null,
      is_active: form.is_active,
      assigned_to: form.assigned_to || null,
    };

    let response;
    if (isEdit.value) {
      response = await api.put(`/customers/${props.customer.id}`, payload);
    } else {
      response = await api.post("/customers", payload);
    }

    emit("saved", response.data);
    closeDialog();
  } catch (error) {
    console.error("Save customer error:", error);

    if (error.response?.data?.errors) {
      const serverErrors = error.response.data.errors;
      const messages = Object.values(serverErrors).flat().join("\n");
      alert("Błąd walidacji:\n" + messages);
    } else {
      alert(error.response?.data?.message || "Błąd podczas zapisywania klienta");
    }
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
/* Animacja dla podświetlenia auto-uzupełnionych pól jest obsługiwana przez Vuetify bg-color */
</style>
