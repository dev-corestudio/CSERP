<template>
  <div class="sticky-sidebar">
    <!-- Szczegóły wariantu -->
    <v-card class="mb-4 bg-white border" elevation="1">
      <v-card-title class="d-flex align-center pb-2">
        <v-icon start size="small" color="primary">mdi-information-outline</v-icon>
        Szczegóły
      </v-card-title>
      <v-divider class="mb-2 mx-4" />

      <v-card-text class="pa-4 pt-0">
        <!-- Zamówienie -->
        <div class="detail-row">
          <div class="label">Zamówienie</div>
          <div class="value">
            <router-link
              :to="`/orders/${variant?.order_id}`"
              class="text-decoration-none text-primary font-weight-bold"
            >
              {{ variant?.order?.full_order_number }}
            </router-link>
          </div>
        </div>

        <!-- Klient -->
        <div class="detail-row">
          <div class="label">Klient</div>
          <div class="value text-truncate">
            <router-link
              v-if="variant?.order?.customer?.id"
              :to="`/customers/${variant.order.customer.id}`"
              class="text-decoration-none text-primary font-weight-medium"
            >
              {{ variant?.order?.customer?.name }}
            </router-link>
            <span v-else>-</span>
          </div>
        </div>

        <!-- Status wariantu -->
        <div class="detail-row">
          <div class="label">Status</div>
          <div class="value">
            <v-select
              :model-value="variant?.status"
              :items="metadataStore.variantStatuses"
              item-title="label"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              :loading="statusLoading"
              :disabled="
                variant?.status === 'CANCELLED' || variant?.status === 'COMPLETED'
              "
              class="status-select"
              @update:model-value="(val) => $emit('update-status', val)"
            >
              <template v-slot:selection="{ item }">
                <div class="d-flex align-center" :class="`text-${item.raw.color}`">
                  <v-icon size="small" class="mr-2">{{ item.raw.icon }}</v-icon>
                  <span class="font-weight-bold" style="font-size: 0.85rem">
                    {{ item.raw.label }}
                  </span>
                </div>
              </template>
              <template v-slot:item="{ props, item }">
                <v-list-item v-bind="props" density="compact">
                  <template v-slot:title>
                    <div class="d-flex align-center">
                      <v-icon :color="item.raw.color" size="small" class="mr-2">
                        {{ item.raw.icon }}
                      </v-icon>
                      <span style="font-size: 0.95rem; color: rgba(0, 0, 0, 0.87)">
                        {{ item.raw.label }}
                      </span>
                    </div>
                  </template>
                </v-list-item>
              </template>
            </v-select>
          </div>
        </div>

        <!-- Priorytet -->
        <div v-if="variant?.order?.priority" class="detail-row">
          <div class="label">Priorytet zamówienia</div>
          <div class="value">
            <v-chip
              size="small"
              :color="formatPriority(variant.order.priority).color"
              variant="flat"
              class="font-weight-bold"
            >
              <v-icon start size="x-small">{{
                formatPriority(variant.order.priority).icon
              }}</v-icon>
              {{ formatPriority(variant.order.priority).label }}
            </v-chip>
          </div>
        </div>

        <!-- Termin realizacji -->
        <div class="detail-row">
          <div class="label">Termin realizacji zamówienia</div>
          <div class="value font-weight-bold text-error">
            {{ formatDateOnly(variant?.order?.planned_delivery_date) }}
          </div>
        </div>

        <!-- Typ -->
        <div class="detail-row">
          <div class="label">Typ</div>
          <div class="value">
            <v-chip size="small" :color="variantTypeColor" variant="tonal">
              <v-icon start size="x-small">{{ variantTypeIcon }}</v-icon>
              {{ variantTypeLabel }}
            </v-chip>
          </div>
        </div>

        <!-- Ilość -->
        <div class="detail-row">
          <div class="label">Ilość</div>
          <div class="value">
            <inline-edit-field
              :model-value="String(variant?.quantity || 0)"
              type="number"
              icon-position="left"
              text-class="font-weight-bold text-body-1"
              :loading="inlineLoading === 'quantity'"
              @save="(val) => $emit('update-inline', 'quantity', val)"
            />
            <span class="text-caption text-medium-emphasis ml-1">szt.</span>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- ========================================== -->
    <!-- Sekcja TKW                                -->
    <!-- ========================================== -->
    <v-card v-if="!variant?.is_group" class="mb-4 bg-white border" elevation="1">
      <v-card-title class="d-flex align-center pb-2">
        <v-icon start size="small" color="deep-purple">mdi-cash-multiple</v-icon>
        TKW
      </v-card-title>
      <v-divider class="mb-2 mx-4" />

      <v-card-text class="pa-4 pt-0">
        <!-- TKW z wyceny -->
        <div class="detail-row">
          <div class="label">
            TKW z wyceny
            <v-tooltip text="Automatycznie obliczane z zatwierdzonej wyceny (materiały + usługi). Możesz wpisać wartość ręcznie." location="top">
              <template v-slot:activator="{ props: tooltipProps }">
                <v-icon v-bind="tooltipProps" size="x-small" class="ml-1 text-medium-emphasis">mdi-information-outline</v-icon>
              </template>
            </v-tooltip>
          </div>
          <div class="value">
            <inline-edit-field
              :model-value="variant?.tkw_z_wyceny != null ? String(variant.tkw_z_wyceny) : ''"
              type="number"
              icon-position="left"
              placeholder="–"
              text-class="font-weight-bold text-body-1"
              :loading="inlineLoading === 'tkw_z_wyceny'"
              @save="(val) => $emit('update-inline', 'tkw_z_wyceny', val || null)"
            />
            <span v-if="variant?.tkw_z_wyceny != null" class="text-caption text-medium-emphasis ml-1">zł</span>
          </div>
        </div>

        <!-- TKW rzeczywiste -->
        <div class="detail-row">
          <div class="label">TKW rzeczywiste</div>
          <div class="value">
            <inline-edit-field
              :model-value="variant?.tkw_rzeczywiste != null ? String(variant.tkw_rzeczywiste) : ''"
              type="number"
              icon-position="left"
              placeholder="Wpisz wartość..."
              text-class="font-weight-bold text-body-1"
              :loading="inlineLoading === 'tkw_rzeczywiste'"
              @save="(val) => $emit('update-inline', 'tkw_rzeczywiste', val || null)"
            />
            <span v-if="variant?.tkw_rzeczywiste != null" class="text-caption text-medium-emphasis ml-1">zł</span>
          </div>
        </div>

        <!-- Różnica TKW -->
        <div v-if="variant?.tkw_z_wyceny != null && variant?.tkw_rzeczywiste != null" class="mt-2">
          <v-chip
            size="small"
            :color="tkwDiff > 0 ? 'error' : tkwDiff < 0 ? 'success' : 'default'"
            variant="tonal"
            class="w-100 d-flex justify-center"
          >
            <v-icon start size="x-small">{{ tkwDiff > 0 ? 'mdi-trending-up' : tkwDiff < 0 ? 'mdi-trending-down' : 'mdi-minus' }}</v-icon>
            Odchyłka: {{ tkwDiff > 0 ? '+' : '' }}{{ formatCurrency(tkwDiff) }}
          </v-chip>
        </div>
      </v-card-text>
    </v-card>

    <!-- ========================================== -->
    <!-- Sekcja Prototypu                           -->
    <!-- ========================================== -->
    <v-card
      v-if="variant?.type === 'PROTOTYPE'"
      variant="outlined"
      class="mb-4 border"
      :class="variant.is_approved ? 'border-success' : 'border-warning'"
    >
      <v-card-text class="pa-4">
        <div
          class="text-subtitle-2 text-medium-emphasis mb-3 text-uppercase font-weight-bold"
        >
          Decyzja Klienta
        </div>

        <div class="mb-3">
          <div
            v-if="variant.is_approved"
            class="d-flex align-center text-success bg-green-lighten-5 pa-2 rounded"
          >
            <v-icon start color="success" size="large">mdi-check-decagram</v-icon>
            <div>
              <div class="font-weight-bold text-body-1">Prototyp zatwierdzony</div>
              <div class="text-caption">Można rozpocząć produkcję seryjną</div>
            </div>
          </div>
          <div
            v-else
            class="d-flex align-center text-orange-darken-2 bg-orange-lighten-5 pa-2 rounded"
          >
            <v-icon start size="large">mdi-clock-alert-outline</v-icon>
            <div>
              <div class="font-weight-bold text-body-1">Oczekuje na decyzję</div>
              <div class="text-caption">Wymaga akceptacji prototypu</div>
            </div>
          </div>
        </div>

        <!-- Edytowalne pole z uwagami -->
        <div class="mb-3 pa-2 bg-grey-lighten-5 rounded border d-flex flex-column">
          <div
            class="text-caption font-weight-bold text-medium-emphasis mb-1 d-flex align-center"
          >
            <v-icon size="x-small" class="mr-1">mdi-message-text-outline</v-icon>
            Notatki / Feedback
          </div>
          <inline-edit-field
            :model-value="variant?.feedback_notes || ''"
            type="textarea"
            icon-position="left"
            placeholder="Kliknij, aby dodać notatkę..."
            text-class="text-body-2 text-medium-emphasis"
            :loading="inlineLoading === 'feedback_notes'"
            @save="(val) => $emit('update-inline', 'feedback_notes', val)"
          />
        </div>

        <div
          v-if="!variant.is_approved && variant.status !== 'CANCELLED'"
          class="d-flex flex-wrap gap-2 mb-2"
        >
          <v-btn
            color="success"
            variant="elevated"
            size="small"
            prepend-icon="mdi-check"
            class="flex-grow-1"
            @click="$emit('open-review', 'approve')"
          >
            Zatwierdź
          </v-btn>
          <v-btn
            color="error"
            variant="outlined"
            size="small"
            prepend-icon="mdi-close"
            class="flex-grow-1"
            @click="$emit('open-review', 'reject')"
          >
            Odrzuć
          </v-btn>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import InlineEditField from "@/components/common/InlineEditField.vue";
