import React, { useState, useEffect, useMemo, memo } from 'react';
import { FaChevronRight, FaChevronLeft, FaSearch } from 'react-icons/fa';
import parse from 'html-react-parser';
import DOMPurify from 'dompurify';
import he from 'he';
import Logo from '../images/robodesk-logo.png';
import Footer from './Footer';
import Author1 from '../images/person1.jpeg';
import Author2 from '../images/person2.jpeg';
import '../styles/FAQ.css';
import defaultLogo from '../images/robodesk-logo.png';


const stripHtml = (html) => he.decode(html.replace(/<[^>]+>/g, '')).trim();
const wpTrimWords = (text, numWords) => {
  const words = stripHtml(text).split(/\s+/);
  return words.slice(0, numWords).join(' ') + (words.length > numWords ? '...' : '');
};

const normalizeImagePaths = (html, baseUrl = '/') => {
  return html.replace(/(<img[^>]+src=['"])(\/[^'"]+)(['"][^>]*>)/gi, (match, prefix, path, suffix) => {
    if (path.startsWith('/')) {
      return `${prefix}${baseUrl}${path}${suffix}`;
    }
    return match;
  });
};

const FAQDetail = memo(({ faq, onBack, isExpanded, setIsExpanded }) => {
  const [isLiked, setIsLiked] = useState(false);
  const [isDisliked, setIsDisliked] = useState(false);
  const [fullContent, setFullContent] = useState(null);
  const [authorNames, setAuthorNames] = useState([]);
  // const wpHomeUrl = window.robodeskData?.home_url || 'http://localhost:10135/';
    const image_urls = window.robodeskData?.imageUrls || {};
    // console.log('arpan', image_urls);

  useEffect(() => {
    const fetchFAQDetails = () => {
      try {
        // const response = await fetch(
        //   `/wp-json/wp/v2/robodesk_faq/${faq.id}?_fields=content.rendered,author_name`
        // );
        // if (!response.ok) throw new Error('Failed to fetch FAQ details');
        // const data = await response.json();
        setFullContent(faq.content);
        setAuthorNames(data.author_name ? data.author_name.split(',').map((name) => name.trim()) : ['Unknown']);
      } catch (err) {
        // console.error('Failed to fetch FAQ details:', err);
        setAuthorNames(['Unknown']);
      }
    };
    fetchFAQDetails();
  }, [faq.id]);

  const parsedContent = useMemo(() => {
    if (!fullContent) return null;
    const normalizedAnswer = normalizeImagePaths(fullContent);
    const sanitizedAnswer = DOMPurify.sanitize(normalizedAnswer, {
      ADD_TAGS: ['iframe', 'img'],
      ADD_ATTR: ['allow', 'allowfullscreen', 'frameborder', 'src', 'alt', 'title', 'width', 'height'],
      ALLOWED_URI_REGEXP: /^(?:https?:\/\/|\/|data:image\/)/,
    });
    return parse(sanitizedAnswer, {
      replace: (domNode) => {
        if (domNode.name === 'img') {
          domNode.attribs = {
            ...domNode.attribs,
            loading: 'lazy',
            onError: (e) => console.error('Image failed to load:', e.target.src),
          };
        }
        return domNode;
      },
    });
  }, [fullContent]);

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
        <div className="single-faq-header rd-flex rd-align-center rd-justify-between rd-cursor-pointer rd-border-bottom-light rd-px-20 rd-py-15">
          <button
            className="back-button rd-flex rd-items-center rd-bg-transparent rd-border-none icon-dark rd-p-0 rd-m-0"
            onClick={onBack}
          >
            <FaChevronLeft />
          </button>
          {image_urls.wide_logo && image_urls.wide_logo.trim() !== '' ? (
              <img
                src={image_urls.wide_logo}
                alt="Plugin Wide Logo"
                className="rd-h-auto rd-header-logo"
                onError={(e) => {
                  e.target.style.display = 'none';
                }}
              />
            ) : image_urls.logo && image_urls.logo.trim() !== '' ? (
              <img
                src={image_urls.logo}
                alt="Plugin Logo"
                className="rd-h-auto rd-header-logo"
                onError={(e) => {
                  e.target.style.display = 'none';
                }}
              />
            ) : (
              <div className="rd-h-auto rd-header-logo default-logo">
                <img src={defaultLogo} alt="Default Plugin Logo" className="rd-h-auto rd-header-logo" />
              </div>
            )}
          <button
            className={`expand-popup rd-bg-transparent rd-border-none rd-cursor-pointer rd-p-0 ${
              isExpanded ? 'expanded' : ''
            }`}
            onClick={() => setIsExpanded(!isExpanded)}
          >
             <svg
              className={`expand-icon ${isExpanded ? 'hidden' : 'visible'}`}
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
              className={`expand-icon ${isExpanded ? 'visible' : 'hidden'}`}
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
            {parsedContent ? <div className="rd-body-text rd-my-10">{parsedContent}</div> : <div>Loading...</div>}
          </div>
          <div className="rd-single-faq-footer rd-py-10 rd-border-vertical-light">
            <div className="rd-flex rd-justify-between rd-align-center">
              <div>
                <h5 className="sub-text">12 articles</h5>
                <h5 className="sub-text">By {authorNames.join(' and ')}</h5>
              </div>
              <div className="rd-author-avatar rd-flex rd-align-center">
                {authorNames.map((author, index) => (
                  <img key={index} src={index === 0 ? Author1 : Author2} alt={`Author ${author}`} />
                ))}
              </div>
            </div>
          </div>
          {/* <div className="rd-faq-actions rd-flex rd-align-center rd-justify-center">
            <div
              className={`rd-faq-like rd-flex rd-align-center rd-gap-10 rd-cursor-pointer ${isLiked ? 'liked' : ''}`}
              onClick={handleLike}
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 19 18" fill="none">
                <path
                  d="M4.15625 7.875H1.34375C0.877754 7.875 0.5 8.25276 0.5 8.71876V17.1563C0.5 17.6223 0.877754 18 1.34375 18H4.15625C4.62225 18 5 17.6223 5 17.1563V8.71876C5 8.25276 4.62225 7.875 4.15625 7.875ZM2.75 16.5938C2.28401 16.5938 1.90625 16.216 1.90625 15.75C1.90625 15.284 2.28401 14.9063 2.75 14.9063C3.216 14.9063 3.59375 15.284 3.59375 15.75C3.59375 16.216 3.216 16.5938 2.75 16.5938ZM14 2.86355C14 4.35474 13.087 5.19117 12.8301 6.1875H16.4063C17.5804 6.1875 18.4945 7.16295 18.5 8.23001C18.5029 8.86065 18.2347 9.53955 17.8166 9.9596L17.8127 9.96346C18.1585 10.7839 18.1023 11.9335 17.4855 12.7573C17.7907 13.6677 17.483 14.786 16.9095 15.3855C17.0606 16.0042 16.9884 16.5307 16.6934 16.9546C15.9759 17.9855 14.1974 18 12.6936 18L12.5935 18C10.896 17.9994 9.50661 17.3813 8.39026 16.8846C7.82927 16.6351 7.09577 16.3261 6.53925 16.3159C6.30933 16.3117 6.125 16.124 6.125 15.8941V8.37872C6.125 8.26622 6.17007 8.15826 6.25009 8.07916C7.64277 6.703 8.24162 5.24602 9.38311 4.1026C9.90356 3.58116 10.0928 2.79352 10.2758 2.03182C10.4322 1.3814 10.7592 0 11.4688 0C12.3125 0 14 0.28125 14 2.86355Z"
                  fill="#004AFF"
                />
              </svg>
              <p className="rd-body-text">{isLiked ? 1 : 0}</p>
            </div>
            <div
              className={`rd-faq-dislike rd-flex rd-align-center rd-gap-10 rd-cursor-pointer ${isDisliked ? 'disliked' : ''}`}
              onClick={handleDislike}
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 19 18" fill="none">
                <path
                  d="M0.5 1.96875V10.4062C0.5 10.8722 0.877753 11.25 1.34375 11.25H4.15625C4.62224 11.25 4.99999 10.8722 4.99999 10.4062V1.96875C4.99999 1.50275 4.62224 1.125 4.15625 1.125H1.34375C0.877753 1.125 0.5 1.50275 0.5 1.96875ZM1.90625 8.99999C1.90625 8.53399 2.284 8.15624 2.75 8.15624C3.21599 8.15624 3.59375 8.53399 3.59375 8.99999C3.59375 9.46598 3.21599 9.84374 2.75 9.84374C2.284 9.84374 1.90625 9.46598 1.90625 8.99999ZM11.4687 18C10.7592 18 10.4322 16.6186 10.2759 15.9681C10.0928 15.2064 9.90358 14.4188 9.38313 13.8974C8.24164 12.7539 7.64279 11.297 6.25011 9.9208C6.21048 9.88162 6.17902 9.83498 6.15755 9.78355C6.13608 9.73213 6.12502 9.67696 6.12503 9.62123V2.10589C6.12503 1.87594 6.30935 1.68831 6.53927 1.68409C7.09583 1.67389 7.8293 1.3649 8.39028 1.11533C9.50663 0.618679 10.896 0.000597656 12.5936 0H12.6935C14.1974 0 15.9758 0.0145195 16.6934 1.04544C16.9884 1.46932 17.0606 1.99585 16.9095 2.61453C17.483 3.21405 17.7907 4.33237 17.4855 5.2427C18.1023 6.06648 18.1585 7.21609 17.8127 8.03653L17.8166 8.0404C18.2347 8.46048 18.5029 9.13935 18.5 9.76998C18.4945 10.837 17.5804 11.8125 16.4063 11.8125H12.8301C13.087 12.8088 14 13.6452 14 15.1364C14 17.7187 12.3125 18 11.4687 18Z"
                  fill="#555555"
                />
              </svg>
              <p className="rd-body-text">{isDisliked ? 1 : 0}</p>
            </div>
          </div> */}
        </div>
      </div>
      <div className="rd-border-top-light">
        <Footer showCopyrightOnly={true} />
      </div>
    </div>
  );
});

