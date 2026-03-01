<template>
  <v-dialog
    :model-value="modelValue"
    max-width="720"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card
      class="rounded-lg d-flex flex-column"
      style="max-height: 90vh; overflow: hidden"
    >
      <!-- ── Nagłówek karty (fixed) ── -->
      <v-card-title
        class="bg-deep-purple text-white d-flex align-center pa-4 flex-shrink-0"
      >
        <v-icon start color="white">mdi-layers-plus</v-icon>
        <span class="text-h6 font-weight-bold">Nowa seria projektu</span>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" color="white" @click="close" />
      </v-card-title>

      <!-- ── Scrollowalna zawartość (stepper header + treść kroków) ── -->
      <v-card-text
        class="pa-0 flex-grow-1"
        style="overflow-y: auto; overflow-x: hidden; min-height: 0"
      >
        <v-stepper
          v-model="step"
          flat
          hide-actions
          class="pa-0 compact-stepper"
          style="background: transparent"
          alt-labels
        >
          <v-stepper-header style="box-shadow: none">
            <v-stepper-item
              :value="1"
              :complete="step > 1"
              color="deep-purple"
              title="Dane serii"
            />
            <v-divider />
            <v-stepper-item
              :value="2"
              :complete="step > 2"
              :color="stepVariantsSkipped ? 'grey' : 'deep-purple'"
              title="Warianty"
              :class="{ 'opacity-40': stepVariantsSkipped }"
            />
            <v-divider />
            <v-stepper-item
              :value="3"
              :complete="step > 3"
              color="deep-purple"
              title="Podsumowanie"
            />
          </v-stepper-header>
        </v-stepper>

        <!-- ═══════════════════════════════════════════════════
             KROK 1 — Dane serii
        ════════════════════════════════════════════════════ -->
        <div v-if="step === 1" class="pa-5">
          <v-alert
            type="info"
            variant="tonal"
            density="compact"
            class="mb-4"
            icon="mdi-information-outline"
          >
            Tworzysz nową serię dla projektu
            <strong>{{ sourceProject?.project_number }}</strong
            >. Nowy numer:
            <strong>{{ sourceProject?.project_number }}/{{ nextSeriesLabel }}</strong>
          </v-alert>

          <v-text-field
            v-model="form.description"
            label="Opis / nazwa serii"
            placeholder="np. Seria Q3 2025, Edycja letnia..."
            variant="outlined"
            prepend-inner-icon="mdi-text"
            class="mb-3"
          />

          <v-text-field
            v-model="form.planned_delivery_date"
            label="Planowana data dostawy"
            type="date"
            variant="outlined"
            prepend-inner-icon="mdi-calendar"
            class="mb-3"
          />

          <v-select
            v-model="form.priority"
            :items="priorityOptions"
            label="Priorytet"
            variant="outlined"
            prepend-inner-icon="mdi-flag"
            item-title="label"
            item-value="value"
            class="mb-3"
          />

          <v-divider class="mb-4" />

          <div class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center">
            <v-icon class="mr-2" color="deep-purple">mdi-content-copy</v-icon>
            Kopiowanie wariantów z poprzedniej serii
          </div>

          <v-switch
            v-model="form.copyVariants"
            color="deep-purple"
            label="Kopiuj warianty z wybranej serii"
            hide-details
            class="mb-3"
          />

          <v-expand-transition>
            <div v-if="form.copyVariants">
              <v-select
                v-model="form.copyFromProjectId"
                :items="availableSeries"
                :loading="loadingSeries"
                label="Seria źródłowa (skąd kopiować)"
                variant="outlined"
                item-title="display_label"
                item-value="id"
                prepend-inner-icon="mdi-layers"
                no-data-text="Brak innych serii"
                class="mb-2 mt-2"
                @update:model-value="onSourceProjectChange"
              >
                <template v-slot:item="{ item, props: iProps }">
                  <v-list-item v-bind="iProps">
                    <template v-slot:subtitle>
                      <span class="text-caption">
                        {{ item.raw.variants?.length ?? 0 }} wariantów •
                        {{ item.raw.overall_status }} •
                        {{ item.raw?.created_at?.split("T")?.[0] }}
                      </span>
                    </template>
                  </v-list-item>
                </template>
              </v-select>

              <div
                v-if="loadingVariants"
                class="d-flex align-center text-caption text-medium-emphasis mb-2"
              >
                <v-progress-circular indeterminate size="14" width="2" class="mr-2" />
                Sprawdzanie wariantów...
              </div>

              <v-alert
                v-if="
                  form.copyFromProjectId && !loadingVariants && variantsForCopy.length === 0
                "
                type="warning"
                variant="tonal"
                density="compact"
                icon="mdi-alert-outline"
                class="mb-2"
              >
                Wybrana seria nie ma wariantów. Seria zostanie utworzona
                <strong>bez kopiowania</strong> — możesz wybrać inną serię lub
                kontynuować.
              </v-alert>

              <v-alert
                v-if="
                  form.copyFromProjectId && !loadingVariants && variantsForCopy.length > 0
                "
                type="success"
                variant="tonal"
                density="compact"
                icon="mdi-check-circle-outline"
                class="mb-2"
              >
                Znaleziono <strong>{{ variantsForCopy.length }}</strong>
                {{ variantsForCopy.length === 1 ? "wariant" : "wariantów" }} do
                skopiowania.
              </v-alert>
            </div>
          </v-expand-transition>
        </div>

        <!-- ═══════════════════════════════════════════════════
             KROK 2 — Wybór wariantów
        ════════════════════════════════════════════════════ -->
        <div v-else-if="step === 2" class="pa-5">
          <div class="d-flex align-center mb-4">
            <div class="text-subtitle-1 font-weight-bold">
              Wybierz warianty do skopiowania
            </div>
            <v-spacer />
            <v-btn
              size="small"
              variant="text"
              color="deep-purple"
              @click="selectAllVariants"
            >
              Zaznacz wszystkie
            </v-btn>
            <v-btn size="small" variant="text" color="grey" @click="deselectAllVariants">
              Odznacz
            </v-btn>
          </div>

          <v-card
            v-for="variant in variantsForCopy"
            :key="variant.id"
            variant="outlined"
            class="mb-3 variant-copy-card"
            :class="{ 'variant-selected': isVariantSelected(variant.id) }"
          >
            <v-card-text class="pa-3">
              <div class="d-flex align-center">
                <v-checkbox
                  :model-value="isVariantSelected(variant.id)"
                  color="deep-purple"
                  hide-details
                  density="compact"
                  class="mr-2 flex-shrink-0"
                  @update:model-value="toggleVariant(variant.id, $event as boolean)"
                />
                <div class="flex-grow-1">
                  <div class="d-flex align-center flex-wrap">
                    <span class="font-weight-bold mr-2"
                      >[{{ variant.variant_number }}] {{ variant.name }}</span
                    >
                    <v-chip size="x-small" variant="outlined" class="mr-1">
                      {{ variant.quantity }} szt
                    </v-chip>
                    <v-chip
                      size="x-small"
                      :color="variant.type === 'PROTOTYPE' ? 'purple' : 'blue'"
                      variant="flat"
                      label
                    >
                      {{ variant.type === "PROTOTYPE" ? "PROTOTYP" : "SERIA" }}
                    </v-chip>
                  </div>

                  <v-expand-transition>
                    <div v-if="isVariantSelected(variant.id)" class="mt-2 pl-1">
                      <div class="d-flex flex-wrap gap-2">
                        <div class="text-caption text-medium-emphasis">Co skopiować:</div>
                        <v-chip
                          :color="
                            getVariantConfig(variant.id).copy_quotation ? 'green' : 'grey'
                          "
                          :variant="
                            getVariantConfig(variant.id).copy_quotation
                              ? 'flat'
                              : 'outlined'
                          "
                          size="small"
                          class="cursor-pointer copy-option-chip"
                          :disabled="!variant.has_quotation"
                          @click="toggleCopyOption(variant.id, 'copy_quotation')"
                        >
                          <v-icon start size="x-small">
                            {{
                              getVariantConfig(variant.id).copy_quotation
                                ? "mdi-check"
                                : "mdi-close"
                            }}
                          </v-icon>
                          Wycena
                          <span v-if="variant.quotation_info" class="ml-1 opacity-70">
                            (v{{ variant.quotation_info.version_number }}
                            <template v-if="variant.quotation_info.is_approved"
                              >✓</template
                            >)
                          </span>
                          <span v-else class="ml-1 opacity-50">(brak)</span>
                        </v-chip>

                        <v-chip
                          :color="
                            getVariantConfig(variant.id).copy_materials
                              ? 'orange'
                              : 'grey'
                          "
                          :variant="
                            getVariantConfig(variant.id).copy_materials
                              ? 'flat'
                              : 'outlined'
                          "
                          size="small"
                          class="cursor-pointer copy-option-chip"
                          :disabled="!variant.has_materials"
                          @click="toggleCopyOption(variant.id, 'copy_materials')"
                        >
                          <v-icon start size="x-small">
                            {{
                              getVariantConfig(variant.id).copy_materials
                                ? "mdi-check"
                                : "mdi-close"
                            }}
                          </v-icon>
                          Materiały
                          <span
                            v-if="variant.materials_count > 0"
                            class="ml-1 opacity-70"
                          >
                            ({{ variant.materials_count }})
                          </span>
                          <span v-else class="ml-1 opacity-50">(brak)</span>
                        </v-chip>
                      </div>
                    </div>
                  </v-expand-transition>
                </div>
              </div>
            </v-card-text>
          </v-card>

          <v-alert
            v-if="selectedVariantCount > 0"
            type="success"
            variant="tonal"
            density="compact"
            class="mt-2"
          >
            Wybrano <strong>{{ selectedVariantCount }}</strong> z
            {{ variantsForCopy.length }} wariantów.
            <span v-if="countCopyOptions.quotations > 0">
              {{ countCopyOptions.quotations }} z wycenami.
            </span>
            <span v-if="countCopyOptions.materials > 0">
              {{ countCopyOptions.materials }} z materiałami.
            </span>
          </v-alert>

          <v-alert
            v-else
            type="warning"
            variant="tonal"
            density="compact"
            icon="mdi-alert-outline"
            class="mt-2"
          >
            Nie zaznaczono żadnego wariantu. Seria zostanie utworzona bez kopiowania.
          </v-alert>
        </div>

        <!-- ═══════════════════════════════════════════════════
             KROK 3 — Podsumowanie
        ════════════════════════════════════════════════════ -->
        <div v-else-if="step === 3" class="pa-5">
          <div class="text-subtitle-1 font-weight-bold mb-4 d-flex align-center">
            <v-icon class="mr-2" color="deep-purple">mdi-clipboard-check-outline</v-icon>
            Podsumowanie — sprawdź przed utworzeniem
          </div>

          <v-card variant="outlined" class="mb-4 summary-card">
            <v-card-title
              class="text-subtitle-2 font-weight-bold pa-3 pb-2 summary-card-title"
            >
              <v-icon size="small" class="mr-1" color="deep-purple"
                >mdi-information-outline</v-icon
              >
              Dane serii
            </v-card-title>
            <v-card-text class="pa-3 pt-2">
              <div class="summary-row">
                <span class="summary-label">Numer serii</span>
                <span class="summary-value font-weight-bold">
                  {{ sourceProject?.project_number }}/{{ nextSeriesLabel }}
                </span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Opis</span>
                <span class="summary-value">{{ form.description || "—" }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Planowana dostawa</span>
                <span class="summary-value">{{ form.planned_delivery_date || "—" }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Priorytet</span>
                <span class="summary-value">
                  <v-chip size="x-small" :color="priorityColor" variant="flat" label>
                    {{ priorityLabel }}
                  </v-chip>
                </span>
              </div>
            </v-card-text>
          </v-card>

          <v-card variant="outlined" class="mb-3 summary-card">
            <v-card-title
              class="text-subtitle-2 font-weight-bold pa-3 pb-2 summary-card-title"
            >
              <v-icon size="small" class="mr-1" color="deep-purple"
                >mdi-content-copy</v-icon
              >
              Kopiowanie wariantów
            </v-card-title>
            <v-card-text class="pa-3 pt-2">
              <template v-if="!form.copyVariants">
                <v-alert type="info" variant="tonal" density="compact" icon="mdi-information">
                  Kopiowanie wyłączone — seria zostanie utworzona bez wariantów.
                </v-alert>
              </template>

              <template v-else-if="!form.copyFromProjectId">
                <v-alert type="warning" variant="tonal" density="compact" icon="mdi-alert-outline">
                  Nie wybrano serii źródłowej — seria zostanie utworzona bez wariantów.
                </v-alert>
              </template>

              <template v-else-if="variantsForCopy.length === 0">
                <v-alert type="warning" variant="tonal" density="compact" icon="mdi-alert-outline">
                  Wybrana seria <strong>{{ sourceSeriesLabel }}</strong>
                  nie zawiera wariantów — seria zostanie utworzona bez kopiowania.
                </v-alert>
              </template>

              <template v-else-if="selectedVariantCount === 0">
                <v-alert type="warning" variant="tonal" density="compact" icon="mdi-alert-outline">
                  Nie zaznaczono żadnego wariantu z serii
                  <strong>{{ sourceSeriesLabel }}</strong>
                  — seria zostanie utworzona bez kopiowania.
                </v-alert>
              </template>

              <template v-else>
                <div class="summary-row mb-3">
                  <span class="summary-label">Seria źródłowa</span>
                  <span class="summary-value font-weight-bold">{{ sourceSeriesLabel }}</span>
                </div>
                <div class="summary-row mb-3">
                  <span class="summary-label">Liczba wariantów</span>
                  <span class="summary-value">
                    <v-chip size="x-small" color="deep-purple" variant="flat">
                      {{ selectedVariantCount }} / {{ variantsForCopy.length }}
                    </v-chip>
                    <span
                      v-if="countCopyOptions.quotations > 0 || countCopyOptions.materials > 0"
                      class="text-caption text-medium-emphasis ml-2"
                    >
                      ({{ countCopyOptions.quotations }} z wycenami,
                      {{ countCopyOptions.materials }} z materiałami)
                    </span>
                  </span>
                </div>

                <div
                  v-for="variantId in Array.from(variantCopyMap.keys())"
                  :key="variantId"
                  class="summary-variant-row"
                >
                  <div class="d-flex align-center flex-wrap py-1">
                    <v-icon size="small" color="deep-purple" class="mr-2">mdi-check-circle</v-icon>
                    <span class="text-body-2 font-weight-medium mr-2">
                      [{{ getVariantNumber(variantId) }}] {{ getVariantName(variantId) }}
                    </span>
                    <v-chip
                      v-if="getVariantConfig(variantId).copy_quotation"
                      size="x-small"
                      color="green"
                      variant="flat"
                      class="mr-1"
                    >
                      wycena
                    </v-chip>
                    <v-chip
                      v-if="getVariantConfig(variantId).copy_materials"
                      size="x-small"
                      color="orange"
                      variant="flat"
                    >
                      materiały
                    </v-chip>
                  </div>
                </div>
              </template>
            </v-card-text>
          </v-card>
        </div>
      </v-card-text>

      <!-- ── Przyciski nawigacji (fixed na dole) ── -->
      <v-divider />
      <v-card-actions class="pa-4 flex-shrink-0">
        <v-btn variant="text" @click="close">Anuluj</v-btn>
        <v-spacer />

        <template v-if="step === 1">
          <v-btn
            color="deep-purple"
            variant="elevated"
            append-icon="mdi-arrow-right"
            :loading="loadingVariants"
            :disabled="form.copyVariants && !form.copyFromProjectId"
            @click="navigateFromStep1"
          >
            Dalej
          </v-btn>
        </template>

        <template v-else-if="step === 2">
          <v-btn variant="outlined" prepend-icon="mdi-arrow-left" class="mr-2" @click="step = 1">
            Wróć
          </v-btn>
          <v-btn color="deep-purple" variant="elevated" append-icon="mdi-arrow-right" @click="step = 3">
            Podsumowanie
          </v-btn>
        </template>

        <template v-else-if="step === 3">
          <v-btn variant="outlined" prepend-icon="mdi-arrow-left" class="mr-2" @click="navigateBackFromStep3">
            Wróć
          </v-btn>
          <v-btn color="deep-purple" variant="elevated" :loading="saving" prepend-icon="mdi-check" @click="save">
            Utwórz serię
          </v-btn>
        </template>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { seriesService } from "@/services/seriesService";
import type { VariantForCopy, VariantCopyConfig } from "@/services/seriesService";

const props = defineProps<{
  modelValue: boolean;
  sourceProject: {
    id: number;
    project_number: string;
    series: string;
    full_project_number: string;
  } | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [result: any];
}>();

const step = ref(1);
const saving = ref(false);
const loadingSeries = ref(false);
const loadingVariants = ref(false);

const availableSeries = ref<any[]>([]);
const variantsForCopy = ref<VariantForCopy[]>([]);
const variantCopyMap = ref<Map<number, VariantCopyConfig>>(new Map());

const form = ref({
  description: "",
  planned_delivery_date: null as string | null,
  priority: "NORMAL",
  copyVariants: false,
  copyFromProjectId: null as number | null,
});

const priorityOptions = [
  { label: "Niski", value: "low" },
  { label: "Normalny", value: "NORMAL" },
  { label: "Wysoki", value: "high" },
  { label: "Pilny", value: "urgent" },
];

const nextSeriesLabel = computed(() => {
  if (!availableSeries.value.length) return "????";
  const max = Math.max(
    ...availableSeries.value.map((s) => parseInt(s.series || "0", 10))
  );
  return String(max + 1).padStart(4, "0");
});

const stepVariantsSkipped = computed(() => {
  return (
    !form.value.copyVariants ||
    !form.value.copyFromProjectId ||
    variantsForCopy.value.length === 0
  );
});

const selectedVariantCount = computed(() => variantCopyMap.value.size);

const countCopyOptions = computed(() => {
  let quotations = 0;
  let materials = 0;
  variantCopyMap.value.forEach((cfg) => {
    if (cfg.copy_quotation) quotations++;
    if (cfg.copy_materials) materials++;
  });
  return { quotations, materials };
});

const sourceSeriesLabel = computed(() => {
  const found = availableSeries.value.find((s) => s.id === form.value.copyFromProjectId);
  return found?.display_label ?? "—";
});

const priorityColor = computed(() => {
  const map: Record<string, string> = {
    low: "grey",
    normal: "blue",
    high: "orange",
    urgent: "red",
  };
  return map[form.value.priority] ?? "grey";
});

const priorityLabel = computed(() => {
  return priorityOptions.find((o) => o.value === form.value.priority)?.label ?? "—";
});

watch(
  () => props.modelValue,
  async (isOpen) => {
    if (isOpen && props.sourceProject) {
      resetForm();
      await loadAvailableSeries();
    }
  }
);

const loadAvailableSeries = async () => {
  if (!props.sourceProject) return;
  loadingSeries.value = true;
  try {
    const all = await seriesService.getAllSeries(props.sourceProject.id);
    availableSeries.value = all.map((s) => ({
      ...s,
      display_label: `${s.full_project_number} — ${s.description || "Brak opisu"}`,
    }));
  } catch (err) {
    console.error("Błąd ładowania serii:", err);
  } finally {
    loadingSeries.value = false;
  }
};

const onSourceProjectChange = async (projectId: number | null) => {
  variantsForCopy.value = [];
  variantCopyMap.value = new Map();
  if (!projectId) return;

  loadingVariants.value = true;
  try {
    variantsForCopy.value = await seriesService.getVariantsForSelector(projectId);
  } catch (err) {
    console.error("Błąd ładowania wariantów:", err);
  } finally {
    loadingVariants.value = false;
  }
};

const navigateFromStep1 = () => {
  const hasVariants =
    form.value.copyVariants &&
    form.value.copyFromProjectId &&
    variantsForCopy.value.length > 0;

  step.value = hasVariants ? 2 : 3;
};

const navigateBackFromStep3 = () => {
  step.value = stepVariantsSkipped.value ? 1 : 2;
};

const isVariantSelected = (id: number): boolean => variantCopyMap.value.has(id);

const getVariantConfig = (id: number): VariantCopyConfig => {
  return (
    variantCopyMap.value.get(id) || {
      source_variant_id: id,
      copy_quotation: false,
      copy_materials: false,
    }
  );
};

const getVariantName = (id: number): string => {
  return variantsForCopy.value.find((v) => v.id === id)?.name ?? `Wariant #${id}`;
};

const getVariantNumber = (id: number): string => {
  return (
    variantsForCopy.value.find((v) => v.id === id)?.variant_number ?? `Wariant #${id}`
  );
};

const toggleVariant = (id: number, selected: boolean) => {
  if (selected) {
    const variant = variantsForCopy.value.find((v) => v.id === id);
    variantCopyMap.value.set(id, {
      source_variant_id: id,
      copy_quotation: variant?.has_quotation ?? false,
      copy_materials: variant?.has_materials ?? false,
    });
  } else {
    variantCopyMap.value.delete(id);
  }
  variantCopyMap.value = new Map(variantCopyMap.value);
};

const toggleCopyOption = (id: number, field: "copy_quotation" | "copy_materials") => {
  const cfg = getVariantConfig(id);
  const variant = variantsForCopy.value.find((v) => v.id === id);
  if (field === "copy_quotation" && !variant?.has_quotation) return;
  if (field === "copy_materials" && !variant?.has_materials) return;
  variantCopyMap.value.set(id, { ...cfg, [field]: !cfg[field] });
  variantCopyMap.value = new Map(variantCopyMap.value);
};

const selectAllVariants = () => {
  variantsForCopy.value.forEach((v) => {
    variantCopyMap.value.set(v.id, {
      source_variant_id: v.id,
      copy_quotation: v.has_quotation,
      copy_materials: v.has_materials,
    });
  });
  variantCopyMap.value = new Map(variantCopyMap.value);
};

const deselectAllVariants = () => {
  variantCopyMap.value = new Map();
};

const buildPayload = () => {
  const payload: any = {
    description: form.value.description || null,
    planned_delivery_date: form.value.planned_delivery_date || undefined,
    priority: form.value.priority,
  };

  if (
    form.value.copyVariants &&
    form.value.copyFromProjectId &&
    variantCopyMap.value.size > 0
  ) {
    payload.copy_from_project_id = form.value.copyFromProjectId;
    payload.variants = Array.from(variantCopyMap.value.values());
  }

  return payload;
};

const save = async () => {
  saving.value = true;
  try {
    const payload = buildPayload();
    const result = await seriesService.createSeries(props.sourceProject!.id, payload);
    emit("saved", result);
    close();
  } catch (err: any) {
    const msg = err.response?.data?.message || "Błąd tworzenia serii";
    alert(msg);
  } finally {
    saving.value = false;
  }
};

const close = () => {
  emit("update:modelValue", false);
};

const resetForm = () => {
  step.value = 1;
  saving.value = false;
  variantsForCopy.value = [];
  variantCopyMap.value = new Map();
  form.value = {
    description: "",
    planned_delivery_date: null,
    priority: "NORMAL",
    copyVariants: false,
    copyFromProjectId: null,
  };
};
</script>

<style scoped>
:deep(.compact-stepper.v-stepper) {
  background: transparent !important;
  padding: 0 !important;
}

:deep(.compact-stepper .v-stepper-header) {
  box-shadow: none !important;
  min-height: 52px !important;
  padding: 0 !important;
}

:deep(.compact-stepper .v-stepper-item) {
  padding-top: 8px !important;
  padding-bottom: 8px !important;
}

:deep(.compact-stepper .v-stepper-item__title) {
  font-size: 0.8rem !important;
  white-space: nowrap;
}

:deep(.compact-stepper .v-avatar) {
  width: 26px !important;
  height: 26px !important;
  min-width: 26px !important;
  font-size: 0.78rem !important;
}

:deep(.compact-stepper .v-stepper-window) {
  display: none !important;
}

.opacity-40 {
  opacity: 0.4;
}

.summary-card {
  border-color: #e0e0e0 !important;
}

.summary-card-title {
  background: rgba(103, 58, 183, 0.04);
  border-bottom: 1px solid #e0e0e0;
}

.summary-row {
  display: flex;
  align-items: center;
  padding: 4px 0;
  gap: 12px;
}

.summary-label {
  min-width: 140px;
  font-size: 0.8rem;
  color: rgba(0, 0, 0, 0.55);
  flex-shrink: 0;
}

.summary-value {
  font-size: 0.875rem;
}

.summary-variant-row {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.summary-variant-row:last-child {
  border-bottom: none;
}

.variant-copy-card {
  border-color: #e0e0e0 !important;
  transition: all 0.2s ease;
}

.variant-copy-card:hover {
  border-color: #9c27b0 !important;
}

.variant-selected {
  border-color: #9c27b0 !important;
  background-color: rgba(156, 39, 176, 0.03) !important;
}

.copy-option-chip {
  cursor: pointer;
  transition: all 0.15s ease;
}

.copy-option-chip:not(.v-chip--disabled):hover {
  opacity: 0.85;
  transform: translateY(-1px);
}

.gap-2 {
  gap: 8px;
}
</style>
