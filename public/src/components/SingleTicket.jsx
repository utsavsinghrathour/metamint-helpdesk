import React, { useState, useRef, useEffect } from 'react';
import { FaChevronLeft } from 'react-icons/fa';
import { FaRegFaceSmileBeam } from 'react-icons/fa6';
import { PiPaperPlaneRightFill } from 'react-icons/pi';
import { FiPaperclip } from 'react-icons/fi';
import EmojiPicker from 'emoji-picker-react';
import Logo from '../images/White_Label_logo.png';
import '../styles/MessageTabs.css';
import { IoClose } from "react-icons/io5";

const SingleTicket = ({ ticket, onBack }) => {
  const [message, setMessage] = useState('');
  const [showPicker, setShowPicker] = useState(false);
  const [previewImage, setPreviewImage] = useState(null); // State for image preview
  const [chatMessages, setChatMessages] = useState([
    { id: 1, text: 'Hi, how can I help you today?', sender: 'other', image: ticket.authorImage },
    { id: 2, text: 'I need assistance with my website.', sender: 'me' },
    { id: 3, text: 'Can you describe the issue?', sender: 'other', image: ticket.authorImage },
    { id: 4, text: 'I need assistance with my website.', sender: 'me' },
    { id: 5, text: 'Hi, how can I help you today?', sender: 'other', image: ticket.authorImage },
    { id: 6, text: 'I need assistance with my website.', sender: 'me' },
    { id: 7, text: 'Can you describe the issue?', sender: 'other', image: ticket.authorImage },
    { id: 8, text: 'I need assistance with my website.', sender: 'me' },
  ]);
  const textareaRef = useRef(null);
  const fileInputRef = useRef(null);

  useEffect(() => {
    const textarea = textareaRef.current;
    if (textarea) {
      textarea.style.height = 'auto';
      textarea.style.height = `${textarea.scrollHeight}px`;
    }
  }, [message]);

  const handleSend = () => {
    if (message.trim() || previewImage) {
      setChatMessages((prev) => [
        ...prev,
        { 
          id: Date.now(), 
          text: message || '', 
          sender: 'me',
          ...(previewImage && { imageUrl: previewImage }) // Add image if present
        },
      ]);
      setMessage('');
      setPreviewImage(null); // Clear preview after sending
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
        setPreviewImage(reader.result); // Set preview image
      };
      reader.readAsDataURL(file);
    }
    e.target.value = null;
  };

  const triggerFileInput = () => {
    fileInputRef.current.click();
  };

  return (
    <div className="rd-single-ticket rd-flex rd-flex-col rd-h-full">
      <div className="single-ticket-header rd-flex-col rd-flex rd-align-center rd-justify-between rd-border-bottom-light rd-px-18 rd-py-15">
        <div className="rd-flex rd-align-center">
          <button
            className="back-button rd-flex rd-items-center rd-bg-transparent rd-border-none icon-dark rd-p-0 rd-m-0 rd-back-arrow-position"
            onClick={onBack}
          >
            <FaChevronLeft />
          </button>
          <img src={Logo} alt="Plugin Logo" className="rd-h-auto" />
        </div>
        <div className="rd-body-text rd-mt-30">
          <p className="rd-text-center">
            If you are interested in working with us, take a step towards discovering exciting opportunities by reaching
            out to us. We look forward to talking with you soon!
          </p>
        </div>
      </div>
      <div className="chat-container rd-flex rd-flex-col rd-flex-grow rd-px-18 rd-py-15 rd-overflow-auto">
        {chatMessages.map((msg) => (
          <div
            key={msg.id}
            className={`chat-message rd-mb-10 rd-flex rd-align-center ${
              msg.sender === 'me' ? 'rd-ml-auto rd-bg-light-gray sent' : 'rd-flex rd-items-start rd-gap-8 recieved'
            }`}
            style={{
              maxWidth: '70%',
              padding: '8px 16px',
              borderRadius: '10px',
              backgroundColor: msg.sender === 'me' ? '#EEEEEE' : 'transparent',
              marginLeft: msg.sender === 'me' ? 'auto' : '0',
            }}
          >
            {msg.sender === 'other' && (
              <img
                src={msg.image}
                alt="Sender"
                className="rd-rounded-full rd-object-cover"
                style={{ height: '30px', width: '30px', borderRadius: '50px' }}
              />
            )}
            {msg.text && <span className="rd-body-text single-message">{msg.text}</span>}
            {msg.imageUrl && (
              <img
                src={msg.imageUrl}
                alt="Uploaded"
                className="rd-max-w-full rd-h-auto rd-rounded"
                style={{ maxWidth: '200px' }}
              />
            )}
          </div>
        ))}
      </div>
      <div className="chat-input rd-flex rd-flex-col rd-px-18 rd-py-15 rd-border-top-light rd-bg-white">
        {previewImage && (
          <div className="rd-mb-10 rd-flex rd-align-center">
            <div className='rd-position-relative'>
            <img
              src={previewImage}
              alt="Preview"
              className="rd-max-w-full rd-h-auto rd-border-radius-md"
              style={{ maxWidth: '80px' }} // Smaller preview size
            />
            <button
              onClick={() => setPreviewImage(null)}
              className="rd-ml-10 rd-text-red-500 rd-border-none rd-cursor-pointer remove-preview rd-position-absolute"
            >
              <IoClose />
            </button>
            </div>
          </div>
        )}
        <div className="rd-flex rd-align-center rd-justify-between">
          <textarea
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
              className="rd-bg-transparent rd-border-none rd-mr-10 icon-dark rd-cursor-pointer"
            >
              <FaRegFaceSmileBeam className="icon-dark" size={20} />
            </button>
            {showPicker && (
              <div className="rd-position-absolute rd-bottom-0 rd-right-0 rd-top-0 emoji-picker-wrap">
                <EmojiPicker onEmojiClick={handleEmojiClick} />
              </div>
            )}
            <div>
              <input
                type="file"
                ref={fileInputRef}
                onChange={handleFileUpload}
                accept="image/*"
                className="rd-hidden"
              />
              <button
                onClick={triggerFileInput}
                className="rd-bg-transparent rd-border-none rd-mr-10 rd-cursor-pointer"
              >
                <FiPaperclip className="icon-dark" size={20} />
              </button>
            </div>
            <button onClick={handleSend} className="rd-bg-transparent rd-border-none rd-ml-10 icon-dark rd-cursor-pointer">
              <PiPaperPlaneRightFill className="icon-dark" size={20} />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SingleTicket;