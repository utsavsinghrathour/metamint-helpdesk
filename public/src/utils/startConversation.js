/**
 * Carries a "start a conversation" request from a notice (action 2) to the
 * chat widget. Because the chat components may not be mounted yet when the
 * user clicks the notice, we keep a module-level pending message AND broadcast
 * a window event for the already-mounted case.
 */
let pendingInitialMessage = null;

export const START_CONVERSATION_EVENT = "mmhd:startConversation";

export const setPendingInitialMessage = (message) => {
  pendingInitialMessage = message || null;
};

export const consumePendingInitialMessage = () => {
  const message = pendingInitialMessage;
  pendingInitialMessage = null;
  return message;
};

export const requestStartConversation = (message) => {
  if (!message) return;
  setPendingInitialMessage(message);
  window.dispatchEvent(
    new CustomEvent(START_CONVERSATION_EVENT, {
      detail: { message },
    }),
  );
};

/**
 * Chat session persistence ("mmhd_chat_session").
 *
 * Storage format: { user: <userKey>, messages: [...] } where userKey is the
 * logged-in user id or "guest". Legacy sessions are bare message arrays.
 * The owning user key lets the widget clear the chat after logout while
 * preserving a guest conversation that continues after login.
 */
export const CHAT_SESSION_KEY = "mmhd_chat_session";

export const readChatSession = () => {
  try {
    const saved = sessionStorage.getItem(CHAT_SESSION_KEY);
    if (!saved) return [];
    const parsed = JSON.parse(saved);
    if (Array.isArray(parsed)) return parsed; // legacy bare-array format
    if (parsed && typeof parsed === "object" && Array.isArray(parsed.messages)) {
      return parsed.messages;
    }
    return [];
  } catch {
    return [];
  }
};

export const writeChatSession = (messages, userKey) => {
  try {
    sessionStorage.setItem(
      CHAT_SESSION_KEY,
      JSON.stringify({ user: userKey || "guest", messages: messages || [] }),
    );
  } catch {
    // Storage full/unavailable - ignore.
  }
};