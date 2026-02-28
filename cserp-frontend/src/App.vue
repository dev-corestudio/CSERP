<template>
  <!-- Jeśli użytkownik zalogowany i nie na stronie logowania -> Użyj MainLayout -->
  <main-layout v-if="showLayout">
    <router-view />
  </main-layout>

  <!-- W przeciwnym razie (Logowanie) -> Sama treść (v-app potrzebne dla Vuetify) -->
  <v-app v-else>
    <router-view />
  </v-app>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import MainLayout from "@/layouts/MainLayout.vue";

const route = useRoute();
const authStore = useAuthStore();

const showLayout = computed(() => {
  return authStore.isAuthenticated && route.name !== "Login" && route.name !== "LoginPin";
});
</script>
