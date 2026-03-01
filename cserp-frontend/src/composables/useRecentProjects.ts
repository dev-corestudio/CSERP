import { useAuthStore } from '@/stores/auth'

const MAX = 7

interface RecentProject {
  id: number
  full_project_number: string
  description: string | null
  overall_status: string
  series: string | null
  customer: { name: string } | null
  viewed_at: string
}

function storageKey(userId: number | string): string {
  return `cserp_recent_projects_${userId}`
}

export function useRecentProjects() {
  const authStore = useAuthStore()

  function getAll(): RecentProject[] {
    const uid = authStore.user?.id
    if (!uid) return []
    try {
      return JSON.parse(localStorage.getItem(storageKey(uid)) ?? '[]')
    } catch {
      return []
    }
  }

  function track(project: any): void {
    const uid = authStore.user?.id
    if (!uid || !project?.id) return

    const entry: RecentProject = {
      id: project.id,
      full_project_number: project.full_project_number,
      description: project.description ?? null,
      overall_status: project.overall_status,
      series: project.series ?? null,
      customer: project.customer ? { name: project.customer.name } : null,
      viewed_at: new Date().toISOString(),
    }

    const existing = getAll().filter(p => p.id !== project.id)
    const updated = [entry, ...existing].slice(0, MAX)

    localStorage.setItem(storageKey(uid), JSON.stringify(updated))
  }

  return { getAll, track }
}
