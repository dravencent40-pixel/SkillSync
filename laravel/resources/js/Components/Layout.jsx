import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';

export function Logo({ size = 'w-9 h-9', icon = 'w-[18px] h-[18px]', to = '/' }) {
    return (
        <Link href={to} className="flex items-center gap-3 shrink-0 group">
            <div className={`logo-tile ${size}`}>
                <svg className={icon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" />
                </svg>
            </div>
            <span className="font-bold tracking-tight text-lg hidden sm:block">SkillSync</span>
        </Link>
    );
}

export function NavLinks({ items, mobile = false }) {
    const size = mobile ? 18 : 15;
    return items.map((item) => (
        <Link
            key={item.url}
            href={item.url}
            className={`nav-link ${item.active ? 'active' : ''} ${mobile ? 'flex items-center gap-3 py-3' : 'flex items-center gap-2'}`}
        >
            <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d={item.icon} />
            </svg>
            {item.label}
        </Link>
    ));
}

export function LogoutButton({ className = '' }) {
    return (
        <button type="button" onClick={() => router.post('/logout')} className={className}>
            <svg width={14} height={14} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" x2="9" y1="12" y2="12" />
            </svg>
            Keluar
        </button>
    );
}

export function FlashBanner() {
    const { flash } = usePage().props;
    const message = flash?.success || flash?.error;
    if (!message) return null;

    const isError = !!flash?.error;
    return (
        <div className="max-w-7xl mx-auto px-6 pt-4 animate-fade-up">
            <div className={`rounded-2xl px-5 py-3.5 text-sm font-medium flex items-center gap-3 ${isError ? 'bg-[var(--danger-50)] text-[var(--danger)] border border-[#f3d6d2]' : 'bg-[var(--paper-soft)] text-[var(--ink)] border border-[var(--border)]'}`}>
                {isError
                    ? <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10" /><line x1="15" x2="9" y1="9" y2="15" /><line x1="9" x2="15" y1="9" y2="15" /></svg>
                    : <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>}
                {message}
            </div>
        </div>
    );
}

export default function Layout({ children }) {
    const { auth, nav, aiMode } = usePage().props;
    const user = auth?.user ?? null;
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        setOpen(false);
    }, [children]);

    return (
        <div className="min-h-[100dvh] bg-paper text-ink antialiased">
            <header id="mainHeader" className={`site-header ${scrolled ? 'scrolled' : ''}`}>
                <div className="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
                    <Logo />

                    {aiMode && (
                        <span className={`mode-badge hidden lg:inline-flex ${aiMode.active ? 'online' : 'offline'}`}>
                            <span className="dot"></span>
                            {aiMode.label}
                        </span>
                    )}

                    {user ? (
                        <>
                            <nav className="hidden md:flex items-center gap-1">
                                <NavLinks items={nav} />
                            </nav>
                            <div className="flex items-center gap-3">
                                <div className="hidden sm:flex items-center gap-3">
                                    <div className="avatar avatar-sm">{user.avatar_initial || user.name?.slice(0, 2).toUpperCase()}</div>
                                    <div className="text-right">
                                        <p className="text-xs font-semibold leading-tight">{user.name}</p>
                                        <p className="text-[10px] text-[var(--muted)] capitalize">{user.role}</p>
                                    </div>
                                </div>
                                <LogoutButton className="btn btn-ghost btn-sm hidden sm:inline-flex" />
                                <button className={`hamburger md:hidden ${open ? 'open' : ''}`} onClick={() => setOpen((v) => !v)} aria-label="Menu">
                                    <span></span><span></span><span></span>
                                </button>
                            </div>
                        </>
                    ) : (
                        <div className="flex items-center gap-3">
                            <Link href="/login" className="btn btn-ghost btn-sm">Masuk</Link>
                            <Link href="/register" className="btn btn-primary btn-sm">Daftar</Link>
                        </div>
                    )}
                </div>

                {user && (
                    <div id="mobileMenu" className={`mobile-menu md:hidden ${open ? 'open' : ''}`}>
                        <div className="flex items-center gap-3 p-4 mb-4 surface rounded-2xl">
                            <div className="avatar avatar-lg">{user.avatar_initial || user.name?.slice(0, 2).toUpperCase()}</div>
                            <div>
                                <p className="font-semibold">{user.name}</p>
                                <p className="text-xs text-[var(--muted)] capitalize">{user.role}</p>
                            </div>
                        </div>
                        <nav className="flex flex-col gap-1">
                            <NavLinks items={nav} mobile />
                            <hr className="my-3 divider" />
                            <div className="nav-link flex items-center gap-3 py-3 text-[var(--danger)]">
                                <LogoutButton className="flex items-center gap-3 text-[var(--danger)]" />
                            </div>
                        </nav>
                    </div>
                )}
            </header>

            <div className="h-16"></div>

            <main>
                <FlashBanner />
                {children}
            </main>

            <footer className="mt-24 border-t border-[var(--border-light)]" style={{ background: 'linear-gradient(180deg, #f7f6f2 0%, #f0efe9 100%)' }}>
                <div className="max-w-7xl mx-auto px-6 py-12">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-10">
                        <div className="md:col-span-5">
                            <Logo />
                            <p className="mt-4 text-sm text-[var(--muted)] leading-relaxed max-w-md">
                                Platform asesmen teknis berbasis AI yang mengotomatisasi penilaian studi kasus industri, menyediakan mentor interaktif, serta menyajikan profil kompetensi transparan.
                            </p>
                        </div>
                        <div className="md:col-span-3 md:col-start-7">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-[var(--muted-light)] mb-4">Platform</h4>
                            <ul className="space-y-2.5">
                                <li><Link href="/talent" className="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors">Talent Pool</Link></li>
                                <li><Link href="/login" className="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors">Masuk</Link></li>
                                <li><Link href="/register" className="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors">Daftar</Link></li>
                            </ul>
                        </div>
                        <div className="md:col-span-4">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-[var(--muted-light)] mb-4">Kontak</h4>
                            <p className="text-sm text-[var(--muted)] mb-3">Dibuat oleh Kelompok Tekabe &middot; Kategori Pendidikan / Inovatif</p>
                            <div className="flex flex-col gap-2">
                                <a href="mailto:taufiqridhoo34@gmail.com" className="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors flex items-center gap-2">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="16" x="2" y="4" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></svg>
                                    taufiqridhoo34@gmail.com
                                </a>
                                <a href="mailto:riwantoraihan@gmail.com" className="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors flex items-center gap-2">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="20" height="16" x="2" y="4" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" /></svg>
                                    riwantoraihan@gmail.com
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr className="my-8 divider" />
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <p className="text-xs text-[var(--muted-light)]">&copy; {new Date().getFullYear()} SkillSync. All rights reserved.</p>
                        <p className="text-[11px] font-mono text-[var(--muted-light)]">Powered by Groq &middot; Llama-3.3-70B</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
