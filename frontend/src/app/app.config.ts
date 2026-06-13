import {
  ApplicationConfig,
  inject,
  provideAppInitializer,
  provideBrowserGlobalErrorListeners,
} from '@angular/core';
import { provideHttpClient, withFetch, withInterceptors } from '@angular/common/http';
import { provideRouter, withComponentInputBinding } from '@angular/router';

import { firstValueFrom } from 'rxjs';

import { routes } from './app.routes';
import { authInterceptor } from './core/auth.interceptor';
import { AuthService } from './core/auth.service';
import { SettingsService } from './core/settings.service';
import { I18nService } from './core/i18n.service';

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(routes, withComponentInputBinding()),
    provideHttpClient(withFetch(), withInterceptors([authInterceptor])),
    provideAppInitializer(() => {
      const i18n = inject(I18nService);
      document.documentElement.lang = i18n.lang();
    }),
    provideAppInitializer(() => inject(AuthService).initSession()),
    provideAppInitializer(() => {
      const settings = inject(SettingsService);
      return firstValueFrom(settings.load())
        .then((s) => settings.applyBranding(s))
        .catch(() => undefined);
    }),
  ],
};
