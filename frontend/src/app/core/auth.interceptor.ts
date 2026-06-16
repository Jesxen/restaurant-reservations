import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { AuthService } from './auth.service';

/**
 * Attaches the Bearer token to API requests, and on a 401 (expired/revoked
 * token) clears the session and bounces the user to the login page.
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const token = auth.token;

  if (token) {
    req = req.clone({ setHeaders: { Authorization: `Bearer ${token}` } });
  }

  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      // Only react to auth failures on already-authenticated requests. Skip the
      // login/session-restore calls so a bad login doesn't trigger a redirect loop.
      const isAuthCall = req.url.endsWith('/login') || req.url.endsWith('/me');
      if (err.status === 401 && token && !isAuthCall) {
        auth.clearSession();
        router.navigate(['/login']);
      }
      return throwError(() => err);
    }),
  );
};
