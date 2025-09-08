import '../styles/Login.css';
import { useEffect, useState } from 'react';
import { loginUser, registerUser, createTicket } from '../utils/modules';
import { useAppData } from '../utils/AppDataContext';
import defaultLogo from '../images/robodesk-logo.png';
import ForgotPassword from './ForgotPassword';
import ChangePassword from './ChangePassword';

// Header Component
const Header = ({ setActiveTab, image_urls, defaultLogo }) => (
  <div className='rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col login-wrap'>
    <div className='rd-flex rd-items-center rd-w-full rd-justify-center'>
      <button
        type='button'
        className='rd-bg-transparent rd-text-white rd-p-0 rd-m-0 back-button rd-flex rd-items-center'
        aria-label='Go back to home'
        onClick={() => setActiveTab(0)}
      >
        <svg
          xmlns='http://www.w3.org/2000/svg'
          width='20'
          height='20'
          viewBox='0 0 24 24'
          fill='none'
          stroke='currentColor'
          strokeWidth='2'
          strokeLinecap='round'
          strokeLinejoin='round'
        >
          <path d='M15 18l-6-6 6-6' />
        </svg>
      </button>
      {image_urls.wide_logo && image_urls.wide_logo.trim() !== '' ? (
        <img
          src={image_urls.wide_logo}
          alt='Plugin Wide Logo'
          className='rd-h-auto rd-header-logo'
          onError={(e) => {
            e.target.style.display = 'none';
          }}
        />
      ) : image_urls.logo && image_urls.logo.trim() !== '' ? (
        <img
          src={image_urls.logo}
          alt='Plugin Logo'
          className='rd-h-auto rd-header-logo'
          onError={(e) => {
            e.target.style.display = 'none';
          }}
        />
      ) : (
        <div className='rd-h-auto rd-header-logo default-logo'>
          <img
            src={defaultLogo}
            alt='Default Plugin Logo'
            className='rd-h-auto rd-header-logo'
          />
        </div>
      )}
    </div>
  </div>
);

