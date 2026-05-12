// movie-card.component.ts
import { Component, Input, Output, EventEmitter } from '@angular/core';
import { Movie } from '../../../core/models';
import { MovieService } from '../../../core/services/movie.service';
import { AuthService } from '../../../core/services/auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-movie-card',
  templateUrl: './movie-card.component.html',
  styleUrls: ['./movie-card.component.css']
})
export class MovieCardComponent {
  @Input() movie!: Movie;
  @Output() favoriteToggled = new EventEmitter<number>();

  constructor(
    public movieService: MovieService,
    public auth: AuthService,
    private router: Router
  ) {}

  goToDetail(): void {
    this.router.navigate(['/movies', this.movie.id]);
  }

  toggleFavorite(event: Event): void {
    event.stopPropagation();
    if (!this.auth.isLoggedIn) { this.router.navigate(['/auth/login']); return; }
    this.movieService.toggleFavorite(this.movie.id).subscribe();
    this.favoriteToggled.emit(this.movie.id);
  }

  addToWatchlist(event: Event): void {
    event.stopPropagation();
    if (!this.auth.isLoggedIn) { this.router.navigate(['/auth/login']); return; }
    this.movieService.addToWatchlist(this.movie.id).subscribe();
  }

  get year(): string {
    return this.movie.release_date ? this.movie.release_date.substring(0, 4) : '';
  }

  get rating(): string {
    const rating = Number(this.movie.vote_average);
    return Number.isFinite(rating) && rating > 0 ? rating.toFixed(1) : 'N/A';
  }
}
