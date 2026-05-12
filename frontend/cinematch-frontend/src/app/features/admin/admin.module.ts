// admin.module.ts
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from '../../shared/shared.module';
import { AdminGuard } from '../../core/guards/auth.guard';
import { AdminComponent } from './admin.component';

const routes: Routes = [
  { path: '', component: AdminComponent, canActivate: [AdminGuard] }
];

@NgModule({
  declarations: [AdminComponent],
  imports: [SharedModule, RouterModule.forChild(routes)]
})
export class AdminModule {}