// ErrorMessage Component
const ErrorMessage = ({ id, message }) =>
  message && (
    <p id={id} className='rd-text-red rd-text-sm rd-mt-5'>
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
  <div className='login-container rd-px-18'>
    <h4 className='heading-4 rd-text-center'>Login</h4>
    <div className='rd-body-text rd-mt-8 rd-text-center rd-mb-20'>
      <p>How do we get in touch with you?</p>
    </div>
    <div className='login-card'>
      <form onSubmit={handleSubmit} noValidate>
        {successMessage && (
          <p className='rd-text-green rd-text-sm rd-mt-5 rd-mb-10'>
            {successMessage}
          </p>
        )}
        <div>
          <label htmlFor='email' className='rd-body-text'>
            Email
          </label>
          <input
            type='email'
            id='email'
            name='email'
            value={formData.email}
            onChange={handleChange}
            placeholder='Email'
            className='rd-w-full rd-border-light rd-p-12 rd-border-radius-md'
            required
            aria-describedby='email-error'
          />
          <ErrorMessage id='email-error' message={errors.email} />
        </div>
        {showPasswordField && (
          <div>
            <label htmlFor='password' className='rd-body-text'>
              Password
            </label>
            <input
              type='password'
              id='password'
              name='password'
              value={formData.password}
              onChange={handleChange}
              placeholder='Password'
              className='rd-w-full rd-border-light rd-p-12 rd-border-radius-md'
              required
              aria-describedby='password-error'
            />
            <ErrorMessage id='password-error' message={errors.password} />
            <p>
              <a href='#' onClick={handleForgotPassword}>
                Lost your password?
              </a>
            </p>
          </div>
        )}
        <button
          type='submit'
          className='rd-login-button rd-w-full rd-border-radius-md'
          disabled={isSubmitting}
        >
          {isSubmitting
            ? 'Just a moment...'
            : showPasswordField
            ? 'Login'
            : 'Continue'}
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
  const [formData, setFormData] = useState({ email: '', password: '' });
  const [errors, setErrors] = useState({ email: '', password: '' });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [showPasswordField, setShowPasswordField] = useState(false);

  const [currentForm, setCurrentForm] = useState('login');

  const image_urls = window.robodeskData?.imageUrls || {};

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const popupParam = params.get('rbd_popup');
    const initiatorParam = params.get('initator');

    if (popupParam === 'true' && initiatorParam === 'robodesk') {
      setCurrentForm('change');
    }
  }, [currentForm]);

  const handleStateUpdate = (ticket) => {
    updateSelectedTicket(ticket);
    setActiveTab(1);
  };

  const validateEmail = () => {
    const newErrors = { email: '', password: '' };
    let isValid = true;

    if (!formData.email) {
      newErrors.email = 'Your email address is needed to continue.';
      isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Oops! That doesn’t look like a valid email.';
      isValid = false;
    }

    setErrors(newErrors);
    return isValid;
  };

  const validatePassword = () => {
    const newErrors = { email: '', password: '' };
    let isValid = true;

    if (!formData.password) {
      newErrors.password = 'Password is required';
      isValid = false;
    } else if (formData.password.length < 5) {
      newErrors.password = 'Password must be at least 5 characters';
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
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'robodesk_check_email',
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
      console.error('Email check error:', error);
      setErrors({
        ...errors,
        email: 'Error checking email. Please try again.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const createUserAndLogin = async () => {
    try {
      const ticketResponse = await registerUser(formData.email);
      const user_id = ticketResponse.user_id;

      await handleTempTicketCreation(user_id);
      updateLoginStatus(true, user_id);
    } catch (err) {
      console.error('Failed to create ticket:', err);
    }
  };

  const handleLogin = async () => {
    if (!validatePassword()) return;

    setIsSubmitting(true);
    try {
      const loginResponse = await loginUser(formData.email, formData.password);

      //   console.log('The login response', loginResponse);
      //add token here later

      updateLoginStatus(true, loginResponse.user_id);

      console.log('The loggedin user id ', loginResponse.user_id);
      try {
        const ticketResponse = await handleTempTicketCreation(
          loginResponse.user_id
        );

        console.log('From the temp ticket resp', ticketResponse);
      } catch (ticketError) {}
    } catch (error) {
      setErrors({
        ...errors,
        password: error.message.includes('invalid_credentials')
          ? error.message
          : 'Oops! That’s not the right password.',
      });
      setShowLogin(false);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleTempTicketCreation = async (userId) => {
    // alert('From temp ticket non logged in usr', userId);
    const ticketTitle = localStorage.getItem('temp_ticket_title');
    console.log('the ticket temp title is thsi');
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
        title: ticketResponse.data.ticket_title,
        source: ticketResponse.data.ticket_origin,
        timeAgo: ticketResponse.data.ticket_time_ago,
        authorImage: ticketResponse.data.ticket_author_image,
        status: ticketResponse.data.ticket_status,
        priority: ticketResponse.data.ticket_priority,
        messages: [],
      };
      refreshTickets();
      handleStateUpdate(newTicket);
      return ticketResponse;
    } catch (ticketError) {
      console.error('Ticket creation error:', ticketError);
      localStorage.removeItem('temp_ticket_title');
      throw ticketError;
    } finally {
      localStorage.removeItem('temp_ticket_title');
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

  const onPasswordReset = () => {
    setCurrentForm('login');
    // setShowPasswordField(true);
  };

  const handleForgotPassword = (e) => {
    e.preventDefault();
    setCurrentForm('forgot');
  };
  const handleBackToLogin = () => {
    setCurrentForm('login');
  };

  return (
    <>
      <Header
        setActiveTab={setActiveTab}
        image_urls={image_urls}
        defaultLogo={defaultLogo}
      />

      {currentForm === 'login' && (
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
      {currentForm === 'forgot' && (
        <ForgotPassword onSent={handleBackToLogin} email={formData.email} />
      )}

      {currentForm === 'change' && (
        <ChangePassword setActiveTab={setActiveTab} onReset={onPasswordReset} />
      )}
    </>
  );
};

export default Login;
