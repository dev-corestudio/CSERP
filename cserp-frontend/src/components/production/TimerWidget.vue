<template>
  <v-card elevation="8" class="timer-card">
    <v-card-text class="pa-8">
      <!-- Nazwa zadania -->
      <div class="text-center mb-6">
        <h2 class="text-h4 font-weight-bold mb-2">{{ taskName }}</h2>
        <v-chip color="primary" variant="outlined">
          <v-icon start>mdi-wrench</v-icon>
          {{ workstation }}
        </v-chip>
      </div>

      <!-- Timer Display -->
      <div class="timer-display text-center mb-6">
        <div class="timer-time">{{ formattedTime }}</div>
        <div class="timer-estimated mt-2">
          Szacowany czas: {{ formattedEstimatedTime }}
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="mb-6">
        <v-progress-linear
          :model-value="progressPercent"
          :color="progressColor"
          height="30"
          rounded
        >
          <template v-slot:default="{ value }">
            <strong class="text-white">{{ Math.ceil(value) }}%</strong>
          </template>
        </v-progress-linear>
        <div class="text-center text-caption mt-2 text-medium-emphasis">
          {{ elapsedSeconds }} / {{ estimatedSeconds }} sekund
        </div>
      </div>

      <!-- Control Buttons -->
      <div class="d-flex justify-center gap-4 mb-4">
        <!-- START -->
        <v-btn
          v-if="!isRunning"
          color="success"
          size="x-large"
          @click="handleStart"
          :loading="loading"
        >
          <v-icon start>mdi-play</v-icon>
          START
        </v-btn>

        <!-- PAUSE/RESUME + STOP -->
        <template v-else>
          <v-btn
            v-if="!isPaused"
            color="warning"
            size="x-large"
            @click="handlePause"
            :loading="loading"
          >
            <v-icon start>mdi-pause</v-icon>
            PAUZA
          </v-btn>

          <v-btn
            v-else
            color="info"
            size="x-large"
            @click="handleResume"
            :loading="loading"
          >
            <v-icon start>mdi-play-circle</v-icon>
            WZNÓW
          </v-btn>

          <v-btn
            color="error"
            size="x-large"
            @click="handleStop"
            :loading="loading"
          >
            <v-icon start>mdi-stop</v-icon>
            ZAKOŃCZ
          </v-btn>
        </template>
      </div>

      <!-- Status -->
      <v-alert
        v-if="isPaused"
        type="warning"
        variant="tonal"
        prominent
        class="mb-4"
      >
        <v-alert-title>Timer wstrzymany</v-alert-title>
        Kliknij WZNÓW aby kontynuować pracę
      </v-alert>

      <v-alert
        v-else-if="isRunning"
        type="info"
        variant="tonal"
        prominent
        class="mb-4"
      >
        <v-alert-title>Timer aktywny</v-alert-title>
        Zadanie w trakcie realizacji
      </v-alert>

      <!-- Task Details -->
      <v-expansion-panels variant="accordion">
        <v-expansion-panel>
          <v-expansion-panel-title>
            <v-icon start>mdi-information</v-icon>
            Szczegóły zadania
          </v-expansion-panel-title>
          <v-expansion-panel-text>
            <v-list density="compact">
              <v-list-item>
                <template v-slot:prepend>
                  <v-icon>mdi-clock-outline</v-icon>
                </template>
                <v-list-item-title>Szacowany czas</v-list-item-title>
                <v-list-item-subtitle>{{ estimatedHours }} godzin</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template v-slot:prepend>
                  <v-icon>mdi-counter</v-icon>
                </template>
                <v-list-item-title>Ilość</v-list-item-title>
                <v-list-item-subtitle>{{ quantity }} szt</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template v-slot:prepend>
                  <v-icon>mdi-cash</v-icon>
                </template>
                <v-list-item-title>Koszt szacowany</v-list-item-title>
                <v-list-item-subtitle>{{ estimatedCost }} PLN</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useTimer } from '@/composables/useTimer'

const props = defineProps({
  taskId: {
    type: Number,
    required: true
  },
  taskName: {
    type: String,
    required: true
  },
  workstation: {
    type: String,
    required: true
  },
  estimatedHours: {
    type: Number,
    required: true
  },
  quantity: {
    type: Number,
    default: 1
  },
  estimatedCost: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['completed'])

const {
  isRunning,
  isPaused,
  elapsedSeconds,
  estimatedSeconds,
  formattedTime,
  formattedEstimatedTime,
  progressPercent,
  progressColor,
  start,
  pause,
  resume,
  stop
} = useTimer(props.taskId)

const loading = ref(false)

// Set estimated seconds from hours
estimatedSeconds.value = Math.floor(props.estimatedHours * 3600)

const handleStart = async () => {
  loading.value = true
  try {
    await start({
      estimated_time_hours: props.estimatedHours
    })
  } catch (error) {
    console.error('Start error:', error)
  } finally {
    loading.value = false
  }
}

const handlePause = async () => {
  loading.value = true
  try {
    await pause()
  } catch (error) {
    console.error('Pause error:', error)
  } finally {
    loading.value = false
  }
}

const handleResume = async () => {
  loading.value = true
  try {
    await resume()
  } catch (error) {
    console.error('Resume error:', error)
  } finally {
    loading.value = false
  }
}

const handleStop = async () => {
  loading.value = true
  try {
    const result = await stop()
    emit('completed', result)
  } catch (error) {
    console.error('Stop error:', error)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.timer-card {
  max-width: 800px;
  margin: 0 auto;
}

.timer-display {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 2rem;
  color: white;
}

.timer-time {
  font-size: 5rem;
  font-weight: 900;
  font-family: 'Roboto Mono', monospace;
  line-height: 1;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.timer-estimated {
  font-size: 1.2rem;
  opacity: 0.9;
}

.gap-4 {
  gap: 1rem;
}
</style>
