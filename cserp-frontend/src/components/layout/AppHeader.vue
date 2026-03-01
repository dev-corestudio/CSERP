<template>
  <v-app-bar color="primary" elevation="2">
    <v-app-bar-nav-icon
      class="d-md-none"
      variant="text"
      @click.stop="$emit('toggle-drawer')"
    />

    <v-app-bar-title class="font-weight-bold d-flex align-center">
      <v-icon start class="mr-2">mdi-factory</v-icon>
      CSERP
    </v-app-bar-title>

    <!-- Desktop Navigation -->
    <template v-if="!isMobile">
      <v-btn
        v-for="item in menuItems"
        :key="item.to"
        :to="item.to"
        variant="text"
        class="ml-1"
      >
        <v-icon start>{{ item.icon }}</v-icon>
        {{ item.title }}
      </v-btn>
    </template>

    <v-spacer />

    <!-- User Menu -->
    <v-menu v-if="authStore.user">
      <template v-slot:activator="{ props }">
        <v-btn v-bind="props" variant="text" class="px-2">
          <v-avatar color="white" size="32" class="mr-2 text-primary">
            <span class="text-subtitle-2 font-weight-bold">
              {{ userInitials }}
            </span>
          </v-avatar>
          <v-icon end>mdi-chevron-down</v-icon>
        </v-btn>
      </template>
      <v-list width="200">
        <v-list-item>
          <template v-slot:prepend>
            <v-icon>mdi-account-circle</v-icon>
          </template>
          <v-list-item-title class="font-weight-bold">
            {{ userRoleLabel }}
          </v-list-item-title>
        </v-list-item>
        <v-divider class="my-1" />
        <v-list-item
          prepend-icon="mdi-logout"
          title="Wyloguj"
          value="logout"
          color="error"
          @click="handleLogout"
        />
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useMetadataStore } from "@/stores/metadata"; // Potrzebne do wyświetlenia ładnej nazwy roli
import { useDisplay } from "vuetify";

const emit = defineEmits<{
  (e: "toggle-drawer"): void;
}>();

const router = useRouter();
const authStore = useAuthStore();
const metadataStore = useMetadataStore();
const { mobile } = useDisplay();

const isMobile = computed(() => mobile.value);

// Obliczanie inicjałów
const userInitials = computed(() => {
  const name = authStore.user?.name || "";
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
});

const userRoleLabel = computed(() => {
  const role = authStore.user?.role;
  if (!role) return "";
  const roleObj = metadataStore.userRoles.find((r: any) => r.value === role);
  return roleObj ? roleObj.label : role;
});

// Dynamiczna lista menu na podstawie gettera ze store
const menuItems = computed(() => {
  const items = [{ title: "Dashboard", icon: "mdi-view-dashboard", to: "/dashboard" }];

  // Używamy gettera logiki biznesowej, a nie sprawdzania stringów
  if (authStore.canManageSystem) {
    items.push(
      { title: "Projekty", icon: "mdi-clipboard-text", to: "/projects" },
      { title: "Klienci", icon: "mdi-account-group", to: "/customers" },
      { title: "Asortyment", icon: "mdi-package-variant-closed", to: "/assortment" },
      { title: "Stanowiska", icon: "mdi-robot", to: "/workstations" },
      { title: "Panel RCP", icon: "mdi-factory", to: "/admin/rcp" }
    );
  }

  if (authStore.isAdmin) {
    items.push({ title: "Użytkownicy", icon: "mdi-account-cog", to: "/users" });
  }

  // Panel pracownika (dostępny dla wszystkich w tym demo, lub użyj authStore.isWorker)
  items.push({ title: "Panel Pracownika", icon: "mdi-timer", to: "/rcp/workstation" });

  return items;
});

const handleLogout = async () => {
  await authStore.logout();
  router.push("/login");
};
</script>
