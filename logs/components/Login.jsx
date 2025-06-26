import React from "react";
import "../styles/Login.css";
import { useState } from "react";
import { createTicket } from "../utils/modules";
import Signup from "./Signup";

const Login = ({ setShowLogin, setActiveTab }) => {
  const [isSignup, setIsSignup] = useState(false);
  const [formData, setFormData] = useState({ email: "", password: "" });
  const [errors, setErrors] = useState({ email: "", password: "" });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const validateForm = () => {
    const newErrors = { email: "", password: "" };
    let isValid = true;

    if (!formData.email) {
      newErrors.email = "Email is required";
      isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Invalid email format";
      isValid = false;
    }

    if (!formData.password) {
      newErrors.password = "Password is required";
      isValid = false;
    } else if (formData.password.length < 5) {
      newErrors.password = "Password must be at least 8 characters";
      isValid = false;
    }

    setErrors(newErrors);
    return isValid;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrors({ email: "", password: "" });

    if (!validateForm()) {
      setIsSubmitting(false);
      return;
    }

    try {
      const loginResponse = await fetch('/wp-json/custom/v1/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          username: formData.email,
          password: formData.password,
        }),
      });

      const loginData = await loginResponse.json();
      
      console.log(loginData);

      if (!loginResponse.ok) {
        throw new Error(loginData.message || loginData.data?.message || 'Login failed');
      }

      if (loginData.success && loginData.token) {
        // Store JWT token securely
        localStorage.setItem('jwt_token', loginData.token);

        const ticketTitle = localStorage.getItem('temp_ticket_title');
        if (ticketTitle) {
          try {
            // Update createTicket to include JWT in headers
            const ticketResponse = await createTicket(loginData.user_id, ticketTitle, '');

            if (ticketResponse.status === 'success') {
              console.log(ticketResponse.message);
              localStorage.removeItem("temp_ticket_title");
              setShowLogin(false);
              setActiveTab(1);
            } else {
              console.warn("Ticket may have been created but response format unexpected");
              setShowLogin(false);
              setActiveTab(1);
            }
          } catch (ticketError) {
            console.error("Ticket creation error:", ticketError);
            setShowLogin(false);
            setActiveTab(1);
            localStorage.removeItem("temp_ticket_title");
          }
        } else {
          setShowLogin(false);
        }
      } else {
        throw new Error('Invalid response format');
      }
    } catch (error) {
      console.error("Login error:", error);
      setErrors({
        email: error.message.includes("email") ? error.message : "",
        password: error.message.includes("credentials") || error.message.includes("Login failed") 
          ? error.message 
          : "Login failed. Please try again."
      });
    }

    setIsSubmitting(false);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  const switchToLogin = (message = "") => {
    setIsSignup(false);
    setErrors({
      email: "",
      password: message || "",
      confirmPassword: ""
    });
    setFormData({ email: "", password: "", confirmPassword: "" });
  };

  return (
    <>
      <div className="rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col login-wrap">
        <div className="rd-flex rd-items-center rd-w-full rd-justify-center">
          <button
            type="button"
            className="rd-bg-transparent rd-text-white rd-p-0 rd-m-0 back-button rd-flex rd-items-center"
            aria-label="Go back to home"
            onClick={() => setShowLogin(false)}
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
            >
              <path d="M15 18l-6-6 6-6" />
            </svg>
          </button>
          <h5 className="heading-5 rd-text-white rd-text-center rd-flex-1">
            {isSignup ? "Sign Up" : "Login"}
          </h5>
        </div>
      </div>
      <div className="login-container rd-px-18">
        <div className="login-card">
          <button
            type="button"
            className="rd-w-full rd-bg-transparent rd-border-blue rd-text-blue rd-border-radius-md rd-flex rd-justify-center rd-align-center rd-gap-15 rd-p-10"
            aria-label="Sign in with Google"
            onClick={() => console.log("Google Sign-In")}
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              x="0px"
              y="0px"
              width="20"
              height="20"
              viewBox="0 0 48 48"
            >
              <path
                fill="#fbc02d"
                d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12 s5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20 s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"
              ></path>
              <path
                fill="#e53935"
                d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039 l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"
              ></path>
              <path
                fill="#4caf50"
                d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36 c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"
              ></path>
              <path
                fill="#1565c0"
                d="M43.611,20.083L43.595,20L42,20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571 c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"
              ></path>
            </svg>
            <span className="rd-title-text rd-text-blue">
              Continue with Google
            </span>
          </button>

          {isSignup ? (
            <Signup switchToLogin={switchToLogin} />
          ) : (
            <>
              <div className="rd-text-center rd-body-text rd-my-30">
                <span className="rd-px-10 rd-bg-white">
                  ----- or Sign in with Email -----
                </span>
              </div>

              <form onSubmit={handleSubmit} noValidate>
                <div>
                  <label htmlFor="email" className="rd-body-text"></label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    placeholder="Email"
                    className="rd-w-full rd-border-light rd-p-12 rd-border-radius-md"
                    required
                    aria-describedby="email-error"
                  />
                  {errors.email && (
                    <p
                      id="email-error"
                      className="rd-text-red rd-text-sm rd-mt-5 form-err-msg"
                    >
                      {errors.email}
                    </p>
                  )}
                </div>

                <div className="rd-mt-15">
                  <label htmlFor="password" className="rd-body-text"></label>
                  <input
                    type="password"
                    id="password"
                    name="password"
                    value={formData.password}
                    onChange={handleChange}
                    placeholder="Password"
                    className="rd-w-full rd-border-light rd-p-12 rd-border-radius-md"
                    required
                    aria-describedby="password-error"
                  />
                  {errors.password && (
                    <p
                      id="password-error"
                      className="rd-text-red rd-text-sm rd-mt-5 form-err-msg"
                    >
                      {errors.password}
                    </p>
                  )}
                </div>

                <div className="rd-mt-15 rd-flex rd-justify-between">
                  <div className="rd-flex rd-body-text rd-gap-8">
                    <input type="checkbox" id="remember" />
                    <label htmlFor="remember">Remember me</label>
                  </div>
                  <a
                    href="#"
                    className="rd-body-text rd-decoration-none rd-text-blue"
                  >
                    Forgot Password?
                  </a>
                </div>

                <button
                  type="submit"
                  className="rd-login-button rd-w-full rd-border-radius-md"
                  disabled={isSubmitting}
                >
                  {isSubmitting ? "Logging in..." : "Login"}
                </button>
              </form>

              <p className="rd-mt-15 rd-text-center rd-body-text rd-text-gray">
                Don't have an account?{" "}
                <button
                  onClick={() => setIsSignup(true)}
                  className="rd-text-blue rd-hover-underline rd-bg-transparent rd-border-none rd-cursor-pointer"
                >
                  Sign up
                </button>
              </p>
            </>
          )}
        </div>
      </div>
    </>
  );
};

export default Login;