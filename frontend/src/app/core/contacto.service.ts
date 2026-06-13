import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface ContactoPayload {
  nombre: string;
  email: string;
  mensaje: string;
}

@Injectable({ providedIn: 'root' })
export class ContactoService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  enviar(payload: ContactoPayload): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.api}/contacto`, payload);
  }
}
