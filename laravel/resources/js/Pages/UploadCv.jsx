import { Head, Link, useForm, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { formatDate } from '../Components/ui';

export default function UploadCv({ profile }) {
    const { errors } = usePage().props;
    const { data, setData, post, processing } = useForm({ cv_file: null });

    const hasCv = profile && profile.cv_path;

    function submit(e) {
        e.preventDefault();
        if (!data.cv_file) return;
        post('/upload-cv');
    }

    return (
        <>
            <Head title="Unggah CV" />
            <section className="max-w-2xl mx-auto px-6 py-10">
                <div className="text-center mb-8 animate-fade-up">
                    <div className="empty-state-icon mx-auto">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" /><polyline points="14 2 14 8 20 8" /></svg>
                    </div>
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight mt-4">Unggah CV Kamu</h1>
                    <p className="mt-2 text-sm text-[var(--muted)] max-w-[50ch] mx-auto">CV ini akan ditautkan ke profil kompetensimu dan bisa dilihat oleh mitra industri di Talent Pool bersama skor studi kasus yang sudah kamu kerjakan.</p>
                </div>

                {errors.form && (
                    <div className="mb-6 p-4 rounded-xl border border-[#f3d6d2] flex items-start gap-3 bg-[var(--danger-50)]">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" strokeWidth="2" className="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10" /><line x1="15" x2="9" y1="9" y2="15" /><line x1="9" x2="15" y1="9" y2="15" /></svg>
                        <div className="text-sm text-[var(--danger)] whitespace-pre-line">{errors.form}</div>
                    </div>
                )}

                {hasCv && (
                    <>
                        <div className="surface p-6 mb-6 flex items-center justify-between gap-4 flex-wrap" style={{ borderColor: 'var(--accent-100)', background: 'var(--accent-50)' }}>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl grid place-items-center shrink-0 bg-white text-[var(--accent)]" style={{ boxShadow: 'var(--shadow-sm)' }}>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" /><polyline points="14 2 14 8 20 8" /></svg>
                                </div>
                                <div>
                                    <p className="font-semibold text-sm text-[var(--ink)]">{profile.cv_original_name || 'cv.pdf'}</p>
                                    <p className="text-xs text-[var(--muted)]">Diunggah {formatDate(profile.cv_uploaded_at)}</p>
                                </div>
                            </div>
                            <Link href={`/view-cv?file=${encodeURIComponent(profile.cv_path.split('/').pop())}`} target="_blank" className="btn btn-ghost btn-sm">Lihat CV</Link>
                        </div>

                        <div className="mb-6">
                            <iframe src={`/view-cv?file=${encodeURIComponent(profile.cv_path.split('/').pop())}`} style={{ width: '100%', height: 500, border: 0 }} className="rounded-2xl border border-[var(--border-light)]" title="Pratinjau CV" />
                        </div>
                    </>
                )}

                <form onSubmit={submit} className="surface p-8">
                    <label htmlFor="cv_file">Unggah {hasCv ? 'ulang' : ''} CV (PDF, maks 5MB)</label>
                    <input
                        id="cv_file"
                        type="file"
                        accept="application/pdf"
                        required
                        onChange={(e) => setData('cv_file', e.target.files[0])}
                        className="file-input-custom"
                    />
                    <button type="submit" disabled={processing || !data.cv_file} className="btn btn-primary w-full py-3 mt-5">
                        {hasCv ? 'Perbarui CV' : 'Unggah CV'}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" x2="12" y1="3" y2="15" /></svg>
                    </button>
                </form>
            </section>
        </>
    );
}

UploadCv.layout = (page) => <Layout>{page}</Layout>;
