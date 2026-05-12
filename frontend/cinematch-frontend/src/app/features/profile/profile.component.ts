import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { TranslateService } from '@ngx-translate/core';
import { User } from '../../core/models';
import { AuthService } from '../../core/services/auth.service';
import { ThemeService } from '../../core/services/theme.service';
import { ExportService, UserService } from '../../core/services/user.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css']
})
export class ProfileComponent implements OnInit {
  user: User | null = null;
  profileForm!: FormGroup;
  passwordForm!: FormGroup;
  profileMsg = '';
  passwordMsg = '';
  passwordErr = '';
  tab: 'profile' | 'password' | 'export' = 'profile';

  constructor(
    private fb: FormBuilder,
    private userService: UserService,
    public exportService: ExportService,
    public auth: AuthService,
    public theme: ThemeService,
    public translate: TranslateService
  ) {}

  ngOnInit(): void {
    this.user = this.auth.currentUser;
    this.profileForm = this.fb.group({
      name: [this.user?.name || '', [Validators.required, Validators.minLength(2)]],
      preferred_lang: [this.user?.preferred_lang || 'pt'],
      theme: [this.user?.theme || 'light'],
    });
    this.passwordForm = this.fb.group({
      current_password: ['', Validators.required],
      new_password: ['', [Validators.required, Validators.minLength(8)]],
    });
  }

  saveProfile(): void {
    if (this.profileForm.invalid) return;
    const value = this.profileForm.value as Partial<User>;
    this.userService.updateProfile(value).subscribe({
      next: () => {
        this.profileMsg = 'Perfil actualizado!';
        if (value.theme) this.theme.setTheme(value.theme);
        if (value.preferred_lang) this.translate.use(value.preferred_lang);
        setTimeout(() => this.profileMsg = '', 3000);
      },
      error: () => this.profileMsg = 'Erro ao actualizar.'
    });
  }

  savePassword(): void {
    if (this.passwordForm.invalid) return;
    const { current_password, new_password } = this.passwordForm.value;
    this.userService.updatePassword(current_password, new_password).subscribe({
      next: () => {
        this.passwordMsg = 'Senha alterada!';
        this.passwordForm.reset();
        setTimeout(() => this.passwordMsg = '', 3000);
      },
      error: err => {
        this.passwordErr = err.error?.message || 'Erro.';
      }
    });
  }
}
