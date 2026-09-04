import React from "react";
import { useState, useEffect } from "react";
import "../styles/Signup.css";
import { registerUser } from "../utils/modules";

const Signup = ({ switchToLogin }) => {
  const [formData, setFormData] = useState({
    username: "",
    email: "",
    password: "",
    confirmPassword: ""
  });
  const [errors, setErrors] = useState({
    username: "",
    email: "",
    password: "",
    confirmPassword: ""
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [passwordStrength, setPasswordStrength] = useState({
    score: 0,
    label: "",
    color: ""
  });

  // Password strength checker
  useEffect(() => {
    if (formData.password) {
      const strength = checkPasswordStrength(formData.password);
      setPasswordStrength(strength);
    } else {
      setPasswordStrength({ score: 0, label: "", color: "" });
    }
  }, [formData.password]);

  const checkPasswordStrength = (password) => {
    let score = 0;

    // Length check
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;

    // Complexity checks
    if (/[A-Z]/.test(password)) score++; // Has uppercase
    if (/[a-z]/.test(password)) score++; // Has lowercase
    if (/[0-9]/.test(password)) score++; // Has numbers
    if (/[^A-Za-z0-9]/.test(password)) score++; // Has special chars

    // Determine label and color
    let label, color;
    if (score <= 2) {
      label = "Weak";
      color = "#ff4d4f"; // Red
    } else if (score <= 4) {
      label = "Moderate";
      color = "#faad14"; // Orange
    } else {
      label = "Strong";
      color = "#52c41a"; // Green
    }

    return { score, label, color };
  };

  const validateForm = () => {
    const newErrors = { username: "", email: "", password: "", confirmPassword: "" };
    let isValid = true;

    if (!formData.username) {
      newErrors.username = "Username is required";
      isValid = false;
    } else if (formData.username.length <= 5) {
      newErrors.username = "Username must be more than 5 characters";
      isValid = false;
    }

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
    } else if (formData.password.length < 8) {
      newErrors.password = "Password must be at least 8 characters";
      isValid = false;
    } else if (passwordStrength.score <= 2) {
      newErrors.password = "Password is too weak";
      isValid = false;
    }

    if (!formData.confirmPassword) {
      newErrors.confirmPassword = "Please confirm your password";
      isValid = false;
    } else if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = "Passwords do not match";
      isValid = false;
    }

    setErrors(newErrors);
    return isValid;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrors({ username: "", email: "", password: "", confirmPassword: "" });

    if (!validateForm()) {
      setIsSubmitting(false);
      return;
    }

    try {
      // Call registerUser with the required fields
      const signupResponse = await registerUser({
        username: formData.username,
        email: formData.email,
        password: formData.password
      });

      // Check if response indicates success
      if (signupResponse.success || signupResponse.message?.toLowerCase().includes("success")) {
        switchToLogin("Registration successful! Please login.");
      } else {
        throw new Error(signupResponse.message || "Registration failed");
      }

    } catch (error) {
      console.error("Signup error:", error.message);
      // Handle specific error messages from the server
      let errorMessage = error.message || "Signup failed. Please try again.";
      setErrors({
        username: error.message.includes("username") ? errorMessage : "",
        email: error.message.includes("email") ? errorMessage : "",
        password: error.message.includes("password") ? errorMessage : errorMessage,
        confirmPassword: ""
      });
    }

    setIsSubmitting(false);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormData({ ...formData, [name]: value });
  };

  return (
    <>
      <div className="mmhd-text-center mmhd-body-text mmhd-my-30">
        <span className="mmhd-px-10 mmhd-bg-white">
          ----- or Sign up with Email -----
        </span>
      </div>

      <form onSubmit={handleSubmit} noValidate>

        <div>
          <label htmlFor="username" className="mmhd-body-text"></label>
          <input
            type="text"
            id="username"
            name="username"
            value={formData.username}
            onChange={handleChange}
            placeholder="Username"
            className="mmhd-w-full mmhd-border-light mmhd-p-12 mmhd-border-radius-md"
            required
            aria-describedby="username-error"
          />
          {errors.username && (
            <p
              id="username-error"
              className="mmhd-text-red mmhd-text-sm mmhd-mt-5 form-err-msg err_msg"
            >
              {errors.username}
            </p>
          )}
        </div>

        <div className="mmhd-mt-15">
          <label htmlFor="email" className="mmhd-body-text"></label>
          <input
            type="email"
            id="email-address"
            name="email"
            value={formData.email}
            onChange={handleChange}
            placeholder="Email"
            className="mmhd-w-full mmhd-border-light mmhd-p-12 mmhd-border-radius-md"
            required
            aria-describedby="email-error"
          />
          {errors.email && (
            <p
              id="email-error"
              className="mmhd-text-red mmhd-text-sm mmhd-mt-5 form-err-msg err_msg"
            >
              {errors.email}
            </p>
          )}
        </div>

        <div className="mmhd-mt-15">
          <label htmlFor="password" className="mmhd-body-text"></label>
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
          {formData.password && (
            <div className="mmhd-mt-10 mmhd-flex mmhd-items-center mmhd-gap-10">
              <div className="mmhd-flex-1 mmhd-h-5 mmhd-bg-gray-100 mmhd-rounded-full mmhd-overflow-hidden">
                <div
                  className="mmhd-h-full mmhd-rounded-full"
                  style={{
                    width: `${(passwordStrength.score / 6) * 100}%`,
                    backgroundColor: passwordStrength.color
                  }}
                ></div>
              </div>
              <span
                className="mmhd-text-sm"
                style={{ color: passwordStrength.color }}
              >
                {passwordStrength.label}
              </span>
            </div>
          )}
          <div className="mmhd-text-xs mmhd-text-gray-500 mmhd-mt-5 mmhd-info err_msg">
            Password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters.
          </div>
          {errors.password && (
            <p
              id="password-error"
              className="mmhd-text-red mmhd-text-sm mmhd-mt-5 form-err-msg err_msg"
            >
              {errors.password}
            </p>
          )}
        </div>

        <div className="mmhd-mt-15">
          <label htmlFor="confirmPassword" className="mmhd-body-text"></label>
          <input
            type="password"
            id="confirmPassword"
            name="confirmPassword"
            value={formData.confirmPassword}
            onChange={handleChange}
            placeholder="Confirm Password"
            className="mmhd-w-full mmhd-border-light mmhd-p-12 mmhd-border-radius-md"
            required
            aria-describedby="confirmPassword-error"
          />
          {errors.confirmPassword && (
            <p
              id="confirmPassword-error"
              className="mmhd-text-red mmhd-text-sm mmhd-mt-5 form-err-msg err_msg"
            >
              {errors.confirmPassword}
            </p>
          )}
        </div>

        <button
          type="submit"
          className="mmhd-login-button mmhd-w-full mmhd-border-radius-md"
          disabled={isSubmitting}
        >
          {isSubmitting ? "Signing up..." : "Sign Up"}
        </button>
      </form>

      <p className="mmhd-mt-15 mmhd-text-center mmhd-body-text mmhd-text-gray">
        Already have an account?{" "}
        <button
          onClick={() => switchToLogin()}
          className="mmhd-text-blue mmhd-hover-underline mmhd-bg-transparent mmhd-border-none mmhd-cursor-pointer"
        >
          Login
        </button>
      </p>
    </>
  );
};

export default Signup;