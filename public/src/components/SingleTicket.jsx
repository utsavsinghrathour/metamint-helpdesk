import { useState, useRef, useEffect, useMemo } from "react";
import { FaChevronLeft } from "react-icons/fa";
import { FaRegFaceSmileBeam } from "react-icons/fa6";
import { PiPaperPlaneRightFill } from "react-icons/pi";
import { FiPaperclip } from "react-icons/fi";
import { IoClose } from "react-icons/io5";
import EmojiPicker from "emoji-picker-react";
import defaultLogo from "../images/robodesk-logo.png";
import "../styles/MessageTabs.css";
import { sendReply } from "../utils/modules";
import { useAppData } from "../utils/AppDataContext";

const SingleTicket = ({ ticket, onBack, loggedInUserId }) => {
    const [message, setMessage] = useState("");
    const [showPicker, setShowPicker] = useState(false);
    const [previewImage, setPreviewImage] = useState(null);
    const [chatMessages, setChatMessages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [perPage] = useState(7);
    const [offset, setOffset] = useState(null);


    const [totalComments, setTotalComments] = useState(0);
    const [isFetching, setIsFetching] = useState(false);

    const textareaRef = useRef(null);
    const fileInputRef = useRef(null);
    const chatContainerRef = useRef(null);
    const image_urls = window.robodeskData?.imageUrls || {};
    const previousScrollHeight = useRef(0);
    const beep = useRef(new Audio(wpAuth.notificationSound));
     const isAtTop = useRef(false);
    const hasConnected = useRef(false);
    const { loggedinContext, loggedInUsrId } = useAppData();

    const [isMessagingActive, setIsisMessagingActive] = useState(
        document.visibilityState === "visible"
    );
    

    useEffect(() => {
// console.log(wpAuth.notificationSound, "Notification sound path");
        beep.current.load();
        const onVisibilityChange = () => {
            setIsisMessagingActive(document.visibilityState === "visible");
        };
        document.addEventListener("visibilitychange", onVisibilityChange);
        return () => {
            document.removeEventListener("visibilitychange", onVisibilityChange);
        };
    }, []);

    const fetchComments = async (fetchOffset = 0, prepend = false) => {
        if (isFetching) return;
        setIsFetching(true);
        try {
        
            setLoading(true);
            setError(null);
            const response = await fetch(
                `/wp-json/wp/v2/comments?post=${ticket.id}&per_page=${perPage}&offset=${fetchOffset}&order=asc&_fields=id,content,date,author_data,time_ago,author_name,author_image`
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch comments: ${response.statusText}`);
            }

            const data = await response.json();
            
            // console.log('We came here first', data);
            // console.log('THe logged in user id is', loggedInUserId);
            // const formattedComments = data.map((comment) => ({
            //     id: comment.id,
            //     text: comment.content?.rendered.replace(/<[^>]+>/g, "") || "",
            //     sender: comment.author_data?.id === loggedInUserId ? "me" : "other",
            //     senderName: comment.author_name,
            //     image: comment.author_data.avatar || "./default-avatar.jpg",
            //     created_at: comment.date,
            //     time_ago: comment.time_ago,
            //     isAdmin: comment.author_data?.id !== ticket.author_id,
            // }));
            
            const formattedComments = formatTicketComments(data);
            
            // console.log('The the comment was formatted like' , formattedComments);
            
            // console.log('The new formatted ticket comments are', newFormattedTicketComments);

            setChatMessages((prev) => {
                const newMessages = prepend
                    ? [...formattedComments, ...prev]
                    : formattedComments;

                const uniqueMessages = Array.from(
                    new Map(newMessages.map((msg) => [msg.id, msg])).values()
                );
                
                return uniqueMessages;
                // return uniqueMessages.sort(
                //     (a, b) => new Date(a.created_at) - new Date(b.created_at)
                // );
            });

            setLoading(false);
            setIsFetching(false);

            if (prepend && chatContainerRef.current && isAtTop.current) {
                const newScrollHeight = chatContainerRef.current.scrollHeight;
                chatContainerRef.current.scrollTop =
                    newScrollHeight - previousScrollHeight.current;
                previousScrollHeight.current = newScrollHeight;
            }
        } catch (err) {
            console.error("Failed to fetch comments:", err);
            setError("Failed to load messages. Please try again.");
            setLoading(false);
            setIsFetching(false);
        }
    };

    //handling new comments received from sse
    const handleNewComment = (commentData) => {
        setChatMessages((prev) => {
            const newMessage = {
                id: commentData.id,
                text: commentData.content?.rendered.replace(/<[^>]+>/g, "") || "",
                sender: commentData.author_id === loggedInUserId ? "me" : "other",
                senderName: commentData.author_name || "Unknown",
                image: commentData.author_image || "/default-avatar.jpg",
                created_at: commentData.created_at || new Date().toISOString(),
                isAdmin: commentData.author_id !== ticket.author_id,
            };
            const uniqueMessages = Array.from(
                new Map([...prev, newMessage].map((msg) => [msg.id, msg])).values()
            );
              return uniqueMessages.sort(
                (a, b) => new Date(a.created_at) - new Date(b.created_at)
              );

            // return uniqueMessages;
        });
    };
    
    
    const formatTicketComments = (comments) => {

        const formattedComments = comments.map((comment) => {
            const authorId = comment.author_data?.id; // Use author_data.id
            const sender = String(authorId) === String(loggedInUserId) ? "me" : "other"; // Ensure type consistency

            return {
                id: comment.id,
                text: comment.content?.rendered?.replace(/<[^>]+>/g, "") || comment.content?.plain || "",
                sender,
                senderName: comment.author_name || "Unknown",
                image: comment.author_data?.avatar || comment.author_image || "./default-avatar.jpg",
                created_at: comment.date,
                time_ago: comment.time_ago,
            };
        });

        return formattedComments.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    };

    // Initial fetch to get total comments and latest messages
    useEffect(() => {
        console.log(ticket.comments, "ticket comments from top");
        if (ticket.comments && ticket.comments.length > 0) {
            // const formattedComments = formatTicketComments(ticket.comments);
            // setChatMessages(formattedComments);
            // setTotalComments(ticket.totalComments);
            // setOffset(Math.max(ticket.totalComments - perPage, 0));
            // setLoading(false);
        }
        const fetchInitialComments = async () => {
            try {
                // const response = await fetch(
                //     `/wp-json/wp/v2/comments?post=${ticket.id}&per_page=1&order=asc&_fields=id`
                // );
                // if (!response.ok) {
                //     throw new Error("Failed to fetch total comments");
                // }
                const total = parseInt(ticket.totalComments, 10) || 0;

                // console.log("Total comments:", total);
                setTotalComments(total);
                const initialOffset = Math.max(total - perPage, 0);
                setOffset(initialOffset);
                await fetchComments(initialOffset, false);
            } catch (err) {
                console.error("Failed to fetch initial comments:", err);
                setError("Failed to load messages. Please try again.");
                setLoading(false);
            }
        };

         fetchInitialComments();
    }, [ticket.id]);

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
            if (chatContainer.scrollTop <= 0 && offset > 0 && !isFetching) {
                isAtTop.current = true; // User is at the top
                previousScrollHeight.current = chatContainer.scrollHeight;
                const newOffset = Math.max(offset - perPage, 0);
                setOffset(newOffset);
                fetchComments(newOffset, true);
            } else {
                isAtTop.current = false; // User is not at the top
            }
        };

        chatContainer.addEventListener("scroll", handleScroll);
        return () => chatContainer.removeEventListener("scroll", handleScroll);
    }, [offset, isFetching]);

    // Scroll to bottom after new messages
    useEffect(() => {
        const chatContainer = chatContainerRef.current;
        if (chatContainer && !isFetching) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

    }, [chatMessages]);

    // Updated serverEvents with better error handling
    const serverEvents = (ticketId, lastCommentId, onNewComment, loggedInUserId) => {

        const eventSource = new EventSource(
            `/wp-json/robodesk/v1/messaging/${ticketId}/${lastCommentId}/${loggedInUserId}`
        );

        eventSource.onopen = () => {
            console.log("SSE connection opened");
            hasConnected.current = true;
        };

        eventSource.addEventListener("new_comment", (event) => {
            const commentData = JSON.parse(event.data);

            console.log("New comment received:", commentData);
            if (typeof onNewComment === "function") {
                beep.current.play();
                onNewComment(commentData);
            }

        });

        eventSource.addEventListener("disconnect", (event) => {
            try {
                const data = JSON.parse(event.data);
                console.log("Server requested disconnect:", data);
            } catch (error) {
                console.error("Error parsing disconnect:", error);
            }
        });

        eventSource.addEventListener("retry", (event) => {
            console.log("Retrying connection...");
        });

        eventSource.onerror = (error) => {

        };

        return () => {
            console.log("Closing SSE Connection");
            hasConnected.current = false;
            eventSource.close();
        };
    };

    const lastMessageId = useMemo(() => {
        if (!chatMessages || chatMessages.length === 0) return false;
        return chatMessages[chatMessages.length - 1].id;
    }, [chatMessages]);

    useEffect(() => {
        if (!hasConnected.current && isMessagingActive && loggedinContext && lastMessageId) {

            const cleanup = serverEvents(ticket.id, lastMessageId, handleNewComment, loggedInUsrId);
            hasConnected.current = true;
            return cleanup;
        }
    }, [lastMessageId]);

    const handleSend = async () => {
        if (message.trim() || previewImage) {
            const tempMessage = {
                id: `temp-${Date.now()}`,
                text: message || "",
                sender: "me",
                senderName: "Me",
                image: "/default-avatar.jpg",
                created_at: new Date().toISOString(),
                isSending: true,
                ...(previewImage && { imageUrl: previewImage }),
            };

            setChatMessages((prev) => [...prev, tempMessage]);
            setMessage("");
            setPreviewImage(null);

            try {
                const result = await sendReply(ticket.id, loggedInUserId, message);
                if (!result.data || !result.data.content) {
                    throw new Error("Invalid response from server: missing content");
                }
                const newMessage = {
                    id: result.data.id || tempMessage.id,
                    text:
                        typeof result.data.content === "string"
                            ? result.data.content.replace(/<[^>]+>/g, "")
                            : "",
                    sender: "me",
                    senderName: result.data.author_name || "Me",
                    image: result.data.author_image ?? "/default-avatar.jpg",
                    created_at: result.data.created_at || tempMessage.created_at,
                    isAdmin: result.data.author_id
                        ? result.data.author_id !== ticket.author_id
                        : false,
                    ...(previewImage && { imageUrl: previewImage }),
                };

                setChatMessages((prev) =>
                    [...prev.filter((msg) => msg.id !== tempMessage.id), newMessage].sort(
                        (a, b) => new Date(a.created_at) - new Date(b.created_at)
                    )
                );
            } catch (err) {
                console.error("Error sending message:", err);
                setChatMessages((prev) =>
                    prev.filter((msg) => msg.id !== tempMessage.id)
                );
                setError("Failed to send message. Please try again.");
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
        if (file && file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = () => setPreviewImage(reader.result);
            reader.readAsDataURL(file);
        }
        e.target.value = null;
    };

    const triggerFileInput = () => fileInputRef.current.click();

    const formatTimestamp = (timestamp) =>
        new Date(timestamp).toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });

    return (
        <div className="rd-single-ticket rd-flex rd-flex-col rd-h-full">
            <div className="single-ticket-header rd-flex-col rd-flex rd-align-center rd-justify-between rd-border-bottom-light rd-px-18 rd-py-15">
                <div className="rd-flex rd-align-center">
                    <button
                        className="icon-dark back-button rd-flex rd-items-center rd-bg-transparent rd-border-none rd-p-0 rd-m-0 rd-back-arrow-position"
                        onClick={onBack}
                    >
                        <FaChevronLeft />
                    </button>
                    {image_urls.wide_logo && image_urls.wide_logo.trim() !== "" ? (
                        <img
                            src={image_urls.wide_logo}
                            alt="Plugin Wide Logo"
                            className="rd-h-auto rd-header-logo"
                            onError={(e) => (e.target.style.display = "none")}
                        />
                    ) : image_urls.logo && image_urls.logo.trim() !== "" ? (
                        <img
                            src={image_urls.logo}
                            alt="Plugin Logo"
                            className="rd-h-auto rd-header-logo"
                            onError={(e) => (e.target.style.display = "none")}
                        />
                    ) : (
                        <div className="rd-h-auto rd-header-logo default-logo">
                            <img
                                src={defaultLogo}
                                alt="Default Plugin Logo"
                                className="rd-h-auto rd-header-logo"
                            />
                        </div>
                    )}
                </div>
                <div className="rd-body-text rd-mt-30">
                    <p className="rd-text-center rd-body-text">
                        If you are interested in working with us, take a step towards
                        discovering exciting opportunities by reaching out to us. We look
                        forward to talking with you soon!
                    </p>
                </div>
            </div>
            <div
                ref={chatContainerRef}
                className="chat-container rd-flex rd-flex-col rd-flex-grow rd-px-18 rd-py-15 rd-overflow-auto"
            >
                {loading && offset === Math.max(totalComments - perPage, 0) ? (
                    <p className="rd-text-center rd-body-text">Loading messages...</p>
                ) : error ? (
                    <p className="err_msg rd-text-center">{error}</p>
                ) : chatMessages.length === 0 ? (
                    <p className="rd-text-center rd-body-text">Loading messages...</p>
                ) : (
                    chatMessages.map((msg, index) => {
                        const prevMsg = index > 0 ? chatMessages[index - 1] : null;
                        const isSameSender = prevMsg && prevMsg.sender === msg.sender;
                        const marginBottom = isSameSender ? "4px" : "12px";

                        return (
                            <div
                                key={msg.id}
                                className={`chat-message rd-flex rd-items-start ${msg.sender === "me" || msg.isSending
                                    ? "rd-ml-auto sent"
                                    : "rd-flex rd-gap-8 received"
                                    }`}
                                style={{
                                    maxWidth: "70%",
                                    marginBottom,
                                    marginTop: index === 0 || !isSameSender ? "12px" : "4px",
                                }}
                            >
                                {(!isSameSender || index === 0) && (
                                    <img
                                        src={msg.image}
                                        alt={msg.sender === "me" ? "You" : msg.senderName}
                                        className="rd-rounded-full rd-object-cover"
                                        style={{
                                            height: "30px",
                                            width: "30px",
                                            borderRadius: "50px",
                                            aspectRatio: "1",
                                        }}
                                    />
                                )}
                                {isSameSender && index !== 0 && (
                                    <div style={{ width: "30px", height: "30px" }} />
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
                                        maxWidth: "100%",
                                    }}
                                >
                                    {msg.isSending ? (
                                        <span className="rd-body-text single-message">
                                            {msg.text}
                                        </span>
                                    ) : (
                                        <>
                                            {msg.text && (
                                                <span className="rd-body-text single-message">
                                                    {msg.text}
                                                </span>
                                            )}
                                            {msg.imageUrl && (
                                                <img
                                                    src={msg.imageUrl}
                                                    alt="Uploaded"
                                                    className="rd-max-w-full rd-h-auto rd-rounded"
                                                    style={{
                                                        maxWidth: "200px",
                                                        marginTop: msg.text ? "8px" : "0",
                                                    }}
                                                />
                                            )}
                                        </>
                                    )}
                                    <div
                                        className="rd-text-small rd-text-gray"
                                        style={{
                                            marginTop: "4px",
                                            textAlign:
                                                msg.sender === "me" || msg.isSending ? "right" : "left",
                                            fontSize: "10px",
                                        }}
                                    >
                                        {msg.isSending ? "" : formatTimestamp(msg.created_at)}
                                    </div>
                                </div>
                            </div>
                        );
                    })
                )}
            </div>
            <div className="chat-input rd-flex rd-flex-col rd-px-18 rd-py-15 rd-border-top-light rd-bg-white">
                {previewImage && (
                    <div className="rd-mb-10 rd-flex rd-align-center">
                        <div className="rd-position-relative">
                            <img
                                src={previewImage}
                                alt="Preview"
                                className="rd-max-w-full rd-h-auto rd-border-radius-md"
                                style={{ maxWidth: "80px" }}
                            />
                            <button
                                onClick={() => setPreviewImage(null)}
                                className="rd-ml-10 rd-text-red-500 rd-border-none rd-cursor-pointer remove-preview rd-position-absolute rd-p-0"
                            >
                                <IoClose />
                            </button>
                        </div>
                    </div>
                )}
                <div className="rd-flex rd-align-center rd-justify-between">
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
                        className="rd-flex-grow rd-border-none rd-outline-none rd-py-5 rd-w-full rd-resize-none rd-min-h-[40px]"
                        rows="1"
                    />
                    <div className="rd-flex rd-gap-20 rd-relative">
                        <button
                            onClick={toggleEmojiPicker}
                            className="rd-bg-transparent rd-border-none rd-mr-10 icon-dark rd-cursor-pointer rd-p-0"
                        >
                            <FaRegFaceSmileBeam className="icon-dark" size={20} />
                        </button>
                        {showPicker && (
                            <div className="rd-position-absolute rd-bottom-0 rd-right-0 rd-top-0 emoji-picker-wrap">
                                <EmojiPicker onEmojiClick={handleEmojiClick} />
                            </div>
                        )}
                        <div className="rd-flex">
                            <input
                                type="file"
                                ref={fileInputRef}
                                onChange={handleFileUpload}
                                accept="image/*"
                                className="rd-hidden"
                            />
                            <button
                                onClick={triggerFileInput}
                                className="rd-bg-transparent rd-border-none rd-mr-10 rd-cursor-pointer rd-p-0 icon-dark"
                            >
                                <FiPaperclip className="icon-dark" size={20} />
                            </button>
                        </div>
                        <button
                            onClick={handleSend}
                            className="rd-bg-transparent rd-border-none rd-ml-10 icon-dark rd-cursor-pointer rd-p-0"
                        >
                            <PiPaperPlaneRightFill className="icon-dark" size={20} />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SingleTicket;
