import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '../layouts/PublicLayout.vue'

const routes = [
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '', name: 'home', component: () => import('../pages/HomePage.vue') },
      {
        path: 'centres',
        name: 'centres',
        component: () => import('../pages/CentresPage.vue'),
      },
      {
        path: 'donor/register',
        name: 'donor-register',
        component: () => import('../pages/DonorRegisterPage.vue'),
      },
      {
        path: 'appointments/book',
        name: 'appointment-book',
        component: () => import('../pages/AppointmentBookPage.vue'),
      },
      {
        path: 'lookup',
        name: 'lookup',
        component: () => import('../pages/LookupPage.vue'),
      },
      {
        path: ':pathMatch(.*)*',
        name: 'not-found',
        component: () => import('../pages/NotFoundPage.vue'),
      },
    ],
  },
]

export default createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})
