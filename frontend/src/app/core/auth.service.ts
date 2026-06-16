import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, firstValueFrom, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import {
  AuthResponse,
  Credentials,
  RegisterData,
  ResetPasswordPayload,
  User,
} from './user.model';

const TOKEN_KEY = 'rl_token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);
  private readonly api = environment.apiUrl;

  private readonly _user = signal<User | null>(null);
  private readonly _token = signal<string | null>(localStorage.getItem(TOKEN_KEY));

  readonly user = this._user.asReadonly();
  readonly isAuthenticated = computed(() => this._token() !== null);
  readonly isAdmin = computed(() => this._user()?.role === 'admin');
  readonly isStaff = computed(() => {
    const role = this._user()?.role;
    return role === 'staff' || role === 'admin';
  });

  get token(): string | null {
    return this._token();
  }

  /** Called once at startup (app initializer) to restore the session. */
  async initSession(): Promise<void> {
    if (!this._token()) {
      return;
    }
    try {
      const res = await firstValueFrom(this.http.get<{ data: User }>(`${this.api}/me`));
      this._user.set(res.data);
    } catch {
      this.clearSession();
    }
  }

  login(credentials: Credentials): Observable<AuthResponse> {
    return this.http
      .post<AuthResponse>(`${this.api}/login`, credentials)
      .pipe(tap((res) => this.setSession(res)));
  }

  register(data: RegisterData): Observable<AuthResponse> {
    return this.http
      .post<AuthResponse>(`${this.api}/register`, data)
      .pipe(tap((res) => this.setSession(res)));
  }

  /** Request a password reset email (POST /api/forgot-password). */
  forgotPassword(email: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.api}/forgot-password`, { email });
  }

  /** Set a new password from a reset token (POST /api/reset-password). */
  resetPassword(payload: ResetPasswordPayload): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.api}/reset-password`, payload);
  }

  /** Resend the email verification notification (auth required). */
  resendVerification(): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(
      `${this.api}/email/verification-notification`,
      {},
    );
  }

  logout(): void {
    // Clear local state first so protected views stop showing account data
    // immediately, then redirect away from any guarded page.
    const done = () => {
      this.clearSession();
      this.router.navigate(['/']);
    };
    this.http.post(`${this.api}/logout`, {}).subscribe({ next: done, error: done });
  }

  private setSession(res: AuthResponse): void {
    localStorage.setItem(TOKEN_KEY, res.token);
    this._token.set(res.token);
    this._user.set(res.user);
  }

  /** Clear local session state (used on logout and on a 401 from the API). */
  clearSession(): void {
    localStorage.removeItem(TOKEN_KEY);
    this._token.set(null);
    this._user.set(null);
  }
}
