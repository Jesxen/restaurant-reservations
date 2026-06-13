import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { WaitlistService } from '../../../core/waitlist.service';
import { WaitlistEntry, WaitlistEstado } from '../../../core/waitlist.model';

const ESTADO_LABEL: Record<WaitlistEstado, string> = {
  esperando: 'Esperando',
  avisado: 'Avisado',
  convertida: 'Convertida',
  cancelada: 'Cancelada',
  expirada: 'Expirada',
};

const ESTADO_BADGE: Record<WaitlistEstado, string> = {
  esperando: 'badge-warning',
  avisado: 'badge-info',
  convertida: 'badge-success',
  cancelada: 'badge-ghost',
  expirada: 'badge-ghost',
};

@Component({
  selector: 'app-admin-waitlist',
  imports: [FormsModule],
  templateUrl: './admin-waitlist.html',
})
export class AdminWaitlist implements OnInit {
  private readonly waitlistService = inject(WaitlistService);

  protected readonly entries = signal<WaitlistEntry[]>([]);
  protected readonly loading = signal(true);
  protected readonly busy = signal<number | null>(null);

  protected filtroFecha = '';
  protected filtroEstado = '';

  protected readonly estados: WaitlistEstado[] = [
    'esperando',
    'avisado',
    'convertida',
    'cancelada',
    'expirada',
  ];

  ngOnInit(): void {
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.waitlistService
      .adminList({ fecha: this.filtroFecha, estado: this.filtroEstado })
      .subscribe({
        next: (data) => {
          this.entries.set(data);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });
  }

  protected limpiar(): void {
    this.filtroFecha = '';
    this.filtroEstado = '';
    this.load();
  }

  protected eliminar(e: WaitlistEntry): void {
    if (!confirm('¿Eliminar esta entrada de la lista de espera?')) return;
    this.busy.set(e.id);
    this.waitlistService.adminDelete(e.id).subscribe({
      next: () => {
        this.entries.update((list) => list.filter((x) => x.id !== e.id));
        this.busy.set(null);
      },
      error: () => this.busy.set(null),
    });
  }

  protected label(estado: WaitlistEstado): string {
    return ESTADO_LABEL[estado];
  }
  protected badge(estado: WaitlistEstado): string {
    return ESTADO_BADGE[estado];
  }
}
