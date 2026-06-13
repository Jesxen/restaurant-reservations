import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';
import {
  NuevaReview,
  Review,
  ReviewCollection,
  ReviewResumen,
} from './review.model';

interface ReviewCreateResponse {
  data: Review;
  message: string;
}

@Injectable({ providedIn: 'root' })
export class ReviewService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;
  private readonly admin = `${environment.apiUrl}/admin`;

  // --- Public ---
  reviews(): Observable<ReviewCollection> {
    return this.http.get<ReviewCollection>(`${this.api}/reviews`);
  }

  resumen(): Observable<ReviewResumen> {
    return this.http
      .get<{ data: ReviewResumen }>(`${this.api}/reviews/resumen`)
      .pipe(map((r) => r.data));
  }

  /** Submit a review (auth). 422 on `reserva` when not eligible / already reviewed. */
  submit(payload: NuevaReview): Observable<ReviewCreateResponse> {
    return this.http.post<ReviewCreateResponse>(`${this.api}/reviews`, payload);
  }

  // --- Admin (staff) ---
  adminReviews(aprobada?: 0 | 1): Observable<Review[]> {
    const params: Record<string, string> = {};
    if (aprobada !== undefined) params['aprobada'] = String(aprobada);
    return this.http
      .get<{ data: Review[] }>(`${this.admin}/reviews`, { params })
      .pipe(map((r) => r.data));
  }

  adminSetAprobada(id: number, aprobada: boolean): Observable<Review> {
    return this.http
      .patch<{ data: Review }>(`${this.admin}/reviews/${id}`, { aprobada })
      .pipe(map((r) => r.data));
  }

  adminDelete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.admin}/reviews/${id}`);
  }
}
