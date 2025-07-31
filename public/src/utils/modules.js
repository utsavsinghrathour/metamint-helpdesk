//create ticket
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
    
    console.log('This is the response from createTicket', responseData);
    return responseData;
  } catch (error) {
    console.error("Error in createTicket:", error);
    throw error;
  }
};

//get all messages
export const  getMessages = async (ticketId, userId) => {
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

//caching states
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
          throw new Error("Network response was not ok");
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


//send messages
export async function sendReply(ticketId, loggedInUserId, message) {
  const payload = {
    event: "replies.receive",
    data: {
      ticket_id: ticketId,
      user_id: loggedInUserId,
      message: message,
    },
  };


// console.log('From modules', payload);
  try {
    const response = await fetch("/wp-json/chat/v1/tickets", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || "Failed to send reply");
    }

    return result;
  } catch (error) {
    console.error("Error sending reply:", error.message);
    throw error;
  }
}

//register user
export async function registerUser(email) {
  try {
    const payload = {
      email: email,
    };
    const response = await fetch("/wp-json/robodesk/v1/signup", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.message || "Failed register");
    }
    return result;
  } catch (error) {
    console.error("Error sending reply:", error.message);
    throw error;
  }
}


//Login user 
export async function loginUser( email, password ) {
  try {
    const payload = {
      email: email,
      password: password
    };
    const response = await fetch("/wp-json/robodesk/v1/login", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.message || "Failed login");
    }
    return result;
  } catch (error) {
    console.error("Error sending reply:", error.message);
    throw error;
  }
}   

//get all tickets
export async function getAllTickets(userId) {
  try {
    const payload = {
      event: 'tickets.get',
      data: {
        uid: userId
      }
    };
    const response = await fetch('/wp-json/chat/v1/tickets', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });
    
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(result.message || 'Failed to fetch tickets');
    }
    
    // Check if the response has the expected structure
    if (result.status === 'success' && Array.isArray(result.data)) {
      return result.data; // Return the formatted tickets array
    } else {
      throw new Error(result.message || 'Invalid response format');
    }
  } catch (error) {
    console.error('Error fetching tickets:', error.message);
    throw error;
  }
}

