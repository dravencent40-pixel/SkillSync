import { Head, Link, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { ScoreRing } from '../Components/ui';

const severities = {
    critical: { tone: 'badge-critical', label: 'Kritis', icon: '<circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/>' },
    warning: { tone: 'badge-warning', label: 'Perhatian', icon: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>' },
    info: { tone: 'badge-accent', label: 'Info', icon: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>' },
};

export default function Submission({ submission, review, findings, rubric, isImageFile, defenseSession }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isCode = (submission.submission_type || 'code') === 'code';
    const processing = !review;

    return (
        <>
            <Head title="Hasil Audit" />
            <section className="max-w-4xl mx-auto px-6 py-10">
                <Link href={user.role === 'siswa' ? '/dashboard' : '/talent'} className="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="19" x2="5" y1="12" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
                    Kembali
                </Link>

                <div className="flex items-start justify-between gap-4 flex-wrap animate-fade-up">
                    <div>
                        <p className="text-sm text-[var(--muted)]">Hasil Audit &middot; {submission.student_name}</p>
                        <h1 className="text-2xl md:text-3xl font-bold tracking-tight mt-1">{submission.task_title}</h1>
                    </div>
                    {user.role === 'siswa' && (
                        <Link href={`/mentor?submission_id=${submission.id}`} className="btn btn-ghost">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg>
                            Tanya Agent Mentor
                        </Link>
                    )}
                </div>

                {defenseSession && user.role === 'siswa' && (
                    <div className="mt-6 surface p-5 rounded-2xl flex items-center justify-between gap-4 flex-wrap" style={{ borderColor: 'var(--accent-100)', background: 'var(--accent-50)' }}>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl grid place-items-center shrink-0" style={{ background: 'white', color: 'var(--accent)' }}>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 8V4H8" /><rect width="16" height="12" x="4" y="8" rx="2" /><path d="M2 14h2" /><path d="M20 14h2" /><path d="M15 13v2" /><path d="M9 13v2" /></svg>
                            </div>
                            <div>
                                <p className="font-semibold text-sm text-[var(--ink)]">Sesi Pembelaan Project</p>
                                <p className="text-xs text-[var(--muted)]">
                                    {defenseSession.status === 'evaluated'
                                        ? `Selesai · skor pemahaman ${defenseSession.comprehension_score}/100`
                                        : 'Wajib diselesaikan agar skor pemahamanmu ikut dihitung ke profil'}
                                </p>
                            </div>
                        </div>
                        <Link href={`/defense/${submission.id}`} className={`btn ${defenseSession.status === 'evaluated' ? 'btn-ghost' : 'btn-accent'} btn-sm`}>
                            {defenseSession.status === 'evaluated' ? 'Lihat Hasil' : 'Mulai Sesi'}
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="5" x2="19" y1="12" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
                        </Link>
                    </div>
                )}

                {processing ? (
                    <div className="mt-8 surface p-12">
                        <div className="empty-state">
                            <div className="empty-state-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="animate-spin"><circle cx="12" cy="12" r="10" strokeDasharray="31.4" strokeDashoffset="10" /></svg>
                            </div>
                            <p className="empty-state-title">Audit sedang diproses</p>
                            <p className="empty-state-desc">Coba muat ulang beberapa saat lagi.</p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div className="surface spot-card p-8 flex items-center gap-6 lg:col-span-1">
                                <ScoreRing score={review.overall_score} size={96} stroke={8} />
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Keseluruhan</p>
                                    <p className="mt-1 text-sm text-[var(--muted)] leading-relaxed">{review.summary}</p>
                                </div>
                            </div>
                            <div className="lg:col-span-2 surface p-8 grid grid-cols-3 gap-4">
                                {[
                                    { score: review.clean_code_score, label: rubric[0].label, icon: 'M16 18l6-6-6-6M8 6l-6 6 6 6' },
                                    { score: review.security_score, label: rubric[1].label, icon: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z' },
                                    { score: review.efficiency_score, label: rubric[2].label, icon: 'M13 2 3 14h9l-1 8 10-12h-9l1-8z' },
                                ].map((c, i) => (
                                    <div key={i} className="text-center p-4 rounded-2xl hover:bg-[var(--accent-50)] transition-colors">
                                        <div className="w-10 h-10 rounded-xl mx-auto mb-3 flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)]">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d={c.icon} /></svg>
                                        </div>
                                        <p className={`num text-2xl font-extrabold ${scoreColor(c.score)}`}>{c.score}</p>
                                        <p className="text-xs text-[var(--muted)] mt-1 font-medium">{c.label}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="mt-10">
                            <h2 className="text-lg font-bold mb-5 flex items-center gap-2">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" strokeWidth="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" /></svg>
                                Temuan Agent Auditor
                            </h2>
                            <div className="space-y-3">
                                {findings.map((f, i) => {
                                    const sev = severities[f.severity] || severities.info;
                                    const source = f.source || 'ai-judged';
                                    return (
                                        <div key={i} className="surface p-5 flex items-start gap-4 transition-all duration-200 hover:border-[var(--accent-200)] hover:shadow-md">
                                            <div className="shrink-0 mt-0.5">
                                                <span className={`${sev.tone} badge`}>
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" dangerouslySetInnerHTML={{ __html: sev.icon }} />
                                                    {sev.label}
                                                </span>
                                            </div>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <p className="font-semibold text-sm text-[var(--ink)]">{f.title}</p>
                                                    {source === 'static-verified'
                                                        ? <span className="badge badge-success" title="Ditemukan lewat pemeriksaan pola deterministik, bukan opini AI">✓ Verified</span>
                                                        : <span className="badge badge-accent" title="Penilaian kualitatif dari Groq AI">AI Judgment</span>}
                                                </div>
                                                <p className="mt-1.5 text-sm text-[var(--muted)] leading-relaxed">{f.detail}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="mt-10">
                            <h2 className="text-lg font-bold mb-5 flex items-center gap-2">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" strokeWidth="2"><polyline points="16 18 22 12 16 6" /><polyline points="8 6 2 12 8 18" /></svg>
                                {isCode ? 'Kode yang Dikirim' : 'Hasil Kerja yang Dikirim'}
                            </h2>

                            {submission.external_link && (
                                <a href={submission.external_link} target="_blank" rel="noopener" className="mb-4 flex items-center gap-3 p-4 rounded-2xl hover:shadow-md transition-all" style={{ background: 'var(--accent-50)', border: '1px solid var(--accent-100)' }}>
                                    <div className="w-9 h-9 rounded-lg grid place-items-center shrink-0" style={{ background: 'white', color: 'var(--accent)' }}>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" /><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" /></svg>
                                    </div>
                                    <span className="text-sm font-medium truncate" style={{ color: 'var(--accent-dark)' }}>{submission.external_link}</span>
                                </a>
                            )}

                            {submission.file_path && (
                                <div className="mb-4">
                                    {isImageFile ? (
                                        <a href={submission.file_path} target="_blank" rel="noopener">
                                            <img src={submission.file_path} alt="File submission" className="rounded-2xl border border-[var(--border-light)] max-h-[500px] w-auto" />
                                        </a>
                                    ) : (
                                        <a href={submission.file_path} target="_blank" rel="noopener" className="link-accent text-sm">Buka file lampiran &rarr;</a>
                                    )}
                                </div>
                            )}

                            {isCode ? (
                                <pre className="code-block overflow-x-auto p-4">{submission.code_content}</pre>
                            ) : (
                                <div className="surface p-6 rounded-2xl">
                                    <p className="text-sm text-[var(--ink-light)] leading-relaxed whitespace-pre-line">{submission.code_content}</p>
                                </div>
                            )}
                        </div>
                    </>
                )}
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

Submission.layout = (page) => <Layout>{page}</Layout>;
