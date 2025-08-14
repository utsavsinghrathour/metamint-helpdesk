import React, { useState, useRef, useCallback, useEffect, createContext, useContext } from 'react';

// SSE Management Hook
const useSSEConnection = () => {
  const eventSourceRef = useRef(null);
  const hasConnectedRef = useRef(false);
  const reconnectTimeoutRef = useRef(null);

  // Open SSE connection for specific tab
  const openConnectionForTab = useCallback((tabType, config) => {
    // Close existing connection if switching tabs or reconnecting
    closeConnection();

    const {
      baseEndpoint,
      ticketId,
      lastCommentId,
      onNewComment,
      onNewTicket, 
      onNewNotification,
      onError,
      loggedInUserId
    } = config;

    // Build endpoint with appropriate query parameters based on tab type
    let endpoint = `${baseEndpoint}/${ticketId}/${lastCommentId}/${loggedInUserId}`;
    
    if (tabType === 'tickets') {
      endpoint += '?ticketstream=true';
    } else if (tabType === 'notifications') {
      endpoint += '?notificationstream=true';
    }
    // For messages tab, use the endpoint without additional parameters

    try {
      eventSourceRef.current = new EventSource(endpoint);
      
      eventSourceRef.current.onopen = () => {
        console.log(`SSE connection opened for ${tabType} tab`);
        hasConnectedRef.current = true;
        
        if (reconnectTimeoutRef.current) {
          clearTimeout(reconnectTimeoutRef.current);
          reconnectTimeoutRef.current = null;
        }
      };

      // Always listen to handshake
      eventSourceRef.current.addEventListener('handshake', (event) => {
        const handshake = JSON.parse(event.data);
        console.log(`Handshake received for ${tabType}:`, handshake);
      });

      // Tab-specific event listeners
      if (tabType === 'messages') {
        eventSourceRef.current.addEventListener('new_comment', (event) => {
          const commentData = JSON.parse(event.data);
          console.log('New comment received:', commentData);
          if (onNewComment) onNewComment(commentData);
        });
      } else if (tabType === 'tickets') {
        eventSourceRef.current.addEventListener('new_ticket', (event) => {
          const ticketData = JSON.parse(event.data);
          console.log('New ticket received:', ticketData);
          if (onNewTicket) onNewTicket(ticketData);
        });
      } else if (tabType === 'notifications') {
        eventSourceRef.current.addEventListener('new_notification', (event) => {
          const notificationData = JSON.parse(event.data);
          console.log('New notification received:', notificationData);
          if (onNewNotification) onNewNotification(notificationData);
        });
      }

      // Common event listeners for all tabs
      eventSourceRef.current.addEventListener('disconnect', (event) => {
        try {
          const data = JSON.parse(event.data);
          console.log('Server requested disconnect:', data);
          closeConnection();
        } catch (error) {
          console.error('Error parsing disconnect:', error);
        }
      });

      eventSourceRef.current.addEventListener('retry', (event) => {
        console.log(`Retrying connection for ${tabType}...`);
      });

      eventSourceRef.current.onerror = (error) => {
        console.error(`SSE error for ${tabType}:`, error);
        hasConnectedRef.current = false;
        
        if (onError) {
          onError(error);
        }

        // Auto-reconnect logic
        if (eventSourceRef.current?.readyState === EventSource.CLOSED) {
          console.log(`Connection closed for ${tabType}, attempting to reconnect in 5 seconds...`);
          reconnectTimeoutRef.current = setTimeout(() => {
            if (eventSourceRef.current?.readyState === EventSource.CLOSED) {
              openConnectionForTab(tabType, config);
            }
          }, 5000);
        }
      };

    } catch (error) {
      console.error(`Failed to create SSE connection for ${tabType}:`, error);
      if (onError) onError(error);
    }
  }, []);

  // Close SSE connection
  const closeConnection = useCallback(() => {
    if (eventSourceRef.current) {
      console.log('Closing SSE Connection');
      eventSourceRef.current.close();
      eventSourceRef.current = null;
      hasConnectedRef.current = false;
    }

    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
      reconnectTimeoutRef.current = null;
    }
  }, []);

  // Check connection status
  const isConnected = useCallback(() => {
    return hasConnectedRef.current && 
           eventSourceRef.current?.readyState === EventSource.OPEN;
  }, []);

  // Get current active tab
  const getCurrentTab = useCallback(() => {
    return currentTabRef.current;
  }, []);

  // Get connection state
  const getConnectionState = useCallback(() => {
    if (!eventSourceRef.current) return 'CLOSED';
    
    switch (eventSourceRef.current.readyState) {
      case EventSource.CONNECTING: return 'CONNECTING';
      case EventSource.OPEN: return 'OPEN';
      case EventSource.CLOSED: return 'CLOSED';
      default: return 'UNKNOWN';
    }
  }, []);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      closeConnection();
    };
  }, [closeConnection]);

  return {
    openConnectionForTab,
    closeConnection,
    isConnected,
    getConnectionState
  };
};

