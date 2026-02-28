import { defineStore } from 'pinia'
import { ref } from 'vue'
import { assortmentService } from '@/services/assortmentService'
import type { AssortmentItem } from '@/types'

export const useAssortmentStore = defineStore('assortment', () => {
  const items = ref<AssortmentItem[]>([])
  const currentItem = ref<AssortmentItem | null>(null)
  const categories = ref<string[]>([])
  const history = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchItems = async (filters: any = {}) => {
    loading.value = true
    try {
      items.value = await assortmentService.getAll(filters)
    } catch (err) {
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  const fetchCategories = async (type: string | null = null) => {
    try {
      categories.value = await assortmentService.getCategories(type) || []
    } catch (err) {
      console.error(err)
    }
  }

  const createItem = async (data: Partial<AssortmentItem>) => {
    try {
      await assortmentService.create(data)
      await fetchItems()
      // Pobieramy kategorie, bo mogła dojść nowa
      await fetchCategories(data.type || null)
    } catch (err) {
      throw err
    }
  }

  const updateItem = async (id: number, data: Partial<AssortmentItem>) => {
    try {
      await assortmentService.update(id, data)
      await fetchItems()
      await fetchCategories(data.type || null)
    } catch (err) {
      throw err
    }
  }

  const deleteItem = async (id: number) => {
    try {
      await assortmentService.delete(id)
      await fetchItems()
    } catch (err) {
      throw err
    }
  }

  const toggleActive = async (id: number) => {
    try {
      await assortmentService.toggleActive(id)
      await fetchItems()
    } catch (err) {
      throw err
    }
  }

  const fetchHistory = async (id: number) => {
    loading.value = true
    try {
      history.value = await assortmentService.getHistory(id) || []
    } catch (err) {
      console.error('Fetch history error:', err)
      history.value = []
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    currentItem,
    categories,
    history,
    loading,
    error,
    fetchItems,
    fetchCategories,
    createItem,
    updateItem,
    deleteItem,
    toggleActive,
    fetchHistory
  }
})