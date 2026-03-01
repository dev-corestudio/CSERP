<template>
  <v-container fluid>
    <!-- ===== Stan ładowania ===== -->
    <div v-if="loading" class="text-center py-12">
      <v-progress-circular indeterminate size="64" color="primary" />
      <p class="mt-4 text-h6">Ładowanie projektu...</p>
    </div>

    <!-- ===== Stan błędu ===== -->
    <v-alert v-else-if="error" type="error" variant="tonal" prominent class="mb-4">
      <v-alert-title>Błąd</v-alert-title>
      {{ error }}
      <template v-slot:append>
        <v-btn color="error" variant="outlined" @click="init">Spróbuj ponownie</v-btn>
      </template>
    </v-alert>

    <!-- ===== Główna treść ===== -->
    <template v-else-if="project">
      <!-- NAGŁÓWEK -->
      <page-header
        :title="project.full_project_number"
        :subtitle="`Projekt • ${project.customer?.name || 'Klient'}`"
        icon="mdi-clipboard-text"
        :icon-color="formatProjectStatus(project.overall_status).color"
        :breadcrumbs="[
          { title: 'Projekty', to: '/projects', disabled: false },
          { title: project.full_project_number, disabled: true },
        ]"
      >
        <template #actions>
          <v-btn
            variant="outlined"
            prepend-icon="mdi-arrow-left"
            class="mr-2"
            @click="$router.push('/projects')"
          >
            Wróć
          </v-btn>
          <v-btn
            color="deep-purple"
            variant="outlined"
            prepend-icon="mdi-layers"
            class="mr-2"
            @click="seriesDialog = true"
          >
            Serie
          </v-btn>
          <v-btn prepend-icon="mdi-pencil" color="primary" @click="openEditProject">
            Edytuj
          </v-btn>
        </template>
      </page-header>

      <v-row>
        <!-- ============================================================ -->
        <!-- LEWA KOLUMNA (8) – opis + finanse + warianty                 -->
        <!-- ============================================================ -->
        <v-col cols="12" md="8">
          <!-- Opis projektu -->
          <project-description :description="project.description" />

          <!-- Lista grup i wariantów -->
          <project-variants-grid
            class="mt-6"
            :variants="project.variants || []"
            @add-group="openAddGroup"
            @add-child="openAddChild"
            @view="viewVariant"
            @edit="openEdit"
            @duplicate="openDuplicate"
            @delete="handleVariantDelete"
            @delete-group-force="handleGroupDeleteForce"
          />
        </v-col>

        <!-- ============================================================ -->
        <!-- PRAWA KOLUMNA (4) – panel serii + sidebar                    -->
        <!-- ============================================================ -->
        <v-col cols="12" md="4">
            <!-- Szczegóły + Klient -->
          <project-sidebar
            :project="project"
            :inline-loading="inlineLoading"
            @update-inline="handleInlineUpdate"
          />
        </v-col>
      </v-row>
    </template>

    <!-- ===================================================================
         DIALOGI
    ==================================================================== -->

    <!--
      Dialog tworzenia/edycji grupy lub wariantu.
      Prop `mode` steruje zachowaniem:
        'group'        → tworzy grupę (POST /projects/{id}/variants)
        'variant'      → tworzy wariant jako dziecko (POST /projects/{id}/variants/{parent}/children)
        'edit-group'   → edytuje grupę (PUT /variants/{id})
        'edit-variant' → edytuje wariant (PUT /variants/{id})
    -->
    <variant-form-dialog
      v-model="variantDialog"
      :mode="variantDialogMode"
      :entity-id="project?.id"
      :parent="variantDialogParent"
      :item="variantDialogItem"
      :existing-variants="project?.variants || []"
      @saved="handleVariantSaved"
    />

    <!-- Dialog edycji projektu -->
    <project-form-dialog
      v-model="editProjectDialog"
      :project="project"
      @saved="handleProjectSaved"
    />

    <!--
      Dialog duplikowania grupy lub wariantu.
      Komponent sam wykrywa typ źródła (is_group || quantity===0).
    -->
    <variant-duplicate-dialog
      v-model="duplicateDialog"
      :source-variant="duplicatingVariant"
      :existing-variants="project?.variants || []"
      @saved="handleDuplicateSaved"
    />

    <!-- Dialog tworzenia nowej serii projektu -->
    <create-series-dialog
      v-model="createSeriesDialog"
      :source-project="
        project
          ? {
              id: project.id,
              project_number: project.project_number,
              series: project.series,
              full_project_number: project.full_project_number,
            }
          : null
      "
      @saved="handleSeriesCreated"
    />

    <!-- Dialog serii projektu -->
    <v-dialog v-model="seriesDialog" max-width="600" scrollable>
      <series-list-panel
        v-if="seriesDialog"
        ref="seriesPanelRef"
        mode="project"
        :current-id="project?.id"
        :entity-number="project?.project_number"
        :closable="true"
        @create-series="openCreateSeriesDialog"
        @close="seriesDialog = false"
      />
    </v-dialog>

    <!-- Snackbar powiadomień -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="4000"
      location="bottom right"
    >
      <v-icon start>{{ snackbar.icon }}</v-icon>
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">Zamknij</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

