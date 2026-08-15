import { Head, Link, useForm } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { ScoreRing } from '../Components/ui';

export default function Defense({ submission, session, questions }) {
    const evaluated = session.status === 'evaluated';

    const initial = {};
    questions.forEach((q) => { initial[q.id] = ''; });
    const { data, setData, post, processing, errors } = useForm({ answers: initial });

    function submit(e) {
        e.preventDefault();
        post(`/defense/${submission.id}`);
    }

    return (
        <>
            <Head title="Sesi Pembelaan Project" />
            <section className="max-w-3xl mx-auto px-6 py-10">
                <Link href={`/submission/${submission.id}`} className="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="19" x2="5" y1="12" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
                    Kembali ke Hasil Audit
                </Link>

                <div className="flex items-start gap-4 animate-fade-up">
                    <div className="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0" style={{ background: 'var(--accent-50)', color: 'var(--accent)' }}>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 8V4H8" /><rect width="16" height="12" x="4" y="8" rx="2" /><path d="M2 14h2" /><path d="M20 14h2" /><path d="M15 13v2" /><path d="M9 13v2" /></svg>
                    </div>
                    <div>
                        <p className="text-sm text-[var(--muted)]">Agent Defense &middot; {submission.task_title}</p>
                        <h1 className="text-2xl md:text-3xl font-bold tracking-tight mt-1">Sesi Pembelaan Project</h1>
                        <p className="mt-2 text-sm text-[var(--muted)] leading-relaxed max-w-[60ch]">
                            Jawab pertanyaan berikut dengan kata-katamu sendiri. Ini bukan ujian hafalan — tujuannya memverifikasi kamu
                            benar-benar memahami keputusan di project yang kamu submit sendiri, bukan sekadar hasil generate tanpa dipahami.
                        </p>
                    </div>
                </div>

                {Object.keys(errors).length > 0 && (
                    <div className="mt-6 p-4 rounded-2xl bg-[var(--danger-50)] border border-[#f3d6d2] text-[var(--danger)] text-sm">{Object.values(errors)[0]}</div>
                )}

                {evaluated ? (
                    <>
                        <div className="mt-8 surface spot-card p-8 flex items-center gap-6">
                            <ScoreRing score={session.comprehension_score} size={96} stroke={8} />
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Pemahaman</p>
                                <p className="mt-1 text-sm text-[var(--muted)] leading-relaxed">{session.feedback}</p>
                                {session.ai_assisted
                                    ? <span className="badge badge-accent mt-2">Dinilai oleh Groq AI</span>
                                    : <span className="badge badge-warning mt-2">Dinilai mode heuristik — sambungkan Groq API untuk penilaian penuh</span>}
                            </div>
                        </div>

                        <div className="mt-8 space-y-4">
                            {questions.map((q) => (
                                <div key={q.id} className="surface p-6">
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-1">Pertanyaan</p>
                                    <p className="font-semibold text-[var(--ink)]">{q.question}</p>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mt-4 mb-1">Jawabanmu</p>
                                    <p className="text-sm text-[var(--muted)] leading-relaxed">{q.answer !== '' && q.answer !== null ? q.answer : '(tidak dijawab)'}</p>
                                    <div className="mt-4 pt-4 border-t border-[var(--border-light)] flex items-start gap-3">
                                        <span className={`badge ${q.answer_score >= 70 ? 'badge-success' : q.answer_score >= 40 ? 'badge-warning' : 'badge-critical'} shrink-0`}>{q.answer_score}/100</span>
                                        <p className="text-sm text-[var(--muted)]">{q.answer_feedback}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </>
                ) : (
                    <form onSubmit={submit} className="mt-8 space-y-5">
                        {questions.map((q, i) => (
                            <div key={q.id} className="surface p-6">
                                <div className="flex items-center gap-2 mb-3">
                                    <span className="w-6 h-6 rounded-full grid place-items-center text-xs font-bold text-white shrink-0" style={{ background: 'var(--gradient-accent)' }}>{i + 1}</span>
                                    <p className="font-semibold text-[var(--ink)]">{q.question}</p>
                                </div>
                                <textarea
                                    value={data.answers[q.id] || ''}
                                    onChange={(e) => setData('answers', { ...data.answers, [q.id]: e.target.value })}
                                    rows="3"
                                    placeholder="Tulis jawabanmu di sini..."
                                ></textarea>
                            </div>
                        ))}

                        <button type="submit" disabled={processing} className="btn btn-primary btn-lg w-full sm:w-auto">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="m22 2-7 20-4-9-9-4Z" /><path d="M22 2 11 13" /></svg>
                            Submit Jawaban
                        </button>
                    </form>
                )}
            </section>
        </>
    );
}

Defense.layout = (page) => <Layout>{page}</Layout>;
