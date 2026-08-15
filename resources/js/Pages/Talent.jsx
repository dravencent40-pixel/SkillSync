import { Head, Link, router } from '@inertiajs/react';
import Layout from '../Components/Layout';
import { Avatar, EmptyState } from '../Components/ui';

export default function Talent({ talent, q }) {
    function onSearch(value) {
        router.get('/talent', { q: value }, { preserveState: true, replace: true });
    }

    return (
        <>
            <Head title="Talent Pool" />
            <section className="max-w-7xl mx-auto px-6 py-10">
                <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 animate-fade-up">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Talent Pool</h1>
                        <p className="mt-1 text-sm text-[var(--muted)]">Siswa dengan profil kompetensi publik, diurutkan dari skor keseluruhan tertinggi.</p>
                    </div>
                    <div className="shrink-0 w-full md:w-auto">
                        <input
                            type="text"
                            value={q}
                            onChange={(e) => onSearch(e.target.value)}
                            placeholder="Cari nama atau jurusan..."
                            className="w-full md:w-64"
                        />
                    </div>
                </div>

                {talent.length === 0 ? (
                    <div className="mt-10 surface p-14">
                        <EmptyState
                            title={q !== '' ? 'Tidak ada hasil' : 'Belum ada talenta publik'}
                            desc={q !== '' ? 'Coba kata kunci lain.' : 'Talenta akan muncul di sini setelah siswa menyelesaikan studi kasus dan profilnya publik.'}
                        />
                    </div>
                ) : (
                    <div className="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
                        {talent.map((t) => (
                            <Link key={t.profile_id} href={`/talent/${t.profile_id}`} className="cv-card group block">
                                <div className="flex items-start gap-3">
                                    <Avatar name={t.name} initial={t.avatar_initial} size="lg" />
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="font-semibold text-[var(--ink)] truncate group-hover:text-[var(--accent)] transition-colors">{t.name}</p>
                                                <p className="text-xs text-[var(--muted)] truncate">{t.jurusan || 'Jurusan belum diisi'} &middot; {t.sekolah || '-'}</p>
                                            </div>
                                            <span className={`num text-sm font-bold shrink-0 ${scoreColor(t.overall_score)}`}>{Number(t.overall_score) || 0}</span>
                                        </div>
                                        <div className="mt-3 flex items-center justify-between">
                                            <span className="badge badge-accent">{t.badge}</span>
                                            <span className="text-[11px] text-[var(--muted-light)]">{Number(t.tasks_completed) || 0} studi kasus</span>
                                        </div>
                                        {t.cv_path && (
                                            <p className="mt-2 text-[11px] font-medium flex items-center gap-1 text-[var(--accent)]">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 6 9 17l-5-5" /></svg>
                                                CV tersedia
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
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

Talent.layout = (page) => <Layout>{page}</Layout>;
