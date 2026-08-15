export default function Home() {
    return (
        <div className="min-h-[100dvh] flex items-center justify-center bg-paper text-ink px-6">
            <div className="text-center">
                <div className="logo-tile w-14 h-14 mx-auto mb-6">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="6" /><circle cx="12" cy="12" r="2" /></svg>
                </div>
                <h1 className="text-4xl font-extrabold tracking-tight">SkillSync</h1>
                <p className="mt-3 text-muted">Laravel 13 + React 19 (Inertia) + Tailwind v4 berhasil terhubung.</p>
            </div>
        </div>
    );
}
