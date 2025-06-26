
export const createTicket = async (userId, title, message = "") => {
  const webhookUrl = "/wp-json/chat/v1/tickets";

  const ticketData = {
    event: "ticket.created",
    data: {
      title: title,
      description: message,
      uid: userId,
    },
  };

  try {
    const response = await fetch(webhookUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(ticketData),
    });

    const responseData = await response.json();
    return responseData;
  } catch (error) {
    console.error("Error in createTicket:", error);
    throw error;
  }
};

export const getMessages = async (ticketId, userId) => {
  const webhookUrl = "/wp-json/chat/v1/tickets/";

  const messageData = {
    event: "comments.get",
    data: {
      ticket_id: ticketId,
      uid: userId,
    },
  };

  try {
    const response = await fetch(webhookUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(messageData),
    });

    const responseData = await response.json();
    return responseData;
  } catch (error) {
    console.error("Error in createTicket:", error);
    throw error;
  }
};

export const userStatus = () => {
    if (typeof wpAuth !== "undefined" && wpAuth.isLoggedIn === "true") {
      return {
        isLoggedIn: true,
        loggedInUserId: wpAuth.loggedInUserId || false,
      };
    }
  
    return {
      isLoggedIn: false,
      loggedInUserId: false,
    };
};
  
  
const cache = {};
export const useCachedFetch = (url) => {
  const [data, setData] = React.useState(null);
  const [error, setError] = React.useState(null);
  const [loading, setLoading] = React.useState(false);

  React.useEffect(() => {
    const fetchData = async () => {
      try {
        if (cache[url]) {
          setData(cache[url]);
           setLoading(false);
          return;
        }

        setLoading(true);
        const res = await fetch(url);
        if (!res.ok) {
          throw new Error('Network response was not ok');
        }
        const json = await res.json();
        cache[url] = json;
        setData(json);
        setLoading(false);
      } catch (err) {
        setError(err.message);
     
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [url]);

  return { data, error, loading };
};