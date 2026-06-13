import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AdminService } from '../../../core/admin.service';
import { AuthService } from '../../../core/auth.service';
import { EstadoReserva, Mesa, Reserva } from '../../../core/reserva.model';
import { ESTADO_BADGE, ESTADO_LABEL, ESTADOS } from '../../../core/estado.util';

@Component({
  selector: 'app-admin-reservas',
  imports: [FormsModule],
  templateUrl: './admin-reservas.html',
})
export class AdminReservas implements OnInit {
  private readonly admin = inject(AdminService);
  private readonly auth = inject(AuthService);

  protected readonly reservas = signal<Reserva[]>([]);
  protected readonly mesas = signal<Mesa[]>([]);
  protected readonly loading = signal(true);
  protected readonly estados = ESTADOS;

  protected filtroFecha = '';
  protected filtroEstado = '';
  protected filtroQ = '';

  // Detail modal
  protected readonly detalle = signal<Reserva | null>(null);
  protected readonly detalleLoading = signal(false);
  protected notasInternas = '';

  ngOnInit(): void {
    this.admin.mesas().subscribe((m) => this.mesas.set(m));
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.admin.reservas({ fecha: this.filtroFecha, estado: this.filtroEstado, q: this.filtroQ }).subscribe({
      next: (data) => {
        this.reservas.set(data);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected limpiar(): void {
    this.filtroFecha = '';
    this.filtroEstado = '';
    this.filtroQ = '';
    this.load();
  }

  protected exportCsv(): void {
    this.admin.downloadReservasCsv(
      { fecha: this.filtroFecha, estado: this.filtroEstado, q: this.filtroQ },
      this.auth.token,
    );
  }

  protected cambiarEstado(r: Reserva, estado: string): void {
    this.admin.updateReserva(r.id, { estado: estado as EstadoReserva }).subscribe((updated) => {
      this.reservas.update((list) => list.map((x) => (x.id === r.id ? { ...updated } : x)));
    });
  }

  protected asignarMesa(r: Reserva, mesaId: string): void {
    const mesa_id = mesaId ? Number(mesaId) : null;
    this.admin.updateReserva(r.id, { mesa_id }).subscribe((updated) => {
      this.reservas.update((list) => list.map((x) => (x.id === r.id ? { ...updated } : x)));
    });
  }

  protected abrirDetalle(r: Reserva): void {
    this.detalleLoading.set(true);
    this.detalle.set(r);
    this.notasInternas = '';
    this.admin.reserva(r.id).subscribe({
      next: (full) => {
        this.detalle.set(full);
        this.notasInternas = full.notas_internas ?? '';
        this.detalleLoading.set(false);
      },
      error: () => this.detalleLoading.set(false),
    });
  }

  protected cerrarDetalle(): void {
    this.detalle.set(null);
  }

  protected guardarNotas(): void {
    const d = this.detalle();
    if (!d) return;
    this.admin.updateReserva(d.id, { notas_internas: this.notasInternas }).subscribe((updated) => {
      this.detalle.set(updated);
      this.reservas.update((list) => list.map((x) => (x.id === updated.id ? { ...updated } : x)));
    });
  }

  protected badge(e: EstadoReserva): string {
    return ESTADO_BADGE[e];
  }
  protected label(e: EstadoReserva): string {
    return ESTADO_LABEL[e];
  }
}
