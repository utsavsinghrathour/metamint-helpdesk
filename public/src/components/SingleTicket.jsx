import React, { useState, useRef, useEffect } from 'react';
import { FaChevronLeft } from 'react-icons/fa';
import { FaRegFaceSmileBeam } from 'react-icons/fa6';
import { PiPaperPlaneRightFill } from 'react-icons/pi';
import { FiPaperclip } from 'react-icons/fi';
// import { LuDot } from 'react-icons/lu';
import { IoClose } from 'react-icons/io5';
import EmojiPicker from 'emoji-picker-react';
import Logo from '../images/wooescrow-logo.png';
import '../styles/MessageTabs.css';
import axios from 'axios';
import {  getMessages , sendReply } from "../utils/modules";   

const SingleTicket = ({ ticket, onBack , loggedInUserId }) => {
  const [message, setMessage] = useState('');
  const [showPicker, setShowPicker] = useState(false);
  const [previewImage, setPreviewImage] = useState(null);
  const [chatMessages, setChatMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const textareaRef = useRef(null);
  const fileInputRef = useRef(null);
  const chatContainerRef = useRef(null);

  // Fetch messages from WordPress
  const fetchMessages = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await getMessages(ticket.id);

      if(response.code === 400){
        setError('Nothing to show');
        setLoading(false);
        return;
      }
      
    //   console.log('THe response',response);
    
    //   console.log('The logged in user',loggedInUserId , response.data[9].);
      const messages = response.data.map((comment) => ({
        id: comment.id,
        text:  comment.content.replace(/<[^>]+>/g, ''),
        sender:  comment.author_id === loggedInUserId ? 'me' : 'other', // Compare with current user ID
        senderName: comment.author_name,
        image:  comment.author_image ?? '/default-avatar.jpg', // Use WordPress avatar if available
        created_at: comment.created_at,
        isAdmin: comment.author_id !== ticket.author_id // Assuming you want to mark non-author as admin
      }));
      
      
      
      console.log('The chat messages',messages);
      setChatMessages(messages);
      setLoading(false);
    } catch (err) {
      console.error('Error fetching messages:', err);
      setError('Failed to load messages. Please try again.');
      setLoading(false);
    }
  };

// const fetchMessages = async () => {

//     const response = await axios.post('/wp-json/wp/v2/comments', {
//         params: {
//           post: ticket.id,
//           type: 'ticket_replies',
//           per_page: 100,
//           order: 'asc', // To get messages in chronological order
//           _fields: 'id,content,author,author_name,date,author_avatar_urls' // Only request needed fields
//         }
//       });
      
//       console.log('responseabcd', response);
      
//     //   console.log('response');
//     try {
//       setLoading(true);
//       setError(null);

//       const response = await axios.get('/wp-json/wp/v2/comments', {
//         params: {
//           post: ticket.id,
//           type: 'ticket_replies',
//           per_page: 100,
//           order: 'asc', // To get messages in chronological order
//           _fields: 'id,content,author,author_name,date,author_avatar_urls' // Only request needed fields
//         }
//       });
  
//     //   console.log('Ticket replies:', response.data);
  
//       const messages = response.data.map((comment) => ({
//         id: comment.id,
//         text: comment.content.rendered.replace(/<[^>]+>/g, '').trim(),
//         sender: comment.author === currentUser.id ? 'me' : 'other', // Compare with current user ID
//         senderName: comment.author_name,
//         image: comment.author_avatar_urls?.['96'] || '/default-avatar.jpg', // Use WordPress avatar if available
//         created_at: comment.date,
//         isAdmin: comment.author !== ticket.author_id // Assuming you want to mark non-author as admin
//       }));
  
