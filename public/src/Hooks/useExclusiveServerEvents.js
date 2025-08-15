// hooks/useExclusiveServerEvents.js
import { useEffect, useRef } from 'react';

const useExclusiveServerEvents = () => {
  const eventSourceRef = useRef(null);
  const activeStreamRef = useRef(null);
  const eventHandlersRef = useRef({});

  const disconnect = () => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
      activeStreamRef.current = null;
      console.log('SSE stream disconnected');
    }
  };

  const connectExclusive =  (streamType, { postId, lastId, userId }, handlers) => {
    // If already connected to this stream, do nothing
    if (activeStreamRef.current === streamType) return;
    
    // Close any existing connection
    disconnect();
    
    if (postId !== undefined && postId !== null) url += `/${postId}`;


    // Build URL according to your endpoint structure
    let url = `/wp-json/robodesk/v1/stream/${streamType}`;
    if (postId !== undefined && postId !== null) url += `/${postId}`;
    if (lastId !== undefined && lastId !== null ) url += `/${lastId}`;
    if (userId !== undefined && userId !== null) url += `/${userId}`;
    
    activeStreamRef.current = streamType;

    // Create new connection
    eventSourceRef.current = new EventSource(url);
    
    // console.log(eventSourceRef.current);

    // Setup common handlers
    eventSourceRef.current.onopen = () => {
      console.log(`SSE ${streamType} stream connected`);
    };

    eventSourceRef.current.onerror = (error) => {
  
    //   console.log(`SSE error in ${streamType} stream:`, error);
    };

    // Clear previous handlers
    eventHandlersRef.current = {};

    // Register all provided handlers
    Object.entries(handlers).forEach(([event, handler]) => {
      const wrappedHandler = (e) => {
        try {
          const data = JSON.parse(e.data);
          handler(data);
        } catch (error) {
          console.error(`Error processing ${event} event:`, error);
        }
      };
      
      eventHandlersRef.current[event] = wrappedHandler;
      eventSourceRef.current.addEventListener(event, wrappedHandler);
    });
  };

  useEffect(() => {
    return () => disconnect();
  }, []);

  return { connectExclusive, disconnect };
};

export default useExclusiveServerEvents;