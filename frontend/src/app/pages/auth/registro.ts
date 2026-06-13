import { Component, inject, signal } from '@angular/core';
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
import { TranslatePipe } from '../../core/translate.pipe';

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
  selector: 'app-registro',
  imports: [ReactiveFormsModule, RouterLink, TranslatePipe],
  templateUrl: './registro.html',
})
export class Registro {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly submitting = signal(false);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly form = this.fb.nonNullable.group(
    {
      name: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      phone: ['', [Validators.pattern(/^[+\d][\d\s().-]{6,19}$/)]],
      // Must contain letters and numbers to satisfy the backend rule.
      password: [
        '',
        [Validators.required, Validators.minLength(8), Validators.pattern(/^(?=.*[A-Za-z])(?=.*\d).+$/)],
      ],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: passwordsMatch },
  );

  /** Server-side validation messages (HTTP 422) for a given field. */
  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  /** Inline client-side error message for a field, shown only once touched. */
  protected clientError(field: keyof Registro['form']['controls']): string | null {
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
    if (control.hasError('pattern')) {
      if (field === 'password') {
        return 'Debe incluir letras y números';
      }
      if (field === 'phone') {
        return 'El teléfono no es válido';
      }
    }
    return null;
  }

  /** Whether password_confirmation should show a "does not match" error. */
  protected passwordMismatch(): boolean {
    const confirmation = this.form.controls.password_confirmation;
    return this.form.hasError('passwordMismatch') && confirmation.touched;
  }

  protected submit(): void {
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.submitting.set(true);
    this.auth.register(this.form.getRawValue()).subscribe({
      next: () => this.router.navigate(['/cuenta']),
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        this.serverErrors.set(body?.errors ?? { general: ['No se pudo crear la cuenta.'] });
        this.submitting.set(false);
      },
    });
  }
}
