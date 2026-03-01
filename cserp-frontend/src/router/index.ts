// src/router/index.ts
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: Array<RouteRecordRaw> = [
  // ============================================================================
  // PRZEKIEROWANIE GŁÓWNE
  // ============================================================================
  {
    path: '/',
    redirect: () => {
      const mode = localStorage.getItem('login_mode')
      if (!mode || mode === 'pin') {
        return '/login-pin'
      }
      return '/login'
    }
  },

  // ============================================================================
  // AUTENTYKACJA
  // ============================================================================
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: {
      guestOnly: true,
      title: 'Logowanie'
    }
  },
  {
    path: '/login-pin',
    name: 'LoginPin',
    component: () => import('@/views/auth/LoginPin.vue'),
    meta: {
      guestOnly: true,
      title: 'Logowanie PIN'
    }
  },

  // ============================================================================
  // DASHBOARD
  // ============================================================================
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      title: 'Dashboard'
    }
  },

  // ============================================================================
  // PROJEKTY (tylko dla zarządzających)
  // ============================================================================
  {
    path: '/projects',
    name: 'ProjectList',
    component: () => import('@/views/projects/ProjectList.vue'),
    meta: {
      requiresAuth: true,
      requiresManager: true, // Flaga: wymaga uprawnień zarządczych
      title: 'Projekty'
    }
  },
  {
    path: '/projects/:id',
    name: 'ProjectDetail',
    component: () => import('@/views/projects/ProjectDetail.vue'),
    meta: {
      requiresAuth: true,
      requiresManager: true,
      title: 'Szczegóły projektu'
    }
  },
  {
    path: '/projects/:projectId/variants/:id',
    name: 'VariantDetail',
    component: () => import('@/views/projects/VariantDetail.vue'),
    meta: {
      requiresAuth: true,
      requiresManager: true,
      title: 'Szczegóły Wariantu'
    }
  },

  // ============================================================================
  // KLIENCI (tylko dla zarządzających)
  // ============================================================================
  {
    path: '/customers',
    name: 'CustomerList',
    component: () => import('@/views/customers/CustomerList.vue'),
    meta: {
      requiresAuth: true,
      requiresManager: true,
      title: 'Klienci'
    }
  },
  {
    path: '/customers/:id',
    name: 'CustomerDetail',
    component: () => import('@/views/customers/CustomerDetail.vue'),
    meta: {
      requiresAuth: true,
      requiresManager: true,
      title: 'Szczegóły klienta'
    }
  },

  // ============================================================================
  // ASORTYMENT (tylko dla zarządzających)
  // ============================================================================
  {
    path: '/assortment',
    name: 'AssortmentList',
    component: () => import('@/views/assortment/AssortmentList.vue'),
    meta: {
      requiresAuth: true,
      requiresManager: true,
      title: 'Asortyment'
    }
  },

  // ============================================================================
  // PRODUKCJA - PANEL ZARZĄDCZY (tylko dla zarządzających)
  // ============================================================================


  {
    path: '/admin/rcp',
    name: 'RcpManagement',
    component: () => import('@/views/production/RcpManagement.vue'),
    meta: {
      requiresAuth: true,
      title: 'Zarządzanie RCP'
    }
  },

  // ============================================================================
  // STANOWISKA (tylko dla zarządzających)
  // ============================================================================
  {
    path: '/workstations',
    name: 'WorkstationList',
    component: () => import('@/views/workstations/WorkstationList.vue'),
    meta: {
      requiresAuth: true,
      requiresManager: true,
      title: 'Stanowiska Robocze'
    }
  },

  // ============================================================================
  // RCP - PANEL PRACOWNIKA (dostępny dla wszystkich zalogowanych)
  // ============================================================================
  {
    path: '/rcp/workstation',
    name: 'WorkstationSelect',
    component: () => import('@/views/rcp/WorkstationSelect.vue'),
    meta: {
      requiresAuth: true,
      title: 'Wybór stanowiska'
    }
  },
  {
    path: '/rcp/timer/:taskId',
    name: 'Timer',
    component: () => import('@/views/rcp/Timer.vue'),
    meta: {
      requiresAuth: true,
      title: 'Timer zadania'
    }
  },

  {
    path: '/users',
    name: 'UserList',
    component: () => import('@/views/users/UserList.vue'),
    meta: {
      title: 'Użytkownicy',
      requiresAuth: true
    }
  },

  // ============================================================================
  // STRONA 404 (opcjonalnie)
  // ============================================================================
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    redirect: () => {
      const authStore = useAuthStore()
      if (authStore.isAuthenticated) {
        if (authStore.isWorker) {
          return '/rcp/workstation'
        }
        return '/dashboard'
      }
      return '/login'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// ============================================================================
// NAVIGATION GUARD - Kontrola dostępu
// ============================================================================
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Pobierz flagi z meta
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const guestOnly = to.matched.some(record => record.meta.guestOnly)
  const requiresManager = to.matched.some(record => record.meta.requiresManager)

  // ---------------------------------------------------------------------------
  // 1. Przekierowanie z Login na LoginPin jeśli tryb PIN
  // ---------------------------------------------------------------------------
  if (!authStore.token && to.name === 'Login') {
    const mode = localStorage.getItem('login_mode')
    if (mode && mode === 'pin') {
      return next({ name: 'LoginPin' })
    }
  }

  // ---------------------------------------------------------------------------
  // 2. Strona wymaga autoryzacji, ale użytkownik nie jest zalogowany
  // ---------------------------------------------------------------------------
  if (requiresAuth && !authStore.token) {
    const mode = localStorage.getItem('login_mode')
    const loginRoute = (mode === 'standard') ? 'Login' : 'LoginPin'

    return next({
      name: loginRoute,
      query: { redirect: to.fullPath }
    })
  }

  // ---------------------------------------------------------------------------
  // 3. Użytkownik ma token ale nie ma danych usera - pobierz je
  // ---------------------------------------------------------------------------
  if (requiresAuth && authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (err) {
      console.error('Błąd pobierania danych użytkownika:', err)
      await authStore.logout()
      return next({ path: '/' })
    }
  }

  // Zabezpieczenie tras admina
  if (to.path.startsWith('/admin') && !authStore.canManageSystem) {
    return next({ name: 'WorkstationSelect' }) // Przekieruj workera
  }

  // ---------------------------------------------------------------------------
  // 4. Strona tylko dla gości, ale użytkownik jest zalogowany
  // ---------------------------------------------------------------------------
  if (guestOnly && authStore.isAuthenticated) {
    // Pracownicy produkcyjni → Panel RCP
    if (authStore.isWorker) {
      return next({ name: 'WorkstationSelect' })
    }
    // Pozostali (Admin, Manager, Trader, etc.) → Dashboard
    return next({ name: 'Dashboard' })
  }

  // ---------------------------------------------------------------------------
  // 5. Strona wymaga uprawnień zarządczych (requiresManager)
  // ---------------------------------------------------------------------------
  if (requiresManager && authStore.isAuthenticated) {
    // Sprawdź czy użytkownik ma uprawnienia zarządcze
    if (!authStore.canManageSystem) {
      console.warn(`Użytkownik ${authStore.user?.name} nie ma uprawnień do: ${to.path}`)

      // Pracownik produkcyjny → przekieruj do RCP
      if (authStore.isWorker) {
        return next({ name: 'WorkstationSelect' })
      }

      // Inny użytkownik bez uprawnień → Dashboard
      return next({ name: 'Dashboard' })
    }
  }

  // ---------------------------------------------------------------------------
  // 6. Dodatkowa walidacja dla pracowników produkcyjnych
  //    (zabezpieczenie przed ręcznym wpisaniem URL)
  // ---------------------------------------------------------------------------
  if (requiresAuth && authStore.isAuthenticated && authStore.isWorker) {
    // Lista dozwolonych ścieżek dla pracowników produkcyjnych
    const allowedPathsForWorker = [
      '/dashboard',
      '/rcp/workstation',
      '/rcp/timer'
    ]

    // Sprawdź czy ścieżka jest dozwolona
    const isAllowedPath = allowedPathsForWorker.some(path =>
      to.path === path || to.path.startsWith(path + '/')
    )

    if (!isAllowedPath) {
      console.warn(`Pracownik ${authStore.user?.name} próbował wejść na: ${to.path}`)
      return next({ name: 'WorkstationSelect' })
    }
  }

  // ---------------------------------------------------------------------------
  // 7. Aktualizacja tytułu strony (opcjonalnie)
  // ---------------------------------------------------------------------------
  if (to.meta.title) {
    document.title = `${to.meta.title} | CSERP`
  } else {
    document.title = 'CSERP'
  }

  // ---------------------------------------------------------------------------
  // 8. Wszystko OK - kontynuuj nawigację
  // ---------------------------------------------------------------------------
  next()
})

// ============================================================================
// AFTER EACH - Dodatkowe akcje po nawigacji (opcjonalnie)
// ============================================================================
router.afterEach((to, from) => {
  // Można tutaj dodać logowanie, analytics, etc.
  // console.log(`Nawigacja: ${from.path} → ${to.path}`)
})

export default router