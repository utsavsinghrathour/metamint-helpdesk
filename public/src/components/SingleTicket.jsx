import { useState, useRef, useEffect, useMemo } from 'react';
import { FaChevronLeft } from 'react-icons/fa';
import { FaRegFaceSmileBeam } from 'react-icons/fa6';
import { PiPaperPlaneRightFill } from 'react-icons/pi';
import { FiPaperclip } from 'react-icons/fi';
import { IoClose } from 'react-icons/io5';
import EmojiPicker from 'emoji-picker-react';
import defaultLogo from '../images/robodesk-logo.png';
import '../styles/MessageTabs.css';
import { sendReply } from '../utils/modules';
import { useAppData } from '../utils/AppDataContext';
import DefaultAvatar from '../images/default-avatar.jpg';
import useExclusiveServerEvents from '../Hooks/useExclusiveServerEvents.js';

// Skeleton Loader Component for Conversations
const ConversationSkeleton = ({ count = 6 }) => {
  const skeletonMessages = Array.from({ length: count }, (_, i) => {
    // Create more realistic message patterns
    const patterns = [
      { sender: 'other', lines: 2, width: '60%' },
      { sender: 'me', lines: 1, width: '45%' },
      { sender: 'me', lines: 2, width: '55%' },
      { sender: 'other', lines: 3, width: '65%' },
      { sender: 'other', lines: 1, width: '40%' },
      { sender: 'me', lines: 2, width: '50%' },
    ];
    return patterns[i % patterns.length];
  });

  return (
    <div className='rd-skeleton-container'>
      {skeletonMessages.map((msg, index) => {
        const prevMsg = index > 0 ? skeletonMessages[index - 1] : null;
        const isSameSender = prevMsg && prevMsg.sender === msg.sender;
        const marginBottom = isSameSender ? '4px' : '12px';

        return (
          <div
            key={index}
            className={`chat-message rd-flex rd-items-start ${
              msg.sender === 'me'
                ? 'rd-ml-auto sent'
                : 'rd-flex rd-gap-8 received'
            }`}
            style={{
              maxWidth: '70%',
              marginBottom,
              marginTop: index === 0 || !isSameSender ? '12px' : '4px',
            }}
          >
            {msg.sender === 'other' && (!isSameSender || index === 0) && (
              <div
                className='rd-skeleton-avatar rd-rounded-full'
                style={{
                  width: '30px',
                  height: '30px',
                  backgroundColor: '#e5e7eb',
                  flexShrink: 0,
                  animation: 'shimmer 2s infinite',
                }}
              />
            )}
            {msg.sender === 'other' && isSameSender && index !== 0 && (
              <div style={{ width: '30px', height: '30px' }} />
            )}
            <div
              className='message-content rd-skeleton-bubble'
              style={{
                backgroundColor: msg.sender === 'me' ? '#007BFF' : '#FFFFFF',
                border: msg.sender === 'other' ? '1px solid #E0E0E0' : 'none',
                borderRadius: '10px',
                padding: '8px 12px',
                width: msg.width,
                position: 'relative',
                overflow: 'hidden',
              }}
            >
              {/* Create multiple lines based on message pattern */}
              {Array.from({ length: msg.lines }, (_, lineIndex) => (
                <div
                  key={lineIndex}
                  className='rd-skeleton-line'
                  style={{
                    height: '14px',
                    backgroundColor:
                      msg.sender === 'me'
                        ? 'rgba(255, 255, 255, 0.3)'
                        : '#e5e7eb',
                    borderRadius: '4px',
                    marginBottom: lineIndex < msg.lines - 1 ? '6px' : '0',
                    width: lineIndex === msg.lines - 1 ? '60%' : '100%',
                  }}
                />
              ))}

              {/* Timestamp skeleton */}
              <div
                className='rd-skeleton-time'
                style={{
                  height: '10px',
                  width: '35px',
                  backgroundColor:
                    msg.sender === 'me'
                      ? 'rgba(255, 255, 255, 0.2)'
                      : '#d1d5db',
                  borderRadius: '3px',
                  marginTop: '8px',
                  marginLeft: msg.sender === 'me' ? 'auto' : '0',
                }}
              />

              {/* Shimmer effect overlay */}
              <div
                style={{
                  position: 'absolute',
                  top: 0,
                  left: '-100%',
                  width: '100%',
                  height: '100%',
                  background:
                    msg.sender === 'me'
                      ? 'linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent)'
                      : 'linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent)',
                  animation: 'shimmerMove 2s infinite',
                }}
              />
            </div>
          </div>
        );
      })}
      <style jsx='true'>{`
        @keyframes shimmer {
          0%,
          100% {
            opacity: 1;
          }
          50% {
            opacity: 0.7;
          }
        }

        @keyframes shimmerMove {
          0% {
            transform: translateX(0);
          }
          100% {
            transform: translateX(200%);
          }
        }
      `}</style>
    </div>
  );
};

