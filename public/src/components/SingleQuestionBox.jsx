import React, { useState, useEffect, useRef } from "react";
import { FaChevronLeft } from "react-icons/fa";
import SendMessageField from "./SendMessageField";
import parse from "html-react-parser";
import DOMPurify from "dompurify";
import he from "he";
import Footer, { mmhdShowCredits } from "./Footer";
import { readChatSession } from "../utils/startConversation";
import "../styles/SingleQuestionBox.css";
import MetamintBotChatWidgetMain from "./MetamintBotChatWidgetMain";

import defaultLogo from "../images/mmhd-logo.png";

const faqCache = {};
// const wpHomeUrl = window.mmhdData?.home_url || '';

const WIDGET_CONFIG_CACHE_KEY = "mmhd_widget_config_cache";

const readWidgetConfigCache = () => {
  try {
    const raw = sessionStorage.getItem(WIDGET_CONFIG_CACHE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
};

const writeWidgetConfigCache = (data) => {
  try {
    sessionStorage.setItem(WIDGET_CONFIG_CACHE_KEY, JSON.stringify(data));
  } catch {
    // ignore storage failures
  }
};

const decodeTitle = (title = "") => he.decode(String(title)).trim();

const normalizeImagePaths = (html, baseUrl = wpHomeUrl) => {
  return html.replace(
    /(<img[^>]+src=['"])(\/[^'"]+)(['"][^>]*>)/gi,
    (match, prefix, path, suffix) => {
      if (path.startsWith("/")) {
        return `${prefix}${baseUrl}${path}${suffix}`;
      }
      return match;
    },
  );
};

const SingleQuestionBox = ({
  setShowHeaderFooter,
  showHeaderFooter,
  isExpanded,
  setIsExpanded,
  setShowLogin,
  setActiveTab,
  updateSelectedTicket,
  handleFocusedTextArea,
  focused,
}) => {
  const [selectedQuestion, setSelectedQuestion] = useState(null);
  const [isLiked, setIsLiked] = useState(false);
  const [isDisliked, setIsDisliked] = useState(false);
  const [questions, setQuestions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [hasChatStarted, setHasChatStarted] = useState(() => {
    try {
      return readChatSession().length > 0;
    } catch {
      return false;
    }
  });
  const image_urls = window.mmhdData?.imageUrls || {};
  const chatEnabled = window.mmhdData?.enable_chat === "1";
  const canLoadWidgetConfig = Boolean(
    window.mmhdData?.mmhd_addon_active &&
      window.mmhdData?.mmhd_ai_bot_active,
  );
  const [isAddonActive, setIsAddonActive] = useState(false);
  const [loadingAddon, setLoadingAddon] = useState(true);
  const [cachedWidgetConfig] = useState(readWidgetConfigCache);
  const [loadingWidgetConfig, setLoadingWidgetConfig] = useState(
    () => !cachedWidgetConfig,
  );
  const loadingAddonStatus = loadingAddon || loadingWidgetConfig;
  const [primaryBot, setPrimaryBot] = useState(
    () => Boolean(cachedWidgetConfig?.primaryBot),
  );
  const [widgetConfig, setWidgetConfig] = useState(
    () => cachedWidgetConfig?.widgetConfig || null,
  );
  const [activeAIMode, setActiveAIMode] = useState(
    () => cachedWidgetConfig?.enabled || "",
  );
  const contentRef = useRef(null);

  const shouldShowBotWidget = isAddonActive && activeAIMode && primaryBot;

  useEffect(() => {
    const fetchFAQs = async () => {
      const cacheKey = "featured_faqs";
      if (faqCache[cacheKey]) {
        setQuestions(faqCache[cacheKey]);
        setIsLoading(false);
        return;
      }

      try {
        setIsLoading(true);
        const response = await fetch(
          `/wp-json/wp/v2/mmhd_faq?featured=true&_fields=id,title.rendered,excerpt.rendered,content.rendered`,
        );
        if (!response.ok) {
          throw new Error("Failed to fetch FAQs");
        }
        const data = await response.json();
        const formattedQuestions = data.map((faq) => ({
          id: faq.id,
          title: decodeTitle(faq.title?.rendered),
          content: faq?.content?.rendered,
          excerpt: DOMPurify.sanitize(faq.excerpt.rendered, {
            ADD_TAGS: ["p", "strong", "em"],
            ADD_ATTR: [],
          }),
        }));

        faqCache[cacheKey] = formattedQuestions;
        setQuestions(formattedQuestions);
        setIsLoading(false);
      } catch (err) {
        setError(err.message);
        setIsLoading(false);
      }
    };

    fetchFAQs();
  }, []);

  useEffect(() => {
    setShowHeaderFooter(!selectedQuestion);
  }, [selectedQuestion, setShowHeaderFooter]);

  useEffect(() => {
    contentRef.current?.scrollTo?.({ top: 0, behavior: "auto" });
    if (contentRef.current) {
      contentRef.current.scrollTop = 0;
    }
  }, [selectedQuestion?.id]);

  const handleLike = () => {
    setIsLiked(!isLiked);
    if (isDisliked) setIsDisliked(false);
  };

  useEffect(() => {
    setIsAddonActive(canLoadWidgetConfig);
    setLoadingAddon(false);

    if (canLoadWidgetConfig) {
      fetchApiKey();
    } else {
      setPrimaryBot(false);
      setWidgetConfig(null);
      setActiveAIMode("");
      setLoadingWidgetConfig(false);
    }
  }, []);

  const handleDislike = () => {
    setIsDisliked(!isDisliked);
    if (isLiked) setIsLiked(false);
  };

  const handleExpandClick = () => {
    setIsExpanded(!isExpanded);
  };

  const handleBackClick = () => {
    setSelectedQuestion(null);
    setIsLiked(false);
    setIsDisliked(false);
    setIsExpanded(false);
  };

  const handleImageError = (e) => {
    console.error("Image failed to load:", e.target.src);
  };

  const getRestNonce = () =>
    window.MetamintBotConfig?.restNonce ||
    window.wpApiSettings?.nonce ||
    "";

  const fetchApiKey = async (attempt = 0) => {
    if (!canLoadWidgetConfig) {
      setPrimaryBot(false);
      setWidgetConfig(null);
      setActiveAIMode("");
      setLoadingWidgetConfig(false);
      return;
    }
    try {
      const headers = { "Content-Type": "application/json" };
      const nonce = getRestNonce();
      if (nonce) headers["X-WP-Nonce"] = nonce;
      const response = await fetch("/wp-json/mmhd-bot/v1/widget-config", { headers });
      const data = await response.json();
      if (response.ok && data) {
        writeWidgetConfigCache(data);
        setPrimaryBot(!!data?.primaryBot);
        setWidgetConfig(data?.widgetConfig || { enabled: true });
        setActiveAIMode(data?.enabled);
        setLoadingWidgetConfig(false);
      } else if (attempt < 2) {
        setTimeout(() => fetchApiKey(attempt + 1), 500 * (attempt + 1));
      } else if (!cachedWidgetConfig) {
        setPrimaryBot(false);
        setLoadingWidgetConfig(false);
      } else {
        setLoadingWidgetConfig(false);
      }
    } catch (error) {
      // A transient network hiccup must not permanently switch to the
      // normal chat - retry before giving up.
      if (attempt < 2) {
        setTimeout(() => fetchApiKey(attempt + 1), 500 * (attempt + 1));
      } else if (cachedWidgetConfig) {
        // AI enablement is already known for this session: keep it even
        // if the background refresh fails.
        setLoadingWidgetConfig(false);
      } else {
        console.error("Error fetching widget config:", error);
        setPrimaryBot(false);
        setLoadingWidgetConfig(false);
      }
    }
  };

  const QuestionItem = ({ question }) => (
    <div
      className="mmhd-faq-item mmhd-flex mmhd-gap-15 mmhd-border-light single-question-box-field mmhd-border-radius-md"
      onClick={() => setSelectedQuestion(question)}
      style={{ cursor: "pointer" }}
    >
      <div className="mmhd-text-blue">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="20"
          height="20"
          viewBox="0 0 18 16"
          fill="none"
        >
          <path
            d="M3.62812 0.335956C2.32383 0.511737 1.19883 1.42228 0.741797 2.67736C0.544922 3.22228 0.527344 3.43322 0.527344 5.45471C0.527344 6.57971 0.541406 7.39885 0.566016 7.56408C0.734766 8.784 1.58555 9.89494 2.70703 10.359C2.8125 10.4012 2.91094 10.4469 2.925 10.4574C2.93906 10.468 2.80898 11.0164 2.63672 11.6738C2.46445 12.3313 2.32031 12.9149 2.32031 12.9676C2.32031 13.1258 2.4082 13.291 2.55234 13.4C2.66133 13.4844 2.71758 13.502 2.8793 13.502C3.06562 13.502 3.10781 13.4844 3.67383 13.1469C4.8375 12.4543 6.14531 11.6914 6.15937 11.6914C6.16992 11.6914 6.26836 11.811 6.38086 11.9551C6.80977 12.5176 7.42148 12.9219 8.12812 13.1047C8.3918 13.1715 8.54297 13.182 9.70312 13.1996L10.9863 13.2207L13.0711 14.4477C14.5266 15.2985 15.2016 15.6781 15.293 15.6887C15.6023 15.7309 15.9082 15.4567 15.9012 15.1403C15.9012 15.0524 15.7852 14.5567 15.648 14.0363C15.5109 13.5195 15.3984 13.0766 15.3984 13.0485C15.3984 13.0238 15.5461 12.9324 15.7254 12.8445C16.4953 12.4578 17.0789 11.7512 17.3566 10.8617L17.4551 10.5488V8.65041C17.4551 6.78361 17.4551 6.74846 17.3742 6.46017C17.0684 5.34221 16.2562 4.50549 15.1629 4.18205C14.9238 4.11174 14.748 4.08713 14.3754 4.07306L13.8973 4.05197L13.8762 3.69689C13.7848 2.12541 12.7266 0.835175 11.1832 0.4133L10.8457 0.318377L7.34766 0.314862C5.42461 0.311346 3.75117 0.321893 3.62812 0.335956ZM10.9336 1.50666C11.0391 1.5383 11.2605 1.62971 11.4258 1.71056C11.6648 1.8301 11.7844 1.91799 12.027 2.16057C12.2766 2.41017 12.3574 2.51916 12.4945 2.79689C12.7617 3.34181 12.7652 3.40158 12.7547 5.61642L12.7441 7.52541L12.6492 7.80666C12.3715 8.62931 11.7281 9.234 10.9125 9.44142C10.6629 9.50471 10.5152 9.51174 9.1793 9.51174C8.07187 9.51525 7.68867 9.5258 7.59375 9.56096C7.52695 9.58556 6.64805 10.0883 5.64258 10.6824C4.63711 11.2731 3.81094 11.7442 3.80391 11.7266C3.79688 11.709 3.85664 11.4524 3.93398 11.157C4.2293 10.0285 4.21875 10.0742 4.18359 9.92306C4.13086 9.684 3.96562 9.55392 3.61758 9.47306C2.73164 9.26564 2.07773 8.67502 1.76836 7.80666L1.66992 7.52541L1.65937 5.61642C1.64531 3.40158 1.65234 3.34181 1.91953 2.79689C2.05664 2.51564 2.13398 2.41369 2.39062 2.15705C2.64727 1.90392 2.74922 1.8301 3.02695 1.6965C3.20977 1.60861 3.44531 1.52072 3.55078 1.49963C3.65625 1.47853 3.77578 1.45392 3.81445 1.44689C3.85312 1.43986 5.42812 1.43635 7.3125 1.43986C10.4098 1.44689 10.7578 1.45041 10.9336 1.50666ZM14.7551 5.24025C15.4301 5.39846 15.9645 5.88361 16.2387 6.58674L16.3301 6.82228V8.59767V10.3731L16.2422 10.6367C16.0172 11.3293 15.423 11.8672 14.6953 12.0289C14.3297 12.1133 14.1328 12.3067 14.1328 12.5844C14.1328 12.6512 14.2066 12.9781 14.2945 13.3086C14.3824 13.6426 14.4492 13.9203 14.4422 13.9238C14.4316 13.9379 13.5703 13.4387 12.041 12.5317L11.3027 12.0957L9.91406 12.0746C8.38125 12.0535 8.37422 12.0535 7.90664 11.8074C7.56562 11.6317 7.11211 11.1746 7.19297 11.0938C7.21406 11.0727 7.37578 10.9672 7.55859 10.8582L7.89258 10.6543L9.4043 10.6332C10.807 10.6156 10.9371 10.6086 11.1867 10.5418C12.3961 10.2043 13.3031 9.35705 13.6969 8.20041C13.8586 7.7258 13.8867 7.44455 13.8867 6.27385V5.18752H14.2137C14.3895 5.18752 14.6355 5.21213 14.7551 5.24025Z"
            fill="#004AFF"
          />
        </svg>
      </div>
      <div>
        <h4 className="mmhd-title-text">{question.title}</h4>
        <div className="mmhd-body-text truncate-3-lines">
          {parse(question.excerpt)}
        </div>
      </div>
    </div>
  );

  return (
    <>
      {selectedQuestion ? (
        <div className={`mmhd-single-question-view${mmhdShowCredits() ? "" : " mmhd-no-credits"}`}>
          <div className="mmhd-faq-header mmhd-flex mmhd-align-center mmhd-justify-between mmhd-cursor-pointer mmhd-border-bottom-light mmhd-py-15 mmhd-px-20">
            <button
              className="mmhd-back-button mmhd-flex mmhd-items-center mmhd-bg-transparent mmhd-border-none icon-dark mmhd-text-blue mmhd-p-0 mmhd-m-0 mmhd-cursor-pointer"
              onClick={handleBackClick}
            >
              <FaChevronLeft />
            </button>
            {image_urls.wide_logo && image_urls.wide_logo.trim() !== "" ? (
              <img
                src={image_urls.wide_logo}
                alt="Plugin Wide Logo"
                className=" mmhd-header-logo"
                onError={(e) => {
                  e.target.style.display = "none";
                }}
              />
            ) : image_urls.logo && image_urls.logo.trim() !== "" ? (
              <img
                src={image_urls.logo}
                alt="Plugin Logo"
                className=" mmhd-header-logo"
                onError={(e) => {
                  e.target.style.display = "none";
                }}
              />
            ) : (
              <div className=" mmhd-header-logo default-logo">
                <img
                  src={defaultLogo}
                  alt="Default Plugin Logo"
                  className=" mmhd-header-logo"
                />
              </div>
            )}
            <button
              className={`expand-popup mmhd-bg-transparent mmhd-border-none mmhd-cursor-pointer mmhd-p-0 ${
                isExpanded ? "expanded" : ""
              }`}
              onClick={handleExpandClick}
            >
              <svg
                className={`expand-icon ${isExpanded ? "hidden" : "visible"}`}
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="23"
                viewBox="0 0 18 23"
                fill="none"
              >
                <path
                  d="M14.6715 16.861L10.1838 11.5447C10.0853 11.4281 9.92526 11.4276 9.82631 11.5438L8.9902 12.5253C8.89125 12.6414 8.89089 12.8302 8.98938 12.9469L13.477 18.2631L12.7894 19.0704C12.4701 19.4451 12.6944 20.088 13.1449 20.0892L15.7089 20.0961C15.9881 20.0968 16.215 19.8305 16.2157 19.5012L16.2215 16.4776C16.2225 15.9465 15.6784 15.679 15.3592 16.0537L14.6715 16.861Z"
                  fill="black"
                />
                <path
                  d="M3.115 5.65258L7.60267 10.9688C7.70116 11.0855 7.86124 11.0859 7.96019 10.9698L8.7963 9.98827C8.89525 9.87212 8.89561 9.68335 8.79712 9.56667L4.30945 4.25044L4.99714 3.44317C5.31639 3.06841 5.09205 2.42558 4.64159 2.42435L2.07761 2.41746C1.79835 2.41671 1.57147 2.68305 1.57084 3.01234L1.565 6.0359C1.56397 6.56709 2.10808 6.83459 2.42733 6.45983L3.115 5.65258Z"
                  fill="black"
                />
              </svg>
              <svg
                className={`expand-icon ${isExpanded ? "visible" : "hidden"}`}
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="22"
                viewBox="0 0 18 22"
                fill="none"
              >
                <path
                  d="M11.017 14.7143L15.5046 20.0306C15.6031 20.1473 15.7632 20.1477 15.8622 20.0315L16.6983 19.05C16.7972 18.9339 16.7976 18.7451 16.6991 18.6284L12.2114 13.3122L12.8991 12.5049C13.2184 12.1302 12.994 11.4873 12.5436 11.4861L9.97958 11.4792C9.70033 11.4785 9.47344 11.7448 9.47282 12.0741L9.46698 15.0977C9.46595 15.6289 10.0101 15.8964 10.3293 15.5216L11.017 14.7143Z"
                  fill="#333333"
                />
                <path
                  d="M7.19652 7.03602L2.70886 1.71978C2.61036 1.6031 2.45028 1.60267 2.35134 1.71882L1.51522 2.70032C1.41628 2.81647 1.41591 3.00525 1.51441 3.12193L6.00207 8.43816L5.31438 9.24543C4.99513 9.62019 5.21947 10.263 5.66993 10.2642L8.23392 10.2711C8.51317 10.2719 8.74006 10.0055 8.74068 9.67626L8.74652 6.65269C8.74755 6.12151 8.20344 5.85401 7.88419 6.22877L7.19652 7.03602Z"
                  fill="#333333"
                />
              </svg>
            </button>
          </div>

          <div
            ref={contentRef}
            className="mmhd-py-15 single-faq-content mmhd-px-20"
          >
            <div className="mmhd-flex mmhd-gap-15">
              <div>
                <h4 className="mmhd-faq-title heading-4">
                  {selectedQuestion.title}
                </h4>
                <div className="mmhd-body-text mmhd-my-10">
                  {selectedQuestion.content
                    ? parse(selectedQuestion.content, {
                        replace: (domNode) => {
                          if (domNode.name === "img") {
                            domNode.attribs = {
                              ...domNode.attribs,
                              onError: handleImageError,
                            };
                          }
                          return domNode;
                        },
                      })
                    : selectedQuestion.excerpt
                    ? parse(selectedQuestion.excerpt)
                    : null}
                </div>
              </div>
            </div>
            <div className="single-faq-other-faq-content mmhd-my-20 single-content-other-content">
              <h4 className="heading-4">Didn't get your answer?</h4>
              <div className="mmhd-body-text mmhd-mt-8">
                <p>Here are some related articles.</p>
              </div>
              <div className="mmhd-flex mmhd-flex-col mmhd-gap-20 mmhd-mt-20">
                {questions
                  .slice(0, 3)
                  .map(
                    (question) =>
                      question.id !== selectedQuestion.id && (
                        <QuestionItem key={question.id} question={question} />
                      ),
                  )}
              </div>
              {chatEnabled && (
                <div className="mmhd-my-30">
                  <h4 className="heading-4 mmhd-fw-semi mmhd-mb-8">
                    Still have questions?
                  </h4>
                  <a
                    href="#"
                    onClick={(e) => {
                      e.preventDefault();
                      handleBackClick();
                      handleFocusedTextArea(true);
                    }}
                  >
                    Start a conversation
                  </a>
                </div>
              )}
            </div>
          </div>
          <Footer showCopyrightOnly={true} />
        </div>
      ) : (
        <>
          {showHeaderFooter && (
            <>
              {/* <div className='mmhd-text-center mmhd-px-20 mmhd-flex mmhd-flex-col mmhd-gap-5 mmhd-mb-20 home-tab-title'>
                <h4 className='heading-4'>Have Questions?</h4>
                <h5 className='sub-text mmhd-mt-0'>
                  Have any questions or need a quote? Leave us a message.
                </h5>
              </div> */}
              {chatEnabled && loadingAddonStatus ? (
                <div className="mmhd-loader-container">
                  <div className="mmhd-loader"></div>
                </div>
              ) : null}
              {chatEnabled && shouldShowBotWidget ? (
                <>
                  <MetamintBotChatWidgetMain
                    widgetConfig={widgetConfig}
                    activeAIMode={activeAIMode}
                    onChatStart={() => setHasChatStarted(true)}
                  />
                  {!hasChatStarted && (
                    <div className="mmhd-px-18 mmhd-mt-20 mmhd-mb-15 mmhd-single-message-faq-field">
                      <div className="mmhd-flex mmhd-flex-col mmhd-gap-20">
                        {isLoading ? (
                          <p className="mmhd-body-text mmhd-text-center">
                            Loading FAQs...
                          </p>
                        ) : error ? (
                          <p className="mmhd-text-red mmhd-body-text">
                            Error: {error}
                          </p>
                        ) : questions.length === 0 ? (
<p className="err_msg single-question-box-field">No FAQs found.</p>
                        ) : (
                          questions.map((question) => (
                            <QuestionItem key={question.id} question={question} />
                          ))
                        )}
                      </div>
                    </div>
                  )}
                </>
              ) : chatEnabled && !loadingAddonStatus ? (
                <>
                  <SendMessageField
                    primaryBot={primaryBot}
                    setShowLogin={setShowLogin}
                    setActiveTab={setActiveTab}
                    updateSelectedTicket={updateSelectedTicket}
                    focused={focused}
                    handleFocusedTextArea={handleFocusedTextArea}
                  />
                  <div className="mmhd-px-18 mmhd-mt-20 mmhd-mb-15 mmhd-single-message-faq-field">
                    <div className="mmhd-flex mmhd-flex-col mmhd-gap-20">
                      {isLoading ? (
                        <p className="mmhd-body-text mmhd-text-center">
                          Loading FAQs...
                        </p>
                      ) : error ? (
                        <p className="mmhd-text-red mmhd-body-text">
                          Error: {error}
                        </p>
                      ) : questions.length === 0 ? (
                        <p className="err_msg">No FAQs found.</p>
                      ) : (
                        questions.map((question) => (
                          <QuestionItem key={question.id} question={question} />
                        ))
                      )}
                    </div>
                  </div>
                </>
              ) : (
                <div className="mmhd-px-18 mmhd-mt-20 mmhd-mb-15 mmhd-single-message-faq-field">
                  <div className="mmhd-flex mmhd-flex-col mmhd-gap-20">
                    {isLoading ? (
                      <p className="mmhd-body-text mmhd-text-center">
                        Loading FAQs...
                      </p>
                    ) : error ? (
                      <p className="mmhd-text-red mmhd-body-text">
                        Error: {error}
                      </p>
                    ) : questions.length === 0 ? (
                      <p className="err_msg">No FAQs found.</p>
                    ) : (
                      questions.map((question) => (
                        <QuestionItem key={question.id} question={question} />
                      ))
                    )}
                  </div>
                </div>
              )}
            </>
          )}
        </>
      )}
    </>
  );
};

export default SingleQuestionBox;
