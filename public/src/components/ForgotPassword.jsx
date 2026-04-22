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
      const response = await fetch("/wp-json/robodesk/v1/lost-password", {
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
    <div className="login-container rd-px-18">
      <h4 className="heading-4 rd-text-center">Lost password</h4>
      <div className="rd-body-text rd-mt-8 rd-text-center rd-mb-20">
        <p>Please enter your email address to reset your password</p>
      </div>
      <div className="login-card">
        <form onSubmit={handleSubmit} noValidate>
          <div className="rd-flex rd-flex-col rd-gap-10">
            {!sent && (
              <>
                <label htmlFor="userdetail" className="rd-body-text rd-fw-semi">
                  Email
                </label>

                <input
                  type="text"
                  id="userdetail"
                  name="userdetail"
                  ref={userDetailRef}
                  placeholder="Email"
                  className="rd-border-light rd-p-12 rd-border-radius-md"
                  required
                  aria-describedby="email-error"
                  defaultValue={email}
                />
              </>
            )}

            {error && (
              <p className="rd-text-red-500 rd-mt-2 err_msg" id="email-error">
                {error}
              </p>
            )}
            {message && (
              <p className="text-green color-green rd-mt-5 rd-body-text">
                {message}
              </p>
            )}
            <button
              type="submit"
              className="rd-login-button rd-w-full rd-border-radius-md"
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
