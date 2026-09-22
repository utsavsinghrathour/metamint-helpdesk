import "../styles/Login.css";
import { useEffect, useState } from "react";
import { FaChevronLeft } from "react-icons/fa";
import { loginUser, registerUser, createTicket, sendReply } from "../utils/modules";
import { readChatSession, CHAT_SESSION_KEY } from "../utils/startConversation";
import { useAppData } from "../utils/AppDataContext";
import defaultLogo from "../images/mmhd-logo.png";
import ForgotPassword from "./ForgotPassword";
import ChangePassword from "./ChangePassword";

// Header Component
const Header = ({ setActiveTab, image_urls, defaultLogo }) => (
  <div className="mmhd-search-header mmhd-bg-blue mmhd-flex mmhd-items-center mmhd-justify-between mmhd-flex-col mmhd-message-list-header">
    <button
      type="button"
      className="back-button mmhd-flex mmhd-items-center mmhd-gap-1 mmhd-border-none mmhd-bg-transparent mmhd-m-0 mmhd-p-0"
      aria-label="Go back to home"
      onClick={() => setActiveTab(0)}
    >
      <FaChevronLeft />
    </button>
    {image_urls.wide_logo && image_urls.wide_logo.trim() !== "" ? (
      <img
        src={image_urls.wide_logo}
        alt="Plugin Wide Logo"
        className="mmhd-header-logo mmhd-m-auto"
        onError={(e) => {
          e.target.style.display = "none";
        }}
      />
    ) : image_urls.logo && image_urls.logo.trim() !== "" ? (
      <img
        src={image_urls.logo}
        alt="Plugin Logo"
        className="mmhd-header-logo mmhd-m-auto"
        onError={(e) => {
          e.target.style.display = "none";
        }}
      />
    ) : (
      <div className="mmhd-header-logo default-logo">
        <img
          src={defaultLogo}
          alt="Default Plugin Logo"
          className="mmhd-header-logo mmhd-m-auto"
        />
      </div>
    )}
  </div>
);

// ErrorMessage Component
const ErrorMessage = ({ id, message }) =>
  message && (
    <p id={id} className="mmhd-text-red mmhd-text-sm mmhd-mt-5 err_msg">
      {message}
    </p>
  );

// LoginForm Component
const LoginForm = ({
  formData,
  errors,
  isSubmitting,
  successMessage,
  showPasswordField,
  handleSubmit,
  handleChange,
  handleForgotPassword,
}) => (
  <div className="login-container mmhd-px-18">
    <h4 className="heading-4 mmhd-text-center">Login</h4>
    <div className="mmhd-body-text mmhd-mt-8 mmhd-text-center mmhd-mb-20">
      <p>How do we get in touch with you?</p>
    </div>
    <div className="login-card">
      <form onSubmit={handleSubmit} noValidate>
        {successMessage && (
          <p className="mmhd-text-green mmhd-text-sm mmhd-mt-5 mmhd-mb-10">
            {successMessage}
          </p>
        )}
        <div className="mmhd-flex mmhd-flex-col mmhd-gap-10">
          <label htmlFor="email" className="mmhd-body-text mmhd-fw-semi">
            Email
          </label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            placeholder="Email"
            className="mmhd-border-light mmhd-p-12 mmhd-border-radius-md"
            required
            aria-describedby="email-error"
          />
          <ErrorMessage
            id="email-error"
            className="err_msg"
            message={errors.email}
          />
        </div>
        {showPasswordField && (
          <div className="mmhd-mt-10">
            <label htmlFor="password" className="mmhd-body-text mmhd-fw-semi">
              Password
            </label>
            <input
              type="password"
              id="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              placeholder="Password"
              className="mmhd-w-full mmhd-border-light mmhd-p-12 mmhd-border-radius-md"
              required
              aria-describedby="password-error"
            />
            <ErrorMessage
              id="password-error"
              className="err_msg"
              message={errors.password}
            />
            <p className="mmhd-body-text mmhd-mt-8">
              <a
                href="#"
                className="mmhd-decoration-none color-blue"
                onClick={handleForgotPassword}
              >
                Lost your password?
              </a>
            </p>
          </div>
        )}
        <button
          type="submit"
          className="mmhd-login-button mmhd-w-full mmhd-border-radius-md"
          disabled={isSubmitting}
        >
          {isSubmitting
            ? "Just a moment..."
            : showPasswordField
            ? "Login"
            : "Continue"}
        </button>
      </form>
    </div>
  </div>
);

