// ============================================================
//  AUTH MODULE
// ============================================================
import { Component, NgModule } from '@angular/core';
import { ActivatedRoute, Router, RouterModule, Routes } from '@angular/router';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { SharedModule } from '../../shared/shared.module';
import { AuthService } from '../../core/services/auth.service';

// ============================================================
//  LOGIN COMPONENT
// ============================================================
@Component({
  selector: 'app-login',
  templateUrl: './login/login.component.html',
})
export class LoginComponent {
  form: FormGroup;
  loading = false;
  error = '';

  constructor(private fb: FormBuilder, private auth: AuthService, private router: Router) {
    this.form = this.fb.group({
      email:    ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
    });
  }

  submit(): void {
    if (this.form.invalid) return;
    this.loading = true; this.error = '';
    const { email, password } = this.form.value;
    this.auth.login(email, password).subscribe({
      next: () => this.router.navigate(['/']),
      error: (err: any) => { this.error = err.error?.message || 'Erro ao entrar.'; this.loading = false; }
    });
  }
}


// ============================================================
//  REGISTER COMPONENT
// ============================================================
@Component({
  selector: 'app-register',
  templateUrl: './register/register.component.html',
})
export class RegisterComponent {
  form: FormGroup;
  loading = false;
  error = '';
  private readonly passwordPattern = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

  constructor(private fb: FormBuilder, private auth: AuthService, private router: Router) {
    this.form = this.fb.group({
      name:     ['', [Validators.required, Validators.minLength(2)]],
      email:    ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.pattern(this.passwordPattern)]],
      confirm:  ['', Validators.required],
    }, { validators: this.passwordMatch });
  }

  passwordMatch(g: FormGroup) {
    return g.get('password')?.value === g.get('confirm')?.value ? null : { mismatch: true };
  }

  submit(): void {
    this.error = '';
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.loading = true;
    const { name, email, password } = this.form.value;
    this.auth.register(name, email, password).subscribe({
      next: () => this.router.navigate(['/']),
      error: (err: any) => {
        const details = err.error?.errors ? Object.values(err.error.errors).join(' ') : '';
        this.error = details || err.error?.message || 'Erro ao registar.';
        this.loading = false;
      }
    });
  }
}


// ============================================================
//  FORGOT PASSWORD COMPONENT
// ============================================================
@Component({
  selector: 'app-forgot-password',
  templateUrl: './forgot-password/forgot-password.component.html',
})
export class ForgotPasswordComponent {
  form: FormGroup;
  loading = false;
  success = '';
  error = '';

  constructor(private fb: FormBuilder, private auth: AuthService) {
    this.form = this.fb.group({ email: ['', [Validators.required, Validators.email]] });
  }

  submit(): void {
    if (this.form.invalid) return;
    this.loading = true; this.error = '';
    this.auth.forgotPassword(this.form.value.email).subscribe({
      next: (res: any) => { this.success = res.message || 'Instruções enviadas!'; this.loading = false; },
      error: (err: any) => { this.error = err.error?.message || 'Erro.'; this.loading = false; }
    });
  }
}


// ============================================================
//  RESET PASSWORD COMPONENT
// ============================================================
@Component({
  selector: 'app-reset-password',
  templateUrl: './reset-password/reset-password.component.html',
})
export class ResetPasswordComponent {
  form: FormGroup;
  loading = false;
  success = '';
  error = '';
  token = '';

  constructor(private fb: FormBuilder, private auth: AuthService,
              private route: ActivatedRoute, private router: Router) {
    this.token = this.route.snapshot.queryParamMap.get('token') || '';
    this.form = this.fb.group({
      password: ['', [Validators.required, Validators.minLength(8)]],
      confirm:  ['', Validators.required],
    }, { validators: (g: any) => g.get('password')?.value === g.get('confirm')?.value ? null : { mismatch: true } });
  }

  submit(): void {
    if (this.form.invalid || !this.token) return;
    this.loading = true; this.error = '';
    this.auth.resetPassword(this.token, this.form.value.password).subscribe({
      next: () => { this.success = 'Senha alterada! Redireccionando...'; setTimeout(() => this.router.navigate(['/auth/login']), 2000); },
      error: (err: any) => { this.error = err.error?.message || 'Token inválido.'; this.loading = false; }
    });
  }
}

const routes: Routes = [
  { path: 'login',          component: LoginComponent },
  { path: 'register',       component: RegisterComponent },
  { path: 'forgot-password',component: ForgotPasswordComponent },
  { path: 'reset-password', component: ResetPasswordComponent  },
];

@NgModule({
  declarations: [
    LoginComponent,
    RegisterComponent,
    ForgotPasswordComponent,
    ResetPasswordComponent,
  ],
  imports: [SharedModule, RouterModule.forChild(routes)]
})
export class AuthModule {}
