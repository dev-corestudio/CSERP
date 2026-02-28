<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="640"
    persistent
  >
    <v-card>
      <!-- ===== NAGŁÓWEK ===== -->
      <v-card-title
        :class="isGroup ? 'bg-indigo text-white' : 'bg-deep-purple text-white'"
        class="d-flex align-center pa-4"
      >
        <v-icon start color="white">
          {{ isGroup ? "mdi-folder-multiple" : "mdi-content-copy" }}
        </v-icon>
        {{ isGroup ? "Duplikuj grupę" : "Duplikuj wariant" }}
        {{ sourceVariant?.variant_number }}
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          color="white"
          size="small"
          @click="handleCancel"
        />
      </v-card-title>

      <!-- ================================================================
           ŚCIEŻKA A — GRUPA (is_group === true)
           Prosty dialog: nazwa + checkbox copy_children
      ================================================================= -->
      <template v-if="isGroup">
        <v-card-text class="pa-6">
          <v-alert type="info" variant="tonal" density="compact" class="mb-5">
            <strong>Nowa grupa</strong> otrzyma kolejną wolną literę ({{
              nextGroupLetter
            }}). Możesz skopiować wszystkie warianty wewnątrz grupy.
          </v-alert>

          <!-- Nazwa grupy -->
          <v-form ref="groupFormRef">
            <v-text-field
              v-model="groupForm.name"
              label="Nazwa nowej grupy *"
              variant="outlined"
              prepend-inner-icon="mdi-folder-outline"
              :rules="[(v) => !!v || 'Nazwa jest wymagana']"
              class="mb-3"
              autofocus
            />

            <v-textarea
              v-model="groupForm.description"
              label="Opis"
              variant="outlined"
              rows="2"
              prepend-inner-icon="mdi-text"
            />

            <!-- Opcja kopiowania dzieci -->
            <v-card
              :class="[
                'copy-option-card pa-4 cursor-pointer mt-3',
                groupForm.copy_children ? 'border-indigo bg-indigo-lighten' : '',
              ]"
              flat
              @click="groupForm.copy_children = !groupForm.copy_children"
            >
              <div class="d-flex align-center">
                <div class="flex-grow-1">
                  <div class="d-flex align-center">
                    <v-icon color="indigo" class="mr-2">mdi-content-copy</v-icon>
                    <span class="text-subtitle-2 font-weight-bold">Kopiuj warianty</span>
                    <v-chip
                      v-if="childrenCount > 0"
                      size="x-small"
                      color="indigo"
                      class="ml-2"
                    >
                      {{ childrenCount }} wariantów
                    </v-chip>
                    <v-chip v-else size="x-small" color="grey" class="ml-2">
                      Pusta grupa
                    </v-chip>
                  </div>
                  <div class="text-caption text-medium-emphasis mt-1">
                    <template v-if="childrenCount > 0">
                      Kopiuje całe drzewo wariantów z nowymi numerami ({{
                        sourceVariant?.variant_number
                      }}1 → {{ nextGroupLetter }}1, itd.)
                    </template>
                    <template v-else> Brak wariantów do skopiowania </template>
                  </div>
                </div>
                <v-icon
                  :color="groupForm.copy_children ? 'indigo' : 'grey-lighten-2'"
                  size="28"
                >
                  {{
                    groupForm.copy_children ? "mdi-check-circle" : "mdi-circle-outline"
                  }}
                </v-icon>
              </div>
            </v-card>
          </v-form>

          <!-- Podsumowanie -->
          <v-card variant="tonal" color="indigo" class="mt-4 pa-3">
            <div class="text-caption font-weight-bold mb-2 text-uppercase">
              Podsumowanie:
            </div>
            <div class="d-flex flex-wrap gap-2">
              <v-chip size="small" color="indigo" variant="flat">
                <v-icon start size="x-small">mdi-folder-outline</v-icon>
                {{ sourceVariant?.variant_number }} → {{ nextGroupLetter }}
              </v-chip>
              <v-chip
                v-if="groupForm.copy_children && childrenCount > 0"
                size="small"
                color="success"
                variant="flat"
              >
                <v-icon start size="x-small">mdi-check</v-icon>
                {{ childrenCount }} wariantów
              </v-chip>
              <v-chip v-else size="small" color="grey" variant="flat">
                <v-icon start size="x-small">mdi-folder-open-outline</v-icon>
                Pusta
              </v-chip>
            </div>
          </v-card>
        </v-card-text>

        <v-divider />
        <v-card-actions class="pa-4 bg-grey-lighten-5">
          <v-spacer />
          <v-btn variant="text" color="grey-darken-1" @click="handleCancel">Anuluj</v-btn>
          <v-btn
            color="indigo"
            variant="elevated"
            :loading="saving"
            @click="handleGroupSubmit"
          >
            <v-icon start>mdi-content-copy</v-icon>
            Duplikuj grupę
          </v-btn>
        </v-card-actions>
      </template>

      <!-- ================================================================
           ŚCIEŻKA B — WARIANT (is_group === false)
           3-krokowy stepper (relacja → co kopiować → szczegóły)
      ================================================================= -->
      <template v-else>
        <v-stepper
          v-model="step"
          :items="['Relacja', 'Co kopiować', 'Szczegóły']"
          flat
          alt-labels
          class="elevation-0"
          hide-actions
        >
          <!-- KROK 1 — Relacja -->
          <template v-slot:item.1>
            <v-card flat class="pa-2">
              <v-card-text>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Wybierz jak nowy wariant ma się odnosić do
                  <strong
                    >{{ sourceVariant?.variant_number }} –
                    {{ sourceVariant?.name }}</strong
                  >:
                </p>

                <v-row>
                  <!-- Sibling -->
                  <v-col cols="6">
                    <v-card
                      :class="[
                        'relation-card pa-4 text-center cursor-pointer',
                        variantForm.relation === 'sibling'
                          ? 'border-primary border-thick bg-primary-lighten'
                          : 'border-dashed',
                      ]"
                      variant="outlined"
                      @click="variantForm.relation = 'sibling'"
                    >
                      <v-icon
                        size="48"
                        :color="variantForm.relation === 'sibling' ? 'primary' : 'grey'"
                      >
                        mdi-family-tree
                      </v-icon>
                      <div class="mt-2 text-subtitle-1 font-weight-bold">
                        Nowy wariant
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        Nowa litera (np. {{ nextSiblingNumber }})
                      </div>
                      <div class="mt-3">
                        <v-chip size="small" color="grey" variant="tonal">
                          {{ sourceVariant?.variant_number }}
                        </v-chip>
                        <span class="text-caption mx-1 text-medium-emphasis">→</span>
                        <v-chip
                          size="small"
                          :color="variantForm.relation === 'sibling' ? 'primary' : 'grey'"
                          variant="flat"
                        >
                          {{ nextSiblingNumber }}
                        </v-chip>
                      </div>
                    </v-card>
                  </v-col>

                  <!-- Child -->
                  <v-col cols="6">
                    <v-card
                      :class="[
                        'relation-card pa-4 text-center cursor-pointer',
                        variantForm.relation === 'child'
                          ? 'border-primary border-thick bg-primary-lighten'
                          : 'border-dashed',
                      ]"
                      variant="outlined"
                      @click="variantForm.relation = 'child'"
                    >
                      <v-icon
                        size="48"
                        :color="variantForm.relation === 'child' ? 'primary' : 'grey'"
                      >
                        mdi-source-branch
                      </v-icon>
                      <div class="mt-2 text-subtitle-1 font-weight-bold">Podwariant</div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        Ta sama litera z numerem (np. {{ nextChildNumber }})
                      </div>
                      <div class="mt-3">
                        <v-chip size="small" color="grey" variant="tonal">
                          {{ sourceVariant?.variant_number }}
                        </v-chip>
                        <span class="text-caption mx-1 text-medium-emphasis">→</span>
                        <v-chip
                          size="small"
                          :color="variantForm.relation === 'child' ? 'primary' : 'grey'"
                          variant="flat"
                        >
                          {{ nextChildNumber }}
                        </v-chip>
                      </div>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </template>

          <!-- KROK 2 — Co kopiować -->
          <template v-slot:item.2>
            <v-card flat class="pa-2">
              <v-card-text>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Wybierz dane, które mają zostać skopiowane z wariantu
                  <strong>{{ sourceVariant?.variant_number }}</strong> do nowego:
                </p>

                <!-- Wycena -->
                <v-card
                  :class="[
                    'copy-option-card mb-3 pa-4 cursor-pointer',
                    variantForm.copy_quotation ? 'border-success bg-success-lighten' : '',
                  ]"
                  flat
                  @click="variantForm.copy_quotation = !variantForm.copy_quotation"
                >
                  <div class="d-flex align-center">
                    <div class="flex-grow-1">
                      <div class="d-flex align-center">
                        <v-icon color="success" class="mr-2"
                          >mdi-file-document-outline</v-icon
                        >
                        <span class="text-subtitle-2 font-weight-bold">Wycena</span>
                        <v-chip
                          v-if="sourceHasApprovedQuotation"
                          size="x-small"
                          color="success"
                          class="ml-2"
                        >
                          <v-icon start size="x-small">mdi-check-circle</v-icon>
                          Zatwierdzona
                        </v-chip>
                        <v-chip
                          v-else-if="sourceHasAnyQuotation"
                          size="x-small"
                          color="warning"
                          class="ml-2"
                        >
                          Robocza v{{ sourceLatestQuotationVersion }}
                        </v-chip>
                        <v-chip v-else size="x-small" color="grey" class="ml-2"
                          >Brak wyceny</v-chip
                        >
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        <template v-if="sourceHasApprovedQuotation">
                          Kopiuje zatwierdzoną wycenę jako nową roboczą v1
                          (niezatwierdzoną)
                        </template>
                        <template v-else-if="sourceHasAnyQuotation">
                          Kopiuje ostatnią wersję wyceny jako roboczą v1 (niezatwierdzoną)
                        </template>
                        <template v-else>
                          Brak wyceny do skopiowania – opcja niedostępna
                        </template>
                      </div>
                    </div>
                    <v-icon
                      :color="variantForm.copy_quotation ? 'success' : 'grey-lighten-2'"
                      size="28"
                    >
                      {{
                        variantForm.copy_quotation
                          ? "mdi-check-circle"
                          : "mdi-circle-outline"
                      }}
                    </v-icon>
                  </div>
                </v-card>

                <!-- Materiały -->
                <v-card
                  :class="[
                    'copy-option-card mb-3 pa-4 cursor-pointer',
                    variantForm.copy_materials ? 'border-blue bg-blue-lighten' : '',
                  ]"
                  flat
                  @click="variantForm.copy_materials = !variantForm.copy_materials"
                >
                  <div class="d-flex align-center">
                    <div class="flex-grow-1">
                      <div class="d-flex align-center">
                        <v-icon color="blue" class="mr-2">mdi-cube-outline</v-icon>
                        <span class="text-subtitle-2 font-weight-bold">Materiały</span>
                        <v-chip
                          v-if="sourceMaterialsCount > 0"
                          size="x-small"
                          color="blue"
                          class="ml-2"
                        >
                          {{ sourceMaterialsCount }} poz.
                        </v-chip>
                        <v-chip v-else size="x-small" color="grey" class="ml-2"
                          >Brak materiałów</v-chip
                        >
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        Kopiuje listę materiałów ze statusem "Niezamówione"
                      </div>
                    </div>
                    <v-icon
                      :color="variantForm.copy_materials ? 'blue' : 'grey-lighten-2'"
                      size="28"
                    >
                      {{
                        variantForm.copy_materials
                          ? "mdi-check-circle"
                          : "mdi-circle-outline"
                      }}
                    </v-icon>
                  </div>
                </v-card>

                <v-alert
                  type="info"
                  variant="tonal"
                  density="compact"
                  icon="mdi-information-outline"
                >
                  Zrealizowane usługi produkcyjne (RCP)
                  <strong>nigdy nie są kopiowane</strong>. Nowy wariant startuje bez
                  historii produkcji.
                </v-alert>
              </v-card-text>
            </v-card>
          </template>

          <!-- KROK 3 — Szczegóły -->
          <template v-slot:item.3>
            <v-card flat class="pa-2">
              <v-card-text>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Podaj szczegóły nowego wariantu
                  <v-chip size="x-small" color="primary" class="ml-1">
                    {{
                      variantForm.relation === "sibling"
                        ? nextSiblingNumber
                        : nextChildNumber
                    }} </v-chip
                  >:
                </p>

                <v-form ref="variantFormRef">
                  <div class="mb-6">
                    <div
                      class="text-caption text-medium-emphasis mb-2 font-weight-bold text-uppercase"
                    >
                      Typ wariantu
                    </div>
                    <v-btn-toggle
                      v-model="variantForm.type"
                      mandatory
                      divided
                      variant="outlined"
                      color="primary"
                      class="w-100"
                    >
                      <v-btn value="SERIAL" class="w-50">
                        <v-icon start>mdi-factory</v-icon>
                        Produkcja seryjna
                      </v-btn>
                      <v-btn value="PROTOTYPE" class="w-50">
                        <v-icon start>mdi-flask-outline</v-icon>
                        Prototyp
                      </v-btn>
                    </v-btn-toggle>
                  </div>

                  <v-text-field
                    v-model="variantForm.name"
                    label="Nazwa wariantu *"
                    variant="outlined"
                    prepend-inner-icon="mdi-tag"
                    :rules="[(v) => !!v || 'Nazwa jest wymagana']"
                    class="mb-2"
                  />

                  <v-text-field
                    v-model.number="variantForm.quantity"
                    label="Ilość *"
                    type="number"
                    min="1"
                    variant="outlined"
                    prepend-inner-icon="mdi-counter"
                    suffix="szt"
                    :rules="[(v) => v > 0 || 'Ilość musi być większa od 0']"
                    class="mb-2"
                  />

                  <v-textarea
                    v-model="variantForm.description"
                    label="Opis / Specyfikacja"
                    variant="outlined"
                    rows="2"
                    prepend-inner-icon="mdi-text"
                    placeholder="Różnice względem oryginału..."
                  />
                </v-form>

                <!-- Podsumowanie -->
                <v-card variant="tonal" color="deep-purple" class="mt-2 pa-3">
                  <div class="text-caption font-weight-bold mb-2 text-uppercase">
                    Podsumowanie duplikacji:
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <v-chip
                      size="small"
                      :color="
                        variantForm.relation === 'sibling' ? 'primary' : 'secondary'
                      "
                      variant="flat"
                    >
                      <v-icon start size="x-small">
                        {{
                          variantForm.relation === "sibling"
                            ? "mdi-family-tree"
                            : "mdi-source-branch"
                        }}
                      </v-icon>
                      {{ variantForm.relation === "sibling" ? "Wariant" : "Podwariant" }}
                      {{ sourceVariant?.variant_number }} →
                      {{
                        variantForm.relation === "sibling"
                          ? nextSiblingNumber
                          : nextChildNumber
                      }}
                    </v-chip>
                    <v-chip
                      v-if="variantForm.copy_quotation && sourceHasAnyQuotation"
                      size="small"
                      color="success"
                      variant="flat"
                    >
                      <v-icon start size="x-small">mdi-check</v-icon>
                      Wycena v1 (robocza)
                    </v-chip>
                    <v-chip
                      v-if="variantForm.copy_materials && sourceMaterialsCount > 0"
                      size="small"
                      color="blue"
                      variant="flat"
                    >
                      <v-icon start size="x-small">mdi-check</v-icon>
                      {{ sourceMaterialsCount }} materiałów
                    </v-chip>
                    <v-chip size="small" color="grey" variant="flat">
                      <v-icon start size="x-small">mdi-close</v-icon>
                      Bez usług RCP
                    </v-chip>
                  </div>
                </v-card>
              </v-card-text>
            </v-card>
          </template>
        </v-stepper>

        <!-- Akcje steppera -->
        <v-divider />
        <v-card-actions class="pa-4 bg-grey-lighten-5">
          <v-btn
            variant="outlined"
            color="grey-darken-1"
            :disabled="step === 1"
            @click="step--"
          >
            <v-icon start>mdi-arrow-left</v-icon>
            Wstecz
          </v-btn>

          <v-spacer />

          <v-btn variant="text" color="grey-darken-1" @click="handleCancel">Anuluj</v-btn>

          <v-btn v-if="step < 3" color="primary" variant="elevated" @click="step++">
            Dalej
            <v-icon end>mdi-arrow-right</v-icon>
          </v-btn>

          <v-btn
            v-else
            color="deep-purple"
            variant="elevated"
            :loading="saving"
            @click="handleVariantSubmit"
          >
            <v-icon start>mdi-content-copy</v-icon>
            Duplikuj
          </v-btn>
        </v-card-actions>
      </template>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import api from "@/services/api";

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
  modelValue: boolean;
  sourceVariant: any | null;
  existingVariants: any[];
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [newVariant: any];
}>();

