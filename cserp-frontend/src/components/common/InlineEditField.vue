<template>
  <div
    class="inline-edit-container rounded px-2 py-1"
    :class="{ hoverable: !isEditing && !loading, editing: isEditing }"
    @click="startEditing"
  >
    <!-- TRYB EDYCJI -->
    <div v-if="isEditing" class="d-flex align-center w-100">
      <!-- SELECT (np. Status) -->
      <v-select
        v-if="type === 'select'"
        ref="inputRef"
        v-model="internalValue"
        v-model:menu="isMenuOpen"
        :items="items"
        :item-title="itemTitle"
        :item-value="itemValue"
        variant="outlined"
        density="compact"
        hide-details
        menu-icon="mdi-chevron-down"
        class="flex-grow-1"
        @update:model-value="save"
        @keydown.esc="cancel"
      >
        <!-- Przekazywanie slotu dla wybranego elementu (W TRAKCIE EDYCJI) -->
        <template v-if="$slots.selection" v-slot:selection="slotProps">
          <slot name="selection" v-bind="slotProps"></slot>
        </template>

        <!-- Przekazywanie slotu dla elementów rozwijanej listy -->
        <template v-if="$slots.item" v-slot:item="slotProps">
          <slot name="item" v-bind="slotProps"></slot>
        </template>
      </v-select>

      <!-- TEXTAREA -->
      <v-textarea
        v-else-if="type === 'textarea'"
        ref="inputRef"
        v-model="internalValue"
        variant="outlined"
        density="compact"
        hide-details
        auto-grow
        rows="1"
        class="flex-grow-1"
        @keydown.enter.prevent="save"
        @keydown.esc="cancel"
        @blur="save"
      ></v-textarea>

      <!-- TEXT / NUMBER / DATE -->
      <v-text-field
        v-else
        ref="inputRef"
        v-model="internalValue"
        :type="inputType"
        variant="outlined"
        density="compact"
        hide-details
        single-line
        @keydown.enter="save"
        @keydown.esc="cancel"
        @blur="save"
      ></v-text-field>
    </div>

    <!-- TRYB WYŚWIETLANIA -->
    <div
      v-else
      :class="textClass"
      class="d-flex align-center w-100"
      style="min-height: 32px"
    >
      <!-- Loading Spinner -->
      <v-progress-circular
        v-if="loading"
        indeterminate
        size="20"
        width="2"
        color="primary"
        class="mr-2"
      />

      <div
        :class="textClass"
        class="flex-grow-1 d-flex align-center justify-space-between"
      >
        <!-- Ikona ołówka Z LEWEJ -->
        <v-icon
          v-if="!loading && showEditIcon && iconPosition === 'left'"
          icon="mdi-pencil"
          size="x-small"
          class="edit-icon mr-2 text-medium-emphasis"
        ></v-icon>

        <!-- Wartość -->
        <slot name="display" :value="modelValue">
          <span v-if="displayValue" :class="textClass" style="white-space: pre-wrap">{{
            displayValue
          }}</span>
          <span v-else :class="textClass" class="text-medium-emphasis font-italic">{{
            placeholder
          }}</span>
        </slot>

        <!-- Ikona ołówka Z PRAWEJ (domyślnie) -->
        <v-icon
          v-if="!loading && showEditIcon && iconPosition === 'right'"
          icon="mdi-pencil"
          size="x-small"
          class="edit-icon ml-2 text-medium-emphasis"
        ></v-icon>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch } from "vue";

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: "",
  },
  type: {
    type: String,
    default: "text", // 'text', 'number', 'textarea', 'select', 'date'
  },
  items: {
    type: Array,
    default: () => [],
  },
  itemTitle: {
    type: String,
    default: "title",
  },
  itemValue: {
    type: String,
    default: "value",
  },
  placeholder: {
    type: String,
    default: "Kliknij, aby edytować...",
  },
  textClass: {
    type: String,
    default: "",
  },
  loading: {
    type: Boolean,
    default: false,
  },
  suffix: {
    type: String,
    default: "",
  },
  showEditIcon: {
    type: Boolean,
    default: true,
  },
  iconPosition: {
    type: String as () => "left" | "right",
    default: "right", // Domyślnie z prawej
  },
});

const emit = defineEmits(["update:modelValue", "save"]);

const isEditing = ref(false);
const isMenuOpen = ref(false); // <--- NOWE: Steruje otwarciem listy rozwijanej
const internalValue = ref(props.modelValue);
const inputRef = ref(null);

const inputType = computed(() => {
  if (["number", "date", "datetime-local"].includes(props.type)) return props.type;
  return "text";
});

const displayValue = computed(() => {
  if (
    props.modelValue === null ||
    props.modelValue === undefined ||
    props.modelValue === ""
  )
    return null;
  return props.suffix ? `${props.modelValue} ${props.suffix}` : props.modelValue;
});

watch(
  () => props.modelValue,
  (newVal) => {
    internalValue.value = newVal;
  }
);

// Obserwator zamykania menu selecta (np. kliknięcie obok)
watch(isMenuOpen, (isOpen) => {
  if (!isOpen && isEditing.value) {
    cancel();
  }
});

const startEditing = async () => {
  if (props.loading || isEditing.value) return;
  internalValue.value = props.modelValue;
  isEditing.value = true;

  await nextTick();

  if (props.type === "select") {
    // Od razu otwórz dropdown!
    isMenuOpen.value = true;
  } else {
    // Focus na zwykły input
    const el = inputRef.value;
    if (el && typeof (el as any).focus === "function") {
      (el as any).focus();
    }
  }
};

const save = () => {
  if (internalValue.value !== props.modelValue) {
    emit("save", internalValue.value);
  }
  isEditing.value = false;
  isMenuOpen.value = false;
};

const cancel = () => {
  setTimeout(() => {
    isEditing.value = false;
    isMenuOpen.value = false;
    internalValue.value = props.modelValue;
  }, 100);
};
</script>

<style scoped>
.inline-edit-container {
  border: 1px solid transparent;
  cursor: pointer;
  position: relative;
  min-height: 40px;
  display: flex;
  align-items: center;
}

.hoverable:hover {
  background-color: rgba(0, 0, 0, 0.04);
  border-radius: 4px;
}

.edit-icon {
  opacity: 0;
  transition: opacity 0.2s;
}

.hoverable:hover .edit-icon {
  opacity: 1;
}

.editing {
  cursor: default;
  padding: 0 !important;
  background-color: transparent !important;
}
</style>
