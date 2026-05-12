import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable, tap } from 'rxjs';
import { User, ApiResponse, PaginatedResponse } from '../models';
import { environment } from '../../../environments/environment';
import { AuthService } from './auth.service';

@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly API = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  getProfile(): Observable<User> {
    return this.http.get<ApiResponse<User>>(`${this.API}/user/profile`)
      .pipe(map(res => res.data));
  }

  updateProfile(fields: Partial<User>): Observable<ApiResponse<User>> {
    return this.http.put<ApiResponse<User>>(`${this.API}/user/profile`, fields)
      .pipe(tap(res => { if (res.data) this.auth.updateLocalUser(res.data); }));
  }

  updatePassword(current_password: string, new_password: string): Observable<ApiResponse<null>> {
    return this.http.put<ApiResponse<null>>(`${this.API}/user/password`, { current_password, new_password });
  }

  // Admin
  listUsers(page = 1): Observable<PaginatedResponse<User>> {
    return this.http.get<PaginatedResponse<User>>(`${this.API}/admin/users`, {
      params: { page: page.toString() }
    });
  }

  deleteUser(id: number): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.API}/admin/users/${id}`);
  }
}


// ============================================================
//  ExportService
// ============================================================

@Injectable({ providedIn: 'root' })
export class ExportService {
  private readonly API = environment.apiUrl;

  constructor(private http: HttpClient) {}

  private download(url: string, filename: string): void {
    this.http.get(url, { observe: 'response', responseType: 'blob' }).subscribe(response => {
      const blob = response.body;
      if (!blob) return;

      const downloadUrl = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.download = this.getFilename(response.headers.get('content-disposition')) || filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(downloadUrl);
    });
  }

  private getFilename(contentDisposition: string | null): string | null {
    if (!contentDisposition) return null;
    const match = contentDisposition.match(/filename="?([^"]+)"?/i);
    return match?.[1] ?? null;
  }

  watchlistCsv(): void  { this.download(`${this.API}/export/watchlist/csv`,  'watchlist.csv'); }
  ratingsCsv(): void    { this.download(`${this.API}/export/ratings/csv`,    'avaliacoes.csv'); }
  favoritesCsv(): void  { this.download(`${this.API}/export/favorites/csv`,  'favoritos.csv'); }
  ratingsPdf(): void    { this.download(`${this.API}/export/ratings/pdf`,    'avaliacoes.pdf'); }
}
