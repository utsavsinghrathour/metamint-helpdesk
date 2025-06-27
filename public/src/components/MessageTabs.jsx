import React, { useState, useEffect, Suspense } from "react";
import { FaChevronRight, FaChevronLeft } from "react-icons/fa";
import "../styles/MessageTabs.css";
import Author1 from "../images/person1.jpeg";
import Author2 from "../images/person2.jpeg";
import { LuDot } from "react-icons/lu";
import { RiPencilFill } from "react-icons/ri";
import Footer from "./Footer";
import Login from "./Login";
import Preloader from "../images/preloader.svg";
import axios from "axios"; // For making API requests
import { userStatus } from "../utils/modules";
// import { createTicket } from "../utils/modules";

const SingleTicket = React.lazy(() => import("./SingleTicket"));

const TicketItem = ({ ticket, onSelect }) => {

// console.log(ticket);

  return (
    <div
      className="rd-hover single-rd-ticket rd-flex rd-align-center rd-gap-8 rd-border-bottom-light rd-cursor-pointer single-rd-active-ticket"
      onClick={() => onSelect(ticket)}
    >
      <div>
        <img
          src={ticket.authorImage}
          alt="User Image"
          className="rd-rounded-border rd-object-cover"
          style={{ height: "30px", width: "30px" }}
        />
      </div>
      <div className="rd-flex rd-justify-between rd-w-full">
        <div className="rd-message-list-wrap">
          <h5 className="sub-text rd-title-color">{ticket.title}</h5>
          <div className="rd-text-small rd-mt-5 rd-flex rd-gap-5">
            <span className="rd-flex">
              {ticket.source} <LuDot /> {ticket.timeAgo}
            </span>
          </div>
          <div className="rd-flex rd-align-center rd-gap-10 rd-mt-10">
            <span
              className={`rd-priority rd-fw-medium ${
                ticket.priority === "Urgent"
                  ? "rd-urgent"
                  : ticket.priority === "Medium"
                  ? "rd-medium"
                  : "rd-low"
              }`}
            >
              {ticket.priority}
            </span>
            <span className="rd-text-small rd-fw-medium">
              (#{ticket.ticketNumber})
            </span>
          </div>
        </div>
        <FaChevronRight />
      </div>
    </div>
  );
};

const MessageTabs = ({
  setActiveTab,
  setIsOpen,
  isExpanded,
  setIsExpanded,
  activeTab,
}) => {
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedTicket, setSelectedTicket] = useState(null);
  const [currentTab, setCurrentTab] = useState("all");
  const [priorityFilter, setPriorityFilter] = useState("all");
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isLoggedIn, setIsLoggedIn] = useState(false); // Fixed naming consistency
  const [loggedInuserData , setloggedInuserData] = useState(false);

  const handleLogin = (loginStatus) => {
    setIsLoggedIn(loginStatus);
    console.log('the login status', loginStatus);
    if (loginStatus) {
      fetchTickets();
    }
  };
  
  useEffect(() => {
  const usStat = userStatus();

  if (usStat.isLoggedIn && usStat.loggedInUserId) {
    setloggedInuserData(usStat.loggedInUserId);
    setIsLoggedIn(true);
    fetchTickets();
  } else {
    const userDetails = sessionStorage.getItem('user_details');
    if (userDetails) {
      try {
        const parsedData = JSON.parse(userDetails);
        if (parsedData && parsedData.user_id) {
          setloggedInuserData(parsedData.user_id);
          setIsLoggedIn(true);
          fetchTickets(); 
        }
      } catch (error) {
        console.error('Error parsing user_details from sessionStorage', error);
      }
    }
  }
}, [isLoggedIn,loggedInuserData]);

                       
//   useEffect(() => {
  
//     const usrStat = userStatus();
    
//     console.log('saved user stata', usrStat);
    
//     const userDetails = sessionStorage.getItem('user_details');
//     // const savedUserDetails = 
//     if (userDetails) {
//       try {
//         const parsedData = JSON.parse(userDetails);
//         if (parsedData && parsedData.user_id) {
//           setloggedInuserData(parsedData.user_id);
//           setIsLoggedIn(true);
//           fetchTickets();
//         }
//       } catch (error) {
//         console.error('Error parsing user_details from sessionStorage', error);
//       }
//     }
//   },[isLoggedIn]);                     

//   useEffect(() => {
  
  
//     if (isLoggedIn) {
//       fetchTickets();
//     }
//   }, []);
  
//   const fetchTickets = async () => {
//     // console.log('Logged In user:',loggedInuserData);
//     if (!loggedInuserData) return;
    
