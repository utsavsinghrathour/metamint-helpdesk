/**
 * Carries a "start a conversation" request from a notice (action 2) to the
 * chat widget. Because the chat components may not be mounted yet when the
 * user clicks the notice, we keep a module-level pending message AND broadcast
 * a window event for the already-mounted case.
 */
let pendingInitialMessage = null;

export const START_CONVERSATION_EVENT = "robodesk:startConversation";

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