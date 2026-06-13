import { Component, OnInit, inject, signal } from '@angular/core';
import { NgClass } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ReservaService } from '../../core/reserva.service';
import { AuthService } from '../../core/auth.service';
import { EstadoReserva, Reserva } from '../../core/reserva.model';
import { ESTADO_BADGE, ESTADO_LABEL } from '../../core/estado.util';

@Component({
  selector: 'app-cuenta',
  imports: [RouterLink, NgClass],
  templateUrl: './cuenta.html',
})
export class Cuenta implements OnInit {
  private readonly reservaService = inject(ReservaService);
  protected readonly auth = inject(AuthService);

  protected readonly reservas = signal<Reserva[]>([]);
  protected readonly loading = signal(true);
  protected readonly canceling = signal<number | null>(null);

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);
    this.reservaService.misReservas().subscribe({
      next: (data) => {
        this.reservas.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected cancelar(r: Reserva): void {
    this.canceling.set(r.id);
    this.reservaService.cancelar(r.id).subscribe({
      next: (res) => {
        this.reservas.update((list) => list.map((x) => (x.id === r.id ? res.data : x)));
        this.canceling.set(null);
      },
      error: () => this.canceling.set(null),
    });
  }

  protected badge(estado: EstadoReserva): string {
    return ESTADO_BADGE[estado];
  }
  protected label(estado: EstadoReserva): string {
    return ESTADO_LABEL[estado];
  }
}
