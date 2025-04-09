import React from 'react';
import '../styles/Login.css';

const Login = () => {
  return (
    <>
    <div className="rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col login-wrap">
        <h5 className="heading-5 rd-text-white rd-text-center">Login</h5>
      </div>
    <div className="login-container rd-px-18">
      <div className="login-card">
        <button className="rd-w-full rd-bg-transparent rd-border-blue rd-text-blue rd-border-radius-md rd-flex rd-justify-center rd-align-center rd-gap-15 rd-p-10">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18px"
            height="18px"
            viewBox="-3 0 262 262"
            preserveAspectRatio="xMidYMid"
          >
            <path
              d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027"
              fill="#4285F4"
            />
            <path
              d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1"
              fill="#34A853"
            />
            <path
              d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.82l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782"
              fill="#FBBC05"
            />
            <path
              d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251"
              fill="#EB4335"
            />
          </svg>
          <span className="rd-title-text rd-text-blue">Continue with Google</span>
        </button>

        <div className="rd-text-center rd-body-text rd-my-30">-----or Sign in with Email------</div>

        <div>
          <div>
            <label htmlFor="email"></label>
            <input
              type="email"
              id="email"
              placeholder="Email"
              className="rd-w-full rd-border-light rd-p-12 rd-border-radius-md"
            />
          </div>

          <div className="rd-mt-15">
            <label htmlFor="password"></label>
            <input
              type="password"
              id="password"
              placeholder="Password"
              className="rd-w-full rd-border-light rd-p-12 rd-border-radius-md"
            />
          </div>

          <div className="rd-mt-15 rd-flex rd-justify-between">
            <div className="rd-flex rd-body-text rd-gap-8">
              <input type="checkbox" id="remember" />
              <label htmlFor="remember">Remember me</label>
            </div>
            <a href="#" className="rd-body-text rd-decoration-none rd-text-blue">
              Forgot Password?
            </a>
          </div>

          <button className="rd-login-button rd-w-full rd-border-radius-md">Login</button>
        </div>

        {/* <p className="mt-4 text-center text-sm text-gray-600">
      Don't have an account?{' '}
      <a href="#" className="text-blue-600 hover:underline">
        Sign up
      </a>
    </p> */}
      </div>
    </div>
    </>
  );
};

export default Login;
