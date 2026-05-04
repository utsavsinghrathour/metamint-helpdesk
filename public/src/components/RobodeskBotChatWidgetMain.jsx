import React from "react";
import PropTypes from "prop-types";
import { useEffect, useRef, useState } from "@wordpress/element";
import { FaRegFaceSmileBeam } from 'react-icons/fa6';
import { PiPaperPlaneRightFill } from 'react-icons/pi';
import { FiPaperclip } from 'react-icons/fi';
import EmojiPicker from 'emoji-picker-react';

export default function RobodeskBotChatWidgetMain({ widgetConfig, activeAIMode }) {
    console.log('test tse');
    const rootRef = useRef(null);
    const CHAT_KEY = "robodesk_chat_session";
    const chatHeading = window.robodeskData?.chatHeading || widgetConfig?.chatHeading || 'Have Questions?';
    const welcomeMessage = window.robodeskData?.welcomeMessage || widgetConfig?.welcomeMessage || 'Have any questions or need a quote? Leave us a message.';
    const [enabled, setEnabled] = useState(true);
    const [AIMode, setAIMode] = useState(activeAIMode || "");
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState(() => {
        const saved = sessionStorage.getItem(CHAT_KEY);
        return saved ? JSON.parse(saved) : [];
    });
    const [input, setInput] = useState("");
    const [loadingText, setLoadingText] = useState("");
    const [isStreaming, setIsStreaming] = useState(false);
    const [botName, setBotName] = useState("");
    const [botImage, setBotImage] = useState("");
    const apiProxy = window.location.origin + "/wp-json/robodesk-bot/v1/chat";
    const apiProxyStream = window.location.origin + "/wp-json/robodesk-bot/v1/chat-stream";

    /**
     * ASSISTANT MESSAGE RENDERER
     */
    function AssistantMessage({ msg }) {
        return (
            <div className="ai-block">
                {/* MAIN MESSAGE TEXT */}
                {msg.message && <div className="ai-text rd-body-text single-message"><p>{msg.message}</p></div>}

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

    const fetchApiKey = async () => {
        console.log('fetchApiKey');
        try {
            const response = await wp.apiFetch({
                path: `/robodesk-bot/v1/api-key`,
            });
            console.log('response', response);
            // Try both possible key formats your PHP might return
            setBotName(response.botName || response.bot_name || "");
            setBotImage(response.botImage || response.bot_image || "");
        } catch (err) {
            console.error(err);
        }
    };

    useEffect(() => {
        sessionStorage.setItem(CHAT_KEY, JSON.stringify(messages));
        fetchApiKey();

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
        window.dispatchEvent(new CustomEvent("robodesk:messageSent", { detail: { count: messages.length + 1 } }));

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

    const sendMessageStream = async () => {
        const q = input.trim();
        if (!q) return;

        setMessages((m) => [...m, { role: "user", text: q }]);
        setInput("");
        window.dispatchEvent(new CustomEvent("robodesk:messageSent", { detail: { count: messages.length + 1 } }));
        setIsStreaming(true);
        setLoadingText("Thinking...");

        const headers = { "Content-Type": "application/json" };
        if (window.RobodeskBotConfig?.restNonce) {
            headers["X-WP-Nonce"] = RobodeskBotConfig.restNonce;
        }

        try {
            const resp = await fetch(apiProxyStream, {
                method: "POST",
                headers,
                body: JSON.stringify({ query: q }),
            });

            if (!resp.body) throw new Error("Streaming not supported");

            const reader = resp.body.getReader();
            const decoder = new TextDecoder("utf-8");

            let buffer = "";

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });

                const events = buffer.split("\n\n");
                buffer = events.pop();

                for (const ev of events) {
                    const lines = ev.split("\n");

                    let eventName = "";
                    let data = "";

                    for (const line of lines) {
                        if (line.startsWith("event:")) {
                            eventName = line.replace("event:", "").trim();
                        }
                        if (line.startsWith("data:")) {
                            data += line.replace("data:", "").trim();
                        }
                    }

                    if (!data) continue;

                    const parsed = JSON.parse(data);

                    // update loader text
                    if (
                        eventName.startsWith("thinking")
                    ) {
                        console.log("Received thinking event:", eventName,);
                        setLoadingText(parsed.message);
                    }

                    // final message
                    if (eventName === "Final Response:") {
                        console.log("Received final response event:", parsed,);
                        let finalText = "";

                        if (typeof parsed.message === "string") {
                            finalText = parsed.message;
                        } else if (parsed.message && typeof parsed.message === "object") {
                            // If message is an object, try to get text or response field
                            finalText = parsed.message?.text;
                        } else if (parsed.response && typeof parsed.response === "object") {
                            finalText = parsed.response.text || parsed.response.message || JSON.stringify(parsed.response);
                        } else if (typeof parsed.response === "string") {
                            finalText = parsed.response;
                        } else {
                            finalText = "";
                        }
                        console.log("Final parsed response:", finalText,);
                        setMessages((m) => [
                            ...m,
                            {
                                role: "assistant",
                                mode: parsed.mode || "",
                                message: finalText || "",
                                data: parsed.response || null,
                                data: parsed.data || null,
                                ui: parsed.ui || { suggestions: [], actions: [] },
                                intent: parsed.intent || "",
                            },
                        ]);

                        setIsStreaming(false);
                        setLoadingText("");
                    }
                }
            }
        } catch (e) {
            setMessages((m) => [
                ...m,
                { role: "assistant", text: e.message },
            ]);
            setIsStreaming(false);
            setLoadingText("");
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
                <div id="robodesk-bot-messages" className="robodesk-bot-message" ref={rootRef}>
                    {messages.length > 0 && messages.map((m, i) => {
                        const prevMsg = i > 0 ? messages[i - 1] : null;
                        const isSameSender = prevMsg && prevMsg.role === m.role;

                        return (
                            <div
                                key={i}
                                className={`chat-message rd-flex rd-items-start ${m.role === 'user'
                                    ? 'rd-ml-auto sent'
                                    : 'rd-flex rd-gap-8 received'
                                    } ${isSameSender ? 'rd-msg-same-sender' : 'rd-msg-new-sender'} ${m.role === 'user' ? 'rd-msg-user' : 'rd-msg-assistant'}`}
                                style={{
                                    maxWidth: '70%',
                                    marginBottom: isSameSender ? '4px' : '12px',
                                    marginTop: i === 0 || !isSameSender ? '12px' : '4px',
                                }}
                            >
                                {/* AI AVATAR */}
                                {m.role === 'assistant' && (!isSameSender || i === 0) && (
                                    botImage ? <img src={botImage} alt={botName} className="rd-bot-image rd-rounded-full rd-object-cover rd-single-message-avatar" /> : <div className="rd-bot-image rd-rounded-full rd-object-cover rd-single-message-avatar">{botName}</div>
                                )}

                                {/* AI AVATAR SPACER */}
                                {m.role === 'assistant' && isSameSender && i !== 0 && (
                                    <div style={{ width: '30px', height: '30px' }} />
                                )}

                                <div
                                    className="message-content"
                                    style={{
                                        backgroundColor: m.role === 'user' ? '#007BFF' : '#FFFFFF',
                                        color: m.role === 'user' ? '#FFFFFF' : '#000000',
                                        border: m.role === 'assistant' ? '1px solid #E0E0E0' : 'none',
                                        borderRadius: '10px',
                                        padding: '8px 12px',
                                        maxWidth: '100%',
                                    }}
                                >
                                    {m.role === 'assistant' ? (
                                        <AssistantMessage msg={m} />
                                    ) : (
                                        <span className="rd-body-text single-message"><p>{m.text}</p></span>
                                    )}
                                    {!m.thinking && (
                                        <div
                                            className="rd-text-small rd-text-gray"
                                            style={{
                                                marginTop: '4px',
                                                textAlign: m.role === 'user' ? 'right' : 'left',
                                                fontSize: '10px',
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
                            <h4 className='heading-4'>{chatHeading}</h4>
                            <h5 className='sub-text rd-mt-0'>{welcomeMessage}</h5>
                        </div>
                    )}

                    {/* STREAMING LOADER */}
                    {isStreaming && (
                        <div className="chat-message rd-flex rd-items-start rd-gap-8 received rd-streaming-loader" style={{ maxWidth: '70%' }}>
                            {botImage ? <img src={botImage} alt={botName} className="rd-single-message-avatar" /> : <div className="rd-bot-image rd-single-message-avatar">{botName}</div>}
                            <div className="message-content" style={{ border: '1px solid #E0E0E0', borderRadius: '10px', padding: '8px 12px' }}>
                                <span className="rd-body-text single-message">
                                    {loadingText || "Thinking..."}
                                </span>
                            </div>
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
                                    sendMessageStream();
                                }
                            }}
                        />
                        <div className='rd-flex rd-gap-20 rd-relative'>
                            <button
                                onClick={sendMessageStream}
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
