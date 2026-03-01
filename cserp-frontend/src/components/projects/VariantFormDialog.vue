<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="660"
    persistent
  >
    <v-card>
      <!-- ── Nagłówek ── -->
      <v-card-title :class="headerBgClass" class="text-white d-flex align-center pa-4">
        <v-icon start color="white">{{ headerIcon }}</v-icon>
        {{ dialogTitle }}
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          color="white"
          size="small"
          @click="handleCancel"
        />
      </v-card-title>

      <v-card-text class="pt-5">
        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <!-- ═══════════════════════════════════════════════════════════════
               TRYB: NOWA GRUPA (mode='group')
               Tworzony przez POST /projects/{id}/variants
               Backend nadaje kolejną literę (A, B, C...)
          ════════════════════════════════════════════════════════════════ -->
          <template v-if="isGroupMode">
            <v-alert
              type="info"
              variant="tonal"
              density="compact"
              class="mb-4"
              icon="mdi-folder-plus"
            >
              Grupa zostanie oznaczona kolejną literą (np.
              <strong>{{ nextGroupLetter }}</strong
              >). Możesz potem dodać do niej dowolne warianty.
            </v-alert>

            <v-text-field
              v-model="form.name"
              label="Nazwa grupy *"
              variant="outlined"
              prepend-inner-icon="mdi-folder"
              :rules="[rules.required]"
              class="mb-3"
            />

            <v-textarea
              v-model="form.description"
              label="Opis grupy (opcjonalnie)"
              variant="outlined"
              rows="2"
              prepend-inner-icon="mdi-text"
              placeholder="Krótki opis linii produktowej..."
            />
          </template>

          <!-- ═══════════════════════════════════════════════════════════════
               TRYB: NOWY WARIANT (mode='variant')
               Tworzony przez POST /projects/{id}/variants/{parentId}/children
               Kontekst: pod jakim rodzicem powstaje
          ════════════════════════════════════════════════════════════════ -->
          <template v-else-if="isVariantMode">
            <!-- Info o rodzicu -->
            <v-alert
              variant="tonal"
              :color="parent?.is_group ? 'indigo' : 'teal'"
              density="compact"
              class="mb-4"
              :icon="parent?.is_group ? 'mdi-folder' : 'mdi-source-branch'"
            >
              Wariant zostanie dodany do
              <strong
                >{{ parent?.is_group ? "grupy" : "wariantu" }}
                {{ parent?.variant_number }} – {{ parent?.name }}</strong
              >
              jako
              <strong>{{ nextVariantNumber }}</strong
              >.
            </v-alert>

            <!-- Typ wariantu -->
            <div class="mb-4">
              <div
                class="text-caption text-medium-emphasis mb-2 font-weight-bold text-uppercase"
              >
                Typ wariantu
              </div>
              <v-btn-toggle
                v-model="form.type"
                mandatory
                divided
                variant="outlined"
                color="primary"
                class="w-100"
                :disabled="isEdit"
              >
                <v-btn value="SERIAL" class="flex-grow-1 w-50">
                  <v-icon start>mdi-factory</v-icon>
                  Produkcja seryjna
                </v-btn>
                <v-btn value="PROTOTYPE" class="flex-grow-1 w-50">
                  <v-icon start>mdi-flask-outline</v-icon>
                  Prototyp
                </v-btn>
              </v-btn-toggle>
            </div>

            <v-row>
              <!-- Nazwa wariantu -->
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="form.name"
                  label="Nazwa wariantu *"
                  variant="outlined"
                  prepend-inner-icon="mdi-tag"
                  :rules="[rules.required]"
                />
              </v-col>

              <!-- Ilość -->
              <v-col cols="12" md="4">
                <v-text-field
                  v-model.number="form.quantity"
                  label="Ilość *"
                  type="number"
                  min="1"
                  variant="outlined"
                  prepend-inner-icon="mdi-counter"
                  suffix="szt"
                  :rules="[rules.minQty]"
                />
              </v-col>

              <!-- Opis -->
              <v-col cols="12">
                <v-textarea
                  v-model="form.description"
                  label="Opis / Specyfikacja (opcjonalnie)"
                  variant="outlined"
                  rows="2"
                  prepend-inner-icon="mdi-text"
                  placeholder="Wymiary, kolor, uwagi techniczne..."
                />
              </v-col>
            </v-row>
          </template>

          <!-- ═══════════════════════════════════════════════════════════════
               TRYB: EDYCJA GRUPY (mode='edit-group')
          ════════════════════════════════════════════════════════════════ -->
          <template v-else-if="isEditGroupMode">
            <v-text-field
              v-model="form.name"
              label="Nazwa grupy *"
              variant="outlined"
              prepend-inner-icon="mdi-folder"
              :rules="[rules.required]"
              class="mb-3"
            />
            <v-textarea
              v-model="form.description"
              label="Opis grupy (opcjonalnie)"
              variant="outlined"
              rows="2"
              prepend-inner-icon="mdi-text"
            />
          </template>

          <!-- ═══════════════════════════════════════════════════════════════
               TRYB: EDYCJA WARIANTU (mode='edit-variant')
          ════════════════════════════════════════════════════════════════ -->
          <template v-else-if="isEditVariantMode">
            <!-- Typ (tylko do odczytu — nie można konwertować) -->
            <v-chip
              :color="form.type === 'PROTOTYPE' ? 'orange' : 'blue'"
              variant="tonal"
              size="small"
              class="mb-4"
            >
              <v-icon start size="small">
                {{ form.type === "PROTOTYPE" ? "mdi-flask-outline" : "mdi-factory" }}
              </v-icon>
              {{ form.type === "PROTOTYPE" ? "Prototyp" : "Produkcja seryjna" }}
              <span class="ml-1 text-caption">(nie można zmieniać typu)</span>
            </v-chip>

            <v-row>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="form.name"
                  label="Nazwa wariantu *"
                  variant="outlined"
                  prepend-inner-icon="mdi-tag"
                  :rules="[rules.required]"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model.number="form.quantity"
                  label="Ilość *"
                  type="number"
                  min="1"
                  variant="outlined"
                  prepend-inner-icon="mdi-counter"
                  suffix="szt"
                  :rules="[rules.minQty]"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="form.description"
                  label="Opis / Specyfikacja (opcjonalnie)"
                  variant="outlined"
                  rows="2"
                  prepend-inner-icon="mdi-text"
                />
              </v-col>
              <!-- Status edycji -->
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.status"
                  :items="metadataStore.variantStatuses"
                  item-title="label"
                  item-value="value"
                  label="Status"
                  variant="outlined"
                  prepend-inner-icon="mdi-list-status"
                />
              </v-col>
            </v-row>
          </template>
        </v-form>
      </v-card-text>

      <!-- ── Akcje ── -->
      <v-card-actions class="pa-4 bg-grey-lighten-5">
        <v-spacer />
        <v-btn
          variant="text"
          color="grey-darken-1"
          :disabled="loading"
          @click="handleCancel"
        >
          Anuluj
        </v-btn>
        <v-btn
          :color="submitColor"
          variant="elevated"
          :loading="loading"
          @click="handleSubmit"
        >
          <v-icon start>mdi-check</v-icon>
          {{ submitLabel }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useVariantsStore } from "@/stores/variants";
