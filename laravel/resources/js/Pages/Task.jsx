import { Head, Link, useForm, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Task({ task, typeConfig, mySubmission }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isCode = (task.submission_type || 'code') === 'code';

    const { data, setData, post, processing, errors } = useForm({
        code_content: task.starter_code || '',
        notes: '',
        external_link: '',
        submission_file: null,
    });

    function submit(e) {
        e.preventDefault();
        post(`/task/${task.id}`, { forceFormData: true });
    }

    return (
        <>
            <Head title={task.title} />
            <section className="max-w-5xl mx-auto px-6 py-10">
                <Link href="/tasks" className="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="19" x2="5" y1="12" y2="12" /><polyline points="12 19 5 12 12 5" /></svg>
                    Kembali ke Studi Kasus
                </Link>

                <div className="animate-fade-up">
                    <div className="flex items-center gap-2">
                        <span className="badge badge-accent">{task.category_name}</span>
                        <span className="flex items-center gap-1.5 text-[11px] font-medium text-[var(--muted)] capitalize">
                            <span className={`w-1.5 h-1.5 rounded-full ${task.difficulty === 'mahir' ? 'bg-[var(--danger)]' : task.difficulty === 'menengah' ? 'bg-[var(--warning)]' : 'bg-[var(--accent)]'}`}></span>
                            {task.difficulty}
                        </span>
                    </div>
                    <h1 className="mt-3 text-2xl md:text-3xl font-bold tracking-tight">{task.title}</h1>
                    <p className="mt-1 text-sm text-[var(--muted)]">Konteks industri: {task.industry_context || '-'}</p>
                </div>

                <div className="mt-8 surface p-8 animate-fade-up" style={{ animationDelay: '0.1s' }}>
                    <div className="flex items-center gap-2 mb-4">
                        <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" /><polyline points="14 2 14 8 20 8" /></svg>
                        </div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Brief dari Agent Task Issuer</p>
                    </div>
                    <p className="text-sm text-[var(--ink-light)] leading-relaxed whitespace-pre-line">{task.case_brief}</p>

                    {task.starter_code && (
                        <div className="mt-6">
                            <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-3 flex items-center gap-2">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="16 18 22 12 16 6" /><polyline points="8 6 2 12 8 18" /></svg>
                                Kode Awal
                            </p>
                            <pre className="code-block overflow-x-auto p-4">{task.starter_code}</pre>
                        </div>
                    )}
                </div>

                {user.role === 'siswa' && (
                    <>
                        {mySubmission && (
                            <div className="mt-6 p-4 rounded-2xl border border-[var(--border)] bg-[var(--paper-soft)] flex items-center justify-between animate-fade-up">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)]">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>
                                    </div>
                                    <span className="text-sm text-[var(--ink-light)]">Kamu sudah pernah mengirim solusi untuk studi kasus ini.</span>
                                </div>
                                <Link href={`/submission/${mySubmission.id}`} className="link-accent text-sm shrink-0">Lihat hasil &rarr;</Link>
                            </div>
                        )}

                        {Object.keys(errors).length > 0 && (
                            <div className="mt-6 p-4 rounded-xl border border-[#f3d6d2] bg-[var(--danger-50)] flex items-start gap-3">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" strokeWidth="2" className="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10" /><line x1="15" x2="9" y1="9" y2="15" /><line x1="9" x2="15" y1="9" y2="15" /></svg>
                                <div className="text-sm text-[var(--danger)]">
                                    {Object.values(errors).map((err, i) => <p key={i}>{err}</p>)}
                                </div>
                            </div>
                        )}

                        <form onSubmit={submit} encType="multipart/form-data" className="mt-6 surface p-8" id="submitForm">
                            <div className="flex items-center gap-2 mb-4">
                                <div className="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)]">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="16 18 22 12 16 6" /><polyline points="8 6 2 12 8 18" /></svg>
                                </div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Kirim Solusi Kamu &middot; {typeConfig.label}</p>
                            </div>

                            {isCode ? (
                                <textarea value={data.code_content} onChange={(e) => setData('code_content', e.target.value)} rows="14" className="code-editor w-full" placeholder="Tempel kode PHP kamu di sini..."></textarea>
                            ) : (
                                <>
                                    <label>{typeConfig.field_label}</label>
                                    <textarea value={data.code_content} onChange={(e) => setData('code_content', e.target.value)} rows="8" placeholder="Jelaskan pendekatan, keputusan desain/konfigurasi, dan alasannya secara detail..."></textarea>

                                    {typeConfig.accepts_link && (
                                        <div className="mt-4">
                                            <label>Link Eksternal <span className="text-[var(--muted-light)] font-normal">(mis. link Figma — opsional tapi sangat disarankan)</span></label>
                                            <input type="url" value={data.external_link} onChange={(e) => setData('external_link', e.target.value)} placeholder="https://figma.com/file/..." />
                                        </div>
                                    )}

                                    {typeConfig.accepts_file && (
                                        <div className="mt-4">
                                            <label>Unggah File <span className="text-[var(--muted-light)] font-normal">(screenshot/topologi/dokumen — PNG/JPG/PDF, maks 8MB, opsional tapi sangat disarankan)</span></label>
                                            <input type="file" accept=".png,.jpg,.jpeg,.webp,.pdf" onChange={(e) => setData('submission_file', e.target.files[0])} className="file-input-custom" />
                                        </div>
                                    )}
                                </>
                            )}

                            <div className="mt-4">
                                <label>Catatan untuk reviewer <span className="text-[var(--muted-light)] font-normal">(opsional)</span></label>
                                <textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} rows="2" placeholder="Jelaskan pendekatan yang kamu ambil…"></textarea>
                            </div>
                            <button type="submit" disabled={processing} id="submitBtn" className="btn btn-primary mt-5 px-8">
                                {processing
                                    ? <>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="animate-spin"><circle cx="12" cy="12" r="10" strokeDasharray="31.4" strokeDashoffset="10" /></svg>
                                        Agent Reviewer sedang mengaudit kode kamu…
                                      </>
                                    : <>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="22" x2="11" y1="2" y2="13" /><polygon points="22 2 15 22 11 13 2 9 22 2" /></svg>
                                        Kirim untuk Diaudit Agent Reviewer
                                      </>}
                            </button>
                        </form>
                    </>
                )}
            </section>
        </>
    );
}

Task.layout = (page) => <Layout>{page}</Layout>;
