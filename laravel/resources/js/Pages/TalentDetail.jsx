import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { ScoreRing, formatDate } from '../Components/ui';

const STATUSES = [
    ['disimpan', 'Disimpan'],
    ['dihubungi', 'Dihubungi'],
    ['interview', 'Interview'],
    ['magang', 'Diterima Magang'],
];

export default function TalentDetail({ talent, tracks, recommendation, history }) {
    const { errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        status: recommendation?.status || 'disimpan',
        note: recommendation?.note || '',
    });

    function save(e) {
        e.preventDefault();
        post('/talent/' + talent.profile_id);
    }

    const cvSrc = talent.cv_path
        ? `/view-cv?file=${encodeURIComponent(talent.cv_path.split('/').pop())}`
        : null;

    return (
        <>
            <Head title={talent.name} />
            <section className="max-w-4xl mx-auto px-6 py-10">
                <Link href="/talent" className="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="19" x2="5" y1="12" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
                    Kembali ke Talent Pool
                </Link>

                <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 animate-fade-up">
                    <div className="flex items-center gap-5">
                        <div className="avatar avatar-xl text-white" style={{ background: 'var(--gradient-accent)', boxShadow: 'var(--shadow-accent)' }}>
                            {talent.avatar_initial || talent.name.slice(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <h1 className="text-2xl md:text-3xl font-bold tracking-tight">{talent.name}</h1>
                            <p className="text-sm text-[var(--muted)] mt-1">{talent.jurusan || '-'} &middot; {talent.sekolah || 'SMKN 9 Bekasi'}</p>
                        </div>
                    </div>
                    <span className="badge badge-accent shrink-0">{talent.badge}</span>
                </div>

                <div className="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6 stagger">
                    <div className="surface spot-card p-8 flex items-center gap-6 lg:col-span-1">
                        <ScoreRing score={talent.overall_score || 0} size={96} stroke={8} />
                        <div className="min-w-0">
                            <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Keseluruhan</p>
                            <p className="text-xs text-[var(--muted)] mt-1 leading-snug">{Number(talent.tasks_completed) || 0} studi kasus diselesaikan</p>
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
                                                    <div className="mini-bar"><span style={{ width: `${Math.max(2, Math.min(100, c.score))}%`, animationDelay: `${i * 0.08 + 0.1}s` }}></span></div>
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

                {talent.narrative && (
                    <div className="mt-8 surface p-8" style={{ background: 'var(--gradient-dark)', color: 'white', border: 'none' }}>
                        <div className="flex items-center gap-2 mb-3">
                            <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-white/10">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                            </div>
                            <p className="text-xs font-semibold uppercase tracking-wider text-[var(--accent-200)]">Ringkasan Agent Profile Generator</p>
                        </div>
                        <p className="text-sm text-[#e8e6dd] leading-relaxed whitespace-pre-line">{talent.narrative}</p>
                    </div>
                )}

                <div className="mt-8 surface p-8">
                    <div className="flex items-center gap-2 mb-4">
                        <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" /><polyline points="14 2 14 8 20 8" /></svg>
                        </div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Curriculum Vitae</p>
                    </div>
                    {cvSrc ? (
                        <>
                            <div className="flex items-center justify-between gap-4 flex-wrap mb-4">
                                <div>
                                    <p className="font-semibold text-sm text-[var(--ink)]">{talent.cv_original_name || 'cv.pdf'}</p>
                                    <p className="text-xs text-[var(--muted)]">Diunggah {formatDate(talent.cv_uploaded_at)}</p>
                                </div>
                                <a href={cvSrc} target="_blank" rel="noopener" className="btn btn-accent btn-sm">Buka di Tab Baru</a>
                            </div>
                            <iframe src={cvSrc} style={{ width: '100%', height: 500, border: 0 }} className="rounded-2xl border border-[var(--border-light)]" title="Pratinjau CV" />
                        </>
                    ) : (
                        <p className="text-sm text-[var(--muted)]">Talenta ini belum mengunggah CV.</p>
                    )}
                </div>

                <div className="mt-8 surface p-8">
                    <div className="flex items-center gap-2 mb-4">
                        <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                        </div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Status Rekrutmen</p>
                    </div>
                    {errors.form && <p className="mb-3 text-sm text-[var(--danger)]">{errors.form}</p>}
                    <form onSubmit={save} className="flex flex-col sm:flex-row gap-4">
                        <select value={data.status} onChange={(e) => setData('status', e.target.value)} className="flex-1">
                            {STATUSES.map(([val, label]) => (
                                <option key={val} value={val}>{label}</option>
                            ))}
                        </select>
                        <input
                            type="text"
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            placeholder="Catatan internal (opsional)"
                            className="flex-1"
                        />
                        <button type="submit" disabled={processing} className="btn btn-primary shrink-0">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" /><polyline points="17 21 17 13 7 13 7 21" /><polyline points="7 3 7 8 15 8" /></svg>
                            Simpan
                        </button>
                    </form>
                </div>

                <div className="mt-10">
                    <h2 className="text-lg font-bold mb-5">Riwayat Studi Kasus</h2>
                    {history.length === 0 ? (
                        <div className="surface p-12">
                            <div className="empty-state">
                                <div className="empty-state-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" /></svg>
                                </div>
                                <p className="empty-state-title">Belum ada riwayat</p>
                                <p className="empty-state-desc">Talenta ini belum menyelesaikan studi kasus.</p>
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

TalentDetail.layout = (page) => <Layout>{page}</Layout>;
