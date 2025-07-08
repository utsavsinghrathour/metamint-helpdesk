import React from 'react';
import defaultLogo from '../images/robodesk-logo.png'; // Adjust path to your default logo
import '../styles/header.css';
import { IoCloseCircleSharp } from 'react-icons/io5';

const Header = ({ logo, onClose }) => {
  const handleCloseClick = () => {
    onClose();
  };

  return (
    <header className="robodesk-header rd-flex rd-justify-between">
      {logo && logo.trim() !== '' ? (
        <img
          src={logo}
          alt="Plugin Logo"
          className="rd-h-auto rd-header-logo rd-main-header-logo"
          onError={(e) => {
            e.target.style.display = 'none';
          }}
        />
      ) : (
        <div className="rd-h-auto rd-header-logo default">
          <img
            src={defaultLogo}
            alt="Default Plugin Logo"
            className="rd-h-auto rd-header-logo rd-main-header-logo"
          />
        </div>
      )}
      <button
        className="robodesk-close-btn rd-m-5 rd-border-none rd-bg-transparent rd-p-0"
        onClick={handleCloseClick}
      >
        <div className="icon-dark custom-svg rd-cursor-pointer">
          <IoCloseCircleSharp />
        </div>
      </button>
    </header>
  );
};

export default Header;