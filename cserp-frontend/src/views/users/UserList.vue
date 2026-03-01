<template>
  <v-container fluid>
    <page-header
      title="Pracownicy"
      subtitle="Zarządzaj użytkownikami systemu"
      icon="mdi-account-group"
      icon-color="indigo"
      :breadcrumbs="[{ title: 'Użytkownicy', disabled: true }]"
    >
      <template #actions>
        <v-btn
          color="primary"
          variant="elevated"
          prepend-icon="mdi-plus"
          @click="openCreateDialog"
        >
          Dodaj użytkownika
        </v-btn>
      </template>
    </page-header>

    <!-- Filtry -->
    <v-card elevation="2" class="mb-4">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Szukaj (imię, email)..."
              hide-details
              density="compact"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" class="d-flex justify-end gap-1">
            <v-tooltip text="Resetuj filtry" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" :color="hasActiveFilters ? 'warning' : undefined" @click="resetFilters">
                  <v-icon>mdi-filter-remove</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
            <v-tooltip text="Odśwież" location="top">
              <template v-slot:activator="{ props }">
                <v-btn v-bind="props" icon variant="text" :loading="usersStore.loading" @click="refreshList">
                  <v-icon>mdi-refresh</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Tabela -->
    <v-card elevation="2">
      <v-data-table
        :headers="headers"
        :items="usersStore.items"
        :loading="usersStore.loading"
        :search="search"
        hover
      >
        <template v-slot:item.role="{ item }">
          <v-chip
            size="small"
            :color="formatUserRole(item.role).color"
            class="font-weight-medium"
          >
            <v-icon start size="small">{{ formatUserRole(item.role).icon }}</v-icon>
            {{ formatUserRole(item.role).label }}
          </v-chip>
        </template>

        <!-- RCP Access Column (PIN) -->
        <template v-slot:item.has_pin="{ item }">
          <v-tooltip
            location="top"
            :text="
              item.has_pin
                ? 'Dostęp do panelu RCP (PIN ustawiony)'
                : 'Brak dostępu do panelu RCP'
            "
          >
            <template v-slot:activator="{ props }">
              <div v-bind="props" class="d-flex justify-center">
                <v-icon
                  v-if="item.has_pin"
                  color="success"
                  icon="mdi-check-bold"
                ></v-icon>
                <v-icon v-else color="grey-lighten-2" icon="mdi-minus"></v-icon>
              </div>
            </template>
          </v-tooltip>
        </template>

        <!-- Status Column -->
        <template v-slot:item.is_active="{ item }">
          <v-chip
            size="small"
            :color="item.is_active ? 'success' : 'error'"
            variant="flat"
          >
            {{ item.is_active ? "Aktywny" : "Nieaktywny" }}
          </v-chip>
        </template>

        <!-- Actions -->
        <template v-slot:item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-tooltip text="Edytuj" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="editUser(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip :text="item.is_active ? 'Dezaktywuj' : 'Aktywuj'" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  :icon="item.is_active ? 'mdi-account-off' : 'mdi-account-check'"
                  size="small"
                  variant="text"
                  :color="item.is_active ? 'warning' : 'success'"
                  @click="toggleActive(item)"
                />
              </template>
            </v-tooltip>

            <v-tooltip text="Usuń" location="top">
              <template v-slot:activator="{ props }">
                <v-btn
                  v-bind="props"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteUser(item)"
                />
              </template>
            </v-tooltip>
          </div>
        </template>

        <!-- No Data State -->
        <template v-slot:no-data>
          <div class="text-center py-8 text-medium-emphasis">
            <v-icon size="48" class="mb-2">mdi-account-off</v-icon>
            <div>Brak użytkowników do wyświetlenia</div>
            <v-btn variant="text" color="primary" class="mt-2" @click="refreshList">
              Odśwież
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Dialog tworzenia/edycji użytkownika -->
    <user-form-dialog v-model="dialog" :user="editedUser" @saved="refreshList" />
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useUsersStore } from "@/stores/users";
import PageHeader from "@/components/layout/PageHeader.vue";
import UserFormDialog from "@/components/users/UserFormDialog.vue";
import { useStatusFormatter } from "@/composables/useStatusFormatter";
import { usePersistedFilters } from "@/composables/usePersistedFilters";

const usersStore = useUsersStore();
const { formatUserRole } = useStatusFormatter();

const search = usePersistedFilters<string>("users:search", "");
const dialog = ref(false);
const editedUser = ref(null);

const headers = [
  { title: "Imię i Nazwisko", key: "name", align: "start" },
  { title: "Email", key: "email", align: "start" },
  { title: "Rola", key: "role", align: "start" },
  { title: "Dostęp RCP", key: "has_pin", align: "center" }, // Nowa kolumna
  { title: "Status", key: "is_active", align: "center" },
  { title: "Akcje", key: "actions", align: "end", sortable: false },
];

const hasActiveFilters = computed(() => search.value !== "");

const resetFilters = () => {
  search.value = "";
};

const refreshList = async () => {
  await usersStore.fetchUsers();
};

const openCreateDialog = () => {
  editedUser.value = null; // null oznacza tryb tworzenia
  dialog.value = true;
};

const editUser = (user: any) => {
  editedUser.value = user; // Przekazujemy obiekt usera do edycji
  dialog.value = true;
};

const toggleActive = async (user: any) => {
  const action = user.is_active ? "dezaktywować" : "aktywować";
  if (confirm(`Czy na pewno chcesz ${action} konto użytkownika ${user.name}?`)) {
    await usersStore.toggleActive(user);
  }
};

const deleteUser = async (user: any) => {
  if (confirm(`Czy na pewno trwale usunąć użytkownika ${user.name}?`)) {
    await usersStore.deleteUser(user.id);
  }
};

onMounted(() => {
  refreshList();
});
</script>
