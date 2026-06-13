import { Component, inject, input, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import {
  AbstractControl,
  FormBuilder,
  ReactiveFormsModule,
  ValidationErrors,
  Validators,
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';
import { ApiValidationError } from '../../core/reserva.model';

/** Group-level validator: password_confirmation must match password. */
function passwordsMatch(group: AbstractControl): ValidationErrors | null {
  const password = group.get('password')?.value;
  const confirmation = group.get('password_confirmation')?.value;
  if (!confirmation) {
    return null;
  }
  return password === confirmation ? null : { passwordMismatch: true };
}

@Component({
  selector: 'app-restablecer',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './restablecer.html',
})
export class Restablecer {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  /** Bound from query params (withComponentInputBinding). */
  readonly token = input<string>('');
  readonly email = input<string>('');

  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly form = this.fb.nonNullable.group(
    {
      password: [
        '',
        [Validators.required, Validators.minLength(8), Validators.pattern(/^(?=.*[A-Za-z])(?=.*\d).+$/)],
      ],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: passwordsMatch },
  );

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected clientError(field: 'password' | 'password_confirmation'): string | null {
    const control = this.form.controls[field];
    if (!control.touched) {
      return null;
    }
    if (control.hasError('required')) {
      return 'Campo obligatorio';
    }
    if (control.hasError('minlength')) {
      const min = control.getError('minlength').requiredLength;
      return `Mínimo ${min} caracteres`;
    }
    if (control.hasError('pattern')) {
      return 'Debe incluir letras y números';
    }
    return null;
  }

  protected passwordMismatch(): boolean {
    const confirmation = this.form.controls.password_confirmation;
    return this.form.hasError('passwordMismatch') && confirmation.touched;
  }

  protected submit(): void {
    this.error.set(null);
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    if (!this.token() || !this.email()) {
      this.error.set('El enlace de restablecimiento no es válido o ha caducado.');
      return;
    }
    this.submitting.set(true);
    const { password, password_confirmation } = this.form.getRawValue();
    this.auth
      .resetPassword({
        token: this.token(),
        email: this.email(),
        password,
        password_confirmation,
      })
      .subscribe({
        next: () => {
          this.router.navigate(['/login'], {
            queryParams: { reset: '1' },
          });
        },
        error: (err: HttpErrorResponse) => {
          const body = err.error as ApiValidationError | null;
          if (err.status === 422 && body?.errors) {
            this.serverErrors.set(body.errors);
          }
          this.error.set(body?.message ?? 'No se pudo restablecer la contraseña.');
          this.submitting.set(false);
        },
      });
  }
}
