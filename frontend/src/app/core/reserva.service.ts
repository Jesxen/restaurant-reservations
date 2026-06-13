import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';
import { Disponibilidad, NuevaReserva, Reserva } from './reserva.model';

interface CollectionResponse {
  data: Reserva[];
}

interface ResourceResponse {
  data: Reserva;
  message: string;
}

@Injectable({ providedIn: 'root' })
export class ReservaService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  create(reserva: NuevaReserva): Observable<ResourceResponse> {
    return this.http.post<ResourceResponse>(`${this.api}/reservas`, reserva);
  }

  misReservas(): Observable<Reserva[]> {
    return this.http
      .get<CollectionResponse>(`${this.api}/mis-reservas`)
      .pipe(map((r) => r.data));
  }

  cancelar(id: number): Observable<ResourceResponse> {
    return this.http.patch<ResourceResponse>(`${this.api}/reservas/${id}/cancelar`, {});
  }

  disponibilidad(fecha: string, hora: string): Observable<Disponibilidad> {
    return this.http.get<Disponibilidad>(`${this.api}/disponibilidad`, {
      params: { fecha, hora },
    });
  }
}
