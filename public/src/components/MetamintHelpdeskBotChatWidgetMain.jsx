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

/**
 * Only these URL shapes may become an href. Anything else (javascript:, data:,
 * vbscript:, ...) is rendered as plain text so a model answer can never inject a
 * script or a hostile scheme into the DOM.
 */
const ALLOWED_HREF = /^(https?:\/\/|\/|#)/i;

/**
 * Decode HTML entities just far enough to judge a URL scheme, so an entity
 * encoded `java&#115;cript:` cannot sneak past the allow list.
 */
const decodeEntities = (value) =>
  String(value).replace(/&(#[xX]?[0-9a-fA-F]+|\d+);?/g, (match, body) => {
    const isHex = body[0] === "#" && (body[1] === "x" || body[1] === "X");
    const code = isHex ? parseInt(body.slice(2), 16) : parseInt(body.slice(1), 10);
    if (!Number.isFinite(code) || code <= 0 || code > 0x10ffff) return match;
    try {
      return String.fromCodePoint(code);
    } catch (e) {
      return match;
    }
  });

/**
 * @param {string} raw Already HTML escaped URL.
 * @return {string|null} A safe href, or null when the URL must not be linked.
 */
const safeHref = (raw) => {
  const url = String(raw || "").trim();
  if (!url) return null;

  // Whitespace and control characters are ignored by browsers when resolving a
  // scheme, so strip them before deciding, then encode them away for real.
  const probe = decodeEntities(url)
    .replace(/[\s\u0000-\u001f\u007f]+/g, "")
    .toLowerCase();

  if (!ALLOWED_HREF.test(probe)) return null;

  return url.replace(/[\s\u0000-\u001f\u007f]+/g, "%20");
};

/**
 * Render inline markdown on text that has already been HTML escaped, so no
 * markup from the model can survive as a live tag.
 */
const renderInline = (text) => {
  const codes = [];

  // Protect code spans first so their contents stay literal.
  let out = String(text).replace(/`([^`]+)`/g, (match, code) => {
    codes.push(code);
    return `\u0001c${codes.length - 1}\u0001`;
  });

  // [label](url)
  // Parentheses may appear inside a URL, so allow one level of nesting rather
  // than stopping at the first ")" and leaving it behind as stray text.
  out = out.replace(/\[([^\]]+)\]\(([^()]*(?:\([^()]*\)[^()]*)*)\)/g, (match, label, href) => {
    const url = safeHref(href);
    return url
      ? `<a href="${url}" target="_blank" rel="noopener noreferrer">${label}</a>`
      : label;
  });

  // Bare URLs. The leading group keeps us out of href="..." values we just wrote.
  out = out.replace(
    /(^|[\s([{])((?:https?:\/\/|www\.)[^\s<>()]+)/g,
    (match, before, url) => {
      const trimmed = url.replace(/[.,!?;:]+$/, "");
      if (!trimmed) return match;
      const tail = url.slice(trimmed.length);
      const href = /^www\./i.test(trimmed) ? `https://${trimmed}` : trimmed;
      return `${before}<a href="${href}" target="_blank" rel="noopener noreferrer">${trimmed}</a>${tail}`;
    },
  );

  out = out
    .replace(/\*\*\*([^*\n]+)\*\*\*/g, "<strong><em>$1</em></strong>")
    .replace(/\*\*([^*\n]+)\*\*/g, "<strong>$1</strong>")
    .replace(/(^|[^*\w])\*([^*\s][^*\n]*?)\*(?!\w)/g, (m, before, body) => `${before}<em>${body}</em>`)
    .replace(/(^|[^\w\\])_([^_\s][^_\n]*?)_(?!\w)/g, (m, before, body) => `${before}<em>${body}</em>`)
    .replace(/~~([^\n]+?)~~/g, "<del>$1</del>");

  return out.replace(/\u0001c(\d+)\u0001/g, (match, index) => `<code>${codes[Number(index)]}</code>`);
};

/**
 * Width of a line's leading whitespace, counting a tab as up to 4 columns so
 * tabs and spaces indent nested lists identically.
 */
const indentWidth = (line) => {
  let width = 0;
  for (const ch of line) {
    if (ch === "\t") width += 4 - (width % 4);
    else if (ch === " ") width += 1;
    else break;
  }
  return width;
};

/**
 * How many columns `text` spans when it starts at `startCol`, so an item's
 * content column accounts for the marker ("1. " is three columns, not zero).
 */
const visualWidth = (text, startCol = 0) => {
  let width = startCol;
  for (const ch of text) {
    if (ch === "\t") width += 4 - (width % 4);
    else width += 1;
  }
  return width;
};

// A list item marker with content. "- " with nothing after it is not a list.
const LIST_ITEM = /^(\s*)([-*+]|\d{1,9}[.)])(\s+)(.*)$/;
const isListItem = (line) => {
  const m = LIST_ITEM.exec(line);
  return !!m && m[4].trim() !== "";
};
const HR = /^\s*(?:-{3,}|\*{3,}|_{3,})\s*$/;

/**
 * Parse one list (and any lists nested inside it) starting at `start`.
 * @return {{html: string, next: number}}
 */
