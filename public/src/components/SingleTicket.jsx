import { useState, useRef, useEffect, useMemo } from "react";
import { FaChevronLeft, FaEnvelope } from "react-icons/fa";
import { FaRegFaceSmileBeam } from "react-icons/fa6";
import { PiPaperPlaneRightFill } from "react-icons/pi";
import { FiPaperclip } from "react-icons/fi";
import { IoClose } from "react-icons/io5";
import EmojiPicker, { EmojiStyle } from "emoji-picker-react";
import defaultLogo from "../images/mmhd-logo.png";
import "../styles/MessageTabs.css";
import { sendReply } from "../utils/modules";
import { useAppData } from "../utils/AppDataContext";
import DefaultAvatar from "../images/default-avatar.jpg";
import { sseManager } from "../Hooks/sseManager";
import { playMetamintHelpdeskBeep } from "../utils/notificationSound.js";

// Helper to escape HTML in plain text
const escapeHtml = (unsafe) =>
  String(unsafe)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

// Normalize message text: if it already contains HTML tags, return as-is;
// otherwise escape and convert newlines to <br /> so they render correctly.
const normalizeMessageText = (text) => {
  if (!text && text !== 0) return "";
  const str = String(text);
  // crude check for HTML tags
  const hasHtml = /<[^>]+>/.test(str);
  if (hasHtml) return str;
  return escapeHtml(str).replace(/\r\n|\r|\n/g, "<br />");
};

const getMessageSortId = (message) => {
  const numericId = Number(message?.id);
  return Number.isFinite(numericId) ? numericId : 0;
};

// Skeleton Loader Component for Conversations
const ConversationSkeleton = ({ count = 6 }) => {
  const skeletonMessages = Array.from({ length: count }, (_, i) => {
    // Create more realistic message patterns
    const patterns = [
      { sender: "other", lines: 2, width: "60%" },
      { sender: "me", lines: 1, width: "45%" },
      { sender: "me", lines: 2, width: "55%" },
      { sender: "other", lines: 3, width: "65%" },
      { sender: "other", lines: 1, width: "40%" },
      { sender: "me", lines: 2, width: "50%" },
    ];
    return patterns[i % patterns.length];
  });

  return (
    <div className="mmhd-skeleton-container" style={{ width: "100%" }}>
      {skeletonMessages.map((msg, index) => {
        const prevMsg = index > 0 ? skeletonMessages[index - 1] : null;
        const isSameSender = prevMsg && prevMsg.sender === msg.sender;
        const marginBottom = isSameSender ? "4px" : "12px";

        return (
          <div
            key={index}
            className={`chat-message mmhd-flex mmhd-items-start ${msg.sender === "me"
              ? "mmhd-justify-end"
              : "mmhd-justify-start mmhd-gap-8"
              }`}
            style={{
              maxWidth: "100%",
              marginBottom,
              marginTop: index === 0 || !isSameSender ? "12px" : "4px",
              width: "100%",
              display: "flex",
            }}
          >
            {msg.sender === "other" && (!isSameSender || index === 0) && (
              <div
                className="mmhd-skeleton-avatar "
                style={{
                  width: "30px",
                  height: "30px",
                  borderRadius: "50px",
                  backgroundColor: "#e5e7eb",
                  flexShrink: 0,
                  animation: "shimmer 2s infinite",
                }}
              />
            )}
            {msg.sender === "other" && isSameSender && index !== 0 && (
              <div style={{ width: "30px", height: "30px" }} />
            )}
            <div
              className="message-content mmhd-skeleton-bubble"
              style={{
                backgroundColor: msg.sender === "me" ? "#007BFF" : "#FFFFFF",
                border: msg.sender === "other" ? "1px solid #E0E0E0" : "none",
                borderRadius: "10px",
                padding: "8px 12px",
                width: msg.width,
                position: "relative",
                overflow: "hidden",
                maxWidth: "70%",
              }}
            >
              {Array.from({ length: msg.lines }, (_, lineIndex) => (
                <div
                  key={lineIndex}
                  className="mmhd-skeleton-line"
                  style={{
                    height: "14px",
                    backgroundColor:
                      msg.sender === "me"
                        ? "rgba(255, 255, 255, 0.3)"
                        : "#e5e7eb",
                    borderRadius: "4px",
                    marginBottom: lineIndex < msg.lines - 1 ? "6px" : "0",
                    width: lineIndex === msg.lines - 1 ? "60%" : "100%",
                  }}
                />
              ))}
              <div
                className="mmhd-skeleton-time"
                style={{
                  height: "10px",
                  width: "35px",
                  backgroundColor:
                    msg.sender === "me"
                      ? "rgba(255, 255, 255, 0.2)"
                      : "#d1d5db",
                  borderRadius: "3px",
                  marginTop: "8px",
                  marginLeft: msg.sender === "me" ? "auto" : "0",
                }}
              />
              <div
                style={{
                  position: "absolute",
                  top: 0,
                  left: "-100%",
                  width: "100%",
                  height: "100%",
                  background:
                    msg.sender === "me"
                      ? "linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent)"
                      : "linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent)",
                  animation: "shimmerMove 2s infinite",
                }}
              />
            </div>
          </div>
        );
      })}
      <style jsx="true">{`
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

        .mmhd-skeleton-container {
          display: flex;
          flex-direction: column;
          width: 100%;
        }

        .chat-message {
          display: flex;
          width: 100%;
        }

        .mmhd-justify-end {
          justify-content: flex-end;
        }

        .mmhd-justify-start {
          justify-content: flex-start;
        }
      `}</style>
    </div>
  );
};

