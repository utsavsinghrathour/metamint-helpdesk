import React from "react";
import PropTypes from "prop-types";
import { useEffect, useRef, useState } from "@wordpress/element";

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
            <div className="ai-block">
                {/* MAIN MESSAGE TEXT */}
                {msg.message && <div className="ai-text">{msg.message}</div>}

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
            console.log("AI response", j);

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
                <div id="robodesk-bot-header">
                    <strong>AI Assistant ({AIMode})</strong>
                    <button onClick={() => setOpen(false)}>x</button>
                </div>

                {/* MESSAGES */}
                <div id="robodesk-bot-messages" ref={rootRef}>
                    {messages.map((m, i) => (
                        <div
                            key={i}
                            className={`robodesk-bot-msg robodesk-bot-${m.role} ${m.thinking ? "thinking" : ""
                                }`}
                        >
                            {m.role === "assistant" ? <AssistantMessage msg={m} /> : m.text}
                        </div>
                    ))}
                </div>

                {/* INPUT */}
                <div id="robodesk-bot-input">
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
                    <button onClick={sendMessage}>Send</button>
                </div>
            </div>
        </>
    );
}
