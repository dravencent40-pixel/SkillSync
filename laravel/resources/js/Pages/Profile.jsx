import { Head, Link, router, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { ScoreRing, formatDate } from '../Components/ui';

export default function Profile({ profile, tracks, history }) {
    const { auth } = usePage().props;
    const user = auth.user;

    function togglePublic() {
        router.post('/profile');
    }

    return (
        <>
            <Head title="Profil Skill" />
            <section className="max-w-4xl mx-auto px-6 py-10">
                <div className="flex flex-col md:flex-row md:items-start justify-between gap-6 animate-fade-up">
                    <div className="flex items-center gap-5">
                        <div className="avatar avatar-xl text-white" style={{ background: 'var(--gradient-accent)', boxShadow: 'var(--shadow-accent)' }}>
                            {user.avatar_initial || user.name.slice(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <h1 className="text-2xl md:text-3xl font-bold tracking-tight">{user.name}</h1>
                            <p className="text-sm text-[var(--muted)] mt-1">{profile.jurusan || '-'} &middot; {profile.sekolah || 'SMKN 9 Bekasi'}</p>
                            <div className="mt-2">
                                <span className="badge badge-accent">{profile.is_public ? '● Terlihat oleh mitra' : '○ Tersembunyi dari mitra'}</span>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3 shrink-0">
                        <Link href="/upload-cv" className="btn btn-ghost btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" x2="12" y1="3" y2="15" /></svg>
                            Unggah CV
                        </Link>
                        <button onClick={togglePublic} className={`btn ${profile.is_public ? 'btn-primary' : 'btn-ghost'} btn-sm`}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
                            {profile.is_public ? 'Publik' : 'Privat'}
                        </button>
                    </div>
                </div>

                <div className="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="surface spot-card p-8 flex items-center gap-6 lg:col-span-1">
                        <ScoreRing score={profile.overall_score || 0} size={96} stroke={8} />
                        <div className="min-w-0">
                            <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Badge</p>
                            <p className="mt-1 text-lg font-bold">{profile.badge}</p>
                        </div>
                    </div>

                    <div className="lg:col-span-2 surface p-8">
                        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-5">Skor Per Divisi</p>
                        {tracks.length === 0 ? (
                            <p className="text-sm text-[var(--muted)]">Belum ada studi kasus yang diselesaikan.</p>
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
                                        <p className="text-[11px] text-[var(--muted-light)] mt-3">{track.tasks_completed} studi kasus &middot; pemahaman rata-rata <span className="num">{track.comprehension_avg}</span>/100</p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {profile.strengths && (
                    <div className="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div className="surface p-6">
                            <div className="flex items-center gap-2 mb-3">
                                <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)]">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" /></svg>
                                </div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-[var(--ink)]">Kekuatan Utama</p>
                            </div>
                            <p className="font-semibold text-[var(--ink)]">{profile.strengths}</p>
                        </div>
                        <div className="surface p-6">
                            <div className="flex items-center gap-2 mb-3">
                                <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--warning-50)] text-[var(--warning)] border border-[#f6e3c3]">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" x2="12" y1="9" y2="13" /><line x1="12" x2="12.01" y1="17" y2="17" /></svg>
                                </div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-[var(--warning)]">Perlu Ditingkatkan</p>
                            </div>
                            <p className="font-semibold text-[var(--ink)]">{profile.weaknesses}</p>
                        </div>
                    </div>
                )}

                <div className="mt-12">
                    <h2 className="text-lg font-bold mb-5">Riwayat Penilaian</h2>
                    {history.length === 0 ? (
                        <div className="surface p-12">
                            <div className="empty-state">
                                <div className="empty-state-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg>
                                </div>
                                <p className="empty-state-title">Belum ada riwayat</p>
                                <p className="empty-state-desc">Mulai kerjakan studi kasus pertamamu.</p>
                            </div>
                        </div>
                    ) : (
                        <div className="surface overflow-hidden divide-y divide-[var(--border-light)]">
                            {history.map((h) => (
                                <Link key={h.id} href={`/submission/${h.id}`} className="flex items-center justify-between px-6 py-4 transition-colors hover:bg-[var(--paper-soft)] group">
                                    <div className="flex items-center gap-4">
                                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${h.overall_score !== null ? (h.overall_score >= 60 ? 'bg-[var(--accent-50)]' : 'bg-[var(--danger-50)]') : 'bg-[var(--paper-soft)]'}`}>
                                            {h.overall_score !== null
                                                ? <span className={`num text-sm font-bold ${scoreColor(h.overall_score)}`}>{h.overall_score}</span>
                                                : <div className="w-3 h-3 rounded-full bg-[var(--border-strong)] animate-pulse"></div>}
                                        </div>
                                        <div className="min-w-0">
                                            <p className="font-medium text-[var(--ink)] text-sm">{h.title}</p>
                                            <p className="text-xs text-[var(--muted-light)] mt-0.5">{formatDate(h.submitted_at)}</p>
                                        </div>
                                    </div>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted-light)" strokeWidth="2" className="opacity-0 group-hover:opacity-100 transition-opacity"><polyline points="9 18 15 12 9 6" /></svg>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </>
    );
}

function scoreColor(score) {
    const n = Number(score);
    if (n >= 85) return 'text-emerald-600';
    if (n >= 65) return 'text-amber-600';
    return 'text-rose-600';
}

Profile.layout = (page) => <Layout>{page}</Layout>;
