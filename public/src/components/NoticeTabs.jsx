import React, { useState, useEffect, useMemo, memo } from 'react';
import { FaChevronLeft } from 'react-icons/fa';
import Header from './Header.jsx';
import Footer from './Footer.jsx';
import parse from 'html-react-parser';
import DOMPurify from 'dompurify';
import '../styles/NoticeTabs.css';
import defaultLogo from '../images/robodesk-logo.png';

import { useAppData } from '../utils/AppDataContext';

// const wpHomeUrl = window.robodeskData?.home_url || '';

const normalizeImagePaths = (html) => {
  return html.replace(
    /(<img[^>]+src=['"])(\/[^'"]+)(['"][^>]*>)/gi,
    (match, prefix, path, suffix) => {
      if (path.startsWith('/')) {
        return `${prefix}${baseUrl}${path}${suffix}`;
      }
      return match;
    }
  );
};

// Error Boundary Component
class ErrorBoundary extends React.Component {
  state = { hasError: false };
  static getDerivedStateFromError() {
    return { hasError: true };
  }
  render() {
    if (this.state.hasError) {
      return (
        <p className='rd-body-text rd-text-red rd-text-center'>
          Something went wrong.
        </p>
      );
    }
    return this.props.children;
  }
}

const NoticeItem = memo(({ notice, onSelect }) => {
  const parsedExcerpt = useMemo(() => {
    return parse(notice.excerpt);
  }, [notice.excerpt]);

  // console.log(notice);
  return (
    <div
      className='rd-notice-item rd-flex rd-gap-15 rd-border-light single-notice-box-field rd-border-radius-md rd-faq-item'
      onClick={() => onSelect(notice)}
      style={{ cursor: 'pointer' }}
    >
      <div className='rd-text-blue'>
        <svg
          xmlns='http://www.w3.org/2000/svg'
          width='20'
          height='20'
          viewBox='0 0 18 16'
          fill='none'
        >
          <path
            d='M3.62812 0.335956C2.32383 0.511737 1.19883 1.42228 0.741797 2.67736C0.544922 3.22228 0.527344 3.43322 0.527344 5.45471C0.527344 6.57971 0.541406 7.39885 0.566016 7.56408C0.734766 8.784 1.58555 9.89494 2.70703 10.359C2.8125 10.4012 2.91094 10.4469 2.925 10.4574C2.93906 10.468 2.80898 11.0164 2.63672 11.6738C2.46445 12.3313 2.32031 12.9149 2.32031 12.9676C2.32031 13.1258 2.4082 13.291 2.55234 13.4C2.66133 13.4844 2.71758 13.502 2.8793 13.502C3.06562 13.502 3.10781 13.4844 3.67383 13.1469C4.8375 12.4543 6.14531 11.6914 6.15937 11.6914C6.16992 11.6914 6.26836 11.811 6.38086 11.9551C6.80977 12.5176 7.42148 12.9219 8.12812 13.1047C8.3918 13.1715 8.54297 13.182 9.70312 13.1996L10.9863 13.2207L13.0711 14.4477C14.5266 15.2985 15.2016 15.6781 15.293 15.6887C15.6023 15.7309 15.9082 15.4567 15.9012 15.1403C15.9012 15.0524 15.7852 14.5567 15.648 14.0363C15.5109 13.5195 15.3984 13.0766 15.3984 13.0485C15.3984 13.0238 15.5461 12.9324 15.7254 12.8445C16.4953 12.4578 17.0789 11.7512 17.3566 10.8617L17.4551 10.5488V8.65041C17.4551 6.78361 17.4551 6.74846 17.3742 6.46017C17.0684 5.34221 16.2562 4.50549 15.1629 4.18205C14.9238 4.11174 14.748 4.08713 14.3754 4.07306L13.8973 4.05197L13.8762 3.69689C13.7848 2.12541 12.7266 0.835175 11.1832 0.4133L10.8457 0.318377L7.34766 0.314862C5.42461 0.311346 3.75117 0.321893 3.62812 0.335956ZM10.9336 1.50666C11.0391 1.5383 11.2605 1.62971 11.4258 1.71056C11.6648 1.8301 11.7844 1.91799 12.027 2.16057C12.2766 2.41017 12.3574 2.51916 12.4945 2.79689C12.7617 3.34181 12.7652 3.40158 12.7547 5.61642L12.7441 7.52541L12.6492 7.80666C12.3715 8.62931 11.7281 9.234 10.9125 9.44142C10.6629 9.50471 10.5152 9.51174 9.1793 9.51174C8.07187 9.51525 7.68867 9.5258 7.59375 9.56096C7.52695 9.58556 6.64805 10.0883 5.64258 10.6824C4.63711 11.2731 3.81094 11.7442 3.80391 11.7266C3.79688 11.709 3.85664 11.4524 3.93398 11.157C4.2293 10.0285 4.21875 10.0742 4.18359 9.92306C4.13086 9.684 3.96562 9.55392 3.61758 9.47306C2.73164 9.26564 2.07773 8.67502 1.76836 7.80666L1.66992 7.52541L1.65937 5.61642C1.64531 3.40158 1.65234 3.34181 1.91953 2.79689C2.05664 2.51564 2.13398 2.41369 2.39062 2.15705C2.64727 1.90392 2.74922 1.8301 3.02695 1.6965C3.20977 1.60861 3.44531 1.52072 3.55078 1.49963C3.65625 1.47853 3.77578 1.45392 3.81445 1.44689C3.85312 1.43986 5.42812 1.43635 7.3125 1.43986C10.4098 1.44689 10.7578 1.45041 10.9336 1.50666ZM14.7551 5.24025C15.4301 5.39846 15.9645 5.88361 16.2387 6.58674L16.3301 6.82228V8.59767V10.3731L16.2422 10.6367C16.0172 11.3293 15.423 11.8672 14.6953 12.0289C14.3297 12.1133 14.1328 12.3067 14.1328 12.5844C14.1328 12.6512 14.2066 12.9781 14.2945 13.3086C14.3824 13.6426 14.4492 13.9203 14.4422 13.9238C14.4316 13.9379 13.5703 13.4387 12.041 12.5317L11.3027 12.0957L9.91406 12.0746C8.38125 12.0535 8.37422 12.0535 7.90664 11.8074C7.56562 11.6317 7.11211 11.1746 7.19297 11.0938C7.21406 11.0727 7.37578 10.9672 7.55859 10.8582L7.89258 10.6543L9.4043 10.6332C10.807 10.6156 10.9371 10.6086 11.1867 10.5418C12.3961 10.2043 13.3031 9.35705 13.6969 8.20041C13.8586 7.7258 13.8867 7.44455 13.8867 6.27385V5.18752H14.2137C14.3895 5.18752 14.6355 5.21213 14.7551 5.24025Z'
            fill='#004AFF'
          />
        </svg>
      </div>
      <div>
        <h4 className='rd-title-text'>{notice.title}</h4>
        <div className='rd-body-text truncate-3-lines'>{parsedExcerpt}</div>
      </div>
    </div>
  );
});

