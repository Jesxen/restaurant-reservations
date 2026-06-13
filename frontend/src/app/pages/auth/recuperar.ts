import { Component, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';
import { ApiValidationError } from '../../core/reserva.model';

@Component({
  selector: 'app-recuperar',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './recuperar.html',
})
export class Recuperar {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);

  protected readonly submitting = signal(false);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
  });

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected clientError(field: 'email'): string | null {
    const control = this.form.controls[field];
    if (!control.touched || control.valid) {
      return null;
    }
    if (control.hasError('required')) {
      return 'Campo obligatorio';
    }
    if (control.hasError('email')) {
      return 'El correo no es válido';
    }
    return null;
  }

  protected submit(): void {
    this.error.set(null);
    this.successMessage.set(null);
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.submitting.set(true);
    this.auth.forgotPassword(this.form.getRawValue().email).subscribe({
      next: (res) => {
        this.successMessage.set(res.message);
        this.submitting.set(false);
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        if (err.status === 422 && body?.errors) {
          this.serverErrors.set(body.errors);
        }
        this.error.set(body?.message ?? 'No se pudo procesar la solicitud.');
        this.submitting.set(false);
      },
    });
  }
}
