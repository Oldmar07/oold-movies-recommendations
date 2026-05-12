import { Component, OnInit } from '@angular/core';
import { FavoriteItem } from '../../../core/models';
import { MovieService } from '../../../core/services/movie.service';

@Component({
  selector: 'app-favorites',
  templateUrl: './favorites.component.html',
  styleUrls: ['../list-shared.css']
})
export class FavoritesComponent implements OnInit {
  items: FavoriteItem[] = [];
  loading = true;

  constructor(private movieService: MovieService) {}

  ngOnInit(): void {
    this.movieService.getFavorites().subscribe(res => {
      this.items = res;
      this.loading = false;
    });
  }

  remove(tmdbId: number): void {
    this.movieService.removeFavorite(tmdbId).subscribe(() => {
      this.items = this.items.filter(item => item.tmdb_id !== tmdbId);
    });
  }

  posterUrl(path: string | undefined): string {
    return path ? `https://image.tmdb.org/t/p/w185${path}` : 'assets/no-poster.png';
  }

  title(item: FavoriteItem): string {
    return item.title || `Filme #${item.tmdb_id}`;
  }

  meta(item: FavoriteItem): string {
    const year = item.release_date?.slice(0, 4) || 'Ano desconhecido';
    const ratingValue = Number(item.vote_average);
    const rating = Number.isFinite(ratingValue)
      ? ratingValue.toFixed(1)
      : '-';

    return `${year} · ${rating}`;
  }
}
