import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  const auth = inject(AuthService);

  if (auth.isLoggedIn) {
    return true;
  }

  router.navigate(['/auth/login'], { queryParams: { returnUrl: state.url } });
  return false;
};

export const AuthGuard = authGuard;

export const AdminGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  const auth = inject(AuthService);

  if (auth.isAdmin) {
    return true;
  }

  router.navigate(auth.isLoggedIn ? ['/'] : ['/auth/login'], {
    queryParams: auth.isLoggedIn ? undefined : { returnUrl: state.url }
  });
  return false;
};
