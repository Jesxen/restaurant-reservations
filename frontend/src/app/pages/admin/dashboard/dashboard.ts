import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AdminService } from '../../../core/admin.service';
import { DashboardData } from '../../../core/admin.model';
import { EstadoReserva } from '../../../core/reserva.model';
import { ESTADO_BADGE, ESTADO_LABEL, ESTADOS } from '../../../core/estado.util';

@Component({
  selector: 'app-admin-dashboard',
  templateUrl: './dashboard.html',
})
export class Dashboard implements OnInit {
  private readonly admin = inject(AdminService);

  protected readonly data = signal<DashboardData | null>(null);
  protected readonly loading = signal(true);
  protected readonly estados = ESTADOS;

  protected readonly maxProximos = computed(() => {
    const d = this.data();
    if (!d) return 1;
    return Math.max(1, ...d.proximos_dias.map((p) => p.reservas));
  });

  protected readonly maxFranja = computed(() => {
    const d = this.data();
    if (!d || d.por_franja.length === 0) return 1;
    return Math.max(1, ...d.por_franja.map((f) => f.comensales));
  });

  ngOnInit(): void {
    this.admin.dashboard().subscribe({
      next: (d) => {
        this.data.set(d);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected estadoTotal(e: EstadoReserva): number {
    return this.data()?.por_estado[e] ?? 0;
  }
  protected badge(e: EstadoReserva): string {
    return ESTADO_BADGE[e];
  }
  protected label(e: EstadoReserva): string {
    return ESTADO_LABEL[e];
  }
  protected barHeight(reservas: number): string {
    return `${Math.round((reservas / this.maxProximos()) * 100)}%`;
  }
  protected franjaHeight(comensales: number): string {
    return `${Math.round((comensales / this.maxFranja()) * 100)}%`;
  }
}
