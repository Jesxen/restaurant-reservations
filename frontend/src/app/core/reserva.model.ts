export type EstadoReserva =
  | 'pendiente'
  | 'confirmada'
  | 'cancelada'
  | 'completada'
  | 'no_show';

export interface Mesa {
  id: number;
  numero: number;
  capacidad: number;
  activa: boolean;
}

export interface ReservaEvento {
  id: number;
  estado_anterior: string | null;
  estado_nuevo: string;
  usuario: string | null;
  created_at: string | null;
}

export interface Reserva {
  id: number;
  nombre: string;
  email: string;
  fecha: string; // YYYY-MM-DD
  hora: string;  // HH:mm
  personas: number;
  estado: EstadoReserva;
  notas: string | null;
  notas_internas?: string | null;
  mesa_id: number | null;
  mesa: Mesa | null;
  user_id: number | null;
  cancelable: boolean;
  eventos?: ReservaEvento[];
  created_at: string | null;
}

export interface NuevaReserva {
  nombre: string;
  email: string;
  fecha: string;
  hora: string;
  personas: number;
  notas?: string;
}

export interface EditarReserva {
  fecha: string;
  hora: string;
  personas: number;
  notas?: string | null;
}

/** A single bookable slot returned by GET /api/horarios. */
export interface HorarioSlot {
  hora: string; // HH:mm
  disponible: boolean;
  plazas_disponibles: number;
}

/** Response of GET /api/horarios?fecha=YYYY-MM-DD. */
export interface Horarios {
  fecha: string;
  abierto: boolean;
  motivo_cierre: string | null;
  slots: HorarioSlot[];
}

export interface Disponibilidad {
  fecha: string;
  hora: string;
  capacidad_total: number;
  plazas_disponibles: number;
  disponible: boolean;
}

export interface ApiValidationError {
  message: string;
  errors: Record<string, string[]>;
}
