import { createContext, useReducer, useEffect, useMemo } from 'react';

// Define action types for the reducer
const LOAD_FEATUREDFAQS = 'LOAD_FEATUREDFAQS';
const LOAD_NOTICES = 'LOAD_NOTICES';
const LOAD_CONVERSATIONS = 'LOAD_CONVERSATIONS';
const EDIT_CONVERSATION = 'EDIT_CONVERSATION';
const ADD_CONVERSATION = 'ADD_CONVERSATION';
const LOAD_WIKIS = 'LOAD_WIKIS';
const SET_LOADING = 'SET_LOADING';
const SET_ERROR = 'SET_ERROR';
const SET_LOGIN_STATUS = 'SET_LOGIN_STATUS';

// Initial state
const initialState = {
  userStatus: { loggedIn: false, loggedInUserId: null },
  featuredfaqs: { items: [] },
  notices: { items: [] },
  conversations: { items: [] },
  wikis: { items: [] },
  loading: false,
  error: null,
};

// Reducer function to handle state updates
function appDataReducer(state, action) {
  switch (action.type) {
    case LOAD_FEATUREDFAQS:
      return { ...state, featuredfaqs: { items: action.payload } };
    case LOAD_NOTICES:
      return { ...state, notices: { items: action.payload } };
    case LOAD_CONVERSATIONS:
      return { ...state, conversations: { items: action.payload } };
    case ADD_CONVERSATION:
      return {
        ...state,
        conversations: {
          items: [...state.conversations.items, action.payload],
        },
      };
    case EDIT_CONVERSATION:
      return {
        ...state,
        conversations: {
          items: state.conversations.items.map((item) =>
            item.id === action.payload.id
              ? { ...item, ...action.payload }
              : item
          ),
        },
      };
    case LOAD_WIKIS:
      return { ...state, wikis: { items: action.payload } };

    case SET_LOGIN_STATUS:
      return {
        ...state,
        loggedIn: action.payload.loggedIn,
        loggedInUsrId: action.payload.loggedInUsrId,
      };
    case SET_LOADING:
      return { ...state, loading: action.payload };
    case SET_ERROR:
      return { ...state, error: action.payload };
    default:
      return state;
  }
}

// Create the context with default values
export const AppContext = createContext({
  featuredfaqs: { items: [] },
  notices: { items: [] },
  conversations: { items: [], updateConversations: () => {} },
  wikis: { items: [] },
  loggedIn: false,
  loggedInUsrId: null,
  loading: false,
  error: null,
});

export default function AppContextProvider({ children }) {
  const [state, dispatch] = useReducer(appDataReducer, initialState);

  const updateConversations = (actionType, payload) => {
    switch (actionType) {
      case 'EDIT':
        dispatch({ type: EDIT_CONVERSATION, payload });
        break;

      default:
        dispatch({
          type: SET_ERROR,
          payload: 'Invalid action type for updateConversations',
        });
    }
  };

  // Check sessionStorage for login status on mount
  useEffect(() => {
    const checkLoginStatus = () => {
      const storedData = sessionStorage.getItem('wpUserData');
      if (storedData) {
        try {
          const parsedData = JSON.parse(storedData);
          if (parsedData.isLoggedIn && parsedData.loggedInUserId) {
            dispatch({
              type: SET_LOGIN_STATUS,
              payload: {
                loggedIn: true,
                loggedInUsrId: parsedData.loggedInUserId,
              },
            });
            return parsedData.loggedInUserId;
          }
        } catch (error) {
          console.error('Error parsing wpUserData from sessionStorage', error);
        }
      }
      dispatch({
        type: SET_LOGIN_STATUS,
        payload: {
          loggedIn: false,
          loggedInUsrId: null,
        },
      });
      return null;
    };

    checkLoginStatus();
  }, []);

  // Fetch data from WordPress REST API
  useEffect(() => {
    const fetchData = async () => {
      dispatch({ type: SET_LOADING, payload: true });
      dispatch({ type: SET_ERROR, payload: null });

      try {
        // Prepare API requests (exclude conversations if not logged in)
        const requests = [
          fetch(
            '/wp-json/wp/v2/mmhd_faq?featured=true&_fields=id,title.rendered,excerpt.rendered,content.rendered'
          ),
          fetch(
            '/wp-json/wp/v2/notice?_fields=id,title.rendered,excerpt.rendered,content.rendered'
          ),
        ];

        // Add conversations request only if logged in
        if (state.loggedIn && state.loggedInUsrId) {
          requests.push(
            fetch(
              `/wp-json/wp/v2/mmhd_ticket?author=${state.loggedInUsrId}&page=1&per_page='4'&orderby=date&order=desc`
            )
          );
        } else {
          // Set empty conversations if not logged in
          dispatch({ type: LOAD_CONVERSATIONS, payload: [] });
        }

        const [featuredfaqsRes, noticesRes, wikisRes, conversationsRes] =
          await Promise.all(requests);

        // Validate responses (conversationsRes may be undefined if not logged in)
        if (
          !featuredfaqsRes.ok ||
          !noticesRes.ok ||
          !wikisRes.ok ||
          (conversationsRes && !conversationsRes.ok)
        ) {
          throw new Error('Failed to fetch data');
        }

        const featuredfaqsData = await featuredfaqsRes.json();
        const noticesData = await noticesRes.json();
        const wikisData = await wikisRes.json();

        dispatch({ type: LOAD_FEATUREDFAQS, payload: featuredfaqsData });
        dispatch({ type: LOAD_NOTICES, payload: noticesData });
        dispatch({ type: LOAD_WIKIS, payload: wikisData });

        // Process conversations only if fetched
        if (conversationsRes) {
          const conversationsData = await conversationsRes.json();
          dispatch({ type: LOAD_CONVERSATIONS, payload: conversationsData });
        }
      } catch (err) {
        dispatch({ type: SET_ERROR, payload: err.message });
      } finally {
        dispatch({ type: SET_LOADING, payload: false });
      }
    };

    fetchData();
  }, [state.loggedIn, state.loggedInUsrId]);

  // Memoize context value to avoid unnecessary re-renders
  const appDataContextValue = useMemo(
    () => ({
      ...state,
      conversations: {
        ...state.conversations,
        updateConversations,
      },
    }),
    [state]
  );

  return (
    <AppContext.Provider value={appDataContextValue}>
      {children}
    </AppContext.Provider>
  );
}
