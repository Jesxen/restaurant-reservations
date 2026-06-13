import { Component, OnInit, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { AdminService } from '../../../core/admin.service';
import { AuthService } from '../../../core/auth.service';
import { NuevoUsuario, Role, User } from '../../../core/user.model';
import { ApiValidationError } from '../../../core/reserva.model';

@Component({
  selector: 'app-admin-usuarios',
  imports: [FormsModule, ReactiveFormsModule],
  templateUrl: './admin-usuarios.html',
})
export class AdminUsuarios implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly admin = inject(AdminService);
  protected readonly auth = inject(AuthService);

  protected readonly usuarios = signal<User[]>([]);
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});
  protected readonly roles: Role[] = ['client', 'staff', 'admin'];

  protected busqueda = '';

  // New user form
  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    phone: ['', [Validators.pattern(/^[+\d][\d\s().-]{6,19}$/)]],
    role: ['staff' as Role, [Validators.required]],
    password: ['', [Validators.required, Validators.minLength(8)]],
  });

  ngOnInit(): void {
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.admin.usuarios({ q: this.busqueda }).subscribe({
      next: (u) => {
        this.usuarios.set(u);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected clientError(field: 'name' | 'email' | 'phone' | 'password'): string | null {
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
      return 'Formato no válido';
    }
    return null;
  }

  protected crear(): void {
    this.error.set(null);
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.saving.set(true);
    const { name, email, phone, role, password } = this.form.getRawValue();
    const payload: NuevoUsuario = { name, email, phone: phone || null, role, password };
    this.admin.createUsuario(payload).subscribe({
      next: (u) => {
        this.usuarios.update((list) => [u, ...list]);
        this.form.reset({ name: '', email: '', phone: '', role: 'staff', password: '' });
        this.saving.set(false);
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        if (err.status === 422 && body?.errors) {
          this.serverErrors.set(body.errors);
        }
        this.error.set(body?.message ?? 'No se pudo crear el usuario.');
        this.saving.set(false);
      },
    });
  }

  protected cambiarRol(u: User, role: string): void {
    this.error.set(null);
    this.admin.updateUsuario(u.id, { role: role as Role }).subscribe({
      next: (updated) => this.usuarios.update((list) => list.map((x) => (x.id === u.id ? updated : x))),
      error: (err) => {
        this.error.set(err.error?.message ?? 'No se pudo cambiar el rol.');
        this.load();
      },
    });
  }

  protected toggleActivo(u: User): void {
    this.error.set(null);
    this.admin.updateUsuario(u.id, { activo: !u.activo }).subscribe({
      next: (updated) => this.usuarios.update((list) => list.map((x) => (x.id === u.id ? updated : x))),
      error: (err) => {
        this.error.set(err.error?.message ?? 'No se pudo actualizar.');
        this.load();
      },
    });
  }

  protected eliminar(u: User): void {
    this.error.set(null);
    this.admin.deleteUsuario(u.id).subscribe({
      next: () => this.usuarios.update((list) => list.filter((x) => x.id !== u.id)),
      error: (err) => this.error.set(err.error?.message ?? 'No se pudo eliminar.'),
    });
  }

  protected esYo(u: User): boolean {
    return u.id === this.auth.user()?.id;
  }

  protected roleBadge(role: Role): string {
    return role === 'admin' ? 'badge-soft badge-error' : role === 'staff' ? 'badge-soft badge-info' : 'badge-soft badge-neutral';
  }
}
