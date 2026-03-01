<template>
  <v-container fluid>
    <page-header
      title="Dashboard"
      :subtitle="`${greeting}, ${authStore.user?.name || 'Użytkowniku'}! ${currentDate}`"
      icon="mdi-view-dashboard"
      icon-color="primary"
      :breadcrumbs="[]"
    />

    <!-- ── KPI Stats (tylko canManageSystem) ──────────────────── -->
    <v-row v-if="authStore.canManageSystem" class="mb-4">
      <v-col cols="6" md="3" v-for="stat in stats" :key="stat.label">
        <v-card elevation="2" :loading="statsLoading">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-avatar :color="stat.color" size="44" rounded="lg" class="mr-3">
                <v-icon color="white" size="22">{{ stat.icon }}</v-icon>
              </v-avatar>
              <div>
                <div class="text-h5 font-weight-bold">
                  {{ statsLoading ? '—' : stat.value }}
                </div>
                <div class="text-caption text-medium-emphasis">{{ stat.label }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <!-- ── Szybkie akcje ──────────────────────────────────────── -->
      <v-col cols="12" :md="authStore.canManageSystem ? 4 : 12">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-lightning-bolt</v-icon>
            Szybkie akcje
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-row>
              <template v-if="authStore.canManageSystem">
                <v-col
                  v-for="action in managerActions"
                  :key="action.route"
                  cols="12"
                >
                  <v-card
                    variant="outlined"
                    hover
                    @click="$router.push(action.route)"
                    class="cursor-pointer"
                  >
                    <v-card-text class="d-flex align-center pa-3">
                      <v-avatar :color="action.color" size="40" class="mr-3">
                        <v-icon color="white" size="20">{{ action.icon }}</v-icon>
                      </v-avatar>
                      <div>
                        <div class="font-weight-bold text-body-2">{{ action.label }}</div>
                        <div class="text-caption text-medium-emphasis">{{ action.sub }}</div>
                      </div>
                      <v-spacer />
                      <v-icon size="18" color="medium-emphasis">mdi-chevron-right</v-icon>
                    </v-card-text>
                  </v-card>
                </v-col>
              </template>

              <v-col cols="12">
                <v-card
                  variant="outlined"
                  hover
                  color="success"
                  @click="$router.push('/rcp/workstation')"
                  class="cursor-pointer"
                >
                  <v-card-text class="d-flex align-center pa-3">
                    <v-avatar color="success" size="40" class="mr-3">
                      <v-icon color="white" size="20">mdi-timer-play</v-icon>
                    </v-avatar>
                    <div>
                      <div class="font-weight-bold text-body-2">Panel Pracownika</div>
                      <div class="text-caption text-medium-emphasis">Timer produkcyjny</div>
                    </div>
                    <v-spacer />
                    <v-icon size="18" color="medium-emphasis">mdi-chevron-right</v-icon>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- ── Ostatnie otwarte projekty (per user, localStorage) ── -->
      <v-col v-if="authStore.canManageSystem" cols="12" md="8">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-history</v-icon>
            Ostatnie otwarte projekty
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
          <v-divider />

          <v-list v-if="recentProjects.length" lines="two" class="py-0">
            <template v-for="(project, i) in recentProjects" :key="project.id">
              <v-list-item
                :to="`/projects/${project.id}`"
                class="px-4"
              >
                <template v-slot:prepend>
                  <v-avatar
                    size="36"
                    rounded
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

                <v-list-item-subtitle class="text-caption text-truncate" style="max-width: 400px">
                  {{ project.description || '—' }}
                </v-list-item-subtitle>

                <template v-slot:append>
                  <div class="d-flex flex-column align-end" style="gap: 4px">
                    <v-chip
                      size="small"
                      :color="getStatusColor(project.overall_status)"
                      variant="flat"
                    >
                      <v-icon start size="small">{{ getStatusIcon(project.overall_status) }}</v-icon>
                      {{ getStatusLabel(project.overall_status) }}
                    </v-chip>
                    <span class="text-caption text-medium-emphasis">
                      {{ formatDate(project.viewed_at) }}
                    </span>
                  </div>
                </template>
              </v-list-item>
              <v-divider v-if="i < recentProjects.length - 1" />
            </template>
          </v-list>

          <div v-else class="text-center py-8">
            <v-icon size="64" color="grey">mdi-clipboard-text-clock</v-icon>
            <div class="text-h6 mt-4 text-medium-emphasis">Nie otwierałeś jeszcze żadnego projektu</div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRecentProjects } from "@/composables/useRecentProjects";
