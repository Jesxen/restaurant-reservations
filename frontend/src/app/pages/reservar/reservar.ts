import { Component, ElementRef, OnInit, computed, inject, signal, viewChild } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { HttpErrorResponse } from '@angular/common/http';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ReservaService } from '../../core/reserva.service';
import { AuthService } from '../../core/auth.service';
import { SettingsService } from '../../core/settings.service';
import { I18nService } from '../../core/i18n.service';
import { StripeService } from '../../core/stripe.service';
import { WaitlistService } from '../../core/waitlist.service';
import { ApiValidationError } from '../../core/reserva.model';
import { SlotPicker } from '../../shared/slot-picker';
import { TranslatePipe } from '../../core/translate.pipe';

@Component({
  selector: 'app-reservar',
  imports: [ReactiveFormsModule, RouterLink, SlotPicker, TranslatePipe],
  templateUrl: './reservar.html',
})
export class Reservar implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly reservaService = inject(ReservaService);
  private readonly settingsService = inject(SettingsService);
  private readonly stripeService = inject(StripeService);
  private readonly waitlistService = inject(WaitlistService);
  protected readonly auth = inject(AuthService);
  protected readonly i18n = inject(I18nService);

  protected readonly settings = this.settingsService.settings;

  protected readonly submitting = signal(false);
  protected readonly successMessage = signal<string | null>(null);
  protected readonly serverErrors = signal<Record<string, string[]>>({});

  /** Today (YYYY-MM-DD) used as the min for the date input. */
  protected readonly minDate = new Date().toISOString().slice(0, 10);

  // --- Waitlist state ---
  protected readonly waitlistOpen = signal(false);
  protected readonly waitlistSubmitting = signal(false);
  protected readonly waitlistMessage = signal<string | null>(null);
  protected readonly waitlistErrors = signal<Record<string, string[]>>({});

  // --- Stripe deposit state ---
  protected readonly depositSecret = signal<string | null>(null);
  protected readonly depositReservaPersonas = signal(0);
  protected readonly paying = signal(false);
  protected readonly depositMessage = signal<string | null>(null);
  protected readonly depositError = signal<string | null>(null);
  protected readonly depositLoadFailed = signal(false);
  private readonly payHost = viewChild<ElementRef<HTMLDivElement>>('payHost');

  /** Effective deposit config; only collect when activo. */
  protected readonly deposito = computed(() => this.settings().deposito);

  /** Per-person amount formatted for display. */
  protected readonly depositPerPerson = computed(() => this.deposito()?.por_persona ?? 0);
  protected readonly depositTotal = computed(
    () => this.depositPerPerson() * this.depositReservaPersonas(),
  );

  protected readonly form = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
    telefono: ['', [Validators.pattern(/^[+\d][\d\s().-]{6,19}$/)]],
    fecha: ['', [Validators.required]],
    hora: ['', [Validators.required]],
    personas: [2, [Validators.required, Validators.min(1), Validators.max(255)]],
    notas: [''],
  });

  /** Reactive mirror of the date control, fed to the slot picker. */
  protected readonly fecha = toSignal(this.form.controls.fecha.valueChanges, {
    initialValue: this.form.controls.fecha.value,
  });

  ngOnInit(): void {
    const user = this.auth.user();
    if (user) {
      this.form.patchValue({ nombre: user.name, email: user.email, telefono: user.phone ?? '' });
    }
  }

  protected errorsFor(field: string): string[] {
    return this.serverErrors()[field] ?? [];
  }

  protected waitlistErrorsFor(field: string): string[] {
    return this.waitlistErrors()[field] ?? [];
  }

  /** Whether the slot field should render its error state. */
  protected get horaInvalid(): boolean {
    const c = this.form.controls.hora;
    return c.touched && c.invalid;
  }

  /** Set the chosen slot back into the reactive form. */
  protected onHoraChange(hora: string): void {
    this.form.controls.hora.setValue(hora);
    this.form.controls.hora.markAsTouched();
  }

  protected money(value: number): string {
    return new Intl.NumberFormat(this.i18n.lang() === 'en' ? 'en-GB' : 'es-ES', {
      style: 'currency',
      currency: 'EUR',
    }).format(value);
  }

  protected submit(): void {
    this.successMessage.set(null);
    this.serverErrors.set({});
    this.depositMessage.set(null);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    const raw = this.form.getRawValue();
    this.reservaService
      .create({
        nombre: raw.nombre,
        email: raw.email,
        telefono: raw.telefono || undefined,
        fecha: raw.fecha,
        hora: raw.hora,
        personas: raw.personas,
        notas: raw.notas || undefined,
      })
      .subscribe({
        next: (res) => {
          this.submitting.set(false);
          // Deposit required → open payment step before resetting.
          if (res.client_secret && this.deposito()?.activo) {
            this.depositReservaPersonas.set(res.data.personas);
            this.openDeposit(res.client_secret);
            return;
          }
          this.successMessage.set(res.message);
          this.resetForm();
        },
        error: (err: HttpErrorResponse) => {
          this.submitting.set(false);
          const body = err.error as ApiValidationError | null;
          this.serverErrors.set(body?.errors ?? { general: ['No se pudo guardar la reserva.'] });
        },
      });
  }

  private resetForm(): void {
    this.form.reset({
      personas: 2,
      nombre: this.auth.user()?.name ?? '',
      email: this.auth.user()?.email ?? '',
      telefono: this.auth.user()?.phone ?? '',
      fecha: '',
      hora: '',
      notas: '',
    });
  }

  // --- Stripe deposit step ---
  private async openDeposit(clientSecret: string): Promise<void> {
    this.depositSecret.set(clientSecret);
    this.depositError.set(null);
    this.depositLoadFailed.set(false);

    const key = this.deposito()?.stripe_key;
    if (!key) {
      this.depositLoadFailed.set(true);
      return;
    }
    try {
      const stripe = await this.stripeService.load(key);
      if (!stripe) {
        this.depositLoadFailed.set(true);
        return;
      }
      // Wait a tick for the modal host to render.
      setTimeout(async () => {
        const host = this.payHost()?.nativeElement;
        if (!host) {
          this.depositLoadFailed.set(true);
          return;
        }
        const mounted = await this.stripeService.mountPaymentElement(clientSecret, host);
        if (!mounted) this.depositLoadFailed.set(true);
      });
    } catch {
      this.depositLoadFailed.set(true);
    }
  }

  protected async payDeposit(): Promise<void> {
    this.paying.set(true);
    this.depositError.set(null);
    const error = await this.stripeService.confirm();
    this.paying.set(false);
    if (error) {
      this.depositError.set(error === 'payment_failed' || error === 'stripe_not_ready' ? null : error);
      // Generic fallback message via translation key handled in template.
      if (!this.depositError()) this.depositError.set('__fail__');
      return;
    }
    this.depositMessage.set(this.i18n.translate('reservar.dep.ok'));
    this.closeDeposit();
    this.successMessage.set(this.i18n.translate('reservar.dep.ok'));
    this.resetForm();
  }

  protected closeDeposit(): void {
    this.stripeService.destroy();
    this.depositSecret.set(null);
  }

  // --- Waitlist ---
  protected openWaitlist(): void {
    this.waitlistMessage.set(null);
    this.waitlistErrors.set({});
    this.waitlistOpen.set(true);
  }

  protected closeWaitlist(): void {
    this.waitlistOpen.set(false);
  }

  protected submitWaitlist(): void {
    this.waitlistMessage.set(null);
    this.waitlistErrors.set({});
    const raw = this.form.getRawValue();
    // Need at least name, email, date and time to join.
    const missing: Record<string, string[]> = {};
    if (!raw.nombre) missing['nombre'] = ['Campo obligatorio'];
    if (!raw.email) missing['email'] = ['Campo obligatorio'];
    if (!raw.fecha) missing['fecha'] = ['Campo obligatorio'];
    if (!raw.hora) missing['hora'] = ['Campo obligatorio'];
    if (Object.keys(missing).length) {
      this.waitlistErrors.set(missing);
      return;
    }

    this.waitlistSubmitting.set(true);
    this.waitlistService
      .join({
        nombre: raw.nombre,
        email: raw.email,
        telefono: raw.telefono || undefined,
        fecha: raw.fecha,
        hora: raw.hora,
        personas: raw.personas,
      })
      .subscribe({
        next: () => {
          this.waitlistSubmitting.set(false);
          this.waitlistMessage.set(this.i18n.translate('reservar.wl.ok'));
          this.waitlistOpen.set(false);
          this.successMessage.set(this.i18n.translate('reservar.wl.ok'));
        },
        error: (err: HttpErrorResponse) => {
          this.waitlistSubmitting.set(false);
          const body = err.error as ApiValidationError | null;
          this.waitlistErrors.set(
            body?.errors ?? { general: ['No se pudo añadir a la lista de espera.'] },
          );
        },
      });
  }
}
