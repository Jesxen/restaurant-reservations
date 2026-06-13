import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';
import { Categoria } from './menu.model';

interface CollectionResponse {
  data: Categoria[];
}

@Injectable({ providedIn: 'root' })
export class MenuService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  menu(): Observable<Categoria[]> {
    return this.http.get<CollectionResponse>(`${this.api}/menu`).pipe(map((r) => r.data));
  }
}
