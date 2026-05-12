import { Component, OnInit } from '@angular/core';
import { Rating } from '../../../core/models';
import { MovieService } from '../../../core/services/movie.service';

@Component({
  selector: 'app-ratings',
  templateUrl: './ratings.component.html',
  styleUrls: ['../list-shared.css']
})
export class RatingsComponent implements OnInit {
  items: Rating[] = [];
  loading = true;

  constructor(private movieService: MovieService) {}

  ngOnInit(): void {
    this.movieService.getMyRatings().subscribe(res => {
      this.items = res;
      this.loading = false;
    });
  }

  delete(tmdbId: number): void {
    if (!confirm('Remover avaliação?')) return;
    this.movieService.deleteRating(tmdbId).subscribe(() => {
      this.items = this.items.filter(item => item.tmdb_id !== tmdbId);
    });
  }

  posterUrl(path: string | undefined): string {
    return path ? `https://image.tmdb.org/t/p/w185${path}` : 'assets/no-poster.png';
  }
}
