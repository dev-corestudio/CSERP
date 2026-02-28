<template>
  <v-card class="mb-6" elevation="2">
    <v-card-title class="bg-orange text-white d-flex align-center">
      <v-icon start>mdi-clipboard-check-outline</v-icon>
      Usługi wykonane
      <v-spacer />
      <v-chip size="small" variant="tonal" color="white">
        Koszt: {{ formatCurrency(totalCost) }}
      </v-chip>
    </v-card-title>

    <v-card-text class="pa-0">
      <v-data-table
        :headers="headers"
        :items="tasks"
        density="compact"
        hide-default-footer
        items-per-page="-1"
        class="elevation-0"
      >
        <template #item="{ item }">
          <tr style="cursor: default">
            <!-- Ikona statusu -->
            <td class="text-center px-0">
              <div class="d-flex justify-center align-center">
                <v-icon
                  v-if="item.status === 'COMPLETED'"
                  size="small"
                  color="success"
                  icon="mdi-check-circle"
                />
                <div v-else-if="item.status === 'IN_PROGRESS'" class="pulsing-dot" />
                <v-icon
                  v-else
                  size="small"
                  color="grey-lighten-1"
                  icon="mdi-circle-outline"
                />
              </div>
            </td>

            <!-- Nazwa zadania i stanowisko -->
            <td>
              <div
                class="font-weight-medium"
                :class="{ 'text-primary': item.status === 'IN_PROGRESS' }"
              >
                {{ item.service_name }}
              </div>
              <div v-if="item.workstation" class="text-caption text-medium-emphasis">
                {{ item.workstation.name }}
              </div>
            </td>

            <td>{{ item.assigned_worker?.name || "-" }}</td>

            <!-- Stawka/h -->
            <td class="text-right">{{ formatCurrency(item.unit_price) }}</td>

            <!-- Czas (na żywo dla IN_PROGRESS, h+min dla zakończonych) -->
            <td class="text-right font-mono">
              <template v-if="item.status === 'IN_PROGRESS'">
                <span class="text-primary font-weight-bold">
                  {{ formatDuration(Math.floor(getTaskElapsedSeconds(item))) }}
                </span>
              </template>
              <template v-else>
                {{ formatHoursToHm(getTaskHours(item)) }}
              </template>
            </td>

            <!-- Koszt -->
            <td class="text-right font-weight-medium">
              {{ formatCurrency(getTaskCost(item)) }}
            </td>
          </tr>
        </template>

        <template #no-data>
          <div class="text-center py-4 text-medium-emphasis">
            Brak zarejestrowanych usług
          </div>
        </template>
      </v-data-table>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { parseISO, differenceInSeconds, isValid } from "date-fns";
import { useFormatters } from "@/composables/useFormatters";

const props = defineProps<{
  tasks: any[];
  totalCost: number;
}>();

const { formatCurrency, formatDuration } = useFormatters();

const headers: any[] = [
  { title: "", key: "status", width: "40px", align: "center", sortable: false },
  { title: "Zadanie", key: "service_name" },
  { title: "Pracownik", key: "assigned_worker" },
  { title: "Stawka /h", key: "unit_price", align: "end" },
  { title: "Czas", key: "actual_time_hours", align: "end" },
  { title: "Koszt", key: "actual_cost", align: "end" },
];

const now = ref(new Date());
let timerInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  timerInterval = setInterval(() => {
    now.value = new Date();
  }, 1000);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});

const getTaskElapsedSeconds = (item: any): number => {
  if (item.status === "COMPLETED") {
    return (Number(item.actual_time_hours) || 0) * 3600;
  }
  if (item.status === "IN_PROGRESS" && item.actual_start_date) {
    const start = parseISO(item.actual_start_date);
    if (!isValid(start)) return 0;
    return Math.max(0, differenceInSeconds(now.value, start));
  }
  return (Number(item.actual_time_hours) || 0) * 3600;
};

const getTaskHours = (item: any): number => {
  return Number(item.actual_time_hours) || 0;
};

const formatHoursToHm = (decimalHours: number): string => {
  if (!decimalHours || decimalHours <= 0) return "-";
  const totalMin = Math.round(decimalHours * 60);
  const h = Math.floor(totalMin / 60);
  const m = totalMin % 60;
  return h > 0 ? `${h} h ${m} min` : `${m} min`;
};

const getTaskCost = (item: any): number => {
  if (item.status === "COMPLETED") return Number(item.actual_cost) || 0;
  if (item.status === "IN_PROGRESS") {
    const hours = getTaskElapsedSeconds(item) / 3600;
    return hours * (Number(item.unit_price) || 0);
  }
  return Number(item.actual_cost) || 0;
};
</script>

<style scoped>
.pulsing-dot {
  width: 8px;
  height: 8px;
  background-color: rgb(var(--v-theme-primary));
  border-radius: 50%;
  animation: pulse 1.5s infinite;
}
@keyframes pulse {
  0% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(1.3);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.font-mono {
  font-family: "Roboto Mono", monospace;
}
</style>