// ─── Kluczowy computed — typ źródła ──────────────────────────────────────────

/**
 * GŁÓWNY DISCRIMINATOR.
 * is_group=true  → pokazuj ścieżkę A (prosta, 1 krok)
 * is_group=false → pokazuj ścieżkę B (stepper, 3 kroki)
 *
 * Po dodaniu migracji is_group backend zawsze zwraca to pole.
 */
const isGroup = computed<boolean>(() => {
  return props.sourceVariant?.is_group === true;
});

// ─── Wspólne ─────────────────────────────────────────────────────────────────

const saving = ref(false);

const handleCancel = () => {
  emit("update:modelValue", false);
};

// ─── ŚCIEŻKA A — Formularz grupy ─────────────────────────────────────────────

const groupFormRef = ref<any>(null);

const defaultGroupForm = () => ({
  name: "",
  description: "",
  copy_children: false,
});

const groupForm = ref(defaultGroupForm());

/** Liczba bezpośrednich dzieci grupy źródłowej */
const childrenCount = computed<number>(() => {
  if (!props.sourceVariant) return 0;
  return props.existingVariants.filter(
    (v) => v.parent_variant_id === props.sourceVariant.id
  ).length;
});

/** Następna wolna litera dla nowej grupy top-level */
const nextGroupLetter = computed<string>(() => {
  const letters = props.existingVariants
    .filter((v) => !v.parent_variant_id && v.variant_number?.length === 1)
    .map((v) => v.variant_number as string);

  let letter = "A";
  while (letters.includes(letter)) {
    letter = String.fromCharCode(letter.charCodeAt(0) + 1);
  }
  return letter;
});

