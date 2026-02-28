<template>
  <v-footer color="background" class="border-t" style="max-height:60px">
    <v-container fluid class="pa-0">
      <v-row no-gutters align="center">
        
        <v-col cols="12" sm="6" class="px-4 text-center text-sm-left">
          <span class="text-caption text-medium-emphasis font-weight-bold mr-2">
            CSERP
          </span>
          <span class="text-caption text-disabled">
            © {{ new Date().getFullYear() }} Wszelkie prawa zastrzeżone.
          </span>
        </v-col>

        <v-col cols="12" sm="6" class="px-4 text-center text-sm-right">
          <v-chip
            size="x-small"
            variant="flat"
            color="grey-lighten-4"
            class="text-caption text-medium-emphasis"
          >
            <v-icon start icon="mdi-server" size="x-small" color="primary"></v-icon>
            v1.0.0-beta
          </v-chip>
        </v-col>

      </v-row>
    </v-container>
  </v-footer>
</template>

<script setup lang="ts">
// Brak dodatkowej logiki
</script>