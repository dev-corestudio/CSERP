<template>
  <v-card elevation="2" class="mb-6 rounded-lg overflow-hidden">
    <!-- ══ Nagłówek ══ -->
    <v-card-title class="bg-indigo text-white d-flex align-center py-3 px-4">
      <v-icon start color="white">mdi-format-list-bulleted-type</v-icon>
      Warianty ({{ variantsCount }})
      <v-spacer />
      <!-- Rozwiń / Zwiń wszystkie (tylko gdy są grupy) -->
      <v-btn
        v-if="hasAnyGroup"
        size="small"
        color="white"
        variant="text"
        :prepend-icon="allExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down'"
        class="mr-1 text-caption"
        @click="toggleAll"
      >
        {{ allExpanded ? "Zwiń" : "Rozwiń" }}
      </v-btn>
      <!-- Dodaj nową grupę -->
      <v-btn
        size="small"
        color="white"
        variant="outlined"
        prepend-icon="mdi-folder-plus"
        class="mr-2"
        @click="$emit('add-group')"
      >
        Dodaj grupę
      </v-btn>
    </v-card-title>

    <!-- ══ Drzewo ══ -->
    <div v-if="flatTree.length > 0">
      <template
        v-for="(node, index) in flatTree"
        :key="node.isEmpty ? 'empty-' + node.variant.id : node.variant.id"
      >
        <!-- ──────────────────────────────────────────────
             ROW: GRUPA  (is_group=true)
        ────────────────────────────────────────────── -->
        <template v-if="node.isGroup">
          <div
            class="group-row"
            :style="{ paddingLeft: groupIndent(node.depth) }"
            @click="toggleGroup(node.variant.id)"
          >
            <!-- Strzałka rozwijania -->
            <v-icon
              class="expand-icon"
              :class="{ 'expand-icon--open': isExpanded(node.variant.id) }"
              size="18"
              color="indigo"
            >
              mdi-chevron-right
            </v-icon>

            <!-- Badge grupy -->
            <div
              class="group-badge mr-3"
              :style="{ background: badgeColor(node.variant.variant_number) }"
            >
              {{ node.variant.variant_number }}
            </div>

            <!-- Nazwa grupy -->
            <div class="group-info flex-grow-1">
              <span class="group-name">{{ node.variant.name }}</span>
              <span class="group-children-count ml-2">
                ({{ directChildCount(node.variant.id) }})
              </span>
            </div>

            <!-- Akcje grupy -->
            <div class="group-actions" @click.stop>
              <!-- Dodaj wariant do grupy -->
              <v-btn
                size="x-small"
                color="indigo"
                variant="tonal"
                prepend-icon="mdi-plus"
                class="mr-1"
                @click="$emit('add-child', node.variant)"
              >
                Wariant
              </v-btn>

              <!-- Menu grupy -->
              <v-menu location="bottom end" :close-on-content-click="true">
                <template #activator="{ props: menuProps }">
                  <v-btn
                    v-bind="menuProps"
                    icon="mdi-dots-vertical"
                    variant="text"
                    size="small"
                    color="grey-darken-1"
                  />
                </template>
                <v-list density="compact" elevation="3" min-width="180">
                  <v-list-item
                    prepend-icon="mdi-pencil"
                    @click="$emit('edit', node.variant)"
                  >
                    <v-list-item-title>Edytuj grupę</v-list-item-title>
                  </v-list-item>
                  <v-list-item
                    prepend-icon="mdi-content-copy"
                    class="text-deep-purple"
                    @click="$emit('duplicate', node.variant)"
                  >
                    <v-list-item-title>Duplikuj grupę</v-list-item-title>
                  </v-list-item>
                  <v-divider />
                  <v-list-item
                    v-if="directChildCount(node.variant.id) === 0"
                    prepend-icon="mdi-delete"
                    class="text-error"
                    @click="$emit('delete', node.variant)"
                  >
                    <v-list-item-title>Usuń grupę</v-list-item-title>
                  </v-list-item>
                  <v-list-item
                    v-else
                    prepend-icon="mdi-delete-sweep"
                    class="text-error"
                    @click="$emit('delete-group-force', node.variant)"
                  >
                    <v-list-item-title>Usuń grupę z wariantami</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>
          </div>
          <v-divider />
        </template>

        <!-- ──────────────────────────────────────────────
             ROW: PUSTA GRUPA (placeholder)
        ────────────────────────────────────────────── -->
        <template v-else-if="node.isEmpty">
          <div
            class="empty-group-row"
            :style="{ paddingLeft: variantIndent(node.depth) }"
          >
            <v-icon size="18" color="grey-lighten-1" class="mr-2"
              >mdi-folder-open-outline</v-icon
            >
            <span class="empty-group-text">Brak wariantów w grupie</span>
            <v-btn
              size="x-small"
              color="indigo"
              variant="text"
              prepend-icon="mdi-plus"
              class="ml-3 font-weight-bold"
              @click="$emit('add-child', node.variant)"
            >
              Dodaj
            </v-btn>
          </div>
          <v-divider />
        </template>

        <!-- ──────────────────────────────────────────────
             ROW: WARIANT (is_group=false)
        ────────────────────────────────────────────── -->
        <template v-else>
          <div
            class="variant-row"
            :style="{ paddingLeft: variantIndent(node.depth) }"
            @click="$emit('view', node.variant.id)"
          >
            <!-- Linia drzewa + strzałka dziecka -->
            <div class="tree-connector" v-if="node.depth > 0">
              <!-- Pionowe/poziome linie drzewa generowane CSS'em przez wcięcie -->
              <span class="child-arrow">↳</span>
            </div>

            <!-- Badge wariantu -->
            <div
              class="variant-badge mr-3"
              :style="{
                background: badgeColor(node.variant.variant_number),
                fontSize: badgeFontSize(node.variant.variant_number),
              }"
            >
              {{ node.variant.variant_number }}
            </div>

            <!-- Nazwa + opis -->
            <div class="info-col">
              <div class="variant-name">
                {{ node.variant.name }}
                <v-chip
                  size="x-small"
                  :color="node.variant.type === 'PROTOTYPE' ? 'orange' : 'blue'"
                  variant="tonal"
                  class="ml-1 flex-shrink-0"
                >
                  <v-icon start size="10">
                    {{
                      node.variant.type === "PROTOTYPE"
                        ? "mdi-flask-outline"
                        : "mdi-factory"
                    }}
                  </v-icon>
                  {{ node.variant.type === "PROTOTYPE" ? "PROTOTYP" : "SERYJNA" }}
                </v-chip>
              </div>
              <div class="variant-desc">{{ node.variant.description || "—" }}</div>
            </div>

            <!-- Prawa kolumna -->
            <div class="append-col" @click.stop>
              <!-- Ilość -->
              <div class="quantity-block">
                <div class="quantity-label">ILOŚĆ</div>
                <div class="quantity-value">{{ node.variant.quantity }} szt.</div>
              </div>

              <!-- Status -->
              <v-chip
                :color="formatVariantStatus(node.variant.status).color"
                size="small"
                variant="flat"
                class="d-none d-sm-flex"
              >
                <v-icon start size="12">
                  {{ formatVariantStatus(node.variant.status).icon }}
                </v-icon>
                {{ formatVariantStatus(node.variant.status).label }}
              </v-chip>

              <!-- Menu wariantu -->
              <v-menu location="bottom end" :close-on-content-click="true">
                <template #activator="{ props: menuProps }">
                  <v-btn
                    v-bind="menuProps"
                    icon="mdi-dots-vertical"
                    variant="text"
                    size="small"
                    color="grey-darken-1"
                  />
                </template>
                <v-list density="compact" elevation="3" min-width="180">
                  <v-list-item
                    prepend-icon="mdi-eye"
                    @click="$emit('view', node.variant.id)"
                  >
                    <v-list-item-title>Szczegóły</v-list-item-title>
                  </v-list-item>
                  <v-list-item
                    prepend-icon="mdi-pencil"
                    @click="$emit('edit', node.variant)"
                  >
                    <v-list-item-title>Edytuj</v-list-item-title>
                  </v-list-item>
                  <!-- Dodaj podwariant -->
                  <v-list-item
                    prepend-icon="mdi-source-branch-plus"
                    @click="$emit('add-child', node.variant)"
                  >
                    <v-list-item-title>Dodaj podwariant</v-list-item-title>
                  </v-list-item>
                  <v-list-item
                    prepend-icon="mdi-content-copy"
                    class="text-deep-purple"
                    @click="$emit('duplicate', node.variant)"
                  >
                    <v-list-item-title>Duplikuj</v-list-item-title>
                  </v-list-item>
                  <v-divider />
                  <v-list-item
                    prepend-icon="mdi-delete"
                    class="text-error"
                    @click="$emit('delete', node.variant)"
                  >
                    <v-list-item-title>Usuń</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>
          </div>
          <v-divider v-if="index < flatTree.length - 1" />
        </template>
      </template>
    </div>

    <!-- ══ Stan pusty ══ -->
    <v-card-text v-else class="text-center py-12">
      <v-icon size="64" color="grey-lighten-2">mdi-format-list-bulleted-type</v-icon>
      <div class="text-h6 text-grey mt-4">Brak wariantów</div>
      <p class="text-medium-emphasis mt-2">
        Zacznij od dodania grupy, w której umieścisz warianty produktu.
      </p>
      <v-btn
        color="indigo"
        variant="elevated"
        prepend-icon="mdi-folder-plus"
        size="large"
        class="mt-4"
        @click="$emit('add-group')"
      >
        Dodaj pierwszą grupę
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useStatusFormatter } from "@/composables/useStatusFormatter";

