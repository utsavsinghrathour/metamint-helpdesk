import "../styles/Login.css";
import { useState } from "react";
import { loginUser, registerUser, createTicket } from "../utils/modules";
import { useAppData } from "../utils/AppDataContext";
const Login = ({ activeTab, setActiveTab, setShowLogin , updateSelectedTicket}) => {
  const { updateLoginStatus , loginContext , loggedInUsrId } = useAppData();
  const [formData, setFormData] = useState({ email: "", password: "" });
  const [errors, setErrors] = useState({ email: "", password: "" });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successMessage, setSuccessMessage] = useState("");
  const [showPasswordField, setShowPasswordField] = useState(false);
  
  const handleStateUpdate =(ticket) => {
     updateSelectedTicket(ticket);
     setActiveTab(1);
  }
  const validateEmail = () => {
    const newErrors = { email: "", password: "" };
    let isValid = true;

    if (!formData.email) {
      newErrors.email = "Email is required";
      isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Invalid email format";
      isValid = false;
    }

    setErrors(newErrors);
    return isValid;
  };

  const validatePassword = () => {
    const newErrors = { email: "", password: "" };
    let isValid = true;

    if (!formData.password) {
      newErrors.password = "Password is required";
      isValid = false;
    } else if (formData.password.length < 5) {
      newErrors.password = "Password must be at least 5 characters";
      isValid = false;
    }

    setErrors(newErrors);
    return isValid;
  };

  const checkEmailExists = async (e) => {
    e.preventDefault();

    if (!validateEmail()) return;

    setIsSubmitting(true);
    try {
      const response = await fetch(wpAuth.wpAjax.ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action: "robodesk_check_email",
          security: wpAuth.wpAjax.nonce,
          email: formData.email,
        }),
      });
      const result = await response.json();

      if (result.success) {
        setShowPasswordField(result.data.exists);
      } else {
        await createUserAndLogin();
      }
    } catch (error) {
      console.error("Email check error:", error);
      setErrors({
        ...errors,
        email: "Error checking email. Please try again.",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const createUserAndLogin = async () => {
    try {
      const ticketResponse = await registerUser(formData.email);
      const user_id = ticketResponse.user_id;

      if (typeof sessionStorage !== "undefined") {
        const userData = {
          isLoggedIn: true,
          loggedInUserId: user_id,
        };

        sessionStorage.setItem("wpUserData", JSON.stringify(userData));
        updateLoginStatus(userData.isLoggedIn , userData.loggedInUserId);
        console.log("User data saved to sessionStorage:", userData);
      } else {
        console.warn("sessionStorage is not supported in this browser");
      }
      await handleTempTicketCreation(user_id);
        

    } catch (err) {
      console.error("Failed to create ticket:", err);
    }
  };

  //login in user if the email exists
  const handleLogin = async () => {
    if (!validatePassword()) return;
    setIsSubmitting(true);
    try {
      const loginResponse = await loginUser(formData.email, formData.password);

      if (typeof sessionStorage !== "undefined") {
        const userData = {
          isLoggedIn: true,
          loggedInUserId: String(loginResponse.user_id || ""),
        };

        sessionStorage.setItem("wpUserData", JSON.stringify(userData));
        console.log("User data saved to sessionStorage:", userData);
      } else {
        console.warn("sessionStorage is not supported in this browser");
      }
      setShowLogin(true);
      try {
        const ticketResponse = await handleTempTicketCreation(
          loginResponse.user_id
        );
        console.log("Ticket Creation resp", ticketResponse);

      } catch (ticketError) {
        console.error("Failed to create ticket:", ticketError);
      }
      
      
    } catch (error) {
      console.error("Login error:", error);
      setErrors({
        ...errors,
        password: error.message.includes("credentials")
          ? error.message
          : "Login failed. Please try again.",
      });
      setShowLogin(false);
    } finally {
      setIsSubmitting(false);
    }
  };
    
  //create ticket
  const handleTempTicketCreation = async (userId) => {
    const ticketTitle = localStorage.getItem("temp_ticket_title");

    if (!ticketTitle) return;

    try {
      const ticketResponse = await createTicket(
        userId,
        ticketTitle,
        ticketTitle
      );


       const newTicket = {
            id: ticketResponse.data.id,
            ticketNumber: ticketResponse.data.ticket_number,
            title:ticketResponse.data.ticket_title,
            source:  ticketResponse.data.ticket_origin,
            timeAgo: ticketResponse.data.ticket_time_ago,
            authorImage: ticketResponse.data.ticket_author_image,
            status: ticketResponse.data.ticket_status,
            priority:  ticketResponse.data.ticket_priority,
            messages: [],
        };
        handleStateUpdate(newTicket);
       
     
      return ticketResponse;
    } catch (ticketError) {
      console.error("Ticket creation error:", ticketError);
      localStorage.removeItem("temp_ticket_title");
      throw ticketError;
    }finally{ 
        localStorage.removeItem("temp_ticket_title");
      
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (showPasswordField) {
      await handleLogin();
    } else {
      await checkEmailExists(e);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
    if (name === 'email') {
    setShowPasswordField(false);
  }
  };


  return (
    <>
      <div className="rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col login-wrap">
        <div className="rd-flex rd-items-center rd-w-full rd-justify-center">
          <button
            type="button"
            className="rd-bg-transparent rd-text-white rd-p-0 rd-m-0 back-button rd-flex rd-items-center"
            aria-label="Go back to home"
            onClick={() => setActiveTab(0)}
          >
            <svg
              xmlns="http://www.w3.org/2000/svg”"
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
            Login
          </h5>
        </div>
      </div>
      <div className="login-container rd-px-18">
        <div className="login-card">
          <form onSubmit={handleSubmit} noValidate>
            {successMessage && (
              <p className="rd-text-green rd-text-sm rd-mt-5 rd-mb-10">
                {successMessage}
              </p>
            )}
            <div>
              <label htmlFor="email" className="rd-body-text">
                Email
              </label>
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
                <p id="email-error" className="rd-text-red rd-text-sm rd-mt-5">
                  {errors.email}
                </p>
              )}
            </div>
            {showPasswordField && (
              <div>
                <label htmlFor="password" className="rd-body-text">
                  Password
                </label>
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
                    className="rd-text-red rd-text-sm rd-mt-5"
                  >
                    {errors.password}
                  </p>
                )}
              </div>
            )}
            <button
              type="submit"
              className="rd-login-button rd-w-full rd-border-radius-md"
              disabled={isSubmitting}
            >
              {isSubmitting
                ? "Processing..."
                : showPasswordField
                ? "Login"
                : "Continue"}
            </button>
          </form>
        </div>
      </div>
    </>
  );
};

export default Login;
