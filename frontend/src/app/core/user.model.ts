export type Role = 'client' | 'staff' | 'admin';

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: Role;
  activo?: boolean;
  reservas_count?: number;
  created_at?: string | null;
}

export interface AuthResponse {
  token: string;
  user: User;
}

export interface Credentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
}

export interface NuevoUsuario {
  name: string;
  email: string;
  phone?: string | null;
  role: Role;
  password?: string;
  activo?: boolean;
}

export interface Settings {
  nombre_restaurante: string;
  aforo: number | null;
  apertura_comida: string;
  cierre_comida: string;
  apertura_cena: string;
  cierre_cena: string;
  duracion_turno: number;
  ticket_medio: number;
}
