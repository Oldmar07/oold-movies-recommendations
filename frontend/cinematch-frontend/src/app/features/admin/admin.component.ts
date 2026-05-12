import { Component, OnInit } from '@angular/core';
import { User } from '../../core/models';
import { UserService } from '../../core/services/user.service';

@Component({
  selector: 'app-admin',
  templateUrl: './admin.component.html',
  styleUrls: ['./admin.component.css']
})
export class AdminComponent implements OnInit {
  users: User[] = [];
  total = 0;
  page = 1;
  lastPage = 1;
  loading = true;

  constructor(private userService: UserService) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.userService.listUsers(this.page).subscribe(res => {
      this.users = res.data;
      this.total = res.pagination.total;
      this.lastPage = res.pagination.last_page;
      this.loading = false;
    });
  }

  deleteUser(id: number): void {
    if (!confirm('Tens a certeza?')) return;
    this.userService.deleteUser(id).subscribe(() => this.load());
  }

  prevPage(): void {
    if (this.page > 1) {
      this.page--;
      this.load();
    }
  }

  nextPage(): void {
    if (this.page < this.lastPage) {
      this.page++;
      this.load();
    }
  }
}
