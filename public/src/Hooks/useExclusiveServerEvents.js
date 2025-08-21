// // hooks/useExclusiveServerEvents.js
// import { useEffect, useRef } from 'react';

// const useExclusiveServerEvents = () => {
//   const eventSourceRef = useRef(null);
//   const activeStreamRef = useRef(null);
//   const eventHandlersRef = useRef({});

//   const disconnect = () => {
//     if (eventSourceRef.current) {
//       try {
//         // Remove all event listeners
//         Object.entries(eventHandlersRef.current).forEach(([event, handler]) => {
//           try {
//             eventSourceRef.current.removeEventListener(event, handler);
//           } catch (err) {
//             console.warn(`Error removing ${event} listener:`, err);
//           }
//         });

//         // Clear handlers reference
//         eventHandlersRef.current = {};

//         // Close connection
//         eventSourceRef.current.close();
//         console.log(
//           `SSE stream disconnected from: ${
//             activeStreamRef.current || 'unknown'
//           }`
//         );
//       } catch (error) {
//         console.warn('Error during disconnect:', error);
//       } finally {
//         eventSourceRef.current = null;
//         activeStreamRef.current = null;
//       }
//     }
//   };

//   const connectExclusive = (
//     streamType,
//     { postId, lastId, userId },
//     handlers
//   ) => {
//     // Create a unique stream identifier that includes parameters
//     const streamIdentifier = `${streamType}_${postId || 'null'}_${
//       lastId || 'null'
//     }_${userId || 'null'}`;

//     // If already connected to this exact same stream with same parameters, do nothing
//     if (
//       activeStreamRef.current === streamIdentifier &&
//       eventSourceRef.current?.readyState === EventSource.OPEN
//     ) {
//       console.log(`Already connected to stream: ${streamIdentifier}`);
//       return;
//     }

//     // ALWAYS close any existing connection first (ensures mutual exclusivity)
//     // This guarantees ticket streams and comment streams cannot coexist
//     if (
//       eventSourceRef.current &&
//       eventSourceRef.current.readyState !== EventSource.CLOSED
//     ) {
//       console.log(
//         `Forcing closure of existing stream: ${activeStreamRef.current} to open: ${streamIdentifier}`
//       );
//       disconnect();

//       // Small delay to ensure cleanup is complete
//       setTimeout(() => {
//         establishConnection(
//           streamType,
//           { postId, lastId, userId },
//           handlers,
//           streamIdentifier
//         );
//       }, 10);
//       return;
//     }

//     establishConnection(
//       streamType,
//       { postId, lastId, userId },
//       handlers,
//       streamIdentifier
//     );
//   };

//   const establishConnection = (
//     streamType,
//     { postId, lastId, userId },
//     handlers,
//     streamIdentifier
//   ) => {
//     // Build URL according to your endpoint structure
//     let url = `/wp-json/robodesk/v1/stream/${streamType}`;
//     if (postId !== undefined && postId !== null) url += `/${postId}`;
//     if (lastId !== undefined && lastId !== null) url += `/${lastId}`;
//     if (userId !== undefined && userId !== null) url += `/${userId}`;

//     // Set active stream before creating connection
//     activeStreamRef.current = streamIdentifier;

//     try {
//       // Create new connection
//       eventSourceRef.current = new EventSource(url);

//       // Setup common handlers
//       eventSourceRef.current.onopen = () => {
//         console.log(`SSE stream connected: ${streamIdentifier}`);
//       };

//       eventSourceRef.current.onerror = (error) => {
//         console.error(`SSE error in stream ${streamIdentifier}:`, error);
//         // On error, clear the active stream reference
//         if (eventSourceRef.current?.readyState === EventSource.CLOSED) {
//           activeStreamRef.current = null;
//         }
//       };

//       eventSourceRef.current.onclose = () => {
//         console.log(`SSE stream closed: ${streamIdentifier}`);
//         activeStreamRef.current = null;
//       };

//       // Register all provided handlers
//       Object.entries(handlers).forEach(([event, handler]) => {
//         const wrappedHandler = (e) => {
//           try {
//             const data = JSON.parse(e.data);
//             handler(data);
//           } catch (error) {
//             console.error(
//               `Error processing ${event} event in ${streamIdentifier}:`,
//               error
//             );
//           }
//         };

//         eventHandlersRef.current[event] = wrappedHandler;
//         eventSourceRef.current.addEventListener(event, wrappedHandler);
//       });
//     } catch (error) {
//       console.error(
//         `Error creating SSE connection for ${streamIdentifier}:`,
//         error
//       );
//       activeStreamRef.current = null;
//     }
//   };

//   // Get current active stream info
//   const getActiveStream = () => {
//     return {
//       isConnected: eventSourceRef.current?.readyState === EventSource.OPEN,
//       streamIdentifier: activeStreamRef.current,
//       readyState: eventSourceRef.current?.readyState,
//     };
//   };

//   useEffect(() => {
//     return () => disconnect();
//   }, []);

//   return {
//     connectExclusive,
//     disconnect,
//     getActiveStream,
//   };
// };

// export default useExclusiveServerEvents;

// hooks/useExclusiveServerEvents.js
import { useEffect, useRef } from 'react';

const useExclusiveServerEvents = () => {
  const eventSourceRef = useRef(null);
  const activeStreamRef = useRef(null);
  const eventHandlersRef = useRef({});

  const disconnect = () => {
    if (eventSourceRef.current) {
      try {
        // Remove all event listeners
        Object.entries(eventHandlersRef.current).forEach(([event, handler]) => {
          try {
            eventSourceRef.current.removeEventListener(event, handler);
          } catch (err) {
            console.warn(`Error removing ${event} listener:`, err);
          }
        });

        // Clear handlers reference
        eventHandlersRef.current = {};

        // Close connection
        eventSourceRef.current.close();
      } catch (error) {
        console.warn('Error during disconnect:', error);
      } finally {
        eventSourceRef.current = null;
        activeStreamRef.current = null;
        console.log('SSE stream disconnected');
      }
    }
  };

  const connectExclusive = (
    streamType,
    { postId, lastId, userId },
    handlers
  ) => {
    // If already connected to this exact same stream, do nothing
    if (activeStreamRef.current === streamType) return;

    // Close any existing connection
    disconnect();

    // Build URL according to your endpoint structure
    let url = `/wp-json/robodesk/v1/stream/${streamType}`;
    if (postId !== undefined && postId !== null) url += `/${postId}`;
    if (lastId !== undefined && lastId !== null) url += `/${lastId}`;
    if (userId !== undefined && userId !== null) url += `/${userId}`;

    activeStreamRef.current = streamType;

    // console.log('The streaming url is', url);
    // Create new connection
    eventSourceRef.current = new EventSource(url);

    // Setup common handlers
    eventSourceRef.current.onopen = () => {
      //   console.log(`SSE ${streamType} stream connected`);
    };

    eventSourceRef.current.onerror = (error) => {
      //   console.log(`SSE error in ${streamType} stream:`, error);
    };

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
