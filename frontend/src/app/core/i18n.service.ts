import { Injectable, computed, signal } from '@angular/core';

export type Lang = 'es' | 'en';

const STORAGE_KEY = 'rl_lang';

/** Flat key → string dictionaries. ES is the source of truth (defaults). */
const ES: Record<string, string> = {
  // Navbar / footer
  'nav.inicio': 'Inicio',
  'nav.reservar': 'Reservar',
  'nav.entrar': 'Entrar',
  'nav.cuenta': 'Mi cuenta',
  'nav.admin': 'Panel admin',
  'nav.logout': 'Cerrar sesión',
  'footer.reservas_rec': 'Recomendado reservar con antelación',
  'footer.privacidad': 'Privacidad',
  'footer.terminos': 'Términos',
  'footer.contacto': 'Contacto',
  'footer.tagline': 'Cocina canaria de autor.',
  'lang.es': 'ES',
  'lang.en': 'EN',
  'lang.switch': 'Idioma',

  // Landing
  'landing.hero.eyebrow': 'San Cristóbal de La Laguna · Tenerife',
  'landing.hero.title1': 'Una experiencia',
  'landing.hero.title2': 'gastronómica',
  'landing.hero.title3': 'única',
  'landing.hero.subtitle':
    'Cocina canaria de autor en el corazón histórico de La Laguna. Reserva confirmada al instante.',
  'landing.hero.reservar': 'Reservar mesa',
  'landing.hero.carta': 'Ver la carta',
  'landing.hl.producto': 'Producto',
  'landing.hl.producto_t': 'Cocina de mercado',
  'landing.hl.producto_d': 'Ingredientes locales y de temporada.',
  'landing.hl.reserva': 'Reserva',
  'landing.hl.reserva_t': 'Confirmación al instante',
  'landing.hl.reserva_d': 'Disponibilidad en tiempo real.',
  'landing.hl.ubicacion': 'Ubicación',
  'landing.hl.ubicacion_t': 'Casco histórico',
  'landing.hl.ubicacion_d': 'En el corazón de La Laguna.',
  'landing.carta.eyebrow': 'La carta',
  'landing.carta.title': 'Nuestra cocina',
  'landing.ubic.eyebrow': 'Dónde estamos',
  'landing.ubic.title': 'Te esperamos en La Laguna',
  'landing.ubic.desc':
    'Un espacio íntimo en el casco histórico, Patrimonio de la Humanidad, para una velada inolvidable.',
  'landing.ubic.comida': 'Comida',
  'landing.ubic.cena': 'Cena',
  'landing.ubic.reservas': 'Reservas',
  'landing.ubic.reservar': 'Reservar ahora',
  // Reviews section
  'reviews.eyebrow': 'Opiniones',
  'reviews.title': 'Lo que dicen nuestros clientes',
  'reviews.empty': 'Aún no hay reseñas. ¡Sé el primero en opinar!',
  'reviews.based_on': 'Basado en {n} reseñas',
  'reviews.of5': 'de 5',

  // Reservar
  'reservar.eyebrow': 'Reserva',
  'reservar.title': 'Reserva tu mesa',
  'reservar.subtitle':
    'Selecciona fecha, horario y número de comensales. Confirmamos la disponibilidad al instante.',
  'reservar.tienes_cuenta': '¿Tienes cuenta?',
  'reservar.inicia_sesion': 'Inicia sesión',
  'reservar.para_gestionar': 'para gestionar tus reservas.',
  'reservar.nombre': 'Nombre',
  'reservar.nombre_ph': 'Tu nombre',
  'reservar.email': 'Correo electrónico',
  'reservar.email_ph': 'tu@correo.com',
  'reservar.telefono': 'Teléfono',
  'reservar.telefono_ph': '+34 600 000 000',
  'reservar.telefono_nota': 'Te avisaremos por SMS si nos das un teléfono.',
  'reservar.fecha': 'Fecha',
  'reservar.hora': 'Hora',
  'reservar.personas': 'Número de personas',
  'reservar.notas': 'Notas',
  'reservar.opcional': '(opcional)',
  'reservar.notas_ph': 'Alergias, preferencias…',
  'reservar.confirmar': 'Confirmar reserva',
  'reservar.enviando': 'Enviando…',
  'reservar.img_alt': 'Mesa del restaurante',
  // Deposit (Stripe)
  'reservar.dep.title': 'Depósito requerido',
  'reservar.dep.desc':
    'Para confirmar tu reserva necesitamos un depósito reembolsable de {amount} ({per} × {people}).',
  'reservar.dep.pagar': 'Pagar depósito',
  'reservar.dep.procesando': 'Procesando pago…',
  'reservar.dep.ok': 'Depósito pagado, reserva pendiente de confirmación.',
  'reservar.dep.fail': 'No se pudo procesar el pago. Tu reserva queda con el depósito pendiente.',
  'reservar.dep.load_fail': 'No se pudo cargar el formulario de pago. Inténtalo más tarde.',
  'reservar.dep.cerrar': 'Cerrar',
  'reservar.dep.por_persona': 'por persona',
  // Waitlist
  'reservar.wl.full': 'No quedan plazas para la hora seleccionada.',
  'reservar.wl.cta': 'Apúntate a la lista de espera',
  'reservar.wl.title': 'Lista de espera',
  'reservar.wl.desc':
    'Te avisaremos si se libera una mesa para esta fecha y hora.',
  'reservar.wl.enviar': 'Apuntarme',
  'reservar.wl.enviando': 'Enviando…',
  'reservar.wl.ok': 'Te avisaremos si se libera una mesa.',
  'reservar.wl.cancelar': 'Cancelar',

  // Auth: login
  'login.eyebrow': 'Bienvenido',
  'login.title': 'Iniciar sesión',
  'login.email': 'Correo',
  'login.password': 'Contraseña',
  'login.entrar': 'Entrar',
  'login.entrando': 'Entrando…',
  'login.olvidaste': '¿Olvidaste tu contraseña?',
  'login.no_cuenta': '¿No tienes cuenta?',
  'login.registrate': 'Regístrate',
  // Auth: registro
  'registro.eyebrow': 'Únete',
  'registro.title': 'Crear cuenta',
  'registro.nombre': 'Nombre',
  'registro.nombre_ph': 'Tu nombre',
  'registro.email': 'Correo',
  'registro.telefono': 'Teléfono',
  'registro.telefono_nota': 'Te avisaremos por SMS si nos das un teléfono.',
  'registro.password': 'Contraseña',
  'registro.password_ph': 'Mínimo 8 caracteres',
  'registro.password_rep': 'Repetir contraseña',
  'registro.crear': 'Crear cuenta',
  'registro.creando': 'Creando…',
  'registro.ya_cuenta': '¿Ya tienes cuenta?',
  'registro.inicia': 'Inicia sesión',
  // Auth: recuperar
  'recuperar.eyebrow': 'Recuperar acceso',
  'recuperar.title': 'Recuperar contraseña',
  'recuperar.desc': 'Introduce tu correo y te enviaremos un enlace para restablecer la contraseña.',
  'recuperar.email': 'Correo',
  'recuperar.enviar': 'Enviar enlace',
  'recuperar.enviando': 'Enviando…',
  'recuperar.volver': 'Volver a iniciar sesión',
  // Auth: restablecer
  'restablecer.eyebrow': 'Nueva contraseña',
  'restablecer.title': 'Restablecer contraseña',
  'restablecer.password': 'Nueva contraseña',
  'restablecer.password_rep': 'Repetir contraseña',
  'restablecer.guardar': 'Guardar contraseña',
  'restablecer.guardando': 'Guardando…',

  // Cuenta
  'cuenta.eyebrow': 'Mi cuenta',
  'cuenta.hola': 'Hola',
  'cuenta.nueva': 'Nueva reserva',
  'cuenta.verifica': 'Verifica tu correo.',
  'cuenta.verifica_desc': 'Te hemos enviado un enlace de verificación a',
  'cuenta.reenviar': 'Reenviar',
  'cuenta.mis_reservas': 'Mis reservas',
  'cuenta.sin_reservas': 'Aún no tienes reservas.',
  'cuenta.reservar_mesa': 'Reservar mesa',
  'cuenta.personas': 'personas',
  'cuenta.mesa': 'Mesa',
  'cuenta.mesa_por_asignar': 'Mesa por asignar',
  'cuenta.editar': 'Editar',
  'cuenta.cancelar': 'Cancelar',
  // Cuenta: reseña
  'cuenta.review.eyebrow': 'Tu opinión',
  'cuenta.review.title': 'Deja tu reseña',
  'cuenta.review.desc': 'Cuéntanos qué te pareció tu visita. Tu reseña se publicará tras revisión.',
  'cuenta.review.rating': 'Valoración',
  'cuenta.review.comentario': 'Comentario',
  'cuenta.review.comentario_ph': '¿Qué te ha parecido tu experiencia?',
  'cuenta.review.enviar': 'Enviar reseña',
  'cuenta.review.enviando': 'Enviando…',
  'cuenta.review.pendiente': 'Gracias. Tu reseña queda pendiente de aprobación.',
  // Cuenta: lista de espera
  'cuenta.wl.title': 'Lista de espera',
  'cuenta.wl.empty': 'No estás en ninguna lista de espera.',
  'cuenta.wl.personas': 'personas',

  // Contacto
  'contacto.eyebrow': 'Contacto',
  'contacto.title': 'Escríbenos',
  'contacto.desc': '¿Tienes alguna pregunta? Rellena el formulario y te responderemos lo antes posible.',
  'contacto.nombre': 'Nombre',
  'contacto.email': 'Correo',
  'contacto.asunto': 'Asunto',
  'contacto.mensaje': 'Mensaje',
  'contacto.enviar': 'Enviar mensaje',
  'contacto.enviando': 'Enviando…',

  // Slot picker
  'slot.pick_date': 'Selecciona una fecha para ver los horarios disponibles.',
  'slot.loading': 'Cargando horarios…',
  'slot.closed': 'Cerrado en la fecha seleccionada.',
  'slot.none': 'No quedan horarios disponibles para esta fecha.',
  'slot.pick_one': 'Selecciona un horario',
  'slot.full': 'Completo',
  'slot.places': 'plazas disponibles',

  // Generic
  'common.cancelar': 'Cancelar',
  'common.cerrar': 'Cerrar',
};

