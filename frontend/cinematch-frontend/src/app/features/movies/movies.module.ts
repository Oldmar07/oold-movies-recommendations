import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../shared/shared.module';
import { MovieByGenreComponent } from './by-genre/movie-by-genre.component';
import { MovieDetailComponent } from './detail/movie-detail.component';
import { MovieSearchComponent } from './search/movie-search.component';

const routes: Routes = [
  { path: 'search', component: MovieSearchComponent },
  { path: 'genre/:id', component: MovieByGenreComponent },
  { path: ':id', component: MovieDetailComponent },
];

@NgModule({
  declarations: [MovieDetailComponent, MovieSearchComponent, MovieByGenreComponent],
  imports: [SharedModule, RouterModule.forChild(routes)]
})
export class MoviesModule {}