import PageHeader from "@/components/layout/PageHeader.vue";
import api from "@/services/api";

const authStore = useAuthStore();
const { getAll: getRecentProjects } = useRecentProjects();

// ── Data / time ────────────────────────────────────────────────────────────────

const now = new Date();

const greeting = computed(() => {
  const h = now.getHours();
  return h < 18 ? "Dzień dobry" : "Dobry wieczór";
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
  { label: "Aktywne projekty",   value: statsData.value.active,    icon: "mdi-clipboard-play",   color: "blue"   },
  { label: "Wszystkie projekty", value: statsData.value.all,       icon: "mdi-clipboard-list",   color: "indigo" },
  { label: "Klientów",           value: statsData.value.customers, icon: "mdi-account-group",    color: "teal"   },
  { label: "Nieopłacone",        value: statsData.value.unpaid,    icon: "mdi-currency-usd-off", color: "orange" },
]);

const fetchStats = async () => {
  statsLoading.value = true;
  try {
    const [activeRes, allRes, customersRes, unpaidRes] = await Promise.all([
      api.get("/projects",  { params: { per_page: 1, quick_filter: "active" } }),
      api.get("/projects",  { params: { per_page: 1, quick_filter: "all"    } }),
      api.get("/customers", { params: { per_page: 1 } }),
      api.get("/projects",  { params: { per_page: 1, quick_filter: "all", payment_status: "unpaid" } }),
    ]);
    statsData.value = {
      active:    activeRes.data.total    ?? activeRes.data.meta?.total    ?? 0,
      all:       allRes.data.total       ?? allRes.data.meta?.total       ?? 0,
      customers: customersRes.data.total ?? customersRes.data.meta?.total ?? 0,
      unpaid:    unpaidRes.data.total    ?? unpaidRes.data.meta?.total    ?? 0,
    };
  } catch {
    // ciche niepowodzenie
  } finally {
    statsLoading.value = false;
  }
};

// ── Recent projects (z localStorage per user) ──────────────────────────────────

const recentProjects = ref<any[]>([]);

// ── Quick actions ──────────────────────────────────────────────────────────────

const managerActions = [
  { label: "Projekty",        sub: "Zarządzaj projektami",       route: "/projects",       icon: "mdi-clipboard-text",  color: "blue"   },
  { label: "Klienci",         sub: "Baza klientów",              route: "/customers",      icon: "mdi-account-group",   color: "teal"   },
  { label: "Asortyment",      sub: "Materiały i usługi",         route: "/assortment",     icon: "mdi-package-variant", color: "orange" },
  { label: "RCP — Zarządzanie", sub: "Czas pracy pracowników",  route: "/production/rcp", icon: "mdi-clock-check",     color: "purple" },
];

// ── Helpers ────────────────────────────────────────────────────────────────────

const STATUS_MAP: Record<string, { label: string; color: string; icon: string }> = {
  draft:      { label: "Szkic",       color: "grey",    icon: "mdi-pencil-outline"   },
  quotation:  { label: "Wycena",      color: "blue",    icon: "mdi-file-document"    },
  prototype:  { label: "Prototyp",    color: "purple",  icon: "mdi-test-tube"        },
  production: { label: "Produkcja",   color: "orange",  icon: "mdi-factory"          },
  delivery:   { label: "Dostawa",     color: "cyan",    icon: "mdi-truck-delivery"   },
  completed:  { label: "Zakończone",  color: "success", icon: "mdi-check-circle"     },
  cancelled:  { label: "Anulowane",   color: "error",   icon: "mdi-close-circle"     },
};

const getStatusColor = (s: string) => STATUS_MAP[s?.toLowerCase()]?.color  ?? "grey";
const getStatusLabel = (s: string) => STATUS_MAP[s?.toLowerCase()]?.label  ?? s;
const getStatusIcon  = (s: string) => STATUS_MAP[s?.toLowerCase()]?.icon   ?? "mdi-circle-small";

const formatDate = (date: string) =>
  date ? new Date(date).toLocaleDateString("pl-PL", { day: "2-digit", month: "short", year: "numeric" }) : "—";

// ── Lifecycle ──────────────────────────────────────────────────────────────────

onMounted(async () => {
  if (!authStore.user) await authStore.fetchUser();
  if (authStore.canManageSystem) {
    recentProjects.value = getRecentProjects();
    await fetchStats();
  }
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