const EN: Record<string, string> = {
  'nav.inicio': 'Home',
  'nav.reservar': 'Book',
  'nav.entrar': 'Sign in',
  'nav.cuenta': 'My account',
  'nav.admin': 'Admin panel',
  'nav.logout': 'Sign out',
  'footer.reservas_rec': 'Booking ahead is recommended',
  'footer.privacidad': 'Privacy',
  'footer.terminos': 'Terms',
  'footer.contacto': 'Contact',
  'footer.tagline': 'Signature Canarian cuisine.',
  'lang.es': 'ES',
  'lang.en': 'EN',
  'lang.switch': 'Language',

  'landing.hero.eyebrow': 'San Cristóbal de La Laguna · Tenerife',
  'landing.hero.title1': 'A unique',
  'landing.hero.title2': 'gastronomic',
  'landing.hero.title3': 'experience',
  'landing.hero.subtitle':
    'Signature Canarian cuisine in the historic heart of La Laguna. Instantly confirmed booking.',
  'landing.hero.reservar': 'Book a table',
  'landing.hero.carta': 'View the menu',
  'landing.hl.producto': 'Produce',
  'landing.hl.producto_t': 'Market cuisine',
  'landing.hl.producto_d': 'Local, seasonal ingredients.',
  'landing.hl.reserva': 'Booking',
  'landing.hl.reserva_t': 'Instant confirmation',
  'landing.hl.reserva_d': 'Real-time availability.',
  'landing.hl.ubicacion': 'Location',
  'landing.hl.ubicacion_t': 'Historic quarter',
  'landing.hl.ubicacion_d': 'In the heart of La Laguna.',
  'landing.carta.eyebrow': 'The menu',
  'landing.carta.title': 'Our cuisine',
  'landing.ubic.eyebrow': 'Where we are',
  'landing.ubic.title': 'We await you in La Laguna',
  'landing.ubic.desc':
    'An intimate space in the historic quarter, a World Heritage Site, for an unforgettable evening.',
  'landing.ubic.comida': 'Lunch',
  'landing.ubic.cena': 'Dinner',
  'landing.ubic.reservas': 'Bookings',
  'landing.ubic.reservar': 'Book now',
  'reviews.eyebrow': 'Reviews',
  'reviews.title': 'What our guests say',
  'reviews.empty': 'No reviews yet. Be the first to share your opinion!',
  'reviews.based_on': 'Based on {n} reviews',
  'reviews.of5': 'out of 5',

  'reservar.eyebrow': 'Booking',
  'reservar.title': 'Book your table',
  'reservar.subtitle':
    'Choose a date, time and number of guests. We confirm availability instantly.',
  'reservar.tienes_cuenta': 'Have an account?',
  'reservar.inicia_sesion': 'Sign in',
  'reservar.para_gestionar': 'to manage your bookings.',
  'reservar.nombre': 'Name',
  'reservar.nombre_ph': 'Your name',
  'reservar.email': 'Email',
  'reservar.email_ph': 'you@email.com',
  'reservar.telefono': 'Phone',
  'reservar.telefono_ph': '+34 600 000 000',
  'reservar.telefono_nota': "We'll text you by SMS if you give us a phone number.",
  'reservar.fecha': 'Date',
  'reservar.hora': 'Time',
  'reservar.personas': 'Number of guests',
  'reservar.notas': 'Notes',
  'reservar.opcional': '(optional)',
  'reservar.notas_ph': 'Allergies, preferences…',
  'reservar.confirmar': 'Confirm booking',
  'reservar.enviando': 'Sending…',
  'reservar.img_alt': 'Restaurant table',
  'reservar.dep.title': 'Deposit required',
  'reservar.dep.desc':
    'To confirm your booking we need a refundable deposit of {amount} ({per} × {people}).',
  'reservar.dep.pagar': 'Pay deposit',
  'reservar.dep.procesando': 'Processing payment…',
  'reservar.dep.ok': 'Deposit paid, booking pending confirmation.',
  'reservar.dep.fail': 'The payment could not be processed. Your booking keeps the deposit pending.',
  'reservar.dep.load_fail': 'The payment form could not be loaded. Please try again later.',
  'reservar.dep.cerrar': 'Close',
  'reservar.dep.por_persona': 'per guest',
  'reservar.wl.full': 'No spots left for the selected time.',
  'reservar.wl.cta': 'Join the waitlist',
  'reservar.wl.title': 'Waitlist',
  'reservar.wl.desc': "We'll let you know if a table frees up for this date and time.",
  'reservar.wl.enviar': 'Join',
  'reservar.wl.enviando': 'Sending…',
  'reservar.wl.ok': "We'll let you know if a table frees up.",
  'reservar.wl.cancelar': 'Cancel',

  'login.eyebrow': 'Welcome',
  'login.title': 'Sign in',
  'login.email': 'Email',
  'login.password': 'Password',
  'login.entrar': 'Sign in',
  'login.entrando': 'Signing in…',
  'login.olvidaste': 'Forgot your password?',
  'login.no_cuenta': "Don't have an account?",
  'login.registrate': 'Sign up',
  'registro.eyebrow': 'Join us',
  'registro.title': 'Create account',
  'registro.nombre': 'Name',
  'registro.nombre_ph': 'Your name',
  'registro.email': 'Email',
  'registro.telefono': 'Phone',
  'registro.telefono_nota': "We'll text you by SMS if you give us a phone number.",
  'registro.password': 'Password',
  'registro.password_ph': 'At least 8 characters',
  'registro.password_rep': 'Repeat password',
  'registro.crear': 'Create account',
  'registro.creando': 'Creating…',
  'registro.ya_cuenta': 'Already have an account?',
  'registro.inicia': 'Sign in',
  'recuperar.eyebrow': 'Recover access',
  'recuperar.title': 'Recover password',
  'recuperar.desc': "Enter your email and we'll send you a link to reset your password.",
  'recuperar.email': 'Email',
  'recuperar.enviar': 'Send link',
  'recuperar.enviando': 'Sending…',
  'recuperar.volver': 'Back to sign in',
  'restablecer.eyebrow': 'New password',
  'restablecer.title': 'Reset password',
  'restablecer.password': 'New password',
  'restablecer.password_rep': 'Repeat password',
  'restablecer.guardar': 'Save password',
  'restablecer.guardando': 'Saving…',

  'cuenta.eyebrow': 'My account',
  'cuenta.hola': 'Hello',
  'cuenta.nueva': 'New booking',
  'cuenta.verifica': 'Verify your email.',
  'cuenta.verifica_desc': "We've sent a verification link to",
  'cuenta.reenviar': 'Resend',
  'cuenta.mis_reservas': 'My bookings',
  'cuenta.sin_reservas': "You don't have any bookings yet.",
  'cuenta.reservar_mesa': 'Book a table',
  'cuenta.personas': 'guests',
  'cuenta.mesa': 'Table',
  'cuenta.mesa_por_asignar': 'Table to be assigned',
  'cuenta.editar': 'Edit',
  'cuenta.cancelar': 'Cancel',
  'cuenta.review.eyebrow': 'Your opinion',
  'cuenta.review.title': 'Leave a review',
  'cuenta.review.desc': 'Tell us about your visit. Your review will be published after moderation.',
  'cuenta.review.rating': 'Rating',
  'cuenta.review.comentario': 'Comment',
  'cuenta.review.comentario_ph': 'How was your experience?',
  'cuenta.review.enviar': 'Submit review',
  'cuenta.review.enviando': 'Sending…',
  'cuenta.review.pendiente': 'Thank you. Your review is pending approval.',
  'cuenta.wl.title': 'Waitlist',
  'cuenta.wl.empty': "You're not on any waitlist.",
  'cuenta.wl.personas': 'guests',

  'contacto.eyebrow': 'Contact',
  'contacto.title': 'Write to us',
  'contacto.desc': "Have a question? Fill in the form and we'll get back to you as soon as possible.",
  'contacto.nombre': 'Name',
  'contacto.email': 'Email',
  'contacto.asunto': 'Subject',
  'contacto.mensaje': 'Message',
  'contacto.enviar': 'Send message',
  'contacto.enviando': 'Sending…',

  'slot.pick_date': 'Pick a date to see the available times.',
  'slot.loading': 'Loading times…',
  'slot.closed': 'Closed on the selected date.',
  'slot.none': 'No available times left for this date.',
  'slot.pick_one': 'Pick a time',
  'slot.full': 'Full',
  'slot.places': 'spots available',

  'common.cancelar': 'Cancel',
  'common.cerrar': 'Close',
};

const DICTIONARIES: Record<Lang, Record<string, string>> = { es: ES, en: EN };

@Injectable({ providedIn: 'root' })
export class I18nService {
  private readonly _lang = signal<Lang>(this.initialLang());

  readonly lang = this._lang.asReadonly();
  /** Current dictionary as a computed for cheap template reads. */
  readonly dict = computed(() => DICTIONARIES[this._lang()]);

  private initialLang(): Lang {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored === 'en' ? 'en' : 'es';
  }

  setLang(lang: Lang): void {
    this._lang.set(lang);
    localStorage.setItem(STORAGE_KEY, lang);
    document.documentElement.lang = lang;
  }

  toggle(): void {
    this.setLang(this._lang() === 'es' ? 'en' : 'es');
  }

  /**
   * Translate a key, falling back to ES then the key itself.
   * `params` interpolate `{name}` placeholders.
   */
  translate(key: string, params?: Record<string, string | number>): string {
    const value = this.dict()[key] ?? ES[key] ?? key;
    if (!params) return value;
    return value.replace(/\{(\w+)\}/g, (_, p) =>
      params[p] !== undefined ? String(params[p]) : `{${p}}`,
    );
  }
}
