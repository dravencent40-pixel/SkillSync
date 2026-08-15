import { Head, Link, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { ActivityTimeline, EmptyState, ScoreRing, difficultyTone, formatDate } from '../Components/ui';

export default function Dashboard(props) {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <>
            <Head title="Dashboard" />
            <section className="max-w-7xl mx-auto px-6 py-10">
                {props.siswa ? <SiswaDashboard {...props} user={user} /> : <MitraDashboard {...props} user={user} />}
            </section>
        </>
    );
}

function SiswaDashboard({ profile, tracks, recent, recommended, recommendReason, activity, user }) {
    return (
        <>
            <div className="welcome-banner animate-fade-up">
                <div className="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <p className="text-sm text-[#c9c8bd] font-medium">Selamat datang kembali,</p>
                        <h1 className="text-3xl md:text-4xl font-bold tracking-tight text-white mt-1">{user.name}</h1>
                        <p className="mt-2 text-sm text-[#a3a298] max-w-md">Terus asah kemampuanmu dengan mengerjakan studi kasus industri baru. Skor kompetensimu akan terus diperbarui oleh AI.</p>
                    </div>
                    <Link href="/tasks" className="btn btn-primary shrink-0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" x2="12" y1="5" y2="19" /><line x1="5" x2="19" y1="12" y2="12" /></svg>
                        Ambil Studi Kasus Baru
                    </Link>
                </div>
            </div>

            <div className="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="surface spot-card p-8 flex items-center gap-6 lg:col-span-1">
                    <ScoreRing score={profile.overall_score || 0} size={112} stroke={9} />
                    <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Keseluruhan</p>
                        <p className="mt-1 text-lg font-bold text-[var(--ink)]">{profile.badge}</p>
                        <p className="text-xs text-[var(--muted)] mt-1 leading-snug">{profile.tasks_completed || 0} studi kasus &middot; lintas semua divisi</p>
                    </div>
                </div>

                <div className="lg:col-span-2 surface p-8">
                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-5">Skor Per Divisi</p>
                    {tracks.length === 0 ? (
                        <div className="empty-state py-4">
                            <div className="empty-state-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 3v18h18" /><path d="m19 9-5 5-4-4-3 3" /></svg>
                            </div>
                            <p className="empty-state-title">Belum ada studi kasus selesai</p>
                            <p className="empty-state-desc">Kerjakan studi kasus pertamamu untuk melihat breakdown skor per divisi di sini.</p>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {tracks.map((track, i) => (
                                <div key={i}>
                                    <div className="flex items-center justify-between mb-4">
                                        <span className="badge badge-accent">{track.category_name}</span>
                                        <span className={`num text-sm font-bold ${scoreColor(track.overall_score)}`}>{track.overall_score}/100</span>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4">
                                        {track.rubric.map((c, j) => (
                                            <div key={j}>
                                                <div className="flex items-center justify-between mb-1.5">
                                                    <p className="text-[11px] text-[var(--muted)] leading-tight">{c.label}</p>
                                                    <span className={`num text-sm font-bold ${scoreColor(c.score)}`}>{c.score}</span>
                                                </div>
                                                <div className="mini-bar"><span style={{ width: `${Math.max(2, Math.min(100, c.score))}%`, animationDelay: `${i * 0.08 + j * 0.05}s` }}></span></div>
                                            </div>
                                        ))}
                                    </div>
                                    <p className="text-[11px] text-[var(--muted-light)] mt-3">{track.tasks_completed} studi kasus &middot; skor pemahaman rata-rata <span className="num">{track.comprehension_avg}</span>/100</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="mt-12">
                <div className="flex items-center justify-between mb-5">
                    <div>
                        <h2 className="text-lg font-bold text-[var(--ink)]">Direkomendasikan untukmu</h2>
                        <p className="text-xs text-[var(--muted)] mt-0.5">{recommendReason || 'Dipilih oleh Agent Task Issuer berdasarkan kelemahan skill kamu'}</p>
                    </div>
                    <Link href="/tasks" className="link-accent text-sm">Lihat semua</Link>
                </div>
                {recommended.length === 0 ? (
                    <div className="surface p-12">
                        <div className="empty-state">
                            <div className="empty-state-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" /><polyline points="14 2 14 8 20 8" /></svg>
                            </div>
                            <p className="empty-state-title">Semua studi kasus sudah dikerjakan</p>
                            <p className="empty-state-desc">Nantikan task baru dari mitra industri!</p>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                        {recommended.map((t) => (
                            <Link key={t.id} href={`/task/${t.id}`} className="surface surface-hover spot-card p-6 group">
                                <span className="badge badge-accent">{t.category_name}</span>
                                <h3 className="mt-4 font-semibold text-[var(--ink)] leading-snug">{t.title}</h3>
                                <p className="mt-2 text-xs text-[var(--muted)] capitalize flex items-center gap-2">
                                    <span className={`w-1.5 h-1.5 rounded-full ${t.difficulty === 'mahir' ? 'bg-[var(--danger)]' : t.difficulty === 'menengah' ? 'bg-[var(--warning)]' : 'bg-[var(--accent)]'}`}></span>
                                    {t.difficulty}
                                    <span className="text-[var(--border)]">&middot;</span>
                                    {t.industry_context}
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-12">
                <h2 className="text-lg font-bold text-[var(--ink)] mb-5">Riwayat Submission</h2>
                {recent.length === 0 ? (
                    <div className="surface p-12">
                        <div className="empty-state">
                            <div className="empty-state-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg>
                            </div>
                            <p className="empty-state-title">Belum ada submission</p>
                            <p className="empty-state-desc">Ambil studi kasus pertamamu sekarang.</p>
                        </div>
                    </div>
                ) : (
                    <div className="surface overflow-hidden divide-y divide-[var(--border-light)]">
                        {recent.map((r) => (
                            <Link key={r.id} href={`/submission/${r.id}`} className="flex items-center justify-between px-6 py-4 transition-colors hover:bg-[var(--paper-soft)] group">
                                <div className="flex items-center gap-4">
                                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${r.overall_score !== null ? (r.overall_score >= 60 ? 'bg-[var(--accent-50)]' : 'bg-[var(--danger-50)]') : 'bg-[var(--paper-soft)]'}`}>
                                        {r.overall_score !== null
                                            ? <span className={`num text-sm font-bold ${scoreColor(r.overall_score)}`}>{r.overall_score}</span>
                                            : <div className="w-3 h-3 rounded-full bg-[var(--border-strong)] animate-pulse"></div>}
                                    </div>
                                    <div className="min-w-0">
                                        <p className="font-medium text-[var(--ink)] text-sm">{r.title}</p>
                                        <p className="text-xs text-[var(--muted-light)] mt-0.5">{formatDate(r.submitted_at)}</p>
                                    </div>
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted-light)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="opacity-0 group-hover:opacity-100 transition-opacity"><polyline points="9 18 15 12 9 6" /></svg>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-12">
                <div className="flex items-center justify-between mb-5">
                    <div>
                        <h2 className="text-lg font-bold text-[var(--ink)]">Aktivitas Agent</h2>
                        <p className="text-xs text-[var(--muted)] mt-0.5">Jejak kerja Task Issuer, Reviewer &amp; Auditor, Mentor, dan Profile Generator untukmu</p>
                    </div>
                </div>
                <ActivityTimeline activity={activity} />
            </div>
        </>
    );
}

function MitraDashboard({ taskCount, submissionCount, topTalents, activity, user }) {
    return (
        <>
            <div className="welcome-banner animate-fade-up">
                <div className="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <p className="text-sm text-[#c9c8bd] font-medium">Dashboard Mitra</p>
                        <h1 className="text-3xl md:text-4xl font-bold tracking-tight text-white mt-1">{user.name}</h1>
                        <p className="mt-2 text-sm text-[#a3a298] max-w-md">Kelola studi kasus dan temukan talenta terbaik dari pool siswa SMK yang sudah terverifikasi kompetensinya.</p>
                    </div>
                    <Link href="/tasks" className="btn btn-primary shrink-0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" x2="12" y1="5" y2="19" /><line x1="5" x2="19" y1="12" y2="12" /></svg>
                        Terbitkan Studi Kasus
                    </Link>
                </div>
            </div>

            <div className="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                {[
                    { value: taskCount, label: 'Studi kasus aktif', icon: 'M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2zM14 2v6h6', tag: 'Aktif' },
                    { value: submissionCount, label: 'Total submission dinilai AI', icon: 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', tag: 'AI' },
                    { value: topTalents.length, label: 'Talenta dengan profil aktif', icon: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75', tag: 'Pool' },
                ].map((s, i) => (
                    <div key={i} className="stat-card group">
                        <div className="flex items-center justify-between mb-5">
                            <div className="w-11 h-11 rounded-xl flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)] transition-transform duration-300 group-hover:scale-110">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d={s.icon} /></svg>
                            </div>
                            <span className="badge badge-accent">{s.tag}</span>
                        </div>
                        <p className="stat-num text-[var(--ink)]">{s.value}</p>
                        <p className="stat-label mt-1.5">{s.label}</p>
                    </div>
                ))}
            </div>

            <div className="mt-12">
                <div className="flex items-center justify-between mb-5">
                    <h2 className="text-lg font-bold text-[var(--ink)]">Top Talent Pool</h2>
                    <Link href="/talent" className="link-accent text-sm">Lihat semua talenta</Link>
                </div>
                {topTalents.length === 0 ? (
                    <div className="surface p-12">
                        <div className="empty-state">
                            <div className="empty-state-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /></svg>
                            </div>
                            <p className="empty-state-title">Belum ada siswa</p>
                            <p className="empty-state-desc">Belum ada siswa yang menyelesaikan studi kasus.</p>
                        </div>
                    </div>
                ) : (
                    <div className="surface overflow-hidden divide-y divide-[var(--border-light)]">
                        {topTalents.map((t, i) => (
                            <Link key={t.id} href={`/talent/${t.profile_id}`} className="flex items-center justify-between px-6 py-4 transition-colors hover:bg-[var(--paper-soft)] group">
                                <div className="flex items-center gap-4">
                                    <div className="relative">
                                        <span className="avatar avatar-md">{initials(t.name)}</span>
                                        {i < 3 && (
                                            <span className={`absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold ${i === 0 ? 'text-white' : 'text-[var(--accent-dark)]'}`} style={{ background: i === 0 ? 'var(--gradient-accent)' : 'var(--accent-100)', border: '2px solid var(--surface)' }}>{i + 1}</span>
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <p className="font-medium text-[var(--ink)] text-sm">{t.name}</p>
                                        <p className="text-xs text-[var(--muted-light)]">{t.badge} &middot; <span className="num">{t.tasks_completed}</span> task</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className={`num font-bold ${scoreColor(t.overall_score)}`}>{t.overall_score}</span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted-light)" strokeWidth="2" className="opacity-0 group-hover:opacity-100 transition-opacity"><polyline points="9 18 15 12 9 6" /></svg>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-12">
                <div className="flex items-center justify-between mb-5">
                    <div>
                        <h2 className="text-lg font-bold text-[var(--ink)]">Aktivitas Agent</h2>
                        <p className="text-xs text-[var(--muted)] mt-0.5">Jejak aksi sistem multi-agent terkait akunmu</p>
                    </div>
                </div>
                <ActivityTimeline activity={activity} />
            </div>
        </>
    );
}

function scoreColor(score) {
    const n = Number(score);
    if (n >= 85) return 'text-emerald-600';
    if (n >= 65) return 'text-amber-600';
    return 'text-rose-600';
}

function initials(name) {
    if (!name) return '?';
    return name.split(' ').slice(0, 2).map((p) => p[0]).join('').toUpperCase();
}

Dashboard.layout = (page) => <Layout>{page}</Layout>;
