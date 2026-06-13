import { Component, computed, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from './core/auth.service';
import { SettingsService, socialLinks } from './core/settings.service';
import { I18nService, Lang } from './core/i18n.service';
import { TranslatePipe } from './core/translate.pipe';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, TranslatePipe],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {
  protected readonly auth = inject(AuthService);
  private readonly settingsService = inject(SettingsService);
  protected readonly i18n = inject(I18nService);
  protected readonly settings = this.settingsService.settings;
  protected readonly redes = computed(() => socialLinks(this.settings()));
  protected readonly year = new Date().getFullYear();

  protected setLang(lang: Lang): void {
    this.i18n.setLang(lang);
  }

  protected logout(): void {
    this.auth.logout();
  }
}
