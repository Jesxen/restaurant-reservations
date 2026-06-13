import { Component, inject, input, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';
import { ApiValidationError } from '../../core/reserva.model';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './login.html',
})
export class Login {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  /** "1" when arriving from a successful password reset (query param). */
  readonly reset = input<string>('');

  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  /** Server-side validation messages (HTTP 422) for a given field. */
  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  /** Inline client-side error message for a field, shown only once touched. */
  protected clientError(field: 'email' | 'password'): string | null {
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
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.submitting.set(true);
    this.auth.login(this.form.getRawValue()).subscribe({
      next: () => {
        this.router.navigate([this.auth.isAdmin() ? '/admin' : '/cuenta']);
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        if (err.status === 422 && body?.errors) {
          this.serverErrors.set(body.errors);
        }
        this.error.set(body?.message ?? 'No se pudo iniciar sesión.');
        this.submitting.set(false);
      },
    });
  }
}
