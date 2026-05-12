import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-skeleton',
  templateUrl: './skeleton.component.html',
  styleUrls: ['./skeleton.component.css']
})
export class SkeletonComponent {
  @Input() variant: 'home' | 'grid' | 'list' | 'detail' | 'table' = 'grid';
  @Input() rows = 4;

  get items(): number[] {
    return Array.from({ length: this.rows }, (_, index) => index);
  }
}
