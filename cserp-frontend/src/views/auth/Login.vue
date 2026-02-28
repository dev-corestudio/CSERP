<template>
  <v-app>
    <v-main class="d-flex align-center justify-center bg-grey-lighten-4">
      <v-container>
        <v-row justify="center">
          <v-col cols="12" sm="8" md="6" lg="4">
            <v-card elevation="8" rounded="lg">
              <v-card-text class="pa-8">
                <!-- Logo/Tytuł -->
                <div class="text-center mb-6">
                  <v-icon size="80" color="primary">mdi-factory</v-icon>
                  <h1 class="text-h4 font-weight-bold mt-4">CSERP</h1>
                  <p class="text-subtitle-1 text-medium-emphasis">Custom Solutions ERP</p>
                </div>

                <!-- Alert błędu -->
                <v-alert
                  v-if="authStore.error"
                  type="error"
                  variant="tonal"
                  class="mb-4"
                  closable
                  @click:close="authStore.error = null"
                >
                  {{ authStore.error }}
                </v-alert>

                <!-- Alert sesji wygasłej -->
                <v-alert
                  v-if="route.query.session === 'expired'"
                  type="warning"
                  variant="tonal"
                  class="mb-4"
                >
                  <v-alert-title>Sesja wygasła</v-alert-title>
                  Zaloguj się ponownie aby kontynuować
                </v-alert>

                <!-- Formularz logowania -->
                <v-form ref="formRef" @submit.prevent="handleLogin">
                  <v-text-field
                    v-model="email"
                    label="Email"
                    type="email"
                    variant="outlined"
                    prepend-inner-icon="mdi-email"
                    :rules="[rules.required, rules.email]"
                    class="mb-3"
                  />

                  <v-text-field
                    v-model="password"
                    label="Hasło"
                    :type="showPassword ? 'text' : 'password'"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock"
                    :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                    @click:append-inner="showPassword = !showPassword"
                    :rules="[rules.required]"
                    class="mb-4"
                  />

                  <v-btn
                    type="submit"
                    color="primary"
                    variant="elevated"
                    size="large"
                    block
                    :loading="authStore.loading"
                  >
                    <v-icon start>mdi-login</v-icon>
                    Zaloguj się
                  </v-btn>
                </v-form>

                <!-- Demo credentials -->
                <v-divider class="my-6" />
                <div class="text-center text-caption text-medium-emphasis">
                  <p class="mb-2">Demo credentials:</p>
                  <p><strong>Admin:</strong> admin@cserp.pl / pa$$word</p>
                  <p><strong>Manager:</strong> manager@cserp.pl / pa$$word</p>
                  <p><strong>Pracownik:</strong> jan@cserp.pl / pa$$word</p>
                </div>
              </v-card-text>
            </v-card>

            <!-- System Info -->
            <v-card elevation="2" class="mt-4">
              <v-card-text class="text-center text-caption">
                <v-icon size="small" class="mr-1">mdi-information</v-icon>
                CSERP v1.0 • Laravel 11 + Vue 3
              </v-card-text>
            </v-card>

            <v-btn
              variant="text"
              color="primary"
              block
              class="mt-4"
              @click="switchLoginView()"
              prepend-icon="mdi-dialpad"
            >
              Logowanie PIN (Produkcja)
            </v-btn>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const formRef = ref(null);
const email = ref("admin@cserp.pl");
const password = ref("pa$$word");
const showPassword = ref(false);

const rules = {
  required: (v) => !!v || "Pole jest wymagane",
  email: (v) => /.+@.+\..+/.test(v) || "Nieprawidłowy email",
};

const switchLoginView = () => {
  localStorage.removeItem("login_mode");
  router.push("/login-pin");
};

const handleLogin = async () => {
  // Walidacja formularza
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  // Logowanie
  const success = await authStore.login(email.value, password.value);

  if (success) {
    // Przekieruj do strony z query redirect lub dashboard
    const redirectPath = route.query.redirect || "/dashboard";
    router.push(redirectPath);
  }
};
</script>

<style scoped>
.v-main {
  min-height: 100vh;
}
</style>
