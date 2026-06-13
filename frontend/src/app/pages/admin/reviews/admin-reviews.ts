import { Component, OnInit, inject, signal } from '@angular/core';
import { ReviewService } from '../../../core/review.service';
import { Review } from '../../../core/review.model';

type Filtro = 'pendientes' | 'aprobadas';

@Component({
  selector: 'app-admin-reviews',
  templateUrl: './admin-reviews.html',
})
export class AdminReviews implements OnInit {
  private readonly reviewService = inject(ReviewService);

  protected readonly reviews = signal<Review[]>([]);
  protected readonly loading = signal(true);
  protected readonly filtro = signal<Filtro>('pendientes');
  protected readonly busy = signal<number | null>(null);

  protected readonly stars = [1, 2, 3, 4, 5];

  ngOnInit(): void {
    this.load();
  }

  protected setFiltro(f: Filtro): void {
    if (this.filtro() === f) return;
    this.filtro.set(f);
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.reviewService.adminReviews(this.filtro() === 'aprobadas' ? 1 : 0).subscribe({
      next: (data) => {
        this.reviews.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected toggleAprobada(r: Review): void {
    this.busy.set(r.id);
    this.reviewService.adminSetAprobada(r.id, !r.aprobada).subscribe({
      next: () => {
        // It moves to the other filter bucket; drop it from the current list.
        this.reviews.update((list) => list.filter((x) => x.id !== r.id));
        this.busy.set(null);
      },
      error: () => this.busy.set(null),
    });
  }

  protected eliminar(r: Review): void {
    if (!confirm('¿Eliminar esta reseña?')) return;
    this.busy.set(r.id);
    this.reviewService.adminDelete(r.id).subscribe({
      next: () => {
        this.reviews.update((list) => list.filter((x) => x.id !== r.id));
        this.busy.set(null);
      },
      error: () => this.busy.set(null),
    });
  }
}