//     try {
//       setLoading(true);
//       const response = await axios.get("/wp-json/wp/v2/robodesk_ticket", {
//         params: {
//           per_page: 10,
//          author: loggedInuserData,
//           _embed: "wp:comment", // Only embed comments, as terms are directly included
//         },
//       });

//       const mappedTickets = response.data.map((post) => {
//         // Get priority term (keep embedded logic if priority is not directly included)
//         const priorityTerm =
//           post._embedded?.["wp:term"]?.find((tax) =>
//             tax.some((t) => t.taxonomy === "robodesk_ticket_priority")
//           )?.[0]?.name ||
//           post.robodesk_ticket_priority?.[0] ||
//           "Medium";

//         // Get source (origin) term directly from post
//         const sourceTerm =
//           post.robodesk_ticket_origin && post.robodesk_ticket_origin.length > 0
//             ? post.robodesk_ticket_origin[0]
//             : "Unknown";
//         console.log(
//           `Ticket ID ${post.id} - Origin:`,
//           post.robodesk_ticket_origin,
//           "Selected Source:",
//           sourceTerm
//         );

//         // Get comments (ticket replies)
//         const messages =
//           post._embedded?.["wp:comment"]
//             ?.filter((comment) => comment.type === "ticket_replies")
//             ?.map((comment) => ({
//               id: comment.id,
//               text: comment.content.rendered.replace(/<[^>]+>/g, ""),
//               sender: comment.author === 0 ? "me" : "other",
//               image: comment.author === 0 ? Author1 : Author2,
//             })) || [];

//         // Calculate time ago
//         const postDate = new Date(post.date);
//         const now = new Date();
//         const diffInSeconds = Math.floor((now - postDate) / 1000);
//         let timeAgo = "Just now";
//         if (diffInSeconds >= 60 * 60 * 24 * 7) {
//           timeAgo = `${Math.floor(diffInSeconds / (60 * 60 * 24 * 7))}w ago`;
//         } else if (diffInSeconds >= 60 * 60 * 24) {
//           timeAgo = `${Math.floor(diffInSeconds / (60 * 60 * 24))}d ago`;
//         } else if (diffInSeconds >= 60 * 60) {
//           timeAgo = `${Math.floor(diffInSeconds / (60 * 60))}h ago`;
//         } else if (diffInSeconds >= 60) {
//           timeAgo = `${Math.floor(diffInSeconds / 60)}m ago`;
//         }

//         // Determine status
//         const status = post.ticket_status || "new";

//         return {
//           id: post.id,
//           ticketNumber:
//             post.ticket_number || `TCKT-${String(post.id).padStart(3, "0")}`,
//           title: post.title.rendered,
//           source: sourceTerm,
//           timeAgo,
//           authorImage:
//             post.author_image || (post.id % 2 === 0 ? Author1 : Author2),
//           status,
//           priority: priorityTerm,
//           messages,
//         };
//       });

//       setTickets(mappedTickets);
     
//     } catch (error) {
//       console.error("Error fetching tickets:", error);
//       setLoading(false);
//     }
//   };