const handleGroupSubmit = async () => {
  const { valid } = await groupFormRef.value?.validate();
  if (!valid) return;

  saving.value = true;
  try {
    const response = await api.post(`/variants/${props.sourceVariant.id}/duplicate`, {
      name: groupForm.value.name,
      description: groupForm.value.description || null,
      copy_children: groupForm.value.copy_children,
    });

    emit("saved", response.data.variant);
    emit("update:modelValue", false);
  } catch (err: any) {
    console.error("Błąd duplikowania grupy:", err);
    alert(
      "Błąd podczas duplikowania grupy: " + (err.response?.data?.message || err.message)
    );
  } finally {
    saving.value = false;
  }
};

// ─── ŚCIEŻKA B — Stepper wariantu ────────────────────────────────────────────

const step = ref(1);
const variantFormRef = ref<any>(null);

const defaultVariantForm = () => ({
  relation: "sibling" as "sibling" | "child",
  copy_quotation: true,
  copy_materials: true,
  type: "SERIAL",
  name: "",
  quantity: 1,
  description: "",
});

const variantForm = ref(defaultVariantForm());

// ─── Computed — numeracja wariantów ──────────────────────────────────────────

const nextSiblingNumber = computed<string>(() => {
  if (!props.sourceVariant) return "B";

  const parentId = props.sourceVariant.parent_variant_id;

  if (!parentId) {
    // Top-level: następna wolna litera
    const letters = props.existingVariants
      .filter((v) => !v.parent_variant_id && v.variant_number?.length === 1)
      .map((v) => v.variant_number as string);

    let letter = "A";
    while (letters.includes(letter)) {
      letter = String.fromCharCode(letter.charCodeAt(0) + 1);
    }
    return letter;
  }

  // Dziecko: następny numer wśród rodzeństwa
  const parent = props.existingVariants.find((v) => v.id === parentId);
  if (!parent) return props.sourceVariant.variant_number + "?";
  return computeNextChild(parent, props.existingVariants);
});