// Context Provider for SSE
const SSEContext = createContext();

export const SSEProvider = ({ children, loggedInUserId }) => {
  const sseConnection = useSSEConnection();
  
  return (
    <SSEContext.Provider value={{ ...sseConnection, loggedInUserId }}>
      {children}
    </SSEContext.Provider>
  );
};

export const useSSE = () => {
  const context = useContext(SSEContext);
  if (!context) {
    throw new Error('useSSE must be used within an SSEProvider');
  }
  return context;
};

// Tab Management Hook
const useTabSSE = (tabType) => {
  const { 
    openConnectionForTab, 
    closeConnection, 
    isConnected, 
    getConnectionState,
    loggedInUserId 
  } = useSSE();

  const [currentActiveTab, setCurrentActiveTab] = useState(null);

  const activateTab = useCallback((config) => {
    // Only open connection if not already connected to this tab
    if (currentActiveTab !== tabType) {
      console.log(`Switching to ${tabType} tab`);
      setCurrentActiveTab(tabType);
      openConnectionForTab(tabType, {
        ...config,
        loggedInUserId
      });
    }
  }, [tabType, openConnectionForTab, currentActiveTab, loggedInUserId]);

  const isActiveTab = useCallback(() => {
    return currentActiveTab === tabType;
  }, [tabType, currentActiveTab]);

  const deactivateTab = useCallback(() => {
    if (currentActiveTab === tabType) {
      setCurrentActiveTab(null);
      closeConnection();
    }
  }, [tabType, currentActiveTab, closeConnection]);

  return {
    activateTab,
    deactivateTab,
    closeConnection,
    isConnected: isConnected() && isActiveTab(),
    isActiveTab,
    getConnectionState
  };
};

// Messages Tab Component
const MessagesTab = ({ ticketId, lastCommentId }) => {
  const [messages, setMessages] = useState([]);
  const [isTabActive, setIsTabActive] = useState(false);
  const beepRef = useRef(new Audio('/path/to/beep.mp3'));
  
  const { activateTab, isConnected, isActiveTab } = useTabSSE('messages');

  const handleNewComment = useCallback((commentData) => {
    console.log('Processing new comment:', commentData);
    beepRef.current?.play();
    setMessages(prev => [...prev, commentData]);
  }, []);

  const handleSSEError = useCallback((error) => {
    console.error('Messages SSE Error:', error);
  }, []);

  // Activate this tab when component becomes active
  useEffect(() => {
    if (isTabActive && ticketId && lastCommentId) {
      activateTab({
        baseEndpoint: `/wp-json/robodesk/v1/messaging`,
        ticketId,
        lastCommentId,
        onNewComment: handleNewComment,
        onError: handleSSEError
      });
    }
  }, [isTabActive, ticketId, lastCommentId, activateTab, handleNewComment, handleSSEError]);

  return (
    <div style={{ display: isTabActive ? 'block' : 'none' }}>
      <div>
        <h3>Messages ({messages.length})</h3>
        <p>Connection Status: {isConnected ? 'Connected' : 'Disconnected'}</p>
        <p>Active Tab: {isActiveTab() ? 'Yes' : 'No'}</p>
      </div>
      <div>
        {messages.map((message, index) => (
          <div key={index}>
            {/* Render message */}
            <p>{message.content || JSON.stringify(message)}</p>
          </div>
        ))}
      </div>
      <button onClick={() => setIsTabActive(!isTabActive)}>
        {isTabActive ? 'Deactivate' : 'Activate'} Messages Tab
      </button>
    </div>
  );
};

