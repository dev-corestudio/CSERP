<template>
  <v-container fluid>
    <!-- NAGŁÓWEK -->
    <variant-header
      :variant="variant"
      :loading="inlineLoading === 'name'"
      @update-name="handleInlineUpdate('name', $event)"
      @go-back="goBack"
      @edit-full="editDialog = true"
    />

    <!-- Stan ładowania -->
    <div v-if="variantsStore.loading && !variant" class="text-center py-12">
      <v-progress-circular indeterminate size="64" color="primary" />
      <p class="mt-4 text-h6">Ładowanie...</p>
    </div>

    <!-- GŁÓWNA ZAWARTOŚĆ -->
    <div v-else-if="variant">
      <v-row>
        <!-- LEWA KOLUMNA -->
        <v-col cols="12" md="8">
          <!-- 1. Opis / Specyfikacja -->
          <variant-description
            :variant="variant"
            :loading="inlineLoading === 'description'"
            @update-desc="handleInlineUpdate('description', $event)"
          />

          <!-- 2. Kontrola kosztów -->
          <variant-cost-control
            :budget-materials="budgetMaterials"
            :budget-services="budgetServices"
            :actual-materials-cost="actualMaterialsCost"
            :actual-services-cost="actualServicesCost"
            :quantity="variant.quantity ?? 1"
            :tkw-z-wyceny="variant.tkw_z_wyceny ?? null"
          />

          <!-- 3. Wyceny -->
          <variant-quotations
            :quotations="quotations"
            :loading="loadingQuotations"
            :duplicating-id="duplicatingId"
            @create="openCreateDialog"
            @view="viewQuotation"
            @edit="editQuotation"
            @duplicate="handleDuplicate"
            @export="openExportDialog"
            @approve="approveQuotation"
            @delete="confirmDeleteQuotation"
          />

          <!-- 4. Usługi wykonane (RCP) -->
          <variant-rcp-services
            :tasks="productionTasks"
            :total-cost="actualServicesCost"
          />

          <!-- 5. Materiały -->
          <variant-materials-tab
            v-if="variant.id"
            :key="`materials-${variant.id}-${materialsRefreshKey}`"
            :variant-id="variant.id"
            :readonly="variant.status === 'COMPLETED' || variant.status === 'CANCELLED'"
            @data-loaded="onMaterialsLoaded"
            @updated="refreshVariant"
          />
        </v-col>

        <!-- PRAWA KOLUMNA (SIDEBAR) -->
        <v-col cols="12" md="4">
          <variant-sidebar
            :variant="variant"
            :status-loading="statusLoading"
            :inline-loading="inlineLoading"
            @update-inline="handleInlineUpdate"
            @update-status="handleInlineStatusChange"
            @open-review="openReview"
            @open-cancel="cancelDialog = true"
          />
        </v-col>
      </v-row>
    </div>

    <!-- DIALOGI -->

    <!-- Formularz wyceny -->
    <quotation-form-dialog
      v-if="variant"
      v-model="createQuotationDialog"
      :variant="variant"
      :quotation="editingQuotation"
      @saved="handleQuotationSaved"
    />

    <!-- Podgląd wyceny -->
    <quotation-details-dialog
      v-model="viewQuotationDialog"
      :quotation="selectedQuotation"
      @close="viewQuotationDialog = false"
    />

    <!-- Eksport materiałów -->
    <quotation-export-materials-dialog
      v-model="exportMaterialsDialog"
      :quotation="quotationToExport"
      @exported="onMaterialsExported"
    />

    <!-- Edycja wariantu -->
    <variant-form-dialog
      v-model="editDialog"
      :variant="variant"
      @saved="refreshVariant"
    />

    <!-- Potwierdzenie usunięcia wyceny -->
    <variant-delete-quotation-dialog
      v-model="deleteQuotationDialog"
      :version-number="quotationToDelete?.version_number"
      :loading="loadingQuotations"
      @confirm="handleDeleteQuotation"
    />

    <!-- Review prototypu -->
    <variant-review-dialog
      v-model="reviewDialog"
      :action="reviewAction"
      :loading="reviewLoading"
      @submit="submitReview"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useVariantsStore } from "@/stores/variants";
