import { Component, ElementRef, OnDestroy, OnInit, afterNextRender, computed, inject, signal, viewChild } from '@angular/core';
import { Meta, Title } from '@angular/platform-browser';
import { RouterLink } from '@angular/router';
import * as L from 'leaflet';
import { MenuService } from '../../core/menu.service';
import { Categoria } from '../../core/menu.model';
import { RevealDirective } from '../../core/reveal.directive';
import { SettingsService } from '../../core/settings.service';
import { ReviewService } from '../../core/review.service';
import { Review, ReviewResumen } from '../../core/review.model';
import { TranslatePipe } from '../../core/translate.pipe';

const FALLBACK_IMAGES = [
  'https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg',
  'https://images.pexels.com/photos/70497/pexels-photo-70497.jpeg',
  'https://images.pexels.com/photos/958545/pexels-photo-958545.jpeg',
  'https://images.pexels.com/photos/1267320/pexels-photo-1267320.jpeg',
];

L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

@Component({
  selector: 'app-landing',
  imports: [RouterLink, RevealDirective, TranslatePipe],
  templateUrl: './landing.html',
})
export class Landing implements OnInit, OnDestroy {
  private readonly menuService = inject(MenuService);
  private readonly settingsService = inject(SettingsService);
  private readonly reviewService = inject(ReviewService);
  private readonly title = inject(Title);
  private readonly meta = inject(Meta);
  private readonly mapEl = viewChild<ElementRef<HTMLDivElement>>('mapEl');

  protected readonly categorias = signal<Categoria[]>([]);
  protected readonly loadingMenu = signal(true);
  protected readonly settings = this.settingsService.settings;

  protected readonly reviews = signal<Review[]>([]);
  protected readonly reviewResumen = signal<ReviewResumen | null>(null);
  protected readonly loadingReviews = signal(true);
  protected readonly stars = [1, 2, 3, 4, 5];

  /** Gallery images from settings, falling back to the curated defaults. */
  protected readonly images = computed(() => {
    const g = this.settings().galeria;
    return g.length ? g : FALLBACK_IMAGES;
  });

  protected readonly heroImage = signal(FALLBACK_IMAGES[0]);
  private galleryTimer?: ReturnType<typeof setInterval>;

  constructor() {
    afterNextRender(() => {
      const el = this.mapEl()?.nativeElement;
      if (!el) return;
      const s = this.settings();
      const lat = s.coords.lat ?? 28.4874;
      const lng = s.coords.lng ?? -16.3159;
      const map = L.map(el).setView([lat, lng], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
      }).addTo(map);
      L.marker([lat, lng])
        .addTo(map)
        .bindPopup(`<b>${s.nombre_restaurante}</b><br>Tu experiencia gastronómica en Canarias.`)
        .openPopup();
    });
  }

  ngOnInit(): void {
    const s = this.settings();
    this.heroImage.set(this.images()[0]);

    // SEO: per-route title + meta description / OpenGraph driven from settings.
    const description = `${s.nombre_restaurante} · Cocina canaria de autor en ${
      s.contacto.ciudad ?? 'La Laguna'
    }. Reserva tu mesa online con confirmación al instante.`;
    this.title.setTitle(`${s.nombre_restaurante} · Cocina canaria de autor`);
    this.meta.updateTag({ name: 'description', content: description });
    this.meta.updateTag({ property: 'og:title', content: s.nombre_restaurante });
    this.meta.updateTag({ property: 'og:description', content: description });
    if (this.images().length) {
      this.meta.updateTag({ property: 'og:image', content: this.images()[0] });
    }

    this.menuService.menu().subscribe({
      next: (data) => {
        this.categorias.set(data);
        this.loadingMenu.set(false);
      },
      error: () => this.loadingMenu.set(false),
    });

    this.reviewService.reviews().subscribe({
      next: (res) => {
        this.reviews.set(res.data);
        this.reviewResumen.set(res.meta);
        this.loadingReviews.set(false);
      },
      error: () => this.loadingReviews.set(false),
    });

    let i = 0;
    this.galleryTimer = setInterval(() => {
      const imgs = this.images();
      i = (i + 1) % imgs.length;
      this.heroImage.set(imgs[i]);
    }, 5000);
  }

  ngOnDestroy(): void {
    if (this.galleryTimer) clearInterval(this.galleryTimer);
  }
}