const FAQItem = memo(({ faq, onSelect }) => (
  <div className="rd-faq-item rd-flex rd-align-start rd-border-bottom-light rd-gap-8" onClick={() => onSelect(faq)}>
    <div className="rd-faq-content">
      <h3 className="rd-faq-title rd-title-text">{faq.question}</h3>
      <p className="rd-faq-paragraph rd-body-text truncate-3-lines">{faq.summary}</p>
    </div>
    <FaChevronRight />
  </div>
));

const FAQ = ({ setActiveTab, setIsOpen, isExpanded, setIsExpanded, activeTab }) => {
  const [selectedFAQ, setSelectedFAQ] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [faqs, setFaqs] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  // const wpHomeUrl = window.robodeskData?.home_url || 'http://localhost:10135/';
 const CACHE_EXPIRY = 24 * 60 * 60 * 1000;
  useEffect(() => {
    const fetchFAQs = async () => {
      try {
        setIsLoading(true);
        setError(null);
        const cacheKey = `faqs_page_1`;
        const cacheTimestampKey = `${cacheKey}_timestamp`;

        // Check cached data and its timestamp
        const cachedFaqs = localStorage.getItem(cacheKey);
        const cachedTimestamp = localStorage.getItem(cacheTimestampKey);
        const currentTime = new Date().getTime();

        if (cachedFaqs && cachedTimestamp) {
          const isCacheValid = currentTime - parseInt(cachedTimestamp) < CACHE_EXPIRY;
          if (isCacheValid) {
            // console.log('Using cached FAQs:', JSON.parse(cachedFaqs));
            setFaqs(JSON.parse(cachedFaqs));
            setIsLoading(false);
            return;
          } else {
            // Clear expired cache
            localStorage.removeItem(cacheKey);
            localStorage.removeItem(cacheTimestampKey);
          }
        }

        const response = await fetch(
          `/wp-json/wp/v2/robodesk_faq?_fields=id,title.rendered,excerpt.rendered,content.rendered&per_page=100&page=1`
        );
        
        // console.log(wpHomeUrl);
        // console.log('Response status:', response.status, response.statusText);

        if (!response.ok) {
          throw new Error(`Failed to fetch FAQs: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();
        // console.log('API Response:', data);

        if (!Array.isArray(data)) {
          throw new Error('Invalid API response: Expected an array');
        }

        if (data.length === 0) {
        //   console.warn('API returned an empty array. Check if robodesk_faq posts exist.');
        }

        const formattedFaqs = data.map((faq) => {
          const question = stripHtml(faq.title.rendered);
          const summary = faq.excerpt?.rendered && stripHtml(faq.excerpt.rendered).trim()
            ? wpTrimWords(stripHtml(faq.excerpt.rendered), 20)
            : faq.content?.rendered
            ? wpTrimWords(stripHtml(faq.content.rendered), 20)
            : 'No summary available';
            
          const content = faq.content?.rendered
            ? faq.content.rendered
            : 'No content available';               
            
          return {
            id: faq.id,
            question,
            summary,
            content,
            author: faq.author_name,
          };
        });

        // console.log('Formatted FAQs:', formattedFaqs);
        setFaqs(formattedFaqs);
        localStorage.setItem(cacheKey, JSON.stringify(formattedFaqs));
        localStorage.setItem(cacheTimestampKey, currentTime.toString());
      } catch (err) {
        // console.error('Fetch Error:', err);
        setError(err.message);
      } finally {
        setIsLoading(false);
      }
    };

    fetchFAQs();
  }, []);

  const filteredFaqs = useMemo(() => {
    const filtered = faqs.filter((faq) =>
      faq.question.toLowerCase().includes(searchTerm.toLowerCase())
    );
    console.log('Search term:', searchTerm, 'Filtered FAQs:', filtered);
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
/>
      </div>
    );
  }

  return (
    <div className="rd-faq-container">
      <div className="rd-main-content">
        <div className="rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col">
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
            <p className="rd-body-text rd-text-red rd-text-center">Error: {error}</p>
          ) : isLoading ? (
            <div className="rd-skeleton">
              {[...Array(10)].map((_, i) => (
                <div key={i} className="rd-skeleton-item rd-mb-10 rd-p-20 rd-bg-gray-200 rd-rounded"></div>
              ))}
            </div>
          ) : filteredFaqs.length === 0 ? (
            <p className="rd-body-text rd-text-center">
              {searchTerm ? 'No FAQs match your search.' : 'No FAQs found.'}
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