const TicketClosed = ({ handleFocusedTextArea, setActiveTab }) => {
  return (
    <div className='rd-my-30'>
      <h4 className='heading-4 rd-fw-semi rd-mb-8'>Still have questions?</h4>
      <a
        href='#'
        onClick={(e) => (
          e.preventDefault(), setActiveTab(0), handleFocusedTextArea(true)
        )}
      >
        Start a new conversation
      </a>
    </div>
  );
};

// Cache management utilities
const messageCache = new Map();
const CACHE_DURATION = 0;

const getCachedMessages = (ticketId) => {
  const cached = messageCache.get(ticketId);
  if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
    return cached.messages;
  }
  return null;
};

const setCachedMessages = (ticketId, messages) => {
  messageCache.set(ticketId, {
    messages,
    timestamp: Date.now(),
  });
};

const SingleTicket = ({
  ticket,
  onBack,
  loggedInUserId,
  handleFocusedTextArea,
  setActiveTab,
}) => {
  const [message, setMessage] = useState('');
  const [showPicker, setShowPicker] = useState(false);
  const [previewImage, setPreviewImage] = useState(null);
  const [chatMessages, setChatMessages] = useState(() => {
    // Initialize with cached messages if available
    return getCachedMessages(ticket.id) || [];
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [perPage] = useState(7);
  const [offset, setOffset] = useState(null);

  const [totalComments, setTotalComments] = useState(0);
  const [isFetching, setIsFetching] = useState(false);
  const [initialLoadComplete, setInitialLoadComplete] = useState(false);
  const [isPaginationLoading, setIsPaginationLoading] = useState(false);

  const textareaRef = useRef(null);
  const fileInputRef = useRef(null);
  const chatContainerRef = useRef(null);
  const image_urls = window.robodeskData?.imageUrls || {};
  const previousScrollHeight = useRef(0);
  const beep = useRef(new Audio(wpAuth.notificationSound));
  const isAtTop = useRef(false);
  const hasConnected = useRef(false);
  const {
    loggedinContext,
    loggedInUsrId,
    updateTicketNotifications,
    isSingleTicketPage,
    setSingleTicketPage,
  } = useAppData();
  const { connectExclusive, disconnect } = useExclusiveServerEvents();

  const [ticketClosed, setTicketClosed] = useState(false);

  const [imageUpload, setImageUpload] = useState({
    imageUploading: false,
    imageUploadError: null,
    imageUploadMessage: null,
  });

  const [isMessagingActive, setIsisMessagingActive] = useState(true);

  useEffect(() => {
    setSingleTicketPage(true);

    return () => {
      setSingleTicketPage(false);
    };
  }, []);

  useEffect(() => {
    if (ticket.notifications) {
      updateTicketNotifications(ticket.id, 'yes');
    }
  }, []);

  useEffect(() => {
    // Load the beep sound
    beep.current.load();

    return () => {
      if (hasConnected.current) {
        hasConnected.current = false;
      }
    };
  }, []);

  const fetchComments = async (fetchOffset = 0, prepend = false) => {
    if (isFetching) return;
    setIsFetching(true);

    // Set pagination loading state for prepend operations
    if (prepend) {
      setIsPaginationLoading(true);
    }

    // Create an AbortController for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

    try {
      // Only set loading for initial load, not for pagination
      if (!initialLoadComplete && !getCachedMessages(ticket.id)) {
        setLoading(true);
      }
      setError(null);

      const response = await fetch(
        `/wp-json/wp/v2/comments?post=${ticket.id}&per_page=${perPage}&offset=${fetchOffset}&order=asc&comment_type=ticket_replies&_fields=id,content,date,author_data,time_ago,author_name,author_image,comment_action`,
        { signal: controller.signal }
      );

      clearTimeout(timeoutId);

      if (!response.ok) {
        throw new Error(`Failed to fetch comments: ${response.statusText}`);
      }

      const data = await response.json();
      const formattedComments = formatTicketComments(data);

      setChatMessages((prev) => {
        const newMessages = prepend
          ? [...formattedComments, ...prev]
          : formattedComments;

        const uniqueMessages = Array.from(
          new Map(newMessages.map((msg) => [msg.id, msg])).values()
        );

        // Cache the messages
        if (!prepend) {
          setCachedMessages(ticket.id, uniqueMessages);
        }

        return uniqueMessages;
      });

      if (!initialLoadComplete) {
        setInitialLoadComplete(true);
        setLoading(false);

        // Scroll to bottom after initial load
        if (!prepend) {
          setTimeout(() => {
            if (chatContainerRef.current) {
              chatContainerRef.current.scrollTop =
                chatContainerRef.current.scrollHeight;
            }
          }, 50);
        }
      }
      setIsFetching(false);
      setIsPaginationLoading(false);

      if (prepend && chatContainerRef.current) {
        // Maintain scroll position after prepending messages
        const newScrollHeight = chatContainerRef.current.scrollHeight;
        const scrollDiff = newScrollHeight - previousScrollHeight.current;
        chatContainerRef.current.scrollTop = scrollDiff;
      }
    } catch (err) {
      clearTimeout(timeoutId);

      if (err.name === 'AbortError') {
        console.log('Request timed out, using cached data if available');
        // If we have cached data, use it
        const cached = getCachedMessages(ticket.id);
        if (cached && cached.length > 0) {
          setChatMessages(cached);
          setError(null);
        } else {
          setError('Connection is slow. Messages may take time to load.');
        }
      } else {
        console.error('Failed to fetch comments:', err);
        setError('Failed to load messages. Please try again.');
      }

      if (!initialLoadComplete) {
        setInitialLoadComplete(true);
        setLoading(false);
      }
      setIsFetching(false);
      setIsPaginationLoading(false);
    }
  };

  const handleNewComment = (commentData) => {
    updateTicketNotifications(ticket.id, 'yes');
    setChatMessages((prev) => {
      const messageExists = prev.some((msg) => msg.id === commentData.id);

      if (messageExists) {
        return prev;
      }

      beep.current.play();

      const newMessage = {
        id: commentData.id,
        text: commentData.content?.rendered || '',
        sender: commentData.author_id === loggedInUserId ? 'me' : 'other',
        senderName: commentData.author_name || 'Unknown',
        image: commentData.author_image || DefaultAvatar,
        created_at: commentData.created_at || new Date().toISOString(),
        isAdmin: commentData.author_id !== ticket.author_id,
      };

      const uniqueMessages = Array.from(
        new Map([...prev, newMessage].map((msg) => [msg.id, msg])).values()
      );

      // Update cache with new messages
      const sorted = uniqueMessages.sort(
        (a, b) => new Date(a.created_at) - new Date(b.created_at)
      );
      setCachedMessages(ticket.id, sorted);

      return sorted;
    });
  };

  const formatTicketComments = (comments) => {
    const formattedComments = comments.map((comment) => {
      const authorId = comment.author_data?.id; // Use author_data.id
      const sender =
        String(authorId) === String(loggedInUserId) ? 'me' : 'other'; // Ensure type consistency

      return {
        id: comment.id,
        text: comment.content?.rendered || comment.content?.plain || '',
        sender,
        senderName: comment.author_name || 'Unknown',
        image:
          comment.author_data?.avatar || comment.author_image || DefaultAvatar,
        created_at: comment.date,
        action: comment.comment_action,
        time_ago: comment.time_ago,
      };
    });

    return formattedComments.sort(
      (a, b) => new Date(a.created_at) - new Date(b.created_at)
    );
  };

  // Initial fetch to get total comments and latest messages
  useEffect(() => {
    const fetchInitialComments = async () => {
      // If we have cached messages, display them immediately
      const cached = getCachedMessages(ticket.id);
      if (cached && cached.length > 0) {
        setChatMessages(cached);
        setLoading(false);
        setInitialLoadComplete(true);

        // Scroll to bottom after cached messages are set
        setTimeout(() => {
          if (chatContainerRef.current) {
            chatContainerRef.current.scrollTop =
              chatContainerRef.current.scrollHeight;
          }
        }, 50); // Small delay to ensure DOM updates
      }

      try {
        // Add timeout for the initial request too
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 second timeout

        const response = await fetch(
          `/wp-json/wp/v2/comments?post=${ticket.id}&per_page=1&order=asc&_fields=id`,
          { signal: controller.signal }
        );

        clearTimeout(timeoutId);

        if (!response.ok) {
          throw new Error('Failed to fetch total comments');
        }
        const total = parseInt(response.headers.get('X-WP-Total'), 10) || 0;
        setTotalComments(total);

        // If there are no comments at all, just set empty state
        if (total === 0) {
          setChatMessages([]);
          setLoading(false);
          setInitialLoadComplete(true);
          setError(null);
          return;
        }

        const initialOffset = Math.max(total - perPage, 0);
        setOffset(initialOffset);

        // Fetch comments whether we have cache or not
        await fetchComments(initialOffset, false);
      } catch (err) {
        if (err.name === 'AbortError') {
          console.log('Initial fetch timed out');
          if (!cached || cached.length === 0) {
            setError('Connection is slow. Messages may take time to load.');
          }
        } else {
          console.error('Failed to fetch initial comments:', err);
          if (!cached || cached.length === 0) {
            setError('Failed to load messages. Please try again.');
          }
        }
        setLoading(false);
        setInitialLoadComplete(true);
      }
    };

    fetchInitialComments();
  }, [ticket.id]);

  // Handle scrolling to load older messages

  useEffect(() => {
    const textarea = textareaRef.current;
    if (textarea) {
      textarea.style.height = 'auto';
      textarea.style.height = `${textarea.scrollHeight}px`;
    }
  }, [message]);

  // Scroll handling for loading older messages
  useEffect(() => {
    const chatContainer = chatContainerRef.current;
    if (!chatContainer) return;

    const handleScroll = () => {
      if (chatContainer.scrollTop <= 50 && offset > 0 && !isFetching) {
        // Store current scroll height before fetching
        previousScrollHeight.current = chatContainer.scrollHeight;
        const newOffset = Math.max(offset - perPage, 0);
        setOffset(newOffset);
        fetchComments(newOffset, true);
      }
    };

    chatContainer.addEventListener('scroll', handleScroll);
    return () => chatContainer.removeEventListener('scroll', handleScroll);
  }, [offset, isFetching]);

  // Scroll to bottom only for new messages, not pagination
  useEffect(() => {
    const chatContainer = chatContainerRef.current;
    // Only auto-scroll for initial load or new messages at bottom
    // Don't auto-scroll when loading older messages (pagination)
    if (chatContainer && !isFetching && !isPaginationLoading) {
      // Check if user is near bottom (within 100px)
      const isNearBottom =
        chatContainer.scrollHeight -
          chatContainer.scrollTop -
          chatContainer.clientHeight <
        100;

      // Only auto-scroll if near bottom or it's the initial load
      if (
        isNearBottom ||
        (initialLoadComplete && chatMessages.length <= perPage)
      ) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
      }
    }
  }, [chatMessages.length]);

  // Updated serverEvents with better error handling
  const serverEvents = (
    ticketId,
    lastCommentId,
    onNewComment,
    loggedInUserId
  ) => {
    const eventSource = new EventSource(
      `/wp-json/robodesk/v1/messaging/${ticketId}/${lastCommentId}/${loggedInUserId}`
    );

    eventSource.onopen = () => {
      console.log('SSE connection Single ticket opened');

      hasConnected.current = true;
    };

    eventSource.addEventListener('handshake', (event) => {
      // const handshake = JSON.parse(event.data);
      // console.log("New handshake data received:", handshake);
    });

    eventSource.addEventListener('new_comment', (event) => {
      const commentData = JSON.parse(event.data);

      //   console.log('New comment received:', commentData);
      if (typeof handleNewComment === 'function') {
        // Don't play beep here, let handleNewComment decide
        handleNewComment(commentData);
      }
    });

    eventSource.addEventListener('disconnect', (event) => {
      try {
        const data = JSON.parse(event.data);
        console.log('Server requested disconnect:', data);
      } catch (error) {
        console.error('Error parsing disconnect:', error);
      }
    });

    eventSource.addEventListener('retry', (event) => {
      console.log('Retrying connection...');
    });
    eventSource.addEventListener('ticket_open', (event) => {
      setTicketClosed(false);
    });
    eventSource.addEventListener('ticket_closed', (event) => {
      setTicketClosed(true);
      // eventSource.close();
    });
    eventSource.onerror = (error) => {};

    return () => {
      console.log('Closing SSE Connection');
      hasConnected.current = false;
      eventSource.close();
    };
  };

  const lastMessageId = useMemo(() => {
    if (!chatMessages || chatMessages.length === 0) return false;
    return chatMessages[chatMessages.length - 1].id;
  }, [chatMessages]);

  useEffect(() => {
    if (
      !hasConnected.current &&
      isMessagingActive &&
      loggedinContext &&
      lastMessageId &&
      initialLoadComplete &&
      !loading &&
      isSingleTicketPage
    ) {

      connectExclusive(
        'tickets',
        {
          postId: ticket.id,
          lastId: lastMessageId,
          userId: loggedInUsrId,
        },
        {
          new_comment: (data) => {
            const commentData = data;
            handleNewComment(commentData);
          },

          ticket_open: (data) => {
            setTicketClosed(false);
          },
          ticket_closed: (data) => {
            setTicketClosed(true);
          },
        }
      );

      return () => {};
    }
  }, [
    lastMessageId,
    initialLoadComplete,
    chatMessages,
    loading,
    isSingleTicketPage,
  ]);

  const handleSend = async () => {
    console.log('From handle send', message);
    if (message.trim() || previewImage) {
      const imageHTML = previewImage
        ? `<a href="${previewImage}" target="_blank"><img src="${previewImage}" alt="Preview" class="rd-comment-img"  /></a>`
        : false;
      const tempMessage = {
        id: `temp-${Date.now()}`,
        text: message + (imageHTML ? '<br>' + imageHTML : ''),
        sender: 'me',
        senderName: 'Me',
        image: DefaultAvatar,
        created_at: new Date().toISOString(),
        isSending: true,
        isOptimistic: true, // Mark as optimistic update
        ...(previewImage && { imageUrl: previewImage }),
      };

      // Optimistically add the message immediately
      setChatMessages((prev) => {
        const updated = [...prev, tempMessage];
        // Update cache with optimistic message
        setCachedMessages(ticket.id, updated);
        return updated;
      });

      setMessage('');
      setPreviewImage(null);

      // Send with timeout
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout for sending

      try {
        const result = await sendReply(
          ticket.id,
          loggedInUserId,
          message,
          previewImage,
          controller.signal // Pass the signal to sendReply if it supports it
        );

        clearTimeout(timeoutId);

        if (!result.data || !result.data.content) {
          throw new Error('Invalid response from server: missing content');
        }

        const newMessage = {
          id: result.data.id || tempMessage.id,
          text: result.data.content,
          sender: 'me',
          senderName: result.data.author_name || 'Me',
          image: result.data.author_image ?? '/default-avatar.jpg',
          created_at: result.data.created_at || tempMessage.created_at,
          isAdmin: result.data.author_id
            ? result.data.author_id !== ticket.author_id
            : false,
          ...(previewImage && { imageUrl: previewImage }),
        };

        // Replace temp message with real one
        setChatMessages((prev) => {
          const updated = [
            ...prev.filter((msg) => msg.id !== tempMessage.id),
            newMessage,
          ].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
          // Update cache
          setCachedMessages(ticket.id, updated);
          return updated;
        });
      } catch (err) {
        clearTimeout(timeoutId);

        if (err.name === 'AbortError') {
          // Keep the optimistic message but mark it as pending
          setChatMessages((prev) =>
            prev.map((msg) =>
              msg.id === tempMessage.id
                ? { ...msg, isPending: true, isSending: false }
                : msg
            )
          );
          setError(
            'Message is taking longer to send. It will be delivered when connection improves.'
          );
        } else {
          console.error('Error sending message:', err);
          // Remove the failed message
          setChatMessages((prev) => {
            const updated = prev.filter((msg) => msg.id !== tempMessage.id);
            setCachedMessages(ticket.id, updated);
            return updated;
          });
          setError('Failed to send message. Please try again.');
        }
      }
    }
  };

  const handleEmojiClick = (emojiObject) => {
    setMessage((prev) => prev + emojiObject.emoji);
    setShowPicker(false);
  };

  const toggleEmojiPicker = () => {
    setShowPicker((prev) => !prev);
  };

  const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const MAX_FILE_SIZE = 3145728;
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    const ERROR_MESSAGES = {
      INVALID_TYPE: 'Only JPEG, PNG, or GIF images are allowed.',
      SIZE_EXCEEDED: 'Image size must be less than 3MB.',
    };

    // Helper function to update state
    const updateUploadState = (state) =>
      setImageUpload({
        imageUploading: false,
        imageUploadMessage: null,
        imageUploadError: null,
        ...state,
      });

    // Reset input
    e.target.value = null;

    // Validate file type
    if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
      updateUploadState({
        imageUploadError: true,
        imageUploadMessage: ERROR_MESSAGES.INVALID_TYPE,
      });
      alert(ERROR_MESSAGES.INVALID_TYPE);
      return;
    }

    // Validate file size
    if (file.size > MAX_FILE_SIZE) {
      updateUploadState({
        imageUploadError: true,
        imageUploadMessage: ERROR_MESSAGES.SIZE_EXCEEDED,
      });
      alert(ERROR_MESSAGES.SIZE_EXCEEDED);
      return;
    }

    // Start upload process
    updateUploadState({ imageUploading: true });

    const reader = new FileReader();

    reader.onload = () => {
      setPreviewImage(reader.result);
      updateUploadState({});
    };

    reader.onerror = () => {
      updateUploadState({
        imageUploadError: true,
        imageUploadMessage: 'Error reading the file.',
      });
      alert('Error reading the file.');
    };

    reader.readAsDataURL(file);
  };
  const triggerFileInput = () => fileInputRef.current.click();

  const formatTimestamp = (timestamp) =>
    new Date(timestamp).toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit',
    });

  return (
    <div className='rd-single-ticket rd-flex rd-flex-col rd-h-full'>
      <div className='single-ticket-header rd-flex-col rd-flex rd-align-center rd-justify-between rd-border-bottom-light rd-px-18 rd-py-15'>
        <div className='rd-flex rd-align-center'>
          <button
            className='icon-dark back-button rd-flex rd-items-center rd-bg-transparent rd-border-none rd-p-0 rd-m-0 rd-back-arrow-position'
            onClick={onBack}
          >
            <FaChevronLeft />
          </button>
          {image_urls.wide_logo && image_urls.wide_logo.trim() !== '' ? (
            <img
              src={image_urls.wide_logo}
              alt='Plugin Wide Logo'
              className='rd-h-auto rd-header-logo'
              onError={(e) => (e.target.style.display = 'none')}
            />
          ) : image_urls.logo && image_urls.logo.trim() !== '' ? (
            <img
              src={image_urls.logo}
              alt='Plugin Logo'
              className='rd-h-auto rd-header-logo'
              onError={(e) => (e.target.style.display = 'none')}
            />
          ) : (
            <div className='rd-h-auto rd-header-logo default-logo'>
              <img
                src={defaultLogo}
                alt='Default Plugin Logo'
                className='rd-h-auto rd-header-logo'
              />
            </div>
          )}
        </div>
        <div className='rd-body-text rd-mt-30'>
          <p className='rd-text-center rd-body-text'>
            If you are interested in working with us, take a step towards
            discovering exciting opportunities by reaching out to us. We look
            forward to talking with you soon!
          </p>
        </div>
      </div>
      <div
        ref={chatContainerRef}
        className='chat-container rd-flex rd-flex-col rd-flex-grow rd-px-18 rd-py-15 rd-overflow-auto'
      >
        {/* Show pagination loader at the top when loading older messages */}
        {isPaginationLoading && (
          <div className='rd-pagination-loader rd-mb-10'>
            <ConversationSkeleton count={3} />
          </div>
        )}

        {/* Show connection warning if there's an error but we have cached messages */}
        {error && chatMessages.length > 0 && (
          <div
            className='rd-connection-warning rd-px-12 rd-py-8 rd-mb-10 rd-text-center'
            style={{
              backgroundColor: '#fff3cd',
              border: '1px solid #ffc107',
              borderRadius: '8px',
              color: '#856404',
              fontSize: '12px',
            }}
          >
            {error}
          </div>
        )}

        {loading && !initialLoadComplete ? (
          <ConversationSkeleton />
        ) : error && chatMessages.length === 0 ? (
          <p className='err_msg rd-text-center'>{error}</p>
        ) : chatMessages.length === 0 ? (
          <p className='rd-text-center rd-body-text'>
            No conversations yet. Start a conversation below.
          </p>
        ) : (
          <>
            {chatMessages.map((msg, index) => {
              const prevMsg = index > 0 ? chatMessages[index - 1] : null;
              const isSameSender = prevMsg && prevMsg.sender === msg.sender;
              const marginBottom = isSameSender ? '4px' : '12px';

              return (
                <div
                  key={msg.id}
                  className={`chat-message rd-flex rd-items-start ${
                    msg.sender === 'me' || msg.isSending
                      ? 'rd-ml-auto sent'
                      : 'rd-flex rd-gap-8 received'
                  }`}
                  style={{
                    maxWidth: '70%',
                    marginBottom,
                    marginTop: index === 0 || !isSameSender ? '12px' : '4px',
                  }}
                >
                  {(!isSameSender || index === 0) && (
                    <img
                      src={msg.image}
                      alt={msg.sender === 'me' ? 'You' : msg.senderName}
                      className='rd-rounded-full rd-object-cover'
                      style={{
                        height: '30px',
                        width: '30px',
                        borderRadius: '50px',
                        aspectRatio: '1',
                      }}
                    />
                  )}
                  {isSameSender && index !== 0 && (
                    <div style={{ width: '30px', height: '30px' }} />
                  )}
                  <div
                    className='message-content'
                    style={{
                      backgroundColor:
                        msg.sender === 'me' || msg.isSending
                          ? '#007BFF'
                          : '#FFFFFF',
                      color:
                        msg.sender === 'me' || msg.isSending
                          ? '#FFFFFF'
                          : '#000000',
                      border:
                        msg.sender === 'other' && !msg.isSending
                          ? '1px solid #E0E0E0'
                          : 'none',
                      borderRadius: '10px',
                      padding: '8px 12px',
                      maxWidth: '100%',
                    }}
                  >
                    {msg.isSending || msg.isPending ? (
                      <span
                        className='rd-body-text single-message'
                        style={{ opacity: msg.isPending ? 0.7 : 1 }}
                        dangerouslySetInnerHTML={{ __html: msg.text }}
                      />
                    ) : (
                      <>
                        {msg.text && (
                          <span
                            className={[
                              'rd-body-text',
                              'single-message',
                              msg.action ? 'rd-is-action' : null,
                            ]
                              .filter(Boolean)
                              .join(' ')}
                            dangerouslySetInnerHTML={{ __html: msg.text }}
                          />
                        )}
                      </>
                    )}
                    <div
                      className='rd-text-small rd-text-gray'
                      style={{
                        marginTop: '4px',
                        textAlign:
                          msg.sender === 'me' || msg.isSending
                            ? 'right'
                            : 'left',
                        fontSize: '10px',
                      }}
                    >
                      {msg.isSending
                        ? ''
                        : msg.isPending
                        ? 'Pending...'
                        : formatTimestamp(msg.created_at)}
                    </div>
                  </div>
                </div>
              );
            })}
          </>
        )}
      </div>
      <div
        className={`chat-input rd-flex rd-flex-col rd-px-18 rd-py-15 rd-border-top-light rd-bg-white  ${
          previewImage ? 'has-preview' : ''
        }`}
      >
        {previewImage && (
          <div className='rd-mb-10 rd-flex rd-align-center'>
            <div>
              <img
                src={previewImage}
                alt='Preview'
                className='rd-max-w-full rd-h-auto rd-border-radius-md rd-preview-image'
                style={{ maxWidth: '80px' }}
              />
              <button
                onClick={() => setPreviewImage(null)}
                className='rd-ml-10 rd-text-red-500 rd-border-none rd-cursor-pointer remove-preview rd-position-absolute rd-p-0'
              >
                <IoClose />
              </button>
            </div>
          </div>
        )}
        <div className='rd-flex rd-align-center rd-justify-between'>
          {!ticketClosed ? (
            <>
              <textarea
                id='mesg'
                ref={textareaRef}
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSend();
                  }
                }}
                placeholder='Write a message...'
                className='rd-flex-grow rd-border-none rd-outline-none rd-py-5 rd-w-full rd-resize-none rd-min-h-[40px]'
                rows='1'
              />
              <div className='rd-flex rd-gap-20 rd-relative'>
                <button
                  onClick={toggleEmojiPicker}
                  className='rd-bg-transparent rd-border-none rd-mr-10 icon-dark rd-cursor-pointer rd-p-0'
                >
                  <FaRegFaceSmileBeam className='icon-dark' size={20} />
                </button>
                {showPicker && (
                  <div className='rd-position-absolute rd-bottom-0 rd-right-0 rd-top-0 emoji-picker-wrap'>
                    <EmojiPicker onEmojiClick={handleEmojiClick} />
                  </div>
                )}
                <div className='rd-flex'>
                  <input
                    type='file'
                    ref={fileInputRef}
                    onChange={handleFileUpload}
                    accept='image/*'
                    className='rd-hidden'
                  />
                  <button
                    onClick={triggerFileInput}
                    className='rd-bg-transparent rd-border-none rd-mr-10 rd-cursor-pointer rd-p-0 icon-dark'
                  >
                    <FiPaperclip className='icon-dark' size={20} />
                  </button>
                </div>
                <button
                  onClick={handleSend}
                  className='rd-bg-transparent rd-border-none rd-ml-10 icon-dark rd-cursor-pointer rd-p-0'
                >
                  <PiPaperPlaneRightFill className='icon-dark' size={20} />
                </button>
              </div>
            </>
          ) : (
            <TicketClosed
              handleFocusedTextArea={handleFocusedTextArea}
              setActiveTab={setActiveTab}
            />
          )}
        </div>
      </div>
    </div>
  );
};

export default SingleTicket;
