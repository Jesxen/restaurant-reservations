import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';
import { DashboardData } from './admin.model';
import { Categoria, Plato } from './menu.model';
import { Mesa, Reserva } from './reserva.model';
import { NuevoUsuario, Settings, User } from './user.model';

@Injectable({ providedIn: 'root' })
export class AdminService {
  private readonly http = inject(HttpClient);
  private readonly api = `${environment.apiUrl}/admin`;

  // --- Dashboard ---
  dashboard(): Observable<DashboardData> {
    return this.http.get<DashboardData>(`${this.api}/dashboard`);
  }

  // --- Reservas ---
  reservas(filters: { fecha?: string; estado?: string; q?: string } = {}): Observable<Reserva[]> {
    const params: Record<string, string> = {};
    if (filters.fecha) params['fecha'] = filters.fecha;
    if (filters.estado) params['estado'] = filters.estado;
    if (filters.q) params['q'] = filters.q;
    return this.http
      .get<{ data: Reserva[] }>(`${this.api}/reservas`, { params })
      .pipe(map((r) => r.data));
  }

  reserva(id: number): Observable<Reserva> {
    return this.http.get<{ data: Reserva }>(`${this.api}/reservas/${id}`).pipe(map((r) => r.data));
  }

  updateReserva(
    id: number,
    payload: Partial<Pick<Reserva, 'estado' | 'mesa_id' | 'notas' | 'notas_internas'>>,
  ): Observable<Reserva> {
    return this.http
      .patch<{ data: Reserva }>(`${this.api}/reservas/${id}`, payload)
      .pipe(map((r) => r.data));
  }

  exportReservasUrl(filters: { fecha?: string; estado?: string; q?: string } = {}): string {
    const params = new URLSearchParams();
    if (filters.fecha) params.set('fecha', filters.fecha);
    if (filters.estado) params.set('estado', filters.estado);
    if (filters.q) params.set('q', filters.q);
    const qs = params.toString();
    return `${this.api}/reservas/export${qs ? '?' + qs : ''}`;
  }

  /** Download the CSV (sends the bearer token via fetch, then triggers a file save). */
  downloadReservasCsv(filters: { fecha?: string; estado?: string; q?: string }, token: string | null): void {
    fetch(this.exportReservasUrl(filters), {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    })
      .then((res) => res.blob())
      .then((blob) => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `reservas_${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
      });
  }

  // --- Usuarios ---
  usuarios(filters: { q?: string; role?: string } = {}): Observable<User[]> {
    const params: Record<string, string> = {};
    if (filters.q) params['q'] = filters.q;
    if (filters.role) params['role'] = filters.role;
    return this.http.get<{ data: User[] }>(`${this.api}/usuarios`, { params }).pipe(map((r) => r.data));
  }
  createUsuario(payload: NuevoUsuario): Observable<User> {
    return this.http.post<{ data: User }>(`${this.api}/usuarios`, payload).pipe(map((r) => r.data));
  }
  updateUsuario(id: number, payload: Partial<NuevoUsuario>): Observable<User> {
    return this.http.put<{ data: User }>(`${this.api}/usuarios/${id}`, payload).pipe(map((r) => r.data));
  }
  deleteUsuario(id: number): Observable<void> {
    return this.http.delete<void>(`${this.api}/usuarios/${id}`);
  }

  // --- Settings ---
  settings(): Observable<Settings> {
    return this.http.get<{ data: Settings }>(`${this.api}/settings`).pipe(map((r) => r.data));
  }
  updateSettings(payload: Settings): Observable<Settings> {
    return this.http.patch<{ data: Settings }>(`${this.api}/settings`, payload).pipe(map((r) => r.data));
  }

  // --- Mesas ---
  mesas(): Observable<Mesa[]> {
    return this.http.get<{ data: Mesa[] }>(`${this.api}/mesas`).pipe(map((r) => r.data));
  }
  createMesa(payload: Partial<Mesa>): Observable<Mesa> {
    return this.http.post<{ data: Mesa }>(`${this.api}/mesas`, payload).pipe(map((r) => r.data));
  }
  updateMesa(id: number, payload: Partial<Mesa>): Observable<Mesa> {
    return this.http.put<{ data: Mesa }>(`${this.api}/mesas/${id}`, payload).pipe(map((r) => r.data));
  }
  deleteMesa(id: number): Observable<void> {
    return this.http.delete<void>(`${this.api}/mesas/${id}`);
  }

  // --- Categorías ---
  categorias(): Observable<Categoria[]> {
    return this.http.get<{ data: Categoria[] }>(`${this.api}/categorias`).pipe(map((r) => r.data));
  }
  createCategoria(payload: Partial<Categoria>): Observable<Categoria> {
    return this.http.post<{ data: Categoria }>(`${this.api}/categorias`, payload).pipe(map((r) => r.data));
  }
  updateCategoria(id: number, payload: Partial<Categoria>): Observable<Categoria> {
    return this.http.put<{ data: Categoria }>(`${this.api}/categorias/${id}`, payload).pipe(map((r) => r.data));
  }
  deleteCategoria(id: number): Observable<void> {
    return this.http.delete<void>(`${this.api}/categorias/${id}`);
  }

  // --- Platos ---
  createPlato(payload: Partial<Plato>): Observable<Plato> {
    return this.http.post<{ data: Plato }>(`${this.api}/platos`, payload).pipe(map((r) => r.data));
  }
  updatePlato(id: number, payload: Partial<Plato>): Observable<Plato> {
    return this.http.put<{ data: Plato }>(`${this.api}/platos/${id}`, payload).pipe(map((r) => r.data));
  }
  deletePlato(id: number): Observable<void> {
    return this.http.delete<void>(`${this.api}/platos/${id}`);
  }
}
