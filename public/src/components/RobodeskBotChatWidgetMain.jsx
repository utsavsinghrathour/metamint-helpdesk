import React from "react";
import PropTypes from "prop-types";
import { useEffect, useRef, useState } from "@wordpress/element";
import { FaRegFaceSmileBeam } from 'react-icons/fa6';
import { PiPaperPlaneRightFill } from 'react-icons/pi';
import { FiPaperclip } from 'react-icons/fi';
import EmojiPicker from 'emoji-picker-react';

export default function RobodeskBotChatWidgetMain({ widgetConfig, activeAIMode }) {
    const rootRef = useRef(null);
    const CHAT_KEY = "robodesk_chat_session";
    const [enabled, setEnabled] = useState(true);
    const [AIMode, setAIMode] = useState(activeAIMode || "");
    const [open, setOpen] = useState(false);
    // const [messages, setMessages] = useState([]);
    const [messages, setMessages] = useState(() => {
        const saved = sessionStorage.getItem(CHAT_KEY);
        return saved ? JSON.parse(saved) : [];
    });
    const [input, setInput] = useState("");

    const apiProxy = window.location.origin + "/wp-json/robodesk-bot/v1/chat";

    /**
     * ASSISTANT MESSAGE RENDERER
     */
    function AssistantMessage({ msg }) {
        return (
            <div className="ai-block chat-container">
                {/* MAIN MESSAGE TEXT */}
                {msg.message && <div className="ai-text rd-body-text"><p>{msg.message}</p></div>}

                {/* INTENT PILL */}
                {msg.intent && (
                    <div className="ai-intent">
                        Intent: <strong>{msg.intent}</strong>
                    </div>
                )}

                {/* SQL RESULT CARDS */}
                {Array.isArray(msg.data) && msg.data.length > 0 && (
                    <div className="ai-cards">
                        {msg.data.map((row, i) => (
                            <div key={i} className="ai-card">
                                {Object.entries(row).map(([k, v]) => (
                                    <div key={k}>
                                        <strong>{k}</strong>: {String(v)}
                                    </div>
                                ))}
                            </div>
                        ))}
                    </div>
                )}

                {/* SUGGESTIONS (label + prompt) */}
                {msg.ui?.suggestions?.length > 0 && (
                    <div className="ai-suggestions">
                        {msg.ui.suggestions.map((s, i) => (
                            <button
                                key={i}
                                className="chip"
                                onClick={() => setInput(s.prompt || s.label || "")}
                            >
                                {s.label || s.prompt}
                            </button>
                        ))}
                    </div>
                )}

                {/* ACTIONS (link / deep actions) */}
                {msg.ui?.actions?.length > 0 && (
                    <div className="ai-actions">
                        {msg.ui.actions.map((a, i) => (
                            <button
                                key={i}
                                onClick={() => {
                                    if (a.prompt) setInput(a.prompt);
                                    if (a.url) window.location.href = a.url;
                                }}
                            >
                                {a.label}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        );
    }
    AssistantMessage.propTypes = {
        msg: PropTypes.shape({
            message: PropTypes.string,
            intent: PropTypes.string,
            data: PropTypes.any,
            ui: PropTypes.any,
        }).isRequired,
    };

    useEffect(() => {
        sessionStorage.setItem(CHAT_KEY, JSON.stringify(messages));
    }, [messages]);

    // Configuration is now passed via props, no need to read from DOM

    /**
     * AUTO SCROLL LAST MESSAGE
     */
    useEffect(() => {
        if (rootRef.current) {
            rootRef.current.scrollTop = rootRef.current.scrollHeight;
        }
    }, [messages]);

    if (!enabled) return null;

    /**
     * SEND MESSAGE
     */
    const sendMessage = async () => {
        const q = input.trim();
        if (!q) return;

        setMessages((m) => [...m, { role: "user", text: q }]);
        setInput("");

        // add thinking placeholder
        setMessages((m) => [
            ...m,
            { role: "assistant", text: "Thinking...", thinking: true },
        ]);

        const headers = { "Content-Type": "application/json" };
        if (window.RobodeskBotConfig?.restNonce) {
            headers["X-WP-Nonce"] = RobodeskBotConfig.restNonce;
        }

        try {
            const resp = await fetch(apiProxy, {
                method: "POST",
                headers,
                body: JSON.stringify({ query: q }),
            });

            const j = await resp.json();

            setMessages((m) => [
                ...m.filter((x) => !x.thinking),
                {
                    role: "assistant",
                    mode: j.mode,
                    message: j.message || "",
                    data: j.data || null,
                    ui: j.ui || { suggestions: [], actions: [] },
                    intent: j.intent || "",
                },
            ]);
        } catch (e) {
            setMessages((m) => [
                ...m.filter((x) => !x.thinking),
                { role: "assistant", message: e.message },
            ]);
        }
    };

    return (
        <>
            <div id="robodesk-bot-panel">
                {/* <div id="robodesk-bot-header">
                    <strong>AI Assistant ({AIMode})</strong>
                    <button onClick={() => setOpen(false)}>x</button>
                </div> */}

                {/* MESSAGES */}
                <div id="robodesk-bot-messages" ref={rootRef} style={
                    messages.length > 0
                        ? {
                            maxHeight: '145px',
                            overflowY: 'auto',
                            padding: '16px',
                            display: 'flex',
                            flexDirection: 'column',
                            gap: '4px',
                        }
                        : {}
                }>
                    {messages.length > 0 && messages.map((m, i) => {
                        const prevMsg = i > 0 ? messages[i - 1] : null;
                        const isSameSender = prevMsg && prevMsg.role === m.role;
                        const marginBottom = isSameSender ? '4px' : '12px';

                        return (
                            <div
                                key={i}
                                className={`chat-message ${m.role === 'user'
                                    ? 'rd-ml-auto sent'
                                    : 'rd-flex rd-gap-8 received'
                                    }`}
                                style={{
                                    maxWidth: '70%',
                                    marginBottom,
                                    marginTop: i === 0 || !isSameSender ? '12px' : '4px',
                                    display: 'flex',
                                    alignItems: 'flex-start',
                                    marginLeft: m.role === 'user' ? 'auto' : '0',
                                    gap: m.role === 'assistant' ? '8px' : '0'
                                }}
                            >
                                {m.role === 'assistant' && (!isSameSender || i === 0) && (
                                    <div
                                        style={{
                                            height: '30px',
                                            width: '30px',
                                            borderRadius: '50px',
                                            backgroundColor: '#007BFF',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            color: 'white',
                                            fontSize: '14px',
                                            fontWeight: 'bold',
                                            flexShrink: 0
                                        }}
                                    >
                                        AI
                                    </div>
                                )}
                                {m.role === 'assistant' && isSameSender && i !== 0 && (
                                    <div style={{ width: '30px', height: '30px' }} />
                                )}
                                <div
                                    className="message-content"
                                    style={{
                                        backgroundColor: m.role === 'user' || m.thinking ? '#007BFF' : '#FFFFFF',
                                        color: m.role === 'user' || m.thinking ? '#FFFFFF' : '#000000',
                                        border: m.role === 'assistant' && !m.thinking ? '1px solid #E0E0E0' : 'none',
                                        borderRadius: '10px',
                                        padding: '8px 12px',
                                        maxWidth: '100%',
                                        opacity: m.thinking ? 0.7 : 1
                                    }}
                                >
                                    {m.role === 'assistant' ? <AssistantMessage msg={m} /> : (
                                        <span className="rd-body-text"><p>{m.text}</p></span>
                                    )}
                                    {!m.thinking && (
                                        <div
                                            className="rd-text-small rd-text-gray"
                                            style={{
                                                marginTop: '4px',
                                                textAlign: m.role === 'user' ? 'right' : 'left',
                                                fontSize: '10px',
                                                opacity: 0.7
                                            }}
                                        >
                                            {new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                    {messages.length === 0 && (
                        <div className='rd-text-center rd-px-20 rd-flex rd-flex-col rd-gap-5 rd-mb-20 home-tab-title'>
                            <h4 className='heading-4'>Have Questions?</h4>
                            <h5 className='sub-text rd-mt-0'>
                                Have any questions or need a quote? Leave us a message.
                            </h5>
                        </div>
                    )}
                </div>

                {/* INPUT */}
                <div id="robodesk-bot-input" className="chat-input rd-flex rd-px-18 rd-py-15 rd-border-top-light rd-border-bottom-light rd-bg-white rd-justify-between">
                    <>
                        <input
                            value={input}
                            placeholder="Ask about this site..."
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === "Enter" && !e.shiftKey) {
                                    e.preventDefault();
                                    sendMessage();
                                }
                            }}
                        />
                        <div className='rd-flex rd-gap-20 rd-relative'>
                            <button
                                onClick={sendMessage}
                                className='rd-bg-transparent rd-border-none rd-ml-10 icon-dark rd-cursor-pointer rd-p-0'
                            >
                                <PiPaperPlaneRightFill className='icon-dark' size={20} />
                            </button>
                        </div>
                    </>
                </div>
            </div>
        </>
    );
}
