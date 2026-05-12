import { Component, OnInit } from '@angular/core';
import { WatchlistItem } from '../../../core/models';
import { MovieService } from '../../../core/services/movie.service';

@Component({
  selector: 'app-watchlist',
  templateUrl: './watchlist.component.html',
  styleUrls: ['./watchlist.component.css']
})
export class WatchlistComponent implements OnInit {
  items: WatchlistItem[] = [];
  filter: 'all' | 'watched' | 'toWatch' = 'all';
  loading = true;

  constructor(private movieService: MovieService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    const watched = this.filter === 'all' ? undefined : this.filter === 'watched';
    this.movieService.getWatchlist(watched).subscribe(res => {
      this.items = res;
      this.loading = false;
    });
  }

  setFilter(filter: 'all' | 'watched' | 'toWatch'): void {
    this.filter = filter;
    this.load();
  }

  markWatched(tmdbId: number): void {
    this.movieService.markWatched(tmdbId).subscribe(() => this.load());
  }

  remove(tmdbId: number): void {
    this.movieService.removeFromWatchlist(tmdbId).subscribe(() => {
      this.items = this.items.filter(item => item.tmdb_id !== tmdbId);
    });
  }

  posterUrl(path: string | undefined): string {
    return path ? `https://image.tmdb.org/t/p/w185${path}` : 'assets/no-poster.png';
  }

  title(item: WatchlistItem): string {
    return item.title || `Filme #${item.tmdb_id}`;
  }

  meta(item: WatchlistItem): string {
    const year = item.release_date?.slice(0, 4) || 'Ano desconhecido';
    const ratingValue = Number(item.vote_average);
    const rating = Number.isFinite(ratingValue)
      ? ratingValue.toFixed(1)
      : '-';

    return `${year} · ${rating}`;
  }
}
