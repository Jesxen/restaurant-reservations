import { EstadoReserva } from './reserva.model';

export interface BlackoutDate {
  id: number;
  fecha: string; // YYYY-MM-DD
  motivo: string | null;
}

export interface NuevoBlackout {
  fecha: string;
  motivo?: string | null;
}

export interface DashboardData {
  reservas_hoy: number;
  comensales_hoy: number;
  pendientes: number;
  ocupacion_hoy: number;
  capacidad_total: number;
  ingresos_estimados_hoy: number;
  tasa_cancelacion: number;
  ticket_medio: number;
  por_estado: Record<EstadoReserva, number>;
  proximos_dias: { fecha: string; reservas: number; comensales: number }[];
  por_franja: { hora: string; comensales: number }[];
}
