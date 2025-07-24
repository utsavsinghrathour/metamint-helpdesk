## 🧠 SERVER SENT EVENTS OPTIMIZATION
- [ ] As soon as the admin makes a comment on backend we can save some data on user meta 
- [ ] This data can be used on sse function 
- [ ] If there is data lets inititae sse other wise close it
- [ ] If there is  no data at first but data came on the middle 


#### SSE FUNCTION

- [ ] Get user id from client
- [ ] Check if user has unseen comments from user meta 
- [ ] If user have unseen comments lets provide the unseen comments to the client
- [ ] As soon as these unseen comments are provide on the front update them as seen


## 🧠 NOTIFICATION SYSTEM FOR ROBODESK CHAT APP

### Saving unseen comments  
- [ ] When comment is posted by admin save meta data as below.
    - [ ] The comments meta_key name must be `rbd_unseen`
    - [ ] The value of the `rbd_unseen` meta key must be `true`
- [ ] On robodesk ticket rest api filter count `rbd_unseen` comments .
    - [ ] The rest api key must `unseen_comments`.

### Updating seen messages 

- [might be slow ] As soon as the tickets are opened update those comments whose `rbd_unseen` is  true to false.

### Displaying whole unseen message count 
    
- [ ] Count all unseen messages by userId and display the count 


