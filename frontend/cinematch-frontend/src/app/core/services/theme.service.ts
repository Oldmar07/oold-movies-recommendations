import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ThemeService {
  private _isDark = new BehaviorSubject<boolean>(false);
  isDark$: Observable<boolean> = this._isDark.asObservable();

  constructor() {
    const savedTheme = localStorage.getItem('cinematch_theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    const isDark = savedTheme ? savedTheme === 'dark' : prefersDark.matches;

    this.applyTheme(isDark);

    prefersDark.addEventListener('change', (mediaQuery) => {
      if (!localStorage.getItem('cinematch_theme')) {
        this.applyTheme(mediaQuery.matches);
      }
    });
  }

  toggleTheme(): void {
    this.applyTheme(!this._isDark.value);
  }

  toggle(): void {
    this.toggleTheme();
  }

  setTheme(theme: 'light' | 'dark'): void {
    this.applyTheme(theme === 'dark');
  }

  private applyTheme(isDark: boolean): void {
    this._isDark.next(isDark);
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    localStorage.setItem('cinematch_theme', isDark ? 'dark' : 'light');
  }
}
