<template>
  <v-container fluid class="pa-4">

    <!-- ── Welcome banner ─────────────────────────────────────── -->
    <v-card
      class="mb-6 rounded-xl overflow-hidden"
      elevation="0"
      style="background: linear-gradient(135deg, #1565C0 0%, #0D47A1 50%, #283593 100%)"
    >
      <v-card-text class="pa-6">
        <v-row align="center">
          <v-col>
            <div class="text-caption text-blue-lighten-3 font-weight-medium text-uppercase mb-1">
              {{ greeting }}
            </div>
            <div class="text-h4 font-weight-bold text-white mb-1">
              {{ authStore.user?.name || 'Użytkowniku' }}
            </div>
            <div class="text-body-2 text-blue-lighten-3">
              {{ currentDate }}
            </div>
          </v-col>
          <v-col cols="auto" class="d-none d-sm-flex">
            <v-avatar size="80" color="white" style="opacity: 0.12">
              <v-icon size="52" color="white">mdi-view-dashboard</v-icon>
            </v-avatar>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- ── KPI Stats (tylko canManageSystem) ──────────────────── -->
    <v-row v-if="authStore.canManageSystem" class="mb-2">
      <v-col cols="6" md="3" v-for="stat in stats" :key="stat.label">
        <v-card class="rounded-xl" elevation="1" :loading="statsLoading">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-3">
              <v-avatar :color="stat.color" size="44" rounded="lg">
                <v-icon color="white" size="22">{{ stat.icon }}</v-icon>
              </v-avatar>
              <v-chip
                v-if="stat.trend !== undefined"
                size="x-small"
                :color="stat.trend >= 0 ? 'success' : 'error'"
                variant="tonal"
                class="font-weight-bold"
              >
                <v-icon start size="10">{{ stat.trend >= 0 ? 'mdi-trending-up' : 'mdi-trending-down' }}</v-icon>
                {{ Math.abs(stat.trend) }}
              </v-chip>
            </div>
            <div class="text-h4 font-weight-bold text-grey-darken-3 mb-1">
              {{ statsLoading ? '—' : stat.value }}
            </div>
            <div class="text-caption text-medium-emphasis font-weight-medium">
              {{ stat.label }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <!-- ── Szybkie akcje ──────────────────────────────────────── -->
      <v-col cols="12" :md="authStore.canManageSystem ? 4 : 12">
        <v-card class="rounded-xl h-100" elevation="1">
          <v-card-title class="pa-4 pb-2 text-body-1 font-weight-bold">
            <v-icon start size="18" color="primary">mdi-lightning-bolt</v-icon>
            Szybkie akcje
          </v-card-title>
          <v-card-text class="pa-3">
            <div class="d-flex flex-column gap-2">
              <template v-if="authStore.canManageSystem">
                <v-btn
                  v-for="action in managerActions"
                  :key="action.route"
                  :color="action.color"
                  variant="tonal"
                  size="large"
                  class="justify-start rounded-lg"
                  block
                  @click="$router.push(action.route)"
                >
                  <v-icon start>{{ action.icon }}</v-icon>
                  <div class="text-left">
                    <div class="font-weight-bold">{{ action.label }}</div>
                    <div class="text-caption opacity-80">{{ action.sub }}</div>
                  </div>
                  <v-icon end size="18" class="ml-auto opacity-50">mdi-chevron-right</v-icon>
                </v-btn>
              </template>

              <v-btn
                color="success"
                variant="tonal"
                size="large"
                class="justify-start rounded-lg"
                block
                @click="$router.push('/rcp/workstation')"
              >
                <v-icon start>mdi-timer-play</v-icon>
                <div class="text-left">
                  <div class="font-weight-bold">Panel Pracownika</div>
                  <div class="text-caption opacity-80">Timer produkcyjny</div>
                </div>
                <v-icon end size="18" class="ml-auto opacity-50">mdi-chevron-right</v-icon>
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- ── Ostatnie projekty (tylko canManageSystem) ─────────── -->
      <v-col v-if="authStore.canManageSystem" cols="12" md="8">
        <v-card class="rounded-xl" elevation="1">
          <v-card-title class="pa-4 pb-2 d-flex align-center">
            <v-icon start size="18" color="primary">mdi-clock-outline</v-icon>
            <span class="text-body-1 font-weight-bold">Ostatnie projekty</span>
            <v-spacer />
            <v-btn
              variant="text"
              size="small"
              color="primary"
              @click="$router.push('/projects')"
            >
              Wszystkie
              <v-icon end size="16">mdi-arrow-right</v-icon>
            </v-btn>
          </v-card-title>

          <v-divider class="mx-4" />

          <div v-if="projectsLoading" class="pa-6 text-center">
            <v-progress-circular indeterminate color="primary" size="32" />
          </div>

          <v-list v-else-if="recentProjects.length" lines="two" class="py-0">
            <v-list-item
              v-for="(project, i) in recentProjects"
              :key="project.id"
              :to="`/projects/${project.id}`"
              class="px-4"
              :class="{ 'border-b': i < recentProjects.length - 1 }"
              style="border-color: rgba(0,0,0,0.06)"
            >
              <template v-slot:prepend>
                <v-avatar
                  size="38"
                  rounded="lg"
                  :color="getStatusColor(project.overall_status)"
                  class="mr-3"
                >
                  <span class="text-caption font-weight-bold text-white">
                    {{ project.series || '01' }}
                  </span>
                </v-avatar>
              </template>

              <v-list-item-title class="font-weight-bold text-body-2">
                <span class="text-primary">{{ project.full_project_number }}</span>
                <span class="text-medium-emphasis font-weight-regular ml-2 text-caption">
                  {{ project.customer?.name }}
                </span>
              </v-list-item-title>

              <v-list-item-subtitle class="text-caption mt-1 text-truncate" style="max-width: 380px">
                {{ project.description || '—' }}
              </v-list-item-subtitle>

              <template v-slot:append>
                <div class="d-flex flex-column align-end gap-1">
                  <v-chip
                    size="x-small"
                    :color="getStatusColor(project.overall_status)"
                    variant="flat"
                    class="font-weight-bold"
                  >
                    {{ getStatusLabel(project.overall_status) }}
                  </v-chip>
                  <span class="text-caption text-medium-emphasis">
                    {{ formatDate(project.created_at) }}
                  </span>
                </div>
              </template>
            </v-list-item>
          </v-list>

          <div v-else class="pa-8 text-center text-medium-emphasis">
            <v-icon size="40" class="mb-2">mdi-clipboard-text-off</v-icon>
            <div class="text-body-2">Brak projektów</div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import api from "@/services/api";

const authStore = useAuthStore();

// ── Data / time ────────────────────────────────────────────────────────────────

const now = new Date();

const greeting = computed(() => {
  const h = now.getHours();
  if (h < 12) return "Dzień dobry";
  if (h < 18) return "Dzień dobry";
  return "Dobry wieczór";
});

const currentDate = computed(() =>
  now.toLocaleDateString("pl-PL", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  })
);