// Tickets Tab Component
const TicketsTab = ({ ticketId = 456, lastCommentId = 789 }) => {
  const [tickets, setTickets] = useState([]);
  const [isTabActive, setIsTabActive] = useState(false);
  
  const { activateTab, isConnected, isActiveTab } = useTabSSE('tickets');

  const handleNewTicket = useCallback((ticketData) => {
    console.log('Processing new ticket:', ticketData);
    setTickets(prev => [...prev, ticketData]);
  }, []);

  const handleSSEError = useCallback((error) => {
    console.error('Tickets SSE Error:', error);
  }, []);

  // Activate this tab when component becomes active
  useEffect(() => {
    if (isTabActive && ticketId && lastCommentId) {
      activateTab({
        baseEndpoint: `/wp-json/robodesk/v1/messaging`,
        ticketId,
        lastCommentId,
        onNewTicket: handleNewTicket,
        onError: handleSSEError
      });
    }
  }, [isTabActive, ticketId, lastCommentId, activateTab, handleNewTicket, handleSSEError]);

  return (
    <div style={{ display: isTabActive ? 'block' : 'none' }}>
      <div>
        <h3>Tickets ({tickets.length})</h3>
        <p>Connection Status: {isConnected ? 'Connected' : 'Disconnected'}</p>
        <p>Active Tab: {isActiveTab() ? 'Yes' : 'No'}</p>
      </div>
      <div>
        {tickets.map((ticket, index) => (
          <div key={index}>
            {/* Render ticket */}
            <p>{ticket.title || JSON.stringify(ticket)}</p>
          </div>
        ))}
      </div>
      <button onClick={() => setIsTabActive(!isTabActive)}>
        {isTabActive ? 'Deactivate' : 'Activate'} Tickets Tab
      </button>
    </div>
  );
};

// Notifications Tab Component
const NotificationsTab = ({ ticketId = 456, lastCommentId = 789 }) => {
  const [notifications, setNotifications] = useState([]);
  const [isTabActive, setIsTabActive] = useState(false);
  
  const { activateTab, isConnected, isActiveTab } = useTabSSE('notifications');

  const handleNewNotification = useCallback((notificationData) => {
    console.log('Processing new notification:', notificationData);
    setNotifications(prev => [...prev, notificationData]);
  }, []);

  const handleSSEError = useCallback((error) => {
    console.error('Notifications SSE Error:', error);
  }, []);

  // Activate this tab when component becomes active
  useEffect(() => {
    if (isTabActive && ticketId && lastCommentId) {
      activateTab({
        baseEndpoint: `/wp-json/robodesk/v1/messaging`,
        ticketId,
        lastCommentId,
        onNewNotification: handleNewNotification,
        onError: handleSSEError
      });
    }
  }, [isTabActive, ticketId, lastCommentId, activateTab, handleNewNotification, handleSSEError]);

  return (
    <div style={{ display: isTabActive ? 'block' : 'none' }}>
      <div>
        <h3>Notifications ({notifications.length})</h3>
        <p>Connection Status: {isConnected ? 'Connected' : 'Disconnected'}</p>
        <p>Active Tab: {isActiveTab() ? 'Yes' : 'No'}</p>
      </div>
      <div>
        {notifications.map((notification, index) => (
          <div key={index}>
            {/* Render notification */}
            <p>{notification.message || JSON.stringify(notification)}</p>
          </div>
        ))}
      </div>
      <button onClick={() => setIsTabActive(!isTabActive)}>
        {isTabActive ? 'Deactivate' : 'Activate'} Notifications Tab
      </button>
    </div>
  );
};

// Main Application with Tab Navigation
const Application = () => {
  const [activeTab, setActiveTab] = useState('messages');
  const loggedInUserId = 123; // Replace with actual user ID

  const handleTabChange = (tabName) => {
    setActiveTab(tabName);
  };

  return (
    <SSEProvider loggedInUserId={loggedInUserId}>
      <div>
        <nav style={{ marginBottom: '20px' }}>
          <button 
            onClick={() => handleTabChange('messages')}
            style={{ 
              marginRight: '10px', 
              backgroundColor: activeTab === 'messages' ? '#007cba' : '#f0f0f0',
              color: activeTab === 'messages' ? 'white' : 'black'
            }}
          >
            Messages
          </button>
          <button 
            onClick={() => handleTabChange('tickets')}
            style={{ 
              marginRight: '10px',
              backgroundColor: activeTab === 'tickets' ? '#007cba' : '#f0f0f0',
              color: activeTab === 'tickets' ? 'white' : 'black'
            }}
          >
            Tickets
          </button>
          <button 
            onClick={() => handleTabChange('notifications')}
            style={{ 
              backgroundColor: activeTab === 'notifications' ? '#007cba' : '#f0f0f0',
              color: activeTab === 'notifications' ? 'white' : 'black'
            }}
          >
            Notifications
          </button>
        </nav>

        <div>
          <MessagesTab 
            ticketId={456}
            lastCommentId={789}
            isTabActive={activeTab === 'messages'}
          />
          <TicketsTab 
            ticketId={456}
            lastCommentId={789}
            isTabActive={activeTab === 'tickets'} 
          />
          <NotificationsTab 
            ticketId={456}
            lastCommentId={789}
            isTabActive={activeTab === 'notifications'} 
          />
        </div>
      </div>
    </SSEProvider>
  );
};

export default Application;