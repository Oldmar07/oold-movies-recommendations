import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map, shareReplay } from 'rxjs/operators';
import { Movie, MoviePage, Genre, Rating, WatchlistItem, FavoriteItem, ApiResponse } from '../models';
import { environment } from '../../../environments/environment';
import { TranslateService } from '@ngx-translate/core';

@Injectable({ providedIn: 'root' })
export class MovieService {
  private readonly API = environment.apiUrl;
  private readonly cache = new Map<string, Observable<unknown>>();

  constructor(private http: HttpClient, private translate: TranslateService) {}

  private get lang(): string {
    return this.translate.currentLang === 'pt' ? 'pt-BR' : 'en-US';
  }

  private params(extra: Record<string, any> = {}): HttpParams {
    let p = new HttpParams().set('lang', this.lang);
    Object.entries(extra).forEach(([k, v]) => p = p.set(k, v));
    return p;
  }

  private unwrap<T>() {
    return map((res: ApiResponse<T> | T) => {
      if (res && typeof res === 'object' && 'data' in res && 'success' in res) {
        return (res as ApiResponse<T>).data;
      }

      return res as T;
    });
  }

  private cachedGet<T>(url: string, params: HttpParams): Observable<T> {
    const key = `${url}?${params.toString()}`;
    const cached = this.cache.get(key) as Observable<T> | undefined;
    if (cached) return cached;

    const request$ = this.http.get<ApiResponse<T>>(url, { params }).pipe(
      this.unwrap<T>(),
      shareReplay({ bufferSize: 1, refCount: false })
    );
    this.cache.set(key, request$);
    return request$;
  }

  // ----------------------------------------------------------
  //  Descoberta
  // ----------------------------------------------------------

  getPopular(page = 1): Observable<MoviePage> {
    return this.cachedGet<MoviePage>(`${this.API}/movies/popular`, this.params({ page }));
  }

  getTopRated(page = 1): Observable<MoviePage> {
    return this.cachedGet<MoviePage>(`${this.API}/movies/top-rated`, this.params({ page }));
  }

  getNowPlaying(page = 1): Observable<MoviePage> {
    return this.cachedGet<MoviePage>(`${this.API}/movies/now-playing`, this.params({ page }));
  }

  getUpcoming(page = 1): Observable<MoviePage> {
    return this.cachedGet<MoviePage>(`${this.API}/movies/upcoming`, this.params({ page }));
  }

  search(query: string, page = 1): Observable<MoviePage> {
    return this.cachedGet<MoviePage>(`${this.API}/movies/search`, this.params({ q: query, page }));
  }

  getGenres(): Observable<{ genres: Genre[] }> {
    return this.cachedGet<{ genres: Genre[] }>(`${this.API}/movies/genres`, this.params());
  }

  getByGenre(genreId: number, page = 1): Observable<MoviePage> {
    return this.cachedGet<MoviePage>(`${this.API}/movies/by-genre/${genreId}`, this.params({ page }));
  }

  getDetails(id: number): Observable<Movie> {
    return this.http.get<ApiResponse<Movie>>(`${this.API}/movies/${id}`, { params: this.params() })
      .pipe(this.unwrap<Movie>());
  }

  getRecommendations(id: number, page = 1): Observable<MoviePage> {
    return this.http.get<ApiResponse<MoviePage>>(`${this.API}/movies/${id}/recommendations`, { params: this.params({ page }) })
      .pipe(this.unwrap<MoviePage>());
  }

  getForYou(): Observable<{ results: Movie[] }> {
    return this.http.get<ApiResponse<{ results: Movie[] }>>(`${this.API}/movies/for-you`, { params: this.params() })
      .pipe(this.unwrap<{ results: Movie[] }>());
  }

  // ----------------------------------------------------------
  //  Ratings
  // ----------------------------------------------------------

  getMyRatings(): Observable<Rating[]> {
    return this.http.get<ApiResponse<Rating[]>>(`${this.API}/user/ratings`)
      .pipe(this.unwrap<Rating[]>());
  }

  getMovieRating(tmdbId: number): Observable<Rating> {
    return this.http.get<ApiResponse<Rating>>(`${this.API}/movies/${tmdbId}/rating`)
      .pipe(this.unwrap<Rating>());
  }

  upsertRating(tmdbId: number, score: number, review?: string): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(`${this.API}/movies/${tmdbId}/rating`, { score, review });
  }

  deleteRating(tmdbId: number): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.API}/movies/${tmdbId}/rating`);
  }

  // ----------------------------------------------------------
  //  Watchlist
  // ----------------------------------------------------------

  getWatchlist(watched?: boolean): Observable<WatchlistItem[]> {
    let params = new HttpParams();
    if (watched !== undefined) params = params.set('watched', watched ? '1' : '0');
    return this.http.get<ApiResponse<WatchlistItem[]>>(`${this.API}/user/watchlist`, { params })
      .pipe(this.unwrap<WatchlistItem[]>());
  }

  addToWatchlist(tmdbId: number): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(`${this.API}/movies/${tmdbId}/watchlist`, {});
  }

  removeFromWatchlist(tmdbId: number): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.API}/movies/${tmdbId}/watchlist`);
  }

  markWatched(tmdbId: number): Observable<ApiResponse<null>> {
    return this.http.put<ApiResponse<null>>(`${this.API}/movies/${tmdbId}/watchlist/watched`, {});
  }

  // ----------------------------------------------------------
  //  Favorites
  // ----------------------------------------------------------

  getFavorites(): Observable<FavoriteItem[]> {
    return this.http.get<ApiResponse<FavoriteItem[]>>(`${this.API}/user/favorites`)
      .pipe(this.unwrap<FavoriteItem[]>());
  }

  toggleFavorite(tmdbId: number): Observable<ApiResponse<{ action: string }>> {
    return this.http.post<ApiResponse<{ action: string }>>(`${this.API}/movies/${tmdbId}/favorite`, {});
  }

  removeFavorite(tmdbId: number): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.API}/movies/${tmdbId}/favorite`);
  }

  // ----------------------------------------------------------
  //  Imagem
  // ----------------------------------------------------------

  posterUrl(path: string | null | undefined, size = 'w500'): string {
    if (!path) return 'assets/no-poster.png';
    return `https://image.tmdb.org/t/p/${size}${path}`;
  }
}
