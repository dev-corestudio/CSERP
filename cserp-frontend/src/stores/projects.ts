import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { projectService } from '@/services/projectService'
import { useSharedApiState } from '@/composables/useApiAction'
import type { Project } from '@/types'

export const useProjectsStore = defineStore('projects', () => {
  // ---------------------------------------------------------------------------
  // STATE
  // ---------------------------------------------------------------------------

  const projects = ref<Project[]>([])
  const currentProject = ref<Project | null>(null)

  // Wspólny stan loading/error dla wszystkich akcji tego store
  const { loading, error, wrapAction } = useSharedApiState()

  // ---------------------------------------------------------------------------
  // COMPUTED
  // ---------------------------------------------------------------------------

  const activeProjects = computed(() =>
    projects.value.filter(o => !['completed', 'cancelled'].includes(o.overall_status))
  )

  const completedProjects = computed(() =>
    projects.value.filter(o => o.overall_status === 'completed')
  )

  // ---------------------------------------------------------------------------
  // ACTIONS (przez wrapAction — automatyczny loading/error handling)
  // ---------------------------------------------------------------------------

  /** Pobierz listę projektów z opcjonalnymi filtrami */
  const fetchProjects = wrapAction(
    async (params: Record<string, any> = {}) => {
      const response = await projectService.getAll(params)
      projects.value = Array.isArray(response) ? response : []
      return projects.value
    },
    'Błąd pobierania projektów'
  )

  /** Pobierz szczegóły jednego projektu */
  const fetchProject = wrapAction(
    async (id: number | string) => {
      const response = await projectService.getById(id)
      currentProject.value = response
      return currentProject.value
    },
    'Błąd pobierania projektu'
  )

  /** Utwórz nowy projekt */
  const createProject = wrapAction(
    async (projectData: Partial<Project>) => {
      const newProject = await projectService.create(projectData)
      projects.value.unshift(newProject)
      return newProject
    },
    'Błąd tworzenia projektu'
  )

  /** Zaktualizuj istniejący projekt */
  const updateProject = wrapAction(
    async (id: number | string, data: Partial<Project>) => {
      const updated = await projectService.update(id, data)

      // Aktualizuj w liście
      const index = projects.value.findIndex(o => o.id === Number(id))
      if (index !== -1) projects.value[index] = updated

      // Aktualizuj bieżący (jeśli to on)
      if (currentProject.value?.id === Number(id)) currentProject.value = updated

      return updated
    },
    'Błąd aktualizacji projektu'
  )

  /** Usuń projekt */
  const deleteProject = wrapAction(
    async (id: number | string) => {
      await projectService.delete(id)
      projects.value = projects.value.filter(o => o.id !== Number(id))
    },
    'Błąd usuwania projektu'
  )

  return {
    // State
    projects,
    currentProject,
    loading,
    error,

    // Computed
    activeProjects,
    completedProjects,

    // Actions
    fetchProjects,
    fetchProject,
    createProject,
    updateProject,
    deleteProject,
  }
})
