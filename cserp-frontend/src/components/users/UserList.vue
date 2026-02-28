<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="600"
    persistent
  >
    <v-card>
      <v-card-title class="bg-primary text-white">
        <v-icon start>{{ isEdit ? "mdi-account-edit" : "mdi-account-plus" }}</v-icon>
        {{ isEdit ? "Edytuj Użytkownika" : "Nowy Użytkownik" }}
      </v-card-title>

      <v-form ref="formRef" @submit.prevent="handleSubmit">
        <v-card-text class="pt-6">
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="form.name"
                label="Imię i Nazwisko *"
                variant="outlined"
                prepend-inner-icon="mdi-account"
                :rules="[(v) => !!v || 'Wymagane']"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="form.email"
                label="Adres Email *"
                type="email"
                variant="outlined"
                prepend-inner-icon="mdi-email"
                :rules="[
                  (v) => !!v || 'Wymagane',
                  (v) => /.+@.+\..+/.test(v) || 'Nieprawidłowy email',
                ]"
              />
            </v-col>

            <v-col cols="12">
              <v-select
                v-model="form.role"
                :items="metadataStore.userRoles"
                item-title="label"
                item-value="value"
                label="Rola w systemie *"
                variant="outlined"
                prepend-inner-icon="mdi-badge-account"
                :rules="[(v) => !!v || 'Wymagane']"
              />
            </v-col>

            <v-divider class="my-2"></v-divider>
            <v-col cols="12">
              <div class="text-subtitle-2 text-medium-emphasis mb-2">Bezpieczeństwo</div>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.password"
                :label="isEdit ? 'Nowe Hasło (opcjonalnie)' : 'Hasło *'"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                prepend-inner-icon="mdi-lock"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append-inner="showPassword = !showPassword"
                :rules="passwordRules"
                hint="Min. 8 znaków"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.pin_code"
                label="Kod PIN (do RCP)"
                type="text"
                maxlength="4"
                variant="outlined"
                prepend-inner-icon="mdi-dialpad"
                :rules="[(v) => !v || /^\d{4}$/.test(v) || 'PIN musi mieć 4 cyfry']"
                hint="Tylko 4 cyfry"
              />
            </v-col>

            <v-col cols="12" v-if="isEdit">
              <v-switch
                v-model="form.is_active"
                color="success"
                label="Konto aktywne"
                hide-details
              />
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions class="pa-4 bg-grey-lighten-5">
          <v-spacer />
          <v-btn variant="text" @click="close">Anuluj</v-btn>
          <v-btn color="primary" variant="elevated" type="submit" :loading="loading">
            Zapisz
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useUsersStore } from "@/stores/users";
import { useMetadataStore } from "@/stores/metadata";

const props = defineProps({
  modelValue: Boolean,
  user: Object,
});

const emit = defineEmits(["update:modelValue", "saved"]);

const usersStore = useUsersStore();
const metadataStore = useMetadataStore();

const formRef = ref(null);
const loading = ref(false);
const showPassword = ref(false);

const isEdit = computed(() => !!props.user);

const defaultForm = {
  name: "",
  email: "",
  role: null,
  password: "",
  pin_code: "",
  is_active: true,
};

const form = ref({ ...defaultForm });

const passwordRules = computed(() => {
  const rules = [];
  if (!isEdit.value) {
    rules.push((v) => !!v || "Hasło jest wymagane");
  }
  rules.push((v) => !v || v.length >= 8 || "Min. 8 znaków");
  return rules;
});

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      if (props.user) {
        form.value = {
          ...props.user,
          password: "", // Nie wypełniamy hasła przy edycji
          pin_code: "", // Nie wypełniamy PINu (jest hashowany) - ewentualnie placeholder
        };
      } else {
        form.value = { ...defaultForm };
      }
    }
  }
);

const handleSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  loading.value = true;
  try {
    const payload = { ...form.value };

    // Usuń puste hasło/pin przy edycji, żeby nie nadpisać nullem/pustym stringiem
    if (isEdit.value) {
      if (!payload.password) delete payload.password;
      if (!payload.pin_code && payload.pin_code !== "") delete payload.pin_code; // delete only if undefined
      // Backend handles nullable/empty logic usually
    }

    if (isEdit.value) {
      await usersStore.updateUser(props.user.id, payload);
    } else {
      await usersStore.createUser(payload);
    }
    emit("saved");
    close();
  } catch (e: any) {
    alert(e.response?.data?.message || "Błąd zapisu");
    if (e.response?.data?.errors) {
      console.log(e.response.data.errors);
    }
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit("update:modelValue", false);
  formRef.value?.resetValidation();
};
</script>
