import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { forkJoin, Subject, takeUntil } from 'rxjs';
import { CastMember, Movie, Rating } from '../../../core/models';
import { AuthService } from '../../../core/services/auth.service';
import { MovieService } from '../../../core/services/movie.service';

@Component({
  selector: 'app-movie-detail',
  templateUrl: './movie-detail.component.html',
  styleUrls: ['./movie-detail.component.css']
})
export class MovieDetailComponent implements OnInit, OnDestroy {
  movie: Movie | null = null;
  loading = true;
  userRating: Rating | null = null;
  ratingScore = 0;
  ratingReview = '';
  ratingMsg = '';
  inWatchlist = false;
  isFavorite = false;
  membershipLoading = false;
  trailerKey = '';
  showTrailer = false;
  private movieId = 0;
  private destroy$ = new Subject<void>();

  constructor(
    private route: ActivatedRoute,
    public movieService: MovieService,
    public auth: AuthService,
    private translate: TranslateService
  ) {}

  ngOnInit(): void {
    this.route.params
      .pipe(takeUntil(this.destroy$))
      .subscribe(params => {
        this.movieId = +params['id'];
        this.loadMovie(this.movieId);
      });

    this.translate.onLangChange
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        if (this.movieId) this.loadMovie(this.movieId);
    });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadMovie(id: number): void {
    this.loading = true;
    this.inWatchlist = false;
    this.isFavorite = false;
    this.membershipLoading = this.auth.isLoggedIn;
    this.userRating = null;
    this.ratingScore = 0;
    this.ratingReview = '';

    this.movieService.getDetails(id).subscribe(movie => {
      this.movie = movie;
      this.loading = false;
      const trailer = movie.videos?.results?.find(video => video.type === 'Trailer' && video.site === 'YouTube')
        || movie.videos?.results?.find(video => video.site === 'YouTube');
      this.trailerKey = trailer?.key || '';
    });

    if (this.auth.isLoggedIn) {
      this.movieService.getMovieRating(id).subscribe(rating => {
        if (rating?.score) {
          this.userRating = rating;
          this.ratingScore = rating.score;
          this.ratingReview = rating.review || '';
        }
      });

      forkJoin({
        watchlist: this.movieService.getWatchlist(),
        favorites: this.movieService.getFavorites(),
      }).subscribe(({ watchlist, favorites }) => {
        this.inWatchlist = watchlist.some(item => Number(item.tmdb_id) === id);
        this.isFavorite = favorites.some(item => Number(item.tmdb_id) === id);
        this.membershipLoading = false;
      });
    }
  }

  submitRating(): void {
    if (!this.movie) return;
    this.movieService.upsertRating(this.movie.id, this.ratingScore, this.ratingReview).subscribe({
      next: () => {
        this.ratingMsg = 'Avaliação guardada!';
        setTimeout(() => this.ratingMsg = '', 3000);
      },
      error: () => {
        this.ratingMsg = 'Erro ao guardar.';
      }
    });
  }

  toggleWatchlist(): void {
    if (!this.movie) return;
    if (this.inWatchlist) {
      this.movieService.removeFromWatchlist(this.movie.id).subscribe(() => this.inWatchlist = false);
    } else {
      this.movieService.addToWatchlist(this.movie.id).subscribe(() => this.inWatchlist = true);
    }
  }

  toggleFavorite(): void {
    if (!this.movie) return;
    if (this.isFavorite) {
      this.movieService.removeFavorite(this.movie.id).subscribe(() => this.isFavorite = false);
    } else {
      this.movieService.toggleFavorite(this.movie.id).subscribe(res => {
        this.isFavorite = res.data?.action === 'added';
      });
    }
  }

  get director(): string {
    return this.movie?.credits?.crew?.find(crew => crew.job === 'Director')?.name || '';
  }

  get topCast(): CastMember[] {
    return this.movie?.credits?.cast?.slice(0, 8) || [];
  }

  get backdropUrl(): string {
    return this.movie?.backdrop_path
      ? this.movieService.posterUrl(this.movie.backdrop_path, 'original')
      : '';
  }

  get trailerBackgroundUrl(): string {
    if (!this.trailerKey) return '';

    const params = new URLSearchParams({
      autoplay: '1',
      mute: '1',
      controls: '0',
      loop: '1',
      playlist: this.trailerKey,
      playsinline: '1',
      rel: '0',
      modestbranding: '1',
      iv_load_policy: '3',
      disablekb: '1'
    });

    return `https://www.youtube.com/embed/${this.trailerKey}?${params.toString()}`;
  }

  trackByGenreId(_: number, genre: { id: number }): number {
    return genre.id;
  }

  trackByCastId(_: number, cast: CastMember): number {
    return cast.id;
  }

  trackByMovieId(_: number, movie: Movie): number {
    return movie.id;
  }
}
