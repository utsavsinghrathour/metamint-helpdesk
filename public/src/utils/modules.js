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
  
//   For user login
  export const login = async(emailAddress , password) => {
    const response = await fetch('/wp-json/custom/v1/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: emailAddress,
        password: password,
      }),
    });
    const data = await response.json();
    return data;
  }         
  
//for user signup

//for forgot password
  
  
  