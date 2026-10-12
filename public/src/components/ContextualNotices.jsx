import React, { useState, useEffect } from "react";
import "../styles/ContextualNotices.css";
import { FaTimes } from "react-icons/fa";
import { PiChatCircleText } from "react-icons/pi";
import Bell from "../images/alarm-gif.gif";

const formatNotice = (notice) => ({
  id: notice.id,
  title:
    typeof notice.title === "string"
      ? notice.title
      : notice.title?.rendered || "",
  excerpt: notice.excerpt?.rendered || "",
  content: notice.content?.rendered || "",
  notice_action: notice.notice_action || "open",
  initial_message: notice.initial_message || "",
});

const ContextualNotices = ({ onNoticeSelect, onStartConversation }) => {
  const [notices, setNotices] = useState([]);
  const [isContextual, setIsContextual] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const fetchNotices = async () => {
      try {
        setIsLoading(true);

        const context = window.mmhdData?.noticeContext || {};
        const postId = Number(context?.post_id) || 0;
        const termIds = Array.isArray(context?.term_ids)
          ? context.term_ids.filter(Boolean)
          : [];

        let data = [];
        let contextual = false;

        // 1) Contextual notices: assigned to this post and/or its tax terms.
        if (postId || termIds.length > 0) {
          const params = new URLSearchParams();
          if (postId) params.set("post_id", postId);
          if (termIds.length > 0) params.set("term_ids", termIds.join(","));

          const response = await fetch(
            `/wp-json/mmhd/v1/context-notices?${params.toString()}`,
          );
          if (response.ok) {
            const contextNotices = await response.json();
            if (Array.isArray(contextNotices) && contextNotices.length > 0) {
              data = contextNotices.map(formatNotice);
              contextual = true;
            }
          }
        }

        // 2) Fallback: global featured + active notices.
        if (data.length === 0) {
          const response = await fetch(
            "/wp-json/wp/v2/mmhd_notice?featured=true&active=true&_fields=id,title.rendered,excerpt.rendered,content.rendered,notice_action,initial_message",
          );
          if (!response.ok) {
            throw new Error("Failed to fetch notices");
          }
          const featured = await response.json();
          data = featured.map(formatNotice);
        }

        setNotices(data);
        setIsContextual(contextual);
        setIsLoading(false);
      } catch (err) {
        setError(err.message);
        setIsLoading(false);
      }
    };

    fetchNotices();
  }, []);

  // Check sessionStorage to determine visibility
  useEffect(() => {
    const isPopupClosed = sessionStorage.getItem("popupClosed");
    if (!isPopupClosed && !isLoading && !error && notices.length > 0) {
      const timer = setTimeout(() => {
        setIsVisible(true);
      }, 1000);

      return () => clearTimeout(timer);
    }
  }, [isLoading, error, notices.length]);

  const handleNoticeClick = (notice) => {
    if (
      notice.notice_action === "start_conversation" &&
      notice.initial_message
    ) {
      handleCloseAllNotices();
      onStartConversation(notice.initial_message);
      return;
    }
    onNoticeSelect(notice);
  };

  const handleCloseAllNotices = () => {
    sessionStorage.setItem("popupClosed", "true");
    setIsVisible(false);
    setNotices([]);
  };

  return (
    <div
      className={`mmhd-active-featured mmhd-position-relative ${
        !isVisible || isLoading || error || notices.length === 0
          ? "mmhd-hidden"
          : ""
      } ${isVisible ? "mmhd-fade-in" : ""}`}
    >
      {error ? (
        <p className="mmhd-text-red mmhd-body-text">Error: {error}</p>
      ) : (
        notices.length > 0 && (
          <>
            <button
              className="mmhd-close-all-button mmhd-bg-transparent mmhd-border-none mmhd-cursor-pointer mmhd-text-gray-600 hover:mmhd-text-gray-800 mmhd-p-0"
              onClick={handleCloseAllNotices}
              title="Close all notices"
            >
              <FaTimes />
            </button>
            <div className="mmhd-flex mmhd-flex-col">
              {notices.map((notice) => {
                const startsConversation =
                  notice.notice_action === "start_conversation";
                return (
                  <h5
                    key={notice.id}
                    className={`mmhd-notice-title sub-text mmhd-p-10 mmhd-flex mmhd-align-center mmhd-gap-10 ${
                      startsConversation ? "mmhd-notice-starts-chat" : ""
                    } ${isContextual ? "mmhd-notice-contextual" : ""}`}
                    onClick={() => handleNoticeClick(notice)}
                  >
                    <div className="featured-notice-icon">
                      {startsConversation ? (
                        <PiChatCircleText className="mmhd-notice-chat-icon" />
                      ) : (
                        <img
                          src={Bell}
                          alt="Bell icon"
                          className="mmhd-bell-gif"
                        />
                      )}
                    </div>
                    <span className="mmhd-notice-title-text">
                      {notice.title}
                      {startsConversation && (
                        <small className="mmhd-notice-start-chat-label">
                          Start a conversation
                        </small>
                      )}
                    </span>
                  </h5>
                );
              })}
            </div>
          </>
        )
      )}
    </div>
  );
};

export default ContextualNotices;