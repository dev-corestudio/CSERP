<template>
  <div>
    <!-- Panel Materiałów (Lista + Przyciski) -->
    <materials-panel
      :materials="materials"
      :summary="summary"
      :total-cost="totalCost"
      :loading="loading"
      :readonly="readonly"
      @add="openAddDialog"
      @edit="editMaterial"
      @delete="confirmDeleteMaterial"
      @status-change="handleStatusChange"
      @mark-all-ordered="handleMarkAllOrdered"
      @import-batch="batchDialog = true"
      @updateItem="handleInlineUpdate"
      @bulk-delete="handleBulkDelete"
      @bulk-status-change="handleBulkStatusChange"
    />

    <!-- Dialog Dodawania/Edycji Pojedynczego Materiału -->
    <material-form-dialog
      v-model="materialFormDialog"
      :material="editingMaterial"
      @saved="handleSaved"
    />

    <!-- Dialog Masowego Importu -->
    <batch-material-add-dialog
      v-model="batchDialog"
      :loading="batchLoading"
      @save="handleBatchSave"
    />

    <!-- Dialog Potwierdzenia Usunięcia -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title class="bg-error text-white d-flex align-center">
          <v-icon start color="white">mdi-alert-circle</v-icon>
          Usuń materiał
        </v-card-title>
        <v-card-text class="pt-6">
          Czy na pewno chcesz usunąć materiał:
          <div class="font-weight-bold mt-2">
            {{ deletingMaterial?.assortment?.name || "Wybrany materiał" }}
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            Ta operacja jest nieodwracalna.
          </div>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Anuluj</v-btn>
          <v-btn color="error" variant="elevated" @click="handleDelete">Usuń</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { variantMaterialService } from "@/services/variantMaterialService";

// Komponenty
import MaterialsPanel from "@/components/materials/MaterialsPanel.vue";
import MaterialFormDialog from "@/components/materials/MaterialFormDialog.vue";
import BatchMaterialAddDialog from "@/components/materials/BatchMaterialAddDialog.vue";

