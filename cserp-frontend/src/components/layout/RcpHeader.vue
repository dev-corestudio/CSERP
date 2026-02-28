<template>
  <v-app-bar color="primary" elevation="2">
    <v-app-bar-title class="text-h6 font-weight-bold"> Panel operatora </v-app-bar-title>

    <v-spacer />

    <!-- Sekcja Użytkownika i Wylogowania -->
    <div class="d-flex align-center mr-4">
      <div class="text-right mr-4 d-none d-sm-block">
        <div class="text-caption">Zalogowany jako:</div>
        <div class="text-h6 font-weight-bold text-white" style="line-height: 1.2">
          {{ authStore.user?.name }}
        </div>
      </div>

      <v-btn
        variant="tonal"
        size="large"
        prepend-icon="mdi-logout"
        @click="handleLogout"
        class="font-weight-bold px-6 text-none"
      >
        WYLOGUJ
      </v-btn>
    </div>
  </v-app-bar>
</template>
<script setup lang="ts">
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

defineProps({
  showBack: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["back"]);

const authStore = useAuthStore();

const handleLogout = async () => {
  // Bez potwierdzenia (instant logout)
  await authStore.logout();
};
</script>
