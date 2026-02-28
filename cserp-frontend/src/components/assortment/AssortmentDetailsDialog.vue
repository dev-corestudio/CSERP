<template>
  <v-dialog 
    :model-value="modelValue" 
    @update:model-value="$emit('update:modelValue', $event)" 
    max-width="900" 
    scrollable
  >
    <v-card v-if="item">
      <v-card-title class="bg-primary text-white d-flex align-center py-4">
        <v-icon start color="white" size="large">mdi-information</v-icon>
        <span class="text-h5">Szczegóły pozycji</span>
        <v-spacer />
        <v-btn
          icon="mdi-pencil"
          color="white"
          variant="text"
          @click="$emit('edit', item)"
        />
        <v-btn
          icon="mdi-close"
          color="white"
          variant="text"
          @click="$emit('close')"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="pt-6">
        <!-- Basic Info -->
        <v-row>
          <v-col cols="12" md="6">
            <v-card variant="outlined">
              <v-card-text>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Typ</div>
                  <v-chip 
                    :color="item.type === 'material' ? 'blue' : 'purple'" 
                    size="small"
                  >
                    <v-icon start size="small">
                      {{ item.type === 'material' ? 'mdi-cube' : 'mdi-wrench' }}
                    </v-icon>
                    {{ item.type === 'material' ? 'Materiał' : 'Usługa' }}
                  </v-chip>
                </div>

                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Nazwa</div>
                  <div class="text-h6 font-weight-bold">{{ item.name }}</div>
                </div>

                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Kategoria</div>
                  <v-chip size="small" variant="outlined">
                    {{ item.category }}
                  </v-chip>
                </div>

                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Status</div>
                  <v-chip 
                    :color="item.is_active ? 'success' : 'grey'" 
                    size="small"
                  >
                    {{ item.is_active ? 'Aktywna' : 'Nieaktywna' }}
                  </v-chip>
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card variant="outlined">
              <v-card-text>
                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Cena domyślna</div>
                  <div class="text-h5 font-weight-bold text-success">
                    {{ formatCurrency(item.default_price) }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    za {{ item.unit }}
                  </div>
                </div>

                <div class="mb-4">
                  <div class="text-caption text-medium-emphasis">Jednostka</div>
                  <div class="text-h6">{{ item.unit }}</div>
                </div>

                <div>
                  <div class="text-caption text-medium-emphasis">Utworzono</div>
                  <div>{{ formatDate(item.created_at) }}</div>
                </div>

                <div class="mt-2">
                  <div class="text-caption text-medium-emphasis">Zaktualizowano</div>
                  <div>{{ formatDate(item.updated_at) }}</div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Description -->
        <v-card v-if="item.description" variant="outlined" class="mt-4">
          <v-card-text>
            <div class="text-caption text-medium-emphasis mb-2">Opis</div>
            <div class="text-body-2">{{ item.description }}</div>
          </v-card-text>
        </v-card>

        <!-- History -->
        <v-card class="mt-6" elevation="2">
          <v-card-title class="bg-grey-lighten-4">
            <v-icon start>mdi-history</v-icon>
            Historia zmian
          </v-card-title>

          <v-card-text class="pa-0">
            <div v-if="loadingHistory" class="text-center py-6">
              <v-progress-circular indeterminate color="primary" />
            </div>

            <v-timeline v-else-if="history.length > 0" density="compact" side="end">
              <v-timeline-item
                v-for="entry in history"
                :key="entry.id"
                :dot-color="getActionColor(entry.action)"
                size="small"
              >
                <template v-slot:icon>
                  <v-icon size="small" color="white">
                    {{ getActionIcon(entry.action) }}
                  </v-icon>
                </template>

                <v-card variant="outlined" class="mb-2">
                  <v-card-text class="py-2">
                    <div class="d-flex align-center mb-1">
                      <v-chip :color="getActionColor(entry.action)" size="x-small" class="mr-2">
                        {{ getActionLabel(entry.action) }}
                      </v-chip>
                      <span class="text-caption text-medium-emphasis">
                        {{ formatDate(entry.created_at) }}
                      </span>
                      <v-spacer />
                      <v-chip size="x-small" variant="outlined" v-if="entry.user">
                        <v-icon start size="x-small">mdi-account</v-icon>
                        {{ entry.user.name }}
                      </v-chip>
                    </div>

                    <div v-if="entry.description" class="text-body-2">
                      {{ entry.description }}
                    </div>

                    <!-- Change Details -->
                    <div v-if="entry.old_values && entry.new_values" class="mt-2">
                      <v-expansion-panels variant="accordion" density="compact">
                        <v-expansion-panel>
                          <v-expansion-panel-title class="text-caption">
                            Szczegóły zmian
                          </v-expansion-panel-title>
                          <v-expansion-panel-text>
                            <div 
                              v-for="(newValue, key) in entry.new_values" 
                              :key="key"
                              class="mb-2"
                            >
                              <strong>{{ getFieldLabel(key) }}:</strong>
                              <span class="text-error">{{ formatValue(key, entry.old_values[key]) }}</span>
                              →
                              <span class="text-success">{{ formatValue(key, newValue) }}</span>
                            </div>
                          </v-expansion-panel-text>
                        </v-expansion-panel>
                      </v-expansion-panels>
                    </div>
                  </v-card-text>
                </v-card>
              </v-timeline-item>
            </v-timeline>

            <v-alert v-else type="info" variant="tonal" class="ma-4">
              Brak historii zmian dla tej pozycji
            </v-alert>
          </v-card-text>
        </v-card>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import api from '@/services/api'

const props = defineProps({
  modelValue: Boolean,
  item: Object
})

defineEmits(['update:modelValue', 'edit', 'close'])

const history = ref([])
const loadingHistory = ref(false)

watch(() => props.modelValue, async (newVal) => {
  if (newVal && props.item) {
    await loadHistory()
  }
})

const loadHistory = async () => {
  if (!props.item?.id) return
  
  loadingHistory.value = true
  try {
    const response = await api.get(`/assortment/${props.item.id}/history`)
    history.value = response.data
  } catch (error) {
    console.error('History error:', error)
    history.value = []
  } finally {
    loadingHistory.value = false
  }
}

const getActionColor = (action) => {
  const colors = {
    'created': 'success',
    'updated': 'info',
    'deleted': 'error',
    'activated': 'green',
    'deactivated': 'orange'
  }
  return colors[action] || 'grey'
}

const getActionIcon = (action) => {
  const icons = {
    'created': 'mdi-plus',
    'updated': 'mdi-pencil',
    'deleted': 'mdi-delete',
    'activated': 'mdi-check',
    'deactivated': 'mdi-close'
  }
  return icons[action] || 'mdi-circle'
}

const getActionLabel = (action) => {
  const labels = {
    'created': 'Utworzono',
    'updated': 'Zaktualizowano',
    'deleted': 'Usunięto',
    'activated': 'Aktywowano',
    'deactivated': 'Dezaktywowano'
  }
  return labels[action] || action
}

const getFieldLabel = (field) => {
  const labels = {
    'name': 'Nazwa',
    'type': 'Typ',
    'category': 'Kategoria',
    'unit': 'Jednostka',
    'default_price': 'Cena',
    'description': 'Opis',
    'is_active': 'Status'
  }
  return labels[field] || field
}

const formatValue = (field, value) => {
  if (value === null || value === undefined) return '-'
  
  if (field === 'is_active') {
    return value ? 'Aktywna' : 'Nieaktywna'
  }
  
  if (field === 'default_price') {
    return formatCurrency(value)
  }
  
  return value
}

const formatCurrency = (value) => {
  if (!value) return '0,00 PLN'
  return new Intl.NumberFormat('pl-PL', {
    style: 'currency',
    currency: 'PLN'
  }).format(value)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('pl-PL', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
