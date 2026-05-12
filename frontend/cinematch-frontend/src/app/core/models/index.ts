// ============================================================
//  Models / Interfaces
// ============================================================

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  avatar_url?: string;
  preferred_lang: 'pt' | 'en';
  theme: 'light' | 'dark';
  created_at: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  data: { user: User; token: string };
}

export interface Movie {
  id: number;
  title: string;
  original_title?: string;
  overview?: string;
  poster_path?: string;
  backdrop_path?: string;
  release_date?: string;
  genre_ids?: number[];
  genres?: Genre[];
  vote_average?: number;
  vote_count?: number;
  popularity?: number;
  runtime?: number;
  credits?: Credits;
  videos?: { results: Video[] };
  similar?: MoviePage;
  recommendations?: MoviePage;
  internal_avg_score?: number;
}

export interface MoviePage {
  page: number;
  results: Movie[];
  total_pages: number;
  total_results: number;
}

export interface Genre {
  id: number;
  name: string;
}

export interface Credits {
  cast: CastMember[];
  crew: CrewMember[];
}

export interface CastMember {
  id: number;
  name: string;
  character: string;
  profile_path?: string;
}

export interface CrewMember {
  id: number;
  name: string;
  job: string;
}

export interface Video {
  key: string;
  name: string;
  site: string;
  type: string;
}

export interface Rating {
  id?: number;
  user_id?: number;
  tmdb_id: number;
  score: number;
  review?: string;
  created_at?: string;
  release_date?: string;
  title?: string;
  poster_path?: string;
}

export interface WatchlistItem {
  id: number;
  tmdb_id: number;
  watched: boolean;
  added_at: string;
  watched_at?: string;
  title?: string;
  poster_path?: string;
  release_date?: string;
  vote_average?: number;
}

export interface FavoriteItem {
  id: number;
  tmdb_id: number;
  added_at: string;
  title?: string;
  poster_path?: string;
  release_date?: string;
  vote_average?: number;
}

export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
}
