import { Component, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ContactoService } from '../../core/contacto.service';
import { ApiValidationError } from '../../core/reserva.model';

@Component({
  selector: 'app-contacto',
  imports: [ReactiveFormsModule],
  templateUrl: './contacto.html',
})
export class Contacto {
  private readonly fb = inject(FormBuilder);
  private readonly contacto = inject(ContactoService);

  protected readonly email = 'reservas@laguna.com';
  protected readonly telefono = '+34 922 000 000';
  protected readonly direccion = 'Calle La Carrera, 1 · San Cristóbal de La Laguna, Tenerife';
  protected readonly horario = 'Mar–Dom · 13:00–16:00 y 20:00–23:30';

  protected readonly enviando = signal(false);
  protected readonly enviado = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly form = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    mensaje: ['', [Validators.required, Validators.minLength(5), Validators.maxLength(2000)]],
  });

  /** Server-side validation messages (HTTP 422) for a given field. */
  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  /** Inline client-side error message for a field, shown only once touched. */
  protected clientError(field: 'nombre' | 'email' | 'mensaje'): string | null {
    const control = this.form.controls[field];
    if (!control.touched) {
      return null;
    }
    if (control.hasError('required')) {
      return 'Campo obligatorio';
    }
    if (control.hasError('email')) {
      return 'El correo no es válido';
    }
    if (control.hasError('minlength')) {
      const min = control.getError('minlength').requiredLength;
      return `Mínimo ${min} caracteres`;
    }
    if (control.hasError('maxlength')) {
      const max = control.getError('maxlength').requiredLength;
      return `Máximo ${max} caracteres`;
    }
    return null;
  }

  protected enviar(): void {
    if (this.enviando()) return;
    this.error.set(null);
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.enviando.set(true);
    const { nombre, email, mensaje } = this.form.getRawValue();
    this.contacto.enviar({ nombre, email, mensaje }).subscribe({
      next: () => {
        this.enviando.set(false);
        this.enviado.set(true);
      },
      error: (err: HttpErrorResponse) => {
        this.enviando.set(false);
        const body = err.error as ApiValidationError | null;
        if (err.status === 422 && body?.errors) {
          this.serverErrors.set(body.errors);
        }
        this.error.set(body?.message ?? 'No se pudo enviar el mensaje. Inténtalo de nuevo.');
      },
    });
  }
}
