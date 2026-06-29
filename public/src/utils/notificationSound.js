let audioInstance = null;

export function getNotificationAudio() {
  if (!audioInstance && window.wpAuth?.notificationSound) {
    audioInstance = new Audio(window.wpAuth.notificationSound);
  }
  return audioInstance;
}
