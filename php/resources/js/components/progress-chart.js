// Lijngrafiek van de voortgang (zelfde opbouw als ProgressChart in de Next.js-versie).
import Alpine from 'alpinejs';

const H = 210;
const M = { top: 16, right: 56, bottom: 30, left: 44 };
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

Alpine.data('progressChart', (data, id) => ({
    W: 560,
    active: null,
    points: data.points,
    ticks: data.ticks,

    init() {
        const observer = new ResizeObserver(([entry]) => {
            this.W = Math.max(260, Math.round(entry.contentRect.width));
        });
        observer.observe(this.$el);
    },

    geo() {
        const { W, points, ticks } = this;
        const yMin = ticks[0].value;
        const yMax = ticks[ticks.length - 1].value;
        const tMin = points[0].t;
        const tMax = points[points.length - 1].t;
        const plotW = W - M.left - M.right;
        const plotH = H - M.top - M.bottom;
        const x = (t) => (tMax === tMin ? M.left + plotW / 2 : M.left + ((t - tMin) / (tMax - tMin)) * plotW);
        const y = (v) => M.top + (1 - (v - yMin) / (yMax - yMin)) * plotH;
        return { x, y, tMin, tMax, baseline: M.top + plotH };
    },

    svg() {
        const { W, points, ticks, active } = this;
        const { x, y, tMin, tMax, baseline } = this.geo();
        const line = points.map((p, i) => `${i ? 'L' : 'M'}${x(p.t).toFixed(1)},${y(p.value).toFixed(1)}`).join(' ');
        const area = `${line} L${x(tMax).toFixed(1)},${baseline} L${x(tMin).toFixed(1)},${baseline} Z`;
        const last = points[points.length - 1];
        const xLabels = points.length > 2 ? [points[0], points[Math.floor(points.length / 2)], last] : points;
        const point = active !== null ? points[active] : null;
        let out = `<desc id="${id}-desc">${esc(data.desc)}</desc>`;
        for (const t of ticks) {
            out += `<g><line x1="${M.left}" x2="${W - M.right}" y1="${y(t.value)}" y2="${y(t.value)}" stroke="var(--color-line)" stroke-width="1"></line>`;
            out += `<text x="${M.left - 8}" y="${y(t.value)}" dy="0.32em" text-anchor="end" class="fill-muted text-[11px]">${esc(t.label)}</text></g>`;
        }
        xLabels.forEach((p, i) => {
            const anchor = xLabels.length > 1 && i === 0 ? 'start' : xLabels.length > 1 && i === xLabels.length - 1 ? 'end' : 'middle';
            out += `<text x="${x(p.t)}" y="${H - 8}" text-anchor="${anchor}" class="fill-muted text-[11px]">${esc(p.short)}</text>`;
        });
        if (points.length > 1) {
            out += `<path d="${area}" fill="var(--color-accent)" opacity="0.1"></path>`;
            out += `<path d="${line}" fill="none" stroke="var(--color-accent)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>`;
        }
        if (point) {
            out += `<line x1="${x(point.t)}" x2="${x(point.t)}" y1="${M.top}" y2="${baseline}" stroke="var(--color-ink)" stroke-opacity="0.35" stroke-width="1"></line>`;
        }
        points.forEach((p, i) => {
            out += `<circle cx="${x(p.t)}" cy="${y(p.value)}" r="${i === active ? 6 : 4}" fill="var(--color-accent)" stroke="#fff" stroke-width="2"></circle>`;
        });
        out += `<text x="${x(last.t) + 10}" y="${y(last.value)}" dy="0.32em" class="fill-ink text-[12px] font-semibold">${esc(last.label)}</text>`;
        return out;
    },

    move(e) {
        const rect = e.currentTarget.getBoundingClientRect();
        const { x } = this.geo();
        const svgX = ((e.clientX - rect.left) / rect.width) * this.W;
        let best = 0;
        this.points.forEach((p, i) => {
            if (Math.abs(x(p.t) - svgX) < Math.abs(x(this.points[best].t) - svgX)) best = i;
        });
        this.active = best;
    },

    key(e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            e.preventDefault();
            const step = e.key === 'ArrowRight' ? 1 : -1;
            this.active = Math.min(this.points.length - 1, Math.max(0, (this.active ?? this.points.length - 1) + step));
        }
    },

    tipLeft() {
        return Math.min(85, Math.max(15, (this.geo().x(this.points[this.active].t) / this.W) * 100));
    },
}));
