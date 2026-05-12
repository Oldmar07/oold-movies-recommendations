import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { Subject, takeUntil } from 'rxjs';
import { Genre, Movie } from '../../../core/models';
import { MovieService } from '../../../core/services/movie.service';

@Component({
  selector: 'app-movie-by-genre',
  template: `
    <div class="pt-navbar container">
      <div class="section-header" style="padding-top:40px">
        <h2 class="section-title">{{ genreName }}</h2>
      </div>
      <app-skeleton *ngIf="loading" variant="grid" [rows]="10"></app-skeleton>
      <div class="movies-grid" *ngIf="!loading">
        <app-movie-card *ngFor="let movie of movies; trackBy: trackByMovieId" [movie]="movie"></app-movie-card>
      </div>
      <div class="pagination" *ngIf="totalPages > 1">
        <button class="btn btn-outline btn-sm" (click)="prevPage()" [disabled]="page === 1">
          {{ 'common.previous' | translate }}
        </button>
        <span>{{ 'common.page' | translate }} {{ page }} {{ 'common.of' | translate }} {{ totalPages }}</span>
        <button class="btn btn-outline btn-sm" (click)="nextPage()" [disabled]="page === totalPages">
          {{ 'common.next' | translate }}
        </button>
      </div>
    </div>
  `
})
export class MovieByGenreComponent implements OnInit, OnDestroy {
  movies: Movie[] = [];
  loading = true;
  page = 1;
  totalPages = 1;
  genreName = '';
  genreId = 0;
  genres: Genre[] = [];
  private destroy$ = new Subject<void>();

  constructor(
    private route: ActivatedRoute,
    private movieService: MovieService,
    private translate: TranslateService
  ) {}

  ngOnInit(): void {
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
      this.genreId = +params['id'];
      this.loadGenres();
      this.load();
    });

    this.translate.onLangChange
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.loadGenres();
        this.load();
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  load(): void {
    this.loading = true;
    this.movieService.getByGenre(this.genreId, this.page).subscribe(res => {
      this.movies = res.results || [];
      this.totalPages = res.total_pages || 1;
      this.loading = false;
    });
  }

  prevPage(): void {
    if (this.page > 1) {
      this.page--;
      this.load();
    }
  }

  nextPage(): void {
    if (this.page < this.totalPages) {
      this.page++;
      this.load();
    }
  }

  trackByMovieId(_: number, movie: Movie): number {
    return movie.id;
  }

  private loadGenres(): void {
    this.movieService.getGenres()
      .pipe(takeUntil(this.destroy$))
      .subscribe(res => {
        this.genres = res.genres || [];
        this.genreName = this.genres.find(genre => genre.id === this.genreId)?.name || this.genreName;
      });
  }
}
