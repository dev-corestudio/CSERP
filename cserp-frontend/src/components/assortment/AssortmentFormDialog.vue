<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="600"
    persistent
  >
    <v-card>
      <v-card-title class="bg-primary text-white">
        <v-icon start color="white">
          {{ isEdit ? "mdi-pencil" : "mdi-plus" }}
        </v-icon>
        {{ isEdit ? "Edytuj pozycję" : "Nowa pozycja asortymentu" }}
      </v-card-title>

      <v-card-text class="pt-6">
        <v-form ref="formRef" v-model="valid" @submit.prevent="handleSubmit">
          <v-row>
            <!-- Typ -->
            <v-col cols="12" md="6">
              <v-select
                v-model="form.type"
                label="Typ *"
                :items="metadataStore.assortmentTypes"
                item-title="label"
                item-value="value"
                variant="outlined"
                :rules="[(v) => !!v || 'Typ jest wymagany']"
                prepend-inner-icon="mdi-shape"
                @update:model-value="loadCategoriesForType"
              />
            </v-col>

            <!-- Kategoria (Combobox - pozwala dodać nową) -->
            <v-col cols="12" md="6">
              <v-combobox
                v-model="form.category"
                label="Kategoria *"
                :items="assortmentStore.categories"
                variant="outlined"
                :rules="[(v) => !!v || 'Kategoria jest wymagana']"
                prepend-inner-icon="mdi-tag"
                placeholder="Wybierz lub wpisz nową"
              />
            </v-col>

            <!-- Nazwa -->
            <v-col cols="12">
              <v-text-field
                v-model="form.name"
                label="Nazwa *"
                variant="outlined"
                :rules="[(v) => !!v || 'Nazwa jest wymagana']"
                prepend-inner-icon="mdi-format-title"
              />
            </v-col>

            <!-- Jednostka -->
            <v-col cols="12" md="6">
              <v-select
                v-model="form.unit"
                label="Jednostka miary *"
                :items="metadataStore.units"
                item-title="label"
                item-value="value"
                variant="outlined"
                :rules="[(v) => !!v || 'Jednostka jest wymagana']"
                prepend-inner-icon="mdi-ruler"
              />
            </v-col>

            <!-- Cena -->
            <v-col cols="12" md="6">
              <v-text-field
                v-model.number="form.default_price"
                label="Cena domyślna (netto) *"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                :rules="[(v) => v >= 0 || 'Cena nie może być ujemna']"
                prepend-inner-icon="mdi-cash"
                suffix="PLN"
              />
            </v-col>

            <!-- Opis -->
            <v-col cols="12">
              <v-textarea
                v-model="form.description"
                label="Opis / Uwagi"
                variant="outlined"
                rows="3"
                prepend-inner-icon="mdi-text"
              />
            </v-col>

            <!-- Status (Tylko przy edycji) -->
            <v-col cols="12" v-if="isEdit">
              <v-switch
                v-model="form.is_active"
                label="Aktywny"
                color="success"
                hide-details
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="close">Anuluj</v-btn>
        <v-btn color="primary" variant="elevated" :loading="saving" @click="handleSubmit">
          <v-icon start>mdi-check</v-icon>
          Zapisz
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useAssortmentStore } from "@/stores/assortment";
import { useMetadataStore } from "@/stores/metadata";

const props = defineProps({
  modelValue: Boolean,
  item: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const assortmentStore = useAssortmentStore();
const metadataStore = useMetadataStore();

const formRef = ref(null);
const valid = ref(false);
const saving = ref(false);

const isEdit = computed(() => !!props.item);

const defaultForm = {
  type: "MATERIAL", // FIX: changed from "material" to "MATERIAL"
  category: null,
  name: "",
  unit: "SZT", // FIX: changed from "szt" to "SZT" (likely matches Enum AssortmentUnit better)
  default_price: 0,
  description: "",
  is_active: true,
};

const form = ref({ ...defaultForm });

// Wypełnianie formularza przy otwarciu
watch(
  () => props.modelValue,
  async (val) => {
    if (val) {
      if (props.item) {
        form.value = { ...props.item };
      } else {
        form.value = { ...defaultForm };
      }
      // Pobierz kategorie dla aktualnego typu
      await loadCategoriesForType(form.value.type);
    }
  }
);

const loadCategoriesForType = async (type) => {
  if (type) {
    await assortmentStore.fetchCategories(type);
  }
};

const handleSubmit = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  saving.value = true;
  try {
    if (isEdit.value) {
      await assortmentStore.updateItem(props.item.id, form.value);
    } else {
      await assortmentStore.createItem(form.value);
    }
    emit("saved");
    close();
  } catch (error) {
    console.error("Błąd zapisu:", error);
    alert("Wystąpił błąd podczas zapisywania.");
  } finally {
    saving.value = false;
  }
};

const close = () => {
  emit("update:modelValue", false);
  formRef.value?.resetValidation();
};

onMounted(() => {
  // Upewnij się, że metadata są załadowane (np. jednostki)
  if (!metadataStore.loaded) {
    metadataStore.fetchMetadata();
  }
});
</script>
