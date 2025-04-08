import React from 'react';
import '../styles/Login.css'; 
import Header from './Header.jsx'; 
import Footer from './Footer.jsx'; 

const Login = () => {
  return (
    <div className="login-container rd-flex rd-flex-col rd-items-center rd-justify-center">
      <div className="login-card">
        <h2>
          Login
        </h2>
        <div>
          <div>
            <label 
              htmlFor="username" 
            >
              Username
            </label>
            <input
              type="text"
              id="username"
              placeholder="Enter your username"
            />
          </div>
          <div>
            <label 
              htmlFor="password" 
            >
              Password
            </label>
            <input
              type="password"
              id="password"
              placeholder="Enter your password"
            />
          </div>
          <button
          >
            Sign In
          </button>
        </div>
        <p>
          Don't have an account?{' '}
          <a href="#">
            Sign up
          </a>
        </p>
      </div>
    </div>
  );
};

export default Login;