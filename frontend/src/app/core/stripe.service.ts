import { Injectable } from '@angular/core';
import type {
  Stripe,
  StripeElements,
  StripePaymentElement,
} from '@stripe/stripe-js';

/**
 * Thin wrapper around Stripe.js, loaded lazily from `pure` (no auto side-effect
 * injection of the script until we actually load with a key). All methods are
 * defensive: callers must handle a null return / thrown error so a Stripe.js
 * load failure degrades gracefully (the reservation already exists).
 */
@Injectable({ providedIn: 'root' })
export class StripeService {
  private stripe: Stripe | null = null;
  private elements: StripeElements | null = null;
  private paymentElement: StripePaymentElement | null = null;
  private loadedKey: string | null = null;

  /** Load Stripe.js with the publishable key (cached per key). */
  async load(publishableKey: string): Promise<Stripe | null> {
    if (this.stripe && this.loadedKey === publishableKey) {
      return this.stripe;
    }
    const { loadStripe } = await import('@stripe/stripe-js/pure');
    this.stripe = await loadStripe(publishableKey);
    this.loadedKey = publishableKey;
    return this.stripe;
  }

  /** Mount a Payment Element into the given host using the PaymentIntent secret. */
  async mountPaymentElement(
    clientSecret: string,
    host: HTMLElement,
  ): Promise<boolean> {
    if (!this.stripe) return false;
    this.elements = this.stripe.elements({ clientSecret });
    this.paymentElement = this.elements.create('payment');
    this.paymentElement.mount(host);
    return true;
  }

  /**
   * Confirm the payment with no redirect. Returns an error message on failure,
   * or null on success.
   */
  async confirm(): Promise<string | null> {
    if (!this.stripe || !this.elements) {
      return 'stripe_not_ready';
    }
    const result = await this.stripe.confirmPayment({
      elements: this.elements,
      redirect: 'if_required',
    });
    if (result.error) {
      return result.error.message ?? 'payment_failed';
    }
    return null;
  }

  /** Tear down mounted elements (call when the modal closes). */
  destroy(): void {
    try {
      this.paymentElement?.destroy();
    } catch {
      /* noop */
    }
    this.paymentElement = null;
    this.elements = null;
  }
}
