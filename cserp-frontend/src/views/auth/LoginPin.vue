<template>
  <!-- Template pozostaje bez zmian -->
  <v-container
    fluid
    class="fill-height bg-grey-lighten-3 d-flex align-center justify-center"
  >
    <v-card elevation="8" class="rounded-xl overflow-hidden" width="100%" max-width="450">
      <div class="bg-primary pa-6 text-center">
        <v-icon size="48" color="white" class="mb-2">mdi-factory</v-icon>
        <h2 class="text-h5 font-weight-bold text-white">CSERP Produkcja</h2>
        <div class="text-caption text-blue-lighten-4">
          Wprowadź swój identyfikator PIN
        </div>
      </div>

      <v-card-text class="pa-6">
        <!-- Wyświetlacz PIN (Kropki) -->
        <div class="d-flex justify-center gap-4 my-6">
          <div
            v-for="i in 4"
            :key="i"
            class="pin-dot transition-all"
            :class="{
              filled: pin.length >= i,
              'error-shake': shakeError,
            }"
          ></div>
        </div>

        <!-- Komunikat błędu -->
        <v-expand-transition>
          <div
            v-if="authStore.error"
            class="text-center text-error mb-4 font-weight-bold"
          >
            {{ authStore.error }}
          </div>
        </v-expand-transition>

        <!-- Loading -->
        <div v-if="authStore.loading" class="text-center py-8">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
          <div class="mt-2 text-caption">Autoryzacja...</div>
        </div>

        <!-- Klawiatura Ekranowa -->
        <v-row dense class="numpad" v-else>
          <v-col v-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]" :key="n" cols="4">
            <v-btn
              block
              height="70"
              variant="outlined"
              class="text-h4 font-weight-regular rounded-lg mb-3"
              @click="appendPin(n)"
              :ripple="false"
            >
              {{ n }}
            </v-btn>
          </v-col>

          <!-- Przycisk powrotu do logowania hasłem -->
          <v-col cols="4">
            <v-btn
              block
              height="70"
              variant="text"
              class="rounded-lg mb-3"
              @click="switchLoginView()"
              color="grey"
              tabindex="-1"
            >
              <v-icon>mdi-account-tie</v-icon>
            </v-btn>
          </v-col>

          <v-col cols="4">
            <v-btn
              block
              height="70"
              variant="outlined"
              class="text-h4 font-weight-regular rounded-lg mb-3"
              @click="appendPin(0)"
              :ripple="false"
            >
              0
            </v-btn>
          </v-col>

          <v-col cols="4">
            <v-btn
              block
              height="70"
              color="error"
              variant="tonal"
              class="rounded-lg mb-3"
              @click="clearPin"
            >
              <v-icon size="32">mdi-backspace-outline</v-icon>
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue"; // Dodano onUnmounted
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const pin = ref("");
const shakeError = ref(false);

// Funkcja obsługująca klawiaturę fizyczną
const handleKeyboardInput = (event) => {
  // Ignoruj jeśli trwa ładowanie
  if (authStore.loading) return;

  const key = event.key;

  // Cyfry 0-9
  if (/^[0-9]$/.test(key)) {
    appendPin(parseInt(key));
    return;
  }

  // Backspace
  if (key === "Backspace") {
    clearPin();
    return;
  }

  // Enter (opcjonalnie, bo logowanie jest automatyczne po 4 cyfrze,
  // ale jeśli ktoś wciśnie Enter przy 4 cyfrach, to też zadziała)
  if (key === "Enter") {
    if (pin.value.length === 4) {
      handleLogin();
    }
  }
};

onMounted(() => {
  if (authStore.isAuthenticated) {
    router.push("/dashboard");
  }
  // Wyczyść błędy przy wejściu
  authStore.error = null;

  // Dodaj nasłuchiwanie klawiatury
  window.addEventListener("keydown", handleKeyboardInput);
});

onUnmounted(() => {
  // Usuń nasłuchiwanie klawiatury przy wyjściu z widoku
  window.removeEventListener("keydown", handleKeyboardInput);
});

const switchLoginView = () => {
  localStorage.removeItem("login_mode");
  router.push("/login");
};

const appendPin = (num) => {
  if (pin.value.length < 4) {
    pin.value += num;
    authStore.error = null;
    shakeError.value = false;

    // Automatyczne logowanie po wpisaniu 4 cyfry
    if (pin.value.length === 4) {
      handleLogin();
    }
  }
};

const clearPin = () => {
  // Usuwa ostatni znak (działa jak Backspace)
  pin.value = pin.value.slice(0, -1);
  authStore.error = null;
  shakeError.value = false;
};

const handleLogin = async () => {
  // Wywołanie akcji w Pinia (bez userId, sam pin)
  const success = await authStore.loginWithPin(pin.value);

  if (success) {
    router.push("/rcp/workstation");
  } else {
    triggerShake();
    setTimeout(() => {
      pin.value = "";
    }, 500); // Wyczyść pin po animacji błędu
  }
};

const triggerShake = () => {
  shakeError.value = true;
  setTimeout(() => {
    shakeError.value = false;
  }, 500);
};
</script>

<style scoped>
.pin-dot {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 2px solid #bdbdbd;
  background-color: transparent;
  transition: all 0.2s ease;
  margin: 5px;
}

.pin-dot.filled {
  background-color: rgb(var(--v-theme-primary));
  border-color: rgb(var(--v-theme-primary));
  transform: scale(1.1);
  box-shadow: 0 0 10px rgba(var(--v-theme-primary), 0.4);
}

.error-shake {
  animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
  border-color: rgb(var(--v-theme-error)) !important;
  background-color: rgb(var(--v-theme-error)) !important;
}

@keyframes shake {
  10%,
  90% {
    transform: translate3d(-1px, 0, 0);
  }
  20%,
  80% {
    transform: translate3d(2px, 0, 0);
  }
  30%,
  50%,
  70% {
    transform: translate3d(-4px, 0, 0);
  }
  40%,
  60% {
    transform: translate3d(4px, 0, 0);
  }
}

.numpad .v-btn {
  font-size: 1.5rem !important;
}
</style>