// Main Login Component
const Login = ({
  activeTab,
  setActiveTab,
  setShowLogin,
  updateSelectedTicket,
}) => {
  const { updateLoginStatus, refreshTickets } = useAppData();
  const [formData, setFormData] = useState({ email: "", password: "" });
  const [errors, setErrors] = useState({ email: "", password: "" });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successMessage, setSuccessMessage] = useState("");
  const [showPasswordField, setShowPasswordField] = useState(false);
  const [defaultStaffId, setDefaultStaffId] = useState(null);

  const [currentForm, setCurrentForm] = useState("login");

  const image_urls = window.mmhdData?.imageUrls || {};

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const resetKey = params.get("key");
    const resetUser = params.get("user");
    const popupParam = params.get("mmhd_popup");
    const initiatorParam = params.get("initiator") || params.get("initator");

    if (
      popupParam === "true" &&
      initiatorParam === "mmhd" &&
      resetKey &&
      resetUser
    ) {
      setCurrentForm("change");
    }
  }, []);

  useEffect(() => {
    if (window.mmhdAuth?.defaultStaffId) {
      setDefaultStaffId(window.mmhdAuth.defaultStaffId);
    }
  }, []);

  const handleStateUpdate = (ticket) => {
    updateSelectedTicket(ticket);
    if (window.mmhdData?.enable_chat === "1") setActiveTab(1);
  };

  const validateEmail = () => {
    const newErrors = { email: "", password: "" };
    let isValid = true;

    if (!formData.email) {
      newErrors.email = "Your email address is needed to continue.";
      isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Oops! That doesn’t look like a valid email.";
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
      const response = await fetch(window.mmhdAuth?.wpAjax?.ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action: "mmhd_check_email",
          security: window.mmhdAuth?.wpAjax?.nonce,
          email: formData.email,
        }),
      });
      const result = await response.json();

      if (result.success) {
        setShowPasswordField(result.data.exists);
      } else if (result.data && result.data.message) {
        setErrors({
          ...errors,
          email: result.data.message,
        });
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

      if (ticketResponse.rest_nonce) {
        window.mmhdAuth = window.mmhdAuth || {};
        window.mmhdAuth.restNonce = ticketResponse.rest_nonce;
      }

      await handleTempTicketCreation(user_id);
      updateLoginStatus(true, user_id);
    } catch (err) {
      setErrors({
        ...errors,
        email: err.message || "Signup failed. Please try again.",
      });
    }
  };

  const handleLogin = async () => {
    if (!validatePassword()) return;

    setIsSubmitting(true);
    try {
      const loginResponse = await loginUser(formData.email, formData.password);

      updateLoginStatus(true, loginResponse.user_id);
      if (loginResponse.rest_nonce) {
        window.mmhdAuth = window.mmhdAuth || {};
        window.mmhdAuth.restNonce = loginResponse.rest_nonce;
      }

      try {
        const ticketResponse = await handleTempTicketCreation(
          loginResponse.user_id,
        );
      } catch (ticketError) {}
    } catch (error) {
      setErrors({
        ...errors,
        password: error.message.includes("invalid_credentials")
          ? error.message
          : "Oops! That’s not the right password.",
      });
      setShowLogin(false);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleTempTicketCreation = async (userId) => {
    const conversationMessages = readChatSession();

    let transcriptMessages = Array.isArray(conversationMessages)
      ? conversationMessages.filter(
          (message) =>
            (message.role === "user" || message.role === "assistant") &&
            !message.thinking,
        )
      : [];

    // Stably sort transcriptMessages chronologically by timestamp
    const withIndex = transcriptMessages.map((msg, idx) => ({ msg, idx }));
    withIndex.sort((a, b) => {
      const timeA = a.msg.timestamp ? new Date(a.msg.timestamp).getTime() : null;
      const timeB = b.msg.timestamp ? new Date(b.msg.timestamp).getTime() : null;
      if (timeA !== null && timeB !== null) {
        if (timeA !== timeB) {
          return timeA - timeB;
        }
      }
      return a.idx - b.idx;
    });
    transcriptMessages = withIndex.map(item => item.msg);

    const userMessages = transcriptMessages.filter(
      (message) => message.role === "user",
    );

    const fallbackTitle = localStorage.getItem("temp_ticket_title");
    const ticketTitle = userMessages[0]?.text || fallbackTitle;
    const ticketDescription = transcriptMessages.length
      ? transcriptMessages
          .map((message) => {
            const label = message.role === "assistant" ? "AI BOT" : "Customer";
            const body = message.text || message.message || "";
            return `${label}: ${body}`.trim();
          })
          .join("\n\n")
      : ticketTitle;

    if (!ticketTitle) return;

    try {
      const ticketResponse = await createTicket(
        userId,
        ticketTitle,
        ticketDescription,
        userMessages[0]?.timestamp || new Date().toISOString()
      );

      const newTicket = {
        id: ticketResponse.data.id,
        ticketNumber: ticketResponse.data.ticket_number,
        title: ticketResponse.data.ticket_title,
        source:
          ticketResponse.data.ticket_origin &&
          ticketResponse.data.ticket_origin !== 'N/A'
            ? ticketResponse.data.ticket_origin
            : 'Web',
        timeAgo: ticketResponse.data.ticket_time_ago,
        authorImage: ticketResponse.data.ticket_author_image,
        status: ticketResponse.data.ticket_status,
        priority: ticketResponse.data.ticket_priority,
        messages: [],
      };
      if (transcriptMessages.length > 0) {
        const firstUserMsg = userMessages[0];
        for (const m of transcriptMessages) {
          if (m === firstUserMsg) continue;

          const text = m.text || m.message || '';
          if (!text.trim()) continue;

          const isBot = m.role === 'assistant';

          for (let attempt = 0; attempt < 3; attempt++) {
            try {
              await sendReply(
                newTicket.id,
                userId,
                text,
                false,
                isBot,
                undefined,
                m.timestamp || new Date().toISOString()
              );
              break;
            } catch (e) {
              if (attempt === 2) {
                console.error('Failed to transfer message:', e);
              } else {
                await new Promise(r => setTimeout(r, 500));
              }
            }
          }
        }
      }

      refreshTickets();
      handleStateUpdate(newTicket);

      sessionStorage.removeItem(CHAT_SESSION_KEY);
      return ticketResponse;
    } catch (ticketError) {
      console.error("Ticket creation error:", ticketError);
      localStorage.removeItem("temp_ticket_title");
      throw ticketError;
    } finally {
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
    if (name === "email") {
      setShowPasswordField(false);
    }
  };

  const onPasswordReset = () => {
    setCurrentForm("login");
    // setShowPasswordField(true);
  };

  const handleForgotPassword = (e) => {
    e.preventDefault();
    setCurrentForm("forgot");
  };
  const handleBackToLogin = () => {
    setCurrentForm("login");
  };

  return (
    <>
      <Header
        setActiveTab={setActiveTab}
        image_urls={image_urls}
        defaultLogo={defaultLogo}
      />

      {currentForm === "login" && (
        <LoginForm
          formData={formData}
          errors={errors}
          isSubmitting={isSubmitting}
          successMessage={successMessage}
          showPasswordField={showPasswordField}
          handleSubmit={handleSubmit}
          handleChange={handleChange}
          handleForgotPassword={handleForgotPassword}
        />
      )}
      {currentForm === "forgot" && (
        <ForgotPassword onSent={handleBackToLogin} email={formData.email} />
      )}

      {currentForm === "change" && (
        <ChangePassword setActiveTab={setActiveTab} onReset={onPasswordReset} />
      )}
    </>
  );
};

export default Login;