import { useMetadataStore } from "@/stores/metadata";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import { useFormatters } from "@/composables/useFormatters";

const props = defineProps<{
  variant: any;
  statusLoading: boolean;
  inlineLoading: string | null;
}>();

defineEmits(["update-inline", "update-status", "open-review", "open-cancel"]);

const metadataStore = useMetadataStore();

// Import formatters
const { formatVariantType, formatPriority } = useStatusFormatter();
const { formatDateOnly, formatCurrency } = useFormatters();

const variantTypeLabel = computed(
  () => formatVariantType(props.variant?.type || "").label
);
const variantTypeColor = computed(
  () => formatVariantType(props.variant?.type || "").color
);
const variantTypeIcon = computed(() => formatVariantType(props.variant?.type || "").icon);

const tkwDiff = computed(() => {
  const rzeczywiste = Number(props.variant?.tkw_rzeczywiste) || 0;
  const zWyceny = Number(props.variant?.tkw_z_wyceny) || 0;
  return rzeczywiste - zWyceny;
});
</script>

<style scoped>
.sticky-sidebar {
  position: sticky;
  top: 80px;
  z-index: 1;
}

/* Zwężony select statusu */
.status-select {
  max-width: 170px;
  width: 100%;
}
.status-select :deep(.v-field__input) {
  min-height: 32px;
  padding-top: 4px;
  padding-bottom: 4px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f5f5f5;
  min-height: 32px;
}

/* Usuwamy border bottom z ostatniego elementu i dodajemy wrap w razie czego */
.detail-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.detail-row .label {
  font-size: 0.85rem;
  color: rgba(0, 0, 0, 0.6);
  font-weight: 500;
  min-width: 110px;
  flex-shrink: 0;
}

.detail-row .value {
  font-size: 0.95rem;
  font-weight: 500;
  text-align: right;
  flex-grow: 1;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  word-break: break-word; /* Zapewnia złamanie długich słów, np. długich maili */
}

/* Zapobieganie uciekaniu tekstu poza rodzica w flexboxie */
.text-truncate {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  max-width: 100%;
}

.border-success {
  border: 2px solid rgb(var(--v-theme-success)) !important;
}
.border-warning {
  border: 2px solid rgb(var(--v-theme-warning)) !important;
}

.gap-2 {
  gap: 8px;
}
</style>
