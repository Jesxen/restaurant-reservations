import { Component, ElementRef, OnDestroy, OnInit, afterNextRender, inject, signal, viewChild } from '@angular/core';
import { RouterLink } from '@angular/router';
import * as L from 'leaflet';
import { MenuService } from '../../core/menu.service';
import { Categoria } from '../../core/menu.model';
import { RevealDirective } from '../../core/reveal.directive';

const LAT = 28.4874;
const LNG = -16.3159;

L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

@Component({
  selector: 'app-landing',
  imports: [RouterLink, RevealDirective],
  templateUrl: './landing.html',
})
export class Landing implements OnInit, OnDestroy {
  private readonly menuService = inject(MenuService);
  private readonly mapEl = viewChild<ElementRef<HTMLDivElement>>('mapEl');

  protected readonly categorias = signal<Categoria[]>([]);
  protected readonly loadingMenu = signal(true);

  protected readonly images = [
    'https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg',
    'https://images.pexels.com/photos/70497/pexels-photo-70497.jpeg',
    'https://images.pexels.com/photos/958545/pexels-photo-958545.jpeg',
    'https://images.pexels.com/photos/1267320/pexels-photo-1267320.jpeg',
  ];
  protected readonly heroImage = signal(this.images[0]);
  private galleryTimer?: ReturnType<typeof setInterval>;

  constructor() {
    afterNextRender(() => {
      const el = this.mapEl()?.nativeElement;
      if (!el) return;
      const map = L.map(el).setView([LAT, LNG], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
      }).addTo(map);
      L.marker([LAT, LNG])
        .addTo(map)
        .bindPopup('<b>Restaurante La Laguna</b><br>Tu experiencia gastronómica en Canarias.')
        .openPopup();
    });
  }

  ngOnInit(): void {
    this.menuService.menu().subscribe({
      next: (data) => {
        this.categorias.set(data);
        this.loadingMenu.set(false);
      },
      error: () => this.loadingMenu.set(false),
    });

    let i = 0;
    this.galleryTimer = setInterval(() => {
      i = (i + 1) % this.images.length;
      this.heroImage.set(this.images[i]);
    }, 5000);
  }

  ngOnDestroy(): void {
    if (this.galleryTimer) clearInterval(this.galleryTimer);
  }
}