// Serwisy / narzędzia
import api from "@/services/api";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import { useRecentProjects } from "@/composables/useRecentProjects";
import { useVariantsStore } from "@/stores/variants";

// Komponenty layout
import PageHeader from "@/components/layout/PageHeader.vue";
import ProjectDescription from "@/components/projects/ProjectDescription.vue";
import ProjectVariantsGrid from "@/components/variants/VariantsGrid.vue";
import ProjectSidebar from "@/components/projects/ProjectSidebar.vue";

// Dialogi
import VariantFormDialog from "@/components/variants/VariantFormDialog.vue";
import VariantDuplicateDialog from "@/components/variants/VariantDuplicateDialog.vue";
import ProjectFormDialog from "@/components/projects/ProjectFormDialog.vue";
import SeriesListPanel from "@/components/common/SeriesListPanel.vue";
import CreateSeriesDialog from "@/components/projects/CreateSeriesDialog.vue";

// ─── Instancje ────────────────────────────────────────────────────────────────

const route = useRoute();
const router = useRouter();
const variantsStore = useVariantsStore();
const { formatProjectStatus } = useStatusFormatter();
const { track: trackRecentProject } = useRecentProjects();

// ─── Referencje do komponentów ────────────────────────────────────────────────

const seriesPanelRef = ref<InstanceType<typeof SeriesListPanel> | null>(null);

// ─── Stan główny ──────────────────────────────────────────────────────────────

const loading = ref(true);
const error = ref<string | null>(null);
const project = ref<any>(null);
const inlineLoading = ref<string | null>(null);

// ─── Stan — dialogi wariantów ─────────────────────────────────────────────────

const variantDialog = ref(false);

/**
 * Tryb dialogu formularza:
 *   'group'        — tworzenie nowej grupy top-level
 *   'variant'      — tworzenie wariantu jako dziecko grupy/wariantu
 *   'edit-group'   — edycja istniejącej grupy
 *   'edit-variant' — edycja istniejącego wariantu
 */
const variantDialogMode = ref<"group" | "variant" | "edit-group" | "edit-variant">(
  "group"
);

/** Rodzic wariantu (tylko przy mode='variant') */
const variantDialogParent = ref<any>(null);

/** Element do edycji (tylko przy mode='edit-*') */
const variantDialogItem = ref<any>(null);

// ─── Stan — dialog duplikowania ───────────────────────────────────────────────

const duplicateDialog = ref(false);
const duplicatingVariant = ref<any>(null);

// ─── Stan — pozostałe dialogi ─────────────────────────────────────────────────

