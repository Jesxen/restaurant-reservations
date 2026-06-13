import { Component, OnInit, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AdminService } from '../../../core/admin.service';
import { BlackoutDate } from '../../../core/admin.model';
import { ApiValidationError } from '../../../core/reserva.model';

@Component({
  selector: 'app-admin-cierres',
  imports: [ReactiveFormsModule],
  templateUrl: './admin-cierres.html',
})
export class AdminCierres implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly admin = inject(AdminService);

  protected readonly fechas = signal<BlackoutDate[]>([]);
  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly deleting = signal<number | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  protected readonly minDate = new Date().toISOString().slice(0, 10);

  protected readonly form = this.fb.nonNullable.group({
    fecha: ['', [Validators.required]],
    motivo: ['', [Validators.maxLength(255)]],
  });

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);
    this.admin.blackoutDates().subscribe({
      next: (data) => {
        this.fechas.set(data);
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
    this.saving.set(true);
    const { fecha, motivo } = this.form.getRawValue();
    this.admin.createBlackout({ fecha, motivo: motivo || null }).subscribe({
      next: (created) => {
        this.fechas.update((list) =>
          [...list, created].sort((a, b) => a.fecha.localeCompare(b.fecha)),
        );
        this.form.reset({ fecha: '', motivo: '' });
        this.saving.set(false);
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

  protected eliminar(item: BlackoutDate): void {
    this.deleting.set(item.id);
    this.admin.deleteBlackout(item.id).subscribe({
      next: () => {
        this.fechas.update((list) => list.filter((x) => x.id !== item.id));
        this.deleting.set(null);
      },
      error: () => this.deleting.set(null),
    });
  }
}
