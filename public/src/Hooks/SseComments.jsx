import { useState, useEffect, useRef, useCallback } from 'react';

// Custom hook for managing SSE connections
export const useSSEComments = (postId, loggedInUserId, initialLastCommentId = 0) => {
  const [isConnected, setIsConnected] = useState(false);
  const [connectionError, setConnectionError] = useState(null);
  const [lastCommentId, setLastCommentId] = useState(initialLastCommentId);
  const [reconnectAttempts, setReconnectAttempts] = useState(0);
  
  const eventSourceRef = useRef(null);
  const reconnectTimeoutRef = useRef(null);
  const maxReconnectAttempts = 5;
  const baseReconnectDelay = 1000; 
  
  // Callback for new comments
  const [onNewComment, setOnNewComment] = useState(() => () => {});
  
  const connect = useCallback(() => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
    }
    // wp-json/robodesk/v1/comments-stream/
    const url = `/wp-json/robodesk/v1/comments-stream/${postId}/${lastCommentId}`;
    const eventSource = new EventSource(url);
    
    eventSource.onopen = () => {
    //   console.log('SSE connection opened');
      setIsConnected(true);
      setConnectionError(null);
      setReconnectAttempts(0);
    };
    
    eventSource.addEventListener('connected', (event) => {
      try {
        const data = JSON.parse(event.data);
        console.log('SSE connected:', data);
        setLastCommentId(data.last_comment_id);
      } catch (error) {
        console.error('Error parsing connection event:', error);
      }
    });
    
    eventSource.addEventListener('new_comment', (event) => {
      try {
        const commentData = JSON.parse(event.data);
        console.log('New comment received:', commentData);
        
        const formattedComment = {
          id: commentData.id,
          text: commentData.content?.rendered.replace(/<[^>]+>/g, "") || "",
          sender: commentData.author_data?.id === loggedInUserId ? "me" : "other",
          senderName: commentData.author_name,
          image: commentData.author_image || "/default-avatar.jpg",
          created_at: commentData.date,
          time_ago: commentData.time_ago,
          isAdmin: commentData.author_id !== commentData.post_author_id,
        };
        
        setLastCommentId(commentData.id);
        onNewComment(formattedComment);
        
      } catch (error) {
        console.error('Error parsing new comment:', error);
      }
    });
    
    eventSource.addEventListener('heartbeat', (event) => {
      try {
        const data = JSON.parse(event.data);
        console.log('Heartbeat received:', data.timestamp);
        
        if (data.last_comment_id) {
          setLastCommentId(data.last_comment_id);
        }
      } catch (error) {
        console.error('Error parsing heartbeat:', error);
      }
    });
    
    eventSource.addEventListener('disconnect', (event) => {
      console.log('Server requested disconnect:', event.data);
      setIsConnected(false);
      scheduleReconnect();
    });
    
    eventSource.onerror = (error) => {
      console.error('SSE error:', error);
      setIsConnected(false);
      setConnectionError('Connection error occurred');
      
      if (eventSource.readyState === EventSource.CLOSED) {
        scheduleReconnect();
      }
    };
    
    eventSourceRef.current = eventSource;
  }, [postId, lastCommentId, loggedInUserId, onNewComment]);
  
  const scheduleReconnect = useCallback(() => {
    if (reconnectAttempts >= maxReconnectAttempts) {
      setConnectionError('Max reconnection attempts reached');
      return;
    }
    
    const delay = Math.min(baseReconnectDelay * Math.pow(2, reconnectAttempts), 30000); // Max 30 seconds
    
    console.log(`Scheduling reconnect in ${delay}ms (attempt ${reconnectAttempts + 1}/${maxReconnectAttempts})`);
    
    reconnectTimeoutRef.current = setTimeout(() => {
      setReconnectAttempts(prev => prev + 1);
      connect();
    }, delay);
  }, [reconnectAttempts, connect]);
  
  const disconnect = useCallback(() => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }
    
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
      reconnectTimeoutRef.current = null;
    }
    
    setIsConnected(false);
    setReconnectAttempts(0);
  }, []);
  
  const forceReconnect = useCallback(() => {
    disconnect();
    setReconnectAttempts(0);
    setConnectionError(null);
    setTimeout(connect, 1000);
  }, [disconnect, connect]);
  
  const updateLastCommentId = useCallback((newId) => {
    setLastCommentId(newId);
  }, []);
  
  // Set up connection on mount
  useEffect(() => {
    if (postId && loggedInUserId !== undefined) {
      connect();
    }
    
    return () => {
      disconnect();
    };
  }, [postId, loggedInUserId]);
  
  // Handle visibility change to reconnect when tab becomes visible
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (!document.hidden && !isConnected && reconnectAttempts < maxReconnectAttempts) {
        console.log('Tab became visible, attempting to reconnect...');
        forceReconnect();
      }
    };
    
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [isConnected, reconnectAttempts, forceReconnect]);
  
  // Handle online/offline events
  useEffect(() => {
    const handleOnline = () => {
      console.log('Connection restored, attempting to reconnect...');
      forceReconnect();
    };
    
    const handleOffline = () => {
      console.log('Connection lost');
      setConnectionError('Network connection lost');
    };
    
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    
    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, [forceReconnect]);
  
  return {
    isConnected,
    connectionError,
    reconnectAttempts,
    lastCommentId,
    connect,
    disconnect,
    forceReconnect,
    updateLastCommentId,
    setOnNewComment: (callback) => setOnNewComment(() => callback),
  };
};

// Utility function to check if SSE is supported
export const checkSSESupport = async () => {
  try {
    const response = await fetch('/wp-json/wp/v2/sse-status');
    const data = await response.json();
    return {
      supported: !!window.EventSource && data.sse_supported,
      serverInfo: data
    };
  } catch (error) {
    return {
      supported: !!window.EventSource,
      serverInfo: null,
      error: error.message
    };
  }
};