const parseList = (lines, start, indent) => {
  const first = LIST_ITEM.exec(lines[start]);
  const ordered = /\d/.test(first[2]);
  const closer = ordered ? "</ol>" : "</ul>";
  let html = ordered ? "<ol>" : "<ul>";
  let i = start;

  while (i < lines.length) {
    const line = lines[i];

    // A blank line only ends the list when the same list does not continue.
    if (line.trim() === "") {
      let j = i;
      while (j < lines.length && lines[j].trim() === "") j += 1;
      if (j >= lines.length) {
        i = j;
        break;
      }
      const cont = LIST_ITEM.exec(lines[j]);
      if (!cont || indentWidth(lines[j]) !== indent || /\d/.test(cont[2]) !== ordered) {
        i = j;
        break;
      }
      i = j;
      continue;
    }

    const m = LIST_ITEM.exec(line);
    if (!m || indentWidth(line) !== indent || /\d/.test(m[2]) !== ordered) break;

    // Column where this item's text starts, i.e. past its marker.
    const contentIndent = visualWidth(m[2] + m[3], indent);
    const parts = [m[4]];
    let j = i + 1;

    while (j < lines.length) {
      const nested = lines[j];
      if (nested.trim() === "") break;

      const li = indentWidth(nested);
      const nm = LIST_ITEM.exec(nested);

      if (nm) {
        if (li >= contentIndent) {
          const sub = parseList(lines, j, li);
          parts.push({ list: sub.html });
          j = sub.next;
          continue;
        }
        break; // sibling item, or back out to an outer list
      }

      if (li >= contentIndent) {
        parts.push({ br: nested.trim() });
      } else {
        parts.push({ join: nested.trim() }); // lazy continuation
      }
      j += 1;
    }

    let body = "";
    parts.forEach((part, idx) => {
      if (typeof part === "string") body += (idx ? "<br />" : "") + renderInline(part);
      else if (part.br !== undefined) body += `<br />${renderInline(part.br)}`;
      else if (part.join !== undefined) body += ` ${renderInline(part.join)}`;
      else body += part.list;
    });

    html += `<li>${body}</li>`;
    i = j;
  }

  return { html: `${html}${closer}`, next: i };
};

/**
 * Block level markdown: headings, rules, lists and paragraphs.
 */
