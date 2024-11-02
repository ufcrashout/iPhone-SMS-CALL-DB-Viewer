## iPhone SMS and CALL DB/History Viewer

A PHP web application that allows users to browse, filter, and analyze iPhone call and SMS data stored in SQLite databases. The application provides insights into call and message histories, including filtering, sorting, and detailed statistics on communications with individual contacts.

### Features

#### 1. **User Authentication**
   - **Login System**: Users must log in to access call and message data.
   - **Session Management**: Only logged-in users can view the call and message history.

#### 2. **Messages Page (`index.php`)**
   - **Conversations by Contact**:
     - Separate tabs for each conversation, showing messages grouped by individual or group contacts.
     - Tab labels display contact names (if available) along with phone numbers in parentheses.
   - **Message Viewing**:
     - Messages are displayed with iOS-style chat bubbles.
     - Incoming messages use a light background, and outgoing messages use a blue background, mirroring the iOS Messages app.
     - Each message shows the timestamp in Apple's epoch format converted to readable time.
   - **Message Statistics**:
     - Each conversation tab shows statistics for total incoming and outgoing messages.
   - **Search Functionality**:
     - Users can search for keywords across all conversations.
     - Only messages matching the search term are displayed in their respective conversations.
   - **Pagination**:
     - Allows for navigation through large conversations in multiple pages.
   
#### 3. **Call History Page (`calls.php`)**
   - **Call Records Table**:
     - Displays call records with the following details:
       - Contact name (if available) and phone number.
       - Call type (Phone Call, FaceTime, Facebook Audio/Video, or Unknown).
       - Date and time of the call.
       - Call duration (in seconds).
       - Additional information, including service provider and country code.
     - Columns are sortable by contact, call type, date, and duration.
   - **Filtering Options**:
     - Users can filter call records by:
       - **Phone Number**: View only calls with a specific contact.
       - **Call Type**: Filter by specific call types (e.g., Phone Call, FaceTime).
       - **Date**: Filter records by specific dates.
   - **Call Statistics**:
     - When viewing a specific phone number:
       - Displays the total number of calls, number of outgoing and incoming calls, and total talk time for each.
   - **Clickable Contacts**:
     - Phone numbers in the call log are clickable, allowing users to filter by individual contact and view their call history and statistics.
   - **Pagination**:
     - Allows users to navigate through call records in multiple pages.

#### 4. **Data Processing and Display**
   - **Data Conversion**:
     - Converts Apple’s epoch timestamps to readable date formats for display in both messages and call logs.
   - **Cached Contact Lookup**:
     - Caches contact names to optimize performance when displaying multiple records from the same contact.
   - **Automatic Table Sorting**:
     - Allows sorting of call records by date, duration, and contact.
   - **Efficient Database Queries**:
     - Limits the number of records displayed per page to ensure optimal performance with large datasets.

#### 5. **User Interface and Design**
   - **iOS-Inspired Design**:
     - Clean and simple design inspired by iOS styling, with iOS-like message bubbles.
   - **Responsive Tabs for Conversations**:
     - Each conversation displays on a separate tab, with smooth transitions between conversations.
   - **Responsive Layout for Desktop and Mobile**:
     - Designed for usability across devices.

---

### Future Enhancements
- **Advanced Search Filters**: Adding more granular filters for message and call searches, such as filtering by date ranges or message content.
- **Export Data**: Options to export call and message histories as CSV or PDF.
- **Analytics Dashboard**: Visual representations of call durations and messaging frequency with individual contacts over time.
- **Contact Management**: Editable contact names, allowing manual adjustments of displayed names for better clarity.
