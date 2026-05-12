// star-rating.component.ts
import { Component, Input, Output, EventEmitter } from '@angular/core';

@Component({
  selector: 'app-star-rating',
  template: `
    <div class="star-rating">
      <button
        *ngFor="let s of stars"
        class="star"
        [class.filled]="s <= (hovered || value)"
        (mouseenter)="hovered = s"
        (mouseleave)="hovered = 0"
        (click)="select(s)"
        [disabled]="readonly"
      >★</button>
      <span class="score-label" *ngIf="value">{{ value }}/10</span>
    </div>
  `,
  styles: [`
    .star-rating { display: flex; align-items: center; gap: 2px; }
    .star {
      font-size: 24px;
      color: var(--border);
      background: none;
      border: none;
      cursor: pointer;
      padding: 0 1px;
      transition: color 0.15s, transform 0.15s;
      line-height: 1;
    }
    .star.filled { color: #f59e0b; }
    .star:hover:not(:disabled) { transform: scale(1.2); }
    .star:disabled { cursor: default; }
    .score-label { font-size: 14px; font-weight: 700; color: var(--text-secondary); margin-left: 6px; }
  `]
})
export class StarRatingComponent {
  @Input() value = 0;
  @Input() max = 10;
  @Input() readonly = false;
  @Output() valueChange = new EventEmitter<number>();

  hovered = 0;
  stars = Array.from({ length: this.max }, (_, i) => i + 1);

  select(s: number): void {
    if (!this.readonly) {
      this.value = s;
      this.valueChange.emit(s);
    }
  }
}