const SingleNoticeView = memo(
  ({ notice, onBack, isExpanded, setIsExpanded, logo }) => {
    const [content, setContent] = useState(null);

    useEffect(() => {
      const fetchContent = () => {
        try {
          const normalizedContent = normalizeImagePaths(notice.content);
          const sanitizedContent = DOMPurify.sanitize(normalizedContent, {
            ADD_TAGS: ['iframe', 'img'],
            ADD_ATTR: [
              'allow',
              'allowfullscreen',
              'frameborder',
              'src',
              'alt',
              'title',
              'width',
              'height',
            ],
            ALLOWED_URI_REGEXP: /^(?:https?:\/\/|\/|data:image\/)/,
          });
          setContent(sanitizedContent);
          // localStorage.setItem(cacheKey, sanitizedContent);
        } catch (err) {
          // console.error('Failed to fetch content:', err);
          setContent('Error loading content');
        }
      };

      if (notice.content?.rendered) {
        const normalizedContent = normalizeImagePaths(notice.content.rendered);
        const sanitizedContent = DOMPurify.sanitize(normalizedContent, {
          ADD_TAGS: ['iframe', 'img'],
          ADD_ATTR: [
            'allow',
            'allowfullscreen',
            'frameborder',
            'src',
            'alt',
            'title',
            'width',
            'height',
          ],
          ALLOWED_URI_REGEXP: /^(?:https?:\/\/|\/|data:image\/)/,
        });
        setContent(sanitizedContent);
      } else {
        fetchContent();
      }
    }, [notice.id, notice.content]);

    const parsedContent = useMemo(() => {
      if (!content) return 'Loading content...';
      try {
        return parse(content, {
          replace: (domNode) => {
            if (domNode.name === 'img') {
              domNode.attribs = {
                ...domNode.attribs,
                loading: 'lazy',
                onError: (e) =>
                  console.error('Image failed to load:', e.target.src),
              };
            }
            return domNode;
          },
        });
      } catch (error) {
        console.error('Error parsing content:', error);
        return 'Error loading content';
      }
    }, [content]);

    const title =
      typeof notice.title === 'string'
        ? notice.title
        : notice.title?.rendered || '';

    return (
      <div className='rd-single-notice-view rd-main-content no-footer'>
        <div className='rd-px-20 rd-notice-header rd-flex rd-align-center rd-justify-between rd-cursor-pointer rd-border-bottom-light rd-py-15'>
          <button
            className='rd-back-button rd-flex rd-items-center rd-bg-transparent rd-border-none icon-dark rd-text-blue rd-p-0 rd-m-0 rd-cursor-pointer'
            onClick={onBack}
          >
            <FaChevronLeft />
          </button>
          {logo && logo.trim() !== '' ? (
            <img
              src={logo}
              alt='Plugin Logo'
              className='rd-h-auto rd-header-logo'
              onError={(e) => {
                e.target.style.display = 'none';
              }}
            />
          ) : (
            <div className='rd-h-auto rd-header-logo'>
              <img
                src={defaultLogo}
                alt='Default Plugin Logo'
                className='rd-h-auto rd-header-logo'
              />
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
              xmlns='http://www.w3.org/2000/svg'
              width='18'
              height='23'
              viewBox='0 0 18 23'
              fill='none'
            >
              <path
                d='M14.6715 16.861L10.1838 11.5447C10.0853 11.4281 9.92526 11.4276 9.82631 11.5438L8.9902 12.5253C8.89125 12.6414 8.89089 12.8302 8.98938 12.9469L13.477 18.2631L12.7894 19.0704C12.4701 19.4451 12.6944 20.088 13.1449 20.0892L15.7089 20.0961C15.9881 20.0968 16.215 19.8305 16.2157 19.5012L16.2215 16.4776C16.2225 15.9465 15.6784 15.679 15.3592 16.0537L14.6715 16.861Z'
                fill='black'
              />
              <path
                d='M3.115 5.65258L7.60267 10.9688C7.70116 11.0855 7.86124 11.0859 7.96019 10.9698L8.7963 9.98827C8.89525 9.87212 8.89561 9.68335 8.79712 9.56667L4.30945 4.25044L4.99714 3.44317C5.31639 3.06841 5.09205 2.42558 4.64159 2.42435L2.07761 2.41746C1.79835 2.41671 1.57147 2.68305 1.57084 3.01234L1.565 6.0359C1.56397 6.56709 2.10808 6.83459 2.42733 6.45983L3.115 5.65258Z'
                fill='black'
              />
            </svg>
            <svg
              className={`expand-icon ${isExpanded ? 'visible' : 'hidden'}`}
              xmlns='http://www.w3.org/2000/svg'
              width='18'
              height='22'
              viewBox='0 0 18 22'
              fill='none'
            >
              <path
                d='M11.017 14.7143L15.5046 20.0306C15.6031 20.1473 15.7632 20.1477 15.8622 20.0315L16.6983 19.05C16.7972 18.9339 16.7976 18.7451 16.6991 18.6284L12.2114 13.3122L12.8991 12.5049C13.2184 12.1302 12.994 11.4873 12.5436 11.4861L9.97958 11.4792C9.70033 11.4785 9.47344 11.7448 9.47282 12.0741L9.46698 15.0977C9.46595 15.6289 10.0101 15.8964 10.3293 15.5216L11.017 14.7143Z'
                fill='#333333'
              />
              <path
                d='M7.19652 7.03602L2.70886 1.71978C2.61036 1.6031 2.45028 1.60267 2.35134 1.71882L1.51522 2.70032C1.41628 2.81647 1.41591 3.00525 1.51441 3.12193L6.00207 8.43816L5.31438 9.24543C4.99513 9.62019 5.21947 10.263 5.66993 10.2642L8.23392 10.2711C8.51317 10.2719 8.74006 10.0055 8.74068 9.67626L8.74652 6.65269C8.74755 6.12151 8.20344 5.85401 7.88419 6.22877L7.19652 7.03602Z'
                fill='#333333'
              />
            </svg>
          </button>
        </div>
        <div className='rd-px-20 rd-py-15'>
          <h4 className='heading-4 rd-mb-10'>{title}</h4>
          <div className='rd-body-text'>{parsedContent}</div>
        </div>
      </div>
    );
  }
);

const NoticeTabs = ({
  setActiveTab,
  setIsOpen,
  isExpanded,
  setIsExpanded,
  activeTab,
  selectedNotice: initialSelectedNotice,
  setShowActiveFeatured,
  logo,
}) => {
  const [selectedNotice, setSelectedNotice] = useState(initialSelectedNotice);
  const [showHeaderFooter, setShowHeaderFooter] = useState(true);
  const [notices, setNotices] = useState([]);
  const [loading, setLoading] = useState();
  const [error, setError] = useState();

  const { loadingContext, errorContext, noticesContext } = useAppData();

  useEffect(() => {
    setLoading(loadingContext);
    setError(errorContext);
    if (!loading) {
      const formattedNotices = noticesContext.map((notice) => ({
        id: notice.id,
        title: notice.title.rendered,
        content: notice.content.rendered,
        excerpt: DOMPurify.sanitize(notice.excerpt.rendered, {
          ADD_TAGS: ['p', 'strong', 'em'],
          ADD_ATTR: [],
        }),
      }));
      setNotices(formattedNotices);
    }
  }, []);

  // Sync selectedNotice with initialSelectedNotice
  useEffect(() => {
    setSelectedNotice(initialSelectedNotice);
  }, [initialSelectedNotice]);

  // Update header/footer visibility based on selectedNotice
  useEffect(() => {
    setShowHeaderFooter(!selectedNotice);
  }, [selectedNotice]);

  const handleBackClick = () => {
    setSelectedNotice(null);
    setIsExpanded(false);
  };

  return (
    <ErrorBoundary>
      <div className='rd-flex rd-flex-col rd-h-full'>
        {error ? (
          <p className='rd-text-red rd-body-text rd-text-center'>
            Error: {error}
          </p>
        ) : selectedNotice ? (
          <SingleNoticeView
            notice={selectedNotice}
            onBack={handleBackClick}
            isExpanded={isExpanded}
            setIsExpanded={setIsExpanded}
            logo={logo}
          />
        ) : (
          <>
            <div className='rd-main-content'>
              {showHeaderFooter && (
                <>
                  <Header logo={logo} onClose={() => setIsOpen(false)} />
                  <div className='rd-text-center rd-px-20 rd-flex rd-flex-col rd-gap-5 rd-mb-20 notice-tab-title home-tab-title'>
                    <h4 className='heading-4'>Notices</h4>
                    <h5 className='sub-text'>
                      Stay updated with our latest announcements.
                    </h5>
                  </div>
                </>
              )}
              <div className='rd-px-18 rd-mt-20 rd-mb-15 rd-overflow-h-320 notice-tab-content'>
                <div className='rd-flex rd-flex-col rd-gap-20 featured-faq'>
                  {loading ? (
                    <p className='rd-body-text rd-text-center'>
                      Loading notices...
                    </p>
                  ) : notices.length === 0 ? (
                    <p className='rd-body-text'>No notices found.</p>
                  ) : (
                    notices.map((notice) => (
                      <NoticeItem
                        key={notice.id}
                        notice={notice}
                        onSelect={setSelectedNotice}
                      />
                    ))
                  )}
                </div>
              </div>
            </div>
            {showHeaderFooter && (
              <Footer activeTab={2} setActiveTab={setActiveTab} />
            )}
          </>
        )}
      </div>
    </ErrorBoundary>
  );
};

export default NoticeTabs;

// export default NoticeTabs;
