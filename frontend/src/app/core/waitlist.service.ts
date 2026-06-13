import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';
import { NuevaWaitlist, WaitlistEntry } from './waitlist.model';

interface WaitlistCreateResponse {
  data: WaitlistEntry;
  message: string;
}

@Injectable({ providedIn: 'root' })
export class WaitlistService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;
  private readonly admin = `${environment.apiUrl}/admin`;

  // --- Public ---
  /** Join the waitlist. 422 on `hora` when the slot actually has space. */
  join(payload: NuevaWaitlist): Observable<WaitlistCreateResponse> {
    return this.http.post<WaitlistCreateResponse>(`${this.api}/waitlist`, payload);
  }

  /** Own waitlist entries (auth). */
  mias(): Observable<WaitlistEntry[]> {
    return this.http
      .get<{ data: WaitlistEntry[] }>(`${this.api}/mis-esperas`)
      .pipe(map((r) => r.data));
  }

  // --- Admin (staff) ---
  adminList(filters: { fecha?: string; estado?: string } = {}): Observable<WaitlistEntry[]> {
    const params: Record<string, string> = {};
    if (filters.fecha) params['fecha'] = filters.fecha;
    if (filters.estado) params['estado'] = filters.estado;
    return this.http
      .get<{ data: WaitlistEntry[] }>(`${this.admin}/waitlist`, { params })
      .pipe(map((r) => r.data));
  }

  adminDelete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.admin}/waitlist/${id}`);
  }
}
