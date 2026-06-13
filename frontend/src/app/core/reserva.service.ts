import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';
import { Disponibilidad, EditarReserva, NuevaReserva, Reserva } from './reserva.model';

interface CollectionResponse {
  data: Reserva[];
}

interface ResourceResponse {
  data: Reserva;
  message: string;
}

/** POST /api/reservas response — adds top-level `client_secret` for Stripe. */
export interface CreateReservaResponse extends ResourceResponse {
  client_secret: string | null;
}

@Injectable({ providedIn: 'root' })
export class ReservaService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  create(reserva: NuevaReserva): Observable<CreateReservaResponse> {
    return this.http.post<CreateReservaResponse>(`${this.api}/reservas`, reserva);
  }

  misReservas(): Observable<Reserva[]> {
    return this.http
      .get<CollectionResponse>(`${this.api}/mis-reservas`)
      .pipe(map((r) => r.data));
  }

  cancelar(id: number): Observable<ResourceResponse> {
    return this.http.patch<ResourceResponse>(`${this.api}/reservas/${id}/cancelar`, {});
  }

  /** Client edits OWN reservation (PATCH /api/reservas/{id}). */
  editar(id: number, payload: EditarReserva): Observable<ResourceResponse> {
    return this.http.patch<ResourceResponse>(`${this.api}/reservas/${id}`, payload);
  }

  disponibilidad(fecha: string, hora: string): Observable<Disponibilidad> {
    return this.http.get<Disponibilidad>(`${this.api}/disponibilidad`, {
      params: { fecha, hora },
    });
  }
}
