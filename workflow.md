## 🧠 Notification System for Robodesk Chat App

- When a support manager submits a comment from the backend:
  - Set the comment’s meta key: `rbd_seen => false`.
  - Update the number of unseen comments for the related **Robodesk Ticket** post type.
  - Also, update the total count of unseen comments across all tickets.

- When a user opens a ticket from the **Tickets** tab:
  - Mark all comments in the selected ticket as seen using a custom REST API.

---

## 🔧 Other Remaining Issues and Tasks

- Enable attachment uploads in the **Messages** section.
- Fix slow loading in the **Wikis** section (currently shows a white screen with no loader).
- Load the latest 6 messages when tickets are fetched from the **Tickets** tab.
- Display those messages immediately in the **Single Ticket** view upon opening.
  - **Note:** Handle pagination properly.
- When scrolling to the top of the message list:
  - Older messages are loaded, but the view automatically scrolls to the bottom.
  - **Fix:** Prevent auto-scroll to bottom when loading older messages.

## Tracking Pixels

A tracking pixel is a tiny invisible image (usually 1x1) embedded in an email.
When the email client loads that image URL, your server receives a request.
That request is treated as an “email opened/read” signal and you can log the timestamp.
Your email now includes a pixel URL with comment_id + a unique token.
When that URL is requested, the AJAX handler validates the token, records first read time, and adds an internal ticket note.
This gives you send status + read status history directly in ticket notes.
