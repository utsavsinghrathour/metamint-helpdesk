import { useState, useEffect, useMemo, memo } from "react";
import { FaChevronRight, FaChevronLeft, FaSearch } from "react-icons/fa";
import { RiPencilFill } from "react-icons/ri";
import parse from "html-react-parser";
import DOMPurify from "dompurify";
import he from "he";

import Footer from "./Footer";
import Author1 from "../images/person1.jpeg";
import Author2 from "../images/person2.jpeg";
import "../styles/FAQ.css";
import defaultLogo from "../images/robodesk-logo.png";

const stripHtml = (html) => he.decode(html.replace(/<[^>]+>/g, "")).trim();
const wpTrimWords = (text, numWords) => {
  const words = stripHtml(text).split(/\s+/);
  return (
    words.slice(0, numWords).join(" ") + (words.length > numWords ? "..." : "")
  );
};

const normalizeImagePaths = (html, baseUrl = "/") => {
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

const FAQDetail = memo(
  ({
    faq,
    onBack,
    isExpanded,
    setIsExpanded,
    faqs,
    setSelectedFAQ,
    setActiveTab,
    handleFocusedTextArea,
  }) => {
    const [isLiked, setIsLiked] = useState(false);
    const [isDisliked, setIsDisliked] = useState(false);
    const [fullContent, setFullContent] = useState(null);
    const [authorNames, setAuthorNames] = useState([]);
    const image_urls = window.robodeskData?.imageUrls || {};

    useEffect(() => {
      const fetchFAQDetails = () => {
        try {
          setFullContent(faq.content);
          setAuthorNames(
            faq.author
              ? faq.author.split(",").map((name) => name.trim())
              : ["Unknown"],
          );
        } catch (err) {
          setAuthorNames(["Unknown"]);
        }
      };
      fetchFAQDetails();
    }, [faq.id, faq.content, faq.author]);

    const parsedContent = useMemo(() => {
      if (!fullContent) return null;
      const normalizedAnswer = normalizeImagePaths(fullContent);
      const sanitizedAnswer = DOMPurify.sanitize(normalizedAnswer, {
        ADD_TAGS: ["iframe", "img"],
        ADD_ATTR: [
          "allow",
          "allowfullscreen",
          "frameborder",
          "src",
          "alt",
          "title",
          "width",
          "height",
        ],
        ALLOWED_URI_REGEXP: /^(?:https?:\/\/|\/|data:image\/)/,
      });
      return parse(sanitizedAnswer, {
        replace: (domNode) => {
          if (domNode.name === "img") {
            domNode.attribs = {
              ...domNode.attribs,
              loading: "lazy",
              onError: (e) =>
                console.error("Image failed to load:", e.target.src),
            };
          }
          return domNode;
        },
      });
    }, [fullContent]);

    const relatedFaqs = faqs
      .slice(0, 3)
      .filter((relatedFaq) => relatedFaq.id !== faq.id);

    const handleLike = () => {
      setIsLiked(!isLiked);
      if (isDisliked) setIsDisliked(false);
    };

    const handleDislike = () => {
      setIsDisliked(!isDisliked);
      if (isLiked) setIsLiked(false);
    };

    return (
      <div className="rd-faq-detail">
        <div className="rd-main-content no-footer">
          <div className="single-faq-header rd-flex rd-align-center rd-justify-between rd-cursor-pointer rd-border-bottom-light rd-p-10 rd-bg-primary ">
            <button
              className="back-button rd-flex rd-items-center rd-bg-transparent rd-border-none icon-dark rd-p-0 rd-m-0"
              onClick={onBack}
            >
              <FaChevronLeft />
            </button>
            {image_urls.wide_logo && image_urls.wide_logo.trim() !== "" ? (
              <img
                src={image_urls.wide_logo}
                alt="Plugin Wide Logo"
                className="rd-header-logo"
                onError={(e) => {
                  e.target.style.display = "none";
                }}
              />
            ) : image_urls.logo && image_urls.logo.trim() !== "" ? (
              <img
                src={image_urls.logo}
                alt="Plugin Logo"
                className="rd-header-logo"
                onError={(e) => {
                  e.target.style.display = "none";
                }}
              />
            ) : (
              <div className="rd-header-logo default-logo">
                <img
                  src={defaultLogo}
                  alt="Default Plugin Logo"
                  className="rd-header-logo"
                />
              </div>
            )}
            <button
              className={`expand-popup rd-bg-transparent rd-border-none rd-cursor-pointer rd-p-0 ${
                isExpanded ? "expanded" : ""
              }`}
              onClick={() => setIsExpanded(!isExpanded)}
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
          <div className="rd-px-20 rd-py-15 single-faq-content">
            <div>
              <h4 className="rd-faq-title heading-4">{faq.question}</h4>
              {parsedContent ? (
                <div className="rd-body-text rd-my-10">{parsedContent}</div>
              ) : (
                <div>Loading...</div>
              )}
            </div>
            <div className="rd-single-faq-footer rd-py-10 rd-border-vertical-light">
              <div className="rd-flex rd-justify-between rd-align-center">
                <div>
                  <h5 className="sub-text">
                    {faq?.author?.total_posts} articles
                  </h5>
                  {/* <h5 className="sub-text">By {faq?.author?.display_name}</h5> */}
                  <span className="rd-text-small">By {faq?.author?.display_name}</span>
                </div>
                <div className="rd-author-avatar rd-flex rd-align-center">
                  <img
                    src={faq?.author?.gravatar_url}
                    alt={faq?.author?.display_name}
                  />
                </div>
              </div>
            </div>
            {relatedFaqs.length > 0 && (
              <div className="single-faq-other-faq-content rd-my-20 single-content-other-content">
                <h4 className="heading-4">Didn't get your answer?</h4>
                <div className="rd-body-text rd-mt-8">
                  <p>Here are some related articles.</p>
                </div>
                <div className="rd-flex rd-flex-col rd-gap-8 rd-mt-20">
                  {relatedFaqs.map((relatedFaq) => (
                    <FAQItem
                      key={relatedFaq.id}
                      faq={relatedFaq}
                      onSelect={setSelectedFAQ}
                    />
                  ))}
                </div>
                <div className="rd-my-30">
                  <h4 className="heading-4 rd-fw-semi rd-mb-8">
                    Still have questions?
                  </h4>
                  <button
                    type="button"
                    className="button no-bg rd-flex rd-align-center rd-gap-10 rd-cursor-pointer"
                    onClick={() => {
                      setActiveTab(0);
                      handleFocusedTextArea(true);
                    }}
                  >
                    Start a conversation
                    <RiPencilFill />
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
        <div className="rd-border-top-light">
          <Footer showCopyrightOnly={true} />
        </div>
      </div>
    );
  },
);

const FAQItem = memo(({ faq, onSelect }) => (
  <div
    className="rd-faq-item rd-flex rd-align-center rd-border-bottom-light rd-gap-8"
    onClick={() => onSelect(faq)}
  >
    <div className="rd-faq-content">
      <h3 className="rd-faq-title rd-title-text">{faq.question}</h3>
      <p className="rd-faq-paragraph rd-body-text truncate-3-lines">
        {faq.summary}
      </p>
    </div>
    <FaChevronRight />
  </div>
));

const FAQ = ({
  setActiveTab,
  setIsOpen,
  isExpanded,
  setIsExpanded,
  activeTab,
  handleFocusedTextArea,
}) => {
  const [selectedFAQ, setSelectedFAQ] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [faqs, setFaqs] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const CACHE_EXPIRY = 24 * 60 * 60 * 1000;

  useEffect(() => {
    const fetchFAQs = async () => {
      try {
        setIsLoading(true);
        setError(null);
        const cacheKey = `faqs_page_1`;
        const cacheTimestampKey = `${cacheKey}_timestamp`;

        const cachedFaqs = false;
        const cachedTimestamp = localStorage.getItem(cacheTimestampKey);
        const currentTime = new Date().getTime();

        if (cachedFaqs && cachedTimestamp) {
          const isCacheValid =
            currentTime - parseInt(cachedTimestamp) < CACHE_EXPIRY;
          if (isCacheValid) {
            setFaqs(JSON.parse(cachedFaqs));
            setIsLoading(false);
            return;
          } else {
            localStorage.removeItem(cacheKey);
            localStorage.removeItem(cacheTimestampKey);
          }
        }

        const response = await fetch(
          `/wp-json/wp/v2/robodesk_faq?_fields=id,title.rendered,excerpt.rendered,content.rendered,author_info&per_page=100&page=1`,
        );

        if (!response.ok) {
          throw new Error(
            `Failed to fetch FAQs: ${response.status} ${response.statusText}`,
          );
        }

        const data = await response.json();

        if (!Array.isArray(data)) {
          throw new Error("Invalid API response: Expected an array");
        }

        const formattedFaqs = data.map((faq) => {
          const question = stripHtml(faq.title.rendered);
          const summary =
            faq.excerpt?.rendered && stripHtml(faq.excerpt.rendered).trim()
              ? wpTrimWords(stripHtml(faq.excerpt.rendered), 20)
              : faq.content?.rendered
              ? wpTrimWords(stripHtml(faq.content.rendered), 20)
              : "No summary available";

          const content = faq.content?.rendered
            ? faq.content.rendered
            : "No content available";

          return {
            id: faq.id,
            question,
            summary,
            content,
            author: faq.author_info,
          };
        });

        setFaqs(formattedFaqs);
        localStorage.setItem(cacheKey, JSON.stringify(formattedFaqs));
        localStorage.setItem(cacheTimestampKey, currentTime.toString());
      } catch (err) {
        setError(err.message);
      } finally {
        setIsLoading(false);
      }
    };

    fetchFAQs();
  }, []);

  const filteredFaqs = useMemo(() => {
    const filtered = faqs.filter((faq) =>
      faq.question.toLowerCase().includes(searchTerm.toLowerCase()),
    );
    return filtered;
  }, [faqs, searchTerm]);

  if (selectedFAQ) {
    return (
      <div className="rd-faq-container">
        <FAQDetail
          faq={selectedFAQ}
          onBack={() => {
            setSelectedFAQ(null);
            setIsExpanded(false);
          }}
          isExpanded={isExpanded}
          setIsExpanded={setIsExpanded}
          faqs={filteredFaqs}
          setSelectedFAQ={setSelectedFAQ}
          setActiveTab={setActiveTab}
          handleFocusedTextArea={handleFocusedTextArea}
        />
      </div>
    );
  }

  return (
    <div className="rd-faq-container">
      <div className="rd-main-content">
        <div className="rd-p-10 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col">
          <h5 className="heading-5 rd-text-white rd-text-center">Search</h5>
          <div className="rd-mt-20 rd-search-bar rd-flex rd-items-center rd-bg-white rd-rounded rd-position-relative searh-bar">
            <input
              type="text"
              className="rd-border-none rd-bg-transparent rd-w-full rd-border-radius-md"
              placeholder="Search your answer here"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
            <FaSearch className="rd-body-text" />
          </div>
          <button
            className="back-button rd-flex rd-items-center rd-gap-1 rd-border-none rd-bg-transparent rd-m-0 rd-p-0"
            onClick={() => setActiveTab(0)}
          >
            <FaChevronLeft />
          </button>
        </div>
        <div className="rd-mt-8 rd-mb-8 rd-wiki-wrap">
          {error ? (
            <p className="rd-body-text rd-text-red rd-text-center">
              Error: {error}
            </p>
          ) : isLoading ? (
            <div className="rd-skeleton">
              <p className="rd-body-text rd-text rd-text-center">Loading...</p>
              {[...Array(10)].map((_, i) => (
                <div
                  key={i}
                  className="rd-skeleton-item rd-mb-10 rd-p-20 rd-bg-gray-200 rd-rounded"
                ></div>
              ))}
            </div>
          ) : filteredFaqs.length === 0 ? (
            <p className="rd-text-center">
              {searchTerm ? "No FAQs match your search." : "No FAQs found."}
            </p>
          ) : (
            filteredFaqs.map((faq) => (
              <FAQItem key={faq.id} faq={faq} onSelect={setSelectedFAQ} />
            ))
          )}
        </div>
      </div>
      <Footer activeTab={activeTab} setActiveTab={setActiveTab} />
    </div>
  );
};

export default FAQ;
