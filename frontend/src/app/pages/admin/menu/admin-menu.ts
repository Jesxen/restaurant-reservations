import { Component, OnInit, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AdminService } from '../../../core/admin.service';
import { Categoria, Plato } from '../../../core/menu.model';
import { ApiValidationError } from '../../../core/reserva.model';

@Component({
  selector: 'app-admin-menu',
  imports: [ReactiveFormsModule],
  templateUrl: './admin-menu.html',
})
export class AdminMenu implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly admin = inject(AdminService);

  protected readonly categorias = signal<Categoria[]>([]);
  protected readonly loading = signal(true);
  protected readonly categoriaErrors = signal<Record<string, string[]>>({});
  protected readonly platoErrors = signal<Record<string, string[]>>({});

  // New categoria form
  protected readonly categoriaForm = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(60)]],
  });

  // New plato form
  protected readonly platoForm = this.fb.nonNullable.group({
    categoria_id: [null as number | null, [Validators.required]],
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(120)]],
    precio: [null as number | null, [Validators.required, Validators.min(0)]],
    descripcion: [''],
  });

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);
    this.admin.categorias().subscribe({
      next: (c) => {
        this.categorias.set(c);
        this.platoForm.controls.categoria_id.setValue(c[0]?.id ?? null);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  protected categoriaErrorsFor(field: string): string[] {
    return this.categoriaErrors()[field] ?? [];
  }
  protected platoErrorsFor(field: string): string[] {
    return this.platoErrors()[field] ?? [];
  }

  protected crearCategoria(): void {
    this.categoriaErrors.set({});
    if (this.categoriaForm.invalid) {
      this.categoriaForm.markAllAsTouched();
      return;
    }
    const orden = this.categorias().length + 1;
    const nombre = this.categoriaForm.getRawValue().nombre;
    this.admin.createCategoria({ nombre, orden, activa: true }).subscribe({
      next: (cat) => {
        this.categorias.update((list) => [...list, { ...cat, platos: cat.platos ?? [] }]);
        this.categoriaForm.reset({ nombre: '' });
        if (this.platoForm.controls.categoria_id.value === null) {
          this.platoForm.controls.categoria_id.setValue(cat.id);
        }
      },
      error: (err: HttpErrorResponse) => {
        const body = err.error as ApiValidationError | null;
        this.categoriaErrors.set(body?.errors ?? { general: ['No se pudo crear la categoría.'] });
      },
    });
  }

  protected eliminarCategoria(cat: Categoria): void {
    this.admin.deleteCategoria(cat.id).subscribe(() => {
      this.categorias.update((list) => list.filter((c) => c.id !== cat.id));
    });
  }

  protected crearPlato(): void {
    this.platoErrors.set({});
    if (this.platoForm.invalid) {
      this.platoForm.markAllAsTouched();
      return;
    }
    const { categoria_id, nombre, precio, descripcion } = this.platoForm.getRawValue();
    this.admin
      .createPlato({
        categoria_id: categoria_id!,
        nombre,
        descripcion: descripcion || null,
        precio: precio!,
        disponible: true,
      })
      .subscribe({
        next: (plato) => {
          this.categorias.update((list) =>
            list.map((c) => (c.id === plato.categoria_id ? { ...c, platos: [...c.platos, plato] } : c)),
          );
          this.platoForm.patchValue({ nombre: '', precio: null, descripcion: '' });
          this.platoForm.controls.nombre.markAsUntouched();
          this.platoForm.controls.precio.markAsUntouched();
        },
        error: (err: HttpErrorResponse) => {
          const body = err.error as ApiValidationError | null;
          this.platoErrors.set(body?.errors ?? { general: ['No se pudo crear el plato.'] });
        },
      });
  }

  protected toggleDisponible(plato: Plato): void {
    this.admin
      .updatePlato(plato.id, {
        categoria_id: plato.categoria_id,
        nombre: plato.nombre,
        descripcion: plato.descripcion,
        precio: plato.precio,
        disponible: !plato.disponible,
      })
      .subscribe((u) => {
        this.categorias.update((list) =>
          list.map((c) =>
            c.id === u.categoria_id ? { ...c, platos: c.platos.map((p) => (p.id === u.id ? u : p)) } : c,
          ),
        );
      });
  }

  protected eliminarPlato(plato: Plato): void {
    this.admin.deletePlato(plato.id).subscribe(() => {
      this.categorias.update((list) =>
        list.map((c) =>
          c.id === plato.categoria_id ? { ...c, platos: c.platos.filter((p) => p.id !== plato.id) } : c,
        ),
      );
    });
  }
}