const editProjectDialog = ref(false);
const createSeriesDialog = ref(false);
const seriesDialog = ref(false);

// ─── Snackbar ─────────────────────────────────────────────────────────────────

const snackbar = ref({
  show: false,
  message: "",
  color: "success",
  icon: "mdi-check-circle",
});

// ─── Pomocnicza funkcja snackbar ──────────────────────────────────────────────

function showSnackbar(
  message: string,
  color: "success" | "error" | "warning" | "info" = "success"
) {
  snackbar.value = {
    show: true,
    message,
    color,
    icon: color === "success" ? "mdi-check-circle" : "mdi-alert-circle",
  };
}

// ─── Pobieranie danych ────────────────────────────────────────────────────────

/**
 * Pobiera dane projektu wraz z zagnieżdżonymi wariantami.
 * Backend zwraca płaską listę variants[] z is_group/quantity jako dyskryminator.
 */
async function fetchProject() {
  loading.value = true;
  error.value = null;
  try {
    const res = await api.get(`/projects/${route.params.id}`);
    project.value = res.data.data || res.data;
    trackRecentProject(project.value);
  } catch (err: any) {
    error.value = err.response?.data?.message || "Nie udało się pobrać projektu";
  } finally {
    loading.value = false;
  }
}

/** Pełna inicjalizacja — projekt */
async function init() {
  await fetchProject();
}

// ─── Inline update (sidebar) ──────────────────────────────────────────────────

async function handleInlineUpdate(field: string, value: any) {
  if (!project.value) return;
  inlineLoading.value = field;
  try {
    const res = await api.patch(`/projects/${project.value.id}`, { [field]: value });
    project.value = { ...project.value, ...res.data };
  } catch (err: any) {
    alert(err.response?.data?.message || `Błąd aktualizacji pola "${field}"`);
  } finally {
    inlineLoading.value = null;
  }
}

// ─── Akcje UI — projekt ────────────────────────────────────────────────────

const openEditProject = () => {
  editProjectDialog.value = true;
};

const openCreateSeriesDialog = () => {
  createSeriesDialog.value = true;
};

// ─── Akcje UI — warianty / grupy ─────────────────────────────────────────────

/**
 * Otwiera dialog tworzenia nowej GRUPY top-level.
 * Wywoływane przez @add-group z ProjectVariantsGrid.
 * Backend: POST /projects/{id}/variants (z quantity=0 automatycznie)
 */
const openAddGroup = () => {
  variantDialogMode.value = "group";
  variantDialogParent.value = null;
  variantDialogItem.value = null;
  variantDialog.value = true;
};

/**
 * Otwiera dialog tworzenia WARIANTU jako dziecka grupy lub innego wariantu.
 * Wywoływane przez @add-child z ProjectVariantsGrid.
 * Backend: POST /projects/{id}/variants/{parentId}/children
 *
 * @param parent - Obiekt grupy lub wariantu-rodzica
 */
const openAddChild = (parent: any) => {
  variantDialogMode.value = "variant";
  variantDialogParent.value = parent;
  variantDialogItem.value = null;
  variantDialog.value = true;
};

/**
 * Otwiera dialog edycji — automatycznie wykrywa czy to grupa czy wariant.
 * Wywoływane przez @edit z ProjectVariantsGrid.
 *
 * Discriminator: is_group === true LUB quantity === 0 → grupa
 */
const openEdit = (item: any) => {
  const isGroup = item.is_group === true || item.quantity === 0;
  variantDialogMode.value = isGroup ? "edit-group" : "edit-variant";
  variantDialogParent.value = null;
  variantDialogItem.value = item;
  variantDialog.value = true;
};

/**
 * Otwiera dialog duplikowania grupy lub wariantu.
 * Wywoływane przez @duplicate z ProjectVariantsGrid.
 * VariantDuplicateDialog sam wykrywa typ i pokazuje odpowiedni UI.
 */