const TicketClosed = ({ handleFocusedTextArea, setActiveTab }) => {
  return (
    <div>
      <h5 className="mmhd-fw-semi mmhd-mb-5 mmhd-body-text">Still have questions?</h5>
      <a
        href="#"
        className="mmhd-title-text"
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
  const ticketIntroMessage =
    window.mmhdData?.ticketIntroMessage ||
    "If you are interested in working with us, take a step towards discovering exciting opportunities by reaching out to us. We look forward to talking with you soon!";

  const [message, setMessage] = useState("");
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
  const sendInFlightRef = useRef(false);
  const image_urls = window.mmhdData?.imageUrls || {};
  const customerAvatar = ticket?.authorImage || DefaultAvatar;
  const configuredBotAvatar =
    window.mmhdData?.mmhd_bot_profile_image || "";
  const configuredBotName =
    window.mmhdData?.mmhd_bot_title_name || "Bot";
  const previousScrollHeight = useRef(0);
  const isAtTop = useRef(false);
  const [streamCursorId, setStreamCursorId] = useState(null);
  // Tracks message ids we've already beeped so the sound plays exactly once
  // per new reply, regardless of whether SSE or the polling fallback delivers
  // it first (and regardless of stale closure values).
  const beepedIds = useRef(new Set());

  const formatTimestamp = (timestamp) =>
    new Date(timestamp).toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    });

  const {
    loggedinContext,
    loggedInUsrId,
    ticketsContext,
    updateTicketNotifications,
    isSingleTicketPage,
    setSingleTicketPage,
    setOpenTicketId,
  } = useAppData();


  const [ticketClosed, setTicketClosed] = useState(false);

  const [imageUpload, setImageUpload] = useState({
    imageUploading: false,
    imageUploadError: null,
    imageUploadMessage: null,
  });

  const [isMessagingActive, setIsisMessagingActive] = useState(true);
  const [emailSending, setEmailSending] = useState(false);
  const [emailSent, setEmailSent] = useState(false);

  useEffect(() => {
    setSingleTicketPage(true);
    setOpenTicketId(ticket.id);
    sseManager.disconnect("comments");

    return () => {
      setSingleTicketPage(false);
      setOpenTicketId(null);
    };
  }, []);

  useEffect(() => {
    if (ticket.notifications) {
      updateTicketNotifications(ticket.id, "yes");
    }
  }, []);

  // Prime the browser audio engine on the first user gesture so that
  // programmatic notification beeps are not blocked by the autoplay policy.
  useEffect(() => {
    const unlock = () => {
      const sound = window.mmhdAuth?.notificationSound;
      if (sound) {
        const audio = new Audio(sound);
        const p = audio.play();
        if (p && typeof p.then === "function") {
          p.then(() => audio.pause()).catch(() => {});
        }
      }
      window.removeEventListener("pointerdown", unlock);
      window.removeEventListener("keydown", unlock);
      window.removeEventListener("touchstart", unlock);
    };

    window.addEventListener("pointerdown", unlock);
    window.addEventListener("keydown", unlock);
    window.addEventListener("touchstart", unlock);
    return () => {
      window.removeEventListener("pointerdown", unlock);
      window.removeEventListener("keydown", unlock);
      window.removeEventListener("touchstart", unlock);
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
        `/wp-json/wp/v2/comments?post=${ticket.id}&per_page=${perPage}&offset=${fetchOffset}&order=asc&comment_type=ticket_replies&_fields=id,content,date,author_data,time_ago,author_name,author_image,comment_action,is_bot_response`,
        {
          signal: controller.signal,
          headers: {
            "X-WP-Nonce":
              window.mmhdAuth?.restNonce || window.wpApiSettings?.nonce || "",
          },
        },
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
          new Map(newMessages.map((msg) => [msg.id, msg])).values(),
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

      if (err.name === "AbortError") {
        // If we have cached data, use it
        const cached = getCachedMessages(ticket.id);
        if (cached && cached.length > 0) {
          setChatMessages(cached);
          setError(null);
        } else {
          setError("Connection is slow. Messages may take time to load.");
        }
      } else {
        console.error("Failed to fetch comments:", err);
        setError("Failed to load messages. Please try again.");
      }

      if (!initialLoadComplete) {
        setInitialLoadComplete(true);
        setLoading(false);
      }
      setIsFetching(false);
      setIsPaginationLoading(false);
    }
  };

  const handleNewComment = (commentData, play = true) => {
    updateTicketNotifications(ticket.id, "yes");
    const incomingCommentId = Number(commentData?.id);
    if (Number.isFinite(incomingCommentId) && incomingCommentId > 0) {
      setStreamCursorId((current) =>
        Math.max(Number(current) || 0, incomingCommentId),
      );
    }

    let isNew = false;

    setChatMessages((prev) => {
      const messageExists = prev.some(
        (msg) => String(msg.id) === String(commentData.id),
      );

      if (messageExists) {
        return prev;
      }

      isNew = true;

      const isBotMessage =
        commentData.is_bot_response === true ||
        commentData.is_bot_response === "1";
      const isOwnMessage =
        !isBotMessage &&
        String(commentData.author_id) === String(loggedInUserId);

      const newMessage = {
        id: commentData.id,
        text: normalizeMessageText(commentData.content?.rendered || ""),
        sender: isOwnMessage ? "me" : "other",
        senderName: isBotMessage
          ? commentData.author_name || configuredBotName
          : commentData.author_name || "Unknown",
        image: isBotMessage
          ? configuredBotAvatar || commentData.author_image || DefaultAvatar
          : isOwnMessage
            ? customerAvatar
            : commentData.author_data?.avatar ||
            commentData.author_image ||
            DefaultAvatar,
        isBot: isBotMessage,
        author_id: commentData.author_id || commentData.author_data?.id || 0,
        created_at: commentData.created_at || new Date().toISOString(),
        time_ago: commentData.time_ago,
        isAdmin: commentData.author_id !== ticket.author_id,
      };

      const uniqueMessages = Array.from(
        new Map([...prev, newMessage].map((msg) => [msg.id, msg])).values(),
      );

      // Update cache with new messages
      const sorted = uniqueMessages.sort(
        (a, b) => getMessageSortId(a) - getMessageSortId(b),
      );
      setCachedMessages(ticket.id, sorted);

      return sorted;
    });

    if (isNew && play) {
      const cid = Number(commentData?.id);
      const authorId = commentData?.author_id || commentData?.author_data?.id;
      // Only beep for replies from other people, not the viewer's own messages.
      if (
        cid &&
        String(authorId) !== String(loggedInUserId) &&
        !beepedIds.current.has(cid)
      ) {
        beepedIds.current.add(cid);
        playMetamintHelpdeskBeep();
      }
    }
  };

  const formatTicketComments = (comments) => {
    const formattedComments = comments.map((comment) => {
      const authorId = comment.author_data?.id || comment.author || 0;
      const isBotFlag =
        comment.is_bot_response === true || comment.is_bot_response === "1";
      const sender =
        !isBotFlag && String(authorId) === String(loggedInUserId)
          ? "me"
          : "other";

      return {
        id: comment.id,
        text: normalizeMessageText(
          comment.content?.rendered || comment.content?.plain || "",
        ),
        sender,
        senderName: isBotFlag
          ? comment.author_name || configuredBotName
          : comment.author_name || "Unknown",
        image: isBotFlag
          ? configuredBotAvatar || comment.author_image || DefaultAvatar
          : sender === "me"
            ? customerAvatar
            : comment.author_data?.avatar ||
            comment.author_image ||
            DefaultAvatar,
        isBot: isBotFlag,
        author_id: authorId,
        created_at: comment.date,
        action: comment.comment_action,
        time_ago: comment.time_ago,
      };
    });

    return formattedComments.sort(
      (a, b) => getMessageSortId(a) - getMessageSortId(b),
    );
  };

  // Initial fetch to get total comments and latest messages
  useEffect(() => {
    const fetchInitialComments = async () => {
      setStreamCursorId(null);

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
        }, 50);
      }

      try {
        // Add timeout for the initial request too
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 second timeout

        const response = await fetch(
          `/wp-json/wp/v2/comments?post=${ticket.id}&per_page=1&order=asc&_fields=id`,
          {
            signal: controller.signal,
            headers: {
              "X-WP-Nonce":
                window.mmhdAuth?.restNonce || window.wpApiSettings?.nonce || "",
            },
          },
        );

        clearTimeout(timeoutId);

        if (!response.ok) {
          throw new Error("Failed to fetch total comments");
        }
        const total = parseInt(response.headers.get("X-WP-Total"), 10) || 0;
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
        if (err.name === "AbortError") {
          if (!cached || cached.length === 0) {
            setError("Connection is slow. Messages may take time to load.");
          }
        } else {
          console.error("Failed to fetch initial comments:", err);
          if (!cached || cached.length === 0) {
            setError("Failed to load messages. Please try again.");
          }
        }
        setLoading(false);
        setInitialLoadComplete(true);
      }
    };

    fetchInitialComments();
  }, [ticket.id]);

  useEffect(() => {
    if (initialLoadComplete && !streamCursorId && chatMessages.length > 0) {
      const latestMessageId = chatMessages[chatMessages.length - 1]?.id;
      if (latestMessageId) {
        setStreamCursorId(latestMessageId);
      }
    }
  }, [initialLoadComplete, chatMessages, streamCursorId]);

  // Handle scrolling to load older messages

  useEffect(() => {
    const textarea = textareaRef.current;
    if (textarea) {
      textarea.style.height = "auto";
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

    chatContainer.addEventListener("scroll", handleScroll);
    return () => chatContainer.removeEventListener("scroll", handleScroll);
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

  const url = useMemo(() => {
    if (!ticket?.id) return null;
    if (!loggedinContext && !loggedInUsrId) return null;
    if (!streamCursorId) return null;
    const nonce = window.mmhdAuth?.restNonce || window.wpApiSettings?.nonce || '';
    return `/wp-json/mmhd/v1/stream/comments/${ticket.id}/${streamCursorId}/${loggedInUsrId}?_wpnonce=${nonce}`;
  }, [streamCursorId, ticket.id, loggedInUsrId, loggedinContext]);

  useEffect(() => {
    if (!url) return undefined;

    sseManager.connect(url, "comments");

    const unsubscribeNewComment = sseManager.on("new_comment", (commentData) => {
      handleNewComment(commentData);
    }, "comments");

    const unsubscribeNewMineComment = sseManager.on("new_mine_comment", (commentData) => {
      handleNewComment(commentData, false);
    }, "comments");

    const unsubscribeTicketOpen = sseManager.on("ticket_open", () => {
      setTicketClosed(false);
    }, "comments");

    const unsubscribeTicketClosed = sseManager.on("ticket_closed", () => {
      setTicketClosed(true);
    }, "comments");

    return () => {
      unsubscribeNewComment();
      unsubscribeNewMineComment();
      unsubscribeTicketOpen();
      unsubscribeTicketClosed();
      sseManager.disconnect("comments");
    };
  }, [url]);

  useEffect(() => {
    if (!initialLoadComplete || !ticket?.id) return undefined;

    let cancelled = false;

    const pollLatestComments = async () => {
      if (sendInFlightRef.current) return;
      try {
        const response = await fetch(
          `/wp-json/wp/v2/comments?post=${ticket.id}&per_page=20&order=desc&comment_type=ticket_replies&_fields=id,content,date,author_data,time_ago,author_name,author_image,comment_action,is_bot_response`,
          {
            cache: "no-store",
            headers: {
              "X-WP-Nonce":
                window.mmhdAuth?.restNonce || window.wpApiSettings?.nonce || "",
            },
          },
        );

        if (!response.ok) return;

        const data = await response.json();
        if (cancelled || !Array.isArray(data) || data.length === 0) return;

        const formattedComments = formatTicketComments(data);

        let hasIncomingFromOther = false;
        setChatMessages((prev) => {
          const merged = new Map(prev.map((msg) => [String(msg.id), msg]));
          let hasNewMessages = false;

          formattedComments.forEach((msg) => {
            const key = String(msg.id);
            if (!merged.has(key)) {
              merged.set(key, msg);
              hasNewMessages = true;
              // Beep (once) for new replies from other people. The beepedIds
              // guard prevents double-beeping regardless of delivery order.
              // Only beep once the conversation is already on screen, so the
              // initial batch loaded when the ticket is opened never beeps.
              const cid = getMessageSortId(msg);
              if (
                prev.size > 0 &&
                msg.sender === "other" &&
                cid &&
                !beepedIds.current.has(cid)
              ) {
                beepedIds.current.add(cid);
                hasIncomingFromOther = true;
              }
            }
          });

          if (!hasNewMessages) return prev;

          const sorted = Array.from(merged.values()).sort(
            (a, b) => getMessageSortId(a) - getMessageSortId(b),
          );
          setCachedMessages(ticket.id, sorted);

          const latestId = sorted.reduce(
            (max, msg) => Math.max(max, getMessageSortId(msg)),
            0,
          );
          if (latestId > 0) {
            setStreamCursorId((current) =>
              Math.max(Number(current) || 0, latestId),
            );
          }

          return sorted;
        });

        // The SSE new_comment handler also beeps; this covers the case where
        // the poll delivers the message first.
        if (hasIncomingFromOther) {
          playMetamintHelpdeskBeep();
        }
      } catch (error) {
        console.error("Failed to poll latest comments:", error);
      }
    };

    pollLatestComments();
    const intervalId = setInterval(pollLatestComments, 4000);

    return () => {
      cancelled = true;
      clearInterval(intervalId);
    };
  }, [initialLoadComplete, ticket.id, loggedInUserId]);

  const handleSend = async () => {
    // console.log('From handle send', message);
    const outgoingMessage = message;
    const outgoingPreviewImage = previewImage;

    if (sendInFlightRef.current) return;

    if (outgoingMessage.trim() || outgoingPreviewImage) {
      sendInFlightRef.current = true;

      const imageHTML = outgoingPreviewImage
        ? `<a href="${outgoingPreviewImage}" target="_blank"><img src="${outgoingPreviewImage}" alt="Preview" class="mmhd-comment-img"  /></a>`
        : false;
      const tempMessage = {
        id: `temp-${Date.now()}`,
        text:
          normalizeMessageText(outgoingMessage) +
          (imageHTML ? "<br>" + imageHTML : ""),
        sender: "me",
        senderName: "Me",
        image: customerAvatar,
        created_at: new Date().toISOString(),
        isSending: true,
        isOptimistic: true, // Mark as optimistic update
        ...(outgoingPreviewImage && { imageUrl: outgoingPreviewImage }),
      };

      // Optimistically add the message immediately
      setChatMessages((prev) => {
        const updated = [...prev, tempMessage];

        setCachedMessages(ticket.id, updated);
        return updated;
      });

      setMessage("");
      setPreviewImage(null);

      // Send with timeout
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout for sending

      try {
        const result = await sendReply(
          ticket.id,
          loggedInUserId,
          outgoingMessage,
          outgoingPreviewImage,
          false,
          controller.signal,
        );

        clearTimeout(timeoutId);

        if (!result.data || !result.data.content) {
          throw new Error("Invalid response from server: missing content");
        }

        const newMessage = {
          id: result.data.id || tempMessage.id,
          text: normalizeMessageText(result.data.content || ""),
          sender: "me",
          senderName: result.data.author_name || "Me",
          image: customerAvatar,
          created_at: result.data.created_at || tempMessage.created_at,
          isAdmin: result.data.author_id
            ? result.data.author_id !== ticket.author_id
            : false,
          ...(outgoingPreviewImage && { imageUrl: outgoingPreviewImage }),
        };

        // Replace temp message with real one
        setChatMessages((prev) => {
          const updated = [
            ...prev.filter(
              (msg) =>
                String(msg.id) !== String(tempMessage.id) &&
                String(msg.id) !== String(newMessage.id),
            ),
            newMessage,
          ].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
          // Update cache
          setCachedMessages(ticket.id, updated);
          return updated;
        });
        const sentCommentId = Number(newMessage.id);
        if (Number.isFinite(sentCommentId) && sentCommentId > 0) {
          setStreamCursorId((current) =>
            Math.max(Number(current) || 0, sentCommentId),
          );
        }
      } catch (err) {
        clearTimeout(timeoutId);

        if (err.name === "AbortError") {
          // Keep the optimistic message but mark it as pending
          setChatMessages((prev) =>
            prev.map((msg) =>
              msg.id === tempMessage.id
                ? { ...msg, isPending: true, isSending: false }
                : msg,
            ),
          );
          setError(
            "Message is taking longer to send. It will be delivered when connection improves.",
          );
        } else {
          console.error("Error sending message:", err);
          // Remove the failed message
          setChatMessages((prev) => {
            const updated = prev.filter((msg) => msg.id !== tempMessage.id);
            setCachedMessages(ticket.id, updated);
            return updated;
          });
          setError("Failed to send message. Please try again.");
        }
      } finally {
        sendInFlightRef.current = false;
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
    const ALLOWED_IMAGE_TYPES = ["image/jpeg", "image/png", "image/gif"];
    const ERROR_MESSAGES = {
      INVALID_TYPE: "Only JPEG, PNG, or GIF images are allowed.",
      SIZE_EXCEEDED: "Image size must be less than 3MB.",
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
        imageUploadMessage: "Error reading the file.",
      });
      alert("Error reading the file.");
    };

    reader.readAsDataURL(file);
  };
  const triggerFileInput = () => fileInputRef.current.click();

  const handleEmailTranscript = async () => {
    if (emailSending || emailSent || !ticket?.id) return;
    setEmailSending(true);
    try {
      const nonce = window.mmhdAuth?.restNonce || window.wpApiSettings?.nonce || '';
      const res = await fetch('/wp-json/mmhd/v1/send-chat-transcript', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify({ ticket_id: ticket.id }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.message || 'Failed to send email');
      setEmailSent(true);
    } catch (err) {
      alert(err.message || 'Failed to send email. Please try again.');
    } finally {
      setEmailSending(false);
    }
  };

  return (
    <div className="mmhd-single-ticket mmhd-flex mmhd-flex-col mmhd-h-full">
      <div className="single-ticket-header mmhd-search-header mmhd-bg-blue mmhd-flex mmhd-items-center mmhd-justify-between mmhd-flex-col mmhd-message-list-header">
        <button
          className="back-button mmhd-flex mmhd-items-center mmhd-gap-1 mmhd-border-none mmhd-bg-transparent mmhd-m-0 mmhd-p-0"
          onClick={onBack}
        >
          <FaChevronLeft />
        </button>
        <button
          onClick={handleEmailTranscript}
          disabled={emailSending || emailSent}
          className="mmhd-flex mmhd-items-center mmhd-gap-1 mmhd-border-none mmhd-bg-transparent mmhd-m-0 mmhd-p-0 mmhd-cursor-pointer"
          title={emailSent ? "Email sent" : "Email me this conversation"}
            style={{
              position: "absolute",
              right: "18px",
              top: "20px",
              color: emailSent ? "#4CAF50" : "#fff",
            }}
        >
          <FaEnvelope size={16} />
        </button>
        {image_urls.wide_logo && image_urls.wide_logo.trim() !== "" ? (
          <img
            src={image_urls.wide_logo}
            alt="Plugin Wide Logo"
            className="mmhd-header-logo mmhd-m-auto"
            onError={(e) => (e.target.style.display = "none")}
          />
        ) : image_urls.logo && image_urls.logo.trim() !== "" ? (
          <img
            src={image_urls.logo}
            alt="Plugin Logo"
            className="mmhd-header-logo mmhd-m-auto"
            onError={(e) => (e.target.style.display = "none")}
          />
        ) : (
          <div className="mmhd-header-logo default-logo">
            <img
              src={defaultLogo}
              alt="Default Plugin Logo"
              className="mmhd-header-logo mmhd-m-auto"
            />
          </div>
        )}
      </div>
      <div className="mmhd-single-chat-body-wrapper">
        <div className="mmhd-px-18 mmhd-py-15 mmhd-border-bottom-light mmhd-single-chat-intro">
          <div className="mmhd-body-text">
            <p className="mmhd-text-center mmhd-body-text mmhd-mb-0">
              {ticketIntroMessage}
            </p>
          </div>
        </div>
        <div
          ref={chatContainerRef}
          className={`chat-container mmhd-flex mmhd-flex-col mmhd-flex-grow mmhd-px-18 mmhd-py-15 mmhd-overflow-auto ${ticketClosed ? "ticket-closed" : ""
            }`}
        >
          {/* Show pagination loader at the top when loading older messages */}
          {isPaginationLoading && (
            <div className="mmhd-pagination-loader mmhd-mb-10">
              <ConversationSkeleton count={3} />
            </div>
          )}

          {/* Show connection warning if there's an error but we have cached messages */}
          {error && chatMessages.length > 0 && (
            <div
              className="mmhd-connection-warning mmhd-px-12 mmhd-py-8 mmhd-mb-10 mmhd-text-center"
              style={{
                backgroundColor: "#fff3cd",
                border: "1px solid #ffc107",
                borderRadius: "8px",
                color: "#856404",
                fontSize: "12px",
              }}
            >
              {error}
            </div>
          )}

          {loading && !initialLoadComplete ? (
            <ConversationSkeleton />
          ) : error && chatMessages.length === 0 ? (
            <p className="err_msg mmhd-text-center">{error}</p>
          ) : chatMessages.length === 0 ? (
            <p className="mmhd-text-center mmhd-body-text">
              No conversations yet. Start a conversation below.
            </p>
          ) : (
            <>
              {chatMessages.map((msg, index) => {
                const prevMsg = index > 0 ? chatMessages[index - 1] : null;
                const isSameSender =
                  !!prevMsg &&
                  prevMsg.sender === msg.sender &&
                  !!prevMsg.isBot === !!msg.isBot &&
                  String(prevMsg.author_id || "") ===
                  String(msg.author_id || "");
                const marginBottom = isSameSender ? "4px" : "12px";

                return (
                  <div
                    key={msg.id}
                    className={`chat-message mmhd-flex mmhd-items-start ${msg.sender === "me" || msg.isSending
                      ? "mmhd-ml-auto sent"
                      : "mmhd-flex mmhd-gap-8 received"
                      }`}
                    style={{
                      maxWidth: "80%",
                      marginBottom,
                      marginTop: index === 0 || !isSameSender ? "12px" : "4px",
                    }}
                  >
                    {(!isSameSender || index === 0) &&
                      (() => {
                        // Bot avatar handling: prefer configured bot profile image, then the server-provided Gravatar fallback.
                        const botProfileImage =
                          configuredBotAvatar || msg.image || "";
                        const botTitleName =
                          configuredBotName || msg.senderName || "Bot";
                        const isBot = !!msg.isBot;

                        if (isBot) {
                          return (
                            <img
                              src={botProfileImage || DefaultAvatar}
                              alt={botTitleName || "Bot"}
                              className="mmhd-rounded-full mmhd-object-cover mmhd-single-message-avatar"
                              onError={(e) => {
                                e.target.onerror = null;
                                e.target.src = DefaultAvatar;
                              }}
                            />
                          );
                        }

                        if (msg.sender === "me" || msg.isSending) {
                          return (
                            <img
                              src={customerAvatar}
                              alt={"You"}
                              className="mmhd-rounded-full mmhd-object-cover mmhd-single-message-avatar"
                            />
                          );
                        }

                        // regular non-bot other user
                        return (
                          <img
                            src={msg.image || DefaultAvatar}
                            alt={msg.senderName}
                            className="mmhd-rounded-full mmhd-object-cover mmhd-single-message-avatar"
                          />
                        );
                      })()}
                    {isSameSender && index !== 0 && (
                      <div
                        style={{
                          width: "30px",
                          height: "30px",
                          flexShrink: 0,
                        }}
                      />
                    )}
                    <div
                      className="message-content"
                      style={{
                        backgroundColor:
                          msg.sender === "me" || msg.isSending
                            ? "#007BFF"
                            : "#FFFFFF",
                        color:
                          msg.sender === "me" || msg.isSending
                            ? "#FFFFFF"
                            : "#000000",
                        border:
                          msg.sender === "other" && !msg.isSending
                            ? "1px solid #E0E0E0"
                            : "none",
                        borderRadius: "10px",
                        padding: "8px 12px",
                        // width: "80%",
                        maxWidth: "100%",
                        minWidth: "auto",
                      }}
                    >
                      {msg.isSending || msg.isPending ? (
                        <span
                          className="mmhd-body-text single-message"
                          style={{ opacity: msg.isPending ? 0.7 : 1 }}
                          dangerouslySetInnerHTML={{
                            __html: msg.text,
                          }}
                        />
                      ) : (
                        <>
                          {msg.text && (
                            <span
                              className={[
                                "mmhd-body-text",
                                "single-message",
                                msg.action ? "mmhd-is-action" : null,
                              ]
                                .filter(Boolean)
                                .join(" ")}
                              dangerouslySetInnerHTML={{
                                __html: msg.text,
                              }}
                            />
                          )}
                        </>
                      )}
                      <div
                        className="mmhd-text-small mmhd-text-gray"
                        style={{
                          marginTop: "4px",
                          textAlign:
                            msg.sender === "me" || msg.isSending
                              ? "right"
                              : "left",
                          fontSize: "10px",
                        }}
                      >
                        {msg.isSending
                          ? ""
                          : msg.isPending
                            ? "Pending..."
                            : formatTimestamp(msg.created_at)}
                      </div>
                    </div>
                  </div>
                );
              })}
            </>
          )}
        </div>
      </div>

      <div
        className={`chat-input mmhd-flex mmhd-flex-col mmhd-px-18 mmhd-py-15 mmhd-border-top-light mmhd-bg-white  ${previewImage ? "has-preview" : ""
          }`}
      >
        {previewImage && (
          <div className="mmhd-flex mmhd-align-center mmhd-image-upload-container">
            <div className="mmhd-image-preview-wrapper">
              <img
                src={previewImage}
                alt="Preview"
                className="mmhd-max-w-full mmhd-preview-image"
                style={{ maxWidth: "80px" }}
              />
              <button
                onClick={() => setPreviewImage(null)}
                className="mmhd-ml-10 mmhd-text-red-500 mmhd-border-none mmhd-cursor-pointer remove-preview mmhd-position-absolute mmhd-p-0"
              >
                <IoClose />
              </button>
            </div>
          </div>
        )}
        <div className="mmhd-flex mmhd-align-center mmhd-justify-between">
          {!ticketClosed ? (
            <>
              <textarea
                id="mesg"
                ref={textareaRef}
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    handleSend();
                  }
                }}
                placeholder="Write a message..."
                className="mmhd-flex-grow mmhd-border-none mmhd-outline-none mmhd-py-5 mmhd-w-full mmhd-resize-none"
                rows="1"
              />
              <div className="mmhd-flex mmhd-gap-20 mmhd-relative">
                <button
                  onClick={toggleEmojiPicker}
                  className="mmhd-bg-transparent mmhd-border-none mmhd-mr-10 icon-dark mmhd-cursor-pointer mmhd-p-0"
                >
                  <FaRegFaceSmileBeam className="icon-dark" size={20} />
                </button>
                {showPicker && (
                  <div className="mmhd-position-absolute mmhd-bottom-0 mmhd-right-0 emoji-picker-wrap">
                    <EmojiPicker onEmojiClick={handleEmojiClick} emojiStyle={EmojiStyle.NATIVE} />
                  </div>
                )}
                <div className="mmhd-flex">
                  <input
                    type="file"
                    ref={fileInputRef}
                    onChange={handleFileUpload}
                    accept="image/*"
                    className="mmhd-hidden"
                  />
                  <button
                    onClick={triggerFileInput}
                    className="mmhd-bg-transparent mmhd-border-none mmhd-mr-10 mmhd-cursor-pointer mmhd-p-0 icon-dark"
                  >
                    <FiPaperclip
                      className="icon-dark mmhd-paperclip"
                      size={20}
                    />
                  </button>
                </div>
                <button
                  onClick={handleSend}
                  className="mmhd-bg-transparent mmhd-border-none mmhd-ml-10 icon-dark mmhd-cursor-pointer mmhd-p-0"
                >
                  <PiPaperPlaneRightFill className="icon-dark" size={20} />
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
