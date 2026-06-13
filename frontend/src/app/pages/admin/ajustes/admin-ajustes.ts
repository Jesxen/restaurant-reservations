import { Component, OnInit, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormArray, FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { AdminService } from '../../../core/admin.service';
import { Settings } from '../../../core/user.model';
import { ApiValidationError } from '../../../core/reserva.model';

interface DiaOpcion {
  valor: number;
  etiqueta: string;
}

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

  /** 0 = Sunday … 6 = Saturday (matches backend dias_cierre). */
  protected readonly dias: DiaOpcion[] = [
    { valor: 1, etiqueta: 'Lunes' },
    { valor: 2, etiqueta: 'Martes' },
    { valor: 3, etiqueta: 'Miércoles' },
    { valor: 4, etiqueta: 'Jueves' },
    { valor: 5, etiqueta: 'Viernes' },
    { valor: 6, etiqueta: 'Sábado' },
    { valor: 0, etiqueta: 'Domingo' },
  ];

  protected readonly diasCierre = signal<number[]>([]);

  protected readonly form = this.fb.nonNullable.group({
    nombre_restaurante: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(120)]],
    aforo: [null as number | null, [Validators.min(1)]],
    ticket_medio: [35, [Validators.required, Validators.min(0)]],
    horarios_detalle: this.fb.nonNullable.group({
      apertura_comida: ['13:00', [Validators.required]],
      cierre_comida: ['16:00', [Validators.required]],
      apertura_cena: ['20:00', [Validators.required]],
      cierre_cena: ['23:30', [Validators.required]],
    }),
    reservas: this.fb.nonNullable.group({
      intervalo_slots: [30, [Validators.required, Validators.min(5), Validators.max(240)]],
      antelacion_min_horas: [2, [Validators.required, Validators.min(0), Validators.max(168)]],
      max_personas_online: [12, [Validators.required, Validators.min(1), Validators.max(255)]],
      ventana_dias: [60, [Validators.required, Validators.min(1), Validators.max(365)]],
    }),
    branding: this.fb.nonNullable.group({
      logo_url: [''],
      color_primario: ['#a16207'],
      color_acento: ['#3a2a1a'],
    }),
    contacto: this.fb.nonNullable.group({
      email: ['', [Validators.email]],
      telefono: [''],
      direccion: [''],
      ciudad: [''],
    }),
    coords: this.fb.nonNullable.group({
      lat: [null as number | null],
      lng: [null as number | null],
    }),
    social: this.fb.nonNullable.group({
      instagram: [''],
      facebook: [''],
      tiktok: [''],
    }),
    deposito: this.fb.nonNullable.group({
      activo: [false],
      por_persona: [0, [Validators.min(0)]],
    }),
    galeria: this.fb.array<FormControl<string>>([]),
  });

  get galeria(): FormArray<FormControl<string>> {
    return this.form.controls.galeria;
  }

  ngOnInit(): void {
    this.admin.settings().subscribe({
      next: (s) => {
        this.patch(s);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private patch(s: Settings): void {
    this.form.patchValue({
      nombre_restaurante: s.nombre_restaurante,
      aforo: s.aforo,
      ticket_medio: s.ticket_medio,
      horarios_detalle: s.horarios_detalle,
      reservas: s.reservas,
      branding: {
        logo_url: s.branding.logo_url ?? '',
        color_primario: s.branding.color_primario ?? '#a16207',
        color_acento: s.branding.color_acento ?? '#3a2a1a',
      },
      contacto: {
        email: s.contacto.email ?? '',
        telefono: s.contacto.telefono ?? '',
        direccion: s.contacto.direccion ?? '',
        ciudad: s.contacto.ciudad ?? '',
      },
      coords: s.coords,
      social: {
        instagram: s.social.instagram ?? '',
        facebook: s.social.facebook ?? '',
        tiktok: s.social.tiktok ?? '',
      },
      deposito: {
        activo: s.deposito?.activo ?? false,
        por_persona: s.deposito?.por_persona ?? 0,
      },
    });
    this.diasCierre.set([...s.dias_cierre]);
    this.galeria.clear();
    for (const url of s.galeria) {
      this.galeria.push(this.fb.nonNullable.control(url));
    }
  }

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected toggleDia(valor: number, checked: boolean): void {
    this.diasCierre.update((list) =>
      checked ? [...list, valor].sort((a, b) => a - b) : list.filter((d) => d !== valor),
    );
  }

  protected esDiaCierre(valor: number): boolean {
    return this.diasCierre().includes(valor);
  }

  protected addImagen(): void {
    this.galeria.push(this.fb.nonNullable.control(''));
  }

  protected removeImagen(i: number): void {
    this.galeria.removeAt(i);
  }

  protected guardar(): void {
    this.saved.set(false);
    this.serverErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.saving.set(true);
    const raw = this.form.getRawValue();
    const payload: Settings = {
      nombre_restaurante: raw.nombre_restaurante,
      aforo: raw.aforo,
      ticket_medio: raw.ticket_medio,
      horarios: {
        comida: `${raw.horarios_detalle.apertura_comida}–${raw.horarios_detalle.cierre_comida}`,
        cena: `${raw.horarios_detalle.apertura_cena}–${raw.horarios_detalle.cierre_cena}`,
      },
      horarios_detalle: raw.horarios_detalle,
      dias_cierre: this.diasCierre(),
      reservas: raw.reservas,
      branding: {
        logo_url: raw.branding.logo_url || null,
        color_primario: raw.branding.color_primario || null,
        color_acento: raw.branding.color_acento || null,
      },
      contacto: {
        email: raw.contacto.email || null,
        telefono: raw.contacto.telefono || null,
        direccion: raw.contacto.direccion || null,
        ciudad: raw.contacto.ciudad || null,
      },
      coords: raw.coords,
      social: {
        instagram: raw.social.instagram || null,
        facebook: raw.social.facebook || null,
        tiktok: raw.social.tiktok || null,
      },
      deposito: {
        activo: raw.deposito.activo,
        por_persona: raw.deposito.por_persona,
      },
      galeria: raw.galeria.filter((u) => u.trim().length > 0),
    };

    this.admin.updateSettings(payload).subscribe({
      next: (updated) => {
        this.patch(updated);
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