const props = defineProps({
  variantId: {
    type: [Number, String],
    required: true,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["updated", "data-loaded"]);

// --- STATE ---
const loading = ref(false);
const materials = ref<any[]>([]);
const summary = ref<any>(null);
const totalCost = ref(0);

// Dialogs State
const materialFormDialog = ref(false);
const editingMaterial = ref<any>(null);

const batchDialog = ref(false);
const batchLoading = ref(false);

const deleteDialog = ref(false);
const deletingMaterial = ref<any>(null);

// --- LIFECYCLE & WATCHERS ---

watch(() => props.variantId, loadMaterials, { immediate: true });

// --- DATA LOADING ---

async function loadMaterials() {
  if (!props.variantId) return;
  loading.value = true;
  try {
    const data = await variantMaterialService.getAll(props.variantId);

    if (data && typeof data === "object") {
      materials.value = Array.isArray(data.materials)
        ? data.materials
        : Array.isArray(data)
        ? data
        : [];
      summary.value = data.summary || null;

      // POPRAWKA: Obliczamy sumę ręcznie na froncie, aby była zgodna z tabelą
      // (zamiast polegać na data.total_cost, który może nie być odświeżony w backendzie)
      totalCost.value = materials.value.reduce((sum, item) => {
        return sum + Number(item.quantity || 0) * Number(item.unit_price || 0);
      }, 0);

      // Emituj dane do rodzica (VariantDetail) z poprawną sumą
      emit("data-loaded", {
        totalCost: totalCost.value,
        summary: summary.value,
      });
    } else {
      materials.value = [];
      totalCost.value = 0;
      emit("data-loaded", { totalCost: 0, summary: null });
    }
  } catch (err) {
    console.error("Błąd ładowania materiałów wariantu:", err);
    materials.value = [];
  } finally {
    loading.value = false;
  }
}

// --- SINGLE MATERIAL ACTIONS ---

function openAddDialog() {
  editingMaterial.value = null;
  materialFormDialog.value = true;
}

function editMaterial(material: any) {
  editingMaterial.value = material;
  materialFormDialog.value = true;
}

async function handleSaved(payload: any) {
  try {
    if (editingMaterial.value?.id) {
      await variantMaterialService.update(editingMaterial.value.id, payload);
    } else {
      await variantMaterialService.create(props.variantId, payload);
    }
    materialFormDialog.value = false;
    await loadMaterials();
    emit("updated");
  } catch (err: any) {
    console.error("Błąd zapisu materiału:", err);
    alert(err.response?.data?.message || "Błąd zapisu materiału");
  }
}

// --- BATCH IMPORT ACTIONS ---

async function handleBatchSave(items: any[]) {
  if (!items || items.length === 0) return;

  batchLoading.value = true;
  try {
    await variantMaterialService.batchCreate(props.variantId, items);
    batchDialog.value = false;
    await loadMaterials();
    emit("updated");
  } catch (err: any) {
    console.error("Błąd importu masowego:", err);
    alert(err.response?.data?.message || "Wystąpił błąd podczas importu danych.");
  } finally {
    batchLoading.value = false;
  }
}

// --- DELETE ACTIONS ---

function confirmDeleteMaterial(material: any) {
  deletingMaterial.value = material;
  deleteDialog.value = true;
}

async function handleDelete() {
  if (!deletingMaterial.value) return;

  try {
    await variantMaterialService.delete(deletingMaterial.value.id);
    deleteDialog.value = false;
    deletingMaterial.value = null;
    await loadMaterials();
    emit("updated");
  } catch (err) {
    console.error("Błąd usuwania materiału:", err);
    alert("Nie udało się usunąć materiału.");
  }
}

// --- BULK / STATUS ACTIONS ---

async function handleStatusChange(material: any, newStatus: string) {
  try {
    await variantMaterialService.updateStatus(material.id, newStatus);
    await loadMaterials();
    emit("updated");
  } catch (err) {
    console.error("Błąd zmiany statusu:", err);
    alert("Nie udało się zmienić statusu.");
  }
}

async function handleMarkAllOrdered() {
  if (
    !confirm(
      "Czy na pewno chcesz oznaczyć wszystkie niezamówione materiały jako ZAMÓWIONE?"
    )
  )
    return;

  try {
    await variantMaterialService.markAllOrdered(props.variantId);
    await loadMaterials();
    emit("updated");
  } catch (err) {
    console.error("Błąd masowego zamawiania:", err);
    alert("Wystąpił błąd.");
  }
}

async function handleInlineUpdate(material: any, changes: any) {
  try {
    Object.assign(material, changes);
    await variantMaterialService.update(material.id, changes);
    emit("updated");
    // Po aktualizacji inline warto odświeżyć sumy, jeśli zmieniono cenę/ilość
    if (changes.quantity !== undefined || changes.unit_price !== undefined) {
      await loadMaterials();
    }
  } catch (err: any) {
    console.error("Błąd aktualizacji:", err);
    alert(err.response?.data?.message || "Nie udało się zaktualizować pola.");
    await loadMaterials();
  }
}

async function handleBulkDelete(ids: number[]) {
  try {
    await Promise.all(ids.map((id) => variantMaterialService.delete(id)));
    await loadMaterials();
    emit("updated");
  } catch (e) {
    console.error(e);
    alert("Błąd podczas usuwania");
  }
}

async function handleBulkStatusChange(ids: number[], status: string) {
  try {
    await Promise.all(ids.map((id) => variantMaterialService.updateStatus(id, status)));
    await loadMaterials();
    emit("updated");
  } catch (e) {
    console.error(e);
    alert("Błąd zmiany statusu");
  }
}

defineExpose({ loadMaterials });
</script>
