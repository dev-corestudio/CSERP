<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="closeDialog"
    max-width="600"
    persistent
  >
    <v-card>
      <v-toolbar color="primary" density="comfortable">
        <v-icon class="ml-4">{{
          isEdit ? "mdi-account-edit" : "mdi-account-plus"
        }}</v-icon>
        <v-toolbar-title>
          {{ isEdit ? "Edytuj Użytkownika" : "Nowy Użytkownik" }}
        </v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" @click="closeDialog"></v-btn>
      </v-toolbar>

      <v-card-text class="pa-4">
        <!-- Globalny Alert Błędu -->
        <v-alert
          v-if="errorMessage"
          type="error"
          variant="tonal"
          closable
          class="mb-4"
          @click:close="errorMessage = null"
        >
          {{ errorMessage }}
        </v-alert>

        <v-form ref="formRef" v-model="valid" @submit.prevent="handleSubmit">
          <v-row dense>
            <!-- Imię i Nazwisko -->
            <v-col cols="12">
              <v-text-field
                v-model="form.name"
                label="Imię i Nazwisko *"
                variant="outlined"
                prepend-inner-icon="mdi-account"
                :rules="[rules.required]"
                :error-messages="errors.name"
              ></v-text-field>
            </v-col>

            <!-- Email -->
            <v-col cols="12">
              <v-text-field
                v-model="form.email"
                label="Adres Email *"
                variant="outlined"
                prepend-inner-icon="mdi-email"
                type="email"
                :rules="[rules.required, rules.email]"
                :error-messages="errors.email"
              ></v-text-field>
            </v-col>

            <!-- Rola -->
            <v-col cols="12" md="6">
              <v-select
                v-model="form.role"
                :items="metadataStore.userRoles"
                item-title="label"
                item-value="value"
                label="Rola w systemie *"
                variant="outlined"
                prepend-inner-icon="mdi-badge-account"
                :rules="[rules.required]"
                :error-messages="errors.role"
              ></v-select>
            </v-col>

            <!-- Status -->
            <v-col cols="12" md="6" class="d-flex align-center">
              <v-switch
                v-model="form.is_active"
                label="Konto aktywne"
                color="success"
                hide-details
                class="ml-2"
              ></v-switch>
            </v-col>

            <v-divider class="my-3 w-100"></v-divider>
            <div class="text-subtitle-2 text-medium-emphasis mb-2 w-100">
              Bezpieczeństwo
            </div>

            <!-- Hasło -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.password"
                label="Hasło"
                variant="outlined"
                prepend-inner-icon="mdi-lock"
                :type="showPassword ? 'text' : 'password'"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append-inner="showPassword = !showPassword"
                :rules="isEdit ? [] : [rules.required, rules.min8]"
                :hint="isEdit ? 'Pozostaw puste, aby nie zmieniać' : 'Min. 8 znaków'"
                persistent-hint
                :error-messages="errors.password"
              ></v-text-field>
            </v-col>

            <!-- PIN -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.pin_code"
                label="Kod PIN (RCP)"
                variant="outlined"
                prepend-inner-icon="mdi-dialpad"
                type="password"
                maxlength="4"
                :rules="[rules.pin]"
                hint="4 cyfry (opcjonalne, dla produkcji)"
                persistent-hint
                :error-messages="errors.pin_code"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4 bg-grey-lighten-5">
        <v-spacer></v-spacer>
        <v-btn
          variant="text"
          color="grey-darken-1"
          @click="closeDialog"
          :disabled="loading"
        >
          Anuluj
        </v-btn>
        <v-btn
          color="primary"
          variant="elevated"
          @click="handleSubmit"
          :loading="loading"
          :disabled="!valid"
          class="px-6"
        >
          {{ isEdit ? "Zapisz zmiany" : "Utwórz" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useUsersStore } from "@/stores/users";
import { useMetadataStore } from "@/stores/metadata";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const usersStore = useUsersStore();
const metadataStore = useMetadataStore();

const formRef = ref(null);
const valid = ref(false);
const loading = ref(false);
const showPassword = ref(false);

// Stan błędów z serwera
const errors = ref<Record<string, string[]>>({});
const errorMessage = ref<string | null>(null);

const defaultForm = {
  name: "",
  email: "",
  password: "",
  role: null,
  pin_code: "",
  is_active: true,
};

const form = ref({ ...defaultForm });

const isEdit = computed(() => !!props.user);

const rules = {
  required: (v: any) => !!v || "Pole jest wymagane",
  email: (v: string) => /.+@.+\..+/.test(v) || "Nieprawidłowy format email",
  min8: (v: string) => !v || v.length >= 8 || "Min. 8 znaków",
  pin: (v: string) => !v || /^\d{4}$/.test(v) || "PIN musi składać się z 4 cyfr",
};

// Obserwuj otwarcie dialogu
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      // Reset błędów
      errors.value = {};
      errorMessage.value = null;

      if (props.user) {
        // Edycja
        form.value = {
          name: props.user.name,
          email: props.user.email,
          password: "",
          role: props.user.role,
          pin_code: "",
          is_active: props.user.is_active,
        };
      } else {
        // Nowy
        form.value = { ...defaultForm };
      }
      // Reset walidacji
      if (formRef.value) (formRef.value as any).resetValidation();
    }
  }
);

const closeDialog = () => {
  emit("update:modelValue", false);
};

const handleSubmit = async () => {
  // Walidacja frontendowa
  const { valid } = await (formRef.value as any).validate();
  if (!valid) return;

  loading.value = true;
  errors.value = {}; // Wyczyść stare błędy
  errorMessage.value = null;

  try {
    const payload: any = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
      is_active: form.value.is_active,
    };

    if (form.value.password) {
      payload.password = form.value.password;
    }

    // Wyślij PIN tylko jeśli został wpisany (pusty = brak zmian lub usunięcie w zależności od logiki API, tutaj zakładamy wpisanie nowego)
    if (form.value.pin_code) {
      payload.pin_code = form.value.pin_code;
    }

    if (isEdit.value) {
      await usersStore.updateUser(props.user.id, payload);
    } else {
      await usersStore.createUser(payload);
    }

    emit("saved");
    closeDialog();
  } catch (error: any) {
    console.error("Błąd zapisu:", error);

    // Obsługa błędów walidacji z Laravela (status 422)
    if (error.response && error.response.status === 422) {
      // Przypisz błędy do pól (np. pin_code: ["Ten kod PIN jest już zajęty"])
      errors.value = error.response.data.errors;
      errorMessage.value = "Formularz zawiera błędy. Sprawdź poniższe pola.";
    } else {
      // Inne błędy
      errorMessage.value =
        error.response?.data?.message || "Wystąpił nieoczekiwany błąd podczas zapisu.";
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (!metadataStore.loaded) {
    metadataStore.fetchMetadata();
  }
});
</script>
