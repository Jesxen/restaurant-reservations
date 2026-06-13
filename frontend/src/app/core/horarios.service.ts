import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Horarios } from './reserva.model';

@Injectable({ providedIn: 'root' })
export class HorariosService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  /** Available slots for a given date (GET /api/horarios?fecha=YYYY-MM-DD). */
  slots(fecha: string): Observable<Horarios> {
    return this.http.get<Horarios>(`${this.api}/horarios`, { params: { fecha } });
  }
}