import { useQuotationsStore } from "@/stores/quotations";

// Zaimportowane wyodrębnione komponenty wariantu
import VariantHeader from "@/components/variants/VariantHeader.vue";
import VariantDescription from "@/components/variants/VariantDescription.vue";
import VariantCostControl from "@/components/variants/VariantCostControl.vue";
import VariantRcpServices from "@/components/variants/VariantRcpServices.vue";
import VariantQuotations from "@/components/variants/VariantQuotations.vue";
import VariantSidebar from "@/components/variants/VariantSidebar.vue";

// Zaimportowane dialogi
import VariantReviewDialog from "@/components/variants/VariantReviewDialog.vue";
import VariantDeleteQuotationDialog from "@/components/variants/VariantDeleteQuotationDialog.vue";

// Główne komponenty istniejące wcześniej (Materiały, Wyceny, Formularze)
import VariantMaterialsTab from "@/components/materials/VariantMaterialsTab.vue";
import QuotationFormDialog from "@/components/quotations/QuotationFormDialog.vue";
import QuotationDetailsDialog from "@/components/quotations/QuotationDetailsDialog.vue";
import QuotationExportMaterialsDialog from "@/components/quotations/QuotationExportMaterialsDialog.vue";
import VariantFormDialog from "@/components/orders/VariantFormDialog.vue";

// Inicjalizacja i sklepy
const route = useRoute();
const router = useRouter();
const variantsStore = useVariantsStore();
const quotationsStore = useQuotationsStore();

// STATE

// Główny obiekt wariantu
const variant = computed(() => variantsStore.currentVariant);

// Flagi ładowania
const statusLoading = ref(false);
const inlineLoading = ref<string | null>(null);
const loadingQuotations = ref(false);
const reviewLoading = ref(false);

// Dialogi
const reviewDialog = ref(false);
const cancelDialog = ref(false);
const editDialog = ref(false);
const createQuotationDialog = ref(false);
const viewQuotationDialog = ref(false);
const deleteQuotationDialog = ref(false);
const exportMaterialsDialog = ref(false);

// Wyceny
const quotations = ref<any[]>([]);
const selectedQuotation = ref<any>(null);
const quotationToDelete = ref<any>(null);
const quotationToExport = ref<any>(null);
const editingQuotation = ref<any>(null);
const duplicatingId = ref<number | null>(null);

// Prototyp review
const reviewAction = ref<"approve" | "reject">("approve");

// Materiały
const actualMaterialsCost = ref(0);
const materialsRefreshKey = ref(0);

// COMPUTED - Obliczenia kosztowe

const budgetMaterials = computed(
  () => Number(quotationsStore.approvedQuotation?.total_materials_cost) || 0
);

const budgetServices = computed(
  () => Number(quotationsStore.approvedQuotation?.total_services_cost) || 0
);

const productionTasks = computed(() => {
  const tasks = variant.value?.production_order?.services || [];
  return tasks.filter((t: any) => t.status !== "PLANNED");
});

const actualServicesCost = computed(() => {
  return productionTasks.value.reduce((sum: number, task: any) => {
    let cost = Number(task.actual_cost) || 0;
    // Ręczne przeliczenie dla IN_PROGRESS na poziomie wariantu nie jest wymagane,
    // ponieważ VariantRcpServices.vue zajmuje się wizualizacją w tabeli, ale ogólny zarys możemy tu policzyć.
    // Dla prostoty polegamy na zapisanych wartościach, a dynamiczne podsumowanie polega na tabeli
    return sum + cost;
  }, 0);
});

// LIFECYCLE

onMounted(async () => {
  const id = Array.isArray(route.params.id) ? route.params.id[0] : route.params.id;
  await variantsStore.fetchVariant(id);
  await loadQuotations();
});

// METHODS - Nawigacja & Odświeżanie

const goBack = () => router.back();

const refreshVariant = async () => {
  if (!variant.value?.id) return;
  await variantsStore.fetchVariant(variant.value.id);
};

// METHODS - Zmiany z boku i góry (Inline)