const nextChildNumber = computed<string>(() => {
  if (!props.sourceVariant) return "";
  return computeNextChild(props.sourceVariant, props.existingVariants);
});

/**
 * Oblicz następny numer dziecka dla danego rodzica.
 * Identyczna logika co backend:
 *   Rodzic bez cyfry (A, B) → A1, A2  (bez separatora)
 *   Rodzic z cyfrą (A1, B2) → A1_1, A1_2  (separator _)
 */
function computeNextChild(parent: any, allVariants: any[]): string {
  const parentNumber: string = parent.variant_number;
  const isDeepParent = /\d/.test(parentNumber);
  const separator = isDeepParent ? "_" : "";
  const prefix = parentNumber + separator;

  const children = allVariants.filter((v) => v.parent_variant_id === parent.id);
  if (children.length === 0) return prefix + "1";

  const maxNum = children.reduce((max, child) => {
    if (child.variant_number?.startsWith(prefix)) {
      const suffix = child.variant_number.slice(prefix.length);
      if (/^\d+$/.test(suffix)) return Math.max(max, parseInt(suffix));
    }
    return max;
  }, 0);

  return prefix + (maxNum + 1);
}

// ─── Computed — dane źródłowe wariantu ───────────────────────────────────────

const sourceHasApprovedQuotation = computed(
  () => props.sourceVariant?.approved_quotation != null
);

