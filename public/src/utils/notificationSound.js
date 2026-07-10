// Global, debounced notification beep for the frontend chat bot.
// Used by both the conversation list (new_notification) and the open ticket
// (new_comment) so a new reply beeps no matter which view is active, while a
// debounce prevents a double beep when both fire for the same ticket.

let lastPlayedAt = 0;
const DEBOUNCE_MS = 1500;

export function playRobodeskBeep() {
  const sound = window.wpAuth?.notificationSound;
  if (!sound) {
    return;
  }

  const now = Date.now();
  if (now - lastPlayedAt < DEBOUNCE_MS) {
    return;
  }
  lastPlayedAt = now;

  try {
    const audio = new Audio(sound);
    const p = audio.play();
    if (p && typeof p.catch === "function") {
      p.catch(() => {});
    }
  } catch (e) {
    /* no-op */
  }
}
