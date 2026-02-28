<template>
  <v-card elevation="2" class="rounded-lg">
    <v-card-title class="bg-primary text-white d-flex align-center py-3 px-4">
      <v-icon start color="white">mdi-image-multiple</v-icon>
      <span class="text-subtitle-1 font-weight-bold">Galeria zdjęć zamówienia ({{ images.length }})</span>
      <v-spacer />
      <v-btn
        v-if="canUpload"
        color="white"
        variant="text"
        size="small"
        @click="uploadDialog = true"
      >
        <v-icon start>mdi-plus</v-icon>
        Dodaj
      </v-btn>
    </v-card-title>

    <v-card-text class="pa-4">
      <v-row v-if="images.length > 0" dense>
        <v-col
          v-for="(image, index) in images"
          :key="image.id"
          cols="4"
          sm="3"
          md="2"
        >
          <v-hover v-slot:default="{ isHovering, props: hoverProps }">
            <v-card
              v-bind="hoverProps"
              :elevation="isHovering ? 8 : 2"
              class="thumbnail-card rounded-md"
              @click="openFullscreen(index)"
            >
              <v-img :src="image.thumbnail_url || image.url" :aspect-ratio="1" cover>
                <v-overlay :model-value="isHovering" contained scrim="primary" class="align-center justify-center">
                  <v-icon color="white" size="32">mdi-magnify-plus</v-icon>
                </v-overlay>
              </v-img>
            </v-card>
          </v-hover>
        </v-col>
      </v-row>
      <div v-else class="text-center py-8 bg-grey-lighten-4 rounded-lg border-dashed">
        <v-icon size="48" color="grey-lighten-1">mdi-image-off-outline</v-icon>
        <p class="text-body-2 text-medium-emphasis mt-2">Brak zdjęć w tym zamówieniu</p>
      </div>
    </v-card-text>

    <v-dialog v-model="fullscreenDialog" max-width="1100px">
      <v-card color="white" class="rounded-lg overflow-hidden">
        <v-toolbar density="compact" color="white" border="bottom">
          <v-toolbar-title class="text-body-2 font-weight-bold">
            {{ images[currentIndex]?.description || images[currentIndex]?.filename || 'Podgląd' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon="mdi-download-outline" color="primary" variant="text" @click="downloadImage(images[currentIndex])" />
          <v-btn v-if="canDelete" icon="mdi-delete-outline" color="error" variant="text" @click="confirmDelete(images[currentIndex])" />
          <v-btn icon="mdi-close" variant="text" @click="fullscreenDialog = false" />
        </v-toolbar>

        <v-card-text class="pa-0 position-relative d-flex align-center justify-center bg-grey-lighten-4" style="min-height: 50vh; max-height: 70vh;">
          <v-btn v-if="images.length > 1" icon="mdi-chevron-left" variant="elevated" color="white" class="nav-btn left" @click="prevImage" />
          <v-img :key="currentIndex" :src="images[currentIndex]?.url" contain class="w-100 h-100" />
          <v-btn v-if="images.length > 1" icon="mdi-chevron-right" variant="elevated" color="white" class="nav-btn right" @click="nextImage" />
        </v-card-text>

        <v-divider />
        <v-sheet color="white" class="pa-3 d-flex justify-center gap-2 overflow-x-auto">
          <v-avatar v-for="(img, idx) in images" :key="img.id" size="50" class="cursor-pointer thumbnail-mini" :class="{ 'active-mini': idx === currentIndex }" @click="currentIndex = idx">
            <v-img :src="img.thumbnail_url || img.url" cover />
          </v-avatar>
        </v-sheet>
      </v-card>
    </v-dialog>

    <v-dialog v-model="uploadDialog" max-width="500">
      <v-card class="rounded-lg">
        <v-card-title class="bg-primary text-white">Dodaj zdjęcia do zamówienia</v-card-title>
        <v-card-text class="pt-6">
           <v-file-input v-input v-model="selectedFiles" label="Wybierz pliki" accept="image/*" multiple variant="outlined" show-size chips />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="uploadDialog = false">Anuluj</v-btn>
          <v-btn color="primary" variant="elevated" @click="handleUpload" :loading="uploading">Prześlij</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps({
  images: { type: Array, default: () => [] },
  canUpload: { type: Boolean, default: true },
  canDelete: { type: Boolean, default: true }
})

const emit = defineEmits(['upload', 'delete'])
const uploadDialog = ref(false)
const fullscreenDialog = ref(false)
const currentIndex = ref(0)
const selectedFiles = ref(null)
const uploading = ref(false)

const openFullscreen = (index) => {
  currentIndex.value = index
  fullscreenDialog.value = true
}

const nextImage = () => { currentIndex.value = (currentIndex.value + 1) % props.images.length }
const prevImage = () => { currentIndex.value = (currentIndex.value - 1 + props.images.length) % props.images.length }

const downloadImage = (image) => {
  const link = document.createElement('a');
  link.href = image.url;
  link.download = image.filename || 'zdjecie.jpg';
  link.click();
}

const handleUpload = async () => {
  if (!selectedFiles.value) return
  uploading.value = true
  emit('upload', { files: selectedFiles.value })
  uploadDialog.value = false
  selectedFiles.value = null
  uploading.value = false
}
</script>

<style scoped>
.thumbnail-card { cursor: pointer; overflow: hidden; transition: 0.2s; }
.thumbnail-card:hover { transform: translateY(-2px); }
.nav-btn { position: absolute; top: 50%; transform: translateY(-50%); z-index: 10; }
.nav-btn.left { left: 15px; }
.nav-btn.right { right: 15px; }
.thumbnail-mini { opacity: 0.4; border: 2px solid transparent; cursor: pointer; }
.active-mini { opacity: 1; border-color: #1976d2; transform: scale(1.1); }
.border-dashed { border: 2px dashed #ccc !important; }
</style>