import { useMetadataStore } from "@/stores/metadata";

// ─── Props / Emits ────────────────────────────────────────────────────────────

const props = defineProps<{
  modelValue: boolean;

  /**
   * Tryb dialogu:
   *   'group'        — tworzenie nowej grupy top-level
   *   'variant'      — tworzenie wariantu jako dziecko `parent`
   *   'edit-group'   — edycja istniejącej grupy
   *   'edit-variant' — edycja istniejącego wariantu
   */
  mode: "group" | "variant" | "edit-group" | "edit-variant";

  /** ID projektu (wymagane przy tworzeniu) */
  projectId?: number;

  /**
   * Rodzic, do którego dodajemy wariant (wymagane gdy mode='variant').
   * Może być grupą (is_group=true) lub wariantem.
   */
  parent?: any;

  /**
   * Obiekt do edycji (wymagane gdy mode='edit-group' lub 'edit-variant').
   */
  item?: any;

  /**
   * Wszystkie warianty projektu — do obliczenia następnego numeru.
   */
  existingVariants?: any[];
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
}>();

// ─── Instancje ────────────────────────────────────────────────────────────────

const variantsStore = useVariantsStore();
const metadataStore = useMetadataStore();

// ─── Stan ─────────────────────────────────────────────────────────────────────

const formRef = ref<any>(null);
const loading = ref(false);

const defaultForm = () => ({
  name: "",
  description: "",
  quantity: 1,
  type: "SERIAL" as "SERIAL" | "PROTOTYPE",
  status: "PENDING",
});

const form = ref(defaultForm());

// ─── Walidacja ────────────────────────────────────────────────────────────────

const rules = {
  required: (v: string) => !!v || "Pole jest wymagane",
  minQty: (v: number) => v >= 1 || "Ilość musi wynosić co najmniej 1",
};

// ─── Computed — tryb ─────────────────────────────────────────────────────────

