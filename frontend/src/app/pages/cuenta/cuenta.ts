import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { NgClass } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ReservaService } from '../../core/reserva.service';
import { AuthService } from '../../core/auth.service';
import { ApiValidationError, EstadoReserva, Reserva } from '../../core/reserva.model';
import { ESTADO_BADGE, ESTADO_LABEL } from '../../core/estado.util';
import { SlotPicker } from '../../shared/slot-picker';
import { ReviewService } from '../../core/review.service';
import { WaitlistService } from '../../core/waitlist.service';
import { WaitlistEntry } from '../../core/waitlist.model';
import { I18nService } from '../../core/i18n.service';
import { TranslatePipe } from '../../core/translate.pipe';

@Component({
  selector: 'app-cuenta',
  imports: [RouterLink, NgClass, ReactiveFormsModule, SlotPicker, TranslatePipe],
  templateUrl: './cuenta.html',
})
export class Cuenta implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly reservaService = inject(ReservaService);
  private readonly reviewService = inject(ReviewService);
  private readonly waitlistService = inject(WaitlistService);
  protected readonly auth = inject(AuthService);
  protected readonly i18n = inject(I18nService);

  protected readonly reservas = signal<Reserva[]>([]);
  protected readonly loading = signal(true);
  protected readonly canceling = signal<number | null>(null);

  // --- Review form ---
  protected readonly reviewSubmitting = signal(false);
  protected readonly reviewDone = signal(false);
  protected readonly reviewErrors = signal<Record<string, string[]>>({});
  protected readonly reviewRating = signal(0);
  protected readonly stars = [1, 2, 3, 4, 5];

  protected readonly reviewForm = this.fb.nonNullable.group({
    rating: [0, [Validators.required, Validators.min(1), Validators.max(5)]],
    comentario: ['', [Validators.required, Validators.minLength(5), Validators.maxLength(1000)]],
  });

  /** Eligible to review if there is at least one completed reservation. */
  protected readonly canReview = computed(() =>
    this.reservas().some((r) => r.estado === 'completada'),
  );

  // --- Waitlist entries ---
  protected readonly esperas = signal<WaitlistEntry[]>([]);

  // --- Edit modal state ---
  protected readonly editing = signal<Reserva | null>(null);
  protected readonly saving = signal(false);
  protected readonly editMessage = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  // --- Email verification banner ---
  protected readonly resending = signal(false);
  protected readonly resentMessage = signal<string | null>(null);

  protected readonly minDate = new Date().toISOString().slice(0, 10);

  protected readonly editForm = this.fb.nonNullable.group({
    fecha: ['', [Validators.required]],
    hora: ['', [Validators.required]],
    personas: [2, [Validators.required, Validators.min(1), Validators.max(255)]],
    notas: [''],
  });

  /** Reactive mirror of the edit-form date, fed to the slot picker. */
  protected readonly editFecha = toSignal(this.editForm.controls.fecha.valueChanges, {
    initialValue: this.editForm.controls.fecha.value,
  });

  ngOnInit(): void {
    this.load();
    this.waitlistService.mias().subscribe({
      next: (data) => this.esperas.set(data),
      error: () => this.esperas.set([]),
    });
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

  // --- Review ---
  protected setRating(value: number): void {
    this.reviewRating.set(value);
    this.reviewForm.controls.rating.setValue(value);
    this.reviewForm.controls.rating.markAsTouched();
  }

  protected reviewErrorsFor(field: string): string[] {
    return this.reviewErrors()[field] ?? [];
  }

  protected submitReview(): void {
    this.reviewErrors.set({});
    this.reviewDone.set(false);
    if (this.reviewForm.invalid) {
      this.reviewForm.markAllAsTouched();
      return;
    }
    this.reviewSubmitting.set(true);
    const { rating, comentario } = this.reviewForm.getRawValue();
    this.reviewService.submit({ rating, comentario }).subscribe({
      next: () => {
        this.reviewSubmitting.set(false);
        this.reviewDone.set(true);
        this.reviewForm.reset({ rating: 0, comentario: '' });
        this.reviewRating.set(0);
      },
      error: (err: HttpErrorResponse) => {
        this.reviewSubmitting.set(false);
        const body = err.error as ApiValidationError | null;
        this.reviewErrors.set(body?.errors ?? { general: ['No se pudo enviar la reseña.'] });
      },
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

  /** Only pending/confirmed reservations can be edited. */
  protected editable(r: Reserva): boolean {
    return r.estado === 'pendiente' || r.estado === 'confirmada';
  }

  protected abrirEditar(r: Reserva): void {
    this.editMessage.set(null);
    this.serverErrors.set({});
    this.editForm.reset({
      fecha: r.fecha,
      hora: r.hora.slice(0, 5),
      personas: r.personas,
      notas: r.notas ?? '',
    });
    this.editing.set(r);
  }

  protected cerrarEditar(): void {
    this.editing.set(null);
  }

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected get horaInvalid(): boolean {
    const c = this.editForm.controls.hora;
    return c.touched && c.invalid;
  }

  protected onHoraChange(hora: string): void {
    this.editForm.controls.hora.setValue(hora);
    this.editForm.controls.hora.markAsTouched();
  }

  protected guardarEdicion(): void {
    const r = this.editing();
    if (!r) return;
    this.editMessage.set(null);
    this.serverErrors.set({});
    if (this.editForm.invalid) {
      this.editForm.markAllAsTouched();
      return;
    }
    this.saving.set(true);
    const { fecha, hora, personas, notas } = this.editForm.getRawValue();
    this.reservaService.editar(r.id, { fecha, hora, personas, notas }).subscribe({
      next: (res) => {
        this.reservas.update((list) => list.map((x) => (x.id === r.id ? res.data : x)));
        this.editMessage.set(res.message);
        this.saving.set(false);
        this.editing.set(null);
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        this.serverErrors.set(body?.errors ?? { general: ['No se pudo actualizar la reserva.'] });
        this.saving.set(false);
      },
    });
  }

  protected reenviarVerificacion(): void {
    this.resending.set(true);
    this.resentMessage.set(null);
    this.auth.resendVerification().subscribe({
      next: (res) => {
        this.resentMessage.set(res.message);
        this.resending.set(false);
      },
      error: () => {
        this.resentMessage.set('No se pudo reenviar el correo. Inténtalo de nuevo.');
        this.resending.set(false);
      },
    });
  }

  protected badge(estado: EstadoReserva): string {
    return ESTADO_BADGE[estado];
  }
  protected label(estado: EstadoReserva): string {
    return ESTADO_LABEL[estado];
  }
}