const { formatVariantStatus } = useStatusFormatter();

// ─── Props / Emits ────────────────────────────────────────────────────────────

const props = defineProps<{
  variants: any[];
}>();

defineEmits([
  "add-group", // Dodaj nową grupę top-level
  "add-child", // Dodaj wariant do grupy/wariantu (payload: parent variant)
  "view", // Przejdź do widoku wariantu (payload: id)
  "edit", // Edytuj grupę lub wariant (payload: variant)
  "duplicate", // Duplikuj (payload: variant)
  "delete", // Usuń wariant lub pustą grupę (payload: variant)
  "delete-group-force", // Usuń grupę z wszystkimi dziećmi (payload: group variant)
]);

// ─── Expanded state (grupy domyślnie rozwinięte) ──────────────────────────────

/**
 * Zestaw ID grup, które są ROZWINIĘTE.
 * Domyślnie zawiera wszystkie grupy — rozwinięte od startu.
 */
const expandedGroups = ref<Set<number>>(new Set());

/**
 * Gdy lista wariantów się zmieni, automatycznie rozwiń nowe grupy.
 * Istniejące grupy zachowują swój stan (nie resetujemy).
 */
watch(
  () => props.variants,
  (variants) => {
    if (!variants) return;
    variants
      .filter((v) => v.is_group === true)
      .forEach((g) => {
        // Dodaj do expanded TYLKO jeśli jeszcze nie ma (nie resetuj ręcznych zwiniętych)
        if (!expandedGroups.value.has(g.id)) {
          expandedGroups.value.add(g.id);
        }
      });
  },
  { immediate: true, deep: false }
);

