<template>
  <v-app>
    <!-- Header dynamiczny na podstawie roli -->
    <!-- Dla pracowników produkcyjnych (PRODUCTION_EMPLOYEE) pokazujemy RcpHeader -->
    <rcp-header
      v-if="authStore.isWorker"
      :show-back="showBackButton"
      @back="handleBack"
    />

    <!-- Dla pozostałych ról (Admin, Manager, Trader, etc.) pokazujemy pełny AppHeader -->
    <app-header v-else @toggle-drawer="mobileDrawer = !mobileDrawer" />

    <!-- Mobile Drawer - tylko dla użytkowników z pełnym dostępem -->
    <v-navigation-drawer
      v-if="!authStore.isWorker"
      v-model="mobileDrawer"
      temporary
      location="left"
      class="d-md-none"
    >
      <v-list>
        <v-list-item
          prepend-icon="mdi-factory"
          title="CSERP"
          subtitle="Custom Solutions ERP"
          class="mb-2"
        />
        <v-divider />
        <v-list-item
          v-for="item in mobileNavItems"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
        />
      </v-list>
    </v-navigation-drawer>

    <!-- Główna zawartość -->
    <v-main class="flex-grow-1">
      <slot />
    </v-main>

    <!-- Footer - ukryty dla pracowników produkcyjnych (opcjonalnie) -->
    <app-footer />
  </v-app>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import AppHeader from "@/components/layout/AppHeader.vue";
import AppFooter from "@/components/layout/AppFooter.vue";
import RcpHeader from "@/components/layout/RcpHeader.vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const mobileDrawer = ref(false);

// Określanie czy pokazać przycisk wstecz w RcpHeader
// Pokazujemy go gdy jesteśmy na stronie Timer (nie na głównej stronie wyboru stanowiska)
const showBackButton = computed(() => {
  // Lista ścieżek gdzie NIE pokazujemy przycisku wstecz
  const noBackRoutes = ["/rcp/workstation", "/dashboard"];
  return !noBackRoutes.includes(route.path);
});

// Obsługa przycisku wstecz
const handleBack = () => {
  // Jeśli jesteśmy na timerze, wracamy do wyboru stanowiska
  if (route.path.startsWith("/rcp/timer")) {
    router.push("/rcp/workstation");
  } else {
    router.back();
  }
};

// Menu mobilne dla pełnego dostępu
const mobileNavItems = computed(() => {
  const items = [{ title: "Dashboard", icon: "mdi-view-dashboard", to: "/dashboard" }];

  if (authStore.canManageSystem) {
    items.push(
      { title: "Projekty", icon: "mdi-clipboard-text", to: "/projects" },
      { title: "Klienci", icon: "mdi-account-group", to: "/customers" },
      { title: "Asortyment", icon: "mdi-package-variant-closed", to: "/assortment" },
      { title: "Stanowiska", icon: "mdi-factory", to: "/workstations" }
    );
  }

  items.push({ title: "Panel Pracownika", icon: "mdi-timer", to: "/rcp/workstation" });
  return items;
});
</script>