const isGroupMode = computed(() => props.mode === "group");
const isVariantMode = computed(() => props.mode === "variant");
const isEditGroupMode = computed(() => props.mode === "edit-group");
const isEditVariantMode = computed(() => props.mode === "edit-variant");
const isEdit = computed(
  () => props.mode === "edit-group" || props.mode === "edit-variant"
);

// ─── Computed — UI ───────────────────────────────────────────────────────────

const headerBgClass = computed(() => {
  if (isGroupMode.value) return "bg-indigo";
  if (isVariantMode.value) return "bg-teal";
  if (isEditGroupMode.value) return "bg-indigo-darken-2";
  return "bg-primary";
});

const headerIcon = computed(() => {
  if (isGroupMode.value) return "mdi-folder-plus";
  if (isVariantMode.value) return "mdi-plus";
  if (isEditGroupMode.value) return "mdi-folder-edit";
  return "mdi-pencil";
});

const dialogTitle = computed(() => {
  if (isGroupMode.value) return "Nowa grupa wariantów";
  if (isVariantMode.value)
    return `Nowy wariant w grupie ${props.parent?.variant_number ?? ""}`;
  if (isEditGroupMode.value) return `Edytuj grupę ${props.item?.variant_number ?? ""}`;
  return `Edytuj wariant ${props.item?.variant_number ?? ""}`;
});

const submitColor = computed(() => {
  if (isGroupMode.value || isEditGroupMode.value) return "indigo";
  if (isVariantMode.value) return "teal";
  return "primary";
});

const submitLabel = computed(() => {
  if (isEdit.value) return "Zapisz zmiany";
  if (isGroupMode.value) return "Utwórz grupę";
  return "Dodaj wariant";
});

// ─── Computed — numeracja ─────────────────────────────────────────────────────

const nextGroupLetter = computed(() => {
  const existing = (props.existingVariants ?? [])
    .filter((v) => !v.parent_variant_id && (v.is_group || v.quantity === 0))
    .map((v) => v.variant_number);
  let letter = "A";
  while (existing.includes(letter)) {
    letter = String.fromCharCode(letter.charCodeAt(0) + 1);
  }
  return letter;
});

const nextVariantNumber = computed(() => {
  if (!props.parent) return "?";
  const parentNum: string = props.parent.variant_number;
  const isDeepParent = /\d/.test(parentNum);
  const separator = isDeepParent ? "_" : "";
  const prefix = parentNum + separator;

  const siblings = (props.existingVariants ?? []).filter(
    (v) => v.parent_variant_id === props.parent.id
  );

  if (siblings.length === 0) return prefix + "1";

  const maxNum = siblings.reduce((max, s) => {
    if (s.variant_number?.startsWith(prefix)) {
      const suffix = s.variant_number.slice(prefix.length);
      if (/^\d+$/.test(suffix)) return Math.max(max, parseInt(suffix));
    }
    return max;
  }, 0);

  return prefix + (maxNum + 1);
});

// ─── Inicjalizacja formularza ─────────────────────────────────────────────────

watch(
  () => props.modelValue,
  (isOpen) => {
    if (!isOpen) return;

    if (isEdit.value && props.item) {
      form.value = {
        name: props.item.name ?? "",
        description: props.item.description ?? "",
        quantity: props.item.quantity ?? 1,
        type: props.item.type ?? "SERIAL",
        status: props.item.status ?? "PENDING",
      };
    } else {
      form.value = defaultForm();
    }
  }
);

// ─── Submit ───────────────────────────────────────────────────────────────────

const handleSubmit = async () => {
  const { valid } = await formRef.value?.validate();
  if (!valid) return;

  loading.value = true;
  try {
    if (isGroupMode.value) {
      await variantsStore.createGroup(props.projectId!, {
        name: form.value.name,
        description: form.value.description || undefined,
      });
    } else if (isVariantMode.value) {
      await variantsStore.createChildVariant(props.projectId!, props.parent!.id, {
        name: form.value.name,
        quantity: form.value.quantity,
        type: form.value.type,
        description: form.value.description || undefined,
      });
    } else if (isEditGroupMode.value || isEditVariantMode.value) {
      const payload: Record<string, any> = {
        name: form.value.name,
        description: form.value.description || null,
      };
      if (isEditVariantMode.value) {
        payload.quantity = form.value.quantity;
        payload.status = form.value.status;
      }
      await variantsStore.updateVariant(props.item!.id, payload);
    }

    emit("saved");
    handleCancel();
  } catch (error: any) {
    console.error("Save error:", error);
    alert("Błąd zapisu: " + (error.response?.data?.message || error.message));
  } finally {
    loading.value = false;
  }
};

const handleCancel = () => {
  formRef.value?.resetValidation();
  emit("update:modelValue", false);
};
</script>

<style scoped>
.w-100 {
  width: 100%;
}
</style>
