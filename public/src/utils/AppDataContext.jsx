import { createContext, useEffect, useState, useContext } from 'react';

const AppDataContext = createContext();

export const AppDataProvider = ({ children }) => {
  const [data, setData] = useState({
    loadingContext: true,
    errorContext: null,
    loggedinContext: false,
    loggedInUsrId: null,
    ticketsContext: [],
    noticesContext: [],
  });

  const updateLoginStatus = (isLoggedIn, userId) => {
    setData((prev) => ({
      ...prev,
      loggedinContext: isLoggedIn,
      loggedInUsrId: userId,
    }));
  };

  // Ticket fetching logic moved outside to reuse
  const fetchTickets = async (usrId , page=1 ,perPage=4) => {
    if (!usrId) return;
    try {
      setData((prev) => ({ ...prev, loadingContext: true, errorContext: null }));
      const response = await fetch(`/wp-json/wp/v2/robodesk_ticket?author=${usrId}&page=${page}&per_page=${perPage}&orderby=date&order=desc`);
      if (!response.ok) {
        throw new Error(`Failed to fetch ticketsContext: ${response.status} ${response.statusText}`);
      }
      
      const tickets = await response.json();
      
    //   const totalPages = parseInt(response.headers.get("X-WP-TotalPages")) || 1;

      setData((prev) => ({
        ...prev,
        loadingContext: false,
        ticketsContext: tickets,
      }));
    } catch (error) {
    
      setData((prev) => ({
        ...prev,
        loadingContext: false,
        errorContext: error.message || 'Failed to load tickets data',
        ticketsContext: [],
      }));
    }finally{
        // Ensure loading is set to false even if an error occurs
        setData((prev) => ({ ...prev, loadingContext: false }));
    }
  };

  const refreshTickets = () => {
    console.log("Refreshing tickets for user ID:", data.loggedInUsrId);
    if (data.loggedInUsrId) {
      fetchTickets(data.loggedInUsrId);
    }
  };

  useEffect(() => {
    const checkLoginStatus = () => {
      const storedData = sessionStorage.getItem("wpUserData");
      if (storedData) {
        try {
          const parsedData = JSON.parse(storedData);
          if (parsedData.isLoggedIn && parsedData.loggedInUserId) {
            setData((prev) => ({
              ...prev,
              loggedinContext: true,
              loggedInUsrId: parsedData.loggedInUserId,
            }));
            return parsedData.loggedInUserId;
          }
        } catch (error) {
          console.error("Error parsing wpUserData from sessionStorage", error);
        }
      }
      setData((prev) => ({
        ...prev,
        loggedinContext: false,
        loggedInUsrId: null,
      }));
      return null;
    };

    const fetchNotices = async () => {
      try {
        setData((prev) => ({ ...prev, loadingContext: true, errorContext: null }));
        const response = await fetch(
          '/wp-json/wp/v2/notice?_fields=id,title.rendered,excerpt.rendered,content.rendered'
        );
        if (!response.ok) {
          throw new Error(`Failed to fetch noticesContext: ${response.status} ${response.statusText}`);
        }
        const notices = await response.json();
        setData((prev) => ({
          ...prev,
          loadingContext: false,
          noticesContext: notices,
        }));
      } catch (error) {
        console.error("Error fetching noticesContext:", error);
        setData((prev) => ({
          ...prev,
          loadingContext: false,
          errorContext: error.message || 'Failed to load notices data',
          noticesContext: [],
        }));
      }
    };

    fetchNotices();
    checkLoginStatus();
  }, []);

  useEffect(() => {
    if (data.loggedInUsrId) {
      fetchTickets(data.loggedInUsrId);
    }
  }, [data.loggedInUsrId]);

  return (
    <AppDataContext.Provider value={{ ...data, updateLoginStatus, refreshTickets }}>
      {children}
    </AppDataContext.Provider>
  );
};

export const useAppData = () => useContext(AppDataContext);