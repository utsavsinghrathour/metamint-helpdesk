import React from 'react';
import Logo from '../images/wooescrow-logo.png';
import '../styles/header.css';
import { IoCloseCircleSharp } from "react-icons/io5";


const Header = ({ onClose }) => {
  return (
    <header className="robodesk-header rd-flex rd-justify-between">
      <img src={Logo} alt="Plugin Logo" className="rd-h-auto rd-header-logo" />
      <button className="robodesk-close-btn rd-m-5 rd-border-none rd-bg-transparent rd-p-0" onClick={onClose}>
        {/* <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path
            d="M18.125 0.49707H1.875C0.839844 0.49707 0 1.39158 0 2.49411V17.1391C0 18.2416 0.839844 19.1361 1.875 19.1361H18.125C19.1602 19.1361 20 18.2416 20 17.1391V2.49411C20 1.39158 19.1602 0.49707 18.125 0.49707ZM14.8594 12.5833C15.0469 12.783 15.0469 13.1076 14.8594 13.3073L13.2773 14.9923C13.0898 15.192 12.7852 15.192 12.5977 14.9923L10 12.2006L7.40234 14.9923C7.21484 15.192 6.91016 15.192 6.72266 14.9923L5.14062 13.3073C4.95312 13.1076 4.95312 12.783 5.14062 12.5833L7.76172 9.8166L5.14062 7.04986C4.95312 6.85016 4.95312 6.52564 5.14062 6.32594L6.72266 4.64093C6.91016 4.44123 7.21484 4.44123 7.40234 4.64093L10 7.43263L12.5977 4.64093C12.7852 4.44123 13.0898 4.44123 13.2773 4.64093L14.8594 6.32594C15.0469 6.52564 15.0469 6.85016 14.8594 7.04986L12.2383 9.8166L14.8594 12.5833Z"
            fill="#333333"
          />
        </svg> */}
        <div className='icon-dark custom-svg rd-cursor-pointer'>
<IoCloseCircleSharp />
</div>
      </button>
    </header>
  );
};

export default Header;
