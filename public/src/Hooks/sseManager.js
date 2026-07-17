class SSEManager {
  constructor() {
    // channel -> { eventSource, url }
    this.connections = {};
    // channel -> { event -> Set<callback> }
    this.listeners = {};
  }

  connect(url, channel = "default") {
    const existing = this.connections[channel];
    if (existing && existing.url === url && existing.eventSource) {
      return;
    }

    this.disconnect(channel);

    console.log("🔌 Connecting to:", url, "(channel:", channel + ")");
    const eventSource = new EventSource(url);

    const events = [
      "new_ticket",
      "new_notification",
      "new_comment",
      "ticket_closed",
      "ticket_open",
      "new_mine_comment",
    ];
    events.forEach((event) => {
      eventSource.addEventListener(event, (e) => {
        try {
          if (!e.data || e.data === "undefined" || e.data.trim() === "") {
            console.log(`SSE ${event} received invalid or empty data:`, e.data);
            return;
          }

          const data = JSON.parse(e.data);

          this._emit(channel, event, data);
        } catch (err) {
          /* no-op */
        }
      });
    });

    eventSource.onerror = (err) => {
      this._emit(channel, "error", {
        message: "SSE connection error",
        error: err,
      });
    };

    this.connections[channel] = { eventSource, url };
  }

  disconnect(channel) {
    if (channel) {
      const conn = this.connections[channel];
      if (conn && conn.eventSource) {
        console.log("❌ Closing SSE channel:", channel);
        conn.eventSource.close();
      }
      delete this.connections[channel];
      return;
    }

    // Disconnect every channel.
    Object.keys(this.connections).forEach((ch) => {
      const conn = this.connections[ch];
      if (conn && conn.eventSource) {
        conn.eventSource.close();
      }
    });
    this.connections = {};
  }

  on(event, cb, channel = "default") {
    if (!this.listeners[channel]) {
      this.listeners[channel] = {};
    }
    if (!this.listeners[channel][event]) {
      this.listeners[channel][event] = new Set();
    }
    this.listeners[channel][event].add(cb);

    return () => this.listeners[channel][event].delete(cb);
  }

  _emit(channel, event, data) {
    const channelListeners = this.listeners[channel];
    if (channelListeners && channelListeners[event]) {
      channelListeners[event].forEach((cb) => cb(data));
    }
  }
}

export const sseManager = new SSEManager();
