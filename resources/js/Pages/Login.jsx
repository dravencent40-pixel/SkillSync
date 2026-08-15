import { Head, Link, useForm } from '@inertiajs/react';
import Layout from '../Components/Layout';

const features = [
    { icon: 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', label: 'Automated code audit oleh AI' },
    { icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', label: 'AI Mentor interaktif 24/7' },
    { icon: 'M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z', label: 'Profil kompetensi transparan' },
];

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '' });

    function submit(e) {
        e.preventDefault();
        post('/login');
    }

    return (
        <>
            <Head title="Masuk" />

            <div className="min-h-[80vh] flex items-center justify-center px-6 py-10">
                <div className="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div className="hidden lg:block animate-fade-up">
                        <div className="p-10 rounded-3xl relative overflow-hidden" style={{ background: 'var(--gradient-dark)' }}>
                            <div className="blob" style={{ width: 250, height: 250, top: -80, right: -50, background: 'rgba(31,156,110,0.25)' }}></div>
                            <div className="relative z-10">
                                <div className="logo-tile w-14 h-14 mb-8">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" /></svg>
                                </div>
                                <h2 className="text-2xl font-bold text-white leading-tight">SkillSync</h2>
                                <p className="mt-3 text-sm text-neutral-400 leading-relaxed max-w-[30ch]">Platform asesmen teknis berbasis AI untuk siswa SMK dan mitra industri.</p>
                                <div className="mt-10 space-y-4">
                                    {features.map((f, i) => (
                                        <div key={i} className="flex items-center gap-3 text-sm text-neutral-300">
                                            <div className="w-8 h-8 rounded-lg flex items-center justify-center" style={{ background: 'rgba(255,255,255,0.08)', border: '1px solid rgba(255,255,255,0.08)' }}>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5fca9a" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d={f.icon} /></svg>
                                            </div>
                                            {f.label}
                                        </div>
                                    ))}
                                </div>
                                <div className="mt-10 pt-6 border-t border-white/10 flex items-center gap-3">
                                    <span className="num text-2xl font-extrabold text-white">4</span>
                                    <p className="text-xs text-neutral-400 leading-snug">AI agent bekerja sama dalam satu pipeline asesmen.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="animate-fade-up" style={{ animationDelay: '0.1s' }}>
                        <div className="w-full max-w-md mx-auto">
                            <div className="lg:hidden flex items-center gap-3 mb-8">
                                <div className="logo-tile w-10 h-10">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" /></svg>
                                </div>
                                <span className="font-bold text-xl">SkillSync <span className="text-accent">AI</span></span>
                            </div>

                            <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Selamat datang kembali</h1>
                            <p className="mt-2 text-sm text-[var(--muted)]">Masuk untuk melanjutkan progres kamu.</p>

                            {Object.keys(errors).length > 0 && (
                                <div className="mt-5 p-4 rounded-xl border border-[#f3d6d2] bg-[var(--danger-50)] flex items-start gap-3">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" strokeWidth="2" className="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10" /><line x1="15" x2="9" y1="9" y2="15" /><line x1="9" x2="15" y1="9" y2="15" /></svg>
                                    <div className="text-sm text-[var(--danger)]">
                                        {Object.values(errors).map((err, i) => <p key={i}>{err}</p>)}
                                    </div>
                                </div>
                            )}

                            <form onSubmit={submit} className="mt-6 space-y-4">
                                <div>
                                    <label htmlFor="email">Email</label>
                                    <input type="email" id="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="nama@email.com" />
                                </div>
                                <div>
                                    <label htmlFor="password">Password</label>
                                    <input type="password" id="password" value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="Masukkan password" />
                                </div>
                                <button type="submit" disabled={processing} className="btn btn-primary w-full py-3 mt-2">
                                    Masuk
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="5" x2="19" y1="12" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
                                </button>
                            </form>

                            <p className="mt-6 text-center text-sm text-[var(--muted)]">Belum punya akun? <Link href="/register" className="link-accent">Daftar di sini</Link></p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Login.layout = (page) => <Layout>{page}</Layout>;
