import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Observable, map, shareReplay, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import { Settings } from './user.model';

/** Sensible defaults used until /api/settings resolves (or if it fails). */
export const DEFAULT_SETTINGS: Settings = {
  nombre_restaurante: 'Restaurante La Laguna',
  aforo: null,
  ticket_medio: 35,
  horarios: {
    comida: '13:00–16:00',
    cena: '20:00–23:30',
  },
  horarios_detalle: {
    apertura_comida: '13:00',
    cierre_comida: '16:00',
    apertura_cena: '20:00',
    cierre_cena: '23:30',
  },
  dias_cierre: [1],
  reservas: {
    intervalo_slots: 30,
    antelacion_min_horas: 2,
    max_personas_online: 12,
    ventana_dias: 60,
  },
  branding: {
    logo_url: null,
    color_primario: null,
    color_acento: null,
  },
  contacto: {
    email: 'reservas@laguna.com',
    telefono: '+34 922 000 000',
    direccion: 'Calle La Carrera, 1 · San Cristóbal de La Laguna, Tenerife',
    ciudad: 'San Cristóbal de La Laguna',
  },
  coords: {
    lat: 28.4874,
    lng: -16.3159,
  },
  social: {
    instagram: null,
    facebook: null,
    tiktok: null,
  },
  galeria: [],
};

@Injectable({ providedIn: 'root' })
export class SettingsService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  /** Reactive snapshot of the public settings (defaults until loaded). */
  readonly settings = signal<Settings>(DEFAULT_SETTINGS);

  private settings$?: Observable<Settings>;

  /** Cached, shared GET /api/settings. Subscribing once is enough. */
  load(): Observable<Settings> {
    if (!this.settings$) {
      this.settings$ = this.http.get<Settings>(`${this.api}/settings`).pipe(
        tap((s) => this.settings.set(s)),
        shareReplay({ bufferSize: 1, refCount: false }),
      );
    }
    return this.settings$;
  }

  /** Apply optional branding colors as CSS variables (progressive enhancement). */
  applyBranding(s: Settings): void {
    const root = document.documentElement;
    if (s.branding.color_primario) {
      root.style.setProperty('--color-primary', s.branding.color_primario);
    }
    if (s.branding.color_acento) {
      root.style.setProperty('--color-accent', s.branding.color_acento);
    }
  }
}

/** Map a settings object's social entries into renderable links. */
export function socialLinks(s: Settings): { label: string; url: string }[] {
  const out: { label: string; url: string }[] = [];
  if (s.social.instagram) out.push({ label: 'Instagram', url: s.social.instagram });
  if (s.social.facebook) out.push({ label: 'Facebook', url: s.social.facebook });
  if (s.social.tiktok) out.push({ label: 'TikTok', url: s.social.tiktok });
  return out;
}
