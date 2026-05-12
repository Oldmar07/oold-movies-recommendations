import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { debounceTime, Subject, switchMap, takeUntil } from 'rxjs';
import { Genre, Movie } from '../../../core/models';
import { MovieService } from '../../../core/services/movie.service';

@Component({
  selector: 'app-movie-search',
  templateUrl: './movie-search.component.html',
  styleUrls: ['./movie-search.component.css']
})
export class MovieSearchComponent implements OnInit, OnDestroy {
  query = '';
  results: Movie[] = [];
  genres: Genre[] = [];
  loading = false;
  page = 1;
  totalPages = 1;
  private search$ = new Subject<string>();
  private destroy$ = new Subject<void>();

  constructor(
    private movieService: MovieService,
    private route: ActivatedRoute,
    private router: Router,
    private translate: TranslateService
  ) {}

  ngOnInit(): void {
    this.loadGenres();

    this.search$.pipe(
      debounceTime(400),
      switchMap(query => {
        this.loading = true;
        return this.movieService.search(query, this.page);
      }),
      takeUntil(this.destroy$)
    ).subscribe(res => {
      this.results = res.results || [];
      this.totalPages = res.total_pages || 1;
      this.loading = false;
    });

    this.route.queryParamMap
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
      const query = params.get('q') || '';
      if (query) {
        this.query = query;
        this.doSearch();
      }
    });

    this.translate.onLangChange
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.loadGenres();
        if (this.query.trim()) {
          this.searchNow();
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  doSearch(): void {
    if (!this.query.trim()) return;
    this.page = 1;
    this.search$.next(this.query);
    this.router.navigate([], { queryParams: { q: this.query }, queryParamsHandling: 'merge' });
  }

  goToGenre(id: number): void {
    this.router.navigate(['/movies/genre', id]);
  }

  trackByMovieId(_: number, movie: Movie): number {
    return movie.id;
  }

  trackByGenreId(_: number, genre: Genre): number {
    return genre.id;
  }

  prevPage(): void {
    if (this.page > 1) {
      this.page--;
      this.search$.next(this.query);
    }
  }

  nextPage(): void {
    if (this.page < this.totalPages) {
      this.page++;
      this.search$.next(this.query);
    }
  }

  private loadGenres(): void {
    this.movieService.getGenres()
      .pipe(takeUntil(this.destroy$))
      .subscribe(res => this.genres = res.genres || []);
  }

  private searchNow(): void {
    this.loading = true;
    this.movieService.search(this.query, this.page)
      .pipe(takeUntil(this.destroy$))
      .subscribe(res => {
        this.results = res.results || [];
        this.totalPages = res.total_pages || 1;
        this.loading = false;
      });
  }
}