const fetchTickets = async () => {
  if (!loggedInuserData) return; // Exit early if user is not logged in
    console.log('We are here ', loggedInuserData);
  try {
    setLoading(true);
    const response = await axios.get("/wp-json/wp/v2/robodesk_ticket", {
      params: {
        per_page: 100,
        author: loggedInuserData,  // 👈 Fetch tickets by the user ID
        _embed: "wp:comment",      // Embed comments only
      },
    });

    const mappedTickets = response.data.map((post) => {
    
  
      const priorityTerm =
        post._embedded?.["wp:term"]?.find((tax) =>
          tax.some((t) => t.taxonomy === "robodesk_ticket_priority")
        )?.[0]?.name ||
        post.robodesk_ticket_priority?.[0] ||
        "Medium";

      const sourceTerm =
        post.robodesk_ticket_origin && post.robodesk_ticket_origin.length > 0
          ? post.robodesk_ticket_origin[0]
          : "Unknown";

      const messages =
        post._embedded?.["wp:comment"]
          ?.filter((comment) => comment.type === "ticket_replies")
          ?.map((comment) => ({
            id: comment.id,
            text: comment.content.rendered.replace(/<[^>]+>/g, ""),
            sender: comment.author === 0 ? "me" : "other",
            image: comment.author === 0 ? Author1 : Author2,
          })) || [];

      const postDate = new Date(post.date);
      const now = new Date();
      const diffInSeconds = Math.floor((now - postDate) / 1000);
      let timeAgo = "Just now";
      if (diffInSeconds >= 60 * 60 * 24 * 7) {
        timeAgo = `${Math.floor(diffInSeconds / (60 * 60 * 24 * 7))}w ago`;
      } else if (diffInSeconds >= 60 * 60 * 24) {
        timeAgo = `${Math.floor(diffInSeconds / (60 * 60 * 24))}d ago`;
      } else if (diffInSeconds >= 60 * 60) {
        timeAgo = `${Math.floor(diffInSeconds / (60 * 60))}h ago`;
      } else if (diffInSeconds >= 60) {
        timeAgo = `${Math.floor(diffInSeconds / 60)}m ago`;
      }

      const status = post.ticket_status || "new";

      return {
        id: post.id,
        ticketNumber:
          post.ticket_number || `TCKT-${String(post.id).padStart(3, "0")}`,
        title: post.title.rendered,
        source: sourceTerm,
        timeAgo,
        authorImage:post.robodesk_ticket_author_image,
        status,
        priority: priorityTerm,
        messages,
      };
    });
    console.log('THe final ticket data is' , mappedTickets);
    setTickets(mappedTickets);
    setLoading(false);
  } catch (error) {
    console.error("Error fetching tickets:", error);
    setLoading(false);
  }
};

  
  const handleBackToHome = () => {
    console.log("handleBackToHome called");
    if (setActiveTab) {
      setActiveTab(0);
      setIsExpanded(false);
    }
  };

  const handleAddTicket = async () => {
    try {
      const defaultOrigin = "Web"; // Use a valid term slug (e.g., 'Web', 'Email')

      const response = await axios.post(
        "/wp-json/wp/v2/robodesk_ticket",
        {
          title: "New Ticket",
          status: "publish",
          ticket_status: "new",
          ticket_number: `TCKT-${String(tickets.length + 1).padStart(3, "0")}`,
          tax_input: {
            robodesk_ticket_priority: "Medium",
            robodesk_ticket_origin: defaultOrigin,
          },
        },
        {
          headers: {
            "X-WP-Nonce": window.wpApiSettings.nonce,
          },
        }
      );

      // Use the origin directly from the response
      const sourceTerm =
        response.data.robodesk_ticket_origin &&
        response.data.robodesk_ticket_origin.length > 0
          ? response.data.robodesk_ticket_origin[0]
          : defaultOrigin;

      const newTicket = {
        id: response.data.id,
        ticketNumber:
          response.data.ticket_number ||
          `TCKT-${String(tickets.length + 1).padStart(3, "0")}`,
        title: response.data.title.rendered,
        source: sourceTerm,
        timeAgo: "Just now",
        authorImage: Author1,
        status: "new",
        priority: "Medium",
        messages: [],
      };

      setTickets([newTicket, ...tickets]);
      setSelectedTicket(newTicket);
    } catch (error) {
      console.error("Error creating ticket:", error);
    }
  };
  
//   const handleLogindata = () => {
  
