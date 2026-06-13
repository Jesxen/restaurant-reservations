import { Component, OnInit, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ReservaService } from '../../core/reserva.service';
import { AuthService } from '../../core/auth.service';
import { ApiValidationError, Disponibilidad } from '../../core/reserva.model';

@Component({
  selector: 'app-reservar',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './reservar.html',
})
export class Reservar implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly reservaService = inject(ReservaService);
  protected readonly auth = inject(AuthService);

  protected readonly submitting = signal(false);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});
  protected readonly disponibilidad = signal<Disponibilidad | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
    fecha: ['', [Validators.required]],
    hora: ['', [Validators.required]],
    personas: [2, [Validators.required, Validators.min(1), Validators.max(255)]],
    notas: [''],
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

  /** Live availability check when date + time are set. */
  protected checkAvailability(): void {
    const { fecha, hora } = this.form.getRawValue();
    if (!fecha || !hora) {
      this.disponibilidad.set(null);
      return;
    }
    this.reservaService.disponibilidad(fecha, hora).subscribe({
      next: (d) => this.disponibilidad.set(d),
      error: () => this.disponibilidad.set(null),
    });
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
        this.disponibilidad.set(null);
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