//       setChatMessages(messages);
//       setLoading(false);
//     } catch (err) {
//       console.error('Error fetching messages:', err);
//       setError('Failed to load messages. Please try again.hellow');
//       setLoading(false);
//     }
// };

  useEffect(() => {
    console.log('SingleTicket ticket prop:', ticket);
    fetchMessages();
  }, [ticket.id]);

  useEffect(() => {
    const textarea = textareaRef.current;
    if (textarea) {
      textarea.style.height = 'auto';
      textarea.style.height = `${textarea.scrollHeight}px`;
    }
  }, [message]);

  useEffect(() => {
    const chatContainer = chatContainerRef.current;
    if (chatContainer) {
      chatContainer.scrollTop = chatContainer.scrollHeight;
    }
  }, [chatMessages]);

  const handleSend = async (e) => {
    if (message.trim() || previewImage) {
      try {
        const newMessage = {
          id: Date.now(),
          text: message || '',
          sender: loggedInUserId,
          created_at: new Date().toISOString(),
          ...(previewImage && { imageUrl: previewImage }),
        };

        setChatMessages((prev) => [...prev, newMessage]);

        const result = await sendReply(ticket.id, loggedInUserId, message );
        
    // const messages = result.data.map((comment) => ({
    //     id: comment.id,
    //     text:  comment.content.replace(/<[^>]+>/g, ''),
    //     sender:  comment.author_id === loggedInUserId ? 'me' : 'other', // Compare with current user ID
    //     senderName: comment.author_name,
    //     image:  comment.author_image ?? '/default-avatar.jpg', // Use WordPress avatar if available
    //     created_at: comment.created_at,
    //     isAdmin: comment.author_id !== ticket.author_id // Assuming you want to mark non-author as admin
    //   }));
        
        console.log(result);
        // Refresh messages to get server data
        await fetchMessages();
        setMessage('');
        setPreviewImage(null);
      } catch (err) {
        console.error('Error sending message:', err);
        setChatMessages((prev) => prev.filter((msg) => msg.id !== newMessage.id));
        setError('Failed to send message. Please try again.');
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
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = () => {
        setPreviewImage(reader.result);
      };
      reader.readAsDataURL(file);
    }
    e.target.value = null;
  };

  const triggerFileInput = () => {
    fileInputRef.current.click();
  };

  const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

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
          
          <img src={Logo} alt="Plugin Logo" className="rd-h-auto rd-header-logo" />
        </div>
        <div className="rd-body-text rd-mt-30">
          <p className="rd-text-center rd-body-text">
            If you are interested in working with us, take a step towards discovering exciting opportunities by reaching
            out to us. We look forward to talking with you soon!
          </p>
        </div>
      </div>
      <div
        ref={chatContainerRef}
        className="chat-container rd-flex rd-flex-col rd-flex-grow rd-px-18 rd-py-15 rd-overflow-auto"
      >
        {loading ? (
          <p className="rd-text-center rd-body-text">Loading messages...</p>
        ) : error ? (
          <p className="err_msg rd-text-center">{error}</p>
        ) : chatMessages.length === 0 ? (
          <p className="rd-text-center rd-body-text">No messages yet.</p>
        ) : (
          chatMessages.map((msg, index) => {
            const prevMsg = index > 0 ? chatMessages[index - 1] : null;
            const isSameSender = prevMsg && prevMsg.sender === msg.sender;
            const marginBottom = isSameSender ? '4px' : '12px';

            return (
              <div
                key={msg.id}
                className={`chat-message rd-flex rd-items-start ${
                  msg.sender === 'me' ? 'rd-ml-auto sent' : 'rd-flex rd-gap-8 received'
                }`}
                style={{
                  maxWidth: '70%',
                  marginBottom,
                  marginTop: index === 0 || !isSameSender ? '12px' : '4px',
                }}
              >
                {msg.sender === 'other' && !isSameSender && (
                  <img
                    src={msg.image}
                    alt="Sender"
                    className="rd-rounded-full rd-object-cover"
                    style={{ height: '30px', width: '30px', borderRadius: '50px', aspectRatio: '1' }}
                  />
                )}
                {msg.sender === 'other' && isSameSender && (
                  <div style={{ width: '30px', height: '30px' }} />
                )}
                <div
                  className="message-content"
                  style={{
                    backgroundColor: msg.sender === 'me' ? '#007BFF' : '#FFFFFF',
                    color: msg.sender === 'me' ? '#FFFFFF' : '#000000',
                    border: msg.sender === 'other' ? '1px solid #E0E0E0' : 'none',
                    borderRadius: '10px',
                    padding: '8px 12px',
                    maxWidth: '100%',
                  }}
                >
                  {msg.text && <span className="rd-body-text single-message">{msg.text}</span>}
                  {msg.imageUrl && (
                    <img
                      src={msg.imageUrl}
                      alt="Uploaded"
                      className="rd-max-w-full rd-h-auto rd-rounded"
                      style={{ maxWidth: '200px', marginTop: msg.text ? '8px' : '0' }}
                    />
                  )}
                  <div
                    className="rd-text-small rd-text-gray"
                    style={{
                      marginTop: '4px',
                      textAlign: msg.sender === 'me' ? 'right' : 'left',
                      fontSize: '0.75rem',
                    }}
                  >
                    {formatTimestamp(msg.created_at)}
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
                style={{ maxWidth: '80px' }}
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
            <button onClick={handleSend} className="rd-bg-transparent rd-border-none rd-ml-10 icon-dark rd-cursor-pointer rd-p-0">
              <PiPaperPlaneRightFill className="icon-dark" size={20} />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SingleTicket;