import { defineStore } from 'pinia'
import { ref } from 'vue'

interface PageInfo {
  title?: string;
  subtitle?: string;
  icon?: string;
  iconColor?: string;
  breadcrumbs?: any[];
}

export const useLayoutStore = defineStore('layout', () => {
  // Page info
  const pageTitle = ref('Dashboard')
  const pageSubtitle = ref('')
  const pageIcon = ref('mdi-view-dashboard')
  const pageIconColor = ref('primary')
  const breadcrumbs = ref<any[]>([])

  // Actions
  const setPageInfo = (info: PageInfo) => {
    if (info.title) pageTitle.value = info.title
    if (info.subtitle !== undefined) pageSubtitle.value = info.subtitle
    if (info.icon) pageIcon.value = info.icon
    if (info.iconColor) pageIconColor.value = info.iconColor
    if (info.breadcrumbs) breadcrumbs.value = info.breadcrumbs
  }

  const resetPageInfo = () => {
    pageTitle.value = 'Dashboard'
    pageSubtitle.value = ''
    pageIcon.value = 'mdi-view-dashboard'
    pageIconColor.value = 'primary'
    breadcrumbs.value = []
  }

  return {
    pageTitle,
    pageSubtitle,
    pageIcon,
    pageIconColor,
    breadcrumbs,
    setPageInfo,
    resetPageInfo
  }
})