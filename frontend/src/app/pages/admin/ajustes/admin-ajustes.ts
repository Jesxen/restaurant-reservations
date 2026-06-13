import { Component, OnInit, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AdminService } from '../../../core/admin.service';
import { Settings } from '../../../core/user.model';
import { ApiValidationError } from '../../../core/reserva.model';

@Component({
  selector: 'app-admin-ajustes',
  imports: [ReactiveFormsModule],
  templateUrl: './admin-ajustes.html',
})
export class AdminAjustes implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly admin = inject(AdminService);

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly saved = signal(false);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly form = this.fb.nonNullable.group({
    nombre_restaurante: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(120)]],
    aforo: [null as number | null, [Validators.min(1)]],
    apertura_comida: ['13:00', [Validators.required]],
    cierre_comida: ['16:00', [Validators.required]],
    apertura_cena: ['20:00', [Validators.required]],
    cierre_cena: ['23:30', [Validators.required]],
    duracion_turno: [120, [Validators.required, Validators.min(30), Validators.max(480)]],
    ticket_medio: [35, [Validators.required, Validators.min(0)]],
  });

  ngOnInit(): void {
    this.admin.settings().subscribe({
      next: (s) => {
        this.form.patchValue(s);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected guardar(): void {
    this.saved.set(false);
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.saving.set(true);
    this.admin.updateSettings(this.form.getRawValue() as Settings).subscribe({
      next: (updated) => {
        this.form.patchValue(updated);
        this.saving.set(false);
        this.saved.set(true);
        setTimeout(() => this.saved.set(false), 2500);
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        if (err.status === 422 && body?.errors) {
          this.serverErrors.set(body.errors);
        }
        this.saving.set(false);
      },
    });
  }
}
