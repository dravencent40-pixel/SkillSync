import { useEffect, useRef, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Mentor({ conversationId, taskTitle, messages }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [items, setItems] = useState(messages.map((m) => ({ sender: m.sender, message: m.message })));
    const [input, setInput] = useState('');
    const [typing, setTyping] = useState(false);
    const [sending, setSending] = useState(false);
    const windowRef = useRef(null);

    useEffect(() => {
        const el = windowRef.current;
        if (el) el.scrollTop = el.scrollHeight;
    }, [items, typing]);

    async function send(e) {
        e.preventDefault();
        const text = input.trim();
        if (!text || sending) return;

        setItems((prev) => [...prev, { sender: 'user', message: text }]);
        setInput('');
        setSending(true);
        setTyping(true);

        try {
            const res = await fetch('/mentor/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conversation_id: conversationId, message: text }),
            });
            const data = await res.json();
            setItems((prev) => [...prev, { sender: 'agent', message: data.reply || 'Maaf, terjadi kendala. Coba lagi ya.' }]);
        } catch {
            setItems((prev) => [...prev, { sender: 'agent', message: 'Koneksi terganggu, coba kirim ulang pesanmu.' }]);
        } finally {
            setTyping(false);
            setSending(false);
        }
    }

    function onKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send(e);
        }
    }

    return (
        <>
            <Head title="AI Mentor" />
            <section className="max-w-3xl mx-auto px-6 py-10 flex flex-col" style={{ height: 'calc(100dvh - 6.5rem)' }}>
                <div className="flex items-center gap-4 mb-4 animate-fade-up shrink-0">
                    <div className="logo-tile w-12 h-12">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg>
                    </div>
                    <div>
                        <h1 className="text-lg font-bold">Agent Mentor</h1>
                        <p className="text-xs text-[var(--muted)] flex items-center gap-1.5">
                            <span className="w-1.5 h-1.5 rounded-full bg-[var(--accent)] animate-pulse"></span>
                            {taskTitle ? `Konteks: ${taskTitle}` : 'Sesi bebas'}
                        </p>
                    </div>
                </div>

                <div ref={windowRef} className="flex-1 surface p-5 overflow-y-auto flex flex-col gap-4 min-h-0">
                    {items.map((m, i) => (
                        m.sender === 'agent' ? (
                            <div key={i} className="flex gap-3 max-w-[85%] animate-fade-up">
                                <div className="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold logo-tile">M</div>
                                <div className="bubble-agent px-4 py-3 text-sm leading-relaxed whitespace-pre-line">{m.message}</div>
                            </div>
                        ) : (
                            <div key={i} className="flex gap-3 max-w-[85%] ml-auto flex-row-reverse animate-fade-up">
                                <div className="avatar avatar-sm shrink-0">{user.avatar_initial || user.name?.slice(0, 2).toUpperCase()}</div>
                                <div className="bubble-user px-4 py-3 text-sm leading-relaxed whitespace-pre-line">{m.message}</div>
                            </div>
                        )
                    ))}
                    {typing && (
                        <div className="flex gap-3 max-w-[85%] animate-fade-up">
                            <div className="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold logo-tile">M</div>
                            <div className="bubble-agent px-4 py-3 flex gap-1.5 items-center">
                                <span className="w-2 h-2 rounded-full" style={{ background: 'var(--muted-light)', animation: 'fadeIn 0.8s infinite' }}></span>
                                <span className="w-2 h-2 rounded-full" style={{ background: 'var(--muted-light)', animation: 'fadeIn 0.8s 0.2s infinite' }}></span>
                                <span className="w-2 h-2 rounded-full" style={{ background: 'var(--muted-light)', animation: 'fadeIn 0.8s 0.4s infinite' }}></span>
                            </div>
                        </div>
                    )}
                </div>

                <form onSubmit={send} className="mt-4 flex items-end gap-3 shrink-0 animate-fade-up" style={{ animationDelay: '0.1s' }}>
                    <div className="flex-1 relative">
                        <textarea
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={onKeyDown}
                            rows="1"
                            placeholder="Tanyakan tentang bug, konsep, atau feedback kode kamu…"
                            className="w-full resize-none pr-12 py-3 px-4 rounded-2xl text-sm"
                            style={{ border: '1px solid var(--border)' }}
                        ></textarea>
                    </div>
                    <button type="submit" disabled={sending} className="w-12 h-12 rounded-full flex items-center justify-center shrink-0 text-white transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-60" style={{ background: 'var(--gradient-accent)', boxShadow: 'var(--shadow-accent)' }}>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="22" x2="11" y1="2" y2="13" /><polygon points="22 2 15 22 11 13 2 9 22 2" /></svg>
                    </button>
                </form>
            </section>
        </>
    );
}

Mentor.layout = (page) => <Layout>{page}</Layout>;
