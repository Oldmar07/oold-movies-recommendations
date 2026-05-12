import { Component, OnDestroy, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { catchError, forkJoin, of, Subject, takeUntil } from 'rxjs';
import { Movie } from '../../core/models';
import { AuthService } from '../../core/services/auth.service';
import { MovieService } from '../../core/services/movie.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit, OnDestroy {
  forYou: Movie[] = [];
  popular: Movie[] = [];
  topRated: Movie[] = [];
  nowPlaying: Movie[] = [];
  upcoming: Movie[] = [];
  loading = true;
  hero: Movie | null = null;
  error = '';
  private destroy$ = new Subject<void>();

  constructor(
    public movieService: MovieService,
    public auth: AuthService,
    private translate: TranslateService
  ) {}

  ngOnInit(): void {
    this.loadSections();
    this.translate.onLangChange
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => this.loadSections());
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadSections(): void {
    this.loading = true;
    this.error = '';

    forkJoin({
      nowPlaying: this.movieService.getNowPlaying().pipe(catchError(() => of(null))),
      popular: this.movieService.getPopular().pipe(catchError(() => of(null))),
      topRated: this.movieService.getTopRated().pipe(catchError(() => of(null))),
      upcoming: this.movieService.getUpcoming().pipe(catchError(() => of(null))),
      forYou: this.auth.isLoggedIn
        ? this.movieService.getForYou().pipe(catchError(() => of(null)))
        : of(null),
    }).subscribe(res => {
      this.nowPlaying = res.nowPlaying?.results?.slice(0, 10) || [];
      this.popular = res.popular?.results?.slice(0, 10) || [];
      this.topRated = res.topRated?.results?.slice(0, 10) || [];
      this.upcoming = res.upcoming?.results?.slice(0, 10) || [];
      this.forYou = res.forYou?.results?.slice(0, 10) || [];
      this.hero = this.nowPlaying[0] || this.popular[0] || null;
      this.loading = false;

      if (!this.nowPlaying.length && !this.popular.length && !this.topRated.length && !this.upcoming.length) {
        this.error = 'Não foi possível carregar os filmes. Confirma se o backend está ligado em http://localhost:8000.';
      }
    });
  }

  get heroBackdrop(): string {
    return this.hero?.backdrop_path
      ? this.movieService.posterUrl(this.hero.backdrop_path, 'original')
      : '';
  }

  trackByMovieId(_: number, movie: Movie): number {
    return movie.id;
  }
}