// ── Stats ──────────────────────────────────────────────────────────────────────

const statsLoading = ref(false);
const statsData = ref({ active: 0, all: 0, customers: 0, unpaid: 0 });

const stats = computed(() => [
  {
    label: "Aktywne projekty",
    value: statsData.value.active,
    icon: "mdi-clipboard-play",
    color: "blue",
  },
  {
    label: "Wszystkie projekty",
    value: statsData.value.all,
    icon: "mdi-clipboard-list",
    color: "indigo",
  },
  {
    label: "Klientów",
    value: statsData.value.customers,
    icon: "mdi-account-group",
    color: "teal",
  },
  {
    label: "Nieopłacone",
    value: statsData.value.unpaid,
    icon: "mdi-currency-usd-off",
    color: "orange",
  },
]);

const fetchStats = async () => {
  statsLoading.value = true;
  try {
    const [activeRes, allRes, customersRes, unpaidRes] = await Promise.all([
      api.get("/projects", { params: { per_page: 1, quick_filter: "active" } }),
      api.get("/projects", { params: { per_page: 1, quick_filter: "all" } }),
      api.get("/customers", { params: { per_page: 1 } }),
      api.get("/projects", { params: { per_page: 1, quick_filter: "all", payment_status: "unpaid" } }),
    ]);
    statsData.value = {
      active: activeRes.data.total ?? activeRes.data.meta?.total ?? 0,
      all: allRes.data.total ?? allRes.data.meta?.total ?? 0,
      customers: customersRes.data.total ?? customersRes.data.meta?.total ?? 0,
      unpaid: unpaidRes.data.total ?? unpaidRes.data.meta?.total ?? 0,
    };
  } catch {
    // ciche niepowodzenie — stats pozostają 0
  } finally {
    statsLoading.value = false;
  }
};

// ── Recent projects ────────────────────────────────────────────────────────────

const projectsLoading = ref(false);
const recentProjects = ref<any[]>([]);

const fetchRecentProjects = async () => {
  projectsLoading.value = true;
  try {
    const res = await api.get("/projects", {
      params: { per_page: 7, sort_by: "created_at", sort_dir: "desc", quick_filter: "all" },
    });
    recentProjects.value = res.data.data ?? [];
  } catch {
    recentProjects.value = [];
  } finally {
    projectsLoading.value = false;
  }
};

// ── Quick actions ──────────────────────────────────────────────────────────────

const managerActions = [
  { label: "Projekty", sub: "Zarządzaj projektami", route: "/projects", icon: "mdi-clipboard-text", color: "primary" },
  { label: "Klienci", sub: "Baza klientów", route: "/customers", icon: "mdi-account-group", color: "teal" },
  { label: "Asortyment", sub: "Materiały i usługi", route: "/assortment", icon: "mdi-package-variant", color: "orange" },
  { label: "RCP — Zarządzanie", sub: "Czas pracy pracowników", route: "/production/rcp", icon: "mdi-clock-check", color: "purple" },
];

// ── Helpers ────────────────────────────────────────────────────────────────────

const getStatusColor = (status: string) => {
  const map: Record<string, string> = {
    draft: "grey", quotation: "blue", prototype: "purple",
    production: "orange", delivery: "cyan", completed: "success", cancelled: "error",
  };
  return map[status?.toLowerCase()] ?? "grey";
};

const getStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    draft: "Szkic", quotation: "Wycena", prototype: "Prototyp",
    production: "Produkcja", delivery: "Dostawa", completed: "Zakończone", cancelled: "Anulowane",
  };
  return map[status?.toLowerCase()] ?? status;
};

const formatDate = (date: string) =>
  date ? new Date(date).toLocaleDateString("pl-PL", { day: "2-digit", month: "short" }) : "—";

// ── Lifecycle ──────────────────────────────────────────────────────────────────

onMounted(async () => {
  if (!authStore.user) await authStore.fetchUser();
  if (authStore.canManageSystem) {
    await Promise.all([fetchStats(), fetchRecentProjects()]);
  }
});
</script>

<style scoped>
.gap-2 { gap: 8px; }
.border-b { border-bottom: 1px solid; }
</style>
