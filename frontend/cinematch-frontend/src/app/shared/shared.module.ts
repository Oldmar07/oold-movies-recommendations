import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';

import { NavbarComponent }      from './components/navbar/navbar.component';
import { MovieCardComponent }   from './components/movie-card/movie-card.component';
import { SkeletonComponent }    from './components/skeleton/skeleton.component';
import { StarRatingComponent }  from './components/star-rating/star-rating.component';
import { SpinnerComponent }     from './components/spinner/spinner.component';
import { SafeUrlPipe } from './pipes/safe-url.pipe';

@NgModule({
  declarations: [
    NavbarComponent,
    MovieCardComponent,
    SkeletonComponent,
    StarRatingComponent,
    SpinnerComponent,
    SafeUrlPipe,
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule,
    ReactiveFormsModule,
    TranslateModule,
  ],
  exports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    TranslateModule,
    NavbarComponent,
    MovieCardComponent,
    SkeletonComponent,
    StarRatingComponent,
    SpinnerComponent,
    SafeUrlPipe,
  ]
})
export class SharedModule {}
