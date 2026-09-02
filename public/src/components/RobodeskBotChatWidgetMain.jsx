import React from "react";
import PropTypes from "prop-types";
import { useEffect, useRef, useState } from "@wordpress/element";
import { FaRegFaceSmileBeam } from "react-icons/fa6";
import { PiPaperPlaneRightFill } from "react-icons/pi";
import { FiPaperclip } from "react-icons/fi";
import EmojiPicker from "emoji-picker-react";
import DefaultAvatar from "../images/default-avatar.jpg";
import {
  START_CONVERSATION_EVENT,
  consumePendingInitialMessage,
  CHAT_SESSION_KEY,
} from "../utils/startConversation.js";

const escapeHtml = (unsafe) =>
  String(unsafe)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

const normalizeMessageText = (text) => {
  if (!text && text !== 0) return "";
  const str = String(text);
  const hasHtml = /<[^>]+>/.test(str);
  if (hasHtml) return str;

  let html = escapeHtml(str);

  // Markdown links [label](url) -> clickable anchor (url is HTML-escaped)
  html = html.replace(
    /\[([^\]]+)\]\((\S+?)\)/g,
    '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
  );

  // Bare URLs -> clickable anchor (skip URLs already inside href="...")
  html = html.replace(
    /(?<!href=")(https?:\/\/\S+)/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
  );

  return html.replace(/\r\n|\r|\n/g, "<br />");
};

/**
 * Ticking countdown shown on expiring action buttons (e.g. refund confirm).
 */
function Countdown({ seconds }) {
  const [left, setLeft] = useState(seconds);
  useEffect(() => {
    const t = setInterval(() => setLeft((s) => (s > 0 ? s - 1 : 0)), 1000);
    return () => clearInterval(t);
  }, []);
  const mm = String(Math.floor(left / 60)).padStart(1, "0");
  const ss = String(left % 60).padStart(2, "0");
  return <span className="rd-countdown">({mm}:{ss})</span>;
}

