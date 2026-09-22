import { useRef, useState } from "react";

export default function ForgotPassword({ onSent, email }) {
  const userDetailRef = useRef(null);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [sent, setSent] = useState(false);
  async function handleSubmit(e) {
    e.preventDefault();

    if (sent) {
      onSent();
      setSent(false);
      return;
    }

    setIsLoading(true);
    setMessage("");
    setError("");
    const userDetail = userDetailRef.current.value;

    try {
      const response = await fetch("/wp-json/mmhd/v1/lost-password", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          user_login: userDetail,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        setMessage(
          data.message ||
            "A password reset email has been sent to your email address.",
        );
        setSent(true);
        userDetailRef.current.value = "";
      } else {
        setError(data.message || "Something went wrong. Please try again.");
      }
    } catch (err) {
      setError("Network error. Please check your connection and try again.");
    } finally {
      setIsLoading(false);
    }
  }
  return (
    <div className="login-container mmhd-px-18">
      <h4 className="heading-4 mmhd-text-center">Lost password</h4>
      <div className="mmhd-body-text mmhd-mt-8 mmhd-text-center mmhd-mb-20">
        <p>Please enter your email address to reset your password</p>
      </div>
      <div className="login-card">
        <form onSubmit={handleSubmit} noValidate>
          <div className="mmhd-flex mmhd-flex-col mmhd-gap-10">
            {!sent && (
              <>
                <label htmlFor="userdetail" className="mmhd-body-text mmhd-fw-semi">
                  Email
                </label>

                <input
                  type="text"
                  id="userdetail"
                  name="userdetail"
                  ref={userDetailRef}
                  placeholder="Email"
                  className="mmhd-border-light mmhd-p-12 mmhd-border-radius-md"
                  required
                  aria-describedby="email-error"
                  defaultValue={email}
                />
              </>
            )}

            {error && (
              <p className="mmhd-text-red-500 mmhd-mt-2 err_msg" id="email-error">
                {error}
              </p>
            )}
            {message && (
              <p className="text-green color-green mmhd-mt-5 mmhd-body-text">
                {message}
              </p>
            )}
            <button
              type="submit"
              className="mmhd-login-button mmhd-w-full mmhd-border-radius-md"
              disabled={isLoading}
            >
              {isLoading
                ? "Just a moment"
                : sent
                ? "Go back"
                : "Reset Password"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
