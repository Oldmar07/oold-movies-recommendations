import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from '../../core/guards/auth.guard';
import { SharedModule } from '../../shared/shared.module';
import { FavoritesComponent } from './favorites/favorites.component';
import { ProfileComponent } from './profile.component';
import { RatingsComponent } from './ratings/ratings.component';
import { WatchlistComponent } from './watchlist/watchlist.component';

const routes: Routes = [
  { path: '', component: ProfileComponent, canActivate: [AuthGuard] },
  { path: 'watchlist', component: WatchlistComponent, canActivate: [AuthGuard] },
  { path: 'favorites', component: FavoritesComponent, canActivate: [AuthGuard] },
  { path: 'ratings', component: RatingsComponent, canActivate: [AuthGuard] },
];

@NgModule({
  declarations: [ProfileComponent, WatchlistComponent, FavoritesComponent, RatingsComponent],
  imports: [SharedModule, RouterModule.forChild(routes)]
})
export class ProfileModule {}
