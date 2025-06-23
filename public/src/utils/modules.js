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