export default function RobodeskBotChatWidgetMain({
  widgetConfig,
  activeAIMode,
  onChatStart,
}) {
  const rootRef = useRef(null);
  const getUserKey = () =>
    window.robodeskAuth?.isLoggedIn === "true" && window.robodeskAuth?.loggedInUserId
      ? String(window.robodeskAuth.loggedInUserId)
      : "guest";
  const chatHeading =
    window.robodeskData?.chatHeading ||
    widgetConfig?.chatHeading ||
    "Have Questions?";
  const welcomeMessage =
    window.robodeskData?.welcomeMessage ||
    widgetConfig?.welcomeMessage ||
    "Have any questions or need a quote? Leave us a message.";
  const resolvedBotName =
    window.robodeskData?.robodesk_bot_title_name ||
    widgetConfig?.botTitleName ||
    widgetConfig?.bot_title_name ||
    "";
  const resolvedBotImage =
    window.robodeskData?.robodesk_bot_profile_image ||
    widgetConfig?.botProfileImage ||
    widgetConfig?.bot_profile_image ||
    "";
  const canLoadWidgetConfig = Boolean(
    window.robodeskData?.robodesk_addon_active &&
    window.robodeskData?.robodesk_ai_bot_active,
  );
  const [enabled, setEnabled] = useState(true);
  const [AIMode, setAIMode] = useState(activeAIMode || "");
  const [open, setOpen] = useState(false);
  const [messages, setMessages] = useState(() => {
    const currentUserKey = getUserKey();
    const saved = sessionStorage.getItem(CHAT_SESSION_KEY);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        // New format: { user: <userKey>, messages: [...] }.
        // Legacy format: a bare array (no user marker) -> treated as a guest chat.
        let storedMessages;
        let storedUser;
        if (Array.isArray(parsed)) {
          storedMessages = parsed;
          storedUser = "guest";
        } else if (parsed && typeof parsed === "object" && Array.isArray(parsed.messages)) {
          storedMessages = parsed.messages;
          storedUser = parsed.user || "guest";
        }
        if (storedMessages) {
          // Keep only when: same visitor, or a guest who has now logged in.
          // Clearing covers login -> logout and user -> different user.
          const keep =
            storedUser === currentUserKey ||
            (storedUser === "guest" && currentUserKey !== "guest");
          if (keep) {
            // Stably sort messages in chronological order by timestamp
            const withIndex = storedMessages.map((msg, idx) => ({ msg, idx }));
            withIndex.sort((a, b) => {
              const timeA = a.msg.timestamp ? new Date(a.msg.timestamp).getTime() : null;
              const timeB = b.msg.timestamp ? new Date(b.msg.timestamp).getTime() : null;
              if (timeA !== null && timeB !== null) {
                if (timeA !== timeB) {
                  return timeA - timeB;
                }
              }
              return a.idx - b.idx;
            });
            return withIndex.map(item => item.msg);
          }
        }
      } catch (e) {
        console.error("Error parsing saved chat session:", e);
      }
    }
    return [];
  });
  const [input, setInput] = useState("");
  const [loadingText, setLoadingText] = useState("");
  const [isStreaming, setIsStreaming] = useState(false);
  const [isCreatingTicket, setIsCreatingTicket] = useState(false);
  const [botName, setBotName] = useState(resolvedBotName);
  const [botImage, setBotImage] = useState(resolvedBotImage);
  const apiProxy = window.location.origin + "/wp-json/robodesk-bot/v1/chat";
  const apiProxyStream =
    window.location.origin + "/wp-json/robodesk-bot/v1/chat-stream";

  /**
   * ASSISTANT MESSAGE RENDERER
   */
  function AssistantMessage({ msg, msgIndex }) {
    return (
      <div className="ai-block">
        {/* MAIN MESSAGE TEXT */}
        {msg.message && (
          <div
            className="ai-text rd-body-text single-message"
            dangerouslySetInnerHTML={{
              __html: normalizeMessageText(msg.message),
            }}
          />
        )}

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
            {msg.ui.actions.map((a, i) => {
              const isUsed = a.used;
              return (
                <button
                  key={i}
                  disabled={isUsed}
                  onClick={() => {
                    if (a.action === "confirm-refund") {
                      confirmRefund(a.pending_id, msgIndex);
                      return;
                    }
                    if (a.prompt) setInput(a.prompt);
                    if (a.url) window.location.href = a.url;
                  }}
                >
                  {isUsed ? "Refund requested ✓" : a.label}
                  {a.expires_in && !isUsed ? <Countdown seconds={a.expires_in} /> : null}
                </button>
              );
            })}
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
    if (!canLoadWidgetConfig) return;
    console.log("fetchApiKey");
    try {
      const response = await wp.apiFetch({
        path: `/robodesk-bot/v1/widget-config`,
      });
      // Try both possible key formats your PHP might return
      setBotName(
        response.botName || response.bot_name || resolvedBotName || ""
      );
      setBotImage(
        response.botImage || response.bot_image || resolvedBotImage || ""
      );
    } catch (err) {
      console.error(err);
      setBotName((current) => current || resolvedBotName || "Bot");
      setBotImage((current) => current || resolvedBotImage || "");
    }
  };

  const getBotDisplayImage = () => botImage || resolvedBotImage || "";
  const getBotDisplayName = () => botName || resolvedBotName || "Bot";

  useEffect(() => {
    sessionStorage.setItem(
      CHAT_SESSION_KEY,
      JSON.stringify({ user: getUserKey(), messages }),
    );
  }, [messages]);

  /**
   * Action-2 notices: "Start a conversation with this message".
   */
  useEffect(() => {
    const pendingMessage = consumePendingInitialMessage();
    if (pendingMessage) {
      setInput(pendingMessage);
      sendMessageStream(pendingMessage);
    }
  }, [enabled]);

  useEffect(() => {
    const handleStartConversation = (e) => {
      const message = consumePendingInitialMessage() || e.detail?.message;
      if (!message) return;
      setInput(message);
      sendMessageStream(message);
    };

    window.addEventListener(START_CONVERSATION_EVENT, handleStartConversation);
    return () =>
      window.removeEventListener(
        START_CONVERSATION_EVENT,
        handleStartConversation,
      );
  }, [messages]);

  useEffect(() => {
    if (canLoadWidgetConfig) {
      fetchApiKey();
    }
  }, []);

  // Listen for ticket creation events
  useEffect(() => {
    const handleTicketCreation = (e) => {
      setIsCreatingTicket(e.detail?.isCreating ?? false);
      if (e.detail?.isCreating) {
        setLoadingText("Transitioning conversations from AI to human agents.");
      } else {
        setLoadingText("");
      }
    };

    window.addEventListener("robodesk:creatingTicket", handleTicketCreation);
    return () => window.removeEventListener("robodesk:creatingTicket", handleTicketCreation);
  }, []);

  // Configuration is now passed via props, no need to read from DOM

  /**
   * AUTO SCROLL LAST MESSAGE
   */
  useEffect(() => {
    if (rootRef.current) {
      rootRef.current.scrollTop = rootRef.current.scrollHeight;
    }
  }, [messages, isStreaming, loadingText]);

  useEffect(() => {
    if (messages.length > 0 && onChatStart) {
      onChatStart();
    }
  }, [messages.length, onChatStart]);

  if (!enabled) return null;

  /**
   * SEND MESSAGE
   */
  const sendMessage = async () => {
    const q = String(input ?? "").trim();
    if (!q) return;

    const now = new Date().toISOString();
    setMessages((m) => [...m, { role: "user", text: q, timestamp: now }]);
    setInput("");
    window.dispatchEvent(
      new CustomEvent("robodesk:messageSent", {
        detail: { count: messages.length + 1 },
      })
    );

    // add thinking placeholder
    setMessages((m) => [
      ...m,
      { role: "assistant", text: "...", thinking: true },
    ]);

    const restNonce = window.robodeskAuth?.restNonce || window.RobodeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";
    const headers = { "Content-Type": "application/json" };
    if (restNonce) {
      headers["X-WP-Nonce"] = restNonce;
    }

    try {
      const resp = await fetch(apiProxy, {
        method: "POST",
        headers,
        body: JSON.stringify({
          query: q,
          history: messages.slice(-15).map(m => ({
            role: m.role,
            content: m.role === "user" ? (m.text || m.message || "") : (m.message || m.text || "")
          }))
        }),
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
          timestamp: new Date().toISOString(),
        },
      ]);
    } catch (e) {
      setMessages((m) => [
        ...m.filter((x) => !x.thinking),
        { role: "assistant", message: e.message, timestamp: new Date().toISOString() },
      ]);
    }
  };

  const sendMessageStream = async (overrideText = null) => {
    // Coerce to string: this is also invoked from onClick, which passes a
    // DOM event as the first argument — never let .trim() blow up.
    const q = String(overrideText ?? input ?? "").trim();
    if (!q) return;

    const now = new Date().toISOString();
    setMessages((m) => [...m, { role: "user", text: q, timestamp: now }]);
    setInput("");
    window.dispatchEvent(
      new CustomEvent("robodesk:messageSent", {
        detail: { count: messages.length + 1 },
      })
    );
    setIsStreaming(true);
    setLoadingText("...");

    const restNonce = window.robodeskAuth?.restNonce || window.RobodeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";
    const headers = { "Content-Type": "application/json" };
    if (restNonce) {
      headers["X-WP-Nonce"] = restNonce;
    }

    try {
      const resp = await fetch(apiProxyStream, {
        method: "POST",
        headers,
        body: JSON.stringify({
          query: q,
          history: messages.slice(-15).map(m => ({
            role: m.role,
            content: m.role === "user" ? (m.text || m.message || "") : (m.message || m.text || "")
          }))
        }),
      });

      if (!resp.ok) {
        let msg = `Request failed (${resp.status})`;
        try {
          const j = await resp.json();
          if (j && j.message) msg = j.message;
        } catch (e) {
          /* ignore — fall back to status text */
        }
        throw new Error(msg);
      }

      if (!resp.body) throw new Error("Streaming not supported");

      const reader = resp.body.getReader();
      const decoder = new TextDecoder("utf-8");

      let buffer = "";
      let gotReply = false;

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
          if (eventName.startsWith("thinking")) {
            // console.log("Received thinking event:", eventName);
            setLoadingText(parsed.message);
          }

          // final message
          if (eventName === "Final Response:") {
            // console.log("Received final response event:", parsed);
            let finalText = "";

            if (typeof parsed.message === "string") {
              finalText = parsed.message;
            } else if (parsed.message && typeof parsed.message === "object") {
              // If message is an object, try to get text or response field
              finalText = parsed.message?.text;
            } else if (parsed.response && typeof parsed.response === "object") {
              finalText =
                parsed.response.text ||
                parsed.response.message ||
                JSON.stringify(parsed.response);
            } else if (typeof parsed.response === "string") {
              finalText = parsed.response;
            } else {
              finalText = "";
            }
            // console.log("Final parsed response:", finalText);
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
                timestamp: new Date().toISOString(),
              },
            ]);

            setIsStreaming(false);
            setLoadingText("");
            gotReply = true;
          }
        }
      }

      // Stream ended without a final reply (e.g. server time-limit hit): show
      // a fallback and clear the loading state so the input isn't stuck.
      if (!gotReply) {
        setMessages((m) => [
          ...m,
          {
            role: "assistant",
            message: "I didn't get a response. Please try again.",
            timestamp: new Date().toISOString(),
          },
        ]);
        setIsStreaming(false);
        setLoadingText("");
      }
    } catch (e) {
      setMessages((m) => [...m, { role: "assistant", text: e.message, timestamp: new Date().toISOString() }]);
      setIsStreaming(false);
      setLoadingText("");
    }
  };

  /**
   * Confirm a pending refund (triggered by the "Confirm refund" action button).
   * Marks the clicked button as used in the message it belongs to, so it can't
   * be double-clicked.
   */
  const confirmRefund = async (pendingId, msgIndex = null) => {
    if (!pendingId) return;
    // Grey out the clicked button immediately.
    if (msgIndex !== null) {
      setMessages((m) => m.map((msg, idx) => (
        idx === msgIndex
          ? { ...msg, ui: { ...msg.ui, actions: (msg.ui?.actions || []).map((a) => (a.pending_id === pendingId ? { ...a, used: true } : a)) } }
          : msg
      )));
    }
    setIsStreaming(true);
    setLoadingText("...");
    const restNonce = window.robodeskAuth?.restNonce || window.RobodeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";
    try {
      const resp = await fetch("/wp-json/robodesk-bot/v1/confirm-refund", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": restNonce },
        body: JSON.stringify({ pending_id: pendingId }),
      });
      const data = await resp.json();
      let message = data.status === "refund_requested"
        ? "Refund request submitted. Our team will review and process it shortly."
        : (data.message || data.error || "Refund could not be processed.");
      setMessages((m) => [...m, { role: "assistant", message, timestamp: new Date().toISOString() }]);
    } catch (e) {
      setMessages((m) => [...m, { role: "assistant", message: e.message, timestamp: new Date().toISOString() }]);
    } finally {
      setIsStreaming(false);
      setLoadingText("");
    }
  };

  return (
    <>
      <div id="robodesk-bot-panel">
        <style>{`
          #robodesk-bot-panel a,
          #robodesk-bot-panel .message-content a,
          #robodesk-bot-panel .ai-text a {
            color: #1a73e8 !important;
            font-weight: 600 !important;
            text-decoration: underline !important;
            text-underline-offset: 2px;
            cursor: pointer;
          }
          #robodesk-bot-panel a:hover {
            color: #0b5ed7 !important;
          }
          #robodesk-bot-panel .ai-actions button,
          #robodesk-bot-panel .ai-suggestions button {
            background: #1a73e8 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 8px;
            padding: 9px 18px;
            font-weight: 600 !important;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(26, 115, 232, 0.35);
            transition: background .15s ease, transform .15s ease, box-shadow .15s ease;
            display: inline-block;
            margin: 4px 6px 4px 0;
          }
          #robodesk-bot-panel .ai-actions button:hover,
          #robodesk-bot-panel .ai-suggestions button:hover {
            background: #0b5ed7 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(26, 115, 232, 0.45);
          }
          #robodesk-bot-panel .ai-actions button:disabled,
          #robodesk-bot-panel .ai-suggestions button:disabled {
            background: #9e9e9e !important;
            color: #ffffff !important;
            cursor: not-allowed !important;
            box-shadow: none;
            transform: none;
            opacity: 0.85;
          }
          #robodesk-bot-panel .rd-countdown {
            font-weight: 400 !important;
            font-size: 12px;
            margin-left: 6px;
            opacity: 0.9;
          }
          #robodesk-bot-panel .ai-actions,
          #robodesk-bot-panel .ai-suggestions {
            margin-top: 8px;
          }
        `}</style>
        {/* <div id="robodesk-bot-header">
                    <strong>AI Assistant ({AIMode})</strong>
                    <button onClick={() => setOpen(false)}>x</button>
                </div> */}

        {/* MESSAGES */}
        <div
          id="robodesk-bot-messages"
          className="robodesk-bot-message"
          ref={rootRef}
        >
          {messages.length > 0 &&
            messages.map((m, i) => {
              const prevMsg = i > 0 ? messages[i - 1] : null;
              const isSameSender = prevMsg && prevMsg.role === m.role;

              return (
                <div
                  key={i}
                  className={`chat-message rd-flex rd-items-start ${m.role === "user"
                    ? "rd-ml-auto sent"
                    : "rd-flex rd-gap-8 received"
                    } ${isSameSender ? "rd-msg-same-sender" : "rd-msg-new-sender"
                    } ${m.role === "user" ? "rd-msg-user" : "rd-msg-assistant"}`}
                  style={{
                    maxWidth: "70%",
                    marginBottom: isSameSender ? "4px" : "12px",
                    marginTop: i === 0 || !isSameSender ? "12px" : "4px",
                  }}
                >
                  {/* AI AVATAR */}
                  {m.role === "assistant" &&
                    (!isSameSender || i === 0) &&
                    (getBotDisplayImage() ? (
                      <img
                        src={getBotDisplayImage()}
                        alt={getBotDisplayName()}
                        className="rd-bot-image rd-rounded-full rd-object-cover rd-single-message-avatar"
                        onError={(e) => {
                          e.target.onerror = null;
                          e.target.src = DefaultAvatar;
                        }}
                      />
                    ) : (
                      <div className="rd-bot-image rd-rounded-full rd-object-cover rd-single-message-avatar">
                        {getBotDisplayName().trim().charAt(0).toUpperCase()}
                      </div>
                    ))}

                  {/* AI AVATAR SPACER */}
                  {m.role === "assistant" && isSameSender && i !== 0 && (
                    <div
                      style={{ width: "30px", height: "30px", flexShrink: 0 }}
                    />
                  )}

                  <div
                    className="message-content"
                    style={{
                      backgroundColor:
                        m.role === "user" ? "#007BFF" : "#FFFFFF",
                      color: m.role === "user" ? "#FFFFFF" : "#000000",
                      border:
                        m.role === "assistant" ? "1px solid #E0E0E0" : "none",
                      borderRadius: "10px",
                      padding: "8px 12px",
                      width: "100%",
                      maxWidth: "100%",
                    }}
                  >
                    {m.role === "assistant" ? (
                      <AssistantMessage msg={m} msgIndex={i} />
                    ) : (
                      <span
                        className="rd-body-text single-message"
                        dangerouslySetInnerHTML={{
                          __html: normalizeMessageText(m.text),
                        }}
                      />
                    )}
                    {!m.thinking && (
                      <div
                        className="rd-text-small rd-text-gray"
                        style={{
                          marginTop: "4px",
                          textAlign: m.role === "user" ? "right" : "left",
                          fontSize: "10px",
                        }}
                      >
                        {new Date(
                          m.timestamp || new Date().toISOString()
                        ).toLocaleTimeString([], {
                          hour: "2-digit",
                          minute: "2-digit",
                        })}
                      </div>
                    )}
                  </div>
                </div>
              );
            })}

          {messages.length === 0 && (
            <div className="rd-text-center rd-px-20 rd-flex rd-flex-col rd-gap-5 rd-mb-20 home-tab-title">
              <h4 className="heading-4">{chatHeading}</h4>
              <h5 className="sub-text rd-mt-0">{welcomeMessage}</h5>
            </div>
          )}

          {/* STREAMING LOADER */}
          {(isStreaming || isCreatingTicket) && (
            <div
              className="chat-message rd-flex rd-items-start rd-gap-8 received rd-streaming-loader"
              style={{ maxWidth: "70%" }}
            >
              <style>{`
                @keyframes rd-typing-pulse { 0%,100%{ opacity:.3; } 50%{ opacity:1; } }
                .rd-typing-ellipsis { display:inline-block; min-width:28px; text-align:left; font-size:20px; font-weight:700; line-height:1; letter-spacing:2px; animation: rd-typing-pulse 1.2s ease-in-out infinite; }
              `}</style>
              {getBotDisplayImage() ? (
                <img
                  src={getBotDisplayImage()}
                  alt={getBotDisplayName()}
                  className="rd-single-message-avatar"
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = DefaultAvatar;
                  }}
                />
              ) : (
                <div className="rd-bot-image rd-single-message-avatar">
                  {getBotDisplayName().trim().charAt(0).toUpperCase()}
                </div>
              )}
              <div
                className="message-content"
                style={{
                  border: "1px solid #E0E0E0",
                  borderRadius: "10px",
                  padding: "8px 12px",
                  display: "inline-flex",
                  alignItems: "center",
                  gap: "8px",
                }}
              >
                <span
                  className="rd-typing-ellipsis rd-body-text single-message"
                  role="status"
                  aria-label="Bot is typing"
                >
                  ...
                </span>
                {loadingText && loadingText !== "..." && (
                  <span className="rd-body-text single-message">
                    {loadingText}
                  </span>
                )}
              </div>
            </div>
          )}
        </div>

        {/* INPUT */}
        <div
          id="robodesk-bot-input"
          className="chat-input rd-flex rd-px-18 rd-py-15 rd-border-top-light rd-border-bottom-light rd-bg-white rd-justify-between"
        >
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
            <div className="rd-flex rd-gap-20 rd-relative">
              <button
                onClick={() => sendMessageStream()}
                className="rd-bg-transparent rd-border-none rd-ml-10 icon-dark rd-cursor-pointer rd-p-0"
              >
                <PiPaperPlaneRightFill className="icon-dark" size={20} />
              </button>
            </div>
          </>
        </div>
      </div>
    </>
  );
}
