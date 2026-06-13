import { Routes } from '@angular/router';
import { adminGuard, authGuard, staffGuard } from './core/guards';

export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    title: 'Restaurante La Laguna',
    loadComponent: () => import('./pages/landing/landing').then((m) => m.Landing),
  },
  {
    path: 'reservar',
    title: 'Reservar · Restaurante La Laguna',
    loadComponent: () => import('./pages/reservar/reservar').then((m) => m.Reservar),
  },
  {
    path: 'login',
    title: 'Entrar · Restaurante La Laguna',
    loadComponent: () => import('./pages/auth/login').then((m) => m.Login),
  },
  {
    path: 'registro',
    title: 'Registro · Restaurante La Laguna',
    loadComponent: () => import('./pages/auth/registro').then((m) => m.Registro),
  },
  {
    path: 'recuperar-password',
    title: 'Recuperar contraseña · Restaurante La Laguna',
    loadComponent: () => import('./pages/auth/recuperar').then((m) => m.Recuperar),
  },
  {
    path: 'restablecer-password',
    title: 'Restablecer contraseña · Restaurante La Laguna',
    loadComponent: () => import('./pages/auth/restablecer').then((m) => m.Restablecer),
  },
  {
    path: 'privacidad',
    title: 'Privacidad · Restaurante La Laguna',
    loadComponent: () => import('./pages/legal/privacidad').then((m) => m.Privacidad),
  },
  {
    path: 'terminos',
    title: 'Términos · Restaurante La Laguna',
    loadComponent: () => import('./pages/legal/terminos').then((m) => m.Terminos),
  },
  {
    path: 'contacto',
    title: 'Contacto · Restaurante La Laguna',
    loadComponent: () => import('./pages/contacto/contacto').then((m) => m.Contacto),
  },
  {
    path: 'cuenta',
    title: 'Mi cuenta · Restaurante La Laguna',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/cuenta/cuenta').then((m) => m.Cuenta),
  },
  {
    path: 'admin',
    canActivate: [staffGuard],
    loadComponent: () => import('./pages/admin/admin-layout').then((m) => m.AdminLayout),
    children: [
      { path: '', pathMatch: 'full', title: 'Dashboard · Admin', loadComponent: () => import('./pages/admin/dashboard/dashboard').then((m) => m.Dashboard) },
      { path: 'reservas', title: 'Reservas · Admin', loadComponent: () => import('./pages/admin/reservas/admin-reservas').then((m) => m.AdminReservas) },
      { path: 'mesas', title: 'Mesas · Admin', loadComponent: () => import('./pages/admin/mesas/admin-mesas').then((m) => m.AdminMesas) },
      { path: 'menu', title: 'Menú · Admin', loadComponent: () => import('./pages/admin/menu/admin-menu').then((m) => m.AdminMenu) },
      { path: 'cierres', title: 'Cierres · Admin', loadComponent: () => import('./pages/admin/cierres/admin-cierres').then((m) => m.AdminCierres) },
      { path: 'reviews', title: 'Reseñas · Admin', loadComponent: () => import('./pages/admin/reviews/admin-reviews').then((m) => m.AdminReviews) },
      { path: 'waitlist', title: 'Lista de espera · Admin', loadComponent: () => import('./pages/admin/waitlist/admin-waitlist').then((m) => m.AdminWaitlist) },
      { path: 'usuarios', canActivate: [adminGuard], title: 'Usuarios · Admin', loadComponent: () => import('./pages/admin/usuarios/admin-usuarios').then((m) => m.AdminUsuarios) },
      { path: 'ajustes', canActivate: [adminGuard], title: 'Ajustes · Admin', loadComponent: () => import('./pages/admin/ajustes/admin-ajustes').then((m) => m.AdminAjustes) },
    ],
  },
  { path: '**', redirectTo: '' },
];