const renderMarkdown = (text) => {
  const lines = String(text).replace(/\r\n?/g, "\n").split("\n");
  let html = "";
  let i = 0;

  while (i < lines.length) {
    const line = lines[i];
    if (line.trim() === "") {
      i += 1;
      continue;
    }

    const heading = /^(#{1,6})\s+(.*)$/.exec(line);
    if (heading) {
      const level = heading[1].length;
      html += `<h${level}>${renderInline(heading[2].trim())}</h${level}>`;
      i += 1;
      continue;
    }

    if (HR.test(line)) {
      html += "<hr />";
      i += 1;
      continue;
    }

    if (isListItem(line)) {
      const list = parseList(lines, i, indentWidth(line));
      html += list.html;
      i = list.next;
      continue;
    }

    const paragraph = [];
    while (i < lines.length && lines[i].trim() !== "") {
      const l = lines[i];
      if (paragraph.length && (/^(#{1,6})\s/.test(l) || HR.test(l) || isListItem(l))) break;
      paragraph.push(l.trim());
      i += 1;
    }
    if (paragraph.length) html += `<p>${paragraph.map(renderInline).join("<br />")}</p>`;
  }

  return html;
};

/**
 * Turn a model answer into safe HTML. Everything is escaped first and the
 * markdown we emit is the only markup in the result, so model output can never
 * inject scripts, event handlers or hostile URLs.
 */
const normalizeMessageText = (text) => {
  if (!text && text !== 0) return "";
  const str = String(text).replace(/\r\n?/g, "\n");
  if (!str.trim()) return "";
  return renderMarkdown(escapeHtml(str));
};

/**
 * Cart requests are answered from the browser's own Store API session, because
 * that is the only place the customer's cart actually exists. These cover the
 * phrasings we answer directly so the model never has to guess at cart contents.
 */
const CART_REMOVE_RE = /\b(?:remove|delete|get rid of|take out|take off|drop)\b\s+(.+)/i;
const CART_MENTION_RE = /\b(?:cart|basket)\b/i;
const CART_REMOVE_PRONOUN_RE = /^(?:it|this|that|these|those|them|one|the item|the items?|an item|the product|the products?|my item|everything|all(?: of them)?|all items|all products)$/i;
const CART_VIEW_RE = /\b(?:what(?:['\u2019]?s| is)? in (?:my|the) (?:cart|basket)|(?:cart|basket) (?:contents|summary|items|list)|(?:show|view|open|see|display|list) (?:me )?(?:my |the )?(?:cart|basket))\b/i;

const TALK_TO_HUMAN_PATTERN = /talk\s+(?:to\s+)?human|speak\s+(?:to\s+)?agent|connect\s+(?:to\s+)?support|escalate|human\s+support|talk\s+(?:to\s+)?someone|customer\s+service/i;

/**
 * Ticking countdown shown on expiring action buttons (e.g. refund confirm).
 * Reports back once when it reaches zero so the button can be disabled rather
 * than left clickable against an intent the server has already dropped.
 */
function Countdown({ seconds, onExpire }) {
  const [left, setLeft] = useState(seconds);
  useEffect(() => {
    const t = setInterval(() => setLeft((s) => (s > 0 ? s - 1 : 0)), 1000);
    return () => clearInterval(t);
  }, []);
  useEffect(() => {
    if (left === 0 && onExpire) onExpire();
  }, [left, onExpire]);
  const mm = String(Math.floor(left / 60)).padStart(1, "0");
  const ss = String(left % 60).padStart(2, "0");
  return <span className="mmhd-countdown">({mm}:{ss})</span>;
}

/**
 * ASSISTANT MESSAGE RENDERER
 * Hoisted to module level so its identity stays stable across re-renders of the
 * widget. Defining it inside the parent component caused React to unmount/remount
 * it (and any child Countdown timers) on every render, resetting the countdown.
 */
function AssistantMessage({
  msg,
  msgIndex,
  setInput,
  setMessages,
  markActionUsed,
  addToCart,
  removeFromCart,
  confirmRefund,
  pushAssistant,
  expiredActions,
  setExpiredActions,
}) {
  return (
    <div className="ai-block">
      {/* MAIN MESSAGE TEXT */}
      {msg.message && (
        <div
          className="ai-text mmhd-body-text single-message"
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
          {msg.ui.suggestions.map((s, i) => {
            const isTalkToHuman =
              s.type === "talk-to-human" ||
              /talk.?human|speak.?agent|connect.?support|escalate/i.test(
                s.label || s.prompt || "",
              );

            if (isTalkToHuman && talkToHuman) {
              return (
                <button
                  key={i}
                  className="mmhd-m-5"
                  onClick={() => convertToTicket()}
                  style={{
                    border: "none",
                    background: "transparent",
                    padding: "0",
                    cursor: "pointer",
                    display: "inline-flex",
                    alignItems: "center",
                  }}
                >
                  <div
                    className="icon-dark custom-svg mmhd-cursor-pointer"
                    onClick={() => convertToTicket()}
                  >
                    <svg
                      stroke="currentColor"
                      fill="currentColor"
                      stroke-width="0"
                      viewBox="0 0 512 512"
                      height="1em"
                      width="1em"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm80 224h-64v64a16 16 0 0 1-32 0v-64h-64a16 16 0 0 1 0-32h64v-64a16 16 0 0 1 32 0v64h64a16 16 0 0 1 0 32z"></path>
                    </svg>
                  </div>
                </button>
              );
            }

            return (
              <button
                key={i}
                className="chip"
                onClick={() => setInput(s.prompt || s.label || "")}
              >
                {s.label || s.prompt}
              </button>
            );
          })}
        </div>
      )}

      {/* ACTIONS (link / deep actions) */}
      {msg.ui?.actions?.length > 0 && (
        <div className="ai-actions">
          {msg.ui.actions.map((a, i) => {
            const isUsed = a.used;
            const isExpired = Boolean(a.pending_id) && expiredActions.includes(a.pending_id);
            const isTalkToHuman =
              a.type === "talk-to-human" ||
              /talk.?human|speak.?agent|connect.?support|escalate/i.test(
                a.label || "",
              );

            if (isTalkToHuman && talkToHuman) {
              return (
                <button
                  key={i}
                  className="mmhd-m-5"
                  onClick={() => convertToTicket()}
                  style={{
                    border: "none",
                    background: "transparent",
                    padding: "0",
                    cursor: "pointer",
                    display: "inline-flex",
                    alignItems: "center",
                  }}
                >
                  <div
                    className="icon-dark custom-svg mmhd-cursor-pointer"
                    onClick={() => convertToTicket()}
                  >
                    <svg
                      stroke="currentColor"
                      fill="currentColor"
                      stroke-width="0"
                      viewBox="0 0 512 512"
                      height="1em"
                      width="1em"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm80 224h-64v64a16 16 0 0 1-32 0v-64h-64a16 16 0 0 1 0-32h64v-64a16 16 0 0 1 32 0v64h64a16 16 0 0 1 0 32z"></path>
                    </svg>
                  </div>
                </button>
              );
            }

            return (
              <button
                key={i}
                disabled={isUsed || isExpired}
                onClick={() => {
                  if (a.action === "confirm-refund") {
                    confirmRefund(a.pending_id, msgIndex);
                    return;
                  }
                  if (a.action === "add-to-cart") {
                    addToCart(a.pending_id, msgIndex);
                    return;
                  }
                  if (a.action === "remove-from-cart") {
                    removeFromCart(a.pending_id, msgIndex);
                    return;
                  }
                  if (a.prompt) setInput(a.prompt);
                  if (a.url) window.location.href = a.url;
                }}
              >
                {isUsed ? `${a.label} ✓` : isExpired ? `${a.label} (expired)` : a.label}
                {a.expires_in && !isUsed && !isExpired ? (
                  <Countdown
                    seconds={a.expires_in}
                    onExpire={() =>
                      setExpiredActions((p) =>
                        p.includes(a.pending_id) ? p : [...p, a.pending_id]
                      )
                    }
                  />
                ) : null}
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
  msgIndex: PropTypes.number,
  setInput: PropTypes.func.isRequired,
  setMessages: PropTypes.func.isRequired,
  markActionUsed: PropTypes.func.isRequired,
  addToCart: PropTypes.func.isRequired,
  removeFromCart: PropTypes.func.isRequired,
  confirmRefund: PropTypes.func.isRequired,
  pushAssistant: PropTypes.func.isRequired,
  expiredActions: PropTypes.arrayOf(PropTypes.string).isRequired,
  setExpiredActions: PropTypes.func.isRequired,
};

export default function MetamintHelpdeskBotChatWidgetMain({
  widgetConfig,
  activeAIMode,
  onChatStart,
}) {
  const rootRef = useRef(null);
  const getUserKey = () =>
    window.mmhdAuth?.isLoggedIn === "true" && window.mmhdAuth?.loggedInUserId
      ? String(window.mmhdAuth.loggedInUserId)
      : "guest";
  const chatHeading =
    window.mmhdData?.chatHeading ||
    widgetConfig?.chatHeading ||
    "Have Questions?";
  const welcomeMessage =
    window.mmhdData?.welcomeMessage ||
    widgetConfig?.welcomeMessage ||
    "Have any questions or need a quote? Leave us a message.";
  const resolvedBotName =
    window.mmhdData?.mmhd_bot_title_name ||
    widgetConfig?.botTitleName ||
    widgetConfig?.bot_title_name ||
    "";
  const resolvedBotImage =
    window.mmhdData?.mmhd_bot_profile_image ||
    widgetConfig?.botProfileImage ||
    widgetConfig?.bot_profile_image ||
    "";
  const canLoadWidgetConfig = Boolean(
    window.mmhdData?.mmhd_addon_active &&
    window.mmhdData?.mmhd_ai_bot_active,
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
  const [expiredActions, setExpiredActions] = useState([]);
  const [botName, setBotName] = useState(resolvedBotName);
  const [botImage, setBotImage] = useState(resolvedBotImage);
  const [talkToHuman, setTalkToHuman] = useState(
    () => Boolean(window.MetamintHelpdeskBotConfig?.talkToHuman)
  );
  const [ticketCreated, setTicketCreated] = useState(false);
  const [ticketId, setTicketId] = useState(null);
  const apiProxy = window.location.origin + "/wp-json/mmhd-bot/v1/chat";
  const apiProxyStream =
    window.location.origin + "/wp-json/mmhd-bot/v1/chat-stream";


  const fetchApiKey = async () => {
    if (!canLoadWidgetConfig) return;
    console.log("fetchApiKey");
    try {
      const headers = { "Content-Type": "application/json" };
      const nonce = window.mmhdAuth?.restNonce || window.wpApiSettings?.nonce || "";
      if (nonce) headers["X-WP-Nonce"] = nonce;
      const response = await fetch("/wp-json/mmhd-bot/v1/widget-config", { headers });
      const data = await response.json();
      if (response.ok && data) {
        setBotName(data.botName || data.bot_name || resolvedBotName || "");
        setBotImage(data.botImage || data.bot_image || resolvedBotImage || "");
        setTalkToHuman(!!data.talkToHuman);
      }
    } catch (err) {
      console.error(err);
      setBotName((current) => current || resolvedBotName || "Bot");
      setBotImage((current) => current || resolvedBotImage || "");
      // Fallback: try to read from localized script config
      setTalkToHuman(!!window.MetamintHelpdeskBotConfig?.talkToHuman);
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

    window.addEventListener("mmhd:creatingTicket", handleTicketCreation);
    return () => window.removeEventListener("mmhd:creatingTicket", handleTicketCreation);
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
      new CustomEvent("mmhd:messageSent", {
        detail: { count: messages.length + 1 },
      })
    );

    // add thinking placeholder
    setMessages((m) => [
      ...m,
      { role: "assistant", text: "...", thinking: true },
    ]);

    const restNonce = window.mmhdAuth?.restNonce || window.MetamintHelpdeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";
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
 
  /**
   * Format a Store API minor-unit amount using the currency metadata the API
   * itself provides, so the widget shows the same symbol and grouping as the
   * server-rendered confirmation ("₨ 45.50") instead of Intl's "NPR 45.50".
   */
  const storeMoney = (fields, amount) => {
    if (amount === undefined || amount === null || amount === "") return "";
    const minor = Number(fields?.currency_minor_unit ?? 2);
    const fixed = (Number(amount) / 10 ** minor).toFixed(minor);
    const [intPart, decPart] = fixed.split(".");
    const sep = fields?.currency_thousand_separator ?? ",";
    const dec = fields?.currency_decimal_separator ?? ".";
    const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    const body = decPart !== undefined ? `${grouped}${dec}${decPart}` : grouped;
    return `${fields?.currency_prefix ?? ""}${body}${fields?.currency_suffix ?? ""}`;
  };

  /**
   * Fetch and display the current cart contents from the Store API.
   *
   * @param {boolean} withActions When false, omit the View cart / Checkout
   *   buttons. Used after an add, where the confirmation message already
   *   renders them, so the buttons are not shown twice.
   */
  const viewCart = async (withActions = true) => {
    setIsStreaming(true);
    setLoadingText("...");

    const pushMessage = (message, actions = []) => {
      setMessages((m) => [
        ...m,
        { role: "assistant", message, timestamp: new Date().toISOString(), ui: actions.length ? { actions } : undefined },
      ]);
    };

    try {
      const resp = await fetch("/wp-json/wc/store/v1/cart");
      if (!resp.ok) {
        pushMessage("Sorry, I couldn't retrieve your cart at the moment.");
        return;
      }
      const data = await resp.json();

      const totalsData = data.totals || {};
      const items = data.items || [];
      if (!items.length) {
        pushMessage("Your cart is currently empty. Let me know if you'd like to add something!");
        return;
      }

      const lines = items.map((item) => {
        const qty = item.quantity || 1;
        const name = item.name || "Item";
        const price = storeMoney(item.totals || totalsData, item.totals?.line_total ?? item.prices?.price);
        return `${qty} × ${name}${price ? ` — ${price}` : ""}`;
      });

      const totalDisplay = storeMoney(totalsData, totalsData.total_price);

      const summary = lines.join("\n");
      pushMessage(
        `Your cart has ${items.reduce((sum, i) => sum + (i.quantity || 1), 0)} ${items.length === 1 ? "item" : "items"}:\n${summary}${totalDisplay ? `\n\nTotal: ${totalDisplay}` : ""}`,
        withActions
          ? [
              { action: "link", label: "View cart", url: "/cart/" },
              { action: "link", label: "Checkout", url: "/checkout/" },
            ]
          : [],
      );
    } catch (e) {
      pushMessage("Sorry, I couldn't retrieve your cart at the moment.");
    } finally {
      setIsStreaming(false);
      setLoadingText("");
    }
  };

/**
   * Helper: mark a pending action button as used so it cannot be clicked twice.
   */
  const markActionUsed = (pendingId, msgIndex = null) => {
    if (msgIndex === null || !pendingId) return;
    setMessages((m) => m.map((msg, idx) => (
      idx === msgIndex
        ? { ...msg, ui: { ...msg.ui, actions: (msg.ui?.actions || []).map((a) => (a.pending_id === pendingId ? { ...a, used: true } : a)) } }
        : msg
    )));
  };

  const pushAssistant = (message, actions = []) => {
    setMessages((m) => [
      ...m,
      { role: "assistant", message, timestamp: new Date().toISOString(), ui: actions.length ? { actions } : undefined },
    ]);
  };

  const restNonce = () =>
    window.mmhdAuth?.restNonce || window.MetamintHelpdeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";

  /**
   * Handle "remove <item> from my cart".
   *
   * The cart lives in the visitor's browser session, so the item is resolved here
   * from the Store API and then staged server side. Nothing is removed until the
   * customer clicks the confirmation button.
   */
  const removeFromCartByName = async (query) => {
    setIsStreaming(true);
    setLoadingText("...");

    try {
      const cartResp = await fetch("/wp-json/wc/store/v1/cart");
      if (!cartResp.ok) {
        pushAssistant("Sorry, I couldn't retrieve your cart to find the item.");
        return true;
      }

      const cartData = await cartResp.json();
      const items = cartData.items || [];
      const mentionsCart = CART_MENTION_RE.test(query);

      const match = query.match(CART_REMOVE_RE);
      let searchName = match ? match[1].trim() : query;
      searchName = searchName
        .replace(/\s+from\s+(?:my |the |your |a )?(?:cart|basket)\b.*$/i, "")
        .replace(/\s+(?:please|pls|for me|now|thanks|thank you)\s*$/i, "")
        .trim()
        .replace(/^the\s+/i, "");
      const isPronoun = !searchName || CART_REMOVE_PRONOUN_RE.test(searchName);

      if (!items.length) {
        // Only answer if they clearly meant their cart; otherwise let the model
        // handle what is probably a broader question.
        if (!mentionsCart && !isPronoun) return false;
        pushAssistant("Your cart is already empty.");
        return true;
      }

      let foundItem = null;

      if (isPronoun) {
        if (items.length === 1) {
          foundItem = items[0];
        } else {
          pushAssistant(`Which item would you like me to remove? Your cart contains: ${items.map((i) => i.name).join(", ")}.`);
          return true;
        }
      } else {
        const searchLower = searchName.toLowerCase();
        const byExact = items.find((item) => (item.name || "").toLowerCase().trim() === searchLower);
        foundItem = byExact || items.find((item) => (item.name || "").toLowerCase().includes(searchLower));
      }

      if (!foundItem) {
        // No such item in the cart: hand back to the model unless the customer
        // explicitly referred to the cart, in which case a cart reply is right.
        if (!mentionsCart) return false;
        pushAssistant(`I couldn't find "${searchName}" in your cart. Your cart contains: ${items.map((i) => i.name).join(", ")}.`);
        return true;
      }

      const itemKey = foundItem.key || foundItem.cart_item_key;

      if (!itemKey) {
        pushAssistant(`Sorry, I couldn't identify ${foundItem.name} in your cart.`);
        return true;
      }

      const stageResp = await fetch("/wp-json/mmhd-bot/v1/cart-remove-stage", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": restNonce() },
        body: JSON.stringify({ key: itemKey, name: foundItem.name }),
      });

      const stageData = await stageResp.json().catch(() => ({}));

      if (!stageResp.ok) {
        pushAssistant(stageData.message || `Sorry, I couldn't prepare the removal of ${foundItem.name}.`);
        return true;
      }

      pushAssistant(
        `Ready to remove ${stageData.name} from your cart. Click below to confirm.`,
        [
          {
            action: "remove-from-cart",
            label: `Remove ${stageData.name}`,
            pending_id: stageData.pending_id,
            expires_in: stageData.expires_in || 15 * 60,
          },
        ],
      );
      return true;
    } catch (e) {
      pushAssistant("Sorry, I couldn't remove that item from your cart.");
      return true;
    } finally {
      setIsStreaming(false);
      setLoadingText("");
    }
  };

  /**
   * Confirm a staged removal (triggered by the "Remove ..." action button).
   * Redeems the staged intent for a Store API nonce, then performs the removal
   * from the browser where the cart session actually lives.
   */
  const removeFromCart = async (pendingId, msgIndex = null) => {
    if (!pendingId) return;
    markActionUsed(pendingId, msgIndex);
    setIsStreaming(true);
    setLoadingText("...");

    try {
      const resp = await fetch("/wp-json/mmhd-bot/v1/cart-remove", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": restNonce() },
        body: JSON.stringify({ pending_id: pendingId }),
      });
      const data = await resp.json().catch(() => ({}));

      if (!resp.ok) {
        pushAssistant(data.message || "Sorry, I couldn't remove that item. Please try again.");
        return;
      }

      const removeResp = await fetch("/wp-json/wc/store/v1/cart/remove-item", {
        method: "POST",
        headers: { "Content-Type": "application/json", Nonce: data.nonce || "" },
        body: JSON.stringify({ key: data.key }),
      });

      if (!removeResp.ok) {
        const err = await removeResp.json().catch(() => ({}));
        pushAssistant(err.message || `Sorry, I couldn't remove ${data.name} from your cart.`);
        return;
      }

      pushAssistant(`Removed ${data.name} from your cart.`);
      await viewCart();
    } catch (e) {
      pushAssistant("Sorry, I couldn't remove that item from your cart.");
    } finally {
      setIsStreaming(false);
      setLoadingText("");
    }
  };

  /**
   * Confirm a staged add (triggered by the "Add to cart" action button).
   * Redeems the staged intent for the validated lines plus a Store API nonce,
   * then adds each line from the browser.
   */
  const addToCart = async (pendingId, msgIndex = null) => {
    if (!pendingId) return;
    markActionUsed(pendingId, msgIndex);
    setIsStreaming(true);
    setLoadingText("...");

    try {
      const resp = await fetch("/wp-json/mmhd-bot/v1/cart-intent", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": restNonce() },
        body: JSON.stringify({ pending_id: pendingId }),
      });
      const data = await resp.json().catch(() => ({}));

      if (!resp.ok) {
        pushAssistant(data.message || "Sorry, I couldn't add that to your cart. Please try again.");
        return;
      }

      const lines = Array.isArray(data.items) && data.items.length ? data.items : [data];
      const added = [];

      for (const line of lines) {
        const addResp = await fetch("/wp-json/wc/store/v1/cart/add-item", {
          method: "POST",
          headers: { "Content-Type": "application/json", Nonce: data.nonce || "" },
          body: JSON.stringify({ id: line.id, quantity: line.quantity || 1 }),
        });

        if (!addResp.ok) {
          const err = await addResp.json().catch(() => ({}));
          pushAssistant(
            added.length
              ? `Added ${added.map((l) => `${l.quantity} × ${l.name}`).join(", ")}, but couldn't add ${line.name}: ${err.message || "please try again."}`
              : `Sorry, I couldn't add ${line.name} to your cart: ${err.message || "please try again."}`
          );
          return;
        }

        added.push({ name: line.name, quantity: line.quantity || 1, price_display: line.price_display });
      }

      const adjustments = Array.isArray(data.adjustments) && data.adjustments.length
        ? `\n\nNote: I reduced the quantity for ${data.adjustments.map((a) => `${a.name} to ${a.quantity}`).join(", ")} because that's all we have in stock.`
        : "";

      pushAssistant(
        `Added ${added.map((l) => `${l.quantity} × ${l.name}`).join(", ")} — ${data.subtotal_display || ""}.${adjustments}`,
        [
          { action: "link", label: "View cart", url: data.cart_url || "/cart/" },
          { action: "link", label: "Checkout", url: data.checkout_url || "/checkout/" },
        ],
      );

      // The confirmation above already rendered View cart / Checkout, so show
      // the refreshed cart summary without repeating the same buttons.
      await viewCart(false);
    } catch (e) {
      pushAssistant("Sorry, I couldn't add that to your cart.");
    } finally {
      setIsStreaming(false);
      setLoadingText("");
    }
  };

  /**
   * CONVERT CHAT TO TICKET (Talk to Human)
   */
  const convertToTicket = async () => {
    setIsCreatingTicket(true);
    try {
      const headers = { "Content-Type": "application/json" };
      const restNonce = window.mmhdAuth?.restNonce || window.MetamintHelpdeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";
      if (restNonce) {
        headers["X-WP-Nonce"] = restNonce;
      }

      const resp = await fetch(
        "/wp-json/mmhd-bot/v1/convert-to-ticket",
        {
          method: "POST",
          headers,
          body: JSON.stringify({
            messages: messages.map((m) => ({
              role: m.role,
              text: m.text ?? m.message ?? "",
            })),
            session_id: sessionStorage.getItem("mmhd_chat_session_id") || "",
          }),
        }
      );

      if (!resp.ok) {
        throw new Error(`HTTP ${resp.status}: ${resp.statusText}`);
      }

      const data = await resp.json();

      if (data.status === "success") {
        setTicketCreated(true);
        setTicketId(data.ticket_id ?? null);
        setMessages((m) => [
          ...m,
          {
            role: "assistant",
            message:
              data.message ||
              "Your conversation has been converted to a support ticket. A human agent will be with you shortly.",
            timestamp: new Date().toISOString(),
          },
        ]);
      } else {
        throw new Error(data.message ?? "Failed to create ticket.");
      }
    } catch (e) {
      console.error("Ticket conversion error:", e);
      setMessages((m) => [
        ...m,
        {
          role: "assistant",
          message:
            "Sorry, we couldn't create a ticket right now. Please try again later or contact support directly.",
          timestamp: new Date().toISOString(),
        },
      ]);
    } finally {
      setIsCreatingTicket(false);
    }
  };

  const sendMessageStream = async (overrideText = null) => {
    // Coerce to string: this is also invoked from onClick, which passes a
    // DOM event as the first argument — never let .trim() blow up.
    const q = String(overrideText ?? input ?? "").trim();
    if (!q) return;

    // Frontend detection: if user asks for human, show the SVG button immediately
    if (talkToHuman && TALK_TO_HUMAN_PATTERN.test(q)) {
      setMessages((m) => [...m, { role: "user", text: q, timestamp: new Date().toISOString() }]);
      setInput("");
      setMessages((m) => [
        ...m,
        {
          role: "assistant",
          message:
            "I can connect you with a human agent. Click the button below to convert this conversation into a support ticket:",
          ui: {
            suggestions: [
              { type: "talk-to-human", label: "Talk to Human", prompt: "" },
            ],
            actions: [],
          },
          timestamp: new Date().toISOString(),
        },
      ]);
      return;
    }

    // Quick local handling for cart queries so the user gets an instant answer.
    const recordUserTurn = (text) => {
      const now = new Date().toISOString();
      setMessages((m) => [...m, { role: "user", text, timestamp: now }]);
      setInput("");
      window.dispatchEvent(
        new CustomEvent("mmhd:messageSent", {
          detail: { count: messages.length + 1 },
        })
      );
    };

    const lower = q.toLowerCase();
    const isCartView = CART_VIEW_RE.test(lower);
    const isCartRemove = CART_REMOVE_RE.test(lower);

    if (isCartView || isCartRemove) {
      // Record the turn up front so the assistant reply lands underneath it.
      recordUserTurn(q);
      if (isCartRemove && !isCartView) {
        const handled = await removeFromCartByName(q);
        if (handled) return;
        // Not actually a cart removal (e.g. a general question that merely
        // contains "remove") — fall through and let the model answer.
      } else {
        await viewCart();
        return;
      }
    } else {
      recordUserTurn(q);
    }

    setIsStreaming(true);
    setLoadingText("...");

    const restNonce = window.mmhdAuth?.restNonce || window.MetamintHelpdeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";
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
        // Rate limiting should be surfaced politely to the user.
        if (resp.status === 429) {
          msg = "You're sending messages a little too quickly. Please wait a moment before trying again.";
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
      setMessages((m) => [
        ...m,
        { role: "assistant", message: e.message, timestamp: new Date().toISOString() },
      ]);
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
    const restNonce = window.mmhdAuth?.restNonce || window.MetamintHelpdeskBotConfig?.restNonce || window.wpApiSettings?.nonce || "";
    try {
      const resp = await fetch("/wp-json/mmhd-bot/v1/confirm-refund", {
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
      <div id="mmhd-bot-panel">
        <style>{`
          #mmhd-bot-panel a,
          #mmhd-bot-panel .message-content a,
          #mmhd-bot-panel .ai-text a {
            color: #1a73e8 !important;
            font-weight: 600 !important;
            text-decoration: underline !important;
            text-underline-offset: 2px;
            cursor: pointer;
          }
          #mmhd-bot-panel a:hover {
            color: #0b5ed7 !important;
          }
          #mmhd-bot-panel .ai-actions button,
          #mmhd-bot-panel .ai-suggestions button {
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
          #mmhd-bot-panel .ai-actions button:hover,
          #mmhd-bot-panel .ai-suggestions button:hover {
            background: #0b5ed7 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(26, 115, 232, 0.45);
          }
          #mmhd-bot-panel .ai-actions button:disabled,
          #mmhd-bot-panel .ai-suggestions button:disabled {
            background: #9e9e9e !important;
            color: #ffffff !important;
            cursor: not-allowed !important;
            box-shadow: none;
            transform: none;
            opacity: 0.85;
          }
          #mmhd-bot-panel .mmhd-countdown {
            font-weight: 400 !important;
            font-size: 12px;
            margin-left: 6px;
            opacity: 0.9;
          }
          #mmhd-bot-panel .ai-actions,
          #mmhd-bot-panel .ai-suggestions {
            margin-top: 8px;
          }
        `}</style>
        {/* <div id="mmhd-bot-header">
                    <strong>AI Assistant ({AIMode})</strong>
                    <button onClick={() => setOpen(false)}>x</button>
                </div> */}

        {/* MESSAGES */}
        {ticketCreated ? (
          <div style={{ padding: "20px", textAlign: "center", color: "#ef4444" }}>
            <div style={{ fontSize: "16px", fontWeight: "600", marginBottom: "8px" }}>
              Ticket Created #{ticketId}
            </div>
            <p style={{ fontSize: "13px", marginBottom: "16px" }}>
              A human agent will review your conversation and get back to you soon.
            </p>
            <button
              onClick={() => {
                setTicketCreated(false);
                setTicketId(null);
                setMessages([]);
                sessionStorage.removeItem("mmhd_chat_session");
              }}
              style={{
                background: "#1f8ef1",
                color: "#ffffff",
                border: "none",
                borderRadius: "6px",
                padding: "8px 16px",
                fontSize: "13px",
                cursor: "pointer",
              }}
            >
              Close Chat
            </button>
          </div>
        ) : (
          <div
            id="mmhd-bot-messages"
            className="mmhd-bot-message"
            ref={rootRef}
          >
            {messages.length > 0 &&
              messages.map((m, i) => {
              const prevMsg = i > 0 ? messages[i - 1] : null;
              const isSameSender = prevMsg && prevMsg.role === m.role;

              return (
                <div
                  key={i}
                  className={`chat-message mmhd-flex mmhd-items-start ${m.role === "user"
                    ? "mmhd-ml-auto sent"
                    : "mmhd-flex mmhd-gap-8 received"
                    } ${isSameSender ? "mmhd-msg-same-sender" : "mmhd-msg-new-sender"
                    } ${m.role === "user" ? "mmhd-msg-user" : "mmhd-msg-assistant"}`}
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
                        className="mmhd-bot-image mmhd-rounded-full mmhd-object-cover mmhd-single-message-avatar"
                        onError={(e) => {
                          e.target.onerror = null;
                          e.target.src = DefaultAvatar;
                        }}
                      />
                    ) : (
                      <div className="mmhd-bot-image mmhd-rounded-full mmhd-object-cover mmhd-single-message-avatar">
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
                      <AssistantMessage
                        msg={m}
                        msgIndex={i}
                        setInput={setInput}
                        setMessages={setMessages}
                        markActionUsed={markActionUsed}
                        addToCart={addToCart}
                        removeFromCart={removeFromCart}
                        confirmRefund={confirmRefund}
                        pushAssistant={pushAssistant}
                        expiredActions={expiredActions}
                        setExpiredActions={setExpiredActions}
                      />
                    ) : (
                      <span
                        className="mmhd-body-text single-message"
                        dangerouslySetInnerHTML={{
                          __html: normalizeMessageText(m.text),
                        }}
                      />
                    )}
                    {!m.thinking && (
                      <div
                        className="mmhd-text-small mmhd-text-gray"
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
            <div className="mmhd-text-center mmhd-px-20 mmhd-flex mmhd-flex-col mmhd-gap-5 mmhd-mb-20 home-tab-title">
              <h4 className="heading-4">{chatHeading}</h4>
              <h5 className="sub-text mmhd-mt-0">{welcomeMessage}</h5>
            </div>
          )}

          {/* STREAMING LOADER */}
          {(isStreaming || isCreatingTicket) && (
            <div
              className="chat-message mmhd-flex mmhd-items-start mmhd-gap-8 received mmhd-streaming-loader"
              style={{ maxWidth: "70%" }}
            >
              <style>{`
                @keyframes mmhd-typing-pulse { 0%,100%{ opacity:.3; } 50%{ opacity:1; } }
                .mmhd-typing-ellipsis { display:inline-block; min-width:28px; text-align:left; font-size:20px; font-weight:700; line-height:1; letter-spacing:2px; animation: mmhd-typing-pulse 1.2s ease-in-out infinite; }
              `}</style>
              {getBotDisplayImage() ? (
                <img
                  src={getBotDisplayImage()}
                  alt={getBotDisplayName()}
                  className="mmhd-single-message-avatar"
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = DefaultAvatar;
                  }}
                />
              ) : (
                <div className="mmhd-bot-image mmhd-single-message-avatar">
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
                  className="mmhd-typing-ellipsis mmhd-body-text single-message"
                  role="status"
                  aria-label="Bot is typing"
                >
                  ...
                </span>
                {loadingText && loadingText !== "..." && (
                  <span className="mmhd-body-text single-message">
                    {loadingText}
                  </span>
                )}
              </div>
            </div>
          )}
        </div>      )}

        {/* INPUT */}
        <div
          id="mmhd-bot-input"
          className="chat-input mmhd-flex mmhd-px-18 mmhd-py-15 mmhd-border-top-light mmhd-border-bottom-light mmhd-bg-white mmhd-justify-between"
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
            <div className="mmhd-flex mmhd-gap-20 mmhd-relative">
              <button
                onClick={() => sendMessageStream()}
                className="mmhd-bg-transparent mmhd-border-none mmhd-ml-10 icon-dark mmhd-cursor-pointer mmhd-p-0"
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
