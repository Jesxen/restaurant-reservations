import {
  Component,
  effect,
  inject,
  input,
  model,
  signal,
} from '@angular/core';
import { HorariosService } from '../core/horarios.service';
import { Horarios } from '../core/reserva.model';
import { I18nService } from '../core/i18n.service';
import { TranslatePipe } from '../core/translate.pipe';

/**
 * Discrete slot picker driven by GET /api/horarios?fecha.
 * Two-way binds the selected `hora` and emits nothing else; the parent owns the
 * form control. When the date is empty it renders a hint. When closed it shows
 * the `motivo_cierre`.
 */
@Component({
  selector: 'app-slot-picker',
  imports: [TranslatePipe],
  templateUrl: './slot-picker.html',
})
export class SlotPicker {
  private readonly horariosService = inject(HorariosService);
  protected readonly i18n = inject(I18nService);

  /** Selected date (YYYY-MM-DD). Empty string = none picked. */
  readonly fecha = input<string>('');
  /** Two-way bound selected slot. */
  readonly hora = model<string>('');
  /** Mark invalid (e.g. submitted with no slot). */
  readonly invalid = input<boolean>(false);

  protected readonly horarios = signal<Horarios | null>(null);
  protected readonly loading = signal(false);

  constructor() {
    effect(() => {
      const fecha = this.fecha();
      if (!fecha) {
        this.horarios.set(null);
        return;
      }
      this.loading.set(true);
      this.horariosService.slots(fecha).subscribe({
        next: (h) => {
          this.horarios.set(h);
          this.loading.set(false);
          // Clear a previously chosen slot if it is no longer offered.
          const current = this.hora();
          if (current && !h.slots.some((s) => s.hora === current && s.disponible)) {
            this.hora.set('');
          }
        },
        error: () => {
          this.horarios.set(null);
          this.loading.set(false);
        },
      });
    });
  }

  protected select(hora: string): void {
    this.hora.set(hora);
  }
}