const sourceHasAnyQuotation = computed(
  () =>
    props.sourceVariant?.approved_quotation != null ||
    (props.sourceVariant?.quotations?.length ?? 0) > 0
);

const sourceLatestQuotationVersion = computed(() => {
  const q = props.sourceVariant?.quotations;
  if (!q?.length) return 0;
  return Math.max(...q.map((v: any) => v.version_number ?? 0));
});

const sourceMaterialsCount = computed(() => props.sourceVariant?.materials?.length ?? 0);

// ─── Submit wariantu ──────────────────────────────────────────────────────────

const handleVariantSubmit = async () => {
  const { valid } = await variantFormRef.value?.validate();
  if (!valid) return;

  saving.value = true;
  try {
    const response = await api.post(`/variants/${props.sourceVariant.id}/duplicate`, {
      relation: variantForm.value.relation,
      name: variantForm.value.name,
      quantity: variantForm.value.quantity,
      type: variantForm.value.type,
      copy_quotation: variantForm.value.copy_quotation,
      copy_materials: variantForm.value.copy_materials,
      description: variantForm.value.description || null,
    });

    emit("saved", response.data.variant);
    emit("update:modelValue", false);
  } catch (err: any) {
    console.error("Błąd duplikowania wariantu:", err);
    alert("Błąd podczas duplikowania: " + (err.response?.data?.message || err.message));
  } finally {
    saving.value = false;
  }
};