const openDuplicate = (item: any) => {
  duplicatingVariant.value = item;
  duplicateDialog.value = true;
};

/** Nawiguje do widoku szczegółów wariantu */
const viewVariant = (variantId: number) => {
  router.push({
    name: "VariantDetail",
    params: { projectId: project.value.id, id: variantId },
  });
};

// ─── Usuwanie wariantów ───────────────────────────────────────────────────────

/**
 * Usuwa pojedynczy wariant (bez dzieci).
 * Wywoływane przez @delete z ProjectVariantsGrid.
 *
 * Jeśli backend zwróci 422 (wariant ma dzieci), informuje użytkownika
 * żeby skorzystał z opcji "Usuń grupę" w menu grupy.
 */
const handleVariantDelete = async (variant: any) => {
  const label = variant.is_group
    ? `grupę ${variant.variant_number} (${variant.name})`
    : `wariant ${variant.variant_number} (${variant.name})`;

  if (!confirm(`Czy na pewno chcesz usunąć ${label}?`)) return;

  try {
    await variantsStore.deleteVariant(variant.id, false);
    showSnackbar(`Usunięto ${variant.variant_number}`);
    await init();
  } catch (err: any) {
    if (err.response?.status === 422) {
      // Backend odmówił — to grupa z dziećmi
      alert(
        `Nie można usunąć grupy ${variant.variant_number} — zawiera warianty.\n` +
          `Użyj opcji "Usuń grupę" z menu grupy, aby usunąć razem z całą zawartością.`
      );
    } else {
      alert("Nie udało się usunąć: " + (err.response?.data?.message || "Nieznany błąd"));
    }
  }
};

/**
 * Usuwa grupę RAZEM z wszystkimi dziećmi (force=true).
 * Wywoływane przez @delete-group-force z ProjectVariantsGrid,
 * po wewnętrznym potwierdzeniu wewnątrz komponentu.
 *
 * Backend: DELETE /variants/{id}?force=true
 */
const handleGroupDeleteForce = async (group: any) => {
  try {
    await variantsStore.deleteVariant(group.id, true);
    showSnackbar(`Grupa ${group.variant_number} i jej warianty zostały usunięte`);
    await init();
  } catch (err: any) {
    alert(
      "Nie udało się usunąć grupy: " + (err.response?.data?.message || "Nieznany błąd")
    );
  }
};

// ─── Callbacki zapisu ─────────────────────────────────────────────────────────

const handleProjectSaved = async () => {
  await init();
  showSnackbar("Projekt zaktualizowany");
};

const handleVariantSaved = async () => {
  await init();
  showSnackbar("Zapisano pomyślnie");
};

const handleDuplicateSaved = async () => {
  await init();
  showSnackbar("Zduplikowano pomyślnie");
};

async function handleSeriesCreated(result: any) {
  const summary = result?.summary;
  const newProjectId = summary?.new_project_id;
  const newNumber = summary?.new_full_project_number;

  showSnackbar(
    `Seria ${newNumber} utworzona! (${summary?.variants_created || 0} wariantów)`
  );

  // Odśwież panel serii natychmiast
  seriesPanelRef.value?.loadSeries();

  // Przekieruj do nowej serii po chwili
  if (newProjectId) {
    setTimeout(() => router.push(`/projects/${newProjectId}`), 900);
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(init);

/**
 * Nasłuchuje zmian route.params.id.
 *
 * Gdy użytkownik klika inną serię w SeriesListPanel, router.push zmienia
 * tylko parametr — Vue nie odmontowuje komponentu, więc onMounted nie odpala.
 * Ten watch zapewnia pełne przeładowanie danych przy każdej zmianie ID.
 */
watch(
  () => route.params.id,
  (newId, oldId) => {
    if (newId && newId !== oldId) {
      init();
    }
  }
);
</script>