//   }
    console.log(tickets);
  const filteredTickets = tickets.filter((ticket) => {
    const matchesTab = currentTab === "all" || ticket.status === currentTab;
    const matchesPriority =
      priorityFilter === "all" || ticket.priority === priorityFilter;
    const matchesSearch =
      ticket.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      ticket.ticketNumber.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesTab && matchesPriority && matchesSearch;
  });
  
  console.log(filteredTickets);
  console.log(isLoggedIn);
    
  return (
    <div className="rd-main-content">
      {isLoggedIn ? (
        <>
          {selectedTicket ? (
            <Suspense
              fallback={
                <img src={Preloader} alt="Loading" className="rd-preloader" />
              }
            >
              <SingleTicket
                ticket={selectedTicket}
                onBack={() => setSelectedTicket(null)}
              />
            </Suspense>
          ) : (
            <>
              <div className="rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col">
                <h5 className="heading-5 rd-text-white rd-text-center">
                  Messages
                </h5>
                <button
                  className="back-button rd-flex rd-items-center rd-gap-1 rd-border-none rd-bg-transparent rd-m-0 rd-p-0"
                  onClick={handleBackToHome}
                >
                  <FaChevronLeft />
                </button>
              </div>
              <div className="rd-message-tab-content">
                {/* Search and Priority Filter */}
                <div className="rd-px-18 rd-py-15 rd-flex rd-gap-10 rd-align-center rd-justify-between">
                  <input
                    type="text"
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    placeholder="Search for ticket"
                    className="rd-border-light rd-border-radius-md rd-p-10 rd-color-gray rd-fw-medium flex-1"
                  />
                  <select
                    value={priorityFilter}
                    onChange={(e) => setPriorityFilter(e.target.value)}
                    className="rd-border-light rd-p-10 rd-body-text rd-border-radius-md rd-color-gray rd-fw-medium flex-1"
                  >
                    <option value="all">All Priorities</option>
                    <option value="Urgent">Urgent</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                  </select>
                </div>

                {/* Tabs */}
                <div className="rd-flex rd-justify-between rd-py-10 rd-px-20 rd-gap-10 rd-border-bottom-light">
                  <select
                    value={currentTab}
                    onChange={(e) => setCurrentTab(e.target.value)}
                    className="rd-p-10 rd-py-5 rd-border-light rd-border-radius-md rd-body-text ticket-tab rd-color-gray rd-fw-medium"
                  >
                    <option value="all">All Tickets</option>
                    <option value="new">New</option>
                    <option value="ongoing">On Going</option>
                    <option value="resolved">Resolved</option>
                  </select>
                  <button
                    className={`rd-hover-underline rd-p-0 rd-cursor-pointer rd-flex rd-gap-8 rd-align-center rd-color-gray rd-title-text ticket-tab rd-px-10 rd-py-5 rd-bg-transparent rd-border-none ${
                      currentTab === "new"
                        ? "rd-text-blue rd-font-bold active-ticket-tab"
                        : "rd-text-gray"
                    }`}
                    onClick={() => setCurrentTab("new")}
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="18"
                      height="18"
                      viewBox="0 0 21 20"
                      fill="none"
                    >
                      <path
                        d="M19.0448 8.75V12.9167C19.0448 15.8333 17.3738 17.0833 14.8673 17.0833H6.51223C4.00572 17.0833 2.33472 15.8333 2.33472 12.9167V7.08333C2.33472 4.16666 4.00572 2.91666 6.51223 2.91666H12.3608"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M6.51221 7.5L9.12734 9.58333C9.9879 10.2667 11.3999 10.2667 12.2605 9.58333L13.2464 8.8"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M16.956 6.66667C18.1096 6.66667 19.0448 5.73393 19.0448 4.58333C19.0448 3.43274 18.1096 2.5 16.956 2.5C15.8024 2.5 14.8672 3.43274 14.8672 4.58333C14.8672 5.73393 15.8024 6.66667 16.956 6.66667Z"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                    New
                  </button>
                  <button
                    className={`rd-hover-underline rd-p-0 rd-cursor-pointer ticket-tab rd-flex rd-gap-8 rd-align-center rd-color-gray rd-title-text rd-px-10 rd-py-5 rd-bg-transparent rd-border-none ${
                      currentTab === "ongoing"
                        ? "rd-text-blue rd-font-bold active-ticket-tab"
                        : "rd-text-gray"
                    }`}
                    onClick={() => setCurrentTab("ongoing")}
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="18"
                      height="18"
                      viewBox="0 0 21 20"
                      fill="none"
                    >
                      <path
                        d="M1.99854 7.08333C1.99854 4.16666 3.66954 2.91666 6.17605 2.91666H14.5311C17.0376 2.91666 18.7086 4.16666 18.7086 7.08333V12.9167C18.7086 15.8333 17.0376 17.0833 14.5311 17.0833H6.17605"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M14.5311 7.5L11.9159 9.58333C11.0554 10.2667 9.64336 10.2667 8.78279 9.58333L6.17603 7.5"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M1.99854 13.75H7.01155"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M1.99854 10.4167H4.50504"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                    On Going
                  </button>
                </div>

                {/* Ticket List */}
                <div className="rd-messge-wrapper">
                  <div className="rd-tickets">
                    <div className="rd-active-tickets-wrap">
                      {loading ? (
                        <img
                          src={Preloader}
                          alt="Loading"
                          className="rd-preloader"
                        />
                      ) : filteredTickets.length > 0 ? (
                        filteredTickets.map((ticket) => (
                          <TicketItem
                            key={ticket.id}
                            ticket={ticket}
                            onSelect={setSelectedTicket}
                          />
                        ))
                      ) : (
                        <p className="rd-text-center rd-text-gray rd-m-5 err_msg">
                          No tickets found
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                <div className="rd-send-message rd-text-center rd-px-18 rd-py-15 rd-flex rd-justify-center">
                  <button
                    className="button no-bg rd-flex rd-align-center rd-gap-10 rd-cursor-pointer"
                    onClick={handleAddTicket}
                  >
                    Add Ticket
                    <RiPencilFill />
                  </button>
                </div>
              </div>
              {/* <Footer activeTab={activeTab} setActiveTab={setActiveTab} /> */}
            </>
          )}
        </>
      ) : (
       <Login setShowLogin={handleLogin} />
      )}
      <Footer activeTab={activeTab} setActiveTab={setActiveTab} />
    </div>
  );
};

export default MessageTabs;
