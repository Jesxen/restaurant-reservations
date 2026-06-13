export type WaitlistEstado = 'esperando' | 'avisado' | 'convertida' | 'cancelada' | 'expirada';

export interface WaitlistEntry {
  id: number;
  nombre: string;
  email?: string;
  telefono?: string | null;
  fecha: string; // YYYY-MM-DD
  hora: string; // HH:mm
  personas: number;
  estado: WaitlistEstado;
  created_at?: string | null;
}

/** POST /api/waitlist body. */
export interface NuevaWaitlist {
  nombre: string;
  email: string;
  telefono?: string;
  fecha: string;
  hora: string; // H:i
  personas: number;
}
