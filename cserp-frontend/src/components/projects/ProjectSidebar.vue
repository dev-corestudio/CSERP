<template>
  <div class="sticky-sidebar">
    <!-- ─── Szczegóły projektu ─── -->
    <v-card elevation="1" class="mb-4 bg-white border">
      <v-card-title class="d-flex align-center pb-2">
        <v-icon start size="small" color="primary">mdi-information-outline</v-icon>
        Szczegóły
      </v-card-title>
      <v-divider class="mb-2 mx-4" />

      <v-card-text class="pa-4 pt-0">
        <!-- Status -->
        <div class="detail-row">
          <div class="label">Status</div>
          <div class="value">
            <v-select
              :model-value="project.overall_status"
              :items="metadataStore.projectStatuses"
              item-title="label"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              :loading="inlineLoading === 'overall_status'"
              class="status-select"
              @update:model-value="(val) => $emit('update-inline', 'overall_status', val)"
            >
              <template v-slot:selection="{ item }">
                <div
                  class="d-flex align-center"
                  :class="`text-${formatProjectStatus(item.value).color}`"
                >
                  <v-icon size="small" class="mr-2">{{
                    formatProjectStatus(item.value).icon
                  }}</v-icon>
                  <span class="font-weight-bold" style="font-size: 0.85rem">
                    {{ formatProjectStatus(item.value).label }}
                  </span>
                </div>
              </template>
              <template v-slot:item="{ props: itemProps, item }">
                <v-list-item v-bind="itemProps" density="compact">
                  <template v-slot:title>
                    <div class="d-flex align-center">
                      <v-icon
                        :color="formatProjectStatus(item.value).color"
                        size="small"
                        class="mr-2"
                      >
                        {{ formatProjectStatus(item.value).icon }}
                      </v-icon>
                      <span style="font-size: 0.95rem; color: rgba(0, 0, 0, 0.87)">
                        {{ formatProjectStatus(item.value).label }}
                      </span>
                    </div>
                  </template>
                </v-list-item>
              </template>
            </v-select>
          </div>
        </div>

        <!-- Płatność -->
        <div class="detail-row">
          <div class="label">Płatność</div>
          <div class="value">
            <v-select
              :model-value="project.payment_status"
              :items="metadataStore.paymentStatuses"
              item-title="label"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              :loading="inlineLoading === 'payment_status'"
              class="status-select"
              @update:model-value="(val) => $emit('update-inline', 'payment_status', val)"
            >
              <template v-slot:selection="{ item }">
                <div
                  class="d-flex align-center"
                  :class="`text-${formatPaymentStatus(item.value).color}`"
                >
                  <v-icon size="small" class="mr-2">{{
                    formatPaymentStatus(item.value).icon
                  }}</v-icon>
                  <span class="font-weight-bold" style="font-size: 0.85rem">
                    {{ formatPaymentStatus(item.value).label }}
                  </span>
                </div>
              </template>
              <template v-slot:item="{ props: itemProps, item }">
                <v-list-item v-bind="itemProps" density="compact">
                  <template v-slot:title>
                    <div class="d-flex align-center">
                      <v-icon
                        :color="formatPaymentStatus(item.value).color"
                        size="small"
                        class="mr-2"
                      >
                        {{ formatPaymentStatus(item.value).icon }}
                      </v-icon>
                      <span style="font-size: 0.95rem; color: rgba(0, 0, 0, 0.87)">
                        {{ formatPaymentStatus(item.value).label }}
                      </span>
                    </div>
                  </template>
                </v-list-item>
              </template>
            </v-select>
          </div>
        </div>

        <!-- Priorytet -->
        <div class="detail-row">
          <div class="label">Priorytet</div>
          <div class="value">
            <v-select
              :model-value="String(project.priority || 'normal').toLowerCase()"
              :items="metadataStore.projectPriorities"
              item-title="label"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              :loading="inlineLoading === 'priority'"
              class="status-select"
              @update:model-value="(val) => $emit('update-inline', 'priority', val)"
            >
              <template v-slot:selection="{ item }">
                <div
                  class="d-flex align-center"
                  :class="`text-${formatPriority(item.value).color}`"
                >
                  <v-icon size="small" class="mr-2">{{
                    formatPriority(item.value).icon
                  }}</v-icon>
                  <span class="font-weight-bold" style="font-size: 0.85rem">
                    {{ formatPriority(item.value).label }}
                  </span>
                </div>
              </template>
              <template v-slot:item="{ props: itemProps, item }">
                <v-list-item v-bind="itemProps" density="compact">
                  <template v-slot:title>
                    <div class="d-flex align-center">
                      <v-icon
                        :color="formatPriority(item.value).color"
                        size="small"
                        class="mr-2"
                      >
                        {{ formatPriority(item.value).icon }}
                      </v-icon>
                      <span style="font-size: 0.95rem; color: rgba(0, 0, 0, 0.87)">
                        {{ formatPriority(item.value).label }}
                      </span>
                    </div>
                  </template>
                </v-list-item>
              </template>
            </v-select>
          </div>
        </div>

        <!-- Opiekun projektu -->
        <div class="detail-row">
          <div class="label">Opiekun</div>
          <div class="value">
            <v-autocomplete
              :model-value="project.assigned_to"
              :items="guardians"
              item-title="name"
              item-value="id"
              density="compact"
              variant="outlined"
              hide-details
              :loading="inlineLoading === 'assigned_to' || loadingGuardians"
              placeholder="Brak"
              no-data-text="Brak opiekunów"
              clearable
              class="status-select"
              @update:model-value="(val) => $emit('update-inline', 'assigned_to', val)"
            />
          </div>
        </div>

        <!-- Data utworzenia (Readonly) -->
        <div class="detail-row">
          <div class="label">Utworzone</div>
          <div class="value font-weight-medium">
            {{ formatDate(project.created_at) }}
          </div>
        </div>

        <!-- Termin realizacji -->
        <div class="detail-row">
          <div class="label">Termin realizacji</div>
          <div class="value">
            <inline-edit-field
              :model-value="
                project.planned_delivery_date
                  ? project.planned_delivery_date.slice(0, 10)
                  : ''
              "
              type="date"
              :loading="inlineLoading === 'planned_delivery_date'"
              :show-edit-icon="true"
              icon-position="left"
              @save="
                (val) => $emit('update-inline', 'planned_delivery_date', val || null)
              "
            >
              <template #display="{ value }">
                <span
                  class="font-weight-bold"
                  :class="value ? 'text-error' : 'text-medium-emphasis'"
                >
                  {{ formatDate(value) || "Nie ustalono" }}
                </span>
              </template>
            </inline-edit-field>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── Klient ─── -->
    <v-card v-if="project.customer" elevation="1" class="mb-4 bg-white border">
      <v-card-title class="d-flex align-center pb-2">
        <v-icon start size="small" color="primary">mdi-account-outline</v-icon>
        Klient
      </v-card-title>
      <v-divider class="mb-2 mx-4" />

      <v-card-text class="pa-4 pt-0">
        <div class="detail-row">
          <div class="label">Nazwa</div>
          <div class="value">
            <router-link
              :to="`/customers/${project.customer.id}`"
              class="text-decoration-none text-primary font-weight-bold"
            >
              {{ project.customer.name }}
            </router-link>
          </div>
        </div>

        <div class="detail-row">
          <div class="label">Typ</div>
          <div class="value">
            <v-chip size="x-small" variant="tonal">
              {{ project.customer.type }}
            </v-chip>
          </div>
        </div>

        <div v-if="project.customer.email" class="detail-row">
          <div class="label">Email</div>
          <div class="value text-truncate text-caption">
            {{ project.customer.email }}
          </div>
        </div>

        <div v-if="project.customer.phone" class="detail-row">
          <div class="label">Telefon</div>
          <div class="value text-caption">
            {{ project.customer.phone }}
          </div>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import InlineEditField from "@/components/common/InlineEditField.vue";
import { useMetadataStore } from "@/stores/metadata";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import api from "@/services/api";

const props = defineProps<{
  project: any;
  inlineLoading: string | null;
}>();

defineEmits(["update-inline"]);

const metadataStore = useMetadataStore();
const { formatProjectStatus, formatPaymentStatus, formatPriority } = useStatusFormatter();

const guardians = ref<any[]>([]);
const loadingGuardians = ref(false);

const fetchGuardians = async () => {
  loadingGuardians.value = true;
  try {
    const { data } = await api.get("/users/for-select");
    guardians.value = data || [];
  } catch {
    guardians.value = [];
  } finally {
    loadingGuardians.value = false;
  }
};

onMounted(fetchGuardians);

const formatDate = (date: string | null): string | null => {
  if (!date) return null;
  return new Date(date).toLocaleDateString("pl-PL");
};
</script>

<style scoped>
.sticky-sidebar {
  position: sticky;
  top: 80px;
  z-index: 1;
}

/* Klasy dla nowych, wbudowanych Selectów */
.status-select {
  max-width: 180px;
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
  word-break: break-word;
}

.text-truncate {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  max-width: 100%;
}
</style>
