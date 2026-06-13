import { Pipe, PipeTransform, inject } from '@angular/core';
import { I18nService } from './i18n.service';

/**
 * Translate pipe: `'reservar.title' | t` or `'x.y' | t:{ name: value }`.
 * Impure so it re-evaluates on every change-detection pass; combined with the
 * I18nService.lang signal read inside transform(), switching language updates
 * all translated bindings at runtime without a rebuild.
 */
@Pipe({ name: 't', pure: false })
export class TranslatePipe implements PipeTransform {
  private readonly i18n = inject(I18nService);

  transform(key: string, params?: Record<string, string | number>): string {
    // Touch the signal so change detection re-runs after switchLang.
    this.i18n.lang();
    return this.i18n.translate(key, params);
  }
}
