import { createRouter, createWebHistory } from "vue-router";
import { useAuth } from "@/composables/useAuth";

const routes = [
  // ============ PUBLIC ============
  {
    path: "/login",
    name: "login",
    component: () => import("@/views/Login.vue"),
    meta: { public: true, title: "Login" },
  },
    {
    path: '/lupa-password',
    name: 'lupa-password',
    component: () => import('@/views/LupaPassword.vue'),
    meta: { public: true, title: 'Lupa Password' },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/ResetPassword.vue'),
    meta: { public: true, title: 'Reset Password' },
  },

  // ============ DASHBOARD ============
  {
    path: "/",
    name: "dashboard",
    component: () => import("@/views/Dashboard.vue"),
    meta: { title: "Dashboard" },
  },

  // ============ SPJ ============
  {
    path: "/spj",
    name: "spj.index",
    component: () => import("@/views/spj/DaftarSpj.vue"),
    meta: { title: "Daftar SPJ" },
  },
  {
    path: "/spj/buat",
    name: "spj.buat",
    component: () => import("@/views/spj/BuatSpj.vue"),
    meta: { roles: ["user"], title: "Buat SPJ Baru" },
  },
    {
    path: '/spj/:slug',
    name: 'spj.detail',
    component: () => import('@/views/spj/DetailSpj.vue'),
    props: true,
    meta: { title: 'Detail SPJ' },
  },
  {
    path: '/spj/:slug/edit',
    name: 'spj.edit',
    component: () => import('@/views/spj/EditSpj.vue'),
    props: true,
    meta: { roles: ['user'], title: 'Edit SPJ' },
  },

  // ============ RESPONSIBLE ============
  {
    path: "/responsible",
    name: "responsible",
    component: () => import("@/views/ResponsibleStandalone.vue"),
    meta: { title: "Kelola Responsible" },
    // CATATAN: 'standalone' DIHAPUS — supaya masuk layout sidebar
  },

  // ============ PROFILE ============
  {
    path: "/profile",
    name: "profile",
    component: () => import("@/views/Profile.vue"),
    meta: { title: "Profil Saya" },
  },

  // ============ ADMIN ============
  {
    path: "/jenis-spj",
    name: "jenis-spj",
    component: () => import("@/views/admin/KelolaJenisSpj.vue"),
    meta: { roles: ["admin"], title: "Jenis SPJ" },
  },
  {
    path: "/kelola-user",
    name: "kelola-user",
    component: () => import("@/views/admin/KelolaUser.vue"),
    meta: { roles: ["admin"], title: "Kelola User" },
  },
  {
    path: "/kelola-backup",
    name: "kelola-backup",
    component: () => import("@/views/admin/KelolaBackup.vue"),
    meta: { roles: ["admin"], title: "Backup Database" },
  },

    {
    path: '/log-aktivitas',
    name: 'log-aktivitas',
    component: () => import('@/views/admin/LogActivity.vue'),
    meta: { roles: ['admin'], title: 'Log Aktivitas' },
  },

  // ============ 404 FALLBACK ============
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

/**
 * Global guard:
 *  1. Public route → lolos tanpa cek auth
 *  2. Belum login   → redirect ke /login
 *  3. Role tidak cocok → redirect ke dashboard
 *  4. Update document.title dari meta.title
 */
router.beforeEach((to) => {
  const { isLoggedIn, user } = useAuth();

  // 1. Halaman public (login)
  if (to.meta.public) return true;

  // 2. Wajib login
  if (!isLoggedIn.value) {
    return { name: "login" };
  }

  // 3. Role-based access
  if (to.meta.roles && !to.meta.roles.includes(user.value?.role)) {
    return { name: "dashboard" };
  }

  return true;
});

// Update document.title setiap kali navigasi sukses
router.afterEach((to) => {
  const baseTitle = "Sistem SPJ";
  document.title = to.meta.title
    ? `${to.meta.title} — ${baseTitle}`
    : baseTitle;
});

export default router;
