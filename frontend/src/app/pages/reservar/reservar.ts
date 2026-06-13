import { Component, OnInit, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ReservaService } from '../../core/reserva.service';
import { AuthService } from '../../core/auth.service';
import { ApiValidationError } from '../../core/reserva.model';
import { SlotPicker } from '../../shared/slot-picker';

@Component({
  selector: 'app-reservar',
  imports: [ReactiveFormsModule, RouterLink, SlotPicker],
  templateUrl: './reservar.html',
})
export class Reservar implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly reservaService = inject(ReservaService);
  protected readonly auth = inject(AuthService);

  protected readonly submitting = signal(false);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  /** Today (YYYY-MM-DD) used as the min for the date input. */
  protected readonly minDate = new Date().toISOString().slice(0, 10);

  protected readonly form = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
    fecha: ['', [Validators.required]],
    hora: ['', [Validators.required]],
    personas: [2, [Validators.required, Validators.min(1), Validators.max(255)]],
    notas: [''],
  });

  /** Reactive mirror of the date control, fed to the slot picker. */
  protected readonly fecha = toSignal(this.form.controls.fecha.valueChanges, {
    initialValue: this.form.controls.fecha.value,
  });

  ngOnInit(): void {
    const user = this.auth.user();
    if (user) {
      this.form.patchValue({ nombre: user.name, email: user.email });
    }
  }

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  /** Whether the slot field should render its error state. */
  protected get horaInvalid(): boolean {
    const c = this.form.controls.hora;
    return c.touched && c.invalid;
  }

  /** Set the chosen slot back into the reactive form. */
  protected onHoraChange(hora: string): void {
    this.form.controls.hora.setValue(hora);
    this.form.controls.hora.markAsTouched();
  }

  protected submit(): void {
    this.successMessage.set(null);
    this.serverErrors.set({});

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.reservaService.create(this.form.getRawValue()).subscribe({
      next: (res) => {
        this.successMessage.set(res.message);
        this.form.reset({ personas: 2, nombre: this.auth.user()?.name ?? '', email: this.auth.user()?.email ?? '', fecha: '', hora: '', notas: '' });
        this.submitting.set(false);
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        this.serverErrors.set(body?.errors ?? { general: ['No se pudo guardar la reserva.'] });
        this.submitting.set(false);
      },
    });
  }
}