const isExpanded = (groupId: number): boolean => expandedGroups.value.has(groupId);

const toggleGroup = (groupId: number) => {
  if (expandedGroups.value.has(groupId)) {
    expandedGroups.value.delete(groupId);
  } else {
    expandedGroups.value.add(groupId);
  }
  // Wymuszamy reaktywność (Set nie jest reaktywny bez tego triku)
  expandedGroups.value = new Set(expandedGroups.value);
};

const hasAnyGroup = computed(() =>
  (props.variants ?? []).some((v) => v.is_group === true)
);

const allExpanded = computed(() => {
  const groups = (props.variants ?? []).filter((v) => v.is_group === true);
  return groups.every((g) => expandedGroups.value.has(g.id));
});

const toggleAll = () => {
  const groups = (props.variants ?? []).filter((v) => v.is_group === true);
  if (allExpanded.value) {
    // Zwiń wszystkie
    groups.forEach((g) => expandedGroups.value.delete(g.id));
  } else {
    // Rozwiń wszystkie
    groups.forEach((g) => expandedGroups.value.add(g.id));
  }
  expandedGroups.value = new Set(expandedGroups.value);
};

// ─── Budowanie spłaszczonego drzewa ──────────────────────────────────────────

/**
 * Węzeł w spłaszczonym drzewie.
 * depth=0 to top-level (grupy lub warianty bez grupy).
 */
