import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '', email: '', role: 'siswa', sekolah: '', jurusan: '', company_name: '', password: '', password_confirm: '',
    });

    function submit(e) {
        e.preventDefault();
        post('/register');
    }

    return (
        <>
            <Head title="Daftar Akun" />

            <div className="min-h-[80vh] flex items-center justify-center px-6 py-10">
                <div className="w-full max-w-md mx-auto animate-fade-up">
                    <div className="lg:hidden flex items-center gap-3 mb-8">
                        <div className="logo-tile w-10 h-10">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" /></svg>
                        </div>
                        <span className="font-bold text-xl">SkillSync <span className="text-accent">AI</span></span>
                    </div>

                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight">Buat akun baru</h1>
                    <p className="mt-2 text-sm text-[var(--muted)]">Gabung sebagai siswa untuk mulai mengasah kompetensi, atau sebagai mitra untuk menemukan talenta.</p>

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
                            <label>Daftar sebagai</label>
                            <div className="grid grid-cols-2 gap-3 mt-1">
                                <label className={`role-option ${data.role === 'siswa' ? 'active' : ''}`}>
                                    <input type="radio" name="role" value="siswa" checked={data.role === 'siswa'} onChange={() => setData('role', 'siswa')} className="sr-only" />
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z" /><path d="M6 12v5c3 3 9 3 12 0v-5" /></svg>
                                    Siswa
                                </label>
                                <label className={`role-option ${data.role === 'mitra' ? 'active' : ''}`}>
                                    <input type="radio" name="role" value="mitra" checked={data.role === 'mitra'} onChange={() => setData('role', 'mitra')} className="sr-only" />
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect width="16" height="20" x="4" y="2" rx="2" /><path d="M9 22v-4h6v4" /><path d="M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01" /></svg>
                                    Mitra Industri
                                </label>
                            </div>
                        </div>

                        <div>
                            <label htmlFor="name">Nama Lengkap</label>
                            <input type="text" id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Nama lengkapmu" />
                        </div>
                        <div>
                            <label htmlFor="email">Email</label>
                            <input type="email" id="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="nama@email.com" />
                        </div>

                        {data.role === 'siswa' ? (
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label htmlFor="sekolah">Sekolah</label>
                                        <input type="text" id="sekolah" value={data.sekolah} onChange={(e) => setData('sekolah', e.target.value)} placeholder="SMKN 9 Bekasi" />
                                    </div>
                                    <div>
                                        <label htmlFor="jurusan">Jurusan</label>
                                        <input type="text" id="jurusan" value={data.jurusan} onChange={(e) => setData('jurusan', e.target.value)} placeholder="RPL / TKJ / DKV" />
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div>
                                <label htmlFor="company_name">Nama Perusahaan</label>
                                <input type="text" id="company_name" value={data.company_name} onChange={(e) => setData('company_name', e.target.value)} placeholder="PT Contoh Teknologi" />
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label htmlFor="password">Password</label>
                                <input type="password" id="password" value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="Min. 8 karakter" />
                            </div>
                            <div>
                                <label htmlFor="password_confirm">Konfirmasi</label>
                                <input type="password" id="password_confirm" value={data.password_confirm} onChange={(e) => setData('password_confirm', e.target.value)} placeholder="Ulangi password" />
                            </div>
                        </div>

                        <button type="submit" disabled={processing} className="btn btn-primary w-full py-3 mt-2">
                            Buat Akun
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="5" x2="19" y1="12" y2="12" /><polyline points="12 5 19 12 12 19" /></svg>
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-[var(--muted)]">Sudah punya akun? <Link href="/login" className="link-accent">Masuk di sini</Link></p>
                </div>
            </div>
        </>
    );
}

Register.layout = (page) => <Layout>{page}</Layout>;
