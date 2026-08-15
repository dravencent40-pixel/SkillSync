export function ScoreRing({ score = 0, size = 140, stroke = 10, label = null, sub = null }) {
    const r = (size - stroke) / 2;
    const c = 2 * Math.PI * r;
    const clamped = Math.min(Math.max(Number(score) || 0, 0), 100);
    const offset = c - (clamped / 100) * c;
    const color = clamped >= 80 ? '#167a56' : clamped >= 60 ? '#b45309' : '#c1382f';

    return (
        <div className="score-ring" style={{ position: 'relative', width: size, height: size, flexShrink: 0 }}>
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="#e7e5dd" strokeWidth={stroke} />
                <circle
                    className="progress"
                    cx={size / 2} cy={size / 2} r={r} fill="none"
                    stroke={color} strokeWidth={stroke} strokeLinecap="round"
                    strokeDasharray={c} strokeDashoffset={offset}
                    transform={`rotate(-90 ${size / 2} ${size / 2})`}
                />
            </svg>
            <div style={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                <span className="ring-score" style={{ fontSize: size / 4.6 }}>{score}</span>
                {label && <span className="ring-label">{label}</span>}
                {sub && <span className="ring-label mt-1">{sub}</span>}
            </div>
        </div>
    );
}

export function ScoreBar({ score = 0, max = 100 }) {
    const clamped = Math.min(Math.max(Number(score) || 0, 0), max);
    const pct = (clamped / max) * 100;
    const color = pct >= 80 ? '#167a56' : pct >= 60 ? '#b45309' : '#c1382f';
    return (
        <div className="mini-bar">
            <span style={{ width: `${pct}%`, background: color }}></span>
        </div>
    );
}

export function Badge({ children, tone = 'accent' }) {
    return <span className={`badge badge-${tone}`}>{children}</span>;
}

export function ScoreBadge({ score }) {
    const n = Number(score) || 0;
    return <Badge tone={n >= 80 ? 'accent' : n >= 60 ? 'warning' : 'critical'}>{n}</Badge>;
}

export function Avatar({ name, initial, size = 'md' }) {
    const initials = initial || (name ? name.split(' ').slice(0, 2).map((p) => p[0]).join('').toUpperCase() : '?');
    return <div className={`avatar avatar-${size}`}>{initials}</div>;
}

export function StatCard({ label, value, note = null }) {
    return (
        <div className="stat-card">
            <p className="stat-label">{label}</p>
            <p className="stat-num mt-1.5">{value}</p>
            {note && <p className="mt-1 text-xs text-[var(--muted)]">{note}</p>}
        </div>
    );
}

export function SectionHeader({ eyebrow, title, lede = null, center = false }) {
    return (
        <div className={center ? 'max-w-lg mx-auto mb-10 text-center' : 'mb-8'}>
            {eyebrow && <span className="eyebrow mb-3">{eyebrow}</span>}
            <h2 className="text-2xl md:text-3xl font-bold tracking-tight">{title}</h2>
            {lede && <p className="mt-3 text-[var(--muted)] leading-relaxed">{lede}</p>}
        </div>
    );
}

export function EmptyState({ icon = null, title, desc = null, action = null }) {
    return (
        <div className="empty-state">
            <div className="empty-state-icon">
                {icon
                    ? <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d={icon} /></svg>
                    : <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg>}
            </div>
            <p className="empty-state-title">{title}</p>
            {desc && <p className="empty-state-desc">{desc}</p>}
            {action && <div className="mt-3">{action}</div>}
        </div>
    );
}

export function formatDate(value) {
    if (!value) return '—';
    const d = new Date(String(value).includes('T') ? value : value.replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) return value;
    return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function difficultyTone(difficulty) {
    return difficulty === 'pemula' ? 'accent' : difficulty === 'menengah' ? 'warning' : 'critical';
}

export function ActivityTimeline({ activity }) {
    if (!activity?.length) {
        return (
            <div className="surface p-10">
                <div className="empty-state">
                    <div className="empty-state-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg>
                    </div>
                    <p className="empty-state-title">Belum ada aktivitas agent</p>
                    <p className="empty-state-desc">Riwayat kerja Task Issuer, Reviewer, Mentor, dan Profile Generator akan muncul di sini.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="surface p-6">
            <div className="space-y-5">
                {activity.map((a, i) => (
                    <div key={i} className="flex gap-4" style={i < activity.length - 1 ? { paddingBottom: '1.25rem', borderBottom: '1px solid var(--border-light)' } : undefined}>
                        <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-[var(--paper-soft)]">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" dangerouslySetInnerHTML={{ __html: a.icon ?? '<circle cx="12" cy="12" r="10"/>' }} />
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-[var(--ink)]">{a.label}</p>
                            {a.meta && <p className="text-xs text-[var(--muted)] mt-0.5">{a.meta}</p>}
                            <p className="text-[11px] text-[var(--muted-light)] mt-1">{a.time_ago}</p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
