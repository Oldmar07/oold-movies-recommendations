// navbar.component.ts
import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Subject, takeUntil } from 'rxjs';
import { Genre, User } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { MovieService } from '../../../core/services/movie.service';
import { ThemeService } from '../../../core/services/theme.service';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.css']
})
export class NavbarComponent implements OnInit, OnDestroy {
  user: User | null = null;
  isDark = false;
  menuOpen = false;
  searchQuery = '';
  selectedGenre = '';
  genres: Genre[] = [];
  private destroy$ = new Subject<void>();

  constructor(
    public auth: AuthService,
    public theme: ThemeService,
    public translate: TranslateService,
    private movieService: MovieService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.auth.currentUser$
      .pipe(takeUntil(this.destroy$))
      .subscribe(u => this.user = u);
    this.theme.isDark$
      .pipe(takeUntil(this.destroy$))
      .subscribe(d => this.isDark = d);
    this.loadGenres();
    this.translate.onLangChange
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => this.loadGenres());
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  toggleTheme(): void { this.theme.toggle(); }

  switchLang(lang: string): void {
    localStorage.setItem('oold_lang', lang);
    this.translate.use(lang);
  }

  logout(): void { this.auth.logout(); }

  search(): void {
    if (this.searchQuery.trim()) {
      this.router.navigate(['/movies/search'], { queryParams: { q: this.searchQuery.trim() } });
      this.searchQuery = '';
      this.menuOpen = false;
    }
  }

  filterByGenre(): void {
    const genreId = Number(this.selectedGenre);
    if (!genreId) return;

    this.router.navigate(['/movies/genre', genreId]);
    this.selectedGenre = '';
    this.menuOpen = false;
  }

  trackByGenreId(_: number, genre: Genre): number {
    return genre.id;
  }

  private loadGenres(): void {
    this.movieService.getGenres()
      .pipe(takeUntil(this.destroy$))
      .subscribe(res => this.genres = res.genres || []);
  }
}
