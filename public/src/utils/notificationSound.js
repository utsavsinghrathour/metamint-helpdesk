// Global, debounced notification beep for the frontend chat bot.
// Used by both the conversation list (new_notification) and the open ticket
// (new_comment) so a new reply beeps no matter which view is active, while a
// debounce prevents a double beep when both fire for the same ticket.

// Reuse a single, preloaded Audio element. Creating a new Audio on every beep
// forces the browser to re-fetch/decode the MP3 each time and is the main cause
// of intermittent "no sound" — a reused element starts reliably.
let beepAudio = null;
let pendingBeep = false;

function ensureAudio() {
  const sound = window.robodeskAuth?.notificationSound;
  if (!sound) {
    return null;
  }
  if (!beepAudio) {
    beepAudio = new Audio(sound);
    beepAudio.preload = "auto";
    beepAudio.dataset.sound = sound;
  } else if (beepAudio.dataset.sound !== sound) {
    beepAudio.src = sound;
    beepAudio.dataset.sound = sound;
  }
  return beepAudio;
}

function flushPending() {
  if (!pendingBeep) {
    return;
  }
  pendingBeep = false;
  const audio = ensureAudio();
  if (!audio) {
    return;
  }
  audio.currentTime = 0;
  const p = audio.play();
  if (p && typeof p.catch === "function") {
    p.catch(() => {
      pendingBeep = true;
    });
  }
}

let lastPlayedAt = 0;
const DEBOUNCE_MS = 1200;

export function playRobodeskBeep() {
  const audio = ensureAudio();
  if (!audio) {
    return;
  }

  const now = Date.now();
  if (now - lastPlayedAt < DEBOUNCE_MS) {
    return;
  }
  lastPlayedAt = now;

  audio.currentTime = 0;
  const p = audio.play();
  if (p && typeof p.catch === "function") {
    p.catch(() => {
      // Autoplay was blocked (e.g. a message arrived before any user
      // interaction). Play it on the next gesture instead of failing silently.
      pendingBeep = true;
      const onGesture = () => {
        window.removeEventListener("pointerdown", onGesture);
        window.removeEventListener("keydown", onGesture);
        window.removeEventListener("touchstart", onGesture);
        flushPending();
      };
      window.addEventListener("pointerdown", onGesture, { once: true });
      window.addEventListener("keydown", onGesture, { once: true });
      window.addEventListener("touchstart", onGesture, { once: true });
    });
  }
}
