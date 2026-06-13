import { Component, computed, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from './core/auth.service';
import { SettingsService, socialLinks } from './core/settings.service';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {
  protected readonly auth = inject(AuthService);
  private readonly settingsService = inject(SettingsService);
  protected readonly settings = this.settingsService.settings;
  protected readonly redes = computed(() => socialLinks(this.settings()));
  protected readonly year = new Date().getFullYear();

  protected logout(): void {
    this.auth.logout();
  }
}
