class SSEManager {
  constructor() {
    this.eventSource = null;
    this.currentUrl = null;
    this.listeners = {};
  }

  connect(url) {
    if (this.currentUrl === url && this.eventSource) {
      return;
    }

    this.disconnect();

    console.log('🔌 Connecting to:', url);
    this.currentUrl = url;
    this.eventSource = new EventSource(url);

    // Handle custom SSE events
    const events = [
      'new_comment',
      'ticket_closed',
      'ticket_open',
      'new_ticket',
      'new_notification',
    ];
    events.forEach((event) => {
      this.eventSource.addEventListener(event, (e) => {
        try {
          if (!e.data || e.data === 'undefined' || e.data.trim() === '') {
            console.warn(
              `SSE ${event} received invalid or empty data:`,
              e.data
            );
            return;
          }

          const data = JSON.parse(e.data);

          this._emit(event, data);
        } catch (err) {}
      });
    });

    this.eventSource.onerror = (err) => {

      this._emit('error', { message: 'SSE connection error', error: err });
      // Avoid disconnecting unless necessary
      // this.disconnect();
    };
  }

  disconnect() {
    if (this.eventSource) {
      console.log('❌ Closing SSE:', this.currentUrl);
      this.eventSource.close();
      this.eventSource = null;
      this.currentUrl = null;
    }
  }

  on(event, cb) {
    if (!this.listeners[event]) {
      this.listeners[event] = new Set();
    }
    this.listeners[event].add(cb);

    return () => this.listeners[event].delete(cb);
  }

  _emit(event, data) {
    if (this.listeners[event]) {
      this.listeners[event].forEach((cb) => cb(data));
    }
  }
}

export const sseManager = new SSEManager();
