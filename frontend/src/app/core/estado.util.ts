import { EstadoReserva } from './reserva.model';

export const ESTADO_LABEL: Record<EstadoReserva, string> = {
  pendiente: 'Pendiente',
  confirmada: 'Confirmada',
  cancelada: 'Cancelada',
  completada: 'Completada',
  no_show: 'No-show',
};

export const ESTADO_BADGE: Record<EstadoReserva, string> = {
  pendiente: 'badge-soft badge-warning',
  confirmada: 'badge-soft badge-success',
  cancelada: 'badge-soft badge-error',
  completada: 'badge-soft badge-info',
  no_show: 'badge-soft badge-neutral',
};

export const ESTADOS: EstadoReserva[] = [
  'pendiente',
  'confirmada',
  'cancelada',
  'completada',
  'no_show',
];
