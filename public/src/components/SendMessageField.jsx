import { useState, useEffect, useRef } from "react";
import { createTicket } from "../utils/modules";
import "../styles/SendMessageField.css";
import { useAppData } from '../utils/AppDataContext';

const SendMessageField = ({ setShowLogin, setActiveTab, updateSelectedTicket, handleFocusedTextArea, focused }) => {
    const [message, setMessage] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [loggedIn, setLoggedIn] = useState(false);
    const [userId, setUserId] = useState(null);
    const [placeHolderText, setplaceHolderText] = useState('Start a conversation...');
    const chatHeading = window.robodeskData?.chatHeading || 'Have Questions?';
    const welcomeMessage = window.robodeskData?.welcomeMessage || 'Have any questions or need a quote? Leave us a message.';
    const { loggedinContext, loggedInUsrId, refreshTickets } = useAppData();


    const handleInputChange = (e) => {
        setMessage(e.target.value);
    };



    const textareaRef = useRef(null);
    const prevFocusedRef = useRef(false);
    const submitLockRef = useRef(false);

    useEffect(() => {
        if (!prevFocusedRef.current && focused) {
            textareaRef.current?.focus();
        }
        prevFocusedRef.current = focused;
    }, [focused]);

    useEffect(() => {

        setLoggedIn(loggedinContext);
        setUserId(loggedInUsrId);
    }, []);

    const formatTicketComments = (comments) => {

        const formattedComments = comments.map((comment) => {
            const authorId = comment.author_data?.id; // Use author_data.id
            const sender = String(authorId) === String(loggedInUsrId) ? "me" : "other"; // Ensure type consistency

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


    const handleSend = async () => {
        const trimmedMessage = message.trim();

        if (!trimmedMessage || submitLockRef.current || isSubmitting) return;
        if (!loggedinContext) {
            localStorage.setItem('temp_ticket_title', trimmedMessage);
            if (window.robodeskData?.enable_chat === "1") setActiveTab(1);
            setShowLogin(true);

            return;
        }

        submitLockRef.current = true;
        setIsSubmitting(true);

        try {
            const result = await createTicket(loggedInUsrId, trimmedMessage, '');

            const newTicket = {
                id: result.data.id,
                ticketNumber: result.data.ticket_number,
                title: result.data.ticket_title,
                source:
                    result.data.ticket_origin && result.data.ticket_origin !== 'N/A'
                        ? result.data.ticket_origin
                        : 'Web',
                timeAgo: result.data.ticket_time_ago,
                authorImage: result.data.ticket_author_image,
                status: result.data.ticket_status,
                priority: result.data.ticket_priority,
                messages: [],
            };
            refreshTickets();
            updateSelectedTicket(newTicket);

            if (window.robodeskData?.enable_chat === "1") setActiveTab(1);
            setMessage("");

        } catch (error) {
            console.error("Failed to create ticket:", error);
        } finally {
            submitLockRef.current = false;
            setIsSubmitting(false);
        }

    };

    return (
        <div className="send-message-field rd-px-16">
            <div className='rd-text-center rd-px-20 rd-flex rd-flex-col rd-gap-5 rd-mb-20 home-tab-title'>
                <h4 className='heading-4'>{chatHeading}</h4>
                <h5 className='sub-text rd-mt-0'>{welcomeMessage}</h5>
            </div>
            <div className="rd-position-relative rd-flex rd-px-18 ">
                <textarea
                    className="rd-w-full rd-border-blue rd-border-radius-md"
                    placeholder={placeHolderText}
                    value={message}
                    ref={textareaRef}
                    onChange={handleInputChange}
                    rows={1}
                    style={{ minHeight: "42px", maxHeight: "200px" }}
                    onKeyPress={(e) => {
                        if (e.key === "Enter" && !e.shiftKey) {
                            e.preventDefault();
                            handleSend();
                        }
                    }}
                    onBlur={() => {
                        handleFocusedTextArea(false);
                        // Do something when focus is lost
                    }}
                />
                <button
                    onClick={() => {
                        handleSend();
                    }}
                    className="rd-bg-transparent rd-border-none rd-text-blue rd-p-0"
                    disabled={!message.trim() || isSubmitting}
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="17"
                        height="16"
                        viewBox="0 0 17 16"
                        fill="none"
                    >
                        <path
                            d="M15.3728 0.0985297L0.886843 8.45568C0.321158 8.78071 0.39304 9.5683 0.955601 9.80582L4.27783 11.1997L13.2569 3.28637C13.4288 3.13323 13.6726 3.36763 13.5257 3.54577L5.99677 12.7186V15.2345C5.99677 15.9721 6.88749 16.2628 7.32503 15.7283L9.30962 13.3124L13.2038 14.9439C13.6476 15.1314 14.1539 14.8532 14.2351 14.3751L16.4854 0.873613C16.5917 0.242295 15.9135 -0.214004 15.3728 0.0985297Z"
                            fill="#004AFF"
                        />
                    </svg>
                </button>
            </div>
        </div>
    );
};

export default SendMessageField;
