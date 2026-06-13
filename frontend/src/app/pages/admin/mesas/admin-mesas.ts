import { Component, OnInit, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AdminService } from '../../../core/admin.service';
import { ApiValidationError, Mesa } from '../../../core/reserva.model';

@Component({
  selector: 'app-admin-mesas',
  imports: [ReactiveFormsModule],
  templateUrl: './admin-mesas.html',
})
export class AdminMesas implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly admin = inject(AdminService);

  protected readonly mesas = signal<Mesa[]>([]);
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  // New table form
  protected readonly form = this.fb.nonNullable.group({
    numero: [null as number | null, [Validators.required, Validators.min(1)]],
    capacidad: [2, [Validators.required, Validators.min(1), Validators.max(50)]],
  });

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);
    this.admin.mesas().subscribe({
      next: (m) => {
        this.mesas.set(m);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected crear(): void {
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const { numero, capacidad } = this.form.getRawValue();
    this.saving.set(true);
    this.admin.createMesa({ numero: numero!, capacidad, activa: true }).subscribe({
      next: (m) => {
        this.mesas.update((list) => [...list, m].sort((a, b) => a.numero - b.numero));
        this.form.reset({ numero: null, capacidad: 2 });
        this.saving.set(false);
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        this.serverErrors.set(body?.errors ?? { general: ['No se pudo crear la mesa.'] });
        this.saving.set(false);
      },
    });
  }

  protected toggleActiva(m: Mesa): void {
    this.admin.updateMesa(m.id, { numero: m.numero, capacidad: m.capacidad, activa: !m.activa }).subscribe((u) => {
      this.mesas.update((list) => list.map((x) => (x.id === m.id ? u : x)));
    });
  }

  protected eliminar(m: Mesa): void {
    this.admin.deleteMesa(m.id).subscribe(() => {
      this.mesas.update((list) => list.filter((x) => x.id !== m.id));
    });
  }
}
