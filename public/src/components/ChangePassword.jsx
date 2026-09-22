import { useState, useEffect } from 'react';

export default function ChangePassword({ onReset }) {
  const [formData, setFormData] = useState({
    password: '',
    confirmPassword: '',
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const [resetKey, setResetKey] = useState(false);
  const [resetUser, setResetUser] = useState(false);

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const key = urlParams.get('key');
    const user = urlParams.get('user');

    if (key) {
      setResetKey(key);
    }

    if (user) {
      setResetUser(user);
    }
  }, [resetKey, resetUser]);

  //   const removeParam = () => {
  //     if (searchParams.has('mmhd_popup')) {
  //       searchParams.delete('mmhd_popup');
  //       setSearchParams(searchParams);
  //     }
  //   };

  const validatePassword = (password) => {
    const errors = [];

    if (password.length < 8) {
      errors.push('Password must be at least 8 characters long');
    }
    // if (!/[a-z]/.test(password)) {
    //   errors.push('Password must contain at least one lowercase letter');
    // }
    // if (!/[A-Z]/.test(password)) {
    //   errors.push('Password must contain at least one uppercase letter');
    // }
    // if (!/[0-9]/.test(password)) {
    //   errors.push('Password must contain at least one number');
    // }
    // if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
    //   errors.push('Password must contain at least one special character');
    // }

    return errors;
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));

    // Clear error when user types
    if (errors[name]) {
      setErrors((prev) => ({
        ...prev,
        [name]: '',
      }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    // Validate password
    const passwordErrors = validatePassword(formData.password);
    if (passwordErrors.length > 0) {
      newErrors.password = passwordErrors[0];
    }

    // Validate confirm password
    if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = 'Passwords do not match';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    setMessage('');

    if (!validateForm()) {
      return;
    }

    if (!resetKey) {
      setErrors((prev) => ({
        ...prev,
        password: 'Reset key is missing. Please try again.',
      }));
      return;
    }

    setLoading(true);

    try {
      // WordPress Ajax request
      const response = await fetch(window.mmhdAuth?.wpAjax?.ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'mmhd_change_password',
          password: formData.password,
          reset_key: resetKey,
          user: resetUser,
          nonce: window.mmhdAuth?.wpAjax?.passwordResetNonce,
        }),
      });

      const data = await response.json();

      if (data.success) {
        setMessage('Password changed successfully! Going Back to Login...');

        setTimeout(() => {
          setFormData({ password: '', confirmPassword: '' });

          const cleanUrl = window.location.origin + window.location.pathname;
          window.history.replaceState({}, document.title, cleanUrl);

          onReset();
        }, 2000);
      } else {
        setMessage(data.data?.message || 'Failed to change password');
        const cleanUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
      }
    } catch (error) {
      setMessage('An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className='login-container mmhd-px-18'>
      <h4 className='heading-4 mmhd-text-center'>Change Password</h4>
      <div className='mmhd-body-text mmhd-mt-8 mmhd-text-center mmhd-mb-20'>
        <p>Please enter your New Password</p>
      </div>

      <div className='login-card'>
        <form noValidate onSubmit={handleSubmit}>
          <div className='mmhd-mb-16'>
            <label htmlFor='password' className='mmhd-body-text mmhd-mb-4 mmhd-block mmhd-fw-semi'>
              New Password
            </label>
            <input
              type='password'
              id='password'
              name='password'
              value={formData.password}
              onChange={handleInputChange}
              placeholder='Enter Your New Password'
              className={`mmhd-w-full mmhd-border mmhd-p-12 mmhd-border-radius-md mmhd-border-light ${
                errors.password ? 'mmhd-border-error' : ''
              }`}
              required
              aria-describedby='password-error'
            />
            {errors.password && (
              <p
                id='password-error'
                className='mmhd-text-error mmhd-text-sm mmhd-mt-4 err_msg'
              >
                {errors.password}
              </p>
            )}
          </div>

          <div className='mmhd-mb-24 mmhd-mt-10'>
            <label
              htmlFor='confirmPassword'
              className='mmhd-body-text mmhd-mb-4 mmhd-block mmhd-fw-semi'
            >
              Confirm New Password
            </label>
            <input
              type='password'
              id='confirmPassword'
              name='confirmPassword'
              value={formData.confirmPassword}
              onChange={handleInputChange}
              placeholder='Confirm Your New Password'
              className={`mmhd-w-full mmhd-border mmhd-p-12 mmhd-border-radius-md mmhd-border-light ${
                errors.confirmPassword ? 'mmhd-border-error' :''
              }`}
              required
              aria-describedby='confirm-password-error'
            />
            {errors.confirmPassword && (
              <p
                id='confirm-password-error'
                className='mmhd-text-error mmhd-text-sm mmhd-mt-4 err_msg'
              >
                {errors.confirmPassword}
              </p>
            )}
          </div>
          {message && (
            <div
              className={`mmhd-alert success_msg ${
                message.includes('success')
                  ? 'mmhd-alert-success'
                  : 'mmhd-alert-error'
              } mmhd-mb-20`}
            >
              {message}
            </div>
          )}
          <button
            type='submit'
            className='mmhd-login-button mmhd-w-full mmhd-border-radius-md'
            disabled={loading}
          >
            {loading ? 'Changing Password...' : 'Change Password'}
          </button>
        </form>
      </div>
    </div>
  );
}
