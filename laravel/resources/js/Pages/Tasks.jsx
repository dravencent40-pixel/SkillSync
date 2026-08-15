import { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { EmptyState } from '../Components/ui';

export default function Tasks({ tasks, categories }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isMitra = user.role === 'mitra';
    const [modalOpen, setModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '', category_id: '', industry_context: '', case_brief: '', starter_code: '', difficulty: 'pemula',
    });

    function submit(e) {
        e.preventDefault();
        post('/tasks', { onSuccess: () => { reset(); setModalOpen(false); } });
    }

    return (
        <>
            <Head title="Studi Kasus" />
            <section className="max-w-7xl mx-auto px-6 py-10">
                <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 animate-fade-up">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Studi Kasus</h1>
                        <p className="mt-1 text-sm text-[var(--muted)]">{isMitra ? 'Kelola bank soal yang akan disajikan Agent Task Issuer.' : 'Diterbitkan oleh Agent Task Issuer & mitra industri.'}</p>
                    </div>
                    {isMitra && (
                        <button onClick={() => setModalOpen(true)} className="btn btn-primary shrink-0">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" x2="12" y1="5" y2="19" /><line x1="5" x2="19" y1="12" y2="12" /></svg>
                            Terbitkan Studi Kasus
                        </button>
                    )}
                </div>

                {tasks.length === 0 ? (
                    <div className="mt-10 surface p-14">
                        <EmptyState
                            title="Belum ada studi kasus"
                            desc="Studi kasus akan muncul di sini setelah diterbitkan."
                        />
                    </div>
                ) : (
                    <div className="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        {tasks.map((t) => (
                            <div key={t.id} className="surface surface-hover spot-card p-6 flex flex-col group">
                                <div className="flex items-center justify-between">
                                    <span className="badge badge-accent">{t.category_name}</span>
                                    <span className="flex items-center gap-1.5 text-[11px] font-medium text-[var(--muted)] capitalize">
                                        <span className={`w-1.5 h-1.5 rounded-full ${t.difficulty === 'mahir' ? 'bg-[var(--danger)]' : t.difficulty === 'menengah' ? 'bg-[var(--warning)]' : 'bg-[var(--accent)]'}`}></span>
                                        {t.difficulty}
                                    </span>
                                </div>
                                <h3 className="mt-4 font-semibold text-[var(--ink)] leading-snug">{t.title}</h3>
                                <p className="mt-2 text-xs text-[var(--muted)] leading-relaxed">{String(t.case_brief).slice(0, 110)}…</p>
                                <div className="mt-5 pt-4 border-t border-[var(--border-light)] flex items-center justify-between">
                                    <span className="text-xs text-[var(--muted-light)] flex items-center gap-1.5">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                                        {t.industry_context}
                                    </span>
                                    {isMitra ? (
                                        <span className="text-xs text-[var(--muted-light)]">{t.submission_count} submission</span>
                                    ) : (
                                        <Link href={`/task/${t.id}`} className="link-accent text-sm">{t.done > 0 ? 'Lihat ulang' : 'Kerjakan'} →</Link>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>

            {isMitra && modalOpen && (
                <div className="modal-overlay open" onClick={(e) => { if (e.target === e.currentTarget) setModalOpen(false); }}>
                    <div className="modal-card max-w-lg w-full p-8">
                        <div className="flex items-center justify-between mb-6">
                            <div>
                                <h2 className="text-lg font-bold text-[var(--ink)]">Terbitkan Studi Kasus</h2>
                                <p className="text-xs text-[var(--muted)] mt-0.5">Isi detail studi kasus untuk siswa</p>
                            </div>
                            <button onClick={() => setModalOpen(false)} className="modal-close">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="18" x2="6" y1="6" y2="18" /><line x1="6" x2="18" y1="6" y2="18" /></svg>
                            </button>
                        </div>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label>Judul</label>
                                <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Perbaiki Endpoint API yang Rentan XSS" />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label>Kategori</label>
                                    <select value={data.category_id} onChange={(e) => setData('category_id', e.target.value)}>
                                        <option value="">Pilih…</option>
                                        {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label>Tingkat Kesulitan</label>
                                    <select value={data.difficulty} onChange={(e) => setData('difficulty', e.target.value)}>
                                        <option value="pemula">Pemula</option>
                                        <option value="menengah">Menengah</option>
                                        <option value="mahir">Mahir</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label>Konteks Industri</label>
                                <input type="text" value={data.industry_context} onChange={(e) => setData('industry_context', e.target.value)} placeholder="Fintech, E-commerce, Logistik…" />
                            </div>
                            <div>
                                <label>Deskripsi Studi Kasus</label>
                                <textarea value={data.case_brief} onChange={(e) => setData('case_brief', e.target.value)} required rows="4" placeholder="Jelaskan permasalahan riil yang harus diselesaikan siswa…"></textarea>
                            </div>
                            <div>
                                <label>Kode Awal <span className="text-[var(--muted-light)] font-normal">(opsional)</span></label>
                                <textarea value={data.starter_code} onChange={(e) => setData('starter_code', e.target.value)} rows="4" className="code-editor" placeholder="// kode bermasalah yang perlu diperbaiki siswa"></textarea>
                            </div>
                            {Object.keys(errors).length > 0 && (
                                <div className="text-sm text-[var(--danger)]">{Object.values(errors)[0]}</div>
                            )}
                            <button type="submit" disabled={processing} className="btn btn-primary w-full py-3">Terbitkan</button>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

Tasks.layout = (page) => <Layout>{page}</Layout>;
