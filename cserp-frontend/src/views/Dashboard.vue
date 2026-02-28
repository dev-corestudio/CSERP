<template>
  <v-container fluid>
    <!-- Page Header -->
    <page-header
      title="Dashboard"
      subtitle="Przegląd systemu CSERP"
      icon="mdi-view-dashboard"
      icon-color="primary"
      :breadcrumbs="[]"
    />

    <!-- Quick Actions -->
    <v-card class="mt-6" elevation="2">
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2">mdi-lightning-bolt</v-icon>
        Szybkie akcje
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row>
          <!-- Sekcja Zarządcza (Admin/Manager) -->
          <template v-if="authStore.canManageSystem">
            <v-col cols="12" sm="6" md="3">
              <v-card
                variant="outlined"
                hover
                @click="$router.push('/orders')"
                class="cursor-pointer text-center pa-4"
              >
                <v-avatar color="blue" size="64" class="mb-3">
                  <v-icon color="white" size="36">mdi-clipboard-text</v-icon>
                </v-avatar>
                <div class="text-h6 font-weight-bold">Zamówienia</div>
                <div class="text-caption text-medium-emphasis mt-1">
                  Zarządzaj zamówieniami
                </div>
              </v-card>
            </v-col>

            <v-col cols="12" sm="6" md="3">
              <v-card
                variant="outlined"
                hover
                @click="$router.push('/assortment')"
                class="cursor-pointer text-center pa-4"
              >
                <v-avatar color="orange" size="64" class="mb-3">
                  <v-icon color="white" size="36">mdi-package-variant</v-icon>
                </v-avatar>
                <div class="text-h6 font-weight-bold">Asortyment</div>
                <div class="text-caption text-medium-emphasis mt-1">
                  Materiały i usługi
                </div>
              </v-card>
            </v-col>
          </template>

          <!-- Sekcja Pracownika (Dostępna dla wszystkich lub można ograniczyć) -->
          <!-- Dynamiczna szerokość kolumny: jeśli zarządzający, to 3 kolumny, jeśli worker, to 6 lub 12 -->
          <v-col
            cols="12"
            :sm="authStore.canManageSystem ? 6 : 12"
            :md="authStore.canManageSystem ? 3 : 6"
          >
            <v-card
              variant="outlined"
              hover
              @click="$router.push('/rcp/workstation')"
              class="cursor-pointer text-center pa-4"
              color="success"
            >
              <v-avatar color="success" size="64" class="mb-3">
                <v-icon color="white" size="36">mdi-timer</v-icon>
              </v-avatar>
              <div class="text-h6 font-weight-bold">Panel Pracownika</div>
              <div class="text-caption text-medium-emphasis mt-1">Timer produkcyjny</div>
            </v-card>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import PageHeader from "@/components/layout/PageHeader.vue";

const authStore = useAuthStore();

onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchUser();
  }
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