interface FlatNode {
  variant: any;
  depth: number;
  isGroup: boolean;
  isEmpty?: boolean; // true gdy to wiersz "Brak wariantów w grupie"
}

/**
 * Zbuduj spłaszczone drzewo zachowując kolejność i wcięcia.
 *
 * Algorytm:
 * 1. Wyciągnij top-level (parent_variant_id=null), posortuj po variant_number
 * 2. Dla każdego węzła rekurencyjnie wstaw dzieci (jeśli expanded)
 * 3. Węzły niewidoczne (rodzic zwinięty) nie trafiają do wynikowej tablicy
 *
 * Obsługuje dowolną głębokość: A → A1 → A1_1 → A1_1_1 → ...
 */
const flatTree = computed<FlatNode[]>(() => {
  if (!props.variants?.length) return [];

  const result: FlatNode[] = [];

  function walk(parentId: number | null, depth: number) {
    const children = (props.variants ?? [])
      .filter((v) => (v.parent_variant_id ?? null) === parentId)
      .sort((a, b) =>
        a.variant_number.localeCompare(b.variant_number, "pl", { numeric: true })
      );

    for (const variant of children) {
      const isGroup = variant.is_group === true;
      result.push({ variant, depth, isGroup });

      // Wejdź w głąb tylko jeśli:
      //   a) to nie jest grupa (wariant ma dzieci podwariantów)
      //   b) to jest grupa I jest rozwinięta
      const shouldExpand = !isGroup || isExpanded(variant.id);
      if (shouldExpand) {
        const childrenExist = (props.variants ?? []).some(
          (v) => v.parent_variant_id === variant.id
        );
        if (isGroup && !childrenExist) {
          result.push({ variant, depth: depth + 1, isGroup: false, isEmpty: true });
        } else {
          walk(variant.id, depth + 1);
        }
      }
    }
  }

  walk(null, 0);
  return result;
});

// ─── Liczniki ─────────────────────────────────────────────────────────────────

/** Liczba wszystkich wariantów (bez grup) */
const variantsCount = computed(
  () => (props.variants ?? []).filter((v) => v.is_group !== true).length
);

/** Liczba bezpośrednich dzieci danego węzła */
const directChildCount = (parentId: number): number =>
  (props.variants ?? []).filter((v) => v.parent_variant_id === parentId).length;

// ─── Kolorowanie badge według głębokości ─────────────────────────────────────

/**
 * Kolor badge zależy od GŁĘBOKOŚCI w drzewie (depth):
 *   depth 0 → indigo      (A, B, C — grupy top-level)
 *   depth 1 → teal        (A1, B2 — warianty w grupach)
 *   depth 2 → deep-orange (A1_1 — podwarianty)
 *   depth 3+ → purple     (A1_1_1 i głębiej)
 *
 * Uwaga: głębokość odczytujemy z FlatNode, nie z variant_number!
 */
const DEPTH_COLORS: Record<number, string> = {
  0: "#3F51B5", // indigo
  1: "#009688", // teal
  2: "#F4511E", // deep-orange
  3: "#7B1FA2", // purple
};

function badgeColor(variantNumber: string): string {
  // Głębokość obliczona ze struktury numeru (jako fallback gdy brak depth)
  const depth = depthFromNumber(variantNumber);
  return DEPTH_COLORS[Math.min(depth, 3)] ?? "#9E9E9E";
}

function depthFromNumber(num: string): number {
  if (!num) return 0;
  const underscores = (num.match(/_/g) ?? []).length;
  if (underscores > 0) return underscores + 1;
  if (/^[A-Z]\d/i.test(num)) return 1;
  return 0;
}

function badgeFontSize(num: string): string {
  const len = num?.length ?? 0;
  if (len <= 1) return "1.1rem";
  if (len <= 2) return "0.95rem";
  if (len <= 4) return "0.8rem";
  if (len <= 6) return "0.7rem";
  return "0.62rem";
}

