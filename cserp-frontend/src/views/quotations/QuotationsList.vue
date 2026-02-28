<template>
  <v-container fluid class="py-6">
    <div class="d-flex align-center mb-6">
      <v-btn icon @click="$router.back()">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <h1 class="text-h4 font-weight-bold ml-4">
        <v-icon class="mr-2">mdi-calculator</v-icon>
        Wyceny - {{ order?.order_number }}
      </h1>
      <v-spacer />
      <v-btn color="primary" variant="elevated" size="large" @click="createDialog = true">
        <v-icon start>mdi-plus</v-icon>
        Nowa wycena
      </v-btn>
    </div>

    <!-- Loading -->
    <div v-if="quotationsStore.loading" class="text-center py-12">
      <v-progress-circular indeterminate size="64" color="primary" />
      <p class="mt-4 text-h6">Ładowanie wycen...</p>
    </div>

    <!-- Quotations Grid -->
    <v-row v-else-if="quotations.length > 0">
      <v-col v-for="quotation in quotations" :key="quotation.id" cols="12" md="6" lg="4">
        <quotation-card
          :quotation="quotation"
          @view="viewQuotation(quotation)"
          @approve="approveQuotation(quotation)"
          @pdf="downloadPDF(quotation)"
        />
      </v-col>
    </v-row>

    <!-- Empty State -->
    <v-card v-else elevation="2" class="text-center pa-12">
      <v-icon size="80" color="grey-lighten-1">mdi-calculator-variant-outline</v-icon>
      <h3 class="text-h5 mt-4 text-medium-emphasis">Brak wycen</h3>
      <p class="text-body-2 text-medium-emphasis mt-2">
        Utwórz pierwszą wycenę dla tego zamówienia
      </p>
      <v-btn color="primary" variant="elevated" class="mt-4" @click="createDialog = true">
        <v-icon start>mdi-plus</v-icon>
        Utwórz wycenę
      </v-btn>
    </v-card>

    <!-- Create Dialog -->
    <quotation-form-dialog v-model="createDialog" :order="order" @saved="handleSaved" />

    <!-- Approve Confirmation -->
    <v-dialog v-model="approveDialog" max-width="500">
      <v-card>
        <v-card-title class="bg-success text-white">
          <v-icon start color="white">mdi-check-circle</v-icon>
          Zatwierdź wycenę
        </v-card-title>
        <v-card-text class="pt-4">
          <p>Czy na pewno chcesz zatwierdzić wycenę:</p>
          <p class="font-weight-bold mt-2">
            Wycena v{{ quotationToApprove?.version_number }}
          </p>
          <p class="text-h6 text-primary mt-2">
            {{ formatCurrency(quotationToApprove?.total_gross) }}
          </p>
          <v-alert type="info" variant="tonal" class="mt-4">
            Tylko jedna wycena może być zatwierdzona. Inne wyceny zostaną automatycznie
            odznaczone.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="approveDialog = false">Anuluj</v-btn>
          <v-btn
            color="success"
            variant="elevated"
            :loading="quotationsStore.loading"
            @click="handleApprove"
          >
            Zatwierdź
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useOrdersStore } from "@/stores/orders";
import { useQuotationsStore } from "@/stores/quotations";
import QuotationCard from "@/components/quotations/QuotationCard.vue";
import QuotationFormDialog from "@/components/quotations/QuotationFormDialog.vue";

const route = useRoute();
const router = useRouter();
const ordersStore = useOrdersStore();
const quotationsStore = useQuotationsStore();

const createDialog = ref(false);
const approveDialog = ref(false);
const quotationToApprove = ref(null);

const order = computed(() => ordersStore.currentOrder);
const quotations = computed(() => quotationsStore.quotations);

onMounted(async () => {
  await ordersStore.fetchOrder(route.params.orderId);
  await quotationsStore.fetchQuotations(route.params.orderId);
});

const viewQuotation = (quotation) => {
  router.push(`/quotations/${quotation.id}`);
};

const approveQuotation = (quotation) => {
  quotationToApprove.value = quotation;
  approveDialog.value = true;
};

const handleApprove = async () => {
  try {
    await quotationsStore.approveQuotation(quotationToApprove.value.id);
    approveDialog.value = false;
    quotationToApprove.value = null;
  } catch (error) {
    console.error("Approve error:", error);
  }
};

const downloadPDF = async (quotation) => {
  console.log("Download PDF:", quotation.id);
  // TODO: Implement PDF download
};

const handleSaved = () => {
  createDialog.value = false;
  quotationsStore.fetchQuotations(route.params.orderId);
};

const formatCurrency = (value) => {
  if (!value) return "0,00 PLN";
  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  }).format(value);
};
</script>