const handleInlineUpdate = async (field: string, value: any) => {
  if (!variant.value) return;
  inlineLoading.value = field;
  try {
    await variantsStore.updateVariant(variant.value.id, { [field]: value });
    await refreshVariant();
  } catch {
    alert(`Błąd podczas aktualizacji pola "${field}"`);
  } finally {
    inlineLoading.value = null;
  }
};

const handleInlineStatusChange = async (newStatus: string) => {
  if (!newStatus || !variant.value) return;
  statusLoading.value = true;
  try {
    await variantsStore.updateStatus(variant.value.id, newStatus);
    await refreshVariant();
  } catch {
    alert("Błąd podczas zmiany statusu");
  } finally {
    statusLoading.value = false;
  }
};

// METHODS - Wyceny

const loadQuotations = async () => {
  if (!variant.value?.id) return;
  loadingQuotations.value = true;
  try {
    const id = Array.isArray(route.params.id) ? route.params.id[0] : route.params.id;
    await quotationsStore.fetchQuotations(id);
    quotations.value = quotationsStore.quotations;
  } finally {
    loadingQuotations.value = false;
  }
};

const openCreateDialog = () => {
  editingQuotation.value = null;
  createQuotationDialog.value = true;
};

const editQuotation = (q: any) => {
  editingQuotation.value = q;
  createQuotationDialog.value = true;
};

const viewQuotation = (q: any) => {
  selectedQuotation.value = q;
  viewQuotationDialog.value = true;
};

const handleQuotationSaved = () => {
  createQuotationDialog.value = false;
  editingQuotation.value = null;
  loadQuotations();
};

const approveQuotation = async (q: any) => {
  try {
    await quotationsStore.approveQuotation(q.id);
    await loadQuotations();
    await refreshVariant();
  } catch {
    alert("Błąd podczas zatwierdzania wyceny");
  }
};

const confirmDeleteQuotation = (q: any) => {
  quotationToDelete.value = q;
  deleteQuotationDialog.value = true;
};

const handleDeleteQuotation = async () => {
  if (!quotationToDelete.value) return;
  try {
    await quotationsStore.deleteQuotation(quotationToDelete.value.id);
    deleteQuotationDialog.value = false;
    quotationToDelete.value = null;
    await loadQuotations();
  } catch {
    alert("Błąd podczas usuwania wyceny");
  }
};

const handleDuplicate = async (quotation: any) => {
  if (duplicatingId.value === quotation.id) return;
  duplicatingId.value = quotation.id;
  try {
    await quotationsStore.duplicateQuotation(quotation.id);
    await loadQuotations();
  } catch {
    alert("Błąd podczas duplikowania wyceny");
  } finally {
    duplicatingId.value = null;
  }
};

// METHODS - Materiały (Import)

const openExportDialog = (quotation: any) => {
  quotationToExport.value = quotation;
  exportMaterialsDialog.value = true;
};

const onMaterialsExported = () => {
  materialsRefreshKey.value++;
};

const onMaterialsLoaded = (data: { totalCost: number; summary: any }) => {
  actualMaterialsCost.value = data.totalCost;
};

// METHODS - Prototyp (Review)

const openReview = (action: "approve" | "reject") => {
  reviewAction.value = action;
  reviewDialog.value = true;
};

const submitReview = async (feedback: string) => {
  if (!variant.value) return;
  reviewLoading.value = true;
  try {
    await variantsStore.reviewPrototype(variant.value.id, reviewAction.value, feedback);
    reviewDialog.value = false;
    await refreshVariant();
  } catch {
    alert("Błąd podczas zmiany decyzji o prototypie");
  } finally {
    reviewLoading.value = false;
  }
};

// METHODS - Anulowanie

const submitCancel = async () => {
  if (!variant.value) return;
  statusLoading.value = true;
  try {
    await variantsStore.updateStatus(variant.value.id, "CANCELLED");
    cancelDialog.value = false;
    await refreshVariant();
  } catch {
    alert("Błąd podczas anulowania wariantu");
  } finally {
    statusLoading.value = false;
  }
};
</script>