// ─── Wcięcia ─────────────────────────────────────────────────────────────────

/** Wcięcie poziome dla grup (px) */
function groupIndent(depth: number): string {
  // Każdy poziom = 20px dodatkowego wcięcia
  return `${16 + depth * 20}px`;
}

/** Wcięcie poziome dla wariantów (px) */
function variantIndent(depth: number): string {
  // Warianty mają trochę więcej bazowego wcięcia (space na strzałkę)
  return `${16 + depth * 20}px`;
}
</script>

<style scoped>
/* ═══════════════════════════════════════
   WIERSZ GRUPY
═══════════════════════════════════════ */
.group-row {
  display: flex;
  align-items: center;
  padding-top: 8px;
  padding-bottom: 8px;
  padding-right: 12px;
  cursor: pointer;
  background: rgba(63, 81, 181, 0.04);
  border-left: 3px solid #3f51b5;
  transition: background 0.15s;
  min-height: 50px;
}

.group-row:hover {
  background: rgba(63, 81, 181, 0.09);
}

.expand-icon {
  transition: transform 0.2s ease;
  flex-shrink: 0;
  margin-right: 4px;
}

.expand-icon--open {
  transform: rotate(90deg);
}

.group-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  min-width: 36px;
  height: 36px;
  padding: 0 8px;
  white-space: nowrap;
  color: #fff;
  font-weight: 700;
  font-family: "Roboto Mono", "Courier New", monospace;
  font-size: 0.95rem;
  letter-spacing: -0.02em;
  flex-shrink: 0;
}

.group-info {
  display: flex;
  align-items: baseline;
  min-width: 0;
}

.group-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: #3f51b5;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.group-children-count {
  font-size: 0.78rem;
  color: rgba(0, 0, 0, 0.42);
  flex-shrink: 0;
}

.group-actions {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  gap: 4px;
}

/* ═══════════════════════════════════════
   WIERSZ WARIANTU
═══════════════════════════════════════ */
.variant-row {
  display: flex;
  align-items: center;
  padding-top: 10px;
  padding-bottom: 10px;
  padding-right: 16px;
  cursor: pointer;
  border-left: 4px solid transparent;
  transition: background 0.15s, border-color 0.15s;
  min-height: 64px;
}

.variant-row:hover {
  background: rgba(0, 0, 0, 0.03);
  border-left-color: #3f51b5;
}

/* Connector drzewa */
.tree-connector {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  margin-right: 4px;
}

.child-arrow {
  font-size: 1rem;
  color: #bbb;
  line-height: 1;
}

/* Badge wariantu (pill) */
.variant-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  min-width: 44px;
  height: 44px;
  padding: 0 10px;
  white-space: nowrap;
  color: #fff;
  font-weight: 700;
  font-family: "Roboto Mono", "Courier New", monospace;
  letter-spacing: -0.02em;
  line-height: 1;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.22);
  flex-shrink: 0;
  transition: transform 0.15s;
}

.variant-row:hover .variant-badge {
  transform: scale(1.06);
}

/* Kolumna środkowa */
.info-col {
  flex: 1;
  min-width: 0;
}

.variant-name {
  font-weight: 600;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  line-height: 1.4;
}

.variant-desc {
  font-size: 0.8rem;
  color: rgba(0, 0, 0, 0.48);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Kolumna prawa */
.append-col {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  margin-left: 12px;
}

.quantity-block {
  text-align: right;
}

.quantity-label {
  font-size: 0.62rem;
  color: rgba(0, 0, 0, 0.42);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.quantity-value {
  font-weight: 700;
  font-size: 0.9rem;
  line-height: 1.2;
}
.empty-group-row {
  display: flex;
  align-items: center;
  padding-top: 8px;
  padding-bottom: 8px;
  padding-right: 16px;
  min-height: 40px;
  background: rgba(0, 0, 0, 0.012);
}

.empty-group-text {
  font-size: 0.82rem;
  color: rgba(0, 0, 0, 0.38);
  font-style: italic;
}
</style>
