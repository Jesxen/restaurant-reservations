export interface Review {
  id: number;
  nombre: string;
  rating: number; // 1..5
  comentario: string;
  fecha: string; // display date
  aprobada?: boolean;
}

export interface ReviewResumen {
  rating_medio: number;
  total: number;
}

/** GET /api/reviews → { data, meta }. */
export interface ReviewCollection {
  data: Review[];
  meta: ReviewResumen;
}

/** POST /api/reviews body. */
export interface NuevaReview {
  rating: number;
  comentario: string;
}