// ─── Reset przy otwarciu dialogu ─────────────────────────────────────────────

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return;

    // Reset stanu kroków
    step.value = 1;

    if (props.sourceVariant?.is_group === true) {
      // Reset formularza grupy
      groupForm.value = defaultGroupForm();
      groupForm.value.name = props.sourceVariant.name + " (kopia)";
      groupForm.value.description = props.sourceVariant.description ?? "";
    } else {
      // Reset formularza wariantu
      variantForm.value = defaultVariantForm();
      variantForm.value.name = props.sourceVariant?.name + " (kopia)";
      variantForm.value.quantity = props.sourceVariant?.quantity ?? 1;
      variantForm.value.type = props.sourceVariant?.type ?? "SERIAL";
      variantForm.value.description = props.sourceVariant?.description ?? "";
    }
  }
);
</script>

<style scoped>
.relation-card {
  transition: all 0.2s ease;
  border-radius: 12px !important;
  height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.relation-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.border-primary {
  border: 2px solid rgb(var(--v-theme-primary)) !important;
}

.border-thick {
  border-width: 2px !important;
}

.border-dashed {
  border-style: dashed !important;
}

.bg-primary-lighten {
  background-color: rgba(var(--v-theme-primary), 0.06) !important;
}

.copy-option-card {
  border-radius: 10px !important;
  border: 2px solid rgba(var(--v-theme-on-surface), 0.12) !important;
  transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.copy-option-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.border-success {
  border-color: rgb(var(--v-theme-success)) !important;
}

.bg-success-lighten {
  background-color: rgba(var(--v-theme-success), 0.06) !important;
}

.border-blue {
  border-color: rgb(var(--v-theme-info)) !important;
}

.bg-blue-lighten {
  background-color: rgba(var(--v-theme-info), 0.06) !important;
}

.border-indigo {
  border-color: rgb(var(--v-theme-indigo, 63, 81, 181)) !important;
  border-color: #3f51b5 !important;
}

.bg-indigo-lighten {
  background-color: rgba(63, 81, 181, 0.06) !important;
}

.gap-2 {
  gap: 8px;
}
</style>
