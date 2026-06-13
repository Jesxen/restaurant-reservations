export type Role = 'client' | 'staff' | 'admin';

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: Role;
  email_verified?: boolean;
  activo?: boolean;
  reservas_count?: number;
  created_at?: string | null;
}

export interface ForgotPasswordPayload {
  email: string;
}

export interface ResetPasswordPayload {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
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

export interface SettingsHorarios {
  comida: string;
  cena: string;
}

export interface SettingsHorariosDetalle {
  apertura_comida: string;
  cierre_comida: string;
  apertura_cena: string;
  cierre_cena: string;
}

export interface SettingsReservas {
  intervalo_slots: number;
  antelacion_min_horas: number;
  max_personas_online: number;
  ventana_dias: number;
}

export interface SettingsBranding {
  logo_url: string | null;
  color_primario: string | null;
  color_acento: string | null;
}

export interface SettingsContacto {
  email: string | null;
  telefono: string | null;
  direccion: string | null;
  ciudad: string | null;
}

export interface SettingsCoords {
  lat: number | null;
  lng: number | null;
}

export interface SettingsSocial {
  instagram: string | null;
  facebook: string | null;
  tiktok: string | null;
}

/** Public + admin settings shape (full SettingResource). */
export interface Settings {
  nombre_restaurante: string;
  aforo: number | null;
  ticket_medio: number;
  horarios: SettingsHorarios;
  horarios_detalle: SettingsHorariosDetalle;
  dias_cierre: number[];
  reservas: SettingsReservas;
  branding: SettingsBranding;
  contacto: SettingsContacto;
  coords: SettingsCoords;
  social: SettingsSocial;
  galeria: string[];